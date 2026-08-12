<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Bản đồ dịch bệnh</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{margin:0;font-family:'Inter',sans-serif;background:var(--mist);color:#1c231d;}
  .font-display{font-family:'Space Grotesk',sans-serif;}
  #map{width:100%;height:calc(100vh - 158px);min-height:420px;}
</style>
</head>
<body>

<header class="flex items-center justify-between px-5 md:px-10 py-4 bg-white border-b">
  <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold" style="color:var(--forest)">← Trang chủ</a>
  <h1 class="font-display font-bold text-[17px] md:text-[19px]" style="color:var(--forest)">Bản đồ dịch bệnh cây trồng</h1>
  @guest
    <a href="{{ route('agri.auth') }}" class="text-[13px] font-semibold" style="color:var(--soil)">Đăng nhập</a>
  @else
    <span class="w-16"></span>
  @endguest
</header>

<x-agri.nav-tabs />

<div class="px-5 md:px-10 py-3 bg-white border-b flex items-center gap-3">
  <label class="text-[12.5px] font-semibold text-gray-500">Lọc theo cây:</label>
  <select id="cropFilter" class="text-[13px] border rounded-lg px-3 py-1.5">
    <option value="">Tất cả</option>
    <option value="che">Chè</option>
    <option value="lua">Lúa</option>
    <option value="ngo">Ngô</option>
    <option value="san">Sắn</option>
    <option value="ca_chua">Cà chua</option>
    <option value="xoai">Xoài</option>
    <option value="ot">Ớt</option>
  </select>
  <span id="mapCount" class="text-[12.5px] text-gray-400"></span>
</div>

<div id="map"></div>

@if (!config('services.google_maps.key'))
  <div class="p-6 text-center text-[13px] text-gray-500">
    Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên bản đồ không thể hiển thị.
  </div>
@endif

<script>
  window.DISEASE_MAP_DATA_URL = '{{ route('agri.disease-map.data') }}';
  let gmap, gmarkers = [];

  function initDiseaseMap(){
    gmap = new google.maps.Map(document.getElementById('map'), {
      center: { lat: 21.5944, lng: 105.8480 }, // Thái Nguyên
      zoom: 8,
    });
    loadReports();
    document.getElementById('cropFilter').addEventListener('change', loadReports);
  }

  function loadReports(){
    const crop = document.getElementById('cropFilter').value;
    const url = window.DISEASE_MAP_DATA_URL + (crop ? ('?crop=' + encodeURIComponent(crop)) : '');
    fetch(url).then(r => r.json()).then(data => {
      gmarkers.forEach(m => m.setMap(null));
      gmarkers = [];
      const reports = data.reports || [];
      document.getElementById('mapCount').textContent = reports.length + ' điểm đã ghi nhận';
      reports.forEach(r => {
        const marker = new google.maps.Marker({
          position: { lat: parseFloat(r.lat), lng: parseFloat(r.lng) },
          map: gmap,
          title: r.disease_name,
        });
        const info = new google.maps.InfoWindow({
          content: '<div style="max-width:200px">' +
            '<img src="' + r.image_url + '" style="width:100%;height:100px;object-fit:cover;border-radius:8px" onerror="this.style.display=\'none\'">' +
            '<p style="font-weight:600;margin:6px 0 2px">' + r.disease_name + '</p>' +
            '<p style="font-size:12px;color:#666;margin:0">' + r.crop_label + ' · ' + r.date + '</p>' +
            '</div>',
        });
        marker.addListener('click', () => info.open(gmap, marker));
        gmarkers.push(marker);
      });
    }).catch(() => {});
  }
</script>
@if (config('services.google_maps.key'))
  <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initDiseaseMap" async defer></script>
@endif

<script>
  if (typeof lucide !== 'undefined') lucide.createIcons();
</script>

</body>
</html>
