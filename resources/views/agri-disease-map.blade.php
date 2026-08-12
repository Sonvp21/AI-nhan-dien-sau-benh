<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Bản đồ dịch bệnh</title>
<link rel="icon" href="{{ asset('image/logo.jpg') }}" type="image/jpeg">
<link rel="apple-touch-icon" href="{{ asset('image/logo.jpg') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{margin:0;font-family:'Be Vietnam Pro',system-ui,sans-serif;background:#fff;color:#1c231d;min-height:100vh;display:flex;flex-direction:column;}
  a{color:#1f6d3c;text-decoration:none;}
  .no-scrollbar::-webkit-scrollbar{display:none;}
  .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
  [x-cloak]{display:none!important;}
</style>
</head>
{{-- Layout giống trang chẩn đoán: topbar+drawer riêng cho mobile, banner +
     thanh tab điều hướng cho desktop (xem agri-index.blade.php). Trang này
     không cần agriApp() đầy đủ, chỉ cần mobileDrawerOpen cho drawer -
     presenterMode/handleLogoClick chỉ để x-agri.header (dùng chung với trang
     chẩn đoán) không báo lỗi console, bấm logo ở đây không có tác dụng gì. --}}
<body x-data="{ mobileDrawerOpen:false, presenterMode:false, handleLogoClick(){} }">

  <x-agri.mobile-nav />

  <x-agri.header />

  <!-- Thanh tab điều hướng dưới banner - chỉ desktop (mobile dùng drawer ở
       trên), hiện luôn kể cả chưa đăng nhập (xem nav-tabs.blade.php). -->
  <div class="hidden md:block">
    <x-agri.nav-tabs />
  </div>

  <div class="px-5 md:px-7 2xl:px-10 py-2.5 md:py-3 border-b flex flex-wrap items-center gap-2 md:gap-3" style="border-color:#eceae3">
    <!-- Ẩn tiêu đề ở mobile để hàng lọc đỡ chật (đã có tiêu đề ở nav-tabs/drawer rồi) -->
    <p class="hidden md:block font-bold text-[15px] md:text-[17px] mr-auto shrink-0" style="color:#12341d">Bản đồ dịch bệnh cây trồng</p>
    <label class="text-[12.5px] font-semibold text-gray-500 shrink-0">Lọc theo cây:</label>
    <select id="cropFilter" class="text-[13px] border rounded-lg px-3 py-1.5 flex-1 min-w-0 md:flex-none">
      <option value="">Tất cả</option>
      <option value="che">Chè</option>
      <option value="lua">Lúa</option>
      <option value="ngo">Ngô</option>
      <option value="san">Sắn</option>
      <option value="ca_chua">Cà chua</option>
      <option value="xoai">Xoài</option>
      <option value="ot">Ớt</option>
    </select>
    <span id="mapCount" class="text-[12px] md:text-[12.5px] text-gray-400 whitespace-nowrap w-full md:w-auto order-last md:order-none"></span>
  </div>

  <div id="map" class="flex-1" style="min-height:320px"></div>

  @if (!config('services.google_maps.key'))
    <div class="p-6 text-center text-[13px] text-gray-500">
      Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên bản đồ không thể hiển thị.
    </div>
  @endif

  {{-- ================= MODAL: chi tiết 1 report - mở khi bấm "Xem chi tiết"
       trong popup marker. Vanilla JS (độc lập với Alpine ở trên) - xem
       openReportDetail()/closeReportDetail() bên dưới. ================= --}}
  <div id="reportDetailModal" class="fixed inset-0 z-50 items-center justify-center p-4" style="display:none;background:rgba(18,52,29,.5)" onclick="if(event.target===this) closeReportDetail()">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 relative shadow-2xl max-h-[88vh] overflow-y-auto">
      <button onclick="closeReportDetail()" class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full flex items-center justify-center" style="background:#fbf1ea;color:#c1440e"><i data-lucide="x" class="w-4 h-4"></i></button>

      <img id="rdImage" src="" alt="Ảnh chẩn đoán" class="w-full h-52 object-cover rounded-xl mb-4" style="background:#f2f7ee">

      <div class="flex items-center gap-2 flex-wrap pr-8">
        <p id="rdDisease" class="text-[19px] font-bold" style="color:#c1440e"></p>
        <span id="rdProbability" class="text-[12.5px] font-bold px-2 py-0.5 rounded-full" style="background:#f6e2d1;color:#c1440e"></span>
      </div>
      <p class="text-[12.5px] mt-1" style="color:#6b7268"><span id="rdCrop"></span> · <span id="rdDate"></span> · <span id="rdLevel"></span></p>

      <div id="rdPathogenBox" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[13.5px] font-bold mb-1" style="color:#12341d">Tác nhân gây bệnh</p>
        <p id="rdPathogen" class="text-[13px] leading-relaxed" style="color:#4a5245"></p>
      </div>
      <div id="rdSignsBox" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[13.5px] font-bold mb-1" style="color:#12341d">Dấu hiệu quan sát được trong ảnh</p>
        <p id="rdSigns" class="text-[13px] leading-relaxed" style="color:#4a5245"></p>
      </div>
      <div id="rdSymptomsBox" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[13.5px] font-bold mb-1" style="color:#12341d">Dấu hiệu nhận biết chung</p>
        <p id="rdSymptoms" class="text-[13px] leading-relaxed" style="color:#4a5245"></p>
      </div>
      <div id="rdTreatmentBox" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[13.5px] font-bold mb-1" style="color:#12341d">Cách chữa trị</p>
        <p id="rdTreatment" class="text-[13px] leading-relaxed" style="color:#4a5245"></p>
      </div>
      <div id="rdPreventionBox" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[13.5px] font-bold mb-1" style="color:#12341d">Cách phòng ngừa</p>
        <p id="rdPrevention" class="text-[13px] leading-relaxed" style="color:#4a5245"></p>
      </div>

      <a id="rdMapLink" href="#" target="_blank" rel="noopener" class="mt-5 flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #dbe8d2;color:var(--forest)">
        <i data-lucide="map-pin" class="w-4 h-4"></i> Mở vị trí trên Google Maps
      </a>
    </div>
  </div>

  <script>
    window.DISEASE_MAP_DATA_URL = '{{ route('agri.disease-map.data') }}';
    let gmap, gmarkers = [];
    let reportsById = {};

    function initDiseaseMap(){
      gmap = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 21.5944, lng: 105.8480 }, // Thái Nguyên
        zoom: 8,
        gestureHandling: 'greedy',
      });
      loadReports();
      document.getElementById('cropFilter').addEventListener('change', loadReports);
    }

    function levelColor(level){
      if(level === 'Nặng') return '#c1440e';
      if(level === 'Trung bình') return '#c9762c';
      return '#1f6d3c';
    }

    function loadReports(){
      const crop = document.getElementById('cropFilter').value;
      const url = window.DISEASE_MAP_DATA_URL + (crop ? ('?crop=' + encodeURIComponent(crop)) : '');
      fetch(url).then(r => r.json()).then(data => {
        gmarkers.forEach(m => m.setMap(null));
        gmarkers = [];
        reportsById = {};
        const reports = data.reports || [];
        document.getElementById('mapCount').textContent = reports.length + ' điểm đã ghi nhận';
        reports.forEach(r => {
          reportsById[r.id] = r;
          const marker = new google.maps.Marker({
            position: { lat: parseFloat(r.lat), lng: parseFloat(r.lng) },
            map: gmap,
            title: r.disease_name,
          });
          // Popup marker làm đẹp hơn: ảnh to hơn, badge mức độ theo màu, nút
          // "Xem chi tiết" mở modal đầy đủ thông tin (thay vì nhồi hết vào popup).
          const info = new google.maps.InfoWindow({
            content:
              '<div style="width:220px;font-family:\'Be Vietnam Pro\',sans-serif">' +
                '<img src="' + r.image_url + '" style="width:100%;height:110px;object-fit:cover;border-radius:10px;margin-bottom:8px" onerror="this.style.display=\'none\'">' +
                '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">' +
                  '<p style="font-weight:700;margin:0;color:#12341d;font-size:13.5px">' + r.disease_name + '</p>' +
                  (r.level ? '<span style="font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:99px;background:' + levelColor(r.level) + '1a;color:' + levelColor(r.level) + '">' + r.level + '</span>' : '') +
                '</div>' +
                '<p style="font-size:11.5px;color:#8a8f83;margin:3px 0 10px">' + r.crop_label + ' · ' + r.date + '</p>' +
                '<button onclick="openReportDetail(' + r.id + ')" style="width:100%;padding:7px 0;border-radius:8px;border:none;background:#1f6d3c;color:#fff;font-size:12.5px;font-weight:600;cursor:pointer">Xem chi tiết</button>' +
              '</div>',
          });
          marker.addListener('click', () => info.open(gmap, marker));
          gmarkers.push(marker);
        });
      }).catch(() => {});
    }

    function fillText(id, boxId, val){
      const el = document.getElementById(id);
      el.textContent = val || '';
      if(boxId) document.getElementById(boxId).style.display = val ? '' : 'none';
    }

    function openReportDetail(id){
      const r = reportsById[id];
      if(!r) return;
      document.getElementById('rdImage').src = r.image_url;
      document.getElementById('rdDisease').textContent = r.disease_name;
      const prob = document.getElementById('rdProbability');
      if(r.probability !== null && r.probability !== undefined){
        prob.textContent = r.probability + '%';
        prob.style.display = '';
      } else {
        prob.style.display = 'none';
      }
      document.getElementById('rdCrop').textContent = r.crop_label;
      document.getElementById('rdDate').textContent = r.date;
      document.getElementById('rdLevel').textContent = r.level || '';
      fillText('rdPathogen', 'rdPathogenBox', r.pathogen);
      fillText('rdSigns', 'rdSignsBox', r.signs_in_photo);
      fillText('rdSymptoms', 'rdSymptomsBox', r.symptoms);
      fillText('rdTreatment', 'rdTreatmentBox', r.treatment);
      fillText('rdPrevention', 'rdPreventionBox', r.prevention);
      document.getElementById('rdMapLink').href = 'https://www.google.com/maps?q=' + r.lat + ',' + r.lng;

      document.getElementById('reportDetailModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
      if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeReportDetail(){
      document.getElementById('reportDetailModal').style.display = 'none';
      document.body.style.overflow = '';
    }
  </script>
  @if (config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initDiseaseMap" async defer></script>
  @endif

  <script>
    window.addEventListener('load', function(){
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>

</body>
</html>
