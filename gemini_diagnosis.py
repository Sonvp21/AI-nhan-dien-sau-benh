"""
Chan doan benh cay bang Gemini, thong qua WebAI-to-API dang chay san tren may
(Docker, cong 6969 - xem F:\\gemini2api\\gemini2api\\docker-compose.yml + config.conf).
Day KHONG phai Gemini API chinh thuc co API key - la cach dung lai phien dang nhap
Gemini web ca nhan (cookie), giong het co che dang dung cho du an GIRC (assess_location.py).

LUU Y quan trong (xem README cua WebAI-to-API): du an do ghi ro "intended for research
and educational purposes only" - khong danh cho muc dich thuong mai. Cookie co the het
han theo thoi gian, can dang nhap lai gemini.google.com va cap nhat trong config.conf
(hoac qua http://localhost:6969/admin) neu thay loi upstream.

Cach hoat dong:
  1. app.py luu tam anh upload vao thu muc tinh (StaticFiles), tao ra 1 URL that
     (vd http://host.docker.internal:8000/tmp-images/xxxx.jpg) roi truyen URL do
     vao day - GIONG HET cach assess_location.py dang lam (image_url that, KHONG
     phai base64 data URI). Ly do: WebAI-to-API dung thu vien `gemini-webapi`, ban
     than no tai anh tu URL that ve roi moi upload cho Gemini; da thu voi base64
     data URI truoc do va Gemini tra loi bay (khong thay anh that) vi server
     khong "fetch" duoc scheme "data:", chi fetch duoc http(s) URL.
  2. Goi WebAI-to-API (chuan OpenAI /v1/chat/completions) voi 1 prompt yeu cau Gemini
     lam theo DUNG THU TU 3 buoc (xem PROMPT_TEMPLATE ben duoi):
     - Buoc 1: kiem tra anh co dung la loai cay nguoi dung da chon khong (crop_match).
     - Buoc 2: neu dung cay, tu uoc luong % kha nang cay dang co benh/sau hai
       (disease_probability, 0-100) dua tren dau hieu thay duoc trong anh.
     - Buoc 3: liet ke TAT CA benh/sau hai nhan dien duoc, moi benh kem % kha nang
       DUNG LA benh do (dua tren dau hieu quan sat), cung voi 3 truong chi tiet rieng
       biet: symptoms (dau hieu benh noi chung, de lan sau tu nhan biet), treatment
       (cach chua tri khi da bi), prevention (cach phong ngua truoc khi bi).
  3. Parse JSON Gemini tra ve, chuyen thanh dung format response ma app.py /
     agri-app.js dang doc (detections[] + summary), them cac truong moi
     crop_mismatch / detected_crop / disease_probability de FE canh bao khi chon
     nham cay va hien % benh tong quat.

LUU Y: day la Gemini tu danh gia % dua tren hinh anh (khong phai model ML co so
lieu thong ke thuc), chi mang tinh tham khao truc quan cho nong dan, khong phai
con so ky thuat chinh xac tuyet doi.
"""

import json
import os
import re

from openai import OpenAI

# Local: WebAI-to-API chay Docker CUNG may voi FastAPI nay -> "localhost:6969" goi
# thang duoc. Khi FastAPI deploy len VPS nhung docker WebAI-to-API VAN o lai may
# Windows local (khong dua len VPS) -> "localhost:6969" tren VPS la SAI (do la
# localhost cua chinh VPS, khong phai may Windows), bat buoc phai expose port 6969
# ra internet (vd them 1 Public Hostname nua trong cung Cloudflare Tunnel dang
# dung cho predict.girc-ai.com, tro ve "localhost:6969") roi dat bien moi truong
# WEBAI_BASE_URL tren VPS tro toi domain that do, vd "https://webai.girc-ai.com/v1".
WEBAI_BASE_URL = os.environ.get("WEBAI_BASE_URL", "http://localhost:6969/v1")
WEBAI_API_KEY = "not-needed"  # WebAI-to-API khong can key that, chi can chuoi khong rong
GEMINI_MODEL = "gemini-3.0-pro"

CROP_VI_NAMES = {
    "che": "Chè",
    "lua": "Lúa",
    "ngo": "Ngô",
    "san": "Sắn",
    "ca_chua": "Cà chua",
    "xoai": "Xoài",
    "ot": "Ớt",
}

