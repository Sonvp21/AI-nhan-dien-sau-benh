{{-- ================= MODAL: Lưu kết quả chẩn đoán =================
     Mở qua openSaveModal() trong agri-app.js ngay sau khi có kết quả (nút "Lưu
     kết quả" ở guide-result-panel.blade.php). Thông tin bệnh tự điền từ
     info/liveResult, ảnh lấy từ confirmedPhotos[0], vị trí lấy từ GPS hoặc do
     người dùng tự kéo marker trên bản đồ Google Maps nhỏ trong modal. Submit
     xong report ở trạng thái "pending", chỉ lên bản đồ công khai sau khi admin
     duyệt (xem DiagnosisReportController + Admin\DiagnosisReportController). --}}
<div x-show="saveModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(18,52,29,.45)" @click.self="closeSaveModal()">
  <div class="bg-white rounded-2xl w-full max-w-md 2xl:max-w-lg p-6 2xl:p-7 relative shadow-xl max-h-[90vh] overflow-y-auto">
    <button @click="closeSaveModal()" class="absolute top-3.5 right-3.5 w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e"><i data-lucide="x" class="w-4 h-4"></i></button>

    <p class="text-[19px] 2xl:text-[20px] font-bold pr-8" style="color:#12341d">Lưu kết quả chẩn đoán</p>
    <p class="text-[12.5px] 2xl:text-[13px] mt-1" style="color:#6b7268">Kết quả sẽ được gửi cho quản trị viên kiểm duyệt trước khi hiện lên bản đồ dịch bệnh công khai.</p>

    <!-- Ảnh + thông tin bệnh (tự điền, chỉ đọc) -->
    <div class="flex gap-3 mt-4">
      <img x-show="confirmedPhotos.length" :src="confirmedPhotos[0] && confirmedPhotos[0].url" class="w-20 h-20 rounded-lg object-cover shrink-0" style="background:#f2f7ee">
      <div class="min-w-0">
        <p class="text-[15px] font-bold" style="color:#c1440e" x-text="saveDiseaseName"></p>
        <p class="text-[12.5px]" style="color:#6b7268" x-text="selectedCrop + (saveProbability !== null ? ' · ' + saveProbability + '%' : '')"></p>
      </div>
    </div>

    <!-- Thông tin người gửi (từ auth, chỉ đọc) -->
    <div class="mt-3 px-3 py-2 rounded-lg text-[12.5px]" style="background:#f2f7ee;color:#4a5245">
      Người gửi: <span class="font-semibold" x-text="(currentUser && currentUser.name) || ''"></span>
      <span x-show="currentUser && currentUser.phone" x-text="'(' + (currentUser ? currentUser.phone : '') + ')'"></span>
    </div>

    <!-- Bản đồ chọn vị trí: mặc định GPS hiện tại, kéo marker để chọn lại -->
    <p class="text-[13px] font-semibold mt-4 mb-1.5" style="color:#12341d">Vị trí phát hiện bệnh</p>
    <p class="text-[11.5px] mb-1.5" style="color:#8a8f83">Kéo điểm đánh dấu trên bản đồ để chọn đúng vị trí.</p>
    <div id="saveReportMap" class="w-full h-48 rounded-lg" style="background:#e2efd9"></div>
    <p class="text-[11.5px] mt-1.5" style="color:#8a8f83" x-show="savePosition.lat !== null">
      Toạ độ: <span x-text="savePosition.lat && savePosition.lat.toFixed(5)"></span>, <span x-text="savePosition.lng && savePosition.lng.toFixed(5)"></span>
    </p>

    <p x-show="saveError" x-cloak class="text-[12.5px] mt-3 px-3 py-2 rounded-lg" style="background:#fbe3dc;color:#c1440e" x-text="saveError"></p>

    <button @click="submitSaveReport()" :disabled="saveSubmitting || savePosition.lat === null" class="w-full mt-4 px-5 py-3 rounded-lg text-[14px] font-semibold text-white flex items-center justify-center gap-2 transition" :style="`background:#1f6d3c;opacity:${(saveSubmitting || savePosition.lat === null) ? .6 : 1}`">
      <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="saveSubmitting"></i>
      <span x-text="saveSubmitting ? 'Đang lưu...' : 'Lưu kết quả'"></span>
    </button>
  </div>
</div>
