@props(['crops' => []])

{{-- ================= Nút nổi + popup điều khiển bản đồ dịch bệnh =================
     Dùng chung cho trang công khai (agri-disease-map.blade.php) và dashboard
     vùng dịch admin (admin/disease-zones/index.blade.php). Thay cho dải lọc
     ngang cũ: 1 nút tròn nổi trên bản đồ (góc phải trên) + popup gồm 3 nhóm -
     đổi loại bản đồ, lọc theo cây (dropdown), bật/tắt lớp dữ liệu (dạng
     switch). Panel ẨN mặc định (khác với panel Chú giải) - bấm nút tròn nổi
     để mở, nút X ở header hoặc bấm lại nút tròn nổi để đóng, KHÔNG tự đóng
     khi bấm ra ngoài. Logic gắn sự kiện nằm ở DiseaseMapKit.initControlPanel() trong
     public/js/disease-map.js - trang gọi hàm đó sau khi map init xong.
     Slot "extra" (tuỳ chọn): cho phép trang chèn thêm hành động vào ngay dưới
     phần lọc cây, ví dụ nút "Đánh dấu đã xử lý toàn bộ" ở dashboard admin.
     Đặt góc TRÁI trên (trước để bên phải, đổi theo yêu cầu) - ở dashboard
     vùng dịch admin, góc phải trên giờ dành cho nút "Thống kê vùng dịch"
     (x-agri.map-zone-stats). --}}
<div class="absolute top-3 left-3 md:top-4 md:left-4 z-10">
  <button type="button" id="btnMapControl" title="Điều khiển bản đồ"
          class="w-11 h-11 rounded-full shadow-lg flex items-center justify-center bg-white transition hover:shadow-xl active:scale-95"
          style="border:1px solid #dbe8d2;color:#1f6d3c">
    <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
  </button>

  <div id="mapControlPanel" class="absolute left-0 mt-2 w-[19rem] max-w-[85vw] bg-white rounded-2xl shadow-2xl" style="display:none;border:1px solid #eceae3">
    <div class="flex items-center justify-between px-3.5 py-3 border-b" style="border-color:#eceae3">
      <p class="text-[13px] font-bold" style="color:#12341d">Điều khiển bản đồ</p>
      <button type="button" id="btnCloseControlPanel" title="Đóng" class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
      </button>
    </div>

    {{-- Nhóm 1: đổi loại bản đồ --}}
    <div class="p-3.5 border-b" style="border-color:#eceae3">
      <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2" style="color:#9aa192">Loại bản đồ</p>
      <div class="grid grid-cols-4 gap-1.5">
        <button type="button" data-maptype-btn="roadmap" class="maptype-btn text-[11px] font-semibold py-1.5 rounded-lg transition" data-active="true">Mặc định</button>
        <button type="button" data-maptype-btn="satellite" class="maptype-btn text-[11px] font-semibold py-1.5 rounded-lg transition">Vệ tinh</button>
        <button type="button" data-maptype-btn="terrain" class="maptype-btn text-[11px] font-semibold py-1.5 rounded-lg transition">Địa hình</button>
        <button type="button" data-maptype-btn="hybrid" class="maptype-btn text-[11px] font-semibold py-1.5 rounded-lg transition">Kết hợp</button>
      </div>
    </div>

    {{-- Nhóm 2: lọc theo cây --}}
    <div class="p-3.5 border-b" style="border-color:#eceae3">
      <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2" style="color:#9aa192">Lọc theo cây trồng</p>
      <button type="button" id="btnCropDropdown" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[13px] font-semibold transition" style="background:#f2f7ee;border:1px solid #dbe8d2;color:#12341d">
        <span id="cropFilterLabel">Tất cả</span>
        <i data-lucide="chevron-down" class="w-3.5 h-3.5 shrink-0" style="color:#6b7268"></i>
      </button>
      <div id="cropDropdown" class="mt-1.5 max-h-56 overflow-y-auto rounded-lg no-scrollbar" style="display:none;border:1px solid #eceae3">
        <button type="button" class="crop-option w-full text-left px-3 py-2 text-[13px] font-medium transition" data-crop="" data-active="true">Tất cả</button>
        @foreach ($crops as $key => $label)
          <button type="button" class="crop-option w-full text-left px-3 py-2 text-[13px] font-medium transition" data-crop="{{ $key }}">{{ $label }}</button>
        @endforeach
      </div>

      {{ $extra ?? '' }}
    </div>

    {{-- Nhóm 3: lớp dữ liệu (switch) --}}
    <div class="p-3.5">
      <p class="text-[10.5px] font-bold uppercase tracking-wide mb-2" style="color:#9aa192">Lớp dữ liệu</p>
      <div class="flex items-center justify-between py-1.5">
        <span class="text-[13px] font-medium flex items-center gap-1.5" style="color:#3d443a"><i data-lucide="flame" class="w-3.5 h-3.5" style="color:#c9762c"></i> Lớp mật độ</span>
        <button type="button" id="layerHeatmapSwitch" class="map-switch" data-on="true"><span class="map-switch-knob"></span></button>
      </div>
      <div class="flex items-center justify-between py-1.5">
        <span class="text-[13px] font-medium flex items-center gap-1.5" style="color:#3d443a"><i data-lucide="map-pin" class="w-3.5 h-3.5" style="color:#1f6d3c"></i> Lớp điểm chi tiết</span>
        <button type="button" id="layerMarkersSwitch" class="map-switch" data-on="true"><span class="map-switch-knob"></span></button>
      </div>
    </div>
  </div>
</div>

<style>
  .maptype-btn{background:#f2f7ee;color:#4a5245;}
  .maptype-btn[data-active="true"]{background:#1f6d3c;color:#fff;}
  .crop-option{color:#4a5245;border-bottom:1px solid #f2f4ef;background:#fff;}
  .crop-option:last-child{border-bottom:none;}
  .crop-option:hover{background:#f2f7ee;}
  .crop-option[data-active="true"]{background:#e2efd9;color:#1f6d3c;font-weight:700;}
  .map-switch{position:relative;width:38px;height:22px;border-radius:999px;background:#d7dcd1;transition:background .15s;flex-shrink:0;border:none;cursor:pointer;padding:0;}
  .map-switch[data-on="true"]{background:#1f6d3c;}
  .map-switch-knob{position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:999px;background:#fff;transition:transform .15s;box-shadow:0 1px 2px rgba(0,0,0,.25);display:block;}
  .map-switch[data-on="true"] .map-switch-knob{transform:translateX(16px);}
</style>