PROMPT_TEMPLATE = """\
You are an expert plant pathologist who diagnoses crop diseases from photos of \
leaves, stems, or fruit.

The user SELECTED crop: "{crop_name}" (Vietnamese name). A real photo is attached \
to this message.

Analyze the photo in EXACTLY this order:

Step 1 - CROP CHECK: decide whether the photo actually shows a "{crop_name}" plant, \
based on leaf shape, leaf vein pattern, stem, and fruit if visible.

Step 2 - HEALTH CHECK (only meaningful if Step 1 is true): look carefully at every \
visible sign (spots, discoloration, wilting, holes, curling, mold, insects, deformed \
growth...) and estimate, as a percentage from 0 to 100, how likely it is that this \
plant currently has a disease or pest problem (0 = looks completely healthy, 100 = \
very obviously diseased). Base this ONLY on what is actually visible in this photo.

Step 3 - DISEASE BREAKDOWN: identify EVERY disease/pest whose visual signs you can \
recognize in the photo (there can be more than one at once). For each one, give your \
own estimated percentage likelihood that it is really this specific disease, based on \
how well the visible signs match it (these percentages do NOT need to sum to 100, they \
are independent confidence estimates per candidate). Crucially, for each disease you \
must also point out EXACTLY which visual evidence in THIS SPECIFIC PHOTO made you think \
so (signs_in_photo below) - this is what actually justifies the diagnosis to the farmer, \
it must NOT be a generic textbook description disconnected from the actual picture. If \
the plant looks healthy overall, return exactly ONE entry with disease_name exactly \
"Cây khỏe mạnh".

Step 4 - WEB REFERENCE (optional, only if you actually have a working web search \
tool available in this conversation): for the disease you are most confident about, \
you may use web search to look up and view a couple of real reference photos of that \
disease on this crop, purely to help confirm your own diagnosis is consistent with \
known cases. Do NOT type, guess, or invent any image URL yourself anywhere in your \
answer - if you cannot actually search/view real images right now, simply skip this \
step silently and continue; never fabricate a link.

Return ONLY one valid JSON object (no markdown, no extra explanation outside the \
JSON), with EXACTLY this structure:

{{
  "crop_match": <true if the photo really shows "{crop_name}", false if it is clearly a different plant>,
  "detected_crop": "<the crop you actually observe in the photo, in Vietnamese with full diacritics, e.g. Cà chua/Lúa/Ngô/Xoài/Ớt/Chè/Sắn/other>",
  "disease_probability": <integer 0-100 from Step 2; use 0 if crop_match is false>,
  "diseases": [
    {{
      "disease_name": "<disease or pest name, in Vietnamese with full diacritics; or exactly 'Cây khỏe mạnh' if there is no issue>",
      "probability": <integer 0-100 from Step 3 for this specific disease>,
      "level": "<EXACTLY one of: Nhẹ, Trung bình, Nặng>",
      "pathogen": "<causal agent: specific fungus/bacteria/virus/insect name, in Vietnamese; 'Không có tác nhân gây bệnh' if healthy>",
      "signs_in_photo": "<the SPECIFIC visual evidence you can actually see in THIS uploaded photo that led you to this diagnosis - which part of the plant, what the spots/damage/discoloration actually look like (shape, color, texture, size), how severe or widespread it looks IN THIS PICTURE. This must describe what is really visible in the photo, not generic textbook symptoms - about 3-5 sentences in Vietnamese. If disease_name is 'Cây khỏe mạnh', describe why the plant in the photo looks healthy instead.>",
      "symptoms": "<GENERAL, typical visual symptoms of this disease on this crop across cases (beyond just this one photo), so a farmer can recognize it by themselves next time on a different plant - write a real paragraph of about 5 sentences in Vietnamese>",
      "treatment": "<concrete, actionable treatment method to cure a plant that already HAS this disease - about 5 sentences, or a short list of the necessary points, in Vietnamese>",
      "prevention": "<concrete, actionable prevention method to avoid this disease in the future - about 5 sentences, or a short list of the necessary points, in Vietnamese>"
    }}
  ]
}}

If "crop_match" is false: still fill in "detected_crop" with your best guess of what \
plant it is, set "disease_probability" to 0, and leave "diseases" as an empty array [].

IMPORTANT: every string value in the JSON (detected_crop, disease_name, pathogen, \
signs_in_photo, symptoms, treatment, prevention) MUST be written in proper Vietnamese \
WITH FULL DIACRITICS (dấu) - e.g. "Đốm nâu" not "Dom nau", "Cà chua" not "Ca chua". Use \
plain, practical language a farmer can understand, avoid overly technical jargon. \
"signs_in_photo", "symptoms", "treatment" and "prevention" must each be a genuine \
paragraph (or a short list of only the necessary points if that reads more naturally) - \
never just a single short line. Do NOT let "symptoms" (general/typical) and \
"signs_in_photo" (specific to this photo) end up saying the exact same thing word for \
word - they serve different purposes. Only the JSON keys themselves stay in English \
exactly as shown above.
"""


