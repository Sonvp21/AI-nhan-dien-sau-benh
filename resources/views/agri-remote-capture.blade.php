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
</style>
</head>
<body>

  <div class="px-5 py-4 shrink-0" style="background:#1f6d3c">
    <p class="text-white font-bold text-[16px]">Chụp ảnh cây trồng</p>
    <p class="text-white/80 text-[12.5px] mt-0.5">Ảnh chụp xong sẽ tự động gửi tới màn hình trình chiếu</p>
  </div>

  <div class="flex-1 px-5 py-5 flex flex-col gap-4" style="background:#f2f7ee">

    <div id="emptyState" class="flex-1 flex flex-col items-center justify-center gap-3 text-center">
      <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background:#e2efd9;color:#1f6d3c">
        <i data-lucide="camera" class="w-7 h-7"></i>
      </div>
      <p class="text-[13.5px] max-w-xs" style="color:#4a5245">Chưa có ảnh nào. Bấm nút bên dưới để chụp ảnh lá hoặc cây cần chẩn đoán.</p>
    </div>

    <div id="previewGrid" class="grid grid-cols-3 gap-2 hidden"></div>

    <div id="successState" class="flex-1 hidden flex-col items-center justify-center gap-3 text-center">
      <div class="w-16 h-16 rounded-full flex items-center justify-center text-white" style="background:#1f6d3c">
        <i data-lucide="check" class="w-8 h-8"></i>
      </div>
      <p class="text-[15px] font-bold" style="color:#12341d">Đã gửi ảnh thành công!</p>
      <p class="text-[13px] max-w-xs" style="color:#6b7268">Vui lòng nhìn lên màn hình trình chiếu để xác nhận ảnh.</p>
      <button id="btnRetake" class="mt-2 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #1f6d3c;color:#1f6d3c">Chụp ảnh khác</button>
    </div>
  </div>

  <div id="actionBar" class="px-5 py-4 shrink-0 flex gap-3" style="border-top:1px solid #dbe8d2;background:#fff">
    <button id="btnCapture" class="flex-1 px-4 py-3 rounded-xl text-white font-semibold text-[14px] flex items-center justify-center gap-2" style="background:#1f6d3c">
      <i data-lucide="camera" class="w-4 h-4"></i> Chụp ảnh
    </button>
    <button id="btnSend" class="flex-1 px-4 py-3 rounded-xl text-white font-semibold text-[14px] hidden items-center justify-center gap-2" style="background:#c1440e">
      <i data-lucide="send" class="w-4 h-4"></i> Gửi ảnh (<span id="sendCount">0</span>)
    </button>
  </div>

  <input id="cameraInput" type="file" accept="image/*" capture="environment" class="hidden">

<script>
  if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
<script>
(function(){
  var TOKEN = @json($token);
  var UPLOAD_URL = @json(route('agri.remote-capture.upload', $token));
  var CSRF = document.querySelector('meta[name=csrf-token]').content;

  var cameraInput = document.getElementById('cameraInput');
  var emptyState = document.getElementById('emptyState');
  var previewGrid = document.getElementById('previewGrid');
  var successState = document.getElementById('successState');
  var actionBar = document.getElementById('actionBar');
  var btnCapture = document.getElementById('btnCapture');
  var btnSend = document.getElementById('btnSend');
  var btnRetake = document.getElementById('btnRetake');
  var sendCount = document.getElementById('sendCount');

  var photos = []; // { file, url }

  function renderGrid(){
    previewGrid.innerHTML = '';
    if (photos.length) {
      emptyState.classList.add('hidden');
      previewGrid.classList.remove('hidden');
      btnSend.classList.remove('hidden');
      btnSend.classList.add('flex');
    } else {
      emptyState.classList.remove('hidden');
      previewGrid.classList.add('hidden');
      btnSend.classList.add('hidden');
      btnSend.classList.remove('flex');
    }
    sendCount.textContent = photos.length;

    photos.forEach(function(p, i){
      var box = document.createElement('div');
      box.className = 'relative aspect-square rounded-lg overflow-hidden';
      var img = document.createElement('img');
      img.src = p.url;
      img.className = 'w-full h-full object-cover';
      var btn = document.createElement('button');
      btn.className = 'absolute top-1 right-1 w-6 h-6 rounded-full flex items-center justify-center text-white text-[13px]';
      btn.style.background = 'rgba(18,52,29,.7)';
      btn.setAttribute('data-i', i);
      btn.textContent = '×';
      btn.addEventListener('click', function(){
        var idx = parseInt(this.getAttribute('data-i'), 10);
        URL.revokeObjectURL(photos[idx].url);
        photos.splice(idx, 1);
        renderGrid();
      });
      box.appendChild(img);
      box.appendChild(btn);
      previewGrid.appendChild(box);
    });

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
  }

  btnCapture.addEventListener('click', function(){ cameraInput.click(); });

  cameraInput.addEventListener('change', function(e){
    var files = Array.prototype.slice.call(e.target.files || []);
    files.forEach(function(file){ photos.push({ file: file, url: URL.createObjectURL(file) }); });
    e.target.value = '';
    successState.classList.add('hidden');
    successState.classList.remove('flex');
    actionBar.classList.remove('hidden');
    renderGrid();
  });

  btnSend.addEventListener('click', function(){
    if (!photos.length) return;
    var originalHTML = btnSend.innerHTML;
    btnSend.disabled = true;
    btnSend.textContent = 'Đang gửi...';

    var fd = new FormData();
    fd.append('_token', CSRF);
    photos.forEach(function(p){ fd.append('photos[]', p.file); });

    fetch(UPLOAD_URL, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF } })
      .then(function(r){ if(!r.ok) throw new Error('upload failed'); return r.json(); })
      .then(function(){
        photos.forEach(function(p){ URL.revokeObjectURL(p.url); });
        photos = [];
        renderGrid();
        actionBar.classList.add('hidden');
        successState.classList.remove('hidden');
        successState.classList.add('flex');
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
      })
      .catch(function(){
        alert('Gửi ảnh thất bại, vui lòng thử lại.');
        btnSend.innerHTML = originalHTML;
      })
      .finally(function(){
        btnSend.disabled = false;
      });
  });

  btnRetake.addEventListener('click', function(){
    successState.classList.add('hidden');
    successState.classList.remove('flex');
    actionBar.classList.remove('hidden');
    renderGrid();
  });

  renderGrid();
})();
</script>
</body>
</html>
