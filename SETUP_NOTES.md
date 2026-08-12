# AgriAI - Ghi chú cài đặt server (backup)

Domain Laravel demo: (xem vhost đang dùng)
Domain FastAPI (qua reverse proxy chung domain): https://aiplant.girc.edu.vn/predict
VPS: 14.225.202.210, path: ~/project/AI-nhan-dien-sau-benh

## 0. Backend chẩn đoán HIỆN TẠI: Gemini (qua WebAI-to-API)
`app.py` có 1 công tắc `DIAGNOSIS_BACKEND` ở đầu file, đang để `"gemini"`:
- **`"gemini"`** (hiện tại): gọi Gemini qua service **WebAI-to-API** - dùng chung cho
  **cả 7 cây**, kể cả Ngô/Sắn/Cà chua/Xoài chưa có model tự train. Gemini tự nhận diện
  luôn cây trong ảnh có khớp với cây người dùng chọn không (`crop_mismatch`), không cần
  lớp "crop gate" riêng.
- **`"local"`**: quay lại dùng model YOLO/EfficientNet tự train (mục 3-4 bên dưới, code vẫn
  còn nguyên, không bị xoá) - chỉ cần đổi `DIAGNOSIS_BACKEND = "local"` trong `app.py` rồi
  restart service, không cần sửa gì thêm.

**Kiến trúc HIỆN TẠI (đã đổi):** container WebAI-to-API chạy Docker ngay trên CHÍNH VPS
này (không còn chạy trên máy Windows local qua Cloudflare Tunnel như trước nữa), cổng
6969 chỉ bind `127.0.0.1` (không public trực tiếp), được reverse-proxy qua OpenLiteSpeed
ra domain riêng **`https://webai.girc.edu.vn`** (có SSL, có Basic Auth cho `/admin`). Nhờ
vậy không còn phụ thuộc máy Windows phải bật liên tục nữa - đây từng là 1 single point of
failure, giờ đã loại bỏ được.

**Đây KHÔNG phải Gemini API chính thức có key** - là cách dùng lại phiên đăng nhập Gemini
web cá nhân (cookie `__Secure-1PSID`/`__Secure-1PSIDTS`), giống hệt cách đang dùng cho dự
án GIRC khảo sát sạt lở (`assess_location.py`). README của WebAI-to-API ghi rõ **"intended
for research and educational purposes only"** - không dùng cho mục đích thương mại. Cookie
có thể hết hạn theo thời gian, cần đăng nhập lại gemini.google.com và cập nhật qua
`https://webai.girc.edu.vn/admin` nếu thấy lỗi 502 từ `/predict`.

**Lưu ý:** vì backend Gemini web-scraping chạy từ IP datacenter (VPS) thay vì IP nhà mạng
dân dụng, khả năng bị Google chặn/nghi ngờ traffic bất thường CAO HƠN so với khi chạy trên
máy Windows local trước đây. Cần theo dõi log container (`docker compose logs -f` trong
`~/gemini2api`) vài ngày đầu sau khi chuyển, đặc biệt tìm dòng "Response stalled"/"ReadError"
lặp lại liên tục - nếu vậy khả năng cookie/IP đã bị chặn, cần cân nhắc quay lại chạy trên
máy nhà hoặc đổi tài khoản Google khác.

**Yêu cầu để chạy được backend Gemini:**
1. Container WebAI-to-API phải đang chạy trên VPS: `cd ~/gemini2api && docker compose up -d`
2. Cookie trong `config.conf` (hoặc qua `https://webai.girc.edu.vn/admin`) còn hạn.
3. `pip install openai` (đã có trong `requirements.txt`).
4. `WEBAI_BASE_URL` trong systemd service (`agriai.service`, xem mục 4) đang set
   `https://webai.girc.edu.vn/v1` - đây là domain reverse-proxy vào Docker cùng VPS, không
   phải chạy thẳng qua `localhost:6969` (tuy cũng chạy được vì cùng máy, nhưng đang chọn
   đi qua domain để tiện theo dõi qua `/admin` và có SSL/Basic Auth sẵn).

