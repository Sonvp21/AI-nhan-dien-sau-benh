"""
AgriAI - FastAPI service DUY NHAT chan doan benh cay trong, gom chung vao repo Laravel
(khong con tach rieng project fastaoi_plant nua, chi chay 1 process/1 deploy).

=== BACKEND CHAN DOAN: GEMINI (tam thoi, xem DIAGNOSIS_BACKEND ben duoi) ===
Hien tai /predict dang dung Gemini (qua WebAI-to-API chay local, xem gemini_diagnosis.py)
lam backend CHINH cho CA 7 CAY, thay vi cac model YOLO/EfficientNet tu train. Toan bo
code model cu (crop_configs.py, load model, _predict_detection/_predict_classification)
VAN GIU NGUYEN o duoi, chi tam thoi khong duoc goi toi - chi can doi
`DIAGNOSIS_BACKEND = "local"` la quay lai dung model tu train nhu cu, khong can sua gi
them.

Co che tu dong chon model cho tung cay (khi DIAGNOSIS_BACKEND = "local"), khai bao
trong crop_configs.py:
  - Neu cay da co model DETECTION (YOLO12, file .pt trong ai_models/) -> uu tien dung,
    tra ve ket qua co khoanh vung (bounding box), chinh xac hon.
  - Neu chua co (file .pt chua ton tai tren may) -> tu dong fallback ve model
    CLASSIFICATION cu (EfficientNet-B0, file .pth o thu muc goc), phan loai ca anh.
  - Neu ca hai deu khong co file -> bo qua cay do, in canh bao luc khoi dong (khong crash).

=> Sau nay train xong YOLO cho cay nao, chi can copy file best.pt vao dung duong dan
   khai bao trong crop_configs.py (vd ai_models/corn_yolo12.pt) roi restart service
   (`systemctl restart agriai`) la TU DONG chuyen sang dung model moi, khong sua code.

Chay:
    pip install -r requirements.txt
    uvicorn app:app --reload --port 8000

Test nhanh: mo trinh duyet http://127.0.0.1:8000/docs
"""

import io
import os
import uuid
from typing import Optional

import torch
import torch.nn as nn
from torchvision import transforms, models
from PIL import Image

from fastapi import FastAPI, File, UploadFile, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.concurrency import run_in_threadpool

from crop_configs import CROP_CONFIGS
from gemini_diagnosis import diagnose_with_gemini, GeminiDiagnosisError, CROP_VI_NAMES

# ==== CONG TAC CHON BACKEND CHAN DOAN ====
# "gemini" = dung Gemini qua WebAI-to-API local (HIEN TAI, cho ca 7 cay, tu nhan dien
#            luon dung/sai cay) | "local" = quay lai model YOLO/EfficientNet tu train
#            (chi Che/Lua/Ot/Ngo/San/Ca chua co model, Xoai chua co).
# Doi dong nay la chuyen qua lai duoc ngay, khong can sua gi khac trong file.
DIAGNOSIS_BACKEND = "gemini"

# WebAI-to-API can 1 URL CONG KHAI THAT de tu tai anh ve roi moi goi cho Gemini
# (gemini-webapi khong tai duoc dia chi noi bo nhu host.docker.internal/127.0.0.1,
# va cung khong on dinh qua "quick tunnel" mien phi cua Cloudflare/ngrok - da thu
# va bi loi that su, xem SETUP_NOTES.md). Dang dung Named Tunnel (Cloudflare Zero
# Trust, gan voi domain rieng girc-ai.com) - on dinh, khong co trang canh bao/gioi
# han nhu quick tunnel hay ngrok free.
#
# - Dang TEST LOCAL: cloudflared chay nen (`cloudflared.exe service install <token>`)
#   route "predict.girc-ai.com" -> "localhost:8000", domain CO DINH, khong doi.
# - Khi DEPLOY THAT len VPS: doi thanh domain that, vd "https://aiplant.girc.edu.vn"
#   (khong can tunnel nua vi da la URL cong khai san tren server).
# Doc tu bien moi truong LOCAL_IMAGE_BASE_URL neu co (dat trong file service systemd
# tren VPS), khong co thi fallback ve domain tunnel dung khi chay local - nho vay
# file nay dung chung duoc ca 2 noi, khong can sua code moi lan deploy.
LOCAL_IMAGE_BASE_URL = os.environ.get("LOCAL_IMAGE_BASE_URL", "https://predict.girc-ai.com")
TMP_IMAGES_DIR = "tmp_images"
os.makedirs(TMP_IMAGES_DIR, exist_ok=True)

