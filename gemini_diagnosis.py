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
  2. Goi WebAI-to-API (chuan OpenAI /v1/chat/completions) voi 1 prompt yeu cau Gemini:
     - Kiem tra anh co dung la loai cay nguoi dung da chon khong (crop_match).
     - Neu dung, liet ke TAT CA benh/sau hai quan sat duoc + cach xu ly.
  3. Parse JSON Gemini tra ve, chuyen thanh dung format response ma app.py /
     agri-app.js dang doc (detections[] + summary), them 2 truong moi
     crop_mismatch / detected_crop de FE canh bao khi chon nham cay.

Gemini KHONG dua ra % xac suat dang tin cay (khac model tu train) nen truong
"confidence" luon la None - phu hop vi UI hien tai cung da bo % roi.
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
GEMINI_MODEL = "gemini-3.0-flash"

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

Step 1: Carefully look at the photo and decide whether it actually shows a \
"{crop_name}" plant, based on leaf shape, leaf vein pattern, stem, and fruit if visible.
Step 2: If it IS "{crop_name}", identify ALL visible diseases or pests in the photo \
(there can be more than one issue at once). If you see no abnormality, treat it as \
"healthy".

Return ONLY one valid JSON object (no markdown, no extra explanation outside the \
JSON), with EXACTLY this structure:

{{
  "crop_match": <true if the photo really shows "{crop_name}", false if it is clearly a different plant>,
  "detected_crop": "<the crop you actually observe in the photo, in Vietnamese with full diacritics, e.g. Cà chua/Lúa/Ngô/Xoài/Ớt/Chè/Sắn/other>",
  "diseases": [
    {{
      "disease_name": "<disease or pest name, in Vietnamese with full diacritics; or 'Cây khỏe mạnh' if there is no issue>",
      "level": "<EXACTLY one of: Nhẹ, Trung bình, Nặng>",
      "pathogen": "<causal agent: specific fungus/bacteria/virus/insect name, in Vietnamese; 'Không có tác nhân gây bệnh' if healthy>",
      "conditions": "<environmental/farming conditions that trigger this disease, 1-2 sentences in Vietnamese>",
      "steps": ["<treatment/prevention step 1, in Vietnamese>", "<step 2>", "<step 3>"]
    }}
  ]
}}

If "crop_match" is false: still fill in "detected_crop" with your best guess of what \
plant it is, and leave "diseases" as an empty array [].

IMPORTANT: every string value in the JSON (detected_crop, disease_name, pathogen, \
conditions, steps) MUST be written in proper Vietnamese WITH FULL DIACRITICS (dấu) - \
e.g. "Đốm nâu" not "Dom nau", "Cà chua" not "Ca chua". Use plain, practical language a \
farmer can understand, avoid overly technical jargon. Only the JSON keys themselves \
stay in English exactly as shown above.
"""


class GeminiDiagnosisError(Exception):
    pass


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
        response = client.chat.completions.create(
            model=GEMINI_MODEL,
            messages=[{
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt},
                    {"type": "image_url", "image_url": {"url": image_url}},
                ],
            }],
        )
    except Exception as exc:
        raise GeminiDiagnosisError(
            f"Không gọi được WebAI-to-API tại {WEBAI_BASE_URL} "
            f"(kiểm tra container đang chạy + cookie còn hạn ở http://localhost:6969/admin): {exc}"
        )

    raw = (response.choices[0].message.content or "").strip()
    data = _extract_json(raw)

    crop_match = bool(data.get("crop_match", True))
    detected_crop = data.get("detected_crop") or ""
    diseases = data.get("diseases") or []

    if not crop_match:
        return {
            "crop_mismatch": True,
            "detected_crop": detected_crop,
            "detections": [],
            "detection_count": 0,
            "found": False,
            "summary": None,
        }

    detections = []
    for d in diseases:
        name = d.get("disease_name", "")
        if not name:
            continue
        detections.append({
            "disease_key": name,  # Gemini khong co ma benh co dinh, dung luon ten lam key
            "disease_name": name,
            "confidence": None,  # khong bia % - Gemini khong dua ra xac suat dang tin
            "level": d.get("level", ""),
            "bbox": None,  # khong khoanh vung nhu YOLO
            "pathogen": d.get("pathogen", ""),
            "conditions": d.get("conditions", ""),
            "recommended_steps": d.get("steps", []) or [],
        })

    if detections:
        top = detections[0]
        summary = {
            "disease_key": top["disease_key"],
            "disease_name": top["disease_name"],
            "confidence": None,
            "level": top["level"],
            "pathogen": top["pathogen"],
            "conditions": top["conditions"],
            "recommended_steps": top["recommended_steps"],
        }
        found = True
    else:
        summary = {
            "disease_key": None,
            "disease_name": "Không xác định được",
            "confidence": None,
            "level": None,
            "pathogen": None,
            "conditions": None,
            "recommended_steps": [
                "Thử chụp lại ảnh rõ nét hơn, đủ sáng, cận cảnh vùng nghi ngờ có bệnh",
            ],
        }
        found = False

    return {
        "crop_mismatch": False,
        "detected_crop": detected_crop,
        "detections": detections,
        "detection_count": len(detections),
        "found": found,
        "summary": summary,
    }