**Cách gửi ảnh cho Gemini:** WebAI-to-API (thư viện `gemini-webapi` bên trong) cần 1 URL
ảnh **thật** để tự tải về rồi mới đưa cho Gemini xem - **không nhận được** base64 data URI
(đã thử, Gemini trả lời bừa không dựa trên ảnh thật khi gửi base64). Vì vậy `app.py` lưu
tạm ảnh upload vào `tmp_images/` và serve qua `http://host.docker.internal:8000/tmp-images/...`
(`host.docker.internal` là tên đặc biệt Docker Desktop dùng để container gọi ngược ra máy
host - **chỉ hoạt động khi WebAI-to-API cũng chạy trong Docker trên chính máy này**). Ảnh
tạm bị xoá ngay sau mỗi lần gọi Gemini xong. Nếu sau này chạy WebAI-to-API trên máy/VPS
khác (không phải Docker cùng máy), phải đổi hằng số `LOCAL_IMAGE_BASE_URL` trong `app.py`
thành domain/IP thật mà WebAI-to-API gọi tới được.

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
**Cập nhật quan trọng:** kể từ khi có model detection (YOLO12), service FastAPI đã gộp
chung 1 file `app.py` + `crop_configs.py` duy nhất tại gốc repo này (không còn project
riêng `fastaoi_plant` nữa, chỉ 1 process/1 deploy). Từng cây tự động ưu tiên dùng model
**detection** (YOLO12, có khoanh vùng) nếu đã có file `.pt` trong `ai_models/`, chưa có
thì tự động fallback về model **classification** cũ (`.pth` ở gốc repo) - không cần sửa
code khi thêm model mới, chỉ cần copy đúng file vào đúng chỗ rồi restart service.

**Lưu ý VPS chỉ có CPU (không có GPU như máy Windows lúc train):** `requirements.txt`
liệt kê `torch`/`torchvision` không ghi rõ CPU hay CUDA. Nếu cài thẳng
`pip install -r requirements.txt` trên Linux, mặc định PyPI sẽ tải nhầm bản CUDA
(kèm một loạt gói `nvidia-*` nặng vài GB, hoàn toàn vô dụng vì server không có GPU
để dùng). Vì vậy **PHẢI cài `torch`/`torchvision` bản CPU-only TRƯỚC**, để pip thấy
đã thoả yêu cầu và bỏ qua khi cài tiếp `requirements.txt`:
- `python3 -m venv venv`
- `source venv/bin/activate`
- `pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu` (bắt buộc làm TRƯỚC, chỉ áp dụng cho VPS - máy Windows local thì dùng bản `cu121` để chạy GPU cho nhanh)
- `pip install -r requirements.txt` (còn lại: fastapi, uvicorn[standard], python-multipart, pillow, ultralytics)
- Model chạy trên CPU vẫn dùng được bình thường (code tự nhận `torch.cuda.is_available()` là `False` rồi chuyển qua CPU, không cần sửa gì) - chỉ chậm hơn GPU, nhưng với tần suất 1 ảnh/lần bấm chẩn đoán thì vẫn ổn (khoảng 1-3 giây/ảnh tuỳ cấu hình VPS).
- **Copy các file model từ máy Windows lên** (không nằm trong git, xem `crop_configs.py` để biết đúng tên/đường dẫn từng cây):
  ```
  # Model classification cũ (.pth) -> đặt ở gốc repo
  scp best_tea_model.pth best_rice_model.pth best_corn_model.pth best_cassava_model.pth best_tomato_model.pth \
      girc-son@14.225.202.210:~/project/AI-nhan-dien-sau-benh/

  # Model detection mới (.pt, YOLO12) -> đặt trong thư mục ai_models/
  scp tea_yolo12.pt rice_yolo12.pt chili_yolo12.pt \
      girc-son@14.225.202.210:~/project/AI-nhan-dien-sau-benh/ai_models/
  ```
  Hiện trạng theo cây: **Chè, Lúa** có cả 2 (đang ưu tiên detection) · **Ớt** chỉ có detection ·
  **Ngô, Sắn, Cà chua** chỉ có classification cũ · **Xoài** chưa có model nào (vẫn dùng dữ liệu mẫu bên Laravel).

## 4. Chạy FastAPI nền bằng systemd
Cấu hình service KHÔNG đổi so với trước (vẫn `uvicorn app:app`, vẫn WorkingDirectory
là gốc repo Laravel) - vì phần detection/classification giờ gộp chung vào đúng `app.py`
này rồi, không phải trỏ sang thư mục project riêng nào khác nữa.