HEALTHY_LABEL = "Cây khỏe mạnh"


class GeminiDiagnosisError(Exception):
    pass


def _clamp_percent(value) -> int | None:
    """Gemini tu uoc luong so % (khong phai model ML) nen doi khi ra ngoai [0, 100],
    thieu, hoac khong phai so - ep ve int trong khoang hop le, None neu khong doc duoc."""
    if value is None:
        return None
    try:
        num = float(value)
    except (TypeError, ValueError):
        return None
    return max(0, min(100, round(num)))


def _extract_reference_images(raw_http_response, limit: int = 4) -> list:
    """THU NGHIEM: 1 so ban WebAI-to-API (nhanh master cua Amm1rr/WebAI-to-API)
    tra them `choices[0].artifacts` chua anh - gom 2 loai: anh Gemini TU TIM
    duoc tren web khi no tu tra cuu de xac nhan cau tra loi (WebImage, anh THAT)
    va anh Gemini TU VE ra (GeneratedImage, KHONG dung o day vi co the "bia" sai
    dac diem benh, nguy hiem hon khong co anh). Day la kenh du lieu PHU, tai lieu
    goc ghi ro "artifact URLs la opaque metadata, khong dam bao ton tai lau/public/
    tai duoc on dinh". Container dang chay (`ghcr.io/leolionart/webai-to-api`) co
    the la ban cu hon chua co tinh nang nay - moi loi/thieu du lieu deu tra ve []
    tham lang, KHONG bao giu lam vo luong chan doan chinh (goi ham nay luon nam
    trong pham vi an toan, khong raise).
    """
    try:
        raw_json = json.loads(raw_http_response.content)
    except Exception:
        return []

    choices = raw_json.get("choices") or []
    if not choices or not isinstance(choices[0], dict):
        return []
    artifacts = choices[0].get("artifacts") or []
    if not isinstance(artifacts, list):
        return []

    images = []
    seen_urls = set()
    for item in artifacts:
        if not isinstance(item, dict):
            continue
        url = item.get("url") or item.get("image_url") or item.get("src")
        if not url or not isinstance(url, str) or url in seen_urls:
            continue
        # Chi lay anh Gemini TIM THAT tren web (WebImage) - bo qua anh no TU VE
        # (GeneratedImage) de tranh hien anh "bia" sai cho nong dan xem theo.
        item_type = str(item.get("type") or item.get("kind") or "").lower()
        if "generat" in item_type:
            continue
        seen_urls.add(url)
        images.append({"url": url, "title": item.get("title") or item.get("alt") or ""})
        if len(images) >= limit:
            break
    return images


def _extract_json(raw: str) -> dict:
    match = re.search(r"\{.*\}", raw, re.DOTALL)
    if not match:
        raise GeminiDiagnosisError(f"Gemini không trả về JSON hợp lệ:\n{raw[:500]}")
    try:
        return json.loads(match.group(0))
    except json.JSONDecodeError as exc:
        raise GeminiDiagnosisError(f"Gemini trả về JSON lỗi ({exc}):\n{raw[:500]}")


