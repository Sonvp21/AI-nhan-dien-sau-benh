// Chuyển các thẻ <i data-lucide="..."> thành SVG icon. Nếu tên icon không tồn tại
// trong thư viện, tự động thay bằng icon "circle" để tránh icon bị trống/vỡ.
function refreshIcons(){
  if(typeof lucide === 'undefined') return;
  lucide.createIcons();
  // Sau khi chuyển đổi, các icon THÀNH CÔNG đã là thẻ <svg> (dù có thể vẫn còn
  // giữ attribute data-lucide). Chỉ những thẻ CHƯA chuyển đổi được (vẫn là <i>)
  // mới là icon không tồn tại trong thư viện, mới thay bằng "circle".
  document.querySelectorAll('[data-lucide]').forEach(function(el){
    if(el.tagName.toLowerCase() !== 'svg'){
      el.setAttribute('data-lucide', 'circle');
    }
  });
  lucide.createIcons();
}
// Chạy lại vài lần ngay sau khi trang tải xong, vì lần chạy đầu tiên (do Alpine
// x-effect gọi) có thể xảy ra trước khi các icon trong x-for/x-if kịp render ra DOM.
window.addEventListener('load', function(){
  refreshIcons();
  setTimeout(refreshIcons, 150);
  setTimeout(refreshIcons, 600);
});

function agriApp(){
  // window.AGRI_ASSETS được khai báo bằng Blade (asset()) ngay trong view, trước
  // khi file này được nạp. Có fallback rỗng để tránh lỗi nếu chưa khai báo kịp.
  const ASSETS = (typeof window !== 'undefined' && window.AGRI_ASSETS) ? window.AGRI_ASSETS : { crops:{} };

  return {
    selectedCrop:'Chè',
    symptomPage:0,

    // ==== chẩn đoán / animation kết quả ====
    diagnosing:false,
    diagnosed:false,
    runDiagnosis(){
      if(this.diagnosing) return;
      if(!this.confirmedPhotos.length){ this.openDropzoneModal(); return; }
      this.diagnosing = true;
      this.diagnosed = false;
      setTimeout(() => {
        this.diagnosing = false;
        this.diagnosed = true;
      }, 1400);
    },
    resetDiagnosis(){
      this.diagnosing = false;
      this.diagnosed = false;
      this.symptomPage = 0;
      this.confirmedPhotos.forEach(url => URL.revokeObjectURL(url));
      this.confirmedPhotos = [];
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
      this.confirmedPhotos.forEach(url => URL.revokeObjectURL(url));
      this.confirmedPhotos = this.pendingFiles.map(p => p.url);
      this.pendingFiles = [];
      this.modalStep = 'choose';
      this.dropzoneModalOpen = false;
      this.diagnosed = false;
      this.diagnosing = false;
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
              this.pendingFiles = data.photos.map(url => ({ file:null, url }));
              this.modalStep = 'preview';
            }
          })
          .catch(() => {});
      }, 1500);
    },
    stopQrPolling(){
      if(this.qrPollTimer){ clearInterval(this.qrPollTimer); this.qrPollTimer = null; }
    },

    crops:[
      {name:'Chè', icon:'leaf', img: ASSETS.crops.che},
      {name:'Lúa', icon:'wheat', img: ASSETS.crops.lua},
      {name:'Ngô', icon:'leafy-green', img: ASSETS.crops.ngo},
      {name:'Sắn', icon:'sprout', img: ASSETS.crops.san},
      {name:'Cà chua', icon:'cherry', img: ASSETS.crops.cachua},
    ],
    selectCrop(name){ this.selectedCrop = name; this.symptomPage = 0; this.diagnosed = false; this.diagnosing = false; },
    get info(){ return this.diseaseDB[this.selectedCrop]; },
    get symptomPages(){
      const arr = this.info.symptoms, pages = [];
      for(let i=0;i<arr.length;i+=3) pages.push(arr.slice(i,i+3));
      return pages;
    },
    nextSymptomPage(){ this.symptomPage = (this.symptomPage+1) % this.symptomPages.length; },
    prevSymptomPage(){ this.symptomPage = (this.symptomPage-1+this.symptomPages.length) % this.symptomPages.length; },
    diseaseDB:{
      'Lúa': {disease:'Đạo ôn lá', nameEn:'Rice Blast', level:'Nặng', confidence:'94.2%',
        steps:['Phun thuốc gốc Tricyclazole trong vòng 24 đến 48 giờ sau khi phát hiện bệnh.','Giảm bón đạm, tăng cường kali để hạn chế bệnh lây lan.','Theo dõi lại sau 5 ngày, chụp ảnh so sánh mức độ lan rộng.'],
        symptoms:[{emoji:'🌾',caption:'Vết bệnh trên lá lúa'},{emoji:'🌱',caption:'Vết bệnh trên thân, cổ bông'},{emoji:'🌿',caption:'Bệnh lan trên diện rộng'},
                  {emoji:'🔶',caption:'Đốm hình thoi, tâm xám trắng'},{emoji:'🌾',caption:'Bông lúa bị lép do nhiễm nấm'},{emoji:'🔥',caption:'Ruộng lúa cháy rụi từng chòm'}]},
      'Ngô': {disease:'Đốm lá lớn', nameEn:'Northern Corn Leaf Blight', level:'Trung bình', confidence:'90.4%',
        steps:['Phun thuốc gốc Azoxystrobin khi mới xuất hiện triệu chứng.','Luân canh cây trồng vụ sau để cắt nguồn bệnh.','Vệ sinh tàn dư cây bệnh sau thu hoạch.'],
        symptoms:[{emoji:'🌽',caption:'Đốm dài trên lá ngô'},{emoji:'🍂',caption:'Lá khô héo từ mép lá'},{emoji:'🌿',caption:'Bệnh lan xuống lá phía dưới'},
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
    },
  }
}
