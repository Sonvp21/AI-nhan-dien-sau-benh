"""
FastAPI service - Chan doan benh cay trong bang AI (5 cay: Che, Lua, Ngo, San, Ca chua)
Nhan anh upload + ten cay -> tra ve JSON: ten benh, tac nhan gay benh, dieu kien phat sinh,
do tin cay, top-3 du doan, khuyen nghi xu ly

Chay: uvicorn app:app --reload --port 8000
Test: mo trinh duyet http://127.0.0.1:8000/docs
"""

import torch
import torch.nn as nn
from torchvision import transforms, models
from PIL import Image
import io

from fastapi import FastAPI, File, UploadFile, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware

# ============================================================
# CAU HINH TUNG CAY - them cay moi chi can them 1 entry vao day
# ============================================================
CROP_CONFIGS = {
    "che": {
        "model_path": "best_tea_model.pth",
        "class_names": ['Anthracnose', 'algal leaf', 'bird eye spot', 'brown blight',
                         'gray light', 'healthy', 'red leaf spot', 'white spot'],
        "disease_info": {
            'Anthracnose': {
                'name_vi': 'Thán thư', 'level': 'Nặng',
                'pathogen': 'Nấm Colletotrichum camelliae (C. gloeosporioides)',
                'conditions': 'Thời tiết ấm ẩm (tháng 3-4 và mùa mưa), lá/chồi non bị tổn thương cơ giới',
                'steps': ['Tiêu hủy triệt để lá, chồi, cành bị bệnh', 'Hạn chế tổn thương cơ giới khi hái búp, đốn tỉa', 'Bón phân cân đối, tạo tán thông thoáng', 'Phun thuốc đặc trị gốc đồng hoặc Carbendazim khi mới chớm bệnh']},
            'algal leaf': {
                'name_vi': 'Đốm rong (bệnh tảo)', 'level': 'Trung bình',
                'pathogen': 'Tảo Cephaleuros virescens',
                'conditions': 'Nương chè rậm rạp, thiếu chăm sóc, cây già cỗi, mùa mưa kéo dài',
                'steps': ['Không trồng quá dày, chăm sóc cây sinh trưởng khỏe', 'Tỉa bỏ cành lá già bệnh nặng, tiêu hủy', 'Quét thuốc gốc đồng đậm đặc lên vùng bệnh trên thân/cành']},
            'bird eye spot': {
                'name_vi': 'Đốm mắt cua', 'level': 'Trung bình',
                'pathogen': 'Nấm Cercospora theae (Pseudocercospora theae)',
                'conditions': 'Mưa nhiều, ẩm độ cao, nương chè rậm rạp thiếu thông thoáng',
                'steps': ['Đốn tỉa định kỳ tạo tán thông thoáng', 'Thu dọn lá rụng, tàn dư sau thu hái/đốn', 'Phun thuốc gốc đồng hoặc Antracol 70WP khi mới xuất hiện']},
            'brown blight': {
                'name_vi': 'Đốm nâu (chè)', 'level': 'Nặng',
                'pathogen': 'Nấm Colletotrichum camelliae, đôi khi kết hợp Pestalotiopsis theae',
                'conditions': 'Mùa mưa, nương chè rậm rạp chăm sóc kém, ẩm độ cao kéo dài',
                'steps': ['Cắt bỏ, tiêu hủy lá cành bệnh nặng', 'Đốn tỉa tạo thông thoáng, tránh trồng quá dày', 'Hạn chế bón thừa đạm mùa mưa, tăng kali', 'Phun Antracol 70WP hoặc thuốc gốc đồng sau khi hái']},
            'gray light': {
                'name_vi': 'Đốm xám', 'level': 'Trung bình',
                'pathogen': 'Nấm Pestalotiopsis (Pseudopestalotiopsis) theae',
                'conditions': 'Mưa ẩm, nhiệt độ 25-28°C, mạnh nhất tháng 7-10, vết thương cơ giới do hái chè',
                'steps': ['Bón phân cân đối, tưới tiêu hợp lý', 'Cày vùi lá cành sau đốn (ép xanh) diệt nguồn bệnh', 'Hái chè đúng kỹ thuật hạn chế vết thương', 'Phun Amtech 100EW hoặc thuốc gốc đồng']},
            'healthy': {
                'name_vi': 'Cây khỏe mạnh', 'level': 'Nhẹ',
                'pathogen': 'Không có tác nhân gây bệnh',
                'conditions': 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh',
                'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ 2 tuần/lần vào mùa mưa', 'Bón phân cân đối NPK theo giai đoạn sinh trưởng']},
            'red leaf spot': {
                'name_vi': 'Đốm lá đỏ', 'level': 'Trung bình',
                'pathogen': 'Nấm Phoma theicola hoặc Cercospora theae',
                'conditions': 'Nương chè già cỗi, đất nghèo dinh dưỡng, thiếu kali, thoát nước kém',
                'steps': ['Bón phân cân đối, tăng kali và phân hữu cơ', 'Đốn tạo tán hợp lý, loại bỏ cành già', 'Phun thuốc gốc đồng hoặc thuốc trừ nấm phổ rộng']},
            'white spot': {
                'name_vi': 'Đốm trắng', 'level': 'Trung bình',
                'pathogen': 'Nấm Phyllosticta theicola hoặc Pseudocercospora sp.',
                'conditions': 'Nương chè rậm rạp, thiếu ánh sáng, độ ẩm cao, thông thoáng kém',
                'steps': ['Đốn tỉa tạo tán thông thoáng', 'Hái chè đúng kỹ thuật, đúng lứa', 'Thu gom tiêu hủy lá bệnh rụng', 'Phun thuốc gốc đồng hoặc Mancozeb']},
        }
    },
    "lua": {
        "model_path": "best_rice_model.pth",
        "class_names": ['Bacterialblight', 'Blast', 'Brownspot', 'Tungro'],
        "disease_info": {
            'Bacterialblight': {
                'name_vi': 'Bạc lá', 'level': 'Nặng',
                'pathogen': 'Vi khuẩn Xanthomonas oryzae',
                'conditions': 'Mưa lớn, gió bão, ruộng bón thừa đạm, cây bị tổn thương cơ giới',
                'steps': ['Ưu tiên giống lúa kháng bệnh', 'Thu gom tiêu hủy tàn dư cây bệnh sau thu hoạch', 'Bón phân cân đối, không thừa đạm, điều tiết nước hợp lý', 'Phun phòng gốc Đồng hydroxide hoặc Oxolinic acid trước/sau mưa giông']},
            'Blast': {
                'name_vi': 'Đạo ôn lá', 'level': 'Nặng',
                'pathogen': 'Nấm Pyricularia oryzae (Magnaporthe oryzae)',
                'conditions': 'Ẩm độ cao (>93%), sương mù, mưa phùn kéo dài, nhiệt độ 25-28°C, bón thừa đạm',
                'steps': ['Luân canh, dùng giống ít mẫn cảm với đạo ôn', 'Bón NPK cân đối, không thừa đạm, tăng kali', 'Phun thuốc khi tỷ lệ lá bệnh đạt khoảng 10%', 'Theo dõi lại sau 5 ngày, so sánh mức độ lây lan']},
            'Brownspot': {
                'name_vi': 'Đốm nâu (lúa)', 'level': 'Trung bình',
                'pathogen': 'Nấm Helminthosporium oryzae (Bipolaris oryzae), Curvularia lunata',
                'conditions': 'Đất nghèo dinh dưỡng (đất phèn, đất cát), ruộng thiếu kali, gieo sạ dày',
                'steps': ['Vệ sinh đồng ruộng, xử lý rơm rạ bằng chế phẩm vi sinh', 'Gieo sạ mật độ hợp lý (100-120 kg giống/ha)', 'Bón phân cân đối, bổ sung lân và kali', 'Phun thuốc nhóm Triazole hoặc Carbendazim khi chớm bệnh']},
            'Tungro': {
                'name_vi': 'Vàng lùn - Lùn xoắn lá', 'level': 'Nặng',
                'pathogen': 'Phức hợp virus (Tungro, virus Lùn lúa cỏ, Lùn xoắn lá) do rầy nâu/rầy xanh truyền',
                'conditions': 'Mật độ rầy môi giới cao, gieo sạ không đồng loạt tạo nguồn thức ăn liên tục cho rầy',
                'steps': ['Theo dõi mật độ rầy, phun trừ rầy khi đạt ~3 con/dảnh trong 40 ngày đầu', 'Gieo sạ đồng loạt, né rầy theo khuyến cáo địa phương', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng bệnh', 'Ưu tiên giống kháng/ít nhiễm rầy']},
        }
    },
    "ngo": {
        "model_path": "best_corn_model.pth",
        "class_names": ['Blight', 'Common_Rust', 'Gray_Leaf_Spot', 'Healthy'],
        "disease_info": {
            'Blight': {
                'name_vi': 'Đốm lá lớn (khô vằn lá)', 'level': 'Nặng',
                'pathogen': 'Nấm Exserohilum turcicum',
                'conditions': 'Nóng ẩm, mưa nhiều, nhiệt độ 20-27°C, thường tăng nhanh từ giai đoạn trổ cờ',
                'steps': ['Ưu tiên giống ngô lai kháng bệnh đốm lá lớn', 'Thu gom tiêu hủy tàn dư thân lá sau thu hoạch', 'Trồng mật độ vừa phải, bón phân cân đối, luân canh', 'Phun thuốc gốc Mancozeb hoặc Propiconazole khi chớm bệnh']},
            'Common_Rust': {
                'name_vi': 'Gỉ sắt', 'level': 'Trung bình',
                'pathogen': 'Nấm Puccinia sorghi (Puccinia maydis)',
                'conditions': 'Thời tiết mát ẩm, nhiệt độ 17-18°C, ẩm độ trên 95%, có sương hoặc mưa kéo dài',
                'steps': ['Chọn giống kháng hoặc ít nhiễm bệnh gỉ sắt', 'Trồng đúng thời vụ, mật độ hợp lý, luân canh', 'Thu gom tiêu hủy tàn dư cây bệnh', 'Phun thuốc gốc Đồng hoặc Dithane/Anvil/Kumulus khi chớm bệnh']},
            'Gray_Leaf_Spot': {
                'name_vi': 'Đốm xám lá', 'level': 'Trung bình',
                'pathogen': 'Nấm Cercospora zeae-maydis (C. zeina)',
                'conditions': 'Ấm ẩm, nhiệt độ 25-35°C, ẩm độ cao về đêm, ruộng trồng dày, độc canh nhiều vụ',
                'steps': ['Luân canh với cây khác họ ít nhất 1 vụ', 'Cày vùi hoặc tiêu hủy triệt để tàn dư thân lá', 'Ưu tiên giống ngô lai kháng bệnh', 'Phun phòng sớm bằng Azoxystrobin, Mancozeb hoặc Propiconazole']},
            'Healthy': {
                'name_vi': 'Cây khỏe mạnh', 'level': 'Nhẹ',
                'pathogen': 'Không có tác nhân gây bệnh',
                'conditions': 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh',
                'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Thăm đồng thường xuyên, đặc biệt trước/trong trổ cờ - phun râu', 'Bón phân cân đối N-P-K theo giai đoạn sinh trưởng']},
        }
    },
    "san": {
        "model_path": "best_cassava_model.pth",
        "class_names": ['Cassava___bacterial_blight', 'Cassava___brown_streak_disease',
                         'Cassava___green_mottle', 'Cassava___healthy', 'Cassava___mosaic_disease'],
        "disease_info": {
            'Cassava___bacterial_blight': {
                'name_vi': 'Cháy lá vi khuẩn', 'level': 'Nặng',
                'pathogen': 'Vi khuẩn Xanthomonas axonopodis (X. phaseoli) pv. manihotis',
                'conditions': 'Mưa nhiều, ẩm độ cao, dùng hom giống bệnh, cây bị tổn thương cơ giới',
                'steps': ['Chọn hom giống sạch bệnh, không lấy từ vùng đang có dịch', 'Tiêu hủy triệt để tàn dư cây bệnh, khử trùng dụng cụ cắt hom', 'Luân canh, không trồng sắn liên tục nhiều vụ trên cùng đất', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng']},
            'Cassava___brown_streak_disease': {
                'name_vi': 'Đốm/Sọc nâu (CBSD)', 'level': 'Nặng',
                'pathogen': 'Virus CBSV/UCBSV, lây qua hom giống và bọ phấn trắng (Bemisia tabaci)',
                'conditions': 'Mật độ bọ phấn trắng cao, dùng hom giống không rõ nguồn gốc',
                'steps': ['Chỉ lấy hom giống từ ruộng đã kiểm tra không nhiễm virus', 'Theo dõi và phun trừ bọ phấn trắng khi cần thiết', 'Nhổ bỏ tiêu hủy sớm cây nghi ngờ nhiễm bệnh', 'Ưu tiên giống có khả năng chống chịu CBSD nếu có']},
            'Cassava___green_mottle': {
                'name_vi': 'Khảm xanh', 'level': 'Trung bình',
                'pathogen': 'Virus Cassava green mottle virus (CGMV)',
                'conditions': 'Sử dụng hom giống nhiễm bệnh, triệu chứng rõ sau trồng 2-5 tháng',
                'steps': ['Chọn hom giống sạch bệnh (biện pháp quan trọng nhất)', 'Nhổ bỏ, đốt tiêu hủy ngay khi phát hiện triệu chứng', 'Hạn chế vận chuyển hom giống từ vùng có dịch']},
            'Cassava___healthy': {
                'name_vi': 'Cây khỏe mạnh', 'level': 'Nhẹ',
                'pathogen': 'Không có tác nhân gây bệnh',
                'conditions': 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh',
                'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Chọn hom giống sạch bệnh cho vụ sau', 'Theo dõi định kỳ phát hiện sớm bất thường']},
            'Cassava___mosaic_disease': {
                'name_vi': 'Khảm lá sắn', 'level': 'Nặng',
                'pathogen': 'Sri Lanka Cassava Mosaic Virus (Begomovirus), lây qua hom giống và bọ phấn trắng',
                'conditions': 'Mật độ bọ phấn trắng cao, hom giống không rõ nguồn gốc từ vùng có dịch',
                'steps': ['Dùng giống kháng và hom giống sạch bệnh', 'Không vận chuyển hom/thân từ vùng dịch sang vùng chưa nhiễm', 'Diệt trừ bọ phấn trắng bằng Nitenpyram+Pymetrozine hoặc Dinotefuran', 'Tiêu hủy cây bệnh; ruộng nhiễm >70% cần tiêu hủy toàn bộ theo hướng dẫn BVTV']},
        }
    },
    "ca_chua": {
        "model_path": "best_tomato_model.pth",
        "class_names": ['Tomato___Bacterial_spot', 'Tomato___Early_blight', 'Tomato___Late_blight',
                         'Tomato___Leaf_Mold', 'Tomato___Septoria_leaf_spot',
                         'Tomato___Spider_mites Two-spotted_spider_mite', 'Tomato___Target_Spot',
                         'Tomato___Tomato_mosaic_virus', 'Tomato___Tomato_Yellow_Leaf_Curl_Virus',
                         'Tomato___healthy'],
        "disease_info": {
            'Tomato___Bacterial_spot': {
                'name_vi': 'Đốm vi khuẩn', 'level': 'Nặng',
                'pathogen': 'Vi khuẩn Xanthomonas spp. (X. campestris pv. vesicatoria...)',
                'conditions': 'Nhiệt độ 24-30°C, ẩm độ >80%, mùa mưa (tháng 5-10), mưa xen nắng',
                'steps': ['Xử lý hạt giống trong nước ấm 50°C/30 phút hoặc thuốc trừ khuẩn', 'Phun phòng khi cây ra lá non (15-20 ngày sau trồng)', 'Luân canh, tránh tưới làm bắn đất lên lá', 'Phun trị khi 5-10% lá/quả có đốm, lặp lại 2-3 lần cách 5-7 ngày']},
            'Tomato___Early_blight': {
                'name_vi': 'Đốm vòng (dịch sớm)', 'level': 'Trung bình',
                'pathogen': 'Nấm Alternaria solani',
                'conditions': 'Ẩm ướt, nhiệt độ ấm, cây thiếu dinh dưỡng, thường tấn công lá già trước',
                'steps': ['Luân canh với cây khác họ cà', 'Tỉa bỏ lá già, lá bệnh phía dưới gốc', 'Bón phân cân đối giúp cây khỏe', 'Phun thuốc gốc Mancozeb hoặc Chlorothalonil khi mới xuất hiện']},
            'Tomato___Late_blight': {
                'name_vi': 'Mốc sương (dịch muộn)', 'level': 'Nặng',
                'pathogen': 'Nấm Phytophthora infestans',
                'conditions': 'Ẩm độ cao, nhiệt độ 18-22°C, mưa nắng xen kẽ, sương mù, đất trũng thoát nước kém',
                'steps': ['Thu dọn sạch tàn dư cây bệnh sau vụ', 'Tỉa cành tạo thoáng, giảm ẩm độ trong vườn', 'Ưu tiên giống kháng Phytophthora infestans', 'Phun Mancozeb, Metalaxyl hoặc Tebuconazole ngay khi chớm bệnh - đây là bệnh cực nguy hiểm, có thể làm chết cả ruộng nhanh chóng']},
            'Tomato___Leaf_Mold': {
                'name_vi': 'Mốc lá', 'level': 'Trung bình',
                'pathogen': 'Nấm Passalora fulva (Cladosporium fulvum)',
                'conditions': 'Ẩm độ không khí trên 85%, phổ biến ở nhà màng/nhà kính thiếu thông gió',
                'steps': ['Đảm bảo thông gió tốt trong nhà màng/nhà kính', 'Tránh tưới nước lên lá vào chiều tối', 'Tỉa bỏ lá già gần gốc, lá bệnh', 'Phun thuốc gốc đồng khi bệnh mới xuất hiện']},
            'Tomato___Septoria_leaf_spot': {
                'name_vi': 'Đốm lá Septoria', 'level': 'Trung bình',
                'pathogen': 'Nấm Septoria lycopersici',
                'conditions': 'Ẩm độ cao, nhiệt độ 20-25°C, thường xuất hiện giai đoạn giữa-cuối vụ, từ lá già gần gốc',
                'steps': ['Ưu tiên giống kháng bệnh đốm lá', 'Tỉa bỏ, tiêu hủy lá già, lá bệnh phía dưới gốc', 'Dọn sạch tàn dư sau thu hoạch', 'Phun thuốc gốc đồng hoặc Mancozeb khi mới xuất hiện']},
            'Tomato___Spider_mites Two-spotted_spider_mite': {
                'name_vi': 'Nhện đỏ hai chấm', 'level': 'Trung bình',
                'pathogen': 'Nhện Tetranychus urticae (dịch hại, không phải bệnh do vi sinh vật)',
                'conditions': 'Thời tiết khô nóng, mật độ trồng dày, thiếu thiên địch hoặc lạm dụng thuốc trừ sâu phổ rộng',
                'steps': ['Hạn chế phun thuốc phổ rộng để bảo tồn thiên địch (bọ rùa)', 'Phun nước mạnh vào mặt dưới lá khi mật độ còn thấp', 'Tưới đủ ẩm trong mùa khô hạn', 'Dùng thuốc trừ nhện đặc hiệu khi mật độ vượt ngưỡng, luân phiên hoạt chất']},
            'Tomato___Target_Spot': {
                'name_vi': 'Đốm mắt tiêu', 'level': 'Trung bình',
                'pathogen': 'Nấm Corynespora cassiicola',
                'conditions': 'Nóng ẩm, nhiệt độ 20-24°C, mưa nhiều, vườn trồng rậm rạp thiếu thông thoáng',
                'steps': ['Luân canh, vệ sinh vườn, tiêu hủy tàn dư lá bệnh', 'Trồng mật độ hợp lý, tỉa lá già tạo thoáng', 'Phun thuốc gốc đồng hoặc Chlorothalonil khi mới xuất hiện']},
            'Tomato___Tomato_mosaic_virus': {
                'name_vi': 'Khảm virus', 'level': 'Nặng',
                'pathogen': 'Virus Tomato mosaic virus (ToMV), thuộc nhóm Tobamovirus',
                'conditions': 'Vệ sinh dụng cụ kém, mật độ trồng dày, hạt giống nhiễm virus, tiếp xúc cơ giới khi chăm sóc',
                'steps': ['Dùng hạt giống đã qua xử lý, nguồn gốc rõ ràng', 'Khử trùng tay, dao kéo, dụng cụ tỉa cành giữa các cây', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng khảm nặng', 'Luân canh với cây khác họ cà (Solanaceae)']},
            'Tomato___Tomato_Yellow_Leaf_Curl_Virus': {
                'name_vi': 'Xoăn vàng lá', 'level': 'Nặng',
                'pathogen': 'Virus Tomato Yellow Leaf Curl Virus (TYLCV), lây qua bọ phấn trắng Bemisia tabaci',
                'conditions': 'Vụ cà chua sớm và vụ xuân hè - thời điểm mật độ bọ phấn trắng cao',
                'steps': ['Phun thuốc trừ bọ phấn trắng (môi giới truyền bệnh)', 'Dùng lưới chắn côn trùng ở vườn ươm và cây con', 'Có thể dùng thiên địch (ong ký sinh Encarsia formosa) hoặc dầu neem', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng xoăn vàng lá nặng']},
            'Tomato___healthy': {
                'name_vi': 'Cây khỏe mạnh', 'level': 'Nhẹ',
                'pathogen': 'Không có tác nhân gây bệnh',
                'conditions': 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh',
                'steps': ['Duy trì chế độ chăm sóc hiện tại', 'Thăm vườn thường xuyên để phát hiện sớm bất thường', 'Bón phân cân đối, đảm bảo thoát nước tốt mùa mưa']},
        }
    },
}

DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# ==== LOAD TAT CA MODEL LUC KHOI DONG (chi 1 lan) ====
loaded_models = {}

def load_model(config):
    model = models.efficientnet_b0(weights=None)
    model.classifier[1] = nn.Linear(model.classifier[1].in_features, len(config["class_names"]))
    model.load_state_dict(torch.load(config["model_path"], map_location=DEVICE))
    model.to(DEVICE)
    model.eval()
    return model

print(f"Dang tai cac model... Chay tren: {DEVICE}")
for crop_key, config in CROP_CONFIGS.items():
    try:
        loaded_models[crop_key] = load_model(config)
        print(f"  -> Da tai model cho '{crop_key}' ({config['model_path']})")
    except FileNotFoundError:
        print(f"  -> CANH BAO: khong tim thay {config['model_path']}, bo qua cay '{crop_key}'")
print("San sang phuc vu.")

transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

# ==== FASTAPI APP ====
app = FastAPI(title="AgriAI - Multi-Crop Disease Detection API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {
        "status": "ok",
        "message": "AgriAI Multi-Crop Disease Detection API dang chay",
        "available_crops": list(loaded_models.keys()),
    }


@app.post("/predict")
async def predict(file: UploadFile = File(...), crop: str = Form(...)):
    crop = crop.lower()

    if crop not in loaded_models:
        raise HTTPException(
            status_code=400,
            detail=f"Cay '{crop}' chua co model. Cac cay ho tro: {list(loaded_models.keys())}"
        )

    if not file.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="File phai la anh (jpg, png...)")

    try:
        contents = await file.read()
        image = Image.open(io.BytesIO(contents)).convert("RGB")
    except Exception:
        raise HTTPException(status_code=400, detail="Khong doc duoc anh, file co the bi loi")

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
        "pathogen": info.get("pathogen", ""),
        "conditions": info.get("conditions", ""),
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
