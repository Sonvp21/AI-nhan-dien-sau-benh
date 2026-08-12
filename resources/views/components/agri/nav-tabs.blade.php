{{-- ================= THANH TAB: Chuẩn đoán bệnh / Bản đồ dịch bệnh / Lịch sử
     của tôi. CHỈ hiện khi đã đăng nhập (theo yêu cầu) - guest không thấy gì cả.
     Dùng lại ở agri-index.blade.php (dưới banner, chỉ desktop - mobile dùng
     drawer riêng), agri-disease-map.blade.php, agri-diagnosis-history.blade.php. ================= --}}
@auth
  @php
    $navTabs = [
      ['route' => 'agri.index', 'label' => 'Chuẩn đoán bệnh', 'icon' => 'stethoscope'],
      ['route' => 'agri.disease-map', 'label' => 'Bản đồ dịch bệnh', 'icon' => 'map'],
      ['route' => 'agri.reports.history', 'label' => 'Lịch sử của tôi', 'icon' => 'history'],
    ];
  @endphp
  <nav class="flex items-center gap-1 px-5 md:px-7 2xl:px-10 bg-white border-b overflow-x-auto no-scrollbar" style="border-color:#eceae3">
    @foreach ($navTabs as $tab)
      <a href="{{ route($tab['route']) }}"
         class="shrink-0 flex items-center gap-1.5 px-4 py-3 text-[13px] md:text-[13.5px] font-semibold border-b-2 transition"
         style="{{ request()->routeIs($tab['route']) ? 'color:#1f6d3c;border-color:#1f6d3c' : 'color:#6b7268;border-color:transparent' }}">
        <i data-lucide="{{ $tab['icon'] }}" class="w-4 h-4"></i>
        {{ $tab['label'] }}
      </a>
    @endforeach
  </nav>
@endauth