**Quan trọng nếu backend đang là `"gemini"` (xem mục 0):** `app.py`/`gemini_diagnosis.py`
đọc 2 hằng số này từ biến môi trường (có fallback về giá trị default trong code, xem
comment ngay tại chỗ khai báo), nên PHẢI set đè lại trong service systemd trên VPS:
- `LOCAL_IMAGE_BASE_URL` → domain public thật của VPS, vd `https://aiplant.girc.edu.vn`
  (không cần tunnel vì đã là domain công khai sẵn trên server).
- `WEBAI_BASE_URL` → **kể từ khi chuyển Docker WebAI-to-API lên chạy ngay trên VPS này**,
  giá trị đúng là `https://webai.girc.edu.vn/v1` (domain riêng, reverse-proxy qua
  OpenLiteSpeed vào container Docker cổng 6969 chỉ bind `127.0.0.1` - xem mục 0 và mục 5b).
  Không còn phụ thuộc máy Windows/Cloudflare Tunnel nữa như thiết lập cũ.

- Tạo `/etc/systemd/system/agriai.service`:
  ```
  [Unit]
  Description=AgriAI FastAPI Service - Multi-Crop Disease Detection (auto detection/classification)
  After=network.target

  [Service]
  Type=simple
  User=girc-son
  WorkingDirectory=/home/girc-son/project/AI-nhan-dien-sau-benh
  Environment="PATH=/home/girc-son/project/AI-nhan-dien-sau-benh/venv/bin"
  Environment="LOCAL_IMAGE_BASE_URL=https://aiplant.girc.edu.vn"
  Environment="WEBAI_BASE_URL=https://webai.girc.edu.vn/v1"
  ExecStart=/home/girc-son/project/AI-nhan-dien-sau-benh/venv/bin/uvicorn app:app --host 127.0.0.1 --port 8000
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```
- Nếu service đã tồn tại từ trước (đang set `WEBAI_BASE_URL` cũ trỏ `webai.girc-ai.com`),
  chỉ cần sửa đúng 1 dòng đó rồi áp dụng lại, không cần tạo file mới:
  ```
  sudo sed -i 's#WEBAI_BASE_URL=.*#WEBAI_BASE_URL=https://webai.girc.edu.vn/v1"#' /etc/systemd/system/agriai.service
  sudo systemctl daemon-reload
  sudo systemctl restart agriai
  sudo systemctl status agriai
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
- Tab **Context** → Add (2 rule, cả `/predict` VÀ `/tmp-images`; thiếu rule thứ 2 thì
  `LOCAL_IMAGE_BASE_URL=https://aiplant.girc.edu.vn` ở mục 4 sẽ không load được ảnh,
  vì domain đó chưa public hoá `/tmp-images` ra ngoài):
  - Type: Proxy, URI: `/predict`, Web Server: `agriai`
  - Type: Proxy, URI: `/tmp-images`, Web Server: `agriai`
- Tab **Rewrite** → thêm **lên đầu** rule (trước mọi rule khác), để tránh Laravel "nuốt" mất request:
  ```
  RewriteCond %{REQUEST_URI} ^/predict
  RewriteRule ^ - [L]
  RewriteCond %{REQUEST_URI} ^/tmp-images
  RewriteRule ^ - [L]
  ```
- Restart (graceful reload đôi khi KHÔNG đủ để nạp Context mới, cần restart cứng):
  ```
  sudo /usr/local/lsws/bin/lswsctrl stop
  sudo /usr/local/lsws/bin/lswsctrl start
  ```

## 5b. WebAI-to-API (Docker Gemini) ngay trên VPS này, domain riêng `webai.girc.edu.vn`
Container `web_ai_server` chạy tại `~/gemini2api` (docker-compose.yml + config.conf, copy
từ máy Windows sang), cổng 6969 chỉ bind `127.0.0.1:6969:6969` (không public trực tiếp):
- `cd ~/gemini2api && docker compose up -d`
- `curl http://localhost:6969/api/admin/status` -> phải thấy `"gemini_status":"connected"`

DNS: A record `webai.girc.edu.vn` -> IP VPS. Virtual Host riêng trong OpenLiteSpeed
(khác vhost với Laravel `aiplant.girc.edu.vn`):
- Virtual Host mới tên `webai`, Context type **Proxy**, URI `/`, Address `http://127.0.0.1:6969`
- Listener 443 hiện có → thêm Virtual Host Mapping cho domain `webai.girc.edu.vn`
- `/admin` được khoá bằng Basic Auth riêng (Security → Realm, tạo qua
  `/usr/local/lsws/admin/misc/htpasswd.sh`) vì dashboard này không có login sẵn, để hở ra
  sẽ lộ cookie đăng nhập Gemini cá nhân
