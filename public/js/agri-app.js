// Chuyển các thẻ <i data-lucide="..."> thành SVG icon. Nếu tên icon không tồn tại
// trong thư viện, tự động thay bằng icon "circle" để tránh icon bị trống/vỡ.
function refreshIcons(){
  if(typeof lucide === 'undefined') return;
  lucide.createIcons();
  document.querySelectorAll('[data-lucide]').forEach(function(el){
    if(el.tagName.toLowerCase() !== 'svg'){
      el.setAttribute('data-lucide', 'circle');
    }
  });
  lucide.createIcons();
}
window.addEventListener('load', function(){
  refreshIcons();
  setTimeout(refreshIcons, 150);
  setTimeout(refreshIcons, 600);
});

function agriApp(){
  const ASSETS = (typeof window !== 'undefined' && window.AGRI_ASSETS) ? window.AGRI_ASSETS : { crops:{} };

  return {
    selectedCrop:'Chè',
    symptomPage:0,

    // Modal xem chi tiet 1 benh KHAC ngoai benh chinh (xem uniqueDiseaseNames()
    // va openOtherDisease() ben duoi + disease-detail-modal.blade.php)
    otherDiseaseModalOpen:false,
    selectedOtherDisease:null,

    // ==== TÍCH HỢP AI THẬT ====
    // Service FastAPI gio nam CHUNG trong repo Laravel nay (app.py o thu muc goc),
    // khong con tach rieng project fastaoi_plant nua - chi con 1 process/1 deploy.
    // Backend chan doan HIEN TAI la Gemini (qua WebAI-to-API chay local, xem
    // gemini_diagnosis.py + SETUP_NOTES.md) - dung chung cho CA 7 CAY, Gemini tu
    // nhan dien ca dung/sai cay (xem crop_mismatch ben duoi). Code model YOLO/
    // EfficientNet tu train van con nguyen trong app.py, chi tam thoi khong dung.
    // URL that qua reverse proxy cua chinh domain Laravel (xem SETUP_NOTES.md muc 5,
    // OpenLiteSpeed Context proxy /predict -> agriai:8000) - KHONG duoc de localhost
    // o day, vi day la code chay trong TRINH DUYET cua nguoi dung cuoi, tro ve
    // 127.0.0.1 se goi vao chinh may cua ho chu khong phai server.
    AI_API_URL: 'https://aiplant.girc.edu.vn/predict',
    // AI_API_URL: 'http://127.0.0.1:8000/predict',
    // Ca 7 cay deu goi API that (Gemini tu nhan dien duoc moi loai cay, khong can
    // model rieng cho tung cay nua). Neu API loi/khong ket noi duoc, catch() ben
    // duoi se bao loi thay vi lang le rot ve du lieu mau.
    cropApiKey: {'Chè':'che', 'Lúa':'lua', 'Ngô':'ngo', 'Sắn':'san', 'Cà chua':'ca_chua', 'Xoài':'xoai', 'Ớt':'ot'},
    liveResult: null,
    titleCase(s){
      let clean = s.replace(/^(Cassava___|Tomato___)/, '').replace(/_/g, ' ');
      return clean.replace(/\w\S*/g, t => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
    },

    // ==== chẩn đoán / animation kết quả ====
    diagnosing:false,
    diagnosed:false,
    async runDiagnosis(){
      if(this.diagnosing) return;
      if(!this.confirmedPhotos.length){ this.openDropzoneModal(); return; }
      this.diagnosing = true;
      this.diagnosed = false;
      this.liveResult = null;

      const apiKey = this.cropApiKey[this.selectedCrop];
      const photoFile = this.confirmedPhotos[0] && this.confirmedPhotos[0].file;

      if(apiKey && photoFile){
        try{
          const formData = new FormData();
          formData.append('file', photoFile);
          formData.append('crop', apiKey);
          const res = await fetch(this.AI_API_URL, { method:'POST', body: formData });
          if(!res.ok) throw new Error('API lỗi: ' + res.status);
          const data = await res.json();

          // Gemini phat hien anh KHONG khop cay da chon -> chan lai, khong hien
          // chan doan benh (tranh chan doan sai tren anh cay khac).
          if(data.crop_mismatch){
            this.liveResult = {
              disease:'', nameEn:'', pathogen:'', level:'',
              probability: null, diseaseProbability: null,
              signsInPhoto:'', symptomsText:'', treatment:'', prevention:'',
              isLive: true, found: false,
              cropMismatch: true, detectedCrop: data.detected_crop || '',
              detections: [], symptoms: [], referenceImages: [],
            };
            this.diagnosing = false;
            this.diagnosed = true;
            return;
          }

          const summary = data.summary || {};
          // "found" (co benh thuc su, khac "Cay khoe manh") gio doc thang tu API,
          // khong tu doan qua disease_key nua (Gemini luon tra ve 1 entry ke ca
          // khi cay khoe, nen disease_key khong con null trong truong hop do).
          const found = !!data.found;

          this.liveResult = {
            disease: summary.disease_name || 'Không xác định',
            nameEn: found ? this.titleCase(summary.disease_key) : '',
            pathogen: summary.pathogen || '',
            level: summary.level || 'Trung bình',
            // % Gemini tu uoc luong (tham khao, khong phai so lieu ML) - null neu
            // Gemini khong tra hoac cay khoe manh khong co gia tri ro rang.
            probability: summary.probability ?? null,
            diseaseProbability: data.disease_probability ?? null,
            // 4 truong chi tiet tach rieng tu prompt Gemini (xem gemini_diagnosis.py):
            // dau hieu QUAN SAT TRONG CHINH ANH NAY (signsInPhoto), dau hieu nhan
            // biet CHUNG cua benh (symptomsText), cach chua tri, cach phong ngua -
            // moi truong la 1 doan van, hien thay cho danh sach "steps" gop chung cu.
            signsInPhoto: summary.signs_in_photo || '',
            symptomsText: summary.symptoms || '',
            treatment: summary.treatment || '',
            prevention: summary.prevention || '',
            isLive: true,
            found: found,
            cropMismatch: false,
            detectedCrop: '',
            // Danh sach TEN BENH + % phat hien duoc, KHONG trung lap, sap theo %
            // giam dan (API da sap xep san). Detection co the ra nhieu vung benh
            // khac nhau tren cung 1 anh.
            detections: this.uniqueDiseaseNames(data.detections),
            symptoms: [],
            // THU NGHIEM: anh Gemini TU TIM tren web (khong phai anh tu ve), xem
            // symptomPages getter + gemini_diagnosis.py. Moi phan tu dang
            // {url, title} - co the la [] neu ban dang chay chua ho tro.
            referenceImages: data.reference_images || [],
          };
          this.diagnosing = false;
          this.diagnosed = true;
        }catch(err){
          this.diagnosing = false;
          console.error(err);
          alert('Không kết nối được tới AI server, vui lòng thử lại sau.');
        }
        return;
      }

      // Cây chưa có model thật -> dùng animation demo như cũ
      setTimeout(() => {
        this.diagnosing = false;
        this.diagnosed = true;
      }, 1400);
    },
    resetDiagnosis(){
      this.diagnosing = false;
      this.diagnosed = false;
      this.symptomPage = 0;
      this.liveResult = null;
      this.otherDiseaseModalOpen = false;
      this.selectedOtherDisease = null;
      this.confirmedPhotos.forEach(p => URL.revokeObjectURL(p.url));
      this.confirmedPhotos = [];
    },

    // Gop danh sach detections tra ve tu API thanh danh sach KHONG trung lap,
    // giu THAM SO ĐẦY ĐỦ tung benh (khong chi ten + %) de: (1) benh dau tien (%
    // cao nhat) lam ket luan chinh hien ngay ngoai panel, (2) cac benh con lai
    // hien thanh list bam vao mo modal xem chi tiet rieng (pathogen/symptoms/
    // treatment/prevention) - xem disease-detail-modal.blade.php. Giu thu tu API
    // da sap xep theo % giam dan.
    uniqueDiseaseNames(detections){
      const seen = new Set();
      const result = [];
      (detections || []).forEach(d => {
        if(!seen.has(d.disease_key)){
          seen.add(d.disease_key);
          result.push({
            disease: d.disease_name,
            nameEn: this.titleCase(d.disease_key),
            probability: d.probability ?? null,
            level: d.level || '',
            pathogen: d.pathogen || '',
            signsInPhoto: d.signs_in_photo || '',
            symptomsText: d.symptoms || '',
            treatment: d.treatment || '',
            prevention: d.prevention || '',
          });
        }
      });
      return result;
    },

    // Mo modal xem chi tiet 1 benh KHAC (khong phai benh chinh dang hien ngoai
    // panel) - d la 1 phan tu trong info.detections (da co day du field o tren).
    openOtherDisease(d){
      this.selectedOtherDisease = d;
      this.otherDiseaseModalOpen = true;
    },

    // ==== dropzone / modal chụp ảnh, chọn ảnh ====
    dropzoneModalOpen:false,
    modalStep:'choose',
    pendingFiles:[],
    confirmedPhotos:[],
    openDropzoneModal(){
      this.dropzoneModalOpen = true;
      this.modalStep = this.pendingFiles.length ? 'preview' : 'choose';
    },
    closeModal(){
      if(this.pendingFiles.length) this.cancelPhotos();
      this.stopQrPolling();
      this.dropzoneModalOpen = false;
    },
    handleFileChosen(e){
      const files = Array.from(e.target.files || []);
      files.forEach(file => {
        this.pendingFiles.push({ file, url: URL.createObjectURL(file) });
      });
      if(files.length) this.modalStep = 'preview';
      e.target.value = '';
    },
    removePendingPhoto(i){
      URL.revokeObjectURL(this.pendingFiles[i].url);
      this.pendingFiles.splice(i, 1);
      if(!this.pendingFiles.length) this.modalStep = 'choose';
    },
    confirmPhotos(){
      this.confirmedPhotos.forEach(p => URL.revokeObjectURL(p.url));
      this.confirmedPhotos = this.pendingFiles.map(p => ({ file: p.file, url: p.url }));
      this.pendingFiles = [];
      this.modalStep = 'choose';
      this.dropzoneModalOpen = false;
      this.diagnosed = false;
      this.diagnosing = false;
      this.liveResult = null;
    },
    cancelPhotos(){
      this.pendingFiles.forEach(p => URL.revokeObjectURL(p.url));
      this.pendingFiles = [];
      this.modalStep = 'choose';
    },

    // ==== chế độ thuyết trình: chụp ảnh từ xa bằng điện thoại qua mã QR ====
    // Bấm liên tiếp 5 lần vào logo (trong 1.5s) để bật/tắt, không hiện chữ để người dùng thường không biết.
    presenterMode: (typeof localStorage !== 'undefined' && localStorage.getItem('agri_presenter_mode') === '1'),
    logoClickCount:0,
    logoClickTimer:null,
    handleLogoClick(){
      clearTimeout(this.logoClickTimer);
      this.logoClickCount++;
      if(this.logoClickCount >= 5){
        this.togglePresenterMode();
        this.logoClickCount = 0;
      } else {
        this.logoClickTimer = setTimeout(() => { this.logoClickCount = 0; }, 1500);
      }
    },
    togglePresenterMode(){
      this.presenterMode = !this.presenterMode;
      try { localStorage.setItem('agri_presenter_mode', this.presenterMode ? '1' : '0'); } catch(e){}
    },
    qrToken:null,
    qrPollTimer:null,
    startQrCapture(){
      this.qrToken = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : (Date.now() + '-' + Math.random().toString(36).slice(2));
      this.modalStep = 'qr';
      this.$nextTick(() => this.renderQr());
      this.pollQrStatus();
    },
    renderQr(){
      const el = document.getElementById('qrCaptureBox');
      if(!el || typeof QRCode === 'undefined') return;
      el.innerHTML = '';
      const url = window.location.origin + '/quet-anh/' + this.qrToken;
      new QRCode(el, { text: url, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M });
    },
    pollQrStatus(){
      this.stopQrPolling();
      this.qrPollTimer = setInterval(() => {
        if(this.modalStep !== 'qr' || !this.qrToken){ this.stopQrPolling(); return; }
        fetch('/quet-anh/' + this.qrToken + '/status')
          .then(r => r.json())
          .then(data => {
            if(data && data.ready && data.photos && data.photos.length){
              this.stopQrPolling();
              // Điện thoại đã tự chọn mô hình, chụp ảnh và bấm chẩn đoán rồi,
              // nên web chỉ nhận ảnh + kết quả và hiển thị luôn, không cần
              // mở modal xác nhận ảnh nữa.
              this.confirmedPhotos.forEach(p => URL.revokeObjectURL(p.url));
              this.confirmedPhotos = data.photos.map(url => ({ file:null, url }));
              this.pendingFiles = [];
              if(data.crop) this.selectedCrop = data.crop;
              this.dropzoneModalOpen = false;
              this.modalStep = 'choose';
              this.symptomPage = 0;
              if(data.result){
                this.liveResult = data.result;
                this.diagnosing = false;
                this.diagnosed = true;
              } else {
                this.liveResult = null;
                this.diagnosing = false;
                this.diagnosed = false;
              }
            }
          })
          .catch(() => {});
      }, 1500);
    },
    stopQrPolling(){
      if(this.qrPollTimer){ clearInterval(this.qrPollTimer); this.qrPollTimer = null; }
    },

    // ==== cuộn ngang danh sách mô hình cây trồng (khu chọn mô hình) ====
    scrollCrops(direction){
      const el = this.$refs.cropScroll;
      if(!el) return;
      el.scrollBy({ left: direction * 220, behavior: 'smooth' });
    },

    crops:[
      {name:'Chè', icon:'leaf', img: ASSETS.crops.che},
      {name:'Lúa', icon:'wheat', img: ASSETS.crops.lua},
      {name:'Ngô', icon:'leafy-green', img: ASSETS.crops.ngo},
      {name:'Sắn', icon:'sprout', img: ASSETS.crops.san},
      {name:'Cà chua', icon:'cherry', img: ASSETS.crops.cachua},
      {name:'Ớt', icon:'flame', img: ASSETS.crops.ot || null},
      {name:'Xoài', icon:'apple', img: ASSETS.crops.xoai || null},
    ],
    selectCrop(name){ this.selectedCrop = name; this.symptomPage = 0; this.diagnosed = false; this.diagnosing = false; this.liveResult = null; this.otherDiseaseModalOpen = false; this.selectedOtherDisease = null; },
    get info(){ return this.liveResult || this.diseaseDB[this.selectedCrop]; },
    get symptomPages(){
      // Du lieu mau (demo): dung sac "symptoms" tinh (emoji + caption, khong co
      // anh thuc). AI thuc (Gemini): dung "referenceImages" - anh Gemini THUC SU
      // tim duoc tren web luc no tu tra cuu (THU NGHIEM, co the rong neu ban
      // WebAI-to-API dang chay chua ho tro - xem gemini_diagnosis.py). Card se tu
      // hien icon la thay the neu tung anh khong co url (xem photo-panel.blade.php).
      const raw = this.info.isLive ? (this.info.referenceImages || []) : (this.info.symptoms || []);
      if(!raw.length) return [];
      // Chuan hoa ve 1 format duy nhat {url, caption} de photo-panel.blade.php
      // dung chung 1 template cho ca demo (khong co url, chi co icon la + caption)
      // va anh Gemini tim duoc thuc (co url thi hien <img>, khong thi fallback icon).
      const arr = raw.map(item => ({ url: item.url || null, caption: item.caption || item.title || '' }));
      const pages = [];
      for(let i=0;i<arr.length;i+=3) pages.push(arr.slice(i,i+3));
      return pages;
    },
    nextSymptomPage(){ if(!this.symptomPages.length) return; this.symptomPage = (this.symptomPage+1) % this.symptomPages.length; },
    prevSymptomPage(){ if(!this.symptomPages.length) return; this.symptomPage = (this.symptomPage-1+this.symptomPages.length) % this.symptomPages.length; },
    diseaseDB:{
      'Lúa': {disease:'Đạo ôn lá', nameEn:'Rice Blast', level:'Nặng', confidence:'94.2%',
        steps:['Phun thuốc gốc Tricyclazole trong vòng 24 đến 48 giờ sau khi phát hiện bệnh.','Giảm bón đạm, tăng cường kali để hạn chế bệnh lây lan.','Theo dõi lại sau 5 ngày, chụp ảnh so sánh mức độ lan rộng.'],
        symptoms:[{emoji:'🌾',caption:'Vết bệnh trên lá lúa'},{emoji:'🌱',caption:'Vết bệnh trên thân, cổ bông'},{emoji:'🌿',caption:'Bệnh lan trên diện rộng'},
                  {emoji:'🔶',caption:'Đốm hình thoi, tâm xám trắng'},{emoji:'🌾',caption:'Bông lúa bị lép do nhiễm nấm'},{emoji:'🔥',caption:'Ruộng lúa cháy rụi từng chòm'}]},
      'Ngô': {disease:'Đốm lá lớn', nameEn:'Northern Corn Leaf Blight', level:'Trung bình', confidence:'90.4%',
        steps:['Phun thuốc gốc Azoxystrobin khi mới xuất hiện triệu chứng.','Luân canh cây trồng vụ sau để cắt nguồn bệnh.','Vệ sinh tàn dư cây bệnh sau thu hoạch.'],
        symptoms:[{emoji:'🌽',caption:'Đốm dài trên lá ngô'},{emoji:'🍂',caption:'Lá khô héo từ mép lá'},{emoji:'��',caption:'Bệnh lan xuống lá phía dưới'},
                  {emoji:'📏',caption:'Vệt bệnh song song gân lá'},{emoji:'🌽',caption:'Bắp ngô nhỏ do cây suy yếu'},{emoji:'🔥',caption:'Ruộng ngô cháy lá hàng loạt'}]},
      'Sắn': {disease:'Khảm lá sắn', nameEn:'Cassava Mosaic Disease', level:'Nặng', confidence:'96.1%',
        steps:['Nhổ bỏ, tiêu hủy cây bị bệnh nặng để tránh lây lan.','Dùng giống sắn kháng bệnh cho vụ sau.','Kiểm soát bọ phấn trắng, trung gian truyền bệnh.'],
        symptoms:[{emoji:'🍃',caption:'Lá khảm vàng loang lổ'},{emoji:'🥔',caption:'Lá biến dạng, nhăn nheo'},{emoji:'🌿',caption:'Cây còi cọc, chậm phát triển'},
                  {emoji:'🌀',caption:'Lá non xoăn lại'},{emoji:'📉',caption:'Năng suất củ giảm mạnh'},{emoji:'🌱',caption:'Cây lùn, phân cành kém'}]},
      'Cà chua': {disease:'Đốm nâu lá', nameEn:'Septoria Leaf Spot', level:'Trung bình', confidence:'89.7%',
        steps:['Cắt tỉa lá bệnh, tránh tưới nước lên tán lá.','Phun thuốc gốc đồng (Copper-based fungicide).','Tăng khoảng cách trồng để cây thông thoáng.'],
        symptoms:[{emoji:'🍅',caption:'Đốm nâu viền vàng trên lá'},{emoji:'🍂',caption:'Lá vàng, rụng sớm'},{emoji:'🌿',caption:'Bệnh lan lên thân non'},
                  {emoji:'🔴',caption:'Quả có đốm nâu lõm'},{emoji:'🍃',caption:'Cây rụng lá hàng loạt'},{emoji:'📉',caption:'Năng suất quả giảm rõ'}]},
      'Chè': {disease:'Cây khỏe mạnh', nameEn:'Healthy', level:'Nhẹ', confidence:'97.8%',
        steps:['Duy trì chế độ chăm sóc hiện tại.','Kiểm tra định kỳ 2 tuần một lần vào mùa mưa.','Bón phân cân đối NPK theo giai đoạn sinh trưởng.'],
        symptoms:[{emoji:'🍃',caption:'Lá xanh, không có đốm bệnh'},{emoji:'🌱',caption:'Búp phát triển bình thường'},{emoji:'🌿',caption:'Tán cây đều, khỏe mạnh'},
                  {emoji:'🌳',caption:'Rễ phát triển tốt'},{emoji:'📈',caption:'Năng suất búp ổn định'},{emoji:'🍃',caption:'Màu lá xanh đậm tự nhiên'}]},
      // Xoài: chưa có model AI thật (chưa có trong cropApiKey) nên dùng dữ liệu mẫu.
      // Ớt vẫn giữ 1 bản mẫu ở đây làm dự phòng, nhưng bình thường sẽ không dùng tới
      // vì Ớt đã có trong cropApiKey (sẽ gọi API thật).
      'Ớt': {disease:'Thán thư quả ớt', nameEn:'Chili Anthracnose', level:'Nặng', confidence:'92.5%',
        steps:['Cắt bỏ, tiêu hủy quả bệnh để tránh lây lan.','Phun thuốc gốc Mancozeb hoặc Chlorothalonil.','Tránh tưới nước lên tán lá, giữ vườn thông thoáng.'],
        symptoms:[{emoji:'🌶️',caption:'Đốm tròn lõm trên quả'},{emoji:'🍂',caption:'Viền đốm màu nâu sẫm'},{emoji:'🌿',caption:'Quả thối nhũn, rụng sớm'},
                  {emoji:'🔴',caption:'Tâm đốm có vòng đồng tâm'},{emoji:'🍃',caption:'Lá vàng, rụng dần'},{emoji:'📉',caption:'Năng suất quả giảm mạnh'}]},
      'Xoài': {disease:'Thán thư lá và quả', nameEn:'Mango Anthracnose', level:'Trung bình', confidence:'88.9%',
        steps:['Tỉa cành tạo tán thông thoáng, giảm ẩm độ trong tán.','Phun thuốc gốc đồng (Copper-based fungicide) định kỳ.','Thu gom, tiêu hủy lá và quả rụng bị bệnh.'],
        symptoms:[{emoji:'🥭',caption:'Đốm đen trên vỏ quả'},{emoji:'🍂',caption:'Lá non có đốm nâu cháy'},{emoji:'🌿',caption:'Hoa bị khô đen, rụng'},
                  {emoji:'🔴',caption:'Đốm lan rộng khi quả chín'},{emoji:'🍃',caption:'Chồi non bị thối đen'},{emoji:'📉',caption:'Quả rụng non hàng loạt'}]},
    },
  }
}
