{{-- ================= MODAL: chi tiết 1 bệnh KHÁC trong danh sách "Các bệnh khác
     có thể gặp" (ngoài bệnh chính đã hiện sẵn ngoài guide-result-panel) =================
     Mở qua openOtherDisease(d) trong agri-app.js, đóng bằng nút X hoặc bấm ra ngoài. --}}
<div x-show="otherDiseaseModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(18,52,29,.45)" @click.self="otherDiseaseModalOpen=false">
  <div class="bg-white rounded-2xl w-full max-w-md 2xl:max-w-lg p-6 2xl:p-7 relative shadow-xl max-h-[85vh] overflow-y-auto">
    <button @click="otherDiseaseModalOpen=false" class="absolute top-3.5 right-3.5 w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e"><i data-lucide="x" class="w-4 h-4"></i></button>

    <template x-if="selectedOtherDisease">
      <div>
        <div class="flex items-center gap-2 flex-wrap pr-8">
          <p class="text-[19px] 2xl:text-[21px] font-bold" style="color:#c1440e" x-text="selectedOtherDisease.disease"></p>
          <span x-show="selectedOtherDisease.probability !== null && selectedOtherDisease.probability !== undefined" class="text-[13px] 2xl:text-[14px] font-bold px-2 py-0.5 rounded-full shrink-0" style="background:#f6e2d1;color:#c1440e" x-text="selectedOtherDisease.probability + '%'"></span>
        </div>

        <p x-show="selectedOtherDisease.level" class="text-[12.5px] 2xl:text-[13.5px] mt-1.5" style="color:#4a5245"><span class="font-semibold">Mức độ:</span> <span x-text="selectedOtherDisease.level"></span></p>

        <p x-show="selectedOtherDisease.pathogen" class="text-[12.5px] 2xl:text-[13.5px] mt-1.5" style="color:#4a5245"><span class="font-semibold">Tác nhân gây bệnh:</span> <span x-text="selectedOtherDisease.pathogen"></span></p>

        <div x-show="selectedOtherDisease.signsInPhoto" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
          <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Dấu hiệu quan sát được trong ảnh này:</p>
          <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="selectedOtherDisease.signsInPhoto"></p>
        </div>

        <div x-show="selectedOtherDisease.symptomsText" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
          <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Dấu hiệu nhận biết chung:</p>
          <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="selectedOtherDisease.symptomsText"></p>
        </div>

        <div x-show="selectedOtherDisease.treatment" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
          <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Cách chữa trị:</p>
          <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="selectedOtherDisease.treatment"></p>
        </div>

        <div x-show="selectedOtherDisease.prevention" class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
          <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Cách phòng ngừa, phòng tránh:</p>
          <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="selectedOtherDisease.prevention"></p>
        </div>
      </div>
    </template>
  </div>
</div>
