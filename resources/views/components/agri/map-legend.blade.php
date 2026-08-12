{{-- ================= Nút nổi "Chú giải" bản đồ dịch bệnh =================
     Góc dưới phải, đối xứng với mapCount (dưới trái) và panel điều khiển
     (trên phải). Panel HIỆN SẴN mặc định khi tải trang, gồm 2 phần: (1) màu
     viền marker theo MỨC ĐỘ bệnh, (2) ảnh đại diện từng LOẠI CÂY (danh sách
     #legendCropList được JS điền tự động từ DiseaseMapKit.CROP_META - luôn
     khớp với ảnh marker thật, không khai báo lại ở đây). Đóng bằng nút X ở
     header (hoặc bấm lại nút tròn nổi) - KHÔNG tự đóng khi bấm ra ngoài.
     Logic nằm ở DiseaseMapKit.initLegend() trong public/js/disease-map.js
     (trang gọi hàm đó sau khi map init xong).
     LƯU Ý z-index: legend luôn phải là lớp THẤP NHẤT trong các panel nổi
     trên bản đồ (control panel, thống kê vùng dịch, bottom-sheet chi tiết
     vùng dịch đều z-10 trở lên) - dùng z-[1] ở đây để khi các popup khác đè
     lên (đặc biệt trên màn hình nhỏ/mobile) thì legend luôn nằm dưới, không
     che mất popup quan trọng hơn. --}}
<div class="absolute bottom-3 right-3 md:bottom-4 md:right-4 z-[1]">
  <button type="button" id="btnMapLegend" title="Chú giải bản đồ"
          class="w-11 h-11 rounded-full shadow-lg flex items-center justify-center bg-white transition hover:shadow-xl active:scale-95"
          style="border:1px solid #dbe8d2;color:#1f6d3c">
    <i data-lucide="info" class="w-5 h-5"></i>
  </button>

  <div id="mapLegendPanel" class="absolute right-0 bottom-full mb-2 w-64 max-h-[70vh] overflow-y-auto no-scrollbar bg-white rounded-2xl shadow-2xl" style="display:block;border:1px solid #eceae3">
    <div class="flex items-center justify-between px-3.5 py-3 border-b" style="border-color:#eceae3">
      <p class="text-[13px] font-bold" style="color:#12341d">Chú giải</p>
      <button type="button" id="btnCloseLegend" title="Đóng" class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
      </button>
    </div>

    <div class="p-3.5 border-b" style="border-color:#eceae3">
      <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2" style="color:#9aa192">Mức độ bệnh</p>
      <div class="flex items-center gap-2 py-1">
        <span class="w-3 h-3 rounded-full shrink-0" style="background:#1f6d3c"></span>
        <span class="text-[12.5px] font-medium" style="color:#3d443a">Nhẹ</span>
      </div>
      <div class="flex items-center gap-2 py-1">
        <span class="w-3 h-3 rounded-full shrink-0" style="background:#c9762c"></span>
        <span class="text-[12.5px] font-medium" style="color:#3d443a">Trung bình</span>
      </div>
      <div class="flex items-center gap-2 py-1">
        <span class="w-3 h-3 rounded-full shrink-0" style="background:#c1440e"></span>
        <span class="text-[12.5px] font-medium" style="color:#3d443a">Nặng</span>
      </div>
    </div>

    <div class="p-3.5">
      <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2" style="color:#9aa192">Loại cây trồng</p>
      <div id="legendCropList"></div>
    </div>
  </div>
</div>
