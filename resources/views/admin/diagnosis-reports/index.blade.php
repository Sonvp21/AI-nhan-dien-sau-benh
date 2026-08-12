@extends('layouts.admin')

@section('title', 'Duyệt báo cáo chẩn đoán')

@section('content')
  <h1 class="text-xl font-bold mb-5" style="color:#12341d">Duyệt báo cáo chẩn đoán</h1>

  <form method="GET" class="flex flex-wrap gap-3 mb-5 bg-white p-4 rounded-xl border">
    <select name="status" class="text-sm border rounded-lg px-3 py-2">
      <option value="pending" @selected($status=='pending')>Chờ duyệt</option>
      <option value="verified" @selected($status=='verified')>Đã duyệt</option>
      <option value="rejected" @selected($status=='rejected')>Đã từ chối</option>
      <option value="all" @selected($status=='all')>Tất cả</option>
    </select>
    <select name="crop" class="text-sm border rounded-lg px-3 py-2">
      <option value="">-- Tất cả cây --</option>
      @foreach($crops as $key => $label)
        <option value="{{ $key }}" @selected(request('crop')==$key)>{{ $label }}</option>
      @endforeach
    </select>
    <button class="text-sm font-semibold text-white px-4 py-2 rounded-lg" style="background:var(--forest)">Lọc</button>
  </form>

  <div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-left text-gray-600">
        <tr>
          <th class="px-4 py-3">Ảnh</th>
          <th class="px-4 py-3">Bệnh</th>
          <th class="px-4 py-3">Cây</th>
          <th class="px-4 py-3">Người gửi</th>
          <th class="px-4 py-3">Vị trí</th>
          <th class="px-4 py-3">Ngày gửi</th>
          <th class="px-4 py-3">Trạng thái</th>
          <th class="px-4 py-3 text-right">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($reports as $r)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <a href="{{ route('admin.diagnosis-reports.show', $r) }}">
                <img src="{{ $r->imageUrl() }}" alt="Ảnh chẩn đoán" class="w-14 h-14 rounded-lg object-cover">
              </a>
            </td>
            <td class="px-4 py-3">
              <a href="{{ route('admin.diagnosis-reports.show', $r) }}" class="font-medium hover:underline">{{ $r->disease_name }}</a>
              @if(!is_null($r->probability))
                <span class="text-xs text-gray-500">({{ $r->probability }}%)</span>
              @endif
            </td>
            <td class="px-4 py-3">{{ $crops[$r->crop] ?? $r->crop_label ?? $r->crop }}</td>
            <td class="px-4 py-3">{{ $r->senderDisplayName() }}<br><span class="text-xs text-gray-400">{{ $r->user->phone ?? '' }}</span></td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ number_format($r->latitude, 5) }}, {{ number_format($r->longitude, 5) }}</td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
            <td class="px-4 py-3">
              @if($r->status === 'pending')
                <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#fff7ed;color:var(--soil)">Chờ duyệt</span>
              @elseif($r->status === 'verified')
                <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#e2efd9;color:var(--forest)">Đã duyệt</span>
              @else
                <span class="text-xs font-semibold px-2 py-1 rounded-full" style="background:#fbe3dc;color:var(--danger)">Từ chối</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
              @if($r->status !== 'verified')
                <form method="POST" action="{{ route('admin.diagnosis-reports.approve', $r) }}" class="inline">
                  @csrf
                  <button class="text-xs font-semibold" style="color:var(--forest)">Duyệt</button>
                </form>
              @endif
              @if($r->status !== 'rejected')
                <form method="POST" action="{{ route('admin.diagnosis-reports.reject', $r) }}" class="inline" onsubmit="return confirm('Từ chối report này?')">
                  @csrf
                  <button class="text-xs font-semibold" style="color:var(--danger)">Từ chối</button>
                </form>
              @endif
              <form method="POST" action="{{ route('admin.diagnosis-reports.destroy', $r) }}" class="inline" onsubmit="return confirm('Xóa hẳn yêu cầu này? Không thể khôi phục lại.')">
                @csrf
                @method('DELETE')
                <button class="text-xs font-semibold text-gray-400 hover:text-gray-600">Xóa</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Không có báo cáo nào khớp bộ lọc</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $reports->links() }}</div>
@endsection
