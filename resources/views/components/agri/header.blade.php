{{-- ================= HEADER: banner full, chữ đè bên trái =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:). Banner này CHỈ hiện
     ở HD/FHD - mobile ẩn hẳn, thay bằng topbar gọn + drawer riêng (xem
     agri-index.blade.php). --}}
<header class="hidden md:block relative overflow-hidden border-b shrink-0" style="border-color:#eceae3">
  <img src="{{ asset('image/banner.jpg') }}" alt="Banner phòng lab / cây trồng" class="w-full h-28 md:h-44 2xl:h-60 object-cover">
  <div class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/55 to-transparent"></div>
  <div class="absolute inset-0 flex items-center gap-4 md:gap-5 2xl:gap-7 px-5 md:px-7 2xl:px-10">
    <div class="flex items-center gap-4 md:gap-5 2xl:gap-7 min-w-0">
      <!-- Bấm liên tiếp 5 lần vào logo để bật/tắt chế độ thuyết trình (ẩn, chỉ người thuyết trình biết) -->
      <div class="relative shrink-0">
        <img @click="handleLogoClick()" src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-16 h-16 md:w-24 md:h-24 2xl:w-32 2xl:h-32 rounded-full object-cover border-2 md:border-[3px] border-white shadow cursor-pointer select-none">
        <span x-show="presenterMode" x-cloak class="absolute -top-0.5 -right-0.5 w-3 h-3 md:w-3.5 md:h-3.5 rounded-full border-2 border-white" style="background:#1f6d3c"></span>
      </div>
      <div class="h-16 md:h-24 2xl:h-32 flex flex-col justify-center leading-snug md:leading-normal min-w-0">
        <p class="font-bold text-[12px] md:text-[15px] 2xl:text-[18px]" style="color:#12341d">TRƯỜNG ĐẠI HỌC NÔNG LÂM</p>
        <p class="font-bold text-[12px] md:text-[15px] 2xl:text-[18px]" style="color:#1f6d3c">TRUNG TÂM NGHIÊN CỨU ĐỊA TIN HỌC</p>
        <p class="text-[10px] md:text-[12px] 2xl:text-[14px] mt-0.5 md:mt-1.5 2xl:mt-2" style="color:#6b7268">Địa chỉ: Phường Quyết Thắng, tỉnh Thái Nguyên</p>
        <p class="text-[10px] md:text-[12px] 2xl:text-[14px]" style="color:#6b7268">Điện thoại: 0904 031 103</p>
        <p class="text-[10px] md:text-[12px] 2xl:text-[14px]" style="color:#6b7268">Https://girc.edu.vn</p>
      </div>
    </div>
  </div>

  <!-- Đăng nhập / tên người dùng + đăng xuất - đặt góc dưới phải banner, có
       nền riêng để nổi rõ trên ảnh banner. Chỉ hiện ở HD/FHD (banner này đã
       ẩn hoàn toàn ở mobile). -->
  <div class="absolute bottom-3 right-4 md:bottom-4 md:right-6 2xl:bottom-5 2xl:right-8 text-center rounded-xl px-4 py-2.5 shadow-md" style="background:rgba(255,255,255,.9)">
    @guest
      <a href="{{ route('agri.auth') }}" class="inline-block text-[12px] md:text-[13px] 2xl:text-[14px] font-semibold text-white px-4 py-2 rounded-full" style="background:#1f6d3c">Đăng nhập</a>
    @endguest
    @auth
      <div class="flex items-center gap-2">
        <p class="text-[12px] md:text-[13px] 2xl:text-[14px] font-semibold whitespace-nowrap" style="color:#12341d">Xin chào, {{ auth()->user()->name }}</p>
        <span class="text-[12px]" style="color:#c7cec0">|</span>
        <form method="POST" action="{{ route('agri.auth.logout') }}">
          @csrf
          <button type="submit" title="Đăng xuất" class="w-6 h-6 flex items-center justify-center rounded-full shrink-0" style="color:#c1440e">
            <i data-lucide="log-out" class="w-4 h-4"></i>
          </button>
        </form>
      </div>
    @endauth
  </div>
</header>
