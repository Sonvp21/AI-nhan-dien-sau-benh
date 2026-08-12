<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin') — AgriAI</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{font-family:ui-sans-serif,system-ui,sans-serif;background:var(--mist);}
</style>
</head>
<body class="min-h-screen flex">

{{-- ================= ASIDE: sidebar quản trị dùng chung cho mọi trang admin.
     Dưới cùng có thông tin người đang đăng nhập + nút xem lại trang web +
     nút đăng xuất (chỉ icon). ================= --}}
<aside class="w-60 shrink-0 bg-white border-r flex flex-col h-screen sticky top-0">
  <div class="px-5 py-5 border-b">
    <p class="font-bold text-lg" style="color:var(--forest)">🌾 Admin AgriAI</p>
  </div>

  <nav class="flex-1 px-3 py-4 space-y-1">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13.5px] font-semibold transition"
       style="{{ request()->routeIs('admin.dashboard') ? 'background:#e2efd9;color:var(--forest)' : 'color:#4a5245' }}">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="{{ route('admin.diagnosis-reports.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13.5px] font-semibold transition"
       style="{{ request()->routeIs('admin.diagnosis-reports.*') ? 'background:#e2efd9;color:var(--forest)' : 'color:#4a5245' }}">
      <i data-lucide="clipboard-check" class="w-4 h-4"></i> Duyệt báo cáo
    </a>
    <a href="{{ route('admin.vung-dich.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13.5px] font-semibold transition"
       style="{{ request()->routeIs('admin.vung-dich.*') ? 'background:#e2efd9;color:var(--forest)' : 'color:#4a5245' }}">
      <i data-lucide="flame" class="w-4 h-4"></i> Vùng dịch
    </a>
    <a href="{{ route('admin.diseases.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13.5px] font-semibold transition"
       style="{{ request()->routeIs('admin.diseases.*') ? 'background:#e2efd9;color:var(--forest)' : 'color:#4a5245' }}">
      <i data-lucide="bug" class="w-4 h-4"></i> Quản lý bệnh
    </a>
  </nav>

  <div class="px-4 py-4 border-t">
    <div class="flex items-center gap-2.5 mb-3">
      <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-white font-semibold text-[13px]" style="background:var(--forest)">
        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-[13px] font-semibold truncate" style="color:#12341d">{{ auth()->user()->name }}</p>
        <p class="text-[11.5px] text-gray-400 truncate">{{ auth()->user()->phone }}</p>
      </div>
      <form method="POST" action="{{ route('agri.auth.logout') }}" class="shrink-0">
        @csrf
        <button type="submit" title="Đăng xuất" class="w-8 h-8 flex items-center justify-center rounded-full" style="color:var(--danger)">
          <i data-lucide="log-out" class="w-4 h-4"></i>
        </button>
      </form>
    </div>
    <a href="{{ route('agri.index') }}" class="flex items-center justify-center gap-2 w-full text-[12.5px] font-semibold px-3 py-2.5 rounded-lg border transition hover:bg-gray-50" style="color:var(--forest);border-color:#dbe8d2">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Xem trang web
    </a>
  </div>
</aside>

<div class="flex-1 min-w-0">
  @if (session('success'))
    <div class="mx-6 mt-6 px-4 py-3 rounded-lg text-sm" style="background:#e2efd9;color:var(--forest)">{{ session('success') }}</div>
  @endif

  <main class="p-6">
    @yield('content')
  </main>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){ if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

{{-- Chỗ cho các trang con chèn thêm script riêng (vd Google Maps JS API ở
     admin/diagnosis-reports/show.blade.php) qua @push('scripts'). --}}
@stack('scripts')
</body>
</html>
