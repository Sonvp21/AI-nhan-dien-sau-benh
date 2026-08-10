<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Chụp ảnh cây trồng</title>
<link rel="icon" href="{{ asset('image/logo.jpg') }}" type="image/jpeg">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  html,body{height:100%;}
  body{margin:0;font-family:'Be Vietnam Pro',system-ui,sans-serif;background:#fff;color:#1c231d;min-height:100vh;display:flex;flex-direction:column;}
  .no-scrollbar::-webkit-scrollbar{display:none;}
  .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
</style>
</head>
<body>

  <div class="px-5 py-4 shrink-0" style="background:#1f6d3c">
    <p class="text-white font-bold text-[16px]">Chụp ảnh cây trồng</p>
    <p class="text-white/80 text-[12.5px] mt-0.5">Chọn cây, chụp ảnh và bấm Chẩn đoán, kết quả sẽ tự hiện lên màn hình trình chiếu</p>
  </div>

  <div class="flex-1 px-5 py-5 flex flex-col gap-5" style="background:#f2f7ee">

    <!-- CHỌN MÔ HÌNH CÂY TRỒNG -->
    <div>
      <p class="text-[12.5px] font-semibold mb-2" style="color:#4a5245">Chọn mô hình cây trồng</p>
      <div id="cropRow" class="flex gap-2 overflow-x-auto no-scrollbar"></div>
    </div>

    <!-- KHU CHỤP ẢNH -->
    <div class="flex-1 flex flex-col">
      <div id="emptyState" class="flex-1 flex flex-col items-center justify-center gap-3 text-center rounded-xl" style="border:2px dashed #c7d6bd">
        <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background:#e2efd9;color:#1f6d3c">
          <i data-lucide="camera" class="w-6 h-6"></i>
        </div>
        <p class="text-[13px] max-w-xs px-4" style="color:#4a5245">Chưa có ảnh nào. Bấm nút bên dưới để chụp ảnh lá hoặc cây cần chẩn đoán.</p>
      </div>

      <div id="previewGrid" class="grid grid-cols-3 gap-2 hidden"></div>

      <div id="analyzingState" class="flex-1 hidden flex-col items-center justify-center gap-3 text-center">
        <i data-lucide="loader-circle" class="w-9 h-9 animate-spin" style="color:#1f6d3c"></i>
        <p class="text-[13.5px] font-semibold" style="color:#1f6d3c">Đang phân tích ảnh bằng AI...</p>
      </div>

      <div id="successState" class="flex-1 hidden flex-col items-center justify-center gap-3 text-center">
        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white" style="background:#1f6d3c">
          <i data-lucide="check" class="w-8 h-8"></i>
        </div>
        <p class="text-[15px] font-bold" style="color:#12341d">Đã gửi kết quả chẩn đoán!</p>
        <p id="successSub" class="text-[13px] max-w-xs px-4" style="color:#6b7268">Vui lòng nhìn lên màn hình trình chiếu để xem kết quả.</p>
        <button id="btnAgain" class="mt-2 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #1f6d3c;color:#1f6d3c">Chẩn đoán ảnh khác</button>
      </div>

      <div id="errorState" class="flex-1 hidden flex-col items-center justify-center gap-3 text-center">
        <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background:#fbf1ea;color:#c1440e">
          <i data-lucide="triangle-alert" class="w-7 h-7"></i>
        </div>
        <p id="errorMsg" class="text-[13.5px] max-w-xs px-4" style="color:#c1440e">Không kết nối được tới AI server, vui lòng thử lại.</p>
        <button id="btnRetry" class="mt-1 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold text-white" style="background:#1f6d3c">Thử lại</button>
      </div>
    </div>
  </div>

  <div id="actionBar" class="px-5 py-4 shrink-0 flex gap-3" style="border-top:1px solid #dbe8d2;background:#fff">
    <button id="btnCapture" class="flex-1 px-4 py-3 rounded-xl text-white font-semibold text-[14px] flex items-center justify-center gap-2" style="background:#1f6d3c">
      <i data-lucide="camera" class="w-4 h-4"></i> Chụp ảnh
    </button>
    <button id="btnRetake" class="flex-1 px-4 py-3 rounded-xl font-semibold text-[14px] hidden items-center justify-center gap-2" style="border:1px solid #c1440e;color:#c1440e">
      <i data-lucide="refresh-cw" class="w-4 h-4"></i> Thử ảnh khác
    </button>
    <button id="btnDiagnose" class="flex-1 px-4 py-3 rounded-xl text-white font-semibold text-[14px] hidden items-center justify-center gap-2" style="background:#c1440e">
      <i data-lucide="stethoscope" class="w-4 h-4"></i> Chẩn đoán bệnh
    </button>
  </div>

  <input id="cameraInput" type="file" accept="image/*" capture="environment" class="hidden">

<script>
(function(){
  if (typeof lucide !== 'undefined') { lucide.createIcons(); }

  var TOKEN = @json($token);
  var UPLOAD_URL = @json(route('agri.remote-capture.upload', $token));
  var CSRF = document.querySelector('meta[name=csrf-token]').content;

  // ==== cấu hình giống hệt agri-app.js bên màn hình trình chiếu ====
  var AI_API_URL = 'https://aiplant.girc.edu.vn/predict';
  var CROP_API_KEY = {'Chè':'che', 'Lúa':'lua', 'Ngô':'ngo', 'Sắn':'san', 'Cà chua':'ca_chua'};
  // Ớt, Xoài chưa có model AI thật (chưa có trong CROP_API_KEY) nên khi chọn 2
  // cây này, bấm Chẩn đoán sẽ không gọi AI mà chỉ gửi ảnh kèm mô hình lên,
  // màn hình trình chiếu sẽ tự hiển thị dữ liệu mẫu.
  var CROPS = [
    {name:'Chè', img: @json(asset('image/crop-che.png')), icon:'leaf'},
    {name:'Lúa', img: @json(asset('image/crop-lua.png')), icon:'wheat'},
    {name:'Ngô', img: @json(asset('image/crop-ngo.png')), icon:'leafy-green'},
    {name:'Sắn', img: @json(asset('image/crop-san.png')), icon:'sprout'},
    {name:'Cà chua', img: @json(asset('image/crop-cachua.png')), icon:'cherry'},
    {name:'Ớt', img: @json(asset('image/crop-ot.jpg')), icon:'flame'},
    {name:'Xoài', img: @json(asset('image/crop-xoai.jpg')), icon:'apple'},
  ];

  function titleCase(s){
    var clean = String(s || '').replace(/^(Cassava___|Tomato___)/, '').replace(/_/g, ' ');
    return clean.replace(/\w\S*/g, function(t){ return t.charAt(0).toUpperCase() + t.slice(1).toLowerCase(); });
  }

  var selectedCrop = CROPS[0].name;
  var photos = []; // { file, url }
  var busy = false;

  // ---- render danh sách mô hình cây trồng ----
  var cropRow = document.getElementById('cropRow');
  function renderCrops(){
    cropRow.innerHTML = '';
    CROPS.forEach(function(c){
      var btn = document.createElement('button');
      var active = c.name === selectedCrop;
      btn.type = 'button';
      btn.className = 'rounded-lg overflow-hidden transition shrink-0';
      btn.style.width = '68px';
      btn.style.border = active ? '2px solid #1f6d3c' : '2px solid transparent';
      btn.style.opacity = active ? '1' : '.6';
      var imgZone = c.img
        ? '<img src="'+c.img+'" alt="'+c.name+'" class="w-full h-full object-cover">'
        : '<i data-lucide="'+c.icon+'" class="w-5 h-5" style="color:#1f6d3c"></i>';
      btn.innerHTML =
        '<div class="h-9 overflow-hidden flex items-center justify-center" style="background:#e2efd9">'+imgZone+'</div>' +
        '<div class="py-1" style="background:'+(active ? '#1f6d3c' : '#eceae3')+'">' +
          '<span class="text-[10.5px] font-semibold text-center block" style="color:'+(active ? '#fff' : '#3c433d')+'">'+c.name+'</span>' +
        '</div>';
      btn.addEventListener('click', function(){
        if(busy) return;
        selectedCrop = c.name;
        renderCrops();
      });
      cropRow.appendChild(btn);
    });
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
  }
  renderCrops();

  var cameraInput = document.getElementById('cameraInput');
  var emptyState = document.getElementById('emptyState');
  var previewGrid = document.getElementById('previewGrid');
  var analyzingState = document.getElementById('analyzingState');
  var successState = document.getElementById('successState');
  var successSub = document.getElementById('successSub');
  var errorState = document.getElementById('errorState');
  var errorMsg = document.getElementById('errorMsg');
  var actionBar = document.getElementById('actionBar');
  var btnCapture = document.getElementById('btnCapture');
  var btnRetake = document.getElementById('btnRetake');
  var btnDiagnose = document.getElementById('btnDiagnose');
  var btnAgain = document.getElementById('btnAgain');
  var btnRetry = document.getElementById('btnRetry');

  function showOnly(el){
    [emptyState, previewGrid, analyzingState, successState, errorState].forEach(function(node){
      var isTarget = node === el;
      node.classList.toggle('hidden', !isTarget);
      if(node === previewGrid){
        node.classList.toggle('grid', isTarget);
      } else {
        node.classList.toggle('flex', isTarget);
      }
    });
  }

  function renderGrid(){
    previewGrid.innerHTML = '';
    photos.forEach(function(p, i){
      var box = document.createElement('div');
      box.className = 'relative aspect-square rounded-lg overflow-hidden';
      var img = document.createElement('img');
      img.src = p.url;
      img.className = 'w-full h-full object-cover';
      var btn = document.createElement('button');
      btn.className = 'absolute top-1 right-1 w-6 h-6 rounded-full flex items-center justify-center text-white text-[13px]';
      btn.style.background = 'rgba(18,52,29,.7)';
      btn.textContent = '×';
      btn.addEventListener('click', function(){
        URL.revokeObjectURL(photos[i].url);
        photos.splice(i, 1);
        syncButtons();
      });
      box.appendChild(img);
      box.appendChild(btn);
      previewGrid.appendChild(box);
    });
  }

  function syncButtons(){
    if(photos.length){
      renderGrid();
      showOnly(previewGrid);
      btnCapture.classList.add('hidden');
      btnRetake.classList.remove('hidden'); btnRetake.classList.add('flex');
      btnDiagnose.classList.remove('hidden'); btnDiagnose.classList.add('flex');
    } else {
      showOnly(emptyState);
      btnCapture.classList.remove('hidden');
      btnRetake.classList.add('hidden'); btnRetake.classList.remove('flex');
      btnDiagnose.classList.add('hidden'); btnDiagnose.classList.remove('flex');
    }
    actionBar.classList.remove('hidden');
  }

  btnCapture.addEventListener('click', function(){ if(!busy) cameraInput.click(); });

  cameraInput.addEventListener('change', function(e){
    var files = Array.prototype.slice.call(e.target.files || []);
    files.forEach(function(file){ photos.push({ file: file, url: URL.createObjectURL(file) }); });
    e.target.value = '';
    syncButtons();
  });

  btnRetake.addEventListener('click', function(){
    if(busy) return;
    photos.forEach(function(p){ URL.revokeObjectURL(p.url); });
    photos = [];
    syncButtons();
  });

  function setBusy(isBusy){
    busy = isBusy;
    actionBar.classList.toggle('hidden', isBusy);
  }

  btnDiagnose.addEventListener('click', function(){
    if(busy || !photos.length) return;
    setBusy(true);
    showOnly(analyzingState);
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }

    var apiKey = CROP_API_KEY[selectedCrop];
    var photoFile = photos[0].file;

    var diagnosePromise = (apiKey && photoFile)
      ? diagnoseWithAI(photoFile, apiKey)
      : Promise.resolve(null);

    diagnosePromise
      .then(function(result){ return sendToScreen(result); })
      .then(function(){
        photos.forEach(function(p){ URL.revokeObjectURL(p.url); });
        photos = [];
        successSub.textContent = 'Vui lòng nhìn lên màn hình trình chiếu để xem kết quả.';
        showOnly(successState);
        actionBar.classList.add('hidden');
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
      })
      .catch(function(err){
        console.error(err);
        errorMsg.textContent = 'Không kết nối được tới AI server, vui lòng thử lại.';
        showOnly(errorState);
        actionBar.classList.add('hidden');
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
      })
      .finally(function(){ setBusy(false); });
  });

  function diagnoseWithAI(photoFile, apiKey){
    var formData = new FormData();
    formData.append('file', photoFile);
    formData.append('crop', apiKey);
    return fetch(AI_API_URL, { method:'POST', body: formData })
      .then(function(res){
        if(!res.ok) throw new Error('API lỗi: ' + res.status);
        return res.json();
      })
      .then(function(data){
        return {
          disease: data.disease_name,
          nameEn: titleCase(data.disease_key),
          pathogen: data.pathogen || '',
          conditions: data.conditions || '',
          confidence: data.confidence + '%',
          level: data.level,
          steps: data.recommended_steps,
          isLive: true,
          lowConfidence: data.confidence < 50,
          top3: (data.top3 || []).map(function(t){
            return { disease: t.disease_name, nameEn: titleCase(t.disease_key), confidence: t.confidence + '%' };
          }),
          symptoms: [],
        };
      });
  }

  function sendToScreen(result){
    var fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('crop', selectedCrop);
    if(result) fd.append('result', JSON.stringify(result));
    photos.forEach(function(p){ fd.append('photos[]', p.file); });

    return fetch(UPLOAD_URL, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF } })
      .then(function(r){ if(!r.ok) throw new Error('upload failed'); return r.json(); });
  }

  btnAgain.addEventListener('click', function(){ syncButtons(); });
  btnRetry.addEventListener('click', function(){ syncButtons(); });

  syncButtons();
})();
</script>
</body>
</html>