- SSL: Let's Encrypt qua chính OpenLiteSpeed (VH → SSL → Issue Let's Encrypt Certificate)
- `sudo /usr/local/lsws/bin/lswsctrl restart` để áp dụng

## 6. Test nhanh sau khi cài lại
- `curl http://127.0.0.1:8000/` -> với backend Gemini phải thấy `"diagnosis_backend":"gemini"` và
  `"available_crops"` liệt kê đủ 7 cây (che, lua, ngo, san, ca_chua, xoai, ot). Với backend `"local"`
  thì `available_crops` là object liệt kê từng cây đang dùng loại model nào, vd
  `{"che":"detection","lua":"detection",...}` (Xoài không xuất hiện vì chưa có model nào).
- `curl -s --http1.1 -X POST https://aiplant.girc.edu.vn/predict -F "crop=che" -F "file=@duong_dan_anh.jpg"` -> phải ra JSON kết quả. Nếu lỗi 502 khi đang dùng backend Gemini -> kiểm tra container `web_ai_server` còn chạy trên VPS (`cd ~/gemini2api && docker compose ps`) và cookie còn hạn (`https://webai.girc.edu.vn/admin`).
- (curl trần không kèm file/-X POST rỗng sẽ báo "Invalid HTTP request received" - đây KHÔNG phải lỗi, chỉ do thiếu body, bỏ qua)

## Lưu ý quan trọng
- `.pth`, `.pt` (model), `tea_data/`, `rice_data*/`, `access_token`, `.env`, `config.conf` (cookie Gemini) KHÔNG nằm trong git (xem `.gitignore`) -> phải tự copy tay khi setup lại máy mới. **Không bao giờ commit `config.conf`** (chứa cookie đăng nhập cá nhân).
- Sau mỗi lần train model mới (dù là detection `.pt` hay classification `.pth`) -> nhớ `scp` đè lên đúng chỗ (`.pth` ở gốc repo, `.pt` vào `ai_models/`) + `sudo systemctl restart agriai` (model chỉ load 1 lần lúc service khởi động, không tự nhận model mới). Lưu ý: các model này **chỉ được dùng khi `DIAGNOSIS_BACKEND = "local"`**, hiện tại đang để `"gemini"` nên phần load model bị bỏ qua lúc khởi động (xem mục 0).
- Sau mỗi lần sửa `agri-index.blade.php`/`app.py`/`crop_configs.py`/`gemini_diagnosis.py` trên server qua patch script -> nhớ đồng bộ ngược lại vào code trên máy Windows + git, tránh lệch code giữa 2 nơi
- Project `fastaoi_plant` (nếu còn trên máy Windows) chỉ còn là bản nháp/tham khảo cũ - service thật đang chạy nằm hẳn trong `app.py` + `crop_configs.py` của repo này, không cần deploy `fastaoi_plant` riêng nữa.
- Docker WebAI-to-API + cookie Gemini giờ chạy ngay trên VPS này (mục 0 + 5b), không còn phụ thuộc máy Windows/Cloudflare Tunnel nữa. Rủi ro đổi lại: traffic gọi Gemini giờ xuất phát từ IP datacenter, dễ bị Google chặn/nghi ngờ hơn IP nhà mạng dân dụng - theo dõi log container nếu thấy chẩn đoán hay lỗi/treo lâu.

## 7. Cách thêm/nâng cấp 1 cây
Có 2 loại model, khai báo trong `crop_configs.py`, mỗi cây có thể có 1 hoặc cả 2:
- **`classification`** (EfficientNet-B0, `.pth`): phân loại cả ảnh, không khoanh vùng. Đã có sẵn cho **Chè, Lúa, Ngô, Sắn, Cà chua**.
- **`detection`** (YOLO12, `.pt`): có khoanh vùng bounding box, chính xác hơn, được ưu tiên dùng nếu có. Đã có sẵn cho **Chè, Lúa, Ớt**.

