"""
FastAPI service - Chẩn đoán bệnh cây trồng bằng AI (đa cây: Chè, Lúa)
Nhận ảnh upload + tên cây -> trả về JSON: tên bệnh, độ tin cậy, top-3 dự đoán

Chạy: uvicorn app:app --reload --port 8000
Test: mở trình duyệt http://127.0.0.1:8000/docs
"""

import torch
import torch.nn as nn
from torchvision import transforms, models
from PIL import Image
import io

from fastapi import FastAPI, File, UploadFile, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware

# ============================================================
# CẤU HÌNH TỪNG CÂY - thêm cây mới chỉ cần thêm 1 entry vào đây
# ============================================================
CROP_CONFIGS = {
    "che": {
        "model_path": "best_tea_model.pth",
        "class_names": ['Anthracnose', 'algal leaf', 'bird eye spot', 'brown blight',
                         'gray light', 'healthy', 'red leaf spot', 'white spot'],
        "disease_info": {
            'Anthracnose': {'name_vi': 'Thán thư', 'level': 'Nặng',
                'steps': ['Cắt bỏ và tiêu hủy lá bệnh', 'Phun thuốc gốc đồng hoặc Chlorothalonil', 'Giảm độ ẩm tán lá, tỉa thưa cành']},
            'algal leaf': {'name_vi': 'Bệnh tảo đỏ', 'level': 'Trung bình',
                'steps': ['Cải thiện thoát nước, giảm độ ẩm', 'Phun thuốc gốc đồng', 'Tỉa cành tạo tán thông thoáng']},
            'bird eye spot': {'name_vi': 'Đốm mắt cua', 'level': 'Trung bình',
                'steps': ['Phun thuốc trừ nấm phổ rộng', 'Thu gom tiêu hủy lá rụng dưới gốc', 'Bón phân cân đối tăng sức đề kháng']},
            'brown blight': {'name_vi': 'Cháy lá nâu', 'level': 'Nặng',
                'steps': ['Phun thuốc gốc Mancozeb hoặc Copper oxychloride', 'Cắt tỉa cành lá bệnh nặng', 'Tránh tưới quá ẩm vào chiều tối']},
            'gray light': {'name_vi': 'Đốm xám', 'level': 'Trung bình',
                'steps': ['Phun thuốc trừ nấm sinh học', 'Tăng thông thoáng vườn chè', 'Theo dõi lại sau 5-7 ngày']},
            'healthy': {'name_vi': 'Cây khỏe mạnh', 'level': 'Nhẹ',
                'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ 2 tuần/lần', 'Bón phân cân đối NPK theo giai đoạn']},
            'red leaf spot': {'name_vi': 'Đốm lá đỏ', 'level': 'Trung bình',
                'steps': ['Phun thuốc gốc đồng', 'Bổ sung kali tăng sức đề kháng', 'Loại bỏ lá bệnh nặng']},
            'white spot': {'name_vi': 'Đốm trắng', 'level': 'Trung bình',
                'steps': ['Phun thuốc trừ nấm đặc trị đốm trắng', 'Giảm mật độ trồng nếu quá dày', 'Vệ sinh vườn, thu gom lá bệnh']},
        }
    },
    "lua": {
        "model_path": "best_rice_model.pth",
        "class_names": ['Bacterialblight', 'Blast', 'Brownspot', 'Tungro'],
        "disease_info": {
            'Bacterialblight': {'name_vi': 'Bạc lá vi khuẩn', 'level': 'Nặng',
                'steps': ['Phun thuốc gốc kháng sinh (Bactericide) khi mới phát hiện', 'Bón phân cân đối, hạn chế bón thừa đạm', 'Rút nước ruộng, tránh ngập úng kéo dài']},
            'Blast': {'name_vi': 'Đạo ôn lá', 'level': 'Nặng',
                'steps': ['Phun thuốc gốc Tricyclazole trong vòng 24-48 giờ', 'Giảm bón đạm, tăng kali để hạn chế lây lan', 'Theo dõi lại sau 5 ngày, chụp ảnh so sánh']},
            'Brownspot': {'name_vi': 'Đốm nâu', 'level': 'Trung bình',
                'steps': ['Phun thuốc gốc Mancozeb hoặc Propiconazole', 'Bón phân cân đối NPK, bổ sung kali', 'Vệ sinh đồng ruộng sau thu hoạch']},
            'Tungro': {'name_vi': 'Bệnh vàng lùn', 'level': 'Nặng',
                'steps': ['Nhổ bỏ và tiêu hủy cây bệnh nặng để tránh lây lan', 'Diệt rầy xanh - trung gian truyền bệnh - bằng thuốc đặc trị', 'Dùng giống lúa kháng bệnh cho vụ sau']},
        }
    },
}

DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# ==== LOAD TẤT CẢ MODEL LÚC KHỞI ĐỘNG (chỉ 1 lần) ====
loaded_models = {}

def load_model(config):
    model = models.efficientnet_b0(weights=None)
    model.classifier[1] = nn.Linear(model.classifier[1].in_features, len(config["class_names"]))
    model.load_state_dict(torch.load(config["model_path"], map_location=DEVICE))
    model.to(DEVICE)
    model.eval()
    return model

print(f"Đang tải các model... Chạy trên: {DEVICE}")
for crop_key, config in CROP_CONFIGS.items():
    try:
        loaded_models[crop_key] = load_model(config)
        print(f"  -> Đã tải model cho '{crop_key}' ({config['model_path']})")
    except FileNotFoundError:
        print(f"  -> CẢNH BÁO: không tìm thấy {config['model_path']}, bỏ qua cây '{crop_key}'")
print("Sẵn sàng phục vụ.")

transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

# ==== FASTAPI APP ====
app = FastAPI(title="AgriAI - Multi-Crop Disease Detection API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # DEMO: production nên đổi thành domain Laravel thật
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {
        "status": "ok",
        "message": "AgriAI Multi-Crop Disease Detection API đang chạy",
        "available_crops": list(loaded_models.keys()),
    }


@app.post("/predict")
async def predict(file: UploadFile = File(...), crop: str = Form(...)):
    crop = crop.lower()

    if crop not in loaded_models:
        raise HTTPException(
            status_code=400,
            detail=f"Cây '{crop}' chưa có model. Các cây hỗ trợ: {list(loaded_models.keys())}"
        )

    if not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File phải là ảnh (jpg, png...)")

    try:
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
    except Exception:
        raise HTTPException(status_code=400, detail="Không đọc được ảnh, file có thể bị lỗi")

    config = CROP_CONFIGS[crop]
    model = loaded_models[crop]
    class_names = config["class_names"]
    disease_info = config["disease_info"]

    tensor = transform(image).unsqueeze(0).to(DEVICE)
    with torch.no_grad():
        output = model(tensor)
        probs = torch.softmax(output, dim=1)[0]

    k = min(3, len(class_names))
    topk_probs, topk_idx = torch.topk(probs, k)

    top_class = class_names[topk_idx[0].item()]
    info = disease_info[top_class]

    return {
        "crop": crop,
        "disease_key": top_class,
        "disease_name": info["name_vi"],
        "confidence": round(topk_probs[0].item() * 100, 2),
        "level": info["level"],
        "recommended_steps": info["steps"],
        "top3": [
            {
                "disease_key": class_names[idx.item()],
                "disease_name": disease_info[class_names[idx.item()]]["name_vi"],
                "confidence": round(prob.item() * 100, 2),
            }
            for prob, idx in zip(topk_probs, topk_idx)
        ],
    }