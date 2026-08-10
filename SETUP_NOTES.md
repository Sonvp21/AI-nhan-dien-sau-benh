# AgriAI - Ghi chú cài đặt server (backup)

Domain Laravel demo: (xem vhost đang dùng)
Domain FastAPI (qua reverse proxy chung domain): https://aiplant.girc.edu.vn/predict
VPS: 14.225.202.210, path: ~/project/AI-nhan-dien-sau-benh

## 1. Clone code
- `cd ~/project && git clone https://github.com/Sonvp21/AI-nhan-dien-sau-benh.git`

## 2. Laravel
- `cd AI-nhan-dien-sau-benh`
- `composer install --no-dev --optimize-autoloader`
- Tạo `.env`, sửa DB/APP_URL nếu cần
- `php artisan key:generate`
- `chmod -R 775 storage bootstrap/cache`
- Vhost OpenLiteSpeed: docRoot trỏ `public/`, domain `aiplant.girc.edu.vn`

## 3. Python / FastAPI
- `python3 -m venv venv`
- `source venv/bin/activate`
- Tạo `requirements.txt` (fastapi, uvicorn[standard], python-multipart, torch, torchvision, pillow) rồi `pip install -r requirements.txt`
- **Copy 2 file model từ máy Windows lên** (không nằm trong git):
  ```
  scp best_tea_model.pth best_rice_model.pth girc-son@14.225.202.210:~/project/AI-nhan-dien-sau-benh/
  ```

## 4. Chạy FastAPI nền bằng systemd
- Tạo `/etc/systemd/system/agriai.service`:
  ```
  [Unit]
  Description=AgriAI FastAPI Service - Tea/Rice Disease Detection
  After=network.target

  [Service]
  Type=simple
  User=girc-son
  WorkingDirectory=/home/girc-son/project/AI-nhan-dien-sau-benh
  Environment="PATH=/home/girc-son/project/AI-nhan-dien-sau-benh/venv/bin"
  ExecStart=/home/girc-son/project/AI-nhan-dien-sau-benh/venv/bin/uvicorn app:app --host 127.0.0.1 --port 8000
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```
- `sudo systemctl daemon-reload`
- `sudo systemctl enable agriai`
- `sudo systemctl start agriai`
- Kiểm tra: `sudo systemctl status agriai` (phải "active running")
- Log lỗi: `sudo journalctl -u agriai -n 50 --no-pager`

## 5. Reverse proxy OpenLiteSpeed (cùng domain Laravel, tránh CORS)
Trong vhost Laravel (`AI-nhan-dien-sau-benh`):
- Tab **External App** → Add:
  - Type: Web Server, Name: `agriai`, Address: `127.0.0.1:8000`
- Tab **Context** → Add:
  - Type: Proxy, URI: `/predict`, Web Server: `agriai`
- Tab **Rewrite** → thêm **lên đầu** rule (trước mọi rule khác), để tránh Laravel "nuốt" mất request `/predict`:
  ```
  RewriteCond %{REQUEST_URI} ^/predict
  RewriteRule ^ - [L]
  ```
- Restart (graceful reload đôi khi KHÔNG đủ để nạp Context mới, cần restart cứng):
  ```
  sudo /usr/local/lsws/bin/lswsctrl stop
  sudo /usr/local/lsws/bin/lswsctrl start
  ```

## 6. Test nhanh sau khi cài lại
- `curl http://127.0.0.1:8000/` -> phải thấy `"available_crops":["che","lua"]`
- `curl -s --http1.1 -X POST https://aiplant.girc.edu.vn/predict -F "crop=che" -F "file=@duong_dan_anh.jpg"` -> phải ra JSON kết quả
- (curl trần không kèm file/-X POST rỗng sẽ báo "Invalid HTTP request received" - đây KHÔNG phải lỗi, chỉ do thiếu body, bỏ qua)

