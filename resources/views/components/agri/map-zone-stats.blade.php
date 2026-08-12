@props(['crops' => [], 'counts' => []])

{{-- ================= Nút nổi "Thống kê vùng dịch" =================
     Dùng chung cho CẢ bản đồ công khai (agri-disease-map.blade.php) và
     dashboard vùng dịch admin (admin/disease-zones/index.blade.php, nơi nó
     thay cho dải thẻ thống kê theo cây từng nằm cố định phía trên bản đồ -
     dời vào đây để bản đồ được full height). Bấm nút mở popup liệt kê từng
     loại cây + số điểm đang hoạt động, bấm vào 1 dòng sẽ lọc bản đồ theo cây
     đó VÀ zoom đến các điểm (xem DiseaseMapKit.initSimplePanel() trong
     disease-map.js + wiring riêng ở từng trang). "counts" truyền vào chỉ là
     giá trị ban đầu (0 ở trang công khai vì route không qua controller) -
     mỗi trang tự cập nhật lại số thật sau khi loadReports() trả về
     data.stats (xem updateZoneStatsCounts() ở 2 trang). Panel ẨN mặc định,
     đóng bằng nút X ở header hoặc bấm lại nút tròn nổi. --}}
<div class="absolute top-3 right-3 md:top-4 md:right-4 z-10">
  <button type="button" id="btnZoneStats" title="Thống kê vùng dịch"
          class="w-11 h-11 rounded-full shadow-lg flex items-center justify-center bg-white transition hover:shadow-xl active:scale-95"
          style="border:1px solid #dbe8d2;color:#c1440e">
    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
  </button>

  <div id="zoneStatsPanel" class="absolute right-0 mt-2 w-72 max-w-[85vw] max-h-[70vh] overflow-y-auto no-scrollbar bg-white rounded-2xl shadow-2xl" style="display:none;border:1px solid #eceae3">
    <div class="flex items-center justify-between px-3.5 py-3 border-b" style="border-color:#eceae3">
      <p class="text-[13px] font-bold" style="color:#12341d">Thống kê vùng dịch</p>
      <button type="button" id="btnCloseZoneStats" title="Đóng" class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
      </button>
    </div>
    <p class="px-3.5 pt-2.5 text-[11px]" style="color:#8a8f83">Bấm vào 1 loại cây để lọc bản đồ và zoom đến các điểm đang hoạt động của cây đó.</p>
    <div class="p-2.5">
      @foreach ($crops as $key => $label)
        @php $c = $counts[$key] ?? 0; @endphp
        <button type="button" class="zone-stat-row w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left transition" data-crop="{{ $key }}">
          <span class="text-[13px] font-semibold" style="color:#12341d">{{ $label }}</span>
          <span class="zone-stat-count text-[12px] font-bold px-2 py-0.5 rounded-full shrink-0" style="background:{{ $c > 0 ? '#fbe3dc' : '#f2f4ef' }};color:{{ $c > 0 ? 'var(--danger)' : '#9aa192' }}">{{ $c }} điểm</span>
        </button>
      @endforeach
    </div>
  </div>
</div>

<style>
  .zone-stat-row:hover{background:#f2f7ee;}
</style>
