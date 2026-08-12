@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
  <p class="text-sm text-gray-500 mb-6">Xin chào, {{ auth()->user()->name }}. Chọn 1 khu vực quản trị bên dưới.</p>

  <div class="grid md:grid-cols-2 gap-5 max-w-3xl">
    <a href="{{ route('admin.diagnosis-reports.index') }}" class="block bg-white rounded-xl border p-6 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="font-bold text-lg" style="color:#12341d">Duyệt báo cáo chẩn đoán</p>
        @if($pendingCount > 0)
          <span class="text-xs font-bold text-white px-2.5 py-1 rounded-full" style="background:var(--soil)">{{ $pendingCount }} chờ duyệt</span>
        @endif
      </div>
      <p class="text-sm text-gray-500 mt-2">Kiểm duyệt các report người dùng lưu lại trước khi hiện lên bản đồ dịch bệnh công khai.</p>
    </a>

    <a href="{{ route('admin.diseases.index') }}" class="block bg-white rounded-xl border p-6 hover:shadow-md transition">
      <p class="font-bold text-lg" style="color:#12341d">Quản lý danh mục bệnh</p>
      <p class="text-sm text-gray-500 mt-2">{{ $diseaseCount }} bệnh trong danh mục. Thêm/sửa/xóa thông tin bệnh dùng cho dữ liệu mẫu.</p>
    </a>
  </div>
@endsection
