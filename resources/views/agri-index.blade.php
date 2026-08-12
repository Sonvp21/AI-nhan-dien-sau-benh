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
    logout: '{{ route('agri.auth.logout') }}',
    diseaseMap: '{{ route('agri.disease-map') }}',
  };
  window.GOOGLE_MAPS_KEY_PRESENT = {{ config('services.google_maps.key') ? 'true' : 'false' }};
</script>
@if (config('services.google_maps.key'))
  <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&loading=async" async defer></script>
@endif
</head>
<body x-data="agriApp()"
      x-effect:scroll-lock="document.body.style.overflow = (saveModalOpen || dropzoneModalOpen || otherDiseaseModalOpen || mobileDrawerOpen) ? 'hidden' : ''"
      x-effect:icons="selectedCrop, symptomPage, modalStep, dropzoneModalOpen, pendingFiles.length, confirmedPhotos.length, diagnosing, diagnosed; $nextTick(() => refreshIcons())">

  <x-agri.mobile-nav />

  <x-agri.header />

  <!-- Thanh tab điều hướng dưới banner - chỉ desktop (mobile dùng drawer ở
       trên), hiện luôn kể cả chưa đăng nhập (xem nav-tabs.blade.php). -->
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

  {{-- ?v=filemtime: chống trình duyệt/CDN cache JS cũ - mỗi lần sửa
       agri-app.js, mtime đổi -> URL đổi -> trình duyệt buộc tải bản mới,
       không cần người dùng tự xoá cache/hard-refresh sau mỗi lần deploy. --}}
  <script src="{{ asset('js/agri-app.js') }}?v={{ file_exists(public_path('js/agri-app.js')) ? filemtime(public_path('js/agri-app.js')) : time() }}"></script>
</body>
</html>
