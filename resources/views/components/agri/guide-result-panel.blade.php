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
    <p class="text-[13px] 2xl:text-[14px] font-semibold mb-1" style="color:#6b7268">Thông tin bệnh:</p>
    <p class="text-[21px] md:text-[22px] 2xl:text-[25px] font-bold" style="color:#c1440e" x-text="info.disease"></p>
    <div class="flex flex-wrap items-center gap-2.5 mt-2">
      <span class="text-[13px] 2xl:text-[14px]" style="color:#4a5245"><span x-text="info.nameEn"></span> · Độ tin cậy <span x-text="info.confidence"></span></span>
      <span class="text-[11px] 2xl:text-[12px] font-semibold text-white px-2.5 py-1 rounded-full"
            :style="`background:${info.level==='Nặng' ? '#c1440e' : info.level==='Nhẹ' ? '#1f6d3c' : '#c9762c'}`" x-text="info.level"></span>
      <template x-if="info.isLive">
        <span class="text-[10.5px] 2xl:text-[11.5px] font-semibold px-2 py-0.5 rounded-full" style="background:#e2efd9;color:#1f6d3c">🧠 Kết quả AI thật</span>
      </template>
      <template x-if="!info.isLive">
        <span class="text-[10.5px] 2xl:text-[11.5px] font-semibold px-2 py-0.5 rounded-full" style="background:#eee;color:#8a8f83">📋 Dữ liệu mẫu</span>
      </template>
    </div>

    <template x-if="info.pathogen">
      <p class="text-[12.5px] 2xl:text-[13.5px] mt-2.5" style="color:#4a5245"><span class="font-semibold">Tác nhân gây bệnh:</span> <span x-text="info.pathogen"></span></p>
    </template>
    <template x-if="info.conditions">
      <p class="text-[12.5px] 2xl:text-[13.5px] mt-1" style="color:#4a5245"><span class="font-semibold">Điều kiện phát sinh:</span> <span x-text="info.conditions"></span></p>
    </template>

    <template x-if="info.isLive && info.lowConfidence && info.top3 && info.top3.length">
      <div class="mt-4 pt-3" style="border-top:1px dashed #f0d9c4">
        <p class="text-[11.5px] 2xl:text-[12.5px] font-semibold mb-2" style="color:#c9762c">⚠️ Độ tin cậy thấp (AI cân nhắc giữa nhiều khả năng)</p>
        <div class="flex flex-col gap-1.5">
          <template x-for="t in info.top3" :key="t.nameEn">
            <div class="flex items-center justify-between text-[12px] 2xl:text-[13px]">
              <span x-text="t.disease" :style="t.nameEn===info.nameEn ? 'color:#1f6d3c;font-weight:600' : 'color:#6b7268'"></span>
              <span x-text="t.confidence" :style="t.nameEn===info.nameEn ? 'color:#1f6d3c;font-weight:600' : 'color:#8a8f83'"></span>
            </div>
          </template>
        </div>
      </div>
    </template>

    <div class="mt-5 pt-4" style="border-top:1px solid #f0d9c4">
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
