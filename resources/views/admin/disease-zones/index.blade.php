@extends('layouts.admin')

@section('title', 'Vùng dịch')

@section('content')
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-bold" style="color:#12341d">Dashboard vùng dịch</h1>
      <p class="text-sm text-gray-500 mt-0.5">Thống kê điểm phát hiện bệnh đang hoạt động theo từng loại cây, preview qua bản đồ. Đánh dấu "đã xử lý" để ẩn khỏi bản đồ (vẫn lưu lại record).</p>
    </div>
    <div class="text-right shrink-0">
      <p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Tổng đang hoạt động</p>
      <p class="text-2xl font-bold" style="color:var(--danger)">{{ number_format($totalActive) }}</p>
    </div>
  </div>

  {{-- ================= BẢN ĐỒ FULL HEIGHT + NÚT NỔI =================
       Dải thẻ thống kê theo cây (từng nằm cố định ở đây) đã dời vào popup
       nút "Thống kê vùng dịch" (góc phải trên) để bản đồ có tối đa không
       gian hiển thị - xem x-agri.map-zone-stats. --}}
  <div class="bg-white rounded-xl border overflow-hidden mb-6">
    <div class="relative" style="height:calc(100vh - 190px);min-height:480px">
      <div id="map" class="absolute inset-0" style="background:#e2efd9"></div>

      <x-agri.map-control-panel :crops="$crops">
        <x-slot:extra>
          <button type="button" id="btnResolveByCrop" disabled title="Chọn 1 loại cây cụ thể trước" class="w-full mt-2.5 flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-[12px] font-semibold text-white transition disabled:opacity-40" style="background:var(--danger)">
            <i data-lucide="check-check" class="w-3.5 h-3.5"></i> Đánh dấu đã xử lý toàn bộ
          </button>
        </x-slot:extra>
      </x-agri.map-control-panel>
      <x-agri.map-zone-stats :crops="$crops" :counts="$counts" />
      <x-agri.map-legend />

      <span id="mapCount" class="absolute bottom-3 left-3 z-10 text-[11.5px] font-semibold px-2.5 py-1.5 rounded-full bg-white shadow" style="color:#6b7268"></span>
    </div>
  </div>

  @if (!config('services.google_maps.key'))
    <p class="text-sm text-gray-500 mb-6">Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên bản đồ không thể hiển thị.</p>
  @endif

  {{-- ================= BẢNG + BIỂU ĐỒ THỐNG KÊ THEO BỆNH =================
       Khác với popup "Thống kê vùng dịch" (danh sách theo CÂY, dùng để lọc
       bản đồ) - phần này thống kê theo TÊN BỆNH của ĐÚNG cây vừa bấm. KHÔNG
       hiện sẵn khi tải/reload trang - chỉ hiện lên khi admin bấm vào 1 dòng
       cây trong popup "Thống kê vùng dịch" (xem renderDiseaseStatsForCrop()
       + filterByCropCard() trong @push('scripts') bên dưới). Dữ liệu tính
       trực tiếp từ report đã lọc theo cây, không cần gọi thêm API. --}}
  <div id="diseaseStatsSection" class="grid lg:grid-cols-2 gap-4" style="display:none">
    <div class="bg-white rounded-xl border p-4">
      <p class="font-bold text-[14px] mb-3" style="color:#12341d">Thống kê theo bệnh - <span id="diseaseStatsCropLabel" style="color:var(--danger)"></span></p>
      <p id="diseaseStatsEmpty" class="text-[13px] text-gray-400" style="display:none">Cây này hiện không có report đang hoạt động.</p>
      <div id="diseaseStatsTableWrap" class="max-h-72 overflow-y-auto">
        <table class="w-full text-[13px]">
          <thead>
            <tr class="text-left text-gray-400 text-[11px] uppercase tracking-wide">
              <th class="pb-2 font-semibold">Tên bệnh</th>
              <th class="pb-2 font-semibold text-right">Số điểm</th>
            </tr>
          </thead>
          <tbody id="diseaseStatsTableBody"></tbody>
        </table>
      </div>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="font-bold text-[14px] mb-3" style="color:#12341d">Biểu đồ số điểm theo bệnh</p>
      <div style="height:260px"><canvas id="chartByDisease"></canvas></div>
    </div>
  </div>

  {{-- ================= MODAL: chi tiết 1 report + nút đánh dấu đã xử lý ================= --}}
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

      <div class="flex gap-2 mt-5">
        <a id="rdMapLink" href="#" target="_blank" rel="noopener" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #dbe8d2;color:var(--forest)">
          <i data-lucide="map-pin" class="w-4 h-4"></i> Mở Google Maps
        </a>
        <button id="rdResolveBtn" onclick="resolveCurrentReport()" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold text-white" style="background:var(--danger)">
          <i data-lucide="check-check" class="w-4 h-4"></i> Đã xử lý
        </button>
      </div>
    </div>
  </div>

  @push('scripts')
    {{-- Google đã gỡ hẳn google.maps.visualization.HeatmapLayer khỏi Maps
         JavaScript API - dùng deck.gl (theo hướng dẫn migrate chính thức của
         Google) để vẽ lớp mật độ, xem DiseaseMapKit.createHeatmap() trong
         disease-map.js. --}}
    <script src="https://unpkg.com/deck.gl@8.9.22/dist.min.js"></script>
    <script src="https://unpkg.com/@deck.gl/google-maps@8.9.22/dist.min.js"></script>
    {{-- Gom cụm marker khi nhiều điểm gần nhau/chồng lấp. --}}
    <script src="https://unpkg.com/@googlemaps/markerclusterer@2.6.2/dist/index.min.js"></script>
    {{-- Chart.js cho biểu đồ cột thống kê theo bệnh phía dưới bản đồ. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/disease-map.js') }}?v={{ file_exists(public_path('js/disease-map.js')) ? filemtime(public_path('js/disease-map.js')) : time() }}"></script>
    <script>
      window.DISEASE_MAP_DATA_URL = @json(route('agri.disease-map.data'));
      window.ADMIN_RESOLVE_URL_BASE = @json(url('/admin/vung-dich'));
      window.ADMIN_RESOLVE_BY_CROP_URL = @json(route('admin.vung-dich.resolve-by-crop'));
      window.ADMIN_CSRF = @json(csrf_token());

      let gmap, gmarkers = [];
      let heatmapLayer = null;
      let clusterer = null; // biến khác tên với global "markerClusterer" (namespace của CDN) để không đè lên nhau
      let reportsById = {};
      let currentDetailId = null;
      let controlState = { crop: '', heatmapOn: true, markersOn: true };

      function initDiseaseMap(){
        gmap = new google.maps.Map(document.getElementById('map'), {
          center: { lat: 21.5944, lng: 105.8480 },
          zoom: 8,
          gestureHandling: 'greedy',
          // Đã có nút điều khiển riêng nên ẩn hết tool mặc định của Google Maps.
          disableDefaultUI: true,
        });

        DiseaseMapKit.initLegend();

        controlState = DiseaseMapKit.initControlPanel({
          getMap: () => gmap,
          onCropChange: function(crop){
            controlState.crop = crop;
            syncResolveByCropButton();
            // Đổi cây qua dropdown điều khiển (khác luồng bấm "Thống kê vùng
            // dịch") - ẩn bảng/biểu đồ theo bệnh đi vì nó gắn với 1 cây cụ
            // thể vừa bấm, đổi cây kiểu này thì chưa có cây nào được "chọn".
            document.getElementById('diseaseStatsSection').style.display = 'none';
            loadReports();
          },
          onLayerToggle: function(layer, on){
            if(layer === 'heatmap' && heatmapLayer) heatmapLayer.setMap(on ? gmap : null);
            // MarkerClusterer là 1 OverlayView nên tự có .setMap() - không lặp
            // qua từng marker nữa (sẽ bị clusterer ghi đè lại ở lần render kế).
            if(layer === 'markers' && clusterer) clusterer.setMap(on ? gmap : null);
          },
        });

        // Chờ tải xong ảnh cây trồng thật (dùng làm icon marker) trước khi vẽ
        // marker lần đầu - xem preloadCropImages() trong disease-map.js.
        // LƯU Ý: gọi loadReports() qua 1 hàm bọc, KHÔNG truyền thẳng
        // loadReports cho .then() - Promise.all() trong preloadCropImages()
        // resolve ra 1 MẢNG, mảng luôn "truthy" trong JS nên nếu để
        // .then(loadReports) thì tham số fitBounds sẽ vô tình nhận giá trị
        // truthy đó, khiến bảng/biểu đồ theo bệnh tự bật lên ngay khi tải
        // trang (dù chưa bấm gì) - đây chính là lỗi đã gặp ở trang công khai.
        DiseaseMapKit.preloadCropImages().then(function(){ loadReports(); });
        document.getElementById('btnResolveByCrop').addEventListener('click', resolveByCrop);

        // Nút nổi "Thống kê vùng dịch" - bấm 1 dòng cây trồng trong popup sẽ
        // lọc bản đồ theo cây đó VÀ zoom đến các điểm (fitBounds=true).
        DiseaseMapKit.initSimplePanel('btnZoneStats', 'zoneStatsPanel', 'btnCloseZoneStats');
        document.querySelectorAll('.zone-stat-row').forEach(function(row){
          row.addEventListener('click', function(){
            filterByCropCard(row.getAttribute('data-crop'));
            document.getElementById('zoneStatsPanel').style.display = 'none';
          });
        });
      }

      function filterByCropCard(cropKey){
        controlState.crop = cropKey;
        DiseaseMapKit.setControlPanelCropUI(cropKey);
        syncResolveByCropButton();
        loadReports(true);
      }

      function syncResolveByCropButton(){
        document.getElementById('btnResolveByCrop').disabled = !controlState.crop;
      }

      function loadReports(fitBounds){
        const crop = controlState.crop;
        const url = window.DISEASE_MAP_DATA_URL + (crop ? ('?crop=' + encodeURIComponent(crop)) : '');
        fetch(url).then(r => r.json()).then(data => {
          gmarkers.forEach(m => m.setMap(null));
          gmarkers = [];
          if(clusterer){ clusterer.setMap(null); clusterer = null; }
          if(heatmapLayer){ heatmapLayer.setMap(null); heatmapLayer = null; }
          reportsById = {};
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
              content: DiseaseMapKit.buildPopupHtml(r, { showResolveButton: true, onResolveAttr: 'resolveReportFromPopup' }),
            });
            marker.addListener('click', () => info.open(gmap, marker));
            gmarkers.push(marker);
          });

          // Gom cụm marker (thư viện chính thức Google) - nhiều điểm gần
          // nhau/chồng lấp sẽ gom thành 1 bong bóng số đếm, tự tách ra khi
          // zoom vào, thay vì hiện chồng chất lên nhau như trước.
          if(gmarkers.length){
            clusterer = new markerClusterer.MarkerClusterer({
              map: showMarkers ? gmap : null,
              markers: gmarkers,
            });
          }

          // Bấm 1 dòng trong popup "Thống kê vùng dịch" -> zoom bản đồ đến
          // đúng các điểm của cây đó (chỉ áp dụng khi có yêu cầu fitBounds),
          // đồng thời hiện bảng + biểu đồ theo bệnh CỦA ĐÚNG cây đó.
          if(fitBounds){
            if(reports.length){
              const bounds = new google.maps.LatLngBounds();
              reports.forEach(r => bounds.extend({ lat: parseFloat(r.lat), lng: parseFloat(r.lng) }));
              gmap.fitBounds(bounds, 60);
            }
            renderDiseaseStatsForCrop(crop, reports);
          }

          // Lớp mật độ vẽ bằng deck.gl (xem createHeatmap() trong disease-map.js).
          // Bọc try/catch riêng để lỗi tạo heatmap (ví dụ deck.gl chưa nạp
          // được) hiện rõ trong console, không bị .catch() ngoài nuốt im lặng
          // khiến bấm nút "Lớp mật độ" không thấy gì xảy ra.
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

      // Bảng + biểu đồ cột số điểm theo TÊN BỆNH CỦA ĐÚNG 1 CÂY vừa bấm trong
      // popup "Thống kê vùng dịch" - KHÔNG hiện sẵn khi tải trang, chỉ hiện
      // khi có người bấm (xem lời gọi renderDiseaseStatsForCrop() trong
      // loadReports() khi fitBounds=true). Tính trực tiếp từ "reports" đã
      // lọc theo cây (không cần gọi thêm API). Giữ 1 instance Chart.js dùng
      // lại (destroy() trước khi vẽ lại) để đổi cây nhiều lần không bị lỗi
      // "Canvas is already in use".
      let diseaseChart = null;
      function renderDiseaseStatsForCrop(cropKey, reports){
        const meta = (DiseaseMapKit.CROP_META && DiseaseMapKit.CROP_META[cropKey]) || { label: cropKey };
        document.getElementById('diseaseStatsCropLabel').textContent = meta.label;

        const counts = {};
        reports.forEach(function(r){ counts[r.disease_name] = (counts[r.disease_name] || 0) + 1; });
        const sorted = Object.keys(counts)
          .map(function(name){ return { name: name, total: counts[name] }; })
          .sort(function(a, b){ return b.total - a.total; });

        const emptyEl = document.getElementById('diseaseStatsEmpty');
        const tableWrap = document.getElementById('diseaseStatsTableWrap');
        const tbody = document.getElementById('diseaseStatsTableBody');
        if(sorted.length === 0){
          emptyEl.style.display = '';
          tableWrap.style.display = 'none';
        } else {
          emptyEl.style.display = 'none';
          tableWrap.style.display = '';
          tbody.innerHTML = sorted.map(function(d){
            return '<tr class="border-t" style="border-color:#f0f2ed">' +
              '<td class="py-2 pr-2" style="color:#12341d">' + d.name + '</td>' +
              '<td class="py-2 text-right font-bold" style="color:var(--danger)">' + d.total + '</td>' +
            '</tr>';
          }).join('');
        }

        if(diseaseChart){ diseaseChart.destroy(); diseaseChart = null; }
        const canvas = document.getElementById('chartByDisease');
        if(canvas && typeof Chart !== 'undefined' && sorted.length){
          diseaseChart = new Chart(canvas, {
            type: 'bar',
            data: {
              labels: sorted.map(function(d){ return d.name; }),
              datasets: [{
                label: 'Số điểm',
                data: sorted.map(function(d){ return d.total; }),
                backgroundColor: '#c1440e',
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

        document.getElementById('diseaseStatsSection').style.display = '';
      }

      function fillText(id, boxId, val){
        const el = document.getElementById(id);
        el.textContent = val || '';
        if(boxId) document.getElementById(boxId).style.display = val ? '' : 'none';
      }

      function openReportDetail(id){
        const r = reportsById[id];
        if(!r) return;
        currentDetailId = id;
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
        currentDetailId = null;
      }

      function postResolve(id){
        return fetch(window.ADMIN_RESOLVE_URL_BASE + '/' + id + '/xu-ly', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': window.ADMIN_CSRF, 'Accept': 'application/json' },
        });
      }

      // Bấm "Đã xử lý" ngay trong popup marker (không cần mở modal chi tiết).
      function resolveReportFromPopup(id){
        if(!confirm('Đánh dấu report này đã xử lý? Report sẽ biến mất khỏi bản đồ.')) return;
        postResolve(id).then(() => loadReports());
      }

      // Bấm "Đã xử lý" trong modal chi tiết.
      function resolveCurrentReport(){
        if(currentDetailId === null) return;
        if(!confirm('Đánh dấu report này đã xử lý? Report sẽ biến mất khỏi bản đồ.')) return;
        postResolve(currentDetailId).then(() => {
          closeReportDetail();
          loadReports();
          window.location.reload(); // đồng bộ lại số thẻ thống kê phía trên
        });
      }

      // Đánh dấu TOÀN BỘ report đang hoạt động của cây đang lọc là đã xử lý.
      function resolveByCrop(){
        const crop = controlState.crop;
        if(!crop) return;
        if(!confirm('Đánh dấu TOÀN BỘ điểm đang hoạt động của cây này là đã xử lý? Không thể hoàn tác qua bản đồ (vẫn còn lưu record).')) return;
        fetch(window.ADMIN_RESOLVE_BY_CROP_URL, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': window.ADMIN_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ crop: crop }),
        }).then(() => window.location.reload());
      }
    </script>
    {{-- Không cần &libraries=visualization nữa - lớp heatmap giờ vẽ bằng
         deck.gl (Google đã gỡ google.maps.visualization.HeatmapLayer). --}}
    @if (config('services.google_maps.key'))
      <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initDiseaseMap" async defer></script>
    @endif
  @endpush
@endsection
