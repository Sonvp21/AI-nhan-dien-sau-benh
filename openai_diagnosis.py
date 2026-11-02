"""
Chan doan benh cay bang OpenAI (vision), thay cho Gemini qua WebAI-to-API.

Pipeline GIU NGUYEN nhu ban Gemini (gemini_diagnosis.py), de frontend dung duoc ket qua cu:
  Buoc 1  kiem tra anh co dung la cay nguoi dung chon (crop_match)
  Buoc 2  uoc luong % cay dang co benh/sau hai (disease_probability)
  Buoc 3  liet ke tung benh kem % , dau hieu thay trong CHINH anh nay (signs_in_photo),
          dau hieu nhan biet chung, cach chua, cach phong ngua
Ket qua tra ve cung dang dict voi diagnose_with_gemini() (detections / summary / found ...),
rieng `reference_images` luon rong: OpenAI khong tu tim anh tren web nhu Gemini.

Khac ban Gemini:
  - Gui anh dang base64 thang cho OpenAI, KHONG con luu anh tam + URL cong khai + threadpool.
  - Anh duoc xoay dung chieu (EXIF), thu nho con DIAGNOSIS_IMAGE_MAX_SIDE roi moi gui: it token,
    nhanh hon, ma van du ro de thay dom benh.
  - Ep ket qua theo JSON schema (Structured Outputs) nen khong con loi JSON hong.
  - Do dai cac doan van bi gioi han (toi da 3 benh, 2-3 cau moi muc): thoi gian cho chu yeu la
    thoi gian model viet, nen ngan hon la nhanh hon.

Cau hinh bang bien moi truong (dat trong .env.ai, nap qua EnvironmentFile cua systemd):
  OPENAI_API_KEY               bat buoc
  DIAGNOSIS_MODEL              mac dinh gpt-6-luna (co nhin anh)
  DIAGNOSIS_REASONING_EFFORT   chi ap dung model reasoning (gpt-5*, gpt-6*, o*), mac dinh none = nhanh nhat
  DIAGNOSIS_IMAGE_MAX_SIDE     canh dai nhat sau khi thu nho, mac dinh 1024
  DIAGNOSIS_IMAGE_DETAIL       low | high | auto, mac dinh high (thay dom benh nho tot hon)
  DIAGNOSIS_TIMEOUT            giay cho OpenAI tra loi, mac dinh 60
  OPENAI_BASE_URL              tuy chon (proxy / Azure)
"""

import base64
import io
import json
import logging
import os

from openai import (
    APIConnectionError,
    APIStatusError,
    APITimeoutError,
    AsyncOpenAI,
    AuthenticationError,
    BadRequestError,
    RateLimitError,
)
from PIL import Image, ImageOps

logger = logging.getLogger("agriai.openai")

CROP_VI_NAMES = {
    "che": "Chè",
    "lua": "Lúa",
    "ngo": "Ngô",
    "san": "Sắn",
    "ca_chua": "Cà chua",
    "xoai": "Xoài",
    "ot": "Ớt",
}

HEALTHY_LABEL = "Cây khỏe mạnh"
LEVELS = ["Nhẹ", "Trung bình", "Nặng", "Không có"]  # "Không có" chi dung cho cay khoe
MAX_DISEASES = 3

MODEL = os.environ.get("DIAGNOSIS_MODEL", "gpt-6-luna")
REASONING_EFFORT = os.environ.get("DIAGNOSIS_REASONING_EFFORT", "none")
IMAGE_MAX_SIDE = int(os.environ.get("DIAGNOSIS_IMAGE_MAX_SIDE", "1024"))
IMAGE_DETAIL = os.environ.get("DIAGNOSIS_IMAGE_DETAIL", "high")
TIMEOUT_SECONDS = float(os.environ.get("DIAGNOSIS_TIMEOUT", "60"))
_REASONING_PREFIXES = ("gpt-5", "gpt-6", "o1", "o3", "o4")


class OpenAIDiagnosisError(Exception):
    """Loi chan doan. `status` la ma HTTP tra ve cho trinh duyet, `message` la cau an toan de hien cho nguoi dung
    (chi tiet that cua loi chi ghi vao log server)."""

    def __init__(self, message: str, status: int = 502):
        super().__init__(message)
        self.message = message
        self.status = status