### 7a. Thêm model classification cho 1 cây chưa có gì (vd Xoài)
1. Tìm dataset Kaggle (tìm theo tên tiếng Anh của cây + "leaf disease"), chọn dataset theo tiêu chí: usability cao, nhiều notebook cộng đồng, không phải bản re-upload trùng file/size với dataset khác.
2. `kaggle datasets download -d <slug>` -> giải nén -> `find <thu_muc> -maxdepth 3` xem đúng cấu trúc lớp bệnh thật.
3. Đếm ảnh mỗi lớp, kiểm tra cân bằng.
4. **Chạy `check_duplicates.py` rồi `dedup_dataset.py` TRƯỚC KHI train** (đã học bài học từ Lúa: nếu bỏ qua bước này, accuracy 100% ảo do data leakage).
5. Copy `train_rice.py` (hoặc `train_mango.py`/`train_pepper.py` nếu đã có sẵn), đổi tên, sửa `SOURCE_DIR` trỏ đúng dataset đã dedup, sửa comment mô tả lớp bệnh.
6. Train xong, kiểm tra confusion matrix có hợp lý không (không còn 100% tuyệt đối là dấu hiệu tốt, không phải xấu).
7. `scp best_<ten_cay>_model.pth` lên VPS, đặt ở gốc repo (cạnh `app.py`).
8. Sửa `crop_configs.py` (cả trên Windows và server, nhớ đồng bộ):
   - Thêm 1 entry mới vào `CROP_CONFIGS`, dạng `"<key>": {"classification": {model_path, class_names, disease_info tiếng Việt cho từng lớp bệnh}}`
9. Sửa `public/js/agri-app.js` và `resources/views/agri-remote-capture.blade.php`:
   - Thêm cây mới vào `cropApiKey`/`CROP_API_KEY` mapping (vd `'Xoài':'xoai'`) - làm xong bước này là cây tự động chuyển từ dữ liệu mẫu sang gọi AI thật, không cần sửa gì thêm trong Blade.
10. `git add/commit/push` (Windows) -> `git pull` (server) -> `sudo systemctl restart agriai`
11. Test qua domain thật: `curl -s --http1.1 -X POST https://aiplant.girc.edu.vn/predict -F "crop=<key_moi>" -F "file=@anh.jpg"`

### 7b. Nâng cấp 1 cây đã có classification lên detection (vd Ngô, Sắn, Cà chua sau này)
Quy trình giống hệt cách đã làm với Chè/Lúa/Ớt trước đó:
1. Tìm dataset có bounding box (Roboflow, format YOLO), train bằng YOLO12 (xem `train_yolo_rice.py`/`train_yolo_chili.py` làm mẫu).
2. `scp best.pt` lên VPS, đặt vào `ai_models/<ten_cay>_yolo12.pt`.
3. Sửa `crop_configs.py`: thêm key `"detection": {model_path, class_names, disease_info}` vào ĐÚNG entry cây đó (giữ nguyên `"classification"` cũ làm dự phòng, không cần xoá).
4. `sudo systemctl restart agriai` - service sẽ tự nhận thấy có file detection và **tự động chuyển sang dùng nó**, không cần sửa gì ở `app.py` hay bên Laravel/JS.


Danh sách dataset đã chọn — 7 cây
#	Cây	Dataset	Link
1	🍃 Chè	Identifying Disease in Tea leaves — Shashwat Tiwari	https://www.kaggle.com/datasets/shashwatwork/identifying-disease-in-tea-leafs
2	🌾 Lúa	Rice Leaf Disease Images — Nirmal Sankalana	https://www.kaggle.com/datasets/nirmalsankalana/rice-leaf-disease-image
3	🌽 Ngô	Corn or Maize Leaf Disease Dataset — Smaranjit Ghose	https://www.kaggle.com/datasets/smaranjitghose/corn-or-maize-leaf-disease-dataset
4	🍠 Sắn	Cassava Leaf Disease Classification — Nirmal Sankalana	https://www.kaggle.com/datasets/nirmalsankalana/cassava-leaf-disease-classification
5	🍅 Cà chua	Tomato leaf disease detection — kaustubh b	https://www.kaggle.com/datasets/kaustubhb999/tomatoleaf
6	🥭 Xoài	Mango🥭 Leaf🍃🍂 Disease Dataset — Arya Shah	https://www.kaggle.com/datasets/aryashah2k/mango-leaf-disease-dataset
7	🌶️ Ớt	PlantVillage (trích riêng phần Pepper bell) — Mohit Singh	https://www.kaggle.com/datasets/mohitsingh1804/plantvillage