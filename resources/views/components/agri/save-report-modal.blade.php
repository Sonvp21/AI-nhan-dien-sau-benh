{{-- ================= MODAL: Lưu kết quả chẩn đoán =================
     Mở qua openSaveModal() trong agri-app.js ngay sau khi có kết quả (nút "Lưu
     kết quả" ở guide-result-panel.blade.php). KHÔNG yêu cầu đăng nhập - chỉ cần
     nhập tên (saveSenderName) là gửi được. Thông tin bệnh tự điền từ
     info/liveResult, ảnh lấy từ confirmedPhotos[0], vị trí lấy từ GPS (nút
     "Dùng vị trí hiện tại") hoặc tự kéo/bấm vào bản đồ Google Maps nhỏ trong
     modal (xem initSaveMap() trong agri-app.js). Submit xong report ở trạng
     thái "pending", chỉ lên bản đồ công khai sau khi admin duyệt (xem
     DiagnosisReportController + Admin\DiagnosisReportController). --}}
<div x-show="saveModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(18,52,29,.5)" @click.self="closeSaveModal()">
  <div class="bg-white rounded-2xl w-full max-w-lg 2xl:max-w-xl relative shadow-2xl max-h-[92vh] overflow-y-auto">

    <!-- Header: nền gradient, icon tròn, dễ nhận biết ngay là bước cuối cùng -->
    <div class="px-6 2xl:px-7 pt-6 2xl:pt-7 pb-5 rounded-t-2xl" style="background:linear-gradient(135deg,#1f6d3c,#164f2b)">
      <button @click="closeSaveModal()" class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition hover:bg-white/10" style="color:#fff"><i data-lucide="x" class="w-4 h-4"></i></button>
      <div class="flex items-center gap-3 pr-8">
        <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:rgba(255,255,255,.15);color:#fff"><i data-lucide="map-pin-plus" class="w-5 h-5"></i></span>
        <div>
          <p class="text-[18px] 2xl:text-[19px] font-bold text-white">Lưu kết quả chẩn đoán</p>
          <p class="text-[12px] 2xl:text-[12.5px] mt-0.5" style="color:#c9e6d3">Admin sẽ kiểm duyệt trước khi hiện lên bản đồ</p>
        </div>
      </div>
    </div>

    <div class="p-6 2xl:p-7">
      <!-- Ảnh + thông tin bệnh (tự điền, chỉ đọc) -->
      <div class="flex gap-3 p-3 rounded-xl" style="background:#f2f7ee;border:1px solid #dbe8d2">
        <img x-show="confirmedPhotos.length" :src="confirmedPhotos[0] && confirmedPhotos[0].url" class="w-16 h-16 rounded-lg object-cover shrink-0" style="background:#fff">
        <div class="min-w-0 flex flex-col justify-center">
          <p class="text-[15px] font-bold leading-snug" style="color:#c1440e" x-text="saveDiseaseName"></p>
          <p class="text-[12.5px] mt-0.5" style="color:#6b7268" x-text="selectedCrop + (saveProbability !== null ? ' · ' + saveProbability + '%' : '')"></p>
        </div>
      </div>

      <!-- Tên người gửi: không cần đăng nhập, chỉ cần nhập tên -->
      <div class="mt-4">
        <label class="text-[13px] font-semibold flex items-center gap-1.5" style="color:#12341d"><i data-lucide="user" class="w-3.5 h-3.5"></i> Tên của bạn</label>
        <input type="text" x-model="saveSenderName" placeholder="Nhập họ tên..." maxlength="100"
               class="w-full mt-1.5 px-3.5 py-2.5 rounded-lg text-[13.5px] border transition focus:outline-none" style="border-color:#dbe8d2" onfocus="this.style.borderColor='#1f6d3c'" onblur="this.style.borderColor='#dbe8d2'">
      </div>

      <!-- Bản đồ chọn vị trí: kéo/bấm vào bản đồ, hoặc bấm nút để lấy đúng vị
           trí hiện tại (đã bỏ ô tìm địa chỉ theo yêu cầu). -->
      <div class="flex items-center justify-between mt-4 mb-1.5">
        <p class="text-[13px] font-semibold flex items-center gap-1.5" style="color:#12341d"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Vị trí phát hiện bệnh</p>
        <button type="button" @click="useCurrentLocationForSave()" class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold transition hover:opacity-85" style="background:#f2f7ee;border:1px solid #dbe8d2;color:#1f6d3c">
          <i data-lucide="locate-fixed" class="w-3.5 h-3.5"></i> Vị trí của tôi
        </button>
      </div>

      <p class="text-[11px] mb-1.5" style="color:#8a8f83">Kéo điểm đánh dấu hoặc bấm vào bản đồ để chọn đúng vị trí. Cuộn chuột để zoom trực tiếp (không cần giữ Ctrl).</p>
      <div id="saveReportMap" class="w-full h-56 2xl:h-64 rounded-xl overflow-hidden" style="background:#e2efd9;border:1px solid #dbe8d2"></div>

      <div class="flex items-center gap-1.5 mt-2" x-show="savePosition.lat !== null">
        <span class="text-[11px] font-mono px-2.5 py-1 rounded-full" style="background:#f2f7ee;color:#4a5245">
          <span x-text="savePosition.lat && savePosition.lat.toFixed(5)"></span>, <span x-text="savePosition.lng && savePosition.lng.toFixed(5)"></span>
        </span>
      </div>

      <p x-show="saveError" x-cloak class="text-[12.5px] mt-3 px-3 py-2.5 rounded-lg flex items-start gap-2" style="background:#fbe3dc;color:#c1440e">
        <i data-lucide="alert-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i> <span x-text="saveError"></span>
      </p>

      <button @click="submitSaveReport()" :disabled="saveSubmitting || savePosition.lat === null || !saveSenderName.trim()" class="w-full mt-5 px-5 py-3.5 rounded-xl text-[14px] font-semibold text-white flex items-center justify-center gap-2 transition" :style="`background:linear-gradient(135deg,#1f6d3c,#164f2b);opacity:${(saveSubmitting || savePosition.lat === null || !saveSenderName.trim()) ? .55 : 1}`">
        <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="saveSubmitting"></i>
        <i data-lucide="send" class="w-4 h-4" x-show="!saveSubmitting"></i>
        <span x-text="saveSubmitting ? 'Đang lưu...' : 'Lưu kết quả'"></span>
      </button>
    </div>
  </div>
</div>