SYSTEM_PROMPT = """\
You are an expert plant pathologist helping Vietnamese farmers. You diagnose crop diseases and \
pests from a photo of leaves, stems or fruit.

Work in EXACTLY this order and fill the JSON fields in the same order:

Step 1 - CROP CHECK: decide whether the photo really shows the crop the user selected, from leaf \
shape, vein pattern, stem and fruit. Set crop_match accordingly and name the crop you actually see \
in detected_crop.

Step 2 - HEALTH CHECK (only meaningful when crop_match is true): look at every visible sign (spots, \
discoloration, wilting, holes, curling, mold, insects, deformed growth) and estimate in \
disease_probability, from 0 to 100, how likely it is that the plant currently has a disease or pest \
problem (0 = completely healthy, 100 = obviously diseased). Base it ONLY on what is visible in this photo.

Step 3 - DISEASE BREAKDOWN: list the diseases or pests whose signs you can recognize, at most 3, most \
likely first. probability is your independent confidence that it is really that disease (the numbers do \
not need to add up to 100). For each one, signs_in_photo must describe the SPECIFIC evidence visible in \
THIS photo (which part of the plant, shape, color, size and spread of the damage), never a generic \
textbook description. If the plant looks healthy, return exactly ONE entry whose disease_name is \
exactly "Cây khỏe mạnh", level is "Không có", and explain in signs_in_photo why it looks healthy.

Rules:
- Never invent what you cannot see. If the photo is blurry, too dark, or too far to judge, say so in \
signs_in_photo and keep probabilities low.
- If crop_match is false: set disease_probability to 0 and return an empty diseases array.
- Every string value must be Vietnamese WITH FULL DIACRITICS, in plain practical words a farmer \
understands, avoiding jargon. Only JSON keys stay in English.
- Keep each text field to 2-3 sentences. symptoms (general, how to recognize this disease on any plant) \
and signs_in_photo (this photo only) must not repeat each other. treatment must be concrete and \
actionable. prevention must be concrete and actionable.
- When you mention a pesticide, name the active ingredient (hoạt chất) and tell the farmer to follow \
the label and the local agricultural extension officer's advice."""

USER_PROMPT = 'The farmer selected the crop: "{crop_name}". Diagnose the attached photo.'


def _schema() -> dict:
    """JSON schema (strict). Thu tu truong = thu tu model viet: kiem tra cay -> suc khoe -> tung benh."""
    disease = {
        "type": "object",
        "properties": {
            "disease_name": {"type": "string"},
            "probability": {"type": "integer"},
            "level": {"type": "string", "enum": LEVELS},
            "pathogen": {"type": "string"},
            "signs_in_photo": {"type": "string"},
            "symptoms": {"type": "string"},
            "treatment": {"type": "string"},
            "prevention": {"type": "string"},
        },
        "required": [
            "disease_name", "probability", "level", "pathogen",
            "signs_in_photo", "symptoms", "treatment", "prevention",
        ],
        "additionalProperties": False,
    }
    return {
        "type": "object",
        "properties": {
            "crop_match": {"type": "boolean"},
            "detected_crop": {"type": "string"},
            "disease_probability": {"type": "integer"},
            "diseases": {"type": "array", "items": disease},
        },
        "required": ["crop_match", "detected_crop", "disease_probability", "diseases"],
        "additionalProperties": False,
    }


def _clamp_percent(value) -> int | None:
    if value is None:
        return None
    try:
        return max(0, min(100, round(float(value))))
    except (TypeError, ValueError):
        return None


def prepare_image(image: Image.Image) -> str:
    """Xoay dung chieu theo EXIF, thu nho, ma hoa JPEG -> data URI base64 de gui cho OpenAI."""
    image = ImageOps.exif_transpose(image).convert("RGB")
    image.thumbnail((IMAGE_MAX_SIDE, IMAGE_MAX_SIDE), Image.Resampling.LANCZOS)
    buffer = io.BytesIO()
    image.save(buffer, format="JPEG", quality=85, optimize=True)
    return "data:image/jpeg;base64," + base64.b64encode(buffer.getvalue()).decode("ascii")


def _chat_params() -> dict:
    """Tham so cho model thuong va model reasoning (khong nhan max_tokens; chi nhan temperature khi effort = none)."""
    if not MODEL.startswith(_REASONING_PREFIXES):
        return {"model": MODEL, "temperature": 0.2, "max_tokens": 2500}
    params = {"model": MODEL, "reasoning_effort": REASONING_EFFORT}
    if REASONING_EFFORT == "none":
        params.update({"temperature": 0.2, "max_completion_tokens": 2500})
    return params


_client: AsyncOpenAI | None = None


def _get_client() -> AsyncOpenAI:
    global _client
    if _client is None:
        api_key = os.environ.get("OPENAI_API_KEY", "").strip()
        if not api_key:
            raise OpenAIDiagnosisError("AI chưa được cấu hình, vui lòng liên hệ quản trị viên.", 503)
        _client = AsyncOpenAI(
            api_key=api_key,
            base_url=os.environ.get("OPENAI_BASE_URL") or None,
            timeout=TIMEOUT_SECONDS,
            max_retries=1,  # tu thu lai 1 lan khi mang chap chon hoac 429/5xx thoang qua
        )
    return _client


def is_configured() -> bool:
    return bool(os.environ.get("OPENAI_API_KEY", "").strip())


