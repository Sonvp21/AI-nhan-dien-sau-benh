{{-- ================= RIGHT PANEL: hướng dẫn (trước khi chẩn đoán) / kết quả (sau khi chẩn đoán) =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) --}}
<div class="md:flex-1 rounded-xl p-5 md:p-6 2xl:p-7" style="background:#fbf1ea;border:1px solid #f0d9c4;border-left:4px solid #c1440e">

  <!-- HƯỚNG DẪN CHỤP ẢNH: chỉ hiện khi chưa có kết quả, mỗi bước là 1 card ảnh -->
  <div x-show="!diagnosed" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <p class="text-[19px] md:text-[20px] 2xl:text-[22px] font-bold mb-4" style="color:#12341d">Hướng dẫn chụp ảnh:</p>
    <div class="flex flex-col gap-3">
      <div class="flex items-center gap-3">
        <div class="w-24 h-24 md:w-28 md:h-28 2xl:w-32 2xl:h-32 rounded-lg overflow-hidden shrink-0">
          <img src="{{ asset('image/guide-1.jpg') }}" alt="Chụp cận cảnh vùng lá, thân nghi bệnh" class="w-full h-full object-cover">
        </div>
        <p class="flex-1 text-[15px] md:text-[16.5px] 2xl:text-[18px] font-bold leading-snug" style="color:#12341d">Chụp cận cảnh vùng lá, thân nghi có dấu hiệu bệnh</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-24 h-24 md:w-28 md:h-28 2xl:w-32 2xl:h-32 rounded-lg overflow-hidden shrink-0">
          <img src="{{ asset('image/guide-2.jpg') }}" alt="Chụp nơi đủ sáng, tránh ngược sáng" class="w-full h-full object-cover">
        </div>
        <p class="flex-1 text-[15px] md:text-[16.5px] 2xl:text-[18px] font-bold leading-snug" style="color:#12341d">Chụp nơi đủ ánh sáng tự nhiên, tránh chụp ngược sáng</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-24 h-24 md:w-28 md:h-28 2xl:w-32 2xl:h-32 rounded-lg overflow-hidden shrink-0">
          <img src="{{ asset('image/guide-3.jpg') }}" alt="Giữ máy ổn định, tránh mờ, rung" class="w-full h-full object-cover">
        </div>
        <p class="flex-1 text-[15px] md:text-[16.5px] 2xl:text-[18px] font-bold leading-snug" style="color:#12341d">Giữ máy ổn định để ảnh rõ nét, không bị mờ, rung</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-24 h-24 md:w-28 md:h-28 2xl:w-32 2xl:h-32 rounded-lg overflow-hidden shrink-0">
          <img src="{{ asset('image/guide-4.jpg') }}" alt="Chụp thêm nhiều góc độ" class="w-full h-full object-cover">
        </div>
        <p class="flex-1 text-[15px] md:text-[16.5px] 2xl:text-[18px] font-bold leading-snug" style="color:#12341d">Chụp thêm nhiều góc độ nếu vùng bệnh lan rộng</p>
      </div>
    </div>
  </div>

  <!-- KẾT QUẢ: chỉ hiện sau khi chẩn đoán xong -->
  <div x-show="diagnosed" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">

    <!-- Ảnh KHÔNG khớp với cây đã chọn (Gemini tự nhận diện sai cây) - chặn hẳn,
         không hiện chẩn đoán benh, chỉ báo ảnh giống cây gì hơn -->
    <template x-if="info.cropMismatch">
      <div>
        <p class="text-[21px] md:text-[22px] 2xl:text-[25px] font-bold" style="color:#c1440e">Ảnh không khớp với cây đã chọn</p>
        <p class="text-[13px] 2xl:text-[14px] mt-2.5 px-3 py-2 rounded-lg" style="background:#fff7ed;color:#c9762c;border:1px dashed #f0d9c4">
          Ảnh này trông giống
          <span class="font-semibold" x-text="info.detectedCrop || 'một loại cây khác'"></span>
          hơn là cây bạn đã chọn. Vui lòng chọn lại đúng loại cây hoặc chụp ảnh khác.
        </p>
      </div>
    </template>

    <!-- AI thật, có phát hiện bệnh: title to = "Chẩn đoán được các bệnh sau" -->
    <template x-if="!info.cropMismatch && info.isLive && info.detections && info.detections.length">
      <div>
        <p class="text-[21px] md:text-[22px] 2xl:text-[25px] font-bold" style="color:#12341d">Chẩn đoán được các bệnh sau:</p>
        <ul class="mt-2.5 flex flex-col gap-1.5">
          <template x-for="(d, i) in info.detections" :key="i">
            <li class="text-[15px] 2xl:text-[16px] font-semibold flex items-center gap-1.5" style="color:#c1440e">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:#c1440e"></span>
              <span x-text="d.disease"></span>
            </li>
          </template>
        </ul>
      </div>
    </template>

    <!-- Dữ liệu mẫu (demo) hoặc AI thật nhưng không có detections: giữ title bằng tên bệnh -->
    <template x-if="!info.cropMismatch && !(info.isLive && info.detections && info.detections.length)">
      <p class="text-[21px] md:text-[22px] 2xl:text-[25px] font-bold" style="color:#c1440e" x-text="info.disease"></p>
    </template>

    <!-- Ảnh AI thật nhưng KHÔNG tìm thấy vùng bệnh nào (khác với "cây khỏe mạnh") -->
    <template x-if="!info.cropMismatch && info.isLive && info.found === false">
      <p class="text-[12.5px] 2xl:text-[13.5px] mt-2.5 px-3 py-2 rounded-lg" style="background:#fff7ed;color:#c9762c;border:1px dashed #f0d9c4">
        AI không phát hiện được vùng bất thường rõ ràng nào trong ảnh này. Có thể ảnh chưa đủ nét/đủ sáng, hoặc cây đang khỏe mạnh.
      </p>
    </template>

    <template x-if="info.pathogen">
      <p class="text-[12.5px] 2xl:text-[13.5px] mt-2.5" style="color:#4a5245"><span class="font-semibold">Tác nhân gây bệnh:</span> <span x-text="info.pathogen"></span></p>
    </template>
    <template x-if="info.conditions">
      <p class="text-[12.5px] 2xl:text-[13.5px] mt-1" style="color:#4a5245"><span class="font-semibold">Điều kiện phát sinh:</span> <span x-text="info.conditions"></span></p>
    </template>

    <div x-show="!info.cropMismatch" class="mt-5 pt-4" style="border-top:1px solid #f0d9c4">
      <p class="text-[14px] 2xl:text-[15px] font-bold mb-3" style="color:#12341d">Cách phòng trừ, khắc phục:</p>
      <div class="flex flex-col gap-3">
        <template x-for="(step, i) in info.steps" :key="i">
          <div class="flex gap-2.5">
            <div class="w-5 h-5 2xl:w-6 2xl:h-6 rounded-full text-white text-[11px] 2xl:text-[12px] font-bold flex items-center justify-center shrink-0" style="background:#c1440e" x-text="i+1"></div>
            <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" x-text="step"></p>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>
