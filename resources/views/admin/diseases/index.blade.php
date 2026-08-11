<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý Bệnh — Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{font-family:ui-sans-serif,system-ui,sans-serif;background:var(--mist);}
</style>
</head>
<body class="min-h-screen">

<header class="bg-white border-b px-6 py-4 flex items-center justify-between">
  <h1 class="font-bold text-lg" style="color:var(--forest)">🌾 Admin AgriAI — Quản lý Bệnh</h1>
  <a href="{{ route('admin.diseases.create') }}" class="text-sm font-semibold text-white px-4 py-2 rounded-lg" style="background:var(--forest)">+ Thêm bệnh mới</a>
</header>

<main class="max-w-6xl mx-auto px-6 py-6">

  @if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background:#e2efd9;color:var(--forest)">{{ session('success') }}</div>
  @endif

  <form method="GET" class="flex flex-wrap gap-3 mb-5 bg-white p-4 rounded-xl border">
    <select name="crop_key" class="text-sm border rounded-lg px-3 py-2">
      <option value="">-- Tất cả cây --</option>
      @foreach($crops as $key => $label)
        <option value="{{ $key }}" @selected(request('crop_key')==$key)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="level" class="text-sm border rounded-lg px-3 py-2">
      <option value="">-- Mọi mức độ --</option>
      <option value="Nhẹ" @selected(request('level')=='Nhẹ')>Nhẹ</option>
      <option value="Trung bình" @selected(request('level')=='Trung bình')>Trung bình</option>
      <option value="Nặng" @selected(request('level')=='Nặng')>Nặng</option>
    </select>
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên bệnh..." class="text-sm border rounded-lg px-3 py-2 flex-1 min-w-[160px]">
    <button class="text-sm font-semibold text-white px-4 py-2 rounded-lg" style="background:var(--forest)">Lọc</button>
    @if(request()->anyFilled(['crop_key','level','q']))
      <a href="{{ route('admin.diseases.index') }}" class="text-sm px-4 py-2 rounded-lg border">Xóa lọc</a>
    @endif
  </form>

  <div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-left text-gray-600">
        <tr>
          <th class="px-4 py-3">Cây</th>
          <th class="px-4 py-3">Tên bệnh</th>
          <th class="px-4 py-3">Class key</th>
          <th class="px-4 py-3">Mức độ</th>
          <th class="px-4 py-3">Nguồn</th>
          <th class="px-4 py-3 text-right">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($diseases as $d)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ $crops[$d->crop_key] ?? $d->crop_key }}</td>
            <td class="px-4 py-3">{{ $d->name_vi }}</td>
            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $d->class_key }}</td>
            <td class="px-4 py-3">
              <span class="text-xs font-semibold text-white px-2 py-1 rounded-full"
                style="background:{{ $d->level=='Nặng' ? 'var(--danger)' : ($d->level=='Nhẹ' ? 'var(--forest)' : 'var(--soil)') }}">
                {{ $d->level }}
              </span>
            </td>
            <td class="px-4 py-3">
              @if($d->info_source=='ai_bien_soan')
                <span class="text-xs text-amber-700 bg-amber-50 px-2 py-1 rounded-full">⚠ AI biên soạn</span>
              @else
                <span class="text-xs text-gray-500">Tài liệu chuyên ngành</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <a href="{{ route('admin.diseases.edit', $d) }}" class="text-xs font-semibold" style="color:var(--forest)">Sửa</a>
              <form method="POST" action="{{ route('admin.diseases.destroy', $d) }}" class="inline" onsubmit="return confirm('Xóa bệnh này? Không thể hoàn tác.')">
                @csrf @method('DELETE')
                <button class="text-xs font-semibold" style="color:var(--danger)">Xóa</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Không có bệnh nào khớp bộ lọc</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $diseases->links() }}</div>
</main>
</body>
</html>
