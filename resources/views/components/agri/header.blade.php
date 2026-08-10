{{-- ================= HEADER: banner full, chữ đè bên trái =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) --}}
<header class="relative overflow-hidden border-b shrink-0" style="border-color:#eceae3">
  <img src="{{ asset('image/header.jpg') }}" alt="Banner phòng lab / cây trồng" class="w-full h-auto block">
  <div class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/55 to-transparent"></div>
  <div class="absolute inset-0 flex items-center gap-4 md:gap-5 2xl:gap-7 px-5 md:px-7 2xl:px-10">
    <!-- Bấm liên tiếp 5 lần vào logo để bật/tắt chế độ thuyết trình (ẩn, chỉ người thuyết trình biết) -->
    <div class="relative shrink-0">
      <img @click="handleLogoClick()" src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-14 h-14 md:w-20 md:h-20 2xl:w-28 2xl:h-28 rounded-full object-cover border-2 md:border-[3px] border-white shadow cursor-pointer select-none">
      <span x-show="presenterMode" x-cloak class="absolute -top-0.5 -right-0.5 w-3 h-3 md:w-3.5 md:h-3.5 rounded-full border-2 border-white" style="background:#1f6d3c"></span>
    </div>
    <div class="leading-snug md:leading-normal min-w-0">
      <p class="font-bold text-[14px] md:text-[19px] 2xl:text-[24px]" style="color:#12341d">TRƯỜNG ĐẠI HỌC NÔNG LÂM</p>
      <p class="font-bold text-[14px] md:text-[19px] 2xl:text-[24px]" style="color:#1f6d3c">TRUNG TÂM NGHIÊN CỨU ĐỊA TIN HỌC</p>
      <p class="text-[12px] md:text-[15px] 2xl:text-[18px] mt-0.5 md:mt-1.5 2xl:mt-2" style="color:#6b7268">Địa chỉ: Phường Quyết Thắng, tỉnh Thái Nguyên</p>
      <p class="text-[12px] md:text-[15px] 2xl:text-[18px]" style="color:#6b7268">Điện thoại: 0904 031 103</p>
      <p class="text-[12px] md:text-[15px] 2xl:text-[18px]" style="color:#6b7268">Https://girc.edu.vn</p>
    </div>
  </div>
</header>
