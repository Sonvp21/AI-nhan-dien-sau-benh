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
{{-- Google đã gỡ hẳn google.maps.visualization.HeatmapLayer khỏi Maps
     JavaScript API - dùng deck.gl (theo hướng dẫn migrate chính thức của
     Google) để vẽ lớp mật độ, xem DiseaseMapKit.createHeatmap() trong
     disease-map.js. --}}
<script src="https://unpkg.com/deck.gl@8.9.22/dist.min.js"></script>
<script src="https://unpkg.com/@deck.gl/google-maps@8.9.22/dist.min.js"></script>
{{-- Gom cụm marker khi nhiều điểm gần nhau/chồng lấp - xem cách dùng trong
     initDiseaseMap()/loadReports() bên dưới (biến "clusterer"). --}}
<script src="https://unpkg.com/@googlemaps/markerclusterer@2.6.2/dist/index.min.js"></script>
{{-- Chart.js cho biểu đồ cột "Theo loại bệnh" trong popup "Thống kê vùng dịch". --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{margin:0;font-family:'Be Vietnam Pro',system-ui,sans-serif;background:#fff;color:#1c231d;min-height:100vh;display:flex;flex-direction:column;}
  a{color:#1f6d3c;text-decoration:none;}
  .no-scrollbar::-webkit-scrollbar{display:none;}
  .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
  [x-cloak]{display:none!important;}
  .zd-point-row:hover{background:#f2f7ee;}
</style>
</head>
{{-- Layout giống trang chẩn đoán nhưng ẨN banner header (x-agri.header) để
     bản đồ có nhiều không gian hiển thị hơn - chỉ còn topbar+drawer mobile và
     thanh tab desktop (xem agri-index.blade.php). Trang này không cần
     agriApp() đầy đủ, chỉ cần mobileDrawerOpen cho drawer. --}}
<body x-data="{ mobileDrawerOpen:false }">

  <x-agri.mobile-nav />

  <!-- Thanh tab điều hướng - chỉ desktop (mobile dùng drawer ở trên), hiện
       luôn kể cả chưa đăng nhập (xem nav-tabs.blade.php). Đã ẩn banner header
       ở trang này để bản đồ có nhiều không gian hơn. -->
  <div class="hidden md:block">
    <x-agri.nav-tabs />
  </div>

  <div class="relative flex-1" style="min-height:320px">
    <div id="map" class="absolute inset-0"></div>

    @php
      $mapCrops = ['che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn', 'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt'];
    @endphp
    <x-agri.map-control-panel :crops="$mapCrops" />
    <x-agri.map-zone-stats :crops="$mapCrops" :counts="[]" />
    <x-agri.map-legend />

    <span id="mapCount" class="absolute bottom-3 left-3 z-10 text-[11.5px] font-semibold px-2.5 py-1.5 rounded-full bg-white shadow" style="color:#6b7268"></span>
  </div>

  @if (!config('services.google_maps.key'))
    <div class="p-6 text-center text-[13px] text-gray-500">
      Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên bản đồ không thể hiển thị.
    </div>
  @endif

  {{-- ================= POPUP THỐNG KÊ 1 VÙNG DỊCH (dạng bottom-sheet) =================
       Mở khi bấm 1 dòng cây trồng trong popup "Thống kê vùng dịch" (nút nổi
       góc phải trên bản đồ) - khác thiết kế ở trang admin: đây là 1 "bottom
       sheet" trượt lên từ mép dưới màn hình, kéo rộng ra và chia 3 CỘT (xếp
       thành 1 cột trên mobile - responsive):
         Cột 1: biểu đồ cột dọc (Chart.js) số điểm theo MỨC ĐỘ.
         Cột 2: bảng số điểm theo LOẠI BỆNH (không kèm biểu đồ).
         Cột 3: danh sách từng ĐIỂM - bấm vào 1 điểm sẽ đóng bottom-sheet,
                zoom bản đồ tới đúng điểm đó và mở popup chi tiết (xem
                zoomToReport() + renderZoneDetailPanel() bên dưới).
       Dữ liệu lấy trực tiếp từ danh sách report đã lọc theo cây (không cần
       gọi thêm API). Ẩn mặc định, đóng bằng nút X - khi đóng (hoặc khi bấm
       vào 1 điểm) sẽ hiện lại nút nổi "btnReopenZoneDetail" ở góc dưới trái
       để mở lại đúng dữ liệu đã xem, không cần bấm lại "Thống kê vùng dịch"
       từ đầu. --}}
  <div id="zoneDetailPanel" class="fixed left-0 right-0 bottom-0 z-30" style="transform:translateY(100%);transition:transform .28s ease">
    {{-- LƯU Ý: popup KHÔNG đặt height/max-height riêng - chiều cao của nó tự
         động bằng đúng header + biểu đồ mức độ (cột 1, cao cố định ZD_ROW_H
         = 170px canvas, xem <style> #zdLevelChart phía dưới hoặc giá trị
         "height:170px" ở cột 1). Bảng theo bệnh (cột 2) và danh sách điểm
         (cột 3) được đặt CÙNG chiều cao 170px đó (không phải max-height) +
         overflow-y-auto riêng - nội dung dài hơn 170px thì tự cuộn bên
         trong, KHÔNG kéo popup cao thêm theo. --}}
    <div class="mx-auto shadow-2xl" style="width:min(96vw,1040px);background:#fff;border-radius:22px 22px 0 0;border:1px solid #eceae3;border-bottom:none;display:flex;flex-direction:column">
      <div class="flex items-center justify-between px-5 pt-4 pb-3 shrink-0" style="border-bottom:1px solid #f0f2ed">
        <div class="flex items-center gap-3 min-w-0">
          <img id="zdImg" src="" alt="" class="w-11 h-11 rounded-full object-cover shrink-0" style="border:2px solid #dbe8d2;background:#f2f7ee">
          <div class="min-w-0">
            <p id="zdCropLabel" class="text-[15px] font-bold truncate" style="color:#12341d"></p>
            <p id="zdTotal" class="text-[12px]" style="color:#6b7268"></p>
          </div>
        </div>
        <button type="button" id="btnCloseZoneDetail" title="Đóng" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
      <div class="px-5 py-4 flex flex-col md:flex-row gap-5">
        <div class="md:w-1/3 min-w-0">
          <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2.5" style="color:#9aa192">Theo mức độ</p>
          <div style="height:170px"><canvas id="zdLevelChart"></canvas></div>
        </div>

        <div class="md:w-1/3 min-w-0">
          <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2.5" style="color:#9aa192">Theo loại bệnh</p>
          <div class="rounded-xl no-scrollbar" style="border:1px solid #eceae3;height:170px;overflow-y:auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr style="background:#f8faf6">
                  <th class="text-left px-3 py-2 text-[10.5px] font-bold uppercase tracking-wide" style="color:#9aa192">Tên bệnh</th>
                  <th class="text-right px-3 py-2 text-[10.5px] font-bold uppercase tracking-wide" style="color:#9aa192">Số điểm</th>
                </tr>
              </thead>
              <tbody id="zdDiseaseTableBody"></tbody>
            </table>
          </div>
        </div>

        <div class="md:w-1/3 min-w-0">
          <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2.5" style="color:#9aa192">Danh sách điểm</p>
          <div id="zdPoints" class="space-y-1.5 no-scrollbar" style="height:170px;overflow-y:auto"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Nút nổi "mở lại" bottom-sheet thống kê vùng dịch sau khi đã đóng (bấm
       X hoặc bấm vào 1 điểm trong danh sách) - ẨN mặc định, chỉ hiện khi đã
       có dữ liệu 1 vùng dịch được xem gần nhất (xem zoneDetailHasData trong
       script bên dưới). Đặt góc dưới TRÁI, tránh đè lên mapCount (cũng góc
       dưới trái nhưng nằm sát trong khung bản đồ hơn). --}}
  <button type="button" id="btnReopenZoneDetail" title="Hiện lại thống kê vùng dịch"
          class="fixed z-20 w-11 h-11 rounded-full shadow-lg flex items-center justify-center bg-white transition hover:shadow-xl active:scale-95"
          style="left:12px;bottom:64px;border:1px solid #dbe8d2;color:#c1440e;display:none">
    <i data-lucide="chevrons-up" class="w-5 h-5"></i>
  </button>

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

  <script src="{{ asset('js/disease-map.js') }}?v={{ file_exists(public_path('js/disease-map.js')) ? filemtime(public_path('js/disease-map.js')) : time() }}"></script>
  <script>
    window.DISEASE_MAP_DATA_URL = '{{ route('agri.disease-map.data') }}';
    let gmap, gmarkers = [];
    let heatmapLayer = null;
    let clusterer = null; // biến khác tên với global "markerClusterer" (namespace của CDN) để không đè lên nhau
    let reportsById = {};
    let markersById = {}; // dùng để zoom tới đúng marker khi bấm 1 điểm trong bottom-sheet "Thống kê vùng dịch"
    let infoWindowsById = {}; // popup chi tiết sẵn của từng report - mở lại khi bấm điểm đó trong bottom-sheet
    let activeInfoWindow = null; // đóng popup đang mở trước khi mở popup khác (zoomToReport)
    let controlState = { crop: '', heatmapOn: true, markersOn: true };

    function initDiseaseMap(){
      gmap = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 21.5944, lng: 105.8480 }, // Thái Nguyên
        zoom: 8,
        gestureHandling: 'greedy',
        // Đã có nút điều khiển riêng (đổi loại bản đồ/lọc cây/lớp dữ liệu) nên
        // ẩn hết tool mặc định của Google Maps (zoom, map type, street view,
        // fullscreen) cho gọn - đỡ trùng lặp chức năng.
        disableDefaultUI: true,
      });

      DiseaseMapKit.initLegend();

      // Cho phép mở trang này kèm ?crop=che để lọc sẵn theo cây - dùng bởi nút
      // "Xem bản đồ vùng dịch [Cây]" ở panel kết quả chẩn đoán (agri-app.js).
      const presetCrop = new URLSearchParams(window.location.search).get('crop');

      controlState = DiseaseMapKit.initControlPanel({
        getMap: () => gmap,
        onCropChange: function(crop){
          controlState.crop = crop;
          // Đổi cây qua dropdown điều khiển (khác luồng bấm "Thống kê vùng
          // dịch") - dữ liệu vùng dịch đang xem (nếu có) hết còn khớp với bộ
          // lọc mới, nên xoá luôn (không cho nút "mở lại" hiện ra nữa).
          zoneDetailHasData = false;
          hideZoneDetailPanel();
          loadReports();
        },
        onLayerToggle: function(layer, on){
          if(layer === 'heatmap' && heatmapLayer) heatmapLayer.setMap(on ? gmap : null);
          // MarkerClusterer là 1 OverlayView nên tự có .setMap() - không lặp
          // qua từng marker nữa (sẽ bị clusterer ghi đè lại ở lần render kế).
          if(layer === 'markers' && clusterer) clusterer.setMap(on ? gmap : null);
        },
      });

      if(presetCrop && DiseaseMapKit.setControlPanelCropUI(presetCrop)){
        controlState.crop = presetCrop;
      }

      // Nút nổi "Thống kê vùng dịch" - bấm 1 dòng cây trồng trong popup sẽ
      // lọc bản đồ theo cây đó, zoom đến các điểm (fitBounds=true) VÀ mở
      // bottom-sheet thống kê riêng cây đó (xem renderZoneDetailPanel()).
      DiseaseMapKit.initSimplePanel('btnZoneStats', 'zoneStatsPanel', 'btnCloseZoneStats');
      document.querySelectorAll('.zone-stat-row').forEach(function(row){
        row.addEventListener('click', function(){
          const cropKey = row.getAttribute('data-crop');
          controlState.crop = cropKey;
          DiseaseMapKit.setControlPanelCropUI(cropKey);
          loadReports(true);
          document.getElementById('zoneStatsPanel').style.display = 'none';
        });
      });
      document.getElementById('btnCloseZoneDetail').addEventListener('click', hideZoneDetailPanel);
      document.getElementById('btnReopenZoneDetail').addEventListener('click', showZoneDetailPanel);

      // Chờ tải xong ảnh cây trồng thật (dùng làm icon marker) trước khi vẽ
      // marker lần đầu - xem preloadCropImages() trong disease-map.js.
      // LƯU Ý: gọi loadReports() qua 1 hàm bọc, KHÔNG truyền thẳng loadReports
      // cho .then() - Promise.all() trong preloadCropImages() resolve ra 1
      // MẢNG, mảng luôn "truthy" trong JS nên nếu để .then(loadReports) thì
      // tham số fitBounds sẽ vô tình nhận giá trị truthy đó, khiến bottom-
      // sheet "Thống kê vùng dịch" tự bật lên ngay khi tải trang (dù chưa
      // bấm gì) - đây chính là lỗi đã gặp.
      DiseaseMapKit.preloadCropImages().then(function(){ loadReports(); });
    }

    // zoneDetailHasData: đánh dấu đã có dữ liệu 1 vùng dịch (cây) được xem
    // gần nhất - dùng để quyết định có hiện nút nổi "mở lại" (bottom-left)
    // khi bottom-sheet bị đóng hay không (X, hoặc bấm vào 1 điểm để zoom).
    let zoneDetailHasData = false;

    function hideZoneDetailPanel(){
      document.getElementById('zoneDetailPanel').style.transform = 'translateY(100%)';
      document.getElementById('btnReopenZoneDetail').style.display = zoneDetailHasData ? 'flex' : 'none';
    }

    function showZoneDetailPanel(){
      if(!zoneDetailHasData) return;
      document.getElementById('zoneDetailPanel').style.transform = 'translateY(0)';
      document.getElementById('btnReopenZoneDetail').style.display = 'none';
    }

    // Bấm vào 1 điểm trong danh sách "Danh sách điểm" của bottom-sheet: đóng
    // bottom-sheet lại để thấy bản đồ, pan+zoom tới đúng marker đó rồi mở
    // popup chi tiết (InfoWindow) đã tạo sẵn cho report đó trong loadReports().
    // QUAN TRỌNG: phải đợi map bắn event 'idle' SAU KHI pan/zoom xong mới mở
    // popup - nếu mở ngay (đồng bộ) thì lúc marker đang bị MarkerClusterer
    // gom cụm (chưa kịp tách cụm ra do vừa đổi zoom), popup có thể không
    // hiện hoặc hiện sai vị trí. Đây chính là lý do popup "chưa hiện" khi
    // bấm vào điểm ở lần trước.
    function zoomToReport(id){
      const marker = markersById[id];
      const info = infoWindowsById[id];
      if(!marker || !info) return;
      hideZoneDetailPanel();
      gmap.panTo(marker.getPosition());
      if(gmap.getZoom() < 16) gmap.setZoom(16);
      google.maps.event.addListenerOnce(gmap, 'idle', function(){
        if(activeInfoWindow) activeInfoWindow.close();
        info.open(gmap, marker);
        activeInfoWindow = info;
      });
    }

    // Bottom-sheet thống kê 1 vùng dịch (1 cây cụ thể) - mở khi bấm 1 dòng
    // trong popup "Thống kê vùng dịch". Tính trực tiếp từ "reports" đã lọc
    // theo cây (không cần gọi thêm API), chia 3 cột (xem HTML):
    //   Cột 1: biểu đồ cột DỌC (Chart.js) theo mức độ.
    //   Cột 2: bảng theo loại bệnh (không kèm biểu đồ).
    //   Cột 3: danh sách từng điểm - bấm vào 1 điểm sẽ zoom tới + mở popup
    //          chi tiết (xem zoomToReport() ở trên).
    let zdLevelChart = null;
    function renderZoneDetailPanel(cropKey, reports){
      const meta = (DiseaseMapKit.CROP_META && DiseaseMapKit.CROP_META[cropKey]) || { label: cropKey, img: '' };
      document.getElementById('zdCropLabel').textContent = meta.label;
      document.getElementById('zdImg').src = meta.img || '';
      document.getElementById('zdTotal').textContent = reports.length + ' điểm đang hoạt động';

      // Cột 1: biểu đồ cột dọc theo mức độ (3 cột màu riêng theo
      // DiseaseMapKit.levelColor() để đồng bộ màu với marker/legend).
      const levels = ['Nhẹ', 'Trung bình', 'Nặng'];
      const levelCounts = { 'Nhẹ': 0, 'Trung bình': 0, 'Nặng': 0 };
      reports.forEach(function(r){ if(levelCounts[r.level] !== undefined) levelCounts[r.level]++; });

      if(zdLevelChart){ zdLevelChart.destroy(); zdLevelChart = null; }
      const levelCanvas = document.getElementById('zdLevelChart');
      if(levelCanvas && typeof Chart !== 'undefined'){
        zdLevelChart = new Chart(levelCanvas, {
          type: 'bar',
          data: {
            labels: levels,
            datasets: [{
              data: levels.map(function(l){ return levelCounts[l]; }),
              backgroundColor: levels.map(function(l){ return DiseaseMapKit.levelColor(l); }),
              borderRadius: 4,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
          },
        });
      }

      // Cột 2: bảng theo loại bệnh
      const diseaseCounts = {};
      reports.forEach(function(r){ diseaseCounts[r.disease_name] = (diseaseCounts[r.disease_name] || 0) + 1; });
      const sortedDiseases = Object.keys(diseaseCounts)
        .map(function(name){ return { name: name, count: diseaseCounts[name] }; })
        .sort(function(a, b){ return b.count - a.count; });

      document.getElementById('zdDiseaseTableBody').innerHTML = sortedDiseases.length
        ? sortedDiseases.map(function(d){
            return '<tr style="border-top:1px solid #f0f2ed">' +
              '<td class="px-3 py-2" style="color:#12341d">' + d.name + '</td>' +
              '<td class="px-3 py-2 text-right font-bold" style="color:var(--danger)">' + d.count + '</td>' +
            '</tr>';
          }).join('')
        : '<tr><td colspan="2" class="px-3 py-3 text-center text-[12.5px]" style="color:#9aa192">Cây này hiện không có điểm nào đang hoạt động.</td></tr>';

      // Cột 3: danh sách điểm - bấm vào 1 điểm thì zoom tới + mở popup chi tiết
      const pointsEl = document.getElementById('zdPoints');
      pointsEl.innerHTML = reports.length
        ? reports.map(function(r){
            const color = DiseaseMapKit.levelColor(r.level);
            return '<button type="button" class="zd-point-row w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-left transition" data-id="' + r.id + '">' +
              '<span class="min-w-0">' +
                '<span class="block text-[13px] font-semibold truncate" style="color:#12341d">' + r.disease_name + '</span>' +
                '<span class="block text-[11.5px]" style="color:#8a8f83">' + (r.date || '') + '</span>' +
              '</span>' +
              '<span class="shrink-0 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:#f2f4ef;color:' + color + '">' + (r.level || '') + '</span>' +
            '</button>';
          }).join('')
        : '<p class="text-[13px]" style="color:#9aa192">Chưa có điểm đang hoạt động.</p>';
      pointsEl.querySelectorAll('.zd-point-row').forEach(function(btn){
        btn.addEventListener('click', function(){
          zoomToReport(parseInt(btn.getAttribute('data-id'), 10));
        });
      });

      zoneDetailHasData = true;
      document.getElementById('zoneDetailPanel').style.transform = 'translateY(0)';
      document.getElementById('btnReopenZoneDetail').style.display = 'none';
      if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Cập nhật số đếm trong popup "Thống kê vùng dịch" - trang này không có
    // controller riêng (Route::view) nên không biết số liệu lúc render trang,
    // phải lấy từ data.stats trả về mỗi lần loadReports() gọi API.
    function updateZoneStatsCounts(stats){
      document.querySelectorAll('.zone-stat-row').forEach(function(row){
        const c = stats[row.getAttribute('data-crop')] || 0;
        const badge = row.querySelector('.zone-stat-count');
        if(!badge) return;
        badge.textContent = c + ' điểm';
        badge.style.background = c > 0 ? '#fbe3dc' : '#f2f4ef';
        badge.style.color = c > 0 ? 'var(--danger)' : '#9aa192';
      });
    }

    function loadReports(fitBounds){
      const crop = controlState.crop;
      const url = window.DISEASE_MAP_DATA_URL + (crop ? ('?crop=' + encodeURIComponent(crop)) : '');
      fetch(url).then(r => r.json()).then(data => {
        updateZoneStatsCounts(data.stats || {});
        gmarkers.forEach(m => m.setMap(null));
        gmarkers = [];
        if(clusterer){ clusterer.setMap(null); clusterer = null; }
        if(heatmapLayer){ heatmapLayer.setMap(null); heatmapLayer = null; }
        if(activeInfoWindow){ activeInfoWindow.close(); activeInfoWindow = null; }
        reportsById = {};
        markersById = {};
        infoWindowsById = {};
        const reports = data.reports || [];
        document.getElementById('mapCount').textContent = reports.length + ' điểm đang hoạt động';

        const showMarkers = controlState.markersOn;
        const showHeatmap = controlState.heatmapOn;

        reports.forEach(r => {
          reportsById[r.id] = r;
          // KHÔNG gán map ở đây nữa - để MarkerClusterer bên dưới quản lý
          // marker nào hiện/gom cụm (gán map riêng ở đây sẽ bị clusterer ghi
          // đè lại ngay khi nó render).
          const marker = new google.maps.Marker({
            position: { lat: parseFloat(r.lat), lng: parseFloat(r.lng) },
            title: r.disease_name + ' (' + r.crop_label + ')',
            icon: DiseaseMapKit.markerIcon(r.crop, r.level),
          });
          const info = new google.maps.InfoWindow({
            content: DiseaseMapKit.buildPopupHtml(r),
          });
          marker.addListener('click', () => info.open(gmap, marker));
          markersById[r.id] = marker;
          infoWindowsById[r.id] = info;
          gmarkers.push(marker);
        });

        // Gom cụm marker (thư viện chính thức Google) - nhiều điểm gần nhau/
        // chồng lấp sẽ gom thành 1 bong bóng số đếm, tự tách ra khi zoom vào,
        // thay vì hiện chồng chất lên nhau như trước.
        if(gmarkers.length){
          clusterer = new markerClusterer.MarkerClusterer({
            map: showMarkers ? gmap : null,
            markers: gmarkers,
          });
        }

        // Bấm 1 dòng trong popup "Thống kê vùng dịch" -> zoom bản đồ đến
        // đúng các điểm của cây đó VÀ mở kèm bottom-sheet thống kê riêng cây
        // đó (chỉ áp dụng khi có yêu cầu fitBounds).
        if(fitBounds){
          if(reports.length){
            const bounds = new google.maps.LatLngBounds();
            reports.forEach(r => bounds.extend({ lat: parseFloat(r.lat), lng: parseFloat(r.lng) }));
            gmap.fitBounds(bounds, 60);
          }
          renderZoneDetailPanel(crop, reports);
        }

        // Lớp mật độ (heatmap, vẽ bằng deck.gl - xem createHeatmap() trong
        // disease-map.js): thể hiện trực quan "vùng dịch" - nơi các điểm tập
        // trung dày sẽ hiện màu nóng hơn, giúp thấy ngay khu vực cần chú ý mà
        // không cần bấm từng marker. Bọc try/catch riêng (không để lỗi ở đây
        // rớt xuống .catch() ngoài rồi bị nuốt im lặng) - nếu deck.gl chưa nạp
        // được (mất mạng/CDN chặn) sẽ thấy rõ lý do trong console thay vì bấm
        // nút không ăn.
        if(reports.length){
          try {
            heatmapLayer = DiseaseMapKit.createHeatmap(reports);
            heatmapLayer.setMap(showHeatmap ? gmap : null);
          } catch (err) {
            console.error('Không tạo được lớp heatmap (deck.gl có thể chưa nạp được):', err);
          }
        }
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
  {{-- Không cần &libraries=visualization nữa - lớp heatmap giờ vẽ bằng
       deck.gl (Google đã gỡ google.maps.visualization.HeatmapLayer), xem
       DiseaseMapKit.createHeatmap() trong disease-map.js. --}}
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