async def diagnose_with_openai(image: Image.Image, crop_key: str) -> dict:
    """Chan doan 1 anh cho 1 cay. Tra ve dict cung dang diagnose_with_gemini(); nem OpenAIDiagnosisError khi loi."""
    crop_name = CROP_VI_NAMES.get(crop_key, crop_key)
    data_uri = prepare_image(image)

    try:
        response = await _get_client().chat.completions.create(
            messages=[
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": [
                    {"type": "text", "text": USER_PROMPT.format(crop_name=crop_name)},
                    {"type": "image_url", "image_url": {"url": data_uri, "detail": IMAGE_DETAIL}},
                ]},
            ],
            response_format={
                "type": "json_schema",
                "json_schema": {"name": "plant_diagnosis", "strict": True, "schema": _schema()},
            },
            **_chat_params(),
        )
    except AuthenticationError as exc:
        logger.error("OpenAI tu choi khoa API: %s", exc)
        raise OpenAIDiagnosisError("AI chưa được cấu hình đúng, vui lòng liên hệ quản trị viên.", 503)
    except RateLimitError as exc:
        logger.warning("OpenAI bao qua tai / het han muc: %s", exc)
        raise OpenAIDiagnosisError("Hệ thống AI đang quá tải, vui lòng thử lại sau ít phút.", 503)
    except APITimeoutError:
        logger.warning("OpenAI khong tra loi trong %ss", TIMEOUT_SECONDS)
        raise OpenAIDiagnosisError("AI phản hồi quá lâu, vui lòng thử lại.", 504)
    except APIConnectionError as exc:
        logger.error("Khong ket noi duoc OpenAI: %s", exc)
        raise OpenAIDiagnosisError("Máy chủ chưa kết nối được tới AI, vui lòng thử lại sau.", 503)
    except BadRequestError as exc:
        logger.error("OpenAI tu choi yeu cau (anh/tham so): %s", exc)
        raise OpenAIDiagnosisError("AI không xử lý được ảnh này, vui lòng chụp lại ảnh khác.", 422)
    except APIStatusError as exc:
        logger.error("OpenAI loi %s: %s", exc.status_code, exc)
        raise OpenAIDiagnosisError("AI đang gặp sự cố, vui lòng thử lại sau.", 502)

    choice = response.choices[0]
    if getattr(choice.message, "refusal", None):
        logger.warning("OpenAI tu choi tra loi: %s", choice.message.refusal)
        raise OpenAIDiagnosisError("AI không phân tích được ảnh này, vui lòng chụp lại ảnh khác.", 422)
    try:
        data = json.loads(choice.message.content or "")
    except json.JSONDecodeError:
        # Hau nhu khong xay ra voi schema strict, tru khi bi cat do het max tokens
        logger.error("OpenAI tra JSON hong (finish_reason=%s): %.300s", choice.finish_reason, choice.message.content)
        raise OpenAIDiagnosisError("AI trả kết quả không hợp lệ, vui lòng thử lại.", 502)

    usage = getattr(response, "usage", None)
    if usage:
        logger.info("OpenAI %s: %s token vao, %s token ra", MODEL, usage.prompt_tokens, usage.completion_tokens)

    return _shape(data)


def _shape(data: dict) -> dict:
    """Doi JSON cua model sang dung dang ma app.py / agri-app.js dang doc."""
    crop_match = bool(data.get("crop_match", True))
    detected_crop = (data.get("detected_crop") or "").strip()

    if not crop_match:
        return {
            "crop_mismatch": True,
            "detected_crop": detected_crop,
            "disease_probability": 0,
            "detections": [],
            "detection_count": 0,
            "found": False,
            "summary": None,
            "reference_images": [],
        }

    detections = []
    for d in (data.get("diseases") or []):
        name = (d.get("disease_name") or "").strip()
        if not name:
            continue
        is_healthy = name == HEALTHY_LABEL
        detections.append({
            "disease_key": name,  # khong co ma benh co dinh, dung luon ten lam key
            "disease_name": name,
            "probability": _clamp_percent(d.get("probability")),
            "level": "" if is_healthy else (d.get("level") or ""),
            "bbox": None,  # khong khoanh vung nhu YOLO
            "pathogen": d.get("pathogen", ""),
            "signs_in_photo": d.get("signs_in_photo", ""),
            "symptoms": d.get("symptoms", ""),
            "treatment": d.get("treatment", ""),
            "prevention": d.get("prevention", ""),
            "is_healthy": is_healthy,
        })
    # Sap xep theo % truoc roi moi cat: neu model liet ke benh chac nhat o vi tri thu 4 thi no van duoc giu
    detections.sort(key=lambda x: x["probability"] or 0, reverse=True)
    detections = detections[:MAX_DISEASES]

    if detections:
        top = detections[0]
        summary = {k: top[k] for k in (
            "disease_key", "disease_name", "probability", "level", "pathogen",
            "signs_in_photo", "symptoms", "treatment", "prevention",
        )}
        found = not top["is_healthy"]  # "found" = co BENH that su, khac entry "Cay khoe manh"
    else:
        summary = {
            "disease_key": None, "disease_name": "Không xác định được", "probability": None, "level": None,
            "pathogen": None, "signs_in_photo": None, "symptoms": None, "treatment": None,
            "prevention": "Thử chụp lại ảnh rõ nét hơn, đủ sáng, cận cảnh vùng nghi ngờ có bệnh",
        }
        found = False

    return {
        "crop_mismatch": False,
        "detected_crop": detected_crop,
        "disease_probability": _clamp_percent(data.get("disease_probability")),
        "detections": detections,
        "detection_count": len(detections),
        "found": found,
        "summary": summary,
        "reference_images": [],
    }