# TAM THOI de True de dieu tra loi "Failed to download" - anh tam se KHONG bi xoa
# sau moi lan goi, de test tay URL tu trong container WebAI-to-API. Nho doi lai
# False sau khi xong, khong thi tmp_images/ se day dan theo thoi gian.
# -> Da tim ra nguyen nhan that (deadlock event loop, xem run_in_threadpool ben
#    duoi), doi lai False de tmp_images/ khong day dan nua.
DEBUG_KEEP_TMP_IMAGES = False

DEFAULT_CONF = 0.25
DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")

CLASSIFY_TRANSFORM = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])


def _load_classification_model(sub_config):
    model = models.efficientnet_b0(weights=None)
    model.classifier[1] = nn.Linear(model.classifier[1].in_features, len(sub_config["class_names"]))
    model.load_state_dict(torch.load(sub_config["model_path"], map_location=DEVICE))
    model.to(DEVICE)
    model.eval()
    return model


def _load_detection_model(sub_config):
    # import cho trong ham de neu may nao chua cai ultralytics van chay duoc
    # phan classification (vd server cu chua kip cai them thu vien).
    from ultralytics import YOLO
    return YOLO(sub_config["model_path"])


# ==== LOAD MODEL CHO TUNG CAY LUC KHOI DONG (chi 1 lan), TU DONG UU TIEN DETECTION ====
# Chi thuc su tai model (.pth/.pt, ton bo nhieu RAM/VRAM) khi DIAGNOSIS_BACKEND = "local".
# Khi dang dung Gemini (mac dinh) thi bo qua het buoc nay - loaded_models se rong,
# giu code nguyen ven de bat lai "local" la chay ngay khong can sua gi.
loaded_models = {}

if DIAGNOSIS_BACKEND == "local":
    print(f"Dang tai model cho tung cay... Chay tren: {DEVICE}")
    for crop_key, cfg in CROP_CONFIGS.items():
        detection_cfg = cfg.get("detection")
        classification_cfg = cfg.get("classification")

        if detection_cfg and os.path.exists(detection_cfg["model_path"]):
            try:
                loaded_models[crop_key] = {
                    "type": "detection",
                    "model": _load_detection_model(detection_cfg),
                    "config": detection_cfg,
                }
                print(f"  -> '{crop_key}': dung model DETECTION ({detection_cfg['model_path']})")
                continue
            except Exception as e:
                print(f"  -> CANH BAO: loi tai model detection cho '{crop_key}': {e}, thu fallback classification...")

        if classification_cfg and os.path.exists(classification_cfg["model_path"]):
            try:
                loaded_models[crop_key] = {
                    "type": "classification",
                    "model": _load_classification_model(classification_cfg),
                    "config": classification_cfg,
                }
                print(f"  -> '{crop_key}': dung model CLASSIFICATION ({classification_cfg['model_path']})")
                continue
            except Exception as e:
                print(f"  -> CANH BAO: loi tai model classification cho '{crop_key}': {e}, bo qua cay nay")
                continue

        print(f"  -> CANH BAO: cay '{crop_key}' chua co model nao (detection lan classification), bo qua")
else:
    print(f"DIAGNOSIS_BACKEND = 'gemini' -> bo qua buoc tai model local (.pth/.pt), "
          f"dung Gemini qua WebAI-to-API cho ca 7 cay.")

print("San sang phuc vu.\n")


