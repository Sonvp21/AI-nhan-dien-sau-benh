@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
  <p class="text-sm text-gray-500 mb-6">Xin chào, {{ auth()->user()->name }}. Tổng quan hệ thống chẩn đoán bệnh cây trồng.</p>

  {{-- ================= THẺ THỐNG KÊ TỔNG QUAN BÁO CÁO ================= --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
    <div class="bg-white rounded-xl border p-4">
      <p class="text-[11.5px] font-semibold text-gray-500">Tổng báo cáo</p>
      <p class="text-2xl font-bold mt-1" style="color:#12341d">{{ number_format($totalReports) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-[11.5px] font-semibold text-gray-500">Đã duyệt</p>
      <p class="text-2xl font-bold mt-1" style="color:var(--forest)">{{ number_format($approvedCount) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-[11.5px] font-semibold text-gray-500">Chờ duyệt</p>
      <p class="text-2xl font-bold mt-1" style="color:var(--soil)">{{ number_format($pendingCount) }}</p>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="text-[11.5px] font-semibold text-gray-500">Đã hủy</p>
      <p class="text-2xl font-bold mt-1" style="color:var(--danger)">{{ number_format($rejectedCount) }}</p>
    </div>
  </div>

  {{-- ================= 2 BIỂU ĐỒ CỘT ================= --}}
  <div class="grid lg:grid-cols-2 gap-3.5 mb-6">
    <div class="bg-white rounded-xl border p-4">
      <p class="font-bold text-[14px] mb-3" style="color:#12341d">Báo cáo theo loại cây</p>
      <p class="text-[11.5px] text-gray-400 mb-3">Toàn bộ báo cáo đã gửi (mọi trạng thái), phân theo loại cây.</p>
      <div style="height:140px"><canvas id="chartReportsByCrop"></canvas></div>
    </div>
    <div class="bg-white rounded-xl border p-4">
      <p class="font-bold text-[14px] mb-3" style="color:#12341d">Vùng dịch đang hoạt động theo loại cây</p>
      <p class="text-[11.5px] text-gray-400 mb-3">Điểm đã duyệt và chưa đánh dấu xử lý - đang hiện trên bản đồ công khai.</p>
      <div style="height:140px"><canvas id="chartZonesByCrop"></canvas></div>
    </div>
  </div>

  {{-- ================= PREVIEW BẢN ĐỒ VÙNG DỊCH ================= --}}
  <div class="bg-white rounded-xl border overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
      <div>
        <p class="font-bold text-[14px]" style="color:#12341d">Preview bản đồ vùng dịch</p>
        <p class="text-[11.5px] text-gray-400 mt-0.5">Chỉ xem - vào Dashboard vùng dịch để lọc theo cây, xem chi tiết hoặc đánh dấu đã xử lý.</p>
      </div>
      <a href="{{ route('admin.vung-dich.index') }}" class="shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[12.5px] font-semibold text-white transition hover:opacity-90" style="background:var(--forest)">
        <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i> Xem đầy đủ
      </a>
    </div>
    <div id="previewMap" style="width:100%;height:300px;background:#e2efd9"></div>
    @if (!config('services.google_maps.key'))
      <p class="text-sm text-gray-500 p-4">Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên bản đồ không thể hiển thị.</p>
    @endif
  </div>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    {{-- Gom cụm marker khi nhiều điểm gần nhau/chồng lấp trên preview. --}}
    <script src="https://unpkg.com/@googlemaps/markerclusterer@2.6.2/dist/index.min.js"></script>
    <script src="{{ asset('js/disease-map.js') }}?v={{ file_exists(public_path('js/disease-map.js')) ? filemtime(public_path('js/disease-map.js')) : time() }}"></script>
    <script>
      const cropLabels = @json($cropLabels);
      const reportsByCrop = @json($reportsByCrop);
      const zonesByCrop = @json($zonesByCrop);

      new Chart(document.getElementById('chartReportsByCrop'), {
        type: 'bar',
        data: {
          labels: cropLabels,
          datasets: [{ data: reportsByCrop, backgroundColor: '#1f6d3c', borderRadius: 5, maxBarThickness: 28 }],
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10.5 } } }, x: { ticks: { font: { size: 10.5 } } } },
        },
      });

      new Chart(document.getElementById('chartZonesByCrop'), {
        type: 'bar',
        data: {
          labels: cropLabels,
          datasets: [{ data: zonesByCrop, backgroundColor: '#c1440e', borderRadius: 5, maxBarThickness: 28 }],
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10.5 } } }, x: { ticks: { font: { size: 10.5 } } } },
        },
      });

      // Preview bản đồ vùng dịch: chỉ marker (không heatmap/control panel/
      // legend - dashboard chi tiết đã có ở /admin/vung-dich), chỉ để nhìn
      // nhanh phân bố các điểm đang hoạt động ngay tại dashboard chính.
      window.DISEASE_MAP_DATA_URL = @json(route('agri.disease-map.data'));

      // Preview này không có modal "xem chi tiết" đầy đủ (chỉ để nhìn nhanh
      // phân bố điểm) - bấm "Xem chi tiết" trong popup sẽ đưa thẳng sang
      // dashboard vùng dịch đầy đủ.
      function previewOpenDetail(){
        window.location.href = @json(route('admin.vung-dich.index'));
      }

      function initPreviewMap(){
        const map = new google.maps.Map(document.getElementById('previewMap'), {
          center: { lat: 21.5944, lng: 105.8480 },
          zoom: 8,
          gestureHandling: 'greedy',
          disableDefaultUI: true,
        });
        DiseaseMapKit.preloadCropImages().then(function(){
          fetch(window.DISEASE_MAP_DATA_URL).then(r => r.json()).then(data => {
            const markers = (data.reports || []).map(r => {
              const marker = new google.maps.Marker({
                position: { lat: parseFloat(r.lat), lng: parseFloat(r.lng) },
                title: r.disease_name + ' (' + r.crop_label + ')',
                icon: DiseaseMapKit.markerIcon(r.crop, r.level),
              });
              const info = new google.maps.InfoWindow({ content: DiseaseMapKit.buildPopupHtml(r, { detailAttr: 'previewOpenDetail' }) });
              marker.addListener('click', () => info.open(map, marker));
              return marker;
            });
            // Gom cụm cho gọn - preview này không có nút bật/tắt lớp điểm nên
            // clusterer luôn gắn thẳng vào map.
            if(markers.length) new markerClusterer.MarkerClusterer({ map: map, markers: markers });
          }).catch(() => {});
        });
      }
    </script>
    @if (config('services.google_maps.key'))
      <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&loading=async&callback=initPreviewMap" async defer></script>
    @endif
  @endpush
@endsection
