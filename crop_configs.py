"""
Cau hinh tat ca cac cay - gop 2 loai model:
  - "detection": model YOLO12 (co bounding box), file .pt, dat trong ai_models/
  - "classification": model EfficientNet-B0 (phan loai ca anh, khong khoanh vung), file .pth, dat o thu muc goc

Uu tien tu dong: neu 1 cay co CA HAI, luc khoi dong service se dung "detection" truoc
(chinh xac hon, co khoanh vung). Neu file .pt chua ton tai (chua train xong / chua scp len)
thi tu dong fallback sang "classification" cu. Neu ca hai deu khong co file thi bo qua cay do
(giong het co che warning cu trong app.py, khong crash service).

=> Sau nay train xong model detection cho cay nao, chi can:
   1. Copy file best.pt vao ai_models/<ten>_yolo12.pt (dung path da khai bao duoi day)
   2. Restart service (systemctl restart agriai) - se TU DONG chuyen sang dung model moi,
      khong can sua code gi them.

Them cay hoan toan moi: them 1 entry vao CROP_CONFIGS ben duoi.
"""

CROP_CONFIGS = {
    # ===================== CHE (co ca 2: uu tien detection) =====================
    "che": {
        "detection": {
            "model_path": "ai_models/tea_yolo12.pt",
            "class_names": ["Algal Leaf Spot", "Brown Blight", "Gray Blight",
                             "Healthy", "Helopeltis", "Red Leaf Spot"],
            "disease_info": {
                "Algal Leaf Spot": {
                    "name_vi": "Đốm rong (bệnh tảo)", "level": "Trung bình",
                    "pathogen": "Tảo Cephaleuros virescens",
                    "conditions": "Nương chè rậm rạp, thiếu chăm sóc, cây già cỗi, mùa mưa kéo dài",
                    "steps": ["Không trồng quá dày, chăm sóc cây sinh trưởng khỏe",
                              "Tỉa bỏ cành lá già bệnh nặng, tiêu hủy",
                              "Quét thuốc gốc đồng đậm đặc lên vùng bệnh trên thân/cành"]},
                "Brown Blight": {
                    "name_vi": "Đốm nâu (chè)", "level": "Nặng",
                    "pathogen": "Nấm Colletotrichum camelliae, đôi khi kết hợp Pestalotiopsis theae",
                    "conditions": "Mùa mưa, nương chè rậm rạp chăm sóc kém, ẩm độ cao kéo dài",
                    "steps": ["Cắt bỏ, tiêu hủy lá cành bệnh nặng",
                              "Đốn tỉa tạo thông thoáng, tránh trồng quá dày",
                              "Hạn chế bón thừa đạm mùa mưa, tăng kali",
                              "Phun Antracol 70WP hoặc thuốc gốc đồng sau khi hái"]},
                "Gray Blight": {
                    "name_vi": "Đốm xám (chè)", "level": "Trung bình",
                    "pathogen": "Nấm Pestalotiopsis (Pseudopestalotiopsis) theae",
                    "conditions": "Mưa ẩm, nhiệt độ 25-28°C, mạnh nhất tháng 7-10, vết thương cơ giới do hái chè",
                    "steps": ["Bón phân cân đối, tưới tiêu hợp lý",
                              "Cày vùi lá cành sau đốn (ép xanh) diệt nguồn bệnh",
                              "Hái chè đúng kỹ thuật hạn chế vết thương",
                              "Phun Amtech 100EW hoặc thuốc gốc đồng"]},
                "Healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại",
                              "Kiểm tra định kỳ 2 tuần/lần vào mùa mưa",
                              "Bón phân cân đối NPK theo giai đoạn sinh trưởng"]},
                "Helopeltis": {
                    "name_vi": "Bọ xít muỗi hại chè (rầy xanh)", "level": "Nặng",
                    "pathogen": "Côn trùng chích hút Helopeltis theivora (dịch hại, không phải nấm/khuẩn)",
                    "conditions": "Thời tiết ẩm, mưa xen nắng, nương chè rậm rạp thiếu thông thoáng, mạnh nhất đầu và cuối mùa mưa",
                    "steps": ["Thăm nương thường xuyên, phát hiện sớm vết chích trên búp/lá non",
                              "Đốn tỉa tạo thông thoáng, hạn chế nơi trú ẩn",
                              "Dùng bẫy dính màu vàng/xanh theo dõi mật độ",
                              "Phun thuốc trừ sâu chích hút đặc hiệu khi mật độ cao, luân phiên hoạt chất tránh kháng thuốc"]},
                "Red Leaf Spot": {
                    "name_vi": "Đốm lá đỏ", "level": "Trung bình",
                    "pathogen": "Nấm Phoma theicola hoặc Cercospora theae",
                    "conditions": "Nương chè già cỗi, đất nghèo dinh dưỡng, thiếu kali, thoát nước kém",
                    "steps": ["Bón phân cân đối, tăng kali và phân hữu cơ",
                              "Đốn tạo tán hợp lý, loại bỏ cành già",
                              "Phun thuốc gốc đồng hoặc thuốc trừ nấm phổ rộng"]},
            },
        },
        "classification": {
            "model_path": "best_tea_model.pth",
            "class_names": ["Anthracnose", "algal leaf", "bird eye spot", "brown blight",
                             "gray light", "healthy", "red leaf spot", "white spot"],
            "disease_info": {
                "Anthracnose": {
                    "name_vi": "Thán thư", "level": "Nặng",
                    "pathogen": "Nấm Colletotrichum camelliae (C. gloeosporioides)",
                    "conditions": "Thời tiết ấm ẩm (tháng 3-4 và mùa mưa), lá/chồi non bị tổn thương cơ giới",
                    "steps": ["Tiêu hủy triệt để lá, chồi, cành bị bệnh", "Hạn chế tổn thương cơ giới khi hái búp, đốn tỉa", "Bón phân cân đối, tạo tán thông thoáng", "Phun thuốc đặc trị gốc đồng hoặc Carbendazim khi mới chớm bệnh"]},
                "algal leaf": {
                    "name_vi": "Đốm rong (bệnh tảo)", "level": "Trung bình",
                    "pathogen": "Tảo Cephaleuros virescens",
                    "conditions": "Nương chè rậm rạp, thiếu chăm sóc, cây già cỗi, mùa mưa kéo dài",
                    "steps": ["Không trồng quá dày, chăm sóc cây sinh trưởng khỏe", "Tỉa bỏ cành lá già bệnh nặng, tiêu hủy", "Quét thuốc gốc đồng đậm đặc lên vùng bệnh trên thân/cành"]},
                "bird eye spot": {
                    "name_vi": "Đốm mắt cua", "level": "Trung bình",
                    "pathogen": "Nấm Cercospora theae (Pseudocercospora theae)",
                    "conditions": "Mưa nhiều, ẩm độ cao, nương chè rậm rạp thiếu thông thoáng",
                    "steps": ["Đốn tỉa định kỳ tạo tán thông thoáng", "Thu dọn lá rụng, tàn dư sau thu hái/đốn", "Phun thuốc gốc đồng hoặc Antracol 70WP khi mới xuất hiện"]},
                "brown blight": {
                    "name_vi": "Đốm nâu (chè)", "level": "Nặng",
                    "pathogen": "Nấm Colletotrichum camelliae, đôi khi kết hợp Pestalotiopsis theae",
                    "conditions": "Mùa mưa, nương chè rậm rạp chăm sóc kém, ẩm độ cao kéo dài",
                    "steps": ["Cắt bỏ, tiêu hủy lá cành bệnh nặng", "Đốn tỉa tạo thông thoáng, tránh trồng quá dày", "Hạn chế bón thừa đạm mùa mưa, tăng kali", "Phun Antracol 70WP hoặc thuốc gốc đồng sau khi hái"]},
                "gray light": {
                    "name_vi": "Đốm xám", "level": "Trung bình",
                    "pathogen": "Nấm Pestalotiopsis (Pseudopestalotiopsis) theae",
                    "conditions": "Mưa ẩm, nhiệt độ 25-28°C, mạnh nhất tháng 7-10, vết thương cơ giới do hái chè",
                    "steps": ["Bón phân cân đối, tưới tiêu hợp lý", "Cày vùi lá cành sau đốn (ép xanh) diệt nguồn bệnh", "Hái chè đúng kỹ thuật hạn chế vết thương", "Phun Amtech 100EW hoặc thuốc gốc đồng"]},
                "healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Kiểm tra định kỳ 2 tuần/lần vào mùa mưa", "Bón phân cân đối NPK theo giai đoạn sinh trưởng"]},
                "red leaf spot": {
                    "name_vi": "Đốm lá đỏ", "level": "Trung bình",
                    "pathogen": "Nấm Phoma theicola hoặc Cercospora theae",
                    "conditions": "Nương chè già cỗi, đất nghèo dinh dưỡng, thiếu kali, thoát nước kém",
                    "steps": ["Bón phân cân đối, tăng kali và phân hữu cơ", "Đốn tạo tán hợp lý, loại bỏ cành già", "Phun thuốc gốc đồng hoặc thuốc trừ nấm phổ rộng"]},
                "white spot": {
                    "name_vi": "Đốm trắng", "level": "Trung bình",
                    "pathogen": "Nấm Phyllosticta theicola hoặc Pseudocercospora sp.",
                    "conditions": "Nương chè rậm rạp, thiếu ánh sáng, độ ẩm cao, thông thoáng kém",
                    "steps": ["Đốn tỉa tạo tán thông thoáng", "Hái chè đúng kỹ thuật, đúng lứa", "Thu gom tiêu hủy lá bệnh rụng", "Phun thuốc gốc đồng hoặc Mancozeb"]},
            },
        },
    },

    # ===================== LUA (co ca 2: uu tien detection) =====================
    "lua": {
        "detection": {
            "model_path": "ai_models/rice_yolo12.pt",
            "class_names": ["Bacterial Leaf Blight", "Brown Spot", "Healthy", "Leaf Blast",
                             "Leaf Blight", "Leaf Scald", "Leaf Smut", "Narrow Brown Spot"],
            "disease_info": {
                "Bacterial Leaf Blight": {
                    "name_vi": "Bạc lá", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas oryzae",
                    "conditions": "Mưa lớn, gió bão, ruộng bón thừa đạm, cây bị tổn thương cơ giới",
                    "steps": ["Ưu tiên giống lúa kháng bệnh", "Thu gom tiêu hủy tàn dư cây bệnh sau thu hoạch", "Bón phân cân đối, không thừa đạm, điều tiết nước hợp lý", "Phun phòng gốc Đồng hydroxide hoặc Oxolinic acid trước/sau mưa giông"]},
                "Brown Spot": {
                    "name_vi": "Đốm nâu (lúa)", "level": "Trung bình",
                    "pathogen": "Nấm Helminthosporium oryzae (Bipolaris oryzae), Curvularia lunata",
                    "conditions": "Đất nghèo dinh dưỡng (đất phèn, đất cát), ruộng thiếu kali, gieo sạ dày",
                    "steps": ["Vệ sinh đồng ruộng, xử lý rơm rạ bằng chế phẩm vi sinh", "Gieo sạ mật độ hợp lý (100-120 kg giống/ha)", "Bón phân cân đối, bổ sung lân và kali", "Phun thuốc nhóm Triazole hoặc Carbendazim khi chớm bệnh"]},
                "Healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Thăm đồng định kỳ, đặc biệt giai đoạn đẻ nhánh - trổ đòng", "Bón phân cân đối N-P-K theo giai đoạn sinh trưởng"]},
                "Leaf Blast": {
                    "name_vi": "Đạo ôn lá", "level": "Nặng",
                    "pathogen": "Nấm Pyricularia oryzae (Magnaporthe oryzae)",
                    "conditions": "Ẩm độ cao (>93%), sương mù, mưa phùn kéo dài, nhiệt độ 25-28°C, bón thừa đạm",
                    "steps": ["Luân canh, dùng giống ít mẫn cảm với đạo ôn", "Bón NPK cân đối, không thừa đạm, tăng kali", "Phun thuốc khi tỷ lệ lá bệnh đạt khoảng 10%", "Theo dõi lại sau 5 ngày, so sánh mức độ lây lan"]},
                "Leaf Blight": {
                    "name_vi": "Khô vằn (cháy lá)", "level": "Nặng",
                    "pathogen": "Nấm Rhizoctonia solani",
                    "conditions": "Ruộng gieo sạ dày, bón thừa đạm, ẩm độ cao, thường phát sinh từ giai đoạn đẻ nhánh rộ",
                    "steps": ["Gieo sạ mật độ vừa phải, không bón thừa đạm giai đoạn đầu", "Vệ sinh đồng ruộng, vớt bỏ hạch nấm nổi trên mặt nước", "Giữ mực nước hợp lý, tránh ẩm độ ruộng quá cao kéo dài", "Phun Validamycin hoặc Hexaconazole khi bệnh chớm xuất hiện ở gốc"]},
                "Leaf Scald": {
                    "name_vi": "Cháy bìa lá (đốm vằn cạnh lá)", "level": "Trung bình",
                    "pathogen": "Nấm Microdochium oryzae (Monographella albescens)",
                    "conditions": "Ruộng bón thừa đạm, gieo sạ dày, thường xuất hiện giai đoạn lúa trổ - chín",
                    "steps": ["Bón phân cân đối, không thừa đạm cuối vụ", "Gieo sạ mật độ hợp lý, tạo độ thông thoáng ruộng", "Thu dọn tàn dư sau thu hoạch, luân canh cây trồng khác", "Phun thuốc gốc Mancozeb hoặc Propiconazole khi bệnh mới xuất hiện"]},
                "Leaf Smut": {
                    "name_vi": "Đốm muội đen (than lá)", "level": "Nhẹ",
                    "pathogen": "Nấm Entyloma oryzae",
                    "conditions": "Ruộng trũng, ẩm độ cao kéo dài, thường xuất hiện trên lá già giai đoạn cuối vụ",
                    "steps": ["Bệnh thường nhẹ, ít ảnh hưởng năng suất, ưu tiên theo dõi là chính", "Bón phân cân đối, tránh ruộng ngập úng kéo dài", "Vệ sinh đồng ruộng, thu dọn tàn dư sau thu hoạch", "Chỉ cần phun thuốc gốc đồng nếu bệnh lây lan mạnh bất thường"]},
                "Narrow Brown Spot": {
                    "name_vi": "Đốm nâu hẹp (gạch nâu)", "level": "Trung bình",
                    "pathogen": "Nấm Cercospora oryzae (Sphaerulina oryzina)",
                    "conditions": "Đất nghèo kali, ruộng thiếu nước giai đoạn làm đòng, cây lúa suy yếu",
                    "steps": ["Bón phân cân đối, chú trọng bổ sung kali", "Đảm bảo đủ nước tưới giai đoạn làm đòng - trổ", "Thu dọn tàn dư sau thu hoạch, luân canh", "Phun thuốc gốc đồng hoặc Mancozeb nếu bệnh lan rộng"]},
            },
        },
        "classification": {
            "model_path": "best_rice_model.pth",
            "class_names": ["Bacterialblight", "Blast", "Brownspot", "Tungro"],
            "disease_info": {
                "Bacterialblight": {
                    "name_vi": "Bạc lá", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas oryzae",
                    "conditions": "Mưa lớn, gió bão, ruộng bón thừa đạm, cây bị tổn thương cơ giới",
                    "steps": ["Ưu tiên giống lúa kháng bệnh", "Thu gom tiêu hủy tàn dư cây bệnh sau thu hoạch", "Bón phân cân đối, không thừa đạm, điều tiết nước hợp lý", "Phun phòng gốc Đồng hydroxide hoặc Oxolinic acid trước/sau mưa giông"]},
                "Blast": {
                    "name_vi": "Đạo ôn lá", "level": "Nặng",
                    "pathogen": "Nấm Pyricularia oryzae (Magnaporthe oryzae)",
                    "conditions": "Ẩm độ cao (>93%), sương mù, mưa phùn kéo dài, nhiệt độ 25-28°C, bón thừa đạm",
                    "steps": ["Luân canh, dùng giống ít mẫn cảm với đạo ôn", "Bón NPK cân đối, không thừa đạm, tăng kali", "Phun thuốc khi tỷ lệ lá bệnh đạt khoảng 10%", "Theo dõi lại sau 5 ngày, so sánh mức độ lây lan"]},
                "Brownspot": {
                    "name_vi": "Đốm nâu (lúa)", "level": "Trung bình",
                    "pathogen": "Nấm Helminthosporium oryzae (Bipolaris oryzae), Curvularia lunata",
                    "conditions": "Đất nghèo dinh dưỡng (đất phèn, đất cát), ruộng thiếu kali, gieo sạ dày",
                    "steps": ["Vệ sinh đồng ruộng, xử lý rơm rạ bằng chế phẩm vi sinh", "Gieo sạ mật độ hợp lý (100-120 kg giống/ha)", "Bón phân cân đối, bổ sung lân và kali", "Phun thuốc nhóm Triazole hoặc Carbendazim khi chớm bệnh"]},
                "Tungro": {
                    "name_vi": "Vàng lùn - Lùn xoắn lá", "level": "Nặng",
                    "pathogen": "Phức hợp virus (Tungro, virus Lùn lúa cỏ, Lùn xoắn lá) do rầy nâu/rầy xanh truyền",
                    "conditions": "Mật độ rầy môi giới cao, gieo sạ không đồng loạt tạo nguồn thức ăn liên tục cho rầy",
                    "steps": ["Theo dõi mật độ rầy, phun trừ rầy khi đạt ~3 con/dảnh trong 40 ngày đầu", "Gieo sạ đồng loạt, né rầy theo khuyến cáo địa phương", "Nhổ bỏ tiêu hủy sớm cây có triệu chứng bệnh", "Ưu tiên giống kháng/ít nhiễm rầy"]},
            },
        },
    },

    # ===================== OT (co ca 2: uu tien detection) =====================
    "ot": {
        "detection": {
            "model_path": "ai_models/chili_yolo12.pt",
            "class_names": ["Anthracnose", "Bacterial Spot", "Cercospora Leaf Spot",
                             "Curl Virus", "Fruit Fly", "Healthy", "White Spot"],
            "disease_info": {
                "Anthracnose": {
                    "name_vi": "Thán thư ớt", "level": "Nặng",
                    "pathogen": "Nấm Colletotrichum spp. (C. capsici, C. gloeosporioides)",
                    "conditions": "Mưa nhiều, ẩm độ cao, thường gây hại nặng trên quả giai đoạn chín",
                    "steps": ["Thu gom tiêu hủy quả, lá bệnh rụng để giảm nguồn bệnh", "Luân canh với cây khác họ cà", "Tránh tưới làm bắn đất lên lá/quả, lên luống cao thoát nước tốt", "Phun Mancozeb, Chlorothalonil hoặc Difenoconazole khi mới chớm bệnh"]},
                "Bacterial Spot": {
                    "name_vi": "Đốm vi khuẩn", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas campestris pv. vesicatoria",
                    "conditions": "Nhiệt độ ấm, ẩm độ cao, mưa xen nắng, lây lan qua nước tưới/mưa bắn",
                    "steps": ["Dùng hạt giống sạch bệnh, xử lý hạt trước khi gieo", "Tránh tưới phun lên lá, tưới gốc thay vì tưới tràn", "Luân canh, vệ sinh đồng ruộng sau vụ", "Phun thuốc gốc đồng khi bệnh mới xuất hiện, lặp lại 5-7 ngày/lần"]},
                "Cercospora Leaf Spot": {
                    "name_vi": "Đốm mắt ếch (đốm lá Cercospora)", "level": "Trung bình",
                    "pathogen": "Nấm Cercospora capsici",
                    "conditions": "Mưa nhiều, ẩm độ cao, vườn trồng dày thiếu thông thoáng, thường tấn công lá già trước",
                    "steps": ["Tỉa bỏ lá già, lá bệnh phía dưới gốc tiêu hủy", "Trồng mật độ hợp lý, tạo tán thông thoáng", "Luân canh với cây khác họ cà", "Phun thuốc gốc đồng hoặc Mancozeb khi mới xuất hiện"]},
                "Curl Virus": {
                    "name_vi": "Xoăn lá virus", "level": "Nặng",
                    "pathogen": "Virus xoăn lá ớt (Chilli leaf curl virus), lây qua bọ phấn trắng Bemisia tabaci",
                    "conditions": "Mật độ bọ phấn trắng cao, thời tiết nóng khô, thường nặng vụ hè thu",
                    "steps": ["Diệt trừ bọ phấn trắng (môi giới truyền bệnh) bằng thuốc đặc hiệu hoặc bẫy dính vàng", "Dùng lưới chắn côn trùng ở vườn ươm cây con", "Nhổ bỏ tiêu hủy sớm cây có triệu chứng xoăn lá nặng", "Ưu tiên giống kháng/ít nhiễm nếu có, tránh trồng liên tục nhiều vụ"]},
                "Fruit Fly": {
                    "name_vi": "Ruồi đục quả", "level": "Nặng",
                    "pathogen": "Côn trùng Bactrocera spp. (dịch hại, không phải bệnh do vi sinh vật)",
                    "conditions": "Vườn ớt giai đoạn có quả chín, mật độ ruồi cao vào mùa mưa ẩm",
                    "steps": ["Thu gom tiêu hủy quả rụng, quả bị hại để cắt vòng đời ruồi", "Dùng bẫy pheromone/bả protein dẫn dụ diệt ruồi đực", "Bao quả hoặc thu hoạch sớm khi quả vừa chín", "Phun thuốc trừ ruồi đục quả đặc hiệu khi mật độ cao, luân phiên hoạt chất"]},
                "Healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Thăm vườn thường xuyên để phát hiện sớm bất thường", "Bón phân cân đối, đảm bảo thoát nước tốt mùa mưa"]},
                "White Spot": {
                    "name_vi": "Đốm trắng", "level": "Trung bình",
                    "pathogen": "Nấm gây đốm trắng lá ớt (Pseudocercospora/Phyllosticta spp.)",
                    "conditions": "Ẩm độ cao, vườn trồng dày thiếu ánh sáng, thoát nước kém",
                    "steps": ["Trồng mật độ hợp lý, tỉa cành tạo thông thoáng", "Thu gom tiêu hủy lá bệnh rụng", "Đảm bảo thoát nước tốt, tránh đọng nước gốc", "Phun thuốc gốc đồng hoặc Mancozeb khi bệnh mới xuất hiện"]},
            },
        },
        # Fallback classification cu (port tu nhanh main, model .pth chua co tren may
        # nay - chi dung khi DIAGNOSIS_BACKEND = "local" VA chua co file detection).
        "classification": {
            "model_path": "best_pepper_model.pth",
            "class_names": ["Pepper,_bell___Bacterial_spot", "Pepper,_bell___healthy"],
            "disease_info": {
                "Pepper,_bell___Bacterial_spot": {
                    "name_vi": "Đốm vi khuẩn", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas campestris pv. vesicatoria",
                    "conditions": "Nhiệt độ ấm, ẩm độ cao, mưa nhiều, tưới phun làm bắn nước lên lá",
                    "steps": ["Dùng hạt giống sạch bệnh, xử lý hạt trước khi gieo", "Luân canh, tránh tưới làm bắn đất/nước lên lá", "Phun thuốc gốc đồng khi mới xuất hiện triệu chứng", "Tiêu hủy tàn dư cây bệnh sau thu hoạch"]},
                "Pepper,_bell___healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Kiểm tra định kỳ phát hiện sớm bất thường", "Bón phân cân đối, đảm bảo thoát nước tốt"]},
            },
        },
    },

    # ===================== XOAI (chi co classification cu, chua co detection) =====================
    # Port tu nhanh main (nguoi khac them song song luc ta dang tach crop_configs.py
    # ra file rieng) - model .pth chua co tren may nay, chi dung khi DIAGNOSIS_BACKEND
    # = "local". Khi dang dung Gemini (mac dinh) thi Xoai da chay duoc ngay qua
    # cropApiKey ben agri-app.js, khong phu thuoc entry nay.
    "xoai": {
        "classification": {
            "model_path": "best_mango_model.pth",
            "class_names": ["Anthracnose", "Bacterial Canker", "Cutting Weevil", "Die Back",
                             "Gall Midge", "Healthy", "Powdery Mildew", "Sooty Mould"],
            "disease_info": {
                "Anthracnose": {
                    "name_vi": "Thán thư", "level": "Nặng",
                    "pathogen": "Nấm Colletotrichum gloeosporioides",
                    "conditions": "Ẩm độ cao, mưa nhiều, thường phát sinh mạnh vào mùa mưa và giai đoạn ra hoa/đậu quả",
                    "steps": ["Tỉa cành tạo tán thông thoáng, giảm ẩm độ trong tán", "Phun thuốc gốc đồng hoặc Mancozeb định kỳ mùa mưa", "Thu gom, tiêu hủy lá và quả rụng bị bệnh"]},
                "Bacterial Canker": {
                    "name_vi": "Loét vi khuẩn", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas campestris pv. mangiferaeindicae",
                    "conditions": "Mưa nhiều kèm gió mạnh, vết thương cơ giới trên lá/cành/quả",
                    "steps": ["Cắt bỏ, tiêu hủy cành lá quả bị loét nặng", "Phun thuốc gốc đồng phòng bệnh trước mùa mưa", "Khử trùng dụng cụ cắt tỉa giữa các cây"]},
                "Cutting Weevil": {
                    "name_vi": "Sâu đục cành (mọt cắt cành)", "level": "Trung bình",
                    "pathogen": "Côn trùng gây hại (không phải nấm/khuẩn) - Cryptorhynchus sp.",
                    "conditions": "Vườn rậm rạp, cành lá già, mật độ trồng dày",
                    "steps": ["Cắt bỏ, tiêu hủy cành bị đục ngay khi phát hiện", "Vệ sinh vườn, tỉa cành tạo thông thoáng", "Dùng thuốc trừ sâu đặc hiệu khi mật độ cao"]},
                "Die Back": {
                    "name_vi": "Khô cành (chết ngược cành)", "level": "Nặng",
                    "pathogen": "Nấm Lasiodiplodia theobromae (Botryodiplodia)",
                    "conditions": "Cây suy yếu, tổn thương sau thu hoạch/cắt tỉa, thời tiết khô hạn kéo dài",
                    "steps": ["Cắt bỏ cành khô, tiêu hủy xa vườn", "Quét thuốc gốc đồng lên vết cắt", "Bón phân cân đối tăng sức đề kháng cho cây"]},
                "Gall Midge": {
                    "name_vi": "Sâu tạo nốt sần (muỗi năn)", "level": "Trung bình",
                    "pathogen": "Côn trùng gây hại - Procontarinia sp. (muỗi năn xoài)",
                    "conditions": "Giai đoạn ra lá non, đọt non, mật độ vườn dày",
                    "steps": ["Cắt tỉa, tiêu hủy lá non bị tạo nốt sần", "Phun thuốc trừ sâu khi đọt non mới nhú", "Theo dõi vườn định kỳ giai đoạn ra đọt"]},
                "Healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Kiểm tra định kỳ, đặc biệt giai đoạn ra hoa/đậu quả", "Bón phân cân đối theo giai đoạn sinh trưởng"]},
                "Powdery Mildew": {
                    "name_vi": "Phấn trắng", "level": "Trung bình",
                    "pathogen": "Nấm Oidium mangiferae",
                    "conditions": "Thời tiết mát, ẩm độ cao vào sáng sớm, thường gây hại nặng trên hoa và quả non",
                    "steps": ["Phun lưu huỳnh hoặc thuốc gốc lưu huỳnh khi cây ra hoa", "Tỉa cành tạo tán thông thoáng, đón nắng", "Theo dõi kỹ giai đoạn ra hoa - đậu quả non"]},
                "Sooty Mould": {
                    "name_vi": "Bồ hóng (muội đen)", "level": "Nhẹ",
                    "pathogen": "Nấm hoại sinh (Capnodium sp.) phát triển trên dịch mật của rệp/rầy",
                    "conditions": "Vườn có rệp sáp/rầy mềm gây hại tiết dịch mật tạo môi trường cho nấm phát triển",
                    "steps": ["Diệt trừ rệp/rầy - nguồn gốc gây bệnh", "Rửa lá bằng nước xà phòng loãng khi mật độ nhẹ", "Phun thuốc trừ côn trùng chích hút định kỳ"]},
            },
        },
    },

    # ===================== NGO, SAN, CA_CHUA: hien tai chi co classification cu =====================
    # Khi train xong model detection cho cay nao trong 3 cay nay, them 1 key "detection"
    # vao entry tuong ung (giong mau che/lua o tren) roi copy file .pt vao ai_models/ la xong,
    # khong can sua gi o app.py.
    "ngo": {
        "classification": {
            "model_path": "best_corn_model.pth",
            "class_names": ["Blight", "Common_Rust", "Gray_Leaf_Spot", "Healthy"],
            "disease_info": {
                "Blight": {
                    "name_vi": "Đốm lá lớn (khô vằn lá)", "level": "Nặng",
                    "pathogen": "Nấm Exserohilum turcicum",
                    "conditions": "Nóng ẩm, mưa nhiều, nhiệt độ 20-27°C, thường tăng nhanh từ giai đoạn trổ cờ",
                    "steps": ["Ưu tiên giống ngô lai kháng bệnh đốm lá lớn", "Thu gom tiêu hủy tàn dư thân lá sau thu hoạch", "Trồng mật độ vừa phải, bón phân cân đối, luân canh", "Phun thuốc gốc Mancozeb hoặc Propiconazole khi chớm bệnh"]},
                "Common_Rust": {
                    "name_vi": "Gỉ sắt", "level": "Trung bình",
                    "pathogen": "Nấm Puccinia sorghi (Puccinia maydis)",
                    "conditions": "Thời tiết mát ẩm, nhiệt độ 17-18°C, ẩm độ trên 95%, có sương hoặc mưa kéo dài",
                    "steps": ["Chọn giống kháng hoặc ít nhiễm bệnh gỉ sắt", "Trồng đúng thời vụ, mật độ hợp lý, luân canh", "Thu gom tiêu hủy tàn dư cây bệnh", "Phun thuốc gốc Đồng hoặc Dithane/Anvil/Kumulus khi chớm bệnh"]},
                "Gray_Leaf_Spot": {
                    "name_vi": "Đốm xám lá", "level": "Trung bình",
                    "pathogen": "Nấm Cercospora zeae-maydis (C. zeina)",
                    "conditions": "Ấm ẩm, nhiệt độ 25-35°C, ẩm độ cao về đêm, ruộng trồng dày, độc canh nhiều vụ",
                    "steps": ["Luân canh với cây khác họ ít nhất 1 vụ", "Cày vùi hoặc tiêu hủy triệt để tàn dư thân lá", "Ưu tiên giống ngô lai kháng bệnh", "Phun phòng sớm bằng Azoxystrobin, Mancozeb hoặc Propiconazole"]},
                "Healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Thăm đồng thường xuyên, đặc biệt trước/trong trổ cờ - phun râu", "Bón phân cân đối N-P-K theo giai đoạn sinh trưởng"]},
            },
        },
    },
    "san": {
        "classification": {
            "model_path": "best_cassava_model.pth",
            "class_names": ["Cassava___bacterial_blight", "Cassava___brown_streak_disease",
                             "Cassava___green_mottle", "Cassava___healthy", "Cassava___mosaic_disease"],
            "disease_info": {
                "Cassava___bacterial_blight": {
                    "name_vi": "Cháy lá vi khuẩn", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas axonopodis (X. phaseoli) pv. manihotis",
                    "conditions": "Mưa nhiều, ẩm độ cao, dùng hom giống bệnh, cây bị tổn thương cơ giới",
                    "steps": ["Chọn hom giống sạch bệnh, không lấy từ vùng đang có dịch", "Tiêu hủy triệt để tàn dư cây bệnh, khử trùng dụng cụ cắt hom", "Luân canh, không trồng sắn liên tục nhiều vụ trên cùng đất", "Nhổ bỏ tiêu hủy sớm cây có triệu chứng"]},
                "Cassava___brown_streak_disease": {
                    "name_vi": "Đốm/Sọc nâu (CBSD)", "level": "Nặng",
                    "pathogen": "Virus CBSV/UCBSV, lây qua hom giống và bọ phấn trắng (Bemisia tabaci)",
                    "conditions": "Mật độ bọ phấn trắng cao, dùng hom giống không rõ nguồn gốc",
                    "steps": ["Chỉ lấy hom giống từ ruộng đã kiểm tra không nhiễm virus", "Theo dõi và phun trừ bọ phấn trắng khi cần thiết", "Nhổ bỏ tiêu hủy sớm cây nghi ngờ nhiễm bệnh", "Ưu tiên giống có khả năng chống chịu CBSD nếu có"]},
                "Cassava___green_mottle": {
                    "name_vi": "Khảm xanh", "level": "Trung bình",
                    "pathogen": "Virus Cassava green mottle virus (CGMV)",
                    "conditions": "Sử dụng hom giống nhiễm bệnh, triệu chứng rõ sau trồng 2-5 tháng",
                    "steps": ["Chọn hom giống sạch bệnh (biện pháp quan trọng nhất)", "Nhổ bỏ, đốt tiêu hủy ngay khi phát hiện triệu chứng", "Hạn chế vận chuyển hom giống từ vùng có dịch"]},
                "Cassava___healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Chọn hom giống sạch bệnh cho vụ sau", "Theo dõi định kỳ phát hiện sớm bất thường"]},
                "Cassava___mosaic_disease": {
                    "name_vi": "Khảm lá sắn", "level": "Nặng",
                    "pathogen": "Sri Lanka Cassava Mosaic Virus (Begomovirus), lây qua hom giống và bọ phấn trắng",
                    "conditions": "Mật độ bọ phấn trắng cao, hom giống không rõ nguồn gốc từ vùng có dịch",
                    "steps": ["Dùng giống kháng và hom giống sạch bệnh", "Không vận chuyển hom/thân từ vùng dịch sang vùng chưa nhiễm", "Diệt trừ bọ phấn trắng bằng Nitenpyram+Pymetrozine hoặc Dinotefuran", "Tiêu hủy cây bệnh; ruộng nhiễm >70% cần tiêu hủy toàn bộ theo hướng dẫn BVTV"]},
            },
        },
    },
    "ca_chua": {
        "classification": {
            "model_path": "best_tomato_model.pth",
            "class_names": ["Tomato___Bacterial_spot", "Tomato___Early_blight", "Tomato___Late_blight",
                             "Tomato___Leaf_Mold", "Tomato___Septoria_leaf_spot",
                             "Tomato___Spider_mites Two-spotted_spider_mite", "Tomato___Target_Spot",
                             "Tomato___Tomato_mosaic_virus", "Tomato___Tomato_Yellow_Leaf_Curl_Virus",
                             "Tomato___healthy"],
            "disease_info": {
                "Tomato___Bacterial_spot": {
                    "name_vi": "Đốm vi khuẩn", "level": "Nặng",
                    "pathogen": "Vi khuẩn Xanthomonas spp. (X. campestris pv. vesicatoria...)",
                    "conditions": "Nhiệt độ 24-30°C, ẩm độ >80%, mùa mưa (tháng 5-10), mưa xen nắng",
                    "steps": ["Xử lý hạt giống trong nước ấm 50°C/30 phút hoặc thuốc trừ khuẩn", "Phun phòng khi cây ra lá non (15-20 ngày sau trồng)", "Luân canh, tránh tưới làm bắn đất lên lá", "Phun trị khi 5-10% lá/quả có đốm, lặp lại 2-3 lần cách 5-7 ngày"]},
                "Tomato___Early_blight": {
                    "name_vi": "Đốm vòng (dịch sớm)", "level": "Trung bình",
                    "pathogen": "Nấm Alternaria solani",
                    "conditions": "Ẩm ướt, nhiệt độ ấm, cây thiếu dinh dưỡng, thường tấn công lá già trước",
                    "steps": ["Luân canh với cây khác họ cà", "Tỉa bỏ lá già, lá bệnh phía dưới gốc", "Bón phân cân đối giúp cây khỏe", "Phun thuốc gốc Mancozeb hoặc Chlorothalonil khi mới xuất hiện"]},
                "Tomato___Late_blight": {
                    "name_vi": "Mốc sương (dịch muộn)", "level": "Nặng",
                    "pathogen": "Nấm Phytophthora infestans",
                    "conditions": "Ẩm độ cao, nhiệt độ 18-22°C, mưa nắng xen kẽ, sương mù, đất trũng thoát nước kém",
                    "steps": ["Thu dọn sạch tàn dư cây bệnh sau vụ", "Tỉa cành tạo thoáng, giảm ẩm độ trong vườn", "Ưu tiên giống kháng Phytophthora infestans", "Phun Mancozeb, Metalaxyl hoặc Tebuconazole ngay khi chớm bệnh - đây là bệnh cực nguy hiểm, có thể làm chết cả ruộng nhanh chóng"]},
                "Tomato___Leaf_Mold": {
                    "name_vi": "Mốc lá", "level": "Trung bình",
                    "pathogen": "Nấm Passalora fulva (Cladosporium fulvum)",
                    "conditions": "Ẩm độ không khí trên 85%, phổ biến ở nhà màng/nhà kính thiếu thông gió",
                    "steps": ["Đảm bảo thông gió tốt trong nhà màng/nhà kính", "Tránh tưới nước lên lá vào chiều tối", "Tỉa bỏ lá già gần gốc, lá bệnh", "Phun thuốc gốc đồng khi bệnh mới xuất hiện"]},
                "Tomato___Septoria_leaf_spot": {
                    "name_vi": "Đốm lá Septoria", "level": "Trung bình",
                    "pathogen": "Nấm Septoria lycopersici",
                    "conditions": "Ẩm độ cao, nhiệt độ 20-25°C, thường xuất hiện giai đoạn giữa-cuối vụ, từ lá già gần gốc",
                    "steps": ["Ưu tiên giống kháng bệnh đốm lá", "Tỉa bỏ, tiêu hủy lá già, lá bệnh phía dưới gốc", "Dọn sạch tàn dư sau thu hoạch", "Phun thuốc gốc đồng hoặc Mancozeb khi mới xuất hiện"]},
                "Tomato___Spider_mites Two-spotted_spider_mite": {
                    "name_vi": "Nhện đỏ hai chấm", "level": "Trung bình",
                    "pathogen": "Nhện Tetranychus urticae (dịch hại, không phải bệnh do vi sinh vật)",
                    "conditions": "Thời tiết khô nóng, mật độ trồng dày, thiếu thiên địch hoặc lạm dụng thuốc trừ sâu phổ rộng",
                    "steps": ["Hạn chế phun thuốc phổ rộng để bảo tồn thiên địch (bọ rùa)", "Phun nước mạnh vào mặt dưới lá khi mật độ còn thấp", "Tưới đủ ẩm trong mùa khô hạn", "Dùng thuốc trừ nhện đặc hiệu khi mật độ vượt ngưỡng, luân phiên hoạt chất"]},
                "Tomato___Target_Spot": {
                    "name_vi": "Đốm mắt tiêu", "level": "Trung bình",
                    "pathogen": "Nấm Corynespora cassiicola",
                    "conditions": "Nóng ẩm, nhiệt độ 20-24°C, mưa nhiều, vườn trồng rậm rạp thiếu thông thoáng",
                    "steps": ["Luân canh, vệ sinh vườn, tiêu hủy tàn dư lá bệnh", "Trồng mật độ hợp lý, tỉa lá già tạo thoáng", "Phun thuốc gốc đồng hoặc Chlorothalonil khi mới xuất hiện"]},
                "Tomato___Tomato_mosaic_virus": {
                    "name_vi": "Khảm virus", "level": "Nặng",
                    "pathogen": "Virus Tomato mosaic virus (ToMV), thuộc nhóm Tobamovirus",
                    "conditions": "Vệ sinh dụng cụ kém, mật độ trồng dày, hạt giống nhiễm virus, tiếp xúc cơ giới khi chăm sóc",
                    "steps": ["Dùng hạt giống đã qua xử lý, nguồn gốc rõ ràng", "Khử trùng tay, dao kéo, dụng cụ tỉa cành giữa các cây", "Nhổ bỏ tiêu hủy sớm cây có triệu chứng khảm nặng", "Luân canh với cây khác họ cà (Solanaceae)"]},
                "Tomato___Tomato_Yellow_Leaf_Curl_Virus": {
                    "name_vi": "Xoăn vàng lá", "level": "Nặng",
                    "pathogen": "Virus Tomato Yellow Leaf Curl Virus (TYLCV), lây qua bọ phấn trắng Bemisia tabaci",
                    "conditions": "Vụ cà chua sớm và vụ xuân hè - thời điểm mật độ bọ phấn trắng cao",
                    "steps": ["Phun thuốc trừ bọ phấn trắng (môi giới truyền bệnh)", "Dùng lưới chắn côn trùng ở vườn ươm và cây con", "Có thể dùng thiên địch (ong ký sinh Encarsia formosa) hoặc dầu neem", "Nhổ bỏ tiêu hủy sớm cây có triệu chứng xoăn vàng lá nặng"]},
                "Tomato___healthy": {
                    "name_vi": "Cây khỏe mạnh", "level": "Nhẹ",
                    "pathogen": "Không có tác nhân gây bệnh",
                    "conditions": "Cây sinh trưởng bình thường, không có dấu hiệu bệnh",
                    "steps": ["Duy trì chế độ chăm sóc hiện tại", "Thăm vườn thường xuyên để phát hiện sớm bất thường", "Bón phân cân đối, đảm bảo thoát nước tốt mùa mưa"]},
            },
        },
    },
}