# ==== FASTAPI APP ====
app = FastAPI(title="AgriAI - Multi-Crop Disease Detection API", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# Serve anh upload tam thoi qua URL that, de WebAI-to-API (chay trong Docker) tai
# ve duoc va dua cho Gemini xem (Gemini/gemini-webapi khong nhan base64 truc tiep
# qua duong nay, phai la 1 URL http that no tu fetch). Moi anh bi xoa ngay sau khi
# goi Gemini xong trong ham predict() ben duoi, thu muc nay chi chua anh tam vai giay.
app.mount("/tmp-images", StaticFiles(directory=TMP_IMAGES_DIR), name="tmp-images")


@app.get("/")
def root():
    if DIAGNOSIS_BACKEND == "gemini":
        return {
            "status": "ok",
            "message": "AgriAI dang chay - backend chan doan: GEMINI (qua WebAI-to-API local)",
            "diagnosis_backend": "gemini",
            "available_crops": list(CROP_VI_NAMES.keys()),
        }
    return {
        "status": "ok",
        "message": "AgriAI Multi-Crop Disease API dang chay (tu dong chon detection/classification theo cay)",
        "diagnosis_backend": "local",
        "available_crops": {
            crop_key: entry["type"] for crop_key, entry in loaded_models.items()
        },
    }


def _predict_detection(entry, image, conf):
    model = entry["model"]
    disease_info = entry["config"]["disease_info"]

    results = model.predict(image, conf=conf, verbose=False)
    r = results[0]

    detections = []
    for box in r.boxes:
        cls_id = int(box.cls[0])
        cls_name = model.names[cls_id]
        confidence = float(box.conf[0]) * 100
        x1, y1, x2, y2 = [float(v) for v in box.xyxy[0]]

        info = disease_info.get(cls_name, {})
        detections.append({
            "disease_key": cls_name,
            "disease_name": info.get("name_vi", cls_name),
            "confidence": round(confidence, 2),
            "level": info.get("level", ""),
            "bbox": {"x1": round(x1, 1), "y1": round(y1, 1), "x2": round(x2, 1), "y2": round(y2, 1)},
        })

    detections.sort(key=lambda d: d["confidence"], reverse=True)

    if detections:
        top = detections[0]
        top_info = disease_info.get(top["disease_key"], {})
        summary = {
            "disease_key": top["disease_key"],
            "disease_name": top["disease_name"],
            "confidence": top["confidence"],
            "level": top["level"],
            "pathogen": top_info.get("pathogen", ""),
            "conditions": top_info.get("conditions", ""),
            "recommended_steps": top_info.get("steps", []),
        }
        found = True
    else:
        summary = {
            "disease_key": None,
            "disease_name": "Không phát hiện được vùng bất thường nào",
            "confidence": None,
            "level": None,
            "pathogen": None,
            "conditions": None,
            "recommended_steps": [
                "Thử chụp lại ảnh rõ nét hơn, đủ sáng, cận cảnh vùng nghi ngờ có bệnh",
                "Có thể cây đang khỏe mạnh, hoặc vết bệnh chưa đủ rõ để nhận diện",
            ],
        }
        found = False

    return detections, summary, found


def _predict_classification(entry, image):
    model = entry["model"]
    class_names = entry["config"]["class_names"]
    disease_info = entry["config"]["disease_info"]

    tensor = CLASSIFY_TRANSFORM(image).unsqueeze(0).to(DEVICE)
    with torch.no_grad():
        output = model(tensor)
        probs = torch.softmax(output, dim=1)[0]

    top_prob, top_idx = torch.max(probs, dim=0)
    top_class = class_names[top_idx.item()]
    info = disease_info.get(top_class, {})
    confidence = round(top_prob.item() * 100, 2)

    detection = {
        "disease_key": top_class,
        "disease_name": info.get("name_vi", top_class),
        "confidence": confidence,
        "level": info.get("level", ""),
        "bbox": None,  # model classification khong khoanh vung
    }

    summary = {
        "disease_key": top_class,
        "disease_name": info.get("name_vi", top_class),
        "confidence": confidence,
        "level": info.get("level", ""),
        "pathogen": info.get("pathogen", ""),
        "conditions": info.get("conditions", ""),
        "recommended_steps": info.get("steps", []),
    }

    # classification luon ra 1 nhan (ke ca "healthy"), khong co khai niem "khong tim thay gi"
    return [detection], summary, True


@app.post("/predict")
async def predict(
    file: UploadFile = File(...),
    crop: str = Form(...),
    conf: Optional[float] = Form(DEFAULT_CONF),
):
    crop = crop.lower()

    if not file.content_type or not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File phai la anh (jpg, png...)")

    try:
        contents = await file.read()
    except Exception:
        raise HTTPException(status_code=400, detail="Khong doc duoc anh, file co the bi loi")

    # ==== BACKEND GEMINI (hien tai) - cho ca 7 cay, tu nhan dien dung/sai cay ====
    if DIAGNOSIS_BACKEND == "gemini":
        if crop not in CROP_VI_NAMES:
            raise HTTPException(
                status_code=400,
                detail=f"Cay '{crop}' khong hop le. Cac cay ho tro: {list(CROP_VI_NAMES.keys())}",
            )
        try:
            # xac nhan file la anh doc duoc truoc khi gui cho Gemini
            image = Image.open(io.BytesIO(contents)).convert("RGB")
        except Exception:
            raise HTTPException(status_code=400, detail="Khong doc duoc anh, file co the bi loi")

        # Luu anh tam ra file tinh, tao URL that de WebAI-to-API tu tai ve (xem
        # giai thich o LOCAL_IMAGE_BASE_URL phia tren) - KHONG dung base64.
        # LUON re-encode sang JPEG (bo qua dinh dang goc nhu webp/heic/png) vi
        # mimetypes trong container Debian slim co the khong nhan dien dung
        # Content-Type cho .webp -> gemini-webapi tu choi tai anh (da gap loi
        # nay thuc te). .jpg la dinh dang chuan nhat, luon duoc nhan dien dung.
        tmp_filename = f"{uuid.uuid4().hex}.jpg"
        tmp_path = os.path.join(TMP_IMAGES_DIR, tmp_filename)
        image.save(tmp_path, format="JPEG", quality=92)
        image_url = f"{LOCAL_IMAGE_BASE_URL}/tmp-images/{tmp_filename}"
        print(f"[DEBUG] Anh tam: {tmp_path} ({os.path.getsize(tmp_path)} bytes) -> {image_url}")

        try:
            # QUAN TRONG: diagnose_with_gemini() goi OpenAI client dong bo (blocking),
            # ban than no lai cho web_ai_server tai nguoc anh qua chinh URL
            # predict.girc-ai.com -> tunnel -> localhost:8000 (tuc la CHINH process
            # nay). Neu goi truc tiep trong "async def predict", loi goi dong bo se
            # chiem het event loop duy nhat cua uvicorn, khien process khong the
            # tra loi duoc chinh request tai anh do -> tu deadlock voi chinh minh,
            # chi thoat duoc khi het timeout tai anh ben webai-to-api (day la nguyen
            # nhan "load rat lau" / "Failed to download"). Chay trong threadpool de
            # event loop con ranh phuc vu StaticFiles /tmp-images song song.
            result = await run_in_threadpool(diagnose_with_gemini, image_url, crop)
        except GeminiDiagnosisError as e:
            raise HTTPException(status_code=502, detail=str(e))
        finally:
            # DEBUG_KEEP_TMP_IMAGES=True: KHONG xoa anh tam, de test tay URL nay
            # tu trong container xem loi that su la gi. Nho doi lai False sau khi
            # xong, khong thi tmp_images/ se day dan.
            if not DEBUG_KEEP_TMP_IMAGES:
                try:
                    os.remove(tmp_path)
                except OSError:
                    pass

        return {
            "crop": crop,
            "model_type": "gemini",
            "image_width": image.width,
            "image_height": image.height,
            "crop_mismatch": result["crop_mismatch"],
            "detected_crop": result["detected_crop"],
            # % Gemini tu danh gia kha nang cay dang co benh/sau hai noi chung (0-100),
            # doc lap voi % tung benh cu the trong detections/summary ben duoi.
            "disease_probability": result["disease_probability"],
            "detections": result["detections"],
            "detection_count": result["detection_count"],
            "found": result["found"],
            "summary": result["summary"],
            # THU NGHIEM: anh Gemini TU TIM duoc tren web (khong phai anh tu ve) khi
            # no tu tra cuu de xac nhan chan doan - xem _extract_reference_images()
            # trong gemini_diagnosis.py. Co the la [] neu ban WebAI-to-API dang chay
            # chua ho tro, khong dam bao luon co.
            "reference_images": result.get("reference_images", []),
        }

    # ==== BACKEND LOCAL (model tu train, giu nguyen tu ban truoc) ====
    if crop not in loaded_models:
        raise HTTPException(
            status_code=400,
            detail=f"Cay '{crop}' chua co model. Cac cay ho tro: {list(loaded_models.keys())}",
        )

    try:
        image = Image.open(io.BytesIO(contents)).convert("RGB")
    except Exception:
        raise HTTPException(status_code=400, detail="Khong doc duoc anh, file co the bi loi")

    entry = loaded_models[crop]

    if entry["type"] == "detection":
        detections, summary, found = _predict_detection(entry, image, conf)
    else:
        detections, summary, found = _predict_classification(entry, image)

    return {
        "crop": crop,
        "model_type": entry["type"],
        "image_width": image.width,
        "image_height": image.height,
        "detections": detections,
        "detection_count": len(detections),
        "found": found,
        "summary": summary,
    }
