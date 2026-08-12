<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI nhận diện bệnh cây</title>
<link rel="icon" href="{{ asset('image/logo.jpg') }}" type="image/jpeg">
<link rel="apple-touch-icon" href="{{ asset('image/logo.jpg') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{margin:0;font-family:'Be Vietnam Pro',system-ui,sans-serif;background:#fff;color:#1c231d;min-height:100vh;display:flex;flex-direction:column;}
  a{color:#1f6d3c;text-decoration:none;}
  .no-scrollbar::-webkit-scrollbar{display:none;}
  .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
  [x-cloak]{display:none!important;}
</style>
{{-- Đường dẫn ảnh sinh bởi Blade asset(), truyền vào cho agri-app.js (file JS tĩnh
     không tự gọi được asset()/route()/csrf_token() của Laravel) --}}
<script>
  window.AGRI_ASSETS = {
    crops: {
      che: '{{ asset("image/crop-che.png") }}',
      lua: '{{ asset("image/crop-lua.png") }}',
      ngo: '{{ asset("image/crop-ngo.png") }}',
      san: '{{ asset("image/crop-san.png") }}',
      cachua: '{{ asset("image/crop-cachua.png") }}',
      ot: '{{ asset("image/crop-ot.jpg") }}',
      xoai: '{{ asset("image/crop-xoai.jpg") }}',
    }
  };
  // Đăng nhập/lưu report/bản đồ dịch bệnh (xem openSaveModal()/submitSaveReport()
  // trong agri-app.js + save-report-modal.blade.php)
  window.AGRI_CSRF = '{{ csrf_token() }}';
  window.AGRI_USER = @json(auth()->check() ? ['name' => auth()->user()->name, 'phone' => auth()->user()->phone] : null);
  window.AGRI_ROUTES = {
    saveReport: '{{ route('agri.reports.store') }}',
    auth: '{{ route('agri.auth') }}',
    history: '{{ route('agri.reports.history') }}',
    logout: '{{ route('agri.auth.logout') }}',
    diseaseMap: '{{ route('agri.disease-map') }}',
  };
  window.GOOGLE_MAPS_KEY_PRESENT = {{ config('services.google_maps.key') ? 'true' : 'false' }};
</script>
@if (config('services.google_maps.key'))
  <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}" async defer></script>
@endif
</head>
<body x-data="agriApp()" x-effect="selectedCrop, symptomPage, modalStep, dropzoneModalOpen, pendingFiles.length, confirmedPhotos.length, diagnosing, diagnosed; $nextTick(() => refreshIcons())">

  <!-- ================= TOPBAR MOBILE: chỉ hiện < md (banner ảnh + menu
       account đã ẩn ở mobile), thay bằng logo nhỏ + nút hamburger mở drawer. ================= -->
  <div class="md:hidden flex items-center justify-between px-5 py-2.5 border-b shrink-0" style="border-color:#eceae3">
    <img src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
    <button @click="mobileDrawerOpen = true" type="button" class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#f2f7ee;color:#1f6d3c">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>
  </div>

  <!-- ================= DRAWER MOBILE: trượt từ bên phải, chứa nút đăng nhập
       (guest) hoặc 3 tab điều hướng + đăng xuất (đã đăng nhập). ================= -->
  <div x-show="mobileDrawerOpen" x-cloak class="md:hidden fixed inset-0 z-50" style="background:rgba(18,52,29,.45)" @click.self="mobileDrawerOpen = false">
    <div x-show="mobileDrawerOpen" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute top-0 right-0 h-full w-72 max-w-[82%] bg-white shadow-xl p-5 flex flex-col">
      <div class="flex items-center justify-between mb-5">
        <img src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow">
        <button @click="mobileDrawerOpen = false" type="button" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e"><i data-lucide="x" class="w-4 h-4"></i></button>
      </div>

      @guest
        <a href="{{ route('agri.auth') }}" class="text-center px-4 py-3 rounded-lg text-white font-semibold text-[14px]" style="background:#1f6d3c">Đăng nhập</a>
      @endguest

      @auth
        <p class="text-[13px] font-semibold mb-2 px-1" style="color:#12341d">Xin chào, {{ auth()->user()->name }}</p>
        <a href="{{ route('agri.index') }}" class="flex items-center gap-2.5 px-3 py-3 rounded-lg text-[14px] font-semibold" style="{{ request()->routeIs('agri.index') ? 'background:#e2efd9;color:#1f6d3c' : 'color:#4a5245' }}">
          <i data-lucide="stethoscope" class="w-4 h-4"></i> Chuẩn đoán bệnh
        </a>
        <a href="{{ route('agri.disease-map') }}" class="flex items-center gap-2.5 px-3 py-3 rounded-lg text-[14px] font-semibold" style="{{ request()->routeIs('agri.disease-map') ? 'background:#e2efd9;color:#1f6d3c' : 'color:#4a5245' }}">
          <i data-lucide="map" class="w-4 h-4"></i> Bản đồ dịch bệnh
        </a>
        <a href="{{ route('agri.reports.history') }}" class="flex items-center gap-2.5 px-3 py-3 rounded-lg text-[14px] font-semibold" style="{{ request()->routeIs('agri.reports.history') ? 'background:#e2efd9;color:#1f6d3c' : 'color:#4a5245' }}">
          <i data-lucide="history" class="w-4 h-4"></i> Lịch sử của tôi
        </a>
        <form method="POST" action="{{ route('agri.auth.logout') }}" class="mt-3">
          @csrf
          <button class="w-full text-center px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #c1440e;color:#c1440e">Đăng xuất</button>
        </form>
      @endauth
    </div>
  </div>

  <x-agri.header />

  <!-- Thanh tab điều hướng dưới banner - chỉ desktop (mobile dùng drawer ở
       trên), chỉ hiện khi đã đăng nhập (xem nav-tabs.blade.php). -->
  <div class="hidden md:block">
    <x-agri.nav-tabs />
  </div>

  <!-- ================= MAIN: 2 panel chụp ảnh / hướng dẫn+kết quả =================
       Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) -->
  <div class="flex-1 px-5 md:px-7 2xl:px-10 pt-2 md:pt-2.5 2xl:pt-3 pb-6 md:pb-7 2xl:pb-9">
    <div class="flex flex-col md:flex-row gap-6 md:gap-0 items-stretch">

      <!-- CỘT TRÁI: chọn mô hình (box riêng) + panel chụp ảnh, xếp dọc, cùng 1 cột -->
      <div class="md:flex-1 md:mr-4 2xl:mr-6 flex flex-col gap-4 2xl:gap-5">
        <x-agri.crop-selector />
        <x-agri.photo-panel />
      </div>

      <!-- CONNECTOR ARROW (desktop) -->
      <div class="hidden md:flex items-center justify-center relative" style="width:0">
        <div class="w-8 h-8 2xl:w-9 2xl:h-9 rounded-full flex items-center justify-center text-white shadow-md shrink-0" style="background:linear-gradient(135deg,#1f6d3c,#c1440e);position:absolute;left:-16px"><i data-lucide="arrow-right" class="w-4 h-4"></i></div>
      </div>

      <x-agri.guide-result-panel />
    </div>
  </div>

  <x-agri.footer />

  <x-agri.photo-modal />
  <x-agri.disease-detail-modal />
  <x-agri.save-report-modal />

  <script src="{{ asset('js/agri-app.js') }}"></script>
</body>
</html>
