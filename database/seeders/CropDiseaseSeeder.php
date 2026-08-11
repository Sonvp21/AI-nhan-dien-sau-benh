<?php

namespace Database\Seeders;

use App\Models\Disease;
use Illuminate\Database\Seeder;

class CropDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'che' => [
                ['Anthracnose', 'Thán thư', 'Nấm Colletotrichum camelliae (C. gloeosporioides)', 'Thời tiết ấm ẩm (tháng 3-4 và mùa mưa), lá/chồi non bị tổn thương cơ giới', 'Nặng',
                    ['Tiêu hủy triệt để lá, chồi, cành bị bệnh', 'Hạn chế tổn thương cơ giới khi hái búp, đốn tỉa', 'Bón phân cân đối, tạo tán thông thoáng', 'Phun thuốc đặc trị gốc đồng hoặc Carbendazim khi mới chớm bệnh'], 'tai_lieu_chuyen_nganh'],
                ['algal leaf', 'Đốm rong (bệnh tảo)', 'Tảo Cephaleuros virescens', 'Nương chè rậm rạp, thiếu chăm sóc, cây già cỗi, mùa mưa kéo dài', 'Trung bình',
                    ['Không trồng quá dày, chăm sóc cây sinh trưởng khỏe', 'Tỉa bỏ cành lá già bệnh nặng, tiêu hủy', 'Quét thuốc gốc đồng đậm đặc lên vùng bệnh trên thân/cành'], 'tai_lieu_chuyen_nganh'],
                ['bird eye spot', 'Đốm mắt cua', 'Nấm Cercospora theae (Pseudocercospora theae)', 'Mưa nhiều, ẩm độ cao, nương chè rậm rạp thiếu thông thoáng', 'Trung bình',
                    ['Đốn tỉa định kỳ tạo tán thông thoáng', 'Thu dọn lá rụng, tàn dư sau thu hái/đốn', 'Phun thuốc gốc đồng hoặc Antracol 70WP khi mới xuất hiện'], 'tai_lieu_chuyen_nganh'],
                ['brown blight', 'Đốm nâu (chè)', 'Nấm Colletotrichum camelliae, đôi khi kết hợp Pestalotiopsis theae', 'Mùa mưa, nương chè rậm rạp chăm sóc kém, ẩm độ cao kéo dài', 'Nặng',
                    ['Cắt bỏ, tiêu hủy lá cành bệnh nặng', 'Đốn tỉa tạo thông thoáng, tránh trồng quá dày', 'Hạn chế bón thừa đạm mùa mưa, tăng kali', 'Phun Antracol 70WP hoặc thuốc gốc đồng sau khi hái'], 'tai_lieu_chuyen_nganh'],
                ['gray light', 'Đốm xám', 'Nấm Pestalotiopsis (Pseudopestalotiopsis) theae', 'Mưa ẩm, nhiệt độ 25-28°C, mạnh nhất tháng 7-10, vết thương cơ giới do hái chè', 'Trung bình',
                    ['Bón phân cân đối, tưới tiêu hợp lý', 'Cày vùi lá cành sau đốn (ép xanh) diệt nguồn bệnh', 'Hái chè đúng kỹ thuật hạn chế vết thương', 'Phun Amtech 100EW hoặc thuốc gốc đồng'], 'tai_lieu_chuyen_nganh'],
                ['healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ 2 tuần/lần vào mùa mưa', 'Bón phân cân đối NPK theo giai đoạn sinh trưởng'], 'tai_lieu_chuyen_nganh'],
                ['red leaf spot', 'Đốm lá đỏ', 'Nấm Phoma theicola hoặc Cercospora theae', 'Nương chè già cỗi, đất nghèo dinh dưỡng, thiếu kali, thoát nước kém', 'Trung bình',
                    ['Bón phân cân đối, tăng kali và phân hữu cơ', 'Đốn tạo tán hợp lý, loại bỏ cành già', 'Phun thuốc gốc đồng hoặc thuốc trừ nấm phổ rộng'], 'tai_lieu_chuyen_nganh'],
                ['white spot', 'Đốm trắng', 'Nấm Phyllosticta theicola hoặc Pseudocercospora sp.', 'Nương chè rậm rạp, thiếu ánh sáng, độ ẩm cao, thông thoáng kém', 'Trung bình',
                    ['Đốn tỉa tạo tán thông thoáng', 'Hái chè đúng kỹ thuật, đúng lứa', 'Thu gom tiêu hủy lá bệnh rụng', 'Phun thuốc gốc đồng hoặc Mancozeb'], 'tai_lieu_chuyen_nganh'],
            ],
            'lua' => [
                ['Bacterialblight', 'Bạc lá', 'Vi khuẩn Xanthomonas oryzae', 'Mưa lớn, gió bão, ruộng bón thừa đạm, cây bị tổn thương cơ giới', 'Nặng',
                    ['Ưu tiên giống lúa kháng bệnh', 'Thu gom tiêu hủy tàn dư cây bệnh sau thu hoạch', 'Bón phân cân đối, không thừa đạm, điều tiết nước hợp lý', 'Phun phòng gốc Đồng hydroxide hoặc Oxolinic acid trước/sau mưa giông'], 'tai_lieu_chuyen_nganh'],
                ['Blast', 'Đạo ôn lá', 'Nấm Pyricularia oryzae (Magnaporthe oryzae)', 'Ẩm độ cao (>93%), sương mù, mưa phùn kéo dài, nhiệt độ 25-28°C, bón thừa đạm', 'Nặng',
                    ['Luân canh, dùng giống ít mẫn cảm với đạo ôn', 'Bón NPK cân đối, không thừa đạm, tăng kali', 'Phun thuốc khi tỷ lệ lá bệnh đạt khoảng 10%', 'Theo dõi lại sau 5 ngày, so sánh mức độ lây lan'], 'tai_lieu_chuyen_nganh'],
                ['Brownspot', 'Đốm nâu (lúa)', 'Nấm Helminthosporium oryzae (Bipolaris oryzae), Curvularia lunata', 'Đất nghèo dinh dưỡng (đất phèn, đất cát), ruộng thiếu kali, gieo sạ dày', 'Trung bình',
                    ['Vệ sinh đồng ruộng, xử lý rơm rạ bằng chế phẩm vi sinh', 'Gieo sạ mật độ hợp lý (100-120 kg giống/ha)', 'Bón phân cân đối, bổ sung lân và kali', 'Phun thuốc nhóm Triazole hoặc Carbendazim khi chớm bệnh'], 'tai_lieu_chuyen_nganh'],
                ['Tungro', 'Vàng lùn - Lùn xoắn lá', 'Phức hợp virus (Tungro, virus Lùn lúa cỏ, Lùn xoắn lá) do rầy nâu/rầy xanh truyền', 'Mật độ rầy môi giới cao, gieo sạ không đồng loạt tạo nguồn thức ăn liên tục cho rầy', 'Nặng',
                    ['Theo dõi mật độ rầy, phun trừ rầy khi đạt ~3 con/dảnh trong 40 ngày đầu', 'Gieo sạ đồng loạt, né rầy theo khuyến cáo địa phương', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng bệnh', 'Ưu tiên giống kháng/ít nhiễm rầy'], 'tai_lieu_chuyen_nganh'],
            ],
            'ngo' => [
                ['Blight', 'Đốm lá lớn (khô vằn lá)', 'Nấm Exserohilum turcicum', 'Nóng ẩm, mưa nhiều, nhiệt độ 20-27°C, thường tăng nhanh từ giai đoạn trổ cờ', 'Nặng',
                    ['Ưu tiên giống ngô lai kháng bệnh đốm lá lớn', 'Thu gom tiêu hủy tàn dư thân lá sau thu hoạch', 'Trồng mật độ vừa phải, bón phân cân đối, luân canh', 'Phun thuốc gốc Mancozeb hoặc Propiconazole khi chớm bệnh'], 'tai_lieu_chuyen_nganh'],
                ['Common_Rust', 'Gỉ sắt', 'Nấm Puccinia sorghi (Puccinia maydis)', 'Thời tiết mát ẩm, nhiệt độ 17-18°C, ẩm độ trên 95%, có sương hoặc mưa kéo dài', 'Trung bình',
                    ['Chọn giống kháng hoặc ít nhiễm bệnh gỉ sắt', 'Trồng đúng thời vụ, mật độ hợp lý, luân canh', 'Thu gom tiêu hủy tàn dư cây bệnh', 'Phun thuốc gốc Đồng hoặc Dithane/Anvil/Kumulus khi chớm bệnh'], 'tai_lieu_chuyen_nganh'],
                ['Gray_Leaf_Spot', 'Đốm xám lá', 'Nấm Cercospora zeae-maydis (C. zeina)', 'Ấm ẩm, nhiệt độ 25-35°C, ẩm độ cao về đêm, ruộng trồng dày, độc canh nhiều vụ', 'Trung bình',
                    ['Luân canh với cây khác họ ít nhất 1 vụ', 'Cày vùi hoặc tiêu hủy triệt để tàn dư thân lá', 'Ưu tiên giống ngô lai kháng bệnh', 'Phun phòng sớm bằng Azoxystrobin, Mancozeb hoặc Propiconazole'], 'tai_lieu_chuyen_nganh'],
                ['Healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Thăm đồng thường xuyên, đặc biệt trước/trong trổ cờ - phun râu', 'Bón phân cân đối N-P-K theo giai đoạn sinh trưởng'], 'tai_lieu_chuyen_nganh'],
            ],
            'san' => [
                ['Cassava___bacterial_blight', 'Cháy lá vi khuẩn', 'Vi khuẩn Xanthomonas axonopodis (X. phaseoli) pv. manihotis', 'Mưa nhiều, ẩm độ cao, dùng hom giống bệnh, cây bị tổn thương cơ giới', 'Nặng',
                    ['Chọn hom giống sạch bệnh, không lấy từ vùng đang có dịch', 'Tiêu hủy triệt để tàn dư cây bệnh, khử trùng dụng cụ cắt hom', 'Luân canh, không trồng sắn liên tục nhiều vụ trên cùng đất', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng'], 'tai_lieu_chuyen_nganh'],
                ['Cassava___brown_streak_disease', 'Đốm/Sọc nâu (CBSD)', 'Virus CBSV/UCBSV, lây qua hom giống và bọ phấn trắng (Bemisia tabaci)', 'Mật độ bọ phấn trắng cao, dùng hom giống không rõ nguồn gốc', 'Nặng',
                    ['Chỉ lấy hom giống từ ruộng đã kiểm tra không nhiễm virus', 'Theo dõi và phun trừ bọ phấn trắng khi cần thiết', 'Nhổ bỏ tiêu hủy sớm cây nghi ngờ nhiễm bệnh', 'Ưu tiên giống có khả năng chống chịu CBSD nếu có'], 'tai_lieu_chuyen_nganh'],
                ['Cassava___green_mottle', 'Khảm xanh', 'Virus Cassava green mottle virus (CGMV)', 'Sử dụng hom giống nhiễm bệnh, triệu chứng rõ sau trồng 2-5 tháng', 'Trung bình',
                    ['Chọn hom giống sạch bệnh (biện pháp quan trọng nhất)', 'Nhổ bỏ, đốt tiêu hủy ngay khi phát hiện triệu chứng', 'Hạn chế vận chuyển hom giống từ vùng có dịch'], 'tai_lieu_chuyen_nganh'],
                ['Cassava___healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Chọn hom giống sạch bệnh cho vụ sau', 'Theo dõi định kỳ phát hiện sớm bất thường'], 'tai_lieu_chuyen_nganh'],
                ['Cassava___mosaic_disease', 'Khảm lá sắn', 'Sri Lanka Cassava Mosaic Virus (Begomovirus), lây qua hom giống và bọ phấn trắng', 'Mật độ bọ phấn trắng cao, hom giống không rõ nguồn gốc từ vùng có dịch', 'Nặng',
                    ['Dùng giống kháng và hom giống sạch bệnh', 'Không vận chuyển hom/thân từ vùng dịch sang vùng chưa nhiễm', 'Diệt trừ bọ phấn trắng bằng Nitenpyram+Pymetrozine hoặc Dinotefuran', 'Tiêu hủy cây bệnh; ruộng nhiễm >70% cần tiêu hủy toàn bộ theo hướng dẫn BVTV'], 'tai_lieu_chuyen_nganh'],
            ],
            'ca_chua' => [
                ['Tomato___Bacterial_spot', 'Đốm vi khuẩn', 'Vi khuẩn Xanthomonas spp. (X. campestris pv. vesicatoria...)', 'Nhiệt độ 24-30°C, ẩm độ >80%, mùa mưa (tháng 5-10), mưa xen nắng', 'Nặng',
                    ['Xử lý hạt giống trong nước ấm 50°C/30 phút hoặc thuốc trừ khuẩn', 'Phun phòng khi cây ra lá non (15-20 ngày sau trồng)', 'Luân canh, tránh tưới làm bắn đất lên lá', 'Phun trị khi 5-10% lá/quả có đốm, lặp lại 2-3 lần cách 5-7 ngày'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Early_blight', 'Đốm vòng (dịch sớm)', 'Nấm Alternaria solani', 'Ẩm ướt, nhiệt độ ấm, cây thiếu dinh dưỡng, thường tấn công lá già trước', 'Trung bình',
                    ['Luân canh với cây khác họ cà', 'Tỉa bỏ lá già, lá bệnh phía dưới gốc', 'Bón phân cân đối giúp cây khỏe', 'Phun thuốc gốc Mancozeb hoặc Chlorothalonil khi mới xuất hiện'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Late_blight', 'Mốc sương (dịch muộn)', 'Nấm Phytophthora infestans', 'Ẩm độ cao, nhiệt độ 18-22°C, mưa nắng xen kẽ, sương mù, đất trũng thoát nước kém', 'Nặng',
                    ['Thu dọn sạch tàn dư cây bệnh sau vụ', 'Tỉa cành tạo thoáng, giảm ẩm độ trong vườn', 'Ưu tiên giống kháng Phytophthora infestans', 'Phun Mancozeb, Metalaxyl hoặc Tebuconazole ngay khi chớm bệnh - cực nguy hiểm'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Leaf_Mold', 'Mốc lá', 'Nấm Passalora fulva (Cladosporium fulvum)', 'Ẩm độ không khí trên 85%, phổ biến ở nhà màng/nhà kính thiếu thông gió', 'Trung bình',
                    ['Đảm bảo thông gió tốt trong nhà màng/nhà kính', 'Tránh tưới nước lên lá vào chiều tối', 'Tỉa bỏ lá già gần gốc, lá bệnh', 'Phun thuốc gốc đồng khi bệnh mới xuất hiện'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Septoria_leaf_spot', 'Đốm lá Septoria', 'Nấm Septoria lycopersici', 'Ẩm độ cao, nhiệt độ 20-25°C, thường xuất hiện giai đoạn giữa-cuối vụ, từ lá già gần gốc', 'Trung bình',
                    ['Ưu tiên giống kháng bệnh đốm lá', 'Tỉa bỏ, tiêu hủy lá già, lá bệnh phía dưới gốc', 'Dọn sạch tàn dư sau thu hoạch', 'Phun thuốc gốc đồng hoặc Mancozeb khi mới xuất hiện'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Spider_mites Two-spotted_spider_mite', 'Nhện đỏ hai chấm', 'Nhện Tetranychus urticae (dịch hại, không phải bệnh do vi sinh vật)', 'Thời tiết khô nóng, mật độ trồng dày, thiếu thiên địch hoặc lạm dụng thuốc trừ sâu phổ rộng', 'Trung bình',
                    ['Hạn chế phun thuốc phổ rộng để bảo tồn thiên địch (bọ rùa)', 'Phun nước mạnh vào mặt dưới lá khi mật độ còn thấp', 'Tưới đủ ẩm trong mùa khô hạn', 'Dùng thuốc trừ nhện đặc hiệu khi mật độ vượt ngưỡng, luân phiên hoạt chất'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Target_Spot', 'Đốm mắt tiêu', 'Nấm Corynespora cassiicola', 'Nóng ẩm, nhiệt độ 20-24°C, mưa nhiều, vườn trồng rậm rạp thiếu thông thoáng', 'Trung bình',
                    ['Luân canh, vệ sinh vườn, tiêu hủy tàn dư lá bệnh', 'Trồng mật độ hợp lý, tỉa lá già tạo thoáng', 'Phun thuốc gốc đồng hoặc Chlorothalonil khi mới xuất hiện'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Tomato_mosaic_virus', 'Khảm virus', 'Virus Tomato mosaic virus (ToMV), thuộc nhóm Tobamovirus', 'Vệ sinh dụng cụ kém, mật độ trồng dày, hạt giống nhiễm virus, tiếp xúc cơ giới khi chăm sóc', 'Nặng',
                    ['Dùng hạt giống đã qua xử lý, nguồn gốc rõ ràng', 'Khử trùng tay, dao kéo, dụng cụ tỉa cành giữa các cây', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng khảm nặng', 'Luân canh với cây khác họ cà (Solanaceae)'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___Tomato_Yellow_Leaf_Curl_Virus', 'Xoăn vàng lá', 'Virus Tomato Yellow Leaf Curl Virus (TYLCV), lây qua bọ phấn trắng Bemisia tabaci', 'Vụ cà chua sớm và vụ xuân hè - thời điểm mật độ bọ phấn trắng cao', 'Nặng',
                    ['Phun thuốc trừ bọ phấn trắng (môi giới truyền bệnh)', 'Dùng lưới chắn côn trùng ở vườn ươm và cây con', 'Có thể dùng thiên địch (ong ký sinh Encarsia formosa) hoặc dầu neem', 'Nhổ bỏ tiêu hủy sớm cây có triệu chứng xoăn vàng lá nặng'], 'tai_lieu_chuyen_nganh'],
                ['Tomato___healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Thăm vườn thường xuyên để phát hiện sớm bất thường', 'Bón phân cân đối, đảm bảo thoát nước tốt mùa mưa'], 'tai_lieu_chuyen_nganh'],
            ],
            'xoai' => [
                ['Anthracnose', 'Thán thư', 'Nấm Colletotrichum gloeosporioides', 'Ẩm độ cao, mưa nhiều, thường phát sinh mạnh vào mùa mưa và giai đoạn ra hoa/đậu quả', 'Nặng',
                    ['Tỉa cành tạo tán thông thoáng, giảm ẩm độ trong tán', 'Phun thuốc gốc đồng hoặc Mancozeb định kỳ mùa mưa', 'Thu gom, tiêu hủy lá và quả rụng bị bệnh'], 'ai_bien_soan'],
                ['Bacterial Canker', 'Loét vi khuẩn', 'Vi khuẩn Xanthomonas campestris pv. mangiferaeindicae', 'Mưa nhiều kèm gió mạnh, vết thương cơ giới trên lá/cành/quả', 'Nặng',
                    ['Cắt bỏ, tiêu hủy cành lá quả bị loét nặng', 'Phun thuốc gốc đồng phòng bệnh trước mùa mưa', 'Khử trùng dụng cụ cắt tỉa giữa các cây'], 'ai_bien_soan'],
                ['Cutting Weevil', 'Sâu đục cành (mọt cắt cành)', 'Côn trùng gây hại - Cryptorhynchus sp.', 'Vườn rậm rạp, cành lá già, mật độ trồng dày', 'Trung bình',
                    ['Cắt bỏ, tiêu hủy cành bị đục ngay khi phát hiện', 'Vệ sinh vườn, tỉa cành tạo thông thoáng', 'Dùng thuốc trừ sâu đặc hiệu khi mật độ cao'], 'ai_bien_soan'],
                ['Die Back', 'Khô cành (chết ngược cành)', 'Nấm Lasiodiplodia theobromae (Botryodiplodia)', 'Cây suy yếu, tổn thương sau thu hoạch/cắt tỉa, thời tiết khô hạn kéo dài', 'Nặng',
                    ['Cắt bỏ cành khô, tiêu hủy xa vườn', 'Quét thuốc gốc đồng lên vết cắt', 'Bón phân cân đối tăng sức đề kháng cho cây'], 'ai_bien_soan'],
                ['Gall Midge', 'Sâu tạo nốt sần (muỗi năn)', 'Côn trùng gây hại - Procontarinia sp. (muỗi năn xoài)', 'Giai đoạn ra lá non, đọt non, mật độ vườn dày', 'Trung bình',
                    ['Cắt tỉa, tiêu hủy lá non bị tạo nốt sần', 'Phun thuốc trừ sâu khi đọt non mới nhú', 'Theo dõi vườn định kỳ giai đoạn ra đọt'], 'ai_bien_soan'],
                ['Healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ, đặc biệt giai đoạn ra hoa/đậu quả', 'Bón phân cân đối theo giai đoạn sinh trưởng'], 'ai_bien_soan'],
                ['Powdery Mildew', 'Phấn trắng', 'Nấm Oidium mangiferae', 'Thời tiết mát, ẩm độ cao vào sáng sớm, thường gây hại nặng trên hoa và quả non', 'Trung bình',
                    ['Phun lưu huỳnh hoặc thuốc gốc lưu huỳnh khi cây ra hoa', 'Tỉa cành tạo tán thông thoáng, đón nắng', 'Theo dõi kỹ giai đoạn ra hoa - đậu quả non'], 'ai_bien_soan'],
                ['Sooty Mould', 'Bồ hóng (muội đen)', 'Nấm hoại sinh (Capnodium sp.) phát triển trên dịch mật của rệp/rầy', 'Vườn có rệp sáp/rầy mềm gây hại tiết dịch mật tạo môi trường cho nấm phát triển', 'Nhẹ',
                    ['Diệt trừ rệp/rầy - nguồn gốc gây bệnh', 'Rửa lá bằng nước xà phòng loãng khi mật độ nhẹ', 'Phun thuốc trừ côn trùng chích hút định kỳ'], 'ai_bien_soan'],
            ],
            'ot' => [
                ['Pepper,_bell___Bacterial_spot', 'Đốm vi khuẩn', 'Vi khuẩn Xanthomonas campestris pv. vesicatoria', 'Nhiệt độ ấm, ẩm độ cao, mưa nhiều, tưới phun làm bắn nước lên lá', 'Nặng',
                    ['Dùng hạt giống sạch bệnh, xử lý hạt trước khi gieo', 'Luân canh, tránh tưới làm bắn đất/nước lên lá', 'Phun thuốc gốc đồng khi mới xuất hiện triệu chứng', 'Tiêu hủy tàn dư cây bệnh sau thu hoạch'], 'ai_bien_soan'],
                ['Pepper,_bell___healthy', 'Cây khỏe mạnh', null, 'Cây sinh trưởng bình thường, không có dấu hiệu bệnh', 'Nhẹ',
                    ['Duy trì chế độ chăm sóc hiện tại', 'Kiểm tra định kỳ phát hiện sớm bất thường', 'Bón phân cân đối, đảm bảo thoát nước tốt'], 'ai_bien_soan'],
            ],
        ];

        foreach ($data as $cropKey => $diseases) {
            foreach ($diseases as [$classKey, $nameVi, $pathogen, $conditions, $level, $steps, $source]) {
                Disease::updateOrCreate(
                    ['crop_key' => $cropKey, 'class_key' => $classKey],
                    [
                        'name_vi' => $nameVi,
                        'pathogen' => $pathogen,
                        'conditions' => $conditions,
                        'level' => $level,
                        'recommended_steps' => $steps,
                        'info_source' => $source,
                    ]
                );
            }
        }
    }
}
