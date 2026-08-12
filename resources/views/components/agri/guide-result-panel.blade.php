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

    <!-- % tổng quát khả năng cây đang có bệnh/sâu hại (Gemini tự ước lượng qua ảnh,
         chỉ mang tính tham khảo trực quan, không phải số liệu ML chính xác) -->
    <template x-if="!info.cropMismatch && info.isLive && info.diseaseProbability !== null && info.diseaseProbability !== undefined">
      <div class="mb-3 px-3 py-2 rounded-lg flex items-center justify-between" style="background:#fff7ed;border:1px dashed #f0d9c4">
        <span class="text-[12.5px] 2xl:text-[13.5px] font-semibold" style="color:#4a5245">Khả năng cây đang có bệnh/sâu hại:</span>
        <span class="text-[16px] 2xl:text-[17px] font-bold" :style="info.diseaseProbability >= 50 ? 'color:#c1440e' : 'color:#1f6d3c'" x-text="info.diseaseProbability + '%'"></span>
      </div>
    </template>

    <!-- Ket luan CHINH: luon la 1 ten benh duy nhat (benh co % cao nhat neu la AI
         thật, hoặc dữ liệu mẫu). Các bệnh khác cùng phát hiện được (nếu có) không
         liệt kê chung ở đây nữa, mà đưa xuống list "Các bệnh khác" bên dưới để
         bấm vào xem chi tiết riêng, tránh loãng kết luận chính. -->
    <template x-if="!info.cropMismatch">
      <div class="flex items-center gap-2 flex-wrap">
        <p class="text-[21px] md:text-[22px] 2xl:text-[25px] font-bold" style="color:#c1440e" x-text="(info.isLive && info.detections && info.detections.length) ? info.detections[0].disease : info.disease"></p>
        <span x-show="info.isLive && info.detections && info.detections.length && info.detections[0].probability !== null && info.detections[0].probability !== undefined" class="text-[13px] 2xl:text-[14px] font-bold px-2 py-0.5 rounded-full shrink-0" style="background:#f6e2d1;color:#c1440e" x-text="info.detections[0].probability + '%'"></span>
      </div>
    </template>

    <!-- Nút "Lưu": hiện ngay sau khi chẩn đoán xong (trừ khi ảnh không khớp cây
         đã chọn), mở modal lưu report chờ admin duyệt - xem openSaveModal()
         trong agri-app.js + save-report-modal.blade.php -->
    <template x-if="!info.cropMismatch">
      <button type="button" @click="openSaveModal()" class="mt-3 px-4 py-2 rounded-lg text-[13px] font-semibold flex items-center gap-2 transition hover:opacity-85" style="background:#fff;border:1px solid #1f6d3c;color:#1f6d3c">
        <i data-lucide="map-pin" class="w-4 h-4"></i> Lưu kết quả lên bản đồ
      </button>
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

    <!-- Dau hieu QUAN SAT DUOC trong chinh anh vua chup (khac voi "dau hieu nhan
         biet chung" ben duoi) - day la phan giai thich VI SAO AI ket luan nhu vay
         dua tren buc anh cu the nay, khong phai mo ta chung tu sach vo. -->
    <template x-if="!info.cropMismatch && info.signsInPhoto">
      <div class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Dấu hiệu quan sát được trong ảnh này:</p>
        <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="info.signsInPhoto"></p>
      </div>
    </template>

    <!-- 3 mục chi tiết còn lại do Gemini trả về: dấu hiệu nhận biết chung, cách
         chữa trị, cách phòng ngừa - mỗi mục 1 đoạn văn riêng (thay cho "steps" gộp cũ) -->
    <template x-if="!info.cropMismatch && info.symptomsText">
      <div class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Dấu hiệu nhận biết chung:</p>
        <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="info.symptomsText"></p>
      </div>
    </template>

    <template x-if="!info.cropMismatch && info.treatment">
      <div class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Cách chữa trị:</p>
        <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="info.treatment"></p>
      </div>
    </template>

    <template x-if="!info.cropMismatch && info.prevention">
      <div class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[14px] 2xl:text-[15px] font-bold mb-1.5" style="color:#12341d">Cách phòng ngừa, phòng tránh:</p>
        <p class="text-[13.5px] 2xl:text-[14.5px] leading-relaxed" style="color:#4a5245" x-text="info.prevention"></p>
      </div>
    </template>

    <!-- Cac benh KHAC cung phat hien duoc tren cung anh (ngoai benh chinh o tren) -
         chi hien ten + %, bam vao moi mo modal xem day du dau hieu/cach chua/phong
         ngua rieng cho benh do (xem disease-detail-modal.blade.php), tranh loang
         phan ket luan chinh ngay tren. -->
    <template x-if="!info.cropMismatch && info.isLive && info.detections && info.detections.length > 1">
      <div class="mt-4 pt-4" style="border-top:1px solid #f0d9c4">
        <p class="text-[14px] 2xl:text-[15px] font-bold mb-2.5" style="color:#12341d">Các bệnh khác có thể gặp:</p>
        <div class="flex flex-col gap-2">
          <template x-for="(d, i) in info.detections.slice(1)" :key="i">
            <button type="button" @click="openOtherDisease(d)" class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-left transition hover:opacity-80" style="background:#fff;border:1px solid #f0d9c4">
              <span class="text-[13.5px] 2xl:text-[14.5px] font-semibold" style="color:#12341d" x-text="d.disease"></span>
              <span class="flex items-center gap-1.5 shrink-0">
                <span x-show="d.probability !== null && d.probability !== undefined" class="text-[12px] 2xl:text-[13px] font-bold" style="color:#c9762c" x-text="d.probability + '%'"></span>
                <i data-lucide="chevron-right" class="w-4 h-4" style="color:#c9762c"></i>
              </span>
            </button>
          </template>
        </div>
      </div>
    </template>

    <!-- Du lieu mau (demo, chua co Gemini) van dung dang "steps" cu -->
    <div x-show="!info.cropMismatch && !info.isLive && info.steps && info.steps.length" class="mt-5 pt-4" style="border-top:1px solid #f0d9c4">
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
