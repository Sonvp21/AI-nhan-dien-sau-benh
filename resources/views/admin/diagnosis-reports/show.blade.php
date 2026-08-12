@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo')

@section('content')
  <a href="{{ route('admin.diagnosis-reports.index') }}" class="text-sm font-semibold" style="color:var(--forest)">← Danh sách báo cáo</a>

  <div class="bg-white rounded-xl border p-6 mt-4 max-w-2xl">
    <img src="{{ $report->imageUrl() }}" alt="Ảnh chẩn đoán" class="w-full max-h-96 object-contain rounded-lg bg-gray-50">

    <div class="mt-4 flex items-center gap-2 flex-wrap">
      <h2 class="font-bold text-xl" style="color:#12341d">{{ $report->disease_name }}</h2>
      @if(!is_null($report->probability))
        <span class="text-sm font-bold px-2 py-0.5 rounded-full" style="background:#f6e2d1;color:var(--danger)">{{ $report->probability }}%</span>
      @endif
      @if($report->status === 'pending')
        <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#fff7ed;color:var(--soil)">Chờ duyệt</span>
      @elseif($report->status === 'verified')
        <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#e2efd9;color:var(--forest)">Đã duyệt</span>
      @else
        <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#fbe3dc;color:var(--danger)">Từ chối</span>
      @endif
    </div>

    <p class="text-sm text-gray-500 mt-1">Cây: {{ $report->crop_label ?? $report->crop }} · Mức độ: {{ $report->level ?? '—' }}</p>
    <p class="text-sm text-gray-500">Người gửi: {{ $report->user->name ?? '—' }} ({{ $report->user->phone ?? '—' }})</p>
    <p class="text-sm text-gray-500">Vị trí: {{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}
      · <a class="underline" style="color:var(--forest)" target="_blank" href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}">Xem trên Google Maps</a></p>
    <p class="text-sm text-gray-500">Gửi lúc: {{ $report->created_at->format('d/m/Y H:i') }}</p>

    @if($report->pathogen)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:#12341d">Tác nhân gây bệnh</p><p class="text-sm text-gray-600 mt-1">{{ $report->pathogen }}</p></div>
    @endif
    @if($report->signs_in_photo)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:#12341d">Dấu hiệu quan sát được trong ảnh</p><p class="text-sm text-gray-600 mt-1">{{ $report->signs_in_photo }}</p></div>
    @endif
    @if($report->symptoms)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:#12341d">Dấu hiệu nhận biết chung</p><p class="text-sm text-gray-600 mt-1">{{ $report->symptoms }}</p></div>
    @endif
    @if($report->treatment)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:#12341d">Cách chữa trị</p><p class="text-sm text-gray-600 mt-1">{{ $report->treatment }}</p></div>
    @endif
    @if($report->prevention)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:#12341d">Cách phòng ngừa</p><p class="text-sm text-gray-600 mt-1">{{ $report->prevention }}</p></div>
    @endif

    @if($report->status === 'rejected' && $report->rejection_reason)
      <div class="mt-4 pt-4 border-t"><p class="font-semibold text-sm" style="color:var(--danger)">Lý do từ chối</p><p class="text-sm text-gray-600 mt-1">{{ $report->rejection_reason }}</p></div>
    @endif

    <div class="mt-6 pt-4 border-t flex gap-3">
      @if($report->status !== 'verified')
        <form method="POST" action="{{ route('admin.diagnosis-reports.approve', $report) }}">
          @csrf
          <button class="text-sm font-semibold text-white px-4 py-2 rounded-lg" style="background:var(--forest)">Duyệt, hiện lên bản đồ</button>
        </form>
      @endif
      @if($report->status !== 'rejected')
        <form method="POST" action="{{ route('admin.diagnosis-reports.reject', $report) }}" onsubmit="return confirm('Từ chối report này?')" class="flex items-center gap-2">
          @csrf
          <input type="text" name="rejection_reason" placeholder="Lý do từ chối (tùy chọn)" class="text-sm border rounded-lg px-3 py-2">
          <button class="text-sm font-semibold px-4 py-2 rounded-lg border" style="color:var(--danger);border-color:var(--danger)">Từ chối</button>
        </form>
      @endif
    </div>
  </div>
@endsection
