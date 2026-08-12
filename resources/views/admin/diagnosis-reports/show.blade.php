@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo')

@section('content')
  <a href="{{ route('admin.diagnosis-reports.index') }}" class="text-sm font-semibold" style="color:var(--forest)">← Danh sách báo cáo</a>

  <div class="mt-3 flex items-center gap-2 flex-wrap">
    <h1 class="text-2xl font-bold" style="color:#12341d">{{ $report->disease_name }}</h1>
    @if(!is_null($report->probability))
      <span class="text-sm font-bold px-2.5 py-1 rounded-full" style="background:#f6e2d1;color:var(--danger)">{{ $report->probability }}%</span>
    @endif
    @if($report->status === 'pending')
      <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#fff7ed;color:var(--soil)">Chờ duyệt</span>
    @elseif($report->status === 'verified')
      <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#e2efd9;color:var(--forest)">Đã duyệt</span>
    @else
      <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#fbe3dc;color:var(--danger)">Từ chối</span>
    @endif
  </div>
  <p class="text-sm text-gray-500 mt-1">Gửi lúc {{ $report->created_at->format('d/m/Y H:i') }} bởi {{ $report->user->name ?? '—' }}</p>

  <div class="grid lg:grid-cols-2 gap-6 mt-6">

    {{-- ================= CỘT TRÁI: ảnh + bản đồ vị trí ================= --}}
    <div class="space-y-5">
      <div class="bg-white rounded-xl border p-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Ảnh chẩn đoán</p>
        <img src="{{ $report->imageUrl() }}" alt="Ảnh chẩn đoán" class="w-full max-h-[440px] object-contain rounded-lg bg-gray-50">
      </div>

      <div class="bg-white rounded-xl border p-4">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Vị trí phát hiện</p>
          <a class="text-xs font-semibold" style="color:var(--forest)" target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}">Mở Google Maps ↗</a>
        </div>
        @if (config('services.google_maps.key'))
          <div id="reportMap" class="w-full h-64 rounded-lg" style="background:#e2efd9"></div>
        @else
          <div class="w-full h-64 rounded-lg flex items-center justify-center text-center px-4" style="background:#e2efd9">
            <p class="text-xs text-gray-500">Chưa cấu hình GOOGLE_MAPS_API_KEY trong .env nên không hiển thị được bản đồ.</p>
          </div>
        @endif
        <p class="text-xs text-gray-400 mt-2 font-mono">{{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}</p>
      </div>
    </div>

    {{-- ================= CỘT PHẢI: thông tin, chi tiết bệnh, thao tác duyệt ================= --}}
    <div class="space-y-5">
      <div class="bg-white rounded-xl border p-5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Thông tin chung</p>
        <dl class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
          <div><dt class="text-gray-400 text-xs">Cây trồng</dt><dd class="font-medium mt-0.5">{{ $report->crop_label ?? $report->crop }}</dd></div>
          <div><dt class="text-gray-400 text-xs">Mức độ</dt><dd class="font-medium mt-0.5">{{ $report->level ?? '—' }}</dd></div>
          <div><dt class="text-gray-400 text-xs">Khả năng có bệnh</dt><dd class="font-medium mt-0.5">{{ !is_null($report->disease_probability) ? $report->disease_probability.'%' : '—' }}</dd></div>
          <div><dt class="text-gray-400 text-xs">% bệnh này</dt><dd class="font-medium mt-0.5">{{ !is_null($report->probability) ? $report->probability.'%' : '—' }}</dd></div>
          <div><dt class="text-gray-400 text-xs">Người gửi</dt><dd class="font-medium mt-0.5">{{ $report->user->name ?? '—' }}</dd></div>
          <div><dt class="text-gray-400 text-xs">Số điện thoại</dt><dd class="font-medium mt-0.5">{{ $report->user->phone ?? '—' }}</dd></div>
        </dl>
      </div>

      @if($report->pathogen)
        <div class="bg-white rounded-xl border p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Tác nhân gây bệnh</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->pathogen }}</p>
        </div>
      @endif

      @if($report->signs_in_photo)
        <div class="bg-white rounded-xl border p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Dấu hiệu quan sát được trong ảnh</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->signs_in_photo }}</p>
        </div>
      @endif

      @if($report->symptoms)
        <div class="bg-white rounded-xl border p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Dấu hiệu nhận biết chung</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->symptoms }}</p>
        </div>
      @endif

      @if($report->treatment)
        <div class="bg-white rounded-xl border p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Cách chữa trị</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->treatment }}</p>
        </div>
      @endif

      @if($report->prevention)
        <div class="bg-white rounded-xl border p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Cách phòng ngừa</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->prevention }}</p>
        </div>
      @endif

      @if($report->status === 'rejected' && $report->rejection_reason)
        <div class="bg-white rounded-xl border p-5" style="border-color:#f0c9c9">
          <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:var(--danger)">Lý do từ chối</p>
          <p class="text-sm text-gray-700 leading-relaxed">{{ $report->rejection_reason }}</p>
        </div>
      @endif

      <div class="bg-white rounded-xl border p-5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Thao tác kiểm duyệt</p>
        <div class="flex flex-wrap gap-3">
          @if($report->status !== 'verified')
            <form method="POST" action="{{ route('admin.diagnosis-reports.approve', $report) }}">
              @csrf
              <button class="text-sm font-semibold text-white px-4 py-2.5 rounded-lg" style="background:var(--forest)">Duyệt, hiện lên bản đồ</button>
            </form>
          @endif
          @if($report->status !== 'rejected')
            <form method="POST" action="{{ route('admin.diagnosis-reports.reject', $report) }}" onsubmit="return confirm('Từ chối report này?')" class="flex items-center gap-2">
              @csrf
              <input type="text" name="rejection_reason" placeholder="Lý do từ chối (tùy chọn)" class="text-sm border rounded-lg px-3 py-2">
              <button class="text-sm font-semibold px-4 py-2.5 rounded-lg border" style="color:var(--danger);border-color:var(--danger)">Từ chối</button>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if (config('services.google_maps.key'))
    @push('scripts')
      <script>
        function initReportMap(){
          var pos = { lat: {{ (float) $report->latitude }}, lng: {{ (float) $report->longitude }} };
          var map = new google.maps.Map(document.getElementById('reportMap'), { center: pos, zoom: 15 });
          new google.maps.Marker({ position: pos, map: map, title: {{ Js::from($report->disease_name) }} });
        }
      </script>
      <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initReportMap" async defer></script>
    @endpush
  @endif
@endsection