## Lưu ý quan trọng
- `.pth` (model), `tea_data/`, `rice_data*/`, `access_token`, `.env` KHÔNG nằm trong git (xem `.gitignore`) -> phải tự copy tay khi setup lại máy mới
- Sau mỗi lần train model mới -> nhớ `scp` đè lên server + `sudo systemctl restart agriai` (model chỉ load 1 lần lúc service khởi động, không tự nhận model mới)
- Sau mỗi lần sửa `agri-index.blade.php`/`app.py` trên server qua patch script -> nhớ đồng bộ ngược lại vào code trên máy Windows + git, tránh lệch code giữa 2 nơi

## 7. Cách thêm 1 cây mới (áp dụng cho đủ 5 cây: Lúa, Ngô, Sắn, Cà chua, Chè)
Đã xong: **Chè** (82% test acc), **Lúa** (~99%+ sau khi xử lý data leakage).
Còn lại: **Ngô, Sắn, Cà chua**.

Mỗi cây mới lặp lại đúng quy trình:
1. Tìm dataset Kaggle (tìm theo tên tiếng Anh của cây + "leaf disease"), chọn dataset theo tiêu chí: usability cao, nhiều notebook cộng đồng, không phải bản re-upload trùng file/size với dataset khác.
2. `kaggle datasets download -d <slug>` -> giải nén -> `find <thu_muc> -maxdepth 3` xem đúng cấu trúc lớp bệnh thật.
3. Đếm ảnh mỗi lớp, kiểm tra cân bằng.
4. **Chạy `check_duplicates.py` rồi `dedup_dataset.py` TRƯỚC KHI train** (đã học bài học từ Lúa: nếu bỏ qua bước này, accuracy 100% ảo do data leakage).
5. Copy `train_rice.py`, đổi tên (vd `train_corn.py`), sửa `SOURCE_DIR` trỏ đúng dataset đã dedup, sửa comment mô tả lớp bệnh.
6. Train xong, kiểm tra confusion matrix có hợp lý không (không còn 100% tuyệt đối là dấu hiệu tốt, không phải xấu).
7. `scp best_<ten_cay>_model.pth` lên VPS.
8. Sửa `app.py` (cả trên Windows và server, nhớ đồng bộ):
   - Thêm 1 entry mới vào `CROP_CONFIGS` (model_path, class_names, disease_info tiếng Việt cho từng lớp bệnh)
9. Sửa `agri-index.blade.php`:
   - Thêm cây mới vào `cropApiKey` mapping (vd `'Ngô':'ngo'`)
   - Sửa điều kiện check AI thật (hiện đang check theo tên cây cụ thể, mở rộng thêm)
10. `git add/commit/push` (Windows) -> `git pull` (server) -> `sudo systemctl restart agriai`
11. Test qua domain thật: `curl -s --http1.1 -X POST https://aiplant.girc.edu.vn/predict -F "crop=<key_moi>" -F "file=@anh.jpg"`


Danh sách dataset đã chọn — 7 cây
#	Cây	Dataset	Link
1	🍃 Chè	Identifying Disease in Tea leaves — Shashwat Tiwari	https://www.kaggle.com/datasets/shashwatwork/identifying-disease-in-tea-leafs
2	🌾 Lúa	Rice Leaf Disease Images — Nirmal Sankalana	https://www.kaggle.com/datasets/nirmalsankalana/rice-leaf-disease-image
3	🌽 Ngô	Corn or Maize Leaf Disease Dataset — Smaranjit Ghose	https://www.kaggle.com/datasets/smaranjitghose/corn-or-maize-leaf-disease-dataset
4	🍠 Sắn	Cassava Leaf Disease Classification — Nirmal Sankalana	https://www.kaggle.com/datasets/nirmalsankalana/cassava-leaf-disease-classification
5	🍅 Cà chua	Tomato leaf disease detection — kaustubh b	https://www.kaggle.com/datasets/kaustubhb999/tomatoleaf
6	🥭 Xoài	Mango🥭 Leaf🍃🍂 Disease Dataset — Arya Shah	https://www.kaggle.com/datasets/aryashah2k/mango-leaf-disease-dataset
7	🌶️ Ớt	PlantVillage (trích riêng phần Pepper bell) — Mohit Singh	https://www.kaggle.com/datasets/mohitsingh1804/plantvillage