def diagnose_with_gemini(image_url: str, crop_key: str) -> dict:
    """Goi Gemini (qua WebAI-to-API) de chan doan 1 anh cho 1 cay cu the.

    `image_url` phai la 1 URL that (http/https) ma container WebAI-to-API goi
    toi duoc - vd anh dang duoc app.py serve tam qua StaticFiles. KHONG dung
    base64 data URI o day (da thu, WebAI-to-API khong fetch duoc, Gemini se
    tra loi bay khong dua tren anh that).

    Tra ve dict cung format voi nhanh model tu train trong app.py (detections/
    summary/found), them crop_mismatch + detected_crop. Nem GeminiDiagnosisError
    neu goi that bai hoac Gemini tra ve du lieu khong hop le - app.py se convert
    thanh HTTP 502 cho Laravel biet de fallback/bao loi ro rang.
    """
    crop_name = CROP_VI_NAMES.get(crop_key, crop_key)
    prompt = PROMPT_TEMPLATE.format(crop_name=crop_name)

    client = OpenAI(base_url=WEBAI_BASE_URL, api_key=WEBAI_API_KEY)
    try:
        # Dung with_raw_response de lay duoc THEM du lieu tho ngoai phan da duoc
        # SDK "openai" parse san (vd choices[0].artifacts - anh Gemini tim/tu ve,
        # xem _extract_reference_images() ben duoi). SDK chuan chi tra ve dung
        # cac field co trong schema OpenAI goc, se tu bo qua nhung field la nay.
        raw_http_response = client.chat.completions.with_raw_response.create(
            model=GEMINI_MODEL,
            messages=[{
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt},
                    {"type": "image_url", "image_url": {"url": image_url}},
                ],
            }],
        )
        response = raw_http_response.parse()
    except Exception as exc:
        raise GeminiDiagnosisError(
            f"Không gọi được WebAI-to-API tại {WEBAI_BASE_URL} "
            f"(kiểm tra container đang chạy + cookie còn hạn ở http://localhost:6969/admin): {exc}"
        )

    reference_images = _extract_reference_images(raw_http_response)

    raw = (response.choices[0].message.content or "").strip()
    data = _extract_json(raw)

    crop_match = bool(data.get("crop_match", True))
    detected_crop = data.get("detected_crop") or ""
    disease_probability = _clamp_percent(data.get("disease_probability"))
    diseases = data.get("diseases") or []

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
    for d in diseases:
        name = d.get("disease_name", "")
        if not name:
            continue
        detections.append({
            "disease_key": name,  # Gemini khong co ma benh co dinh, dung luon ten lam key
            "disease_name": name,
            "probability": _clamp_percent(d.get("probability")),  # % Gemini tu danh gia, chi mang tinh tham khao
            "level": d.get("level", ""),
            "bbox": None,  # khong khoanh vung nhu YOLO
            "pathogen": d.get("pathogen", ""),
            "signs_in_photo": d.get("signs_in_photo", ""),  # dau hieu QUAN SAT DUOC trong chinh anh nay
            "symptoms": d.get("symptoms", ""),  # dau hieu CHUNG cua benh (khong gan voi 1 anh cu the)
            "treatment": d.get("treatment", ""),
            "prevention": d.get("prevention", ""),
            "is_healthy": name.strip() == HEALTHY_LABEL,
        })

    # Sap xep benh co % cao nhat len dau (None coi nhu 0 de khong bi loi so sanh)
    detections.sort(key=lambda x: x["probability"] or 0, reverse=True)

    if detections:
        top = detections[0]
        summary = {
            "disease_key": top["disease_key"],
            "disease_name": top["disease_name"],
            "probability": top["probability"],
            "level": top["level"],
            "pathogen": top["pathogen"],
            "signs_in_photo": top["signs_in_photo"],
            "symptoms": top["symptoms"],
            "treatment": top["treatment"],
            "prevention": top["prevention"],
        }
        # "found" = co phat hien BENH thuc su (khac voi entry "Cay khoe manh")
        found = not top["is_healthy"]
    else:
        summary = {
            "disease_key": None,
            "disease_name": "Không xác định được",
            "probability": None,
            "level": None,
            "pathogen": None,
            "signs_in_photo": None,
            "symptoms": None,
            "treatment": None,
            "prevention": "Thử chụp lại ảnh rõ nét hơn, đủ sáng, cận cảnh vùng nghi ngờ có bệnh",
        }
        found = False

    return {
        "crop_mismatch": False,
        "detected_crop": detected_crop,
        "disease_probability": disease_probability,
        "detections": detections,
        "detection_count": len(detections),
        "found": found,
        "summary": summary,
        "reference_images": reference_images,
    }
