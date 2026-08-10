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

    // ==== TÍCH HỢP AI THẬT ====
    AI_API_URL: 'https://aiplant.girc.edu.vn/predict',
    cropApiKey: {'Chè':'che', 'Lúa':'lua', 'Ngô':'ngo', 'Sắn':'san', 'Cà chua':'ca_chua'},
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

          this.liveResult = {
            disease: data.disease_name,
            nameEn: this.titleCase(data.disease_key),
            pathogen: data.pathogen || '',
            conditions: data.conditions || '',
            confidence: data.confidence + '%',
            level: data.level,
            steps: data.recommended_steps,
            isLive: true,
            lowConfidence: data.confidence < 50,
            top3: (data.top3 || []).map(t => ({
              disease: t.disease_name,
              nameEn: this.titleCase(t.disease_key),
              confidence: t.confidence + '%',
            })),
            symptoms: [],
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
      this.confirmedPhotos.forEach(p => URL.revokeObjectURL(p.url));
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

    crops:[
      {name:'Chè', icon:'leaf', img: ASSETS.crops.che},
      {name:'Lúa', icon:'wheat', img: ASSETS.crops.lua},
      {name:'Ngô', icon:'leafy-green', img: ASSETS.crops.ngo},
      {name:'Sắn', icon:'sprout', img: ASSETS.crops.san},
      {name:'Cà chua', icon:'cherry', img: ASSETS.crops.cachua},
    ],
    selectCrop(name){ this.selectedCrop = name; this.symptomPage = 0; this.diagnosed = false; this.diagnosing = false; this.liveResult = null; },
    get info(){ return this.liveResult || this.diseaseDB[this.selectedCrop]; },
    get symptomPages(){
      const arr = this.info.symptoms || [];
      if(!arr.length) return [];
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
    },
  }
}
