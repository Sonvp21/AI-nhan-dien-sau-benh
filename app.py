"""
FastAPI service - Chẩn đoán bệnh lá chè bằng AI
Nhận ảnh upload -> trả về JSON: tên bệnh, độ tin cậy, top-3 dự đoán

Chạy: uvicorn app:app --reload --port 8000
Test: mở trình duyệt http://127.0.0.1:8000/docs
"""

import torch
import torch.nn as nn
from torchvision import transforms, models
from PIL import Image
import io

from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware

# ==== CẤU HÌNH ====
# QUAN TRỌNG: thứ tự lớp phải khớp CHÍNH XÁC với lúc train (ImageFolder tự sắp xếp theo alphabet)
CLASS_NAMES = ['Anthracnose', 'algal leaf', 'bird eye spot', 'brown blight',
               'gray light', 'healthy', 'red leaf spot', 'white spot']

# Tên hiển thị tiếng Việt + khuyến nghị xử lý cho từng lớp
DISEASE_INFO = {
    'Anthracnose': {
        'name_vi': 'Thán thư',
        'level': 'Nặng',
        'steps': ['Cắt bỏ và tiêu hủy lá bệnh', 'Phun thuốc gốc đồng hoặc Chlorothalonil', 'Giảm độ ẩm tán lá, tỉa thưa cành']
    },
    'algal leaf': {
        'name_vi': 'Bệnh tảo đỏ',
        'level': 'Trung bình',
        'steps': ['Cải thiện thoát nước, giảm độ ẩm', 'Phun thuốc gốc đồng', 'Tỉa cành tạo tán thông thoáng']
    },
    'bird eye spot': {
        'name_vi': 'Đốm mắt cua',
        'level': 'Trung bình',
        'steps': ['Phun thuốc trừ nấm phổ rộng', 'Thu gom tiêu hủy lá rụng dưới gốc', 'Bón phân cân đối tăng sức đề kháng']
    },
    'brown blight': {
        'name_vi': 'Cháy lá nâu',
        'level': 'Nặng',
        'steps': ['Phun thuốc gốc Mancozeb hoặc Copper oxychloride', 'Cắt tỉa cành lá bệnh nặng', 'Tránh tưới quá ẩm vào chiều tối']
    },
    'gray light': {
        'name_vi': 'Đốm xám',
        'level': 'Trung bình',
        'steps': ['Phun thuốc trừ nấm sinh học', 'Tăng thông thoáng vườn chè', 'Theo dõi lại sau 5-7 ngày']
    },
    'healthy': {
        'name_vi': 'Cây khỏe mạnh',
        'level': 'Nhẹ',
        'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ 2 tuần/lần', 'Bón phân cân đối NPK theo giai đoạn']
    },
    'red leaf spot': {
        'name_vi': 'Đốm lá đỏ',
        'level': 'Trung bình',
        'steps': ['Phun thuốc gốc đồng', 'Bổ sung kali tăng sức đề kháng', 'Loại bỏ lá bệnh nặng']
    },
    'white spot': {
        'name_vi': 'Đốm trắng',
        'level': 'Trung bình',
        'steps': ['Phun thuốc trừ nấm đặc trị đốm trắng', 'Giảm mật độ trồng nếu quá dày', 'Vệ sinh vườn, thu gom lá bệnh']
    },
}

MODEL_PATH = "best_tea_model.pth"
DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# ==== LOAD MODEL (chỉ 1 lần lúc khởi động server) ====
def load_model():
    model = models.efficientnet_b0(weights=None)
    model.classifier[1] = nn.Linear(model.classifier[1].in_features, len(CLASS_NAMES))
    model.load_state_dict(torch.load(MODEL_PATH, map_location=DEVICE))
    model.to(DEVICE)
    model.eval()
    return model

print(f"Đang tải model từ {MODEL_PATH} ...")
model = load_model()
print(f"Model đã sẵn sàng. Chạy trên: {DEVICE}")

transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

# ==== FASTAPI APP ====
app = FastAPI(title="AgriAI - Tea Disease Detection API")

# Cho phép Laravel (chạy port khác) gọi được API này
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # DEMO: cho phép tất cả. Khi lên production, đổi thành domain Laravel thật, vd ["https://truyxuat.girc.edu.vn"]
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {"status": "ok", "message": "AgriAI Tea Disease Detection API đang chạy"}


@app.post("/predict")
async def predict(file: UploadFile = File(...)):
    # Kiểm tra đúng định dạng ảnh
    if not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File phải là ảnh (jpg, png...)")

    try:
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
    except Exception:
        raise HTTPException(status_code=400, detail="Không đọc được ảnh, file có thể bị lỗi")

    # Tiền xử lý & suy luận
    tensor = transform(image).unsqueeze(0).to(DEVICE)
    with torch.no_grad():
        output = model(tensor)
        probs = torch.softmax(output, dim=1)[0]

    # Top-3 dự đoán, sắp xếp giảm dần theo độ tin cậy
    top3_probs, top3_idx = torch.topk(probs, 3)

    top_class = CLASS_NAMES[top3_idx[0].item()]
    info = DISEASE_INFO[top_class]

    return {
        "disease_key": top_class,
        "disease_name": info["name_vi"],
        "confidence": round(top3_probs[0].item() * 100, 2),
        "level": info["level"],
        "recommended_steps": info["steps"],
        "top3": [
            {
                "disease_key": CLASS_NAMES[idx.item()],
                "disease_name": DISEASE_INFO[CLASS_NAMES[idx.item()]]["name_vi"],
                "confidence": round(prob.item() * 100, 2),
            }
            for prob, idx in zip(top3_probs, top3_idx)
        ],
    }
