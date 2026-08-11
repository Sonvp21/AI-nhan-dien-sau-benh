{{-- ================= LEFT PANEL: chụp ảnh, chẩn đoán, triệu chứng khác =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) --}}
<div class="rounded-xl p-5 md:p-6 2xl:p-7" style="background:#f2f7ee;border:1px solid #dbe8d2;border-left:4px solid #1f6d3c">
  <p class="text-[19px] md:text-[20px] 2xl:text-[22px] font-bold mb-4" style="color:#12341d">Chụp ảnh của cây trồng</p>

  <!-- DROPZONE -->
  <div @click="openDropzoneModal()" class="w-full h-56 md:h-64 2xl:h-72 rounded-xl flex items-center justify-center text-center px-6 cursor-pointer relative overflow-hidden transition hover:opacity-90" style="border:2px dashed #1f6d3c">
    <template x-if="!confirmedPhotos.length">
      <div class="flex flex-col items-center gap-2.5">
        <i data-lucide="camera" class="w-9 h-9 2xl:w-10 2xl:h-10" style="color:#1f6d3c"></i>
        <span class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#1f6d3c">Chụp hoặc tải ảnh lá / cây để chẩn đoán</span>
      </div>
    </template>
    <template x-if="confirmedPhotos.length">
      <div class="absolute inset-0" style="background:#e2efd9">
        <img :src="confirmedPhotos[0].url" class="w-full h-full object-contain">
        <span x-show="confirmedPhotos.length>1" class="absolute bottom-2 right-2 text-[11px] 2xl:text-[12px] font-semibold text-white px-2.5 py-1 rounded-full" style="background:rgba(18,52,29,.75)" x-text="'+' + (confirmedPhotos.length-1) + ' ảnh'"></span>
        <span class="absolute inset-0 flex items-center justify-center gap-1.5 text-[12px] 2xl:text-[13px] font-semibold text-white opacity-0 hover:opacity-100 transition" style="background:rgba(18,52,29,.45)">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i> Đổi ảnh khác
        </span>
      </div>
    </template>

    <!-- overlay animation lúc đang chẩn đoán -->
    <div x-show="diagnosing" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 flex flex-col items-center justify-center gap-3" style="background:rgba(255,255,255,.92)">
      <i data-lucide="loader-circle" class="w-9 h-9 2xl:w-10 2xl:h-10 animate-spin" style="color:#1f6d3c"></i>
      <span class="text-[13px] 2xl:text-[14px] font-semibold" style="color:#1f6d3c">Đang phân tích ảnh bằng AI...</span>
    </div>
  </div>

  <div class="flex gap-3 mt-4">
    <button @click="runDiagnosis()" :disabled="diagnosing" class="flex-1 px-5 py-2.5 2xl:px-6 2xl:py-3 rounded-lg text-[13.5px] 2xl:text-[14.5px] font-semibold text-white flex items-center justify-center gap-2 transition" :style="`background:#1f6d3c;box-shadow:0 2px 6px rgba(31,109,60,.3);opacity:${diagnosing ? .7 : 1}`">
      <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="diagnosing"></i>
      <span x-text="diagnosing ? 'Đang chẩn đoán...' : 'Chuẩn đoán bệnh'"></span>
    </button>
    <button x-show="diagnosed" x-cloak @click="resetDiagnosis()" class="shrink-0 px-5 py-2.5 2xl:px-6 2xl:py-3 rounded-lg text-[13.5px] 2xl:text-[14.5px] font-semibold flex items-center gap-2 transition hover:opacity-85" style="border:1px solid #c1440e;color:#c1440e">
      <i data-lucide="refresh-cw" class="w-4 h-4"></i> Chẩn đoán ảnh khác
    </button>
  </div>

  <!-- TRIỆU CHỨNG KHÁC: chỉ hiện sau khi có kết quả. Với dữ liệu mẫu (demo) là
       icon lá + caption tĩnh; với AI thật là ảnh Gemini THỰC SỰ tìm được trên web
       lúc nó tự tra cứu (thử nghiệm, xem symptomPages getter + gemini_diagnosis.py
       - có thể không có ảnh nào, khi đó cả khối này tự ẩn vì symptomPages rỗng). -->
  <div x-show="diagnosed && symptomPages.length" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="mt-6">
    <p class="text-[13px] 2xl:text-[14px] font-semibold" style="color:#4a5245" x-text="info.isLive ? 'Ảnh tham khảo tìm được trên web:' : 'Triệu chứng khác thường gặp:'"></p>
    <div class="flex items-center gap-3 mt-3">
      <button @click="prevSymptomPage()" class="w-6 h-6 2xl:w-7 2xl:h-7 rounded-full border flex items-center justify-center shrink-0" style="border-color:#ddd8cb;color:#8a8f83"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i></button>

      <div class="flex gap-3 flex-1">
        <template x-for="(s, i) in symptomPages[symptomPage]" :key="i">
          <div class="flex-1 text-center">
            <div class="w-full h-28 md:h-32 2xl:h-36 rounded-lg overflow-hidden flex items-center justify-center" style="background:linear-gradient(135deg,#e2efd9,#d6dccd)">
              <img x-show="s.url" :src="s.url" class="w-full h-full object-cover" loading="lazy">
              <i x-show="!s.url" data-lucide="leaf" class="w-8 h-8 md:w-9 md:h-9 2xl:w-10 2xl:h-10" style="color:#1f6d3c"></i>
            </div>
            <p class="text-[11px] md:text-[11.5px] 2xl:text-[12.5px] mt-1.5 leading-tight" style="color:#6b7268" x-text="s.caption"></p>
          </div>
        </template>
      </div>

      <button @click="nextSymptomPage()" class="w-6 h-6 2xl:w-7 2xl:h-7 rounded-full border flex items-center justify-center shrink-0" style="border-color:#ddd8cb;color:#8a8f83"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>
    </div>
    <div class="flex justify-center gap-1.5 mt-3">
      <template x-for="(p, i) in symptomPages" :key="i">
        <button @click="symptomPage=i" class="w-1.5 h-1.5 rounded-full transition" :style="symptomPage===i ? 'background:#1f6d3c' : 'background:#d6dccd'"></button>
      </template>
    </div>
  </div>
</div>
