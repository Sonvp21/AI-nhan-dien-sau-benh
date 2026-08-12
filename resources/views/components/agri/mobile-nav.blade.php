{{-- ================= TOPBAR + DRAWER MOBILE (dùng chung cho mọi trang) =================
     Chỉ hiện < md: logo nhỏ + nút hamburger mở drawer trượt từ bên phải, chứa
     2 tab điều hướng (Chuẩn đoán bệnh / Bản đồ dịch bệnh) - hiện luôn kể cả
     chưa đăng nhập, phần đăng nhập/đăng xuất chỉ là phụ ở dưới cùng (đăng nhập
     giờ chỉ còn dùng cho admin vào trang quản trị).
     Yêu cầu: trang cha phải có Alpine `mobileDrawerOpen` trong x-data (agri-index
     dùng agriApp(), agri-disease-map dùng x-data rút gọn riêng). ================= --}}
<div class="md:hidden flex items-center justify-between px-5 py-2.5 border-b shrink-0" style="border-color:#eceae3">
  <img src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
  <button @click="mobileDrawerOpen = true" type="button" class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#f2f7ee;color:#1f6d3c">
    <i data-lucide="menu" class="w-5 h-5"></i>
  </button>
</div>

<div x-show="mobileDrawerOpen" x-cloak class="md:hidden fixed inset-0 z-50" style="background:rgba(18,52,29,.45)" @click.self="mobileDrawerOpen = false">
  <div x-show="mobileDrawerOpen" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute top-0 right-0 h-full w-72 max-w-[82%] bg-white shadow-xl p-5 flex flex-col">
    <div class="flex items-center justify-between mb-5">
      <img src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow">
      <button @click="mobileDrawerOpen = false" type="button" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:#fbf1ea;color:#c1440e"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>

    <a href="{{ route('agri.index') }}" class="flex items-center gap-2.5 px-3 py-3 rounded-lg text-[14px] font-semibold" style="{{ request()->routeIs('agri.index') ? 'background:#e2efd9;color:#1f6d3c' : 'color:#4a5245' }}">
      <i data-lucide="stethoscope" class="w-4 h-4"></i> Chuẩn đoán bệnh
    </a>
    <a href="{{ route('agri.disease-map') }}" class="flex items-center gap-2.5 px-3 py-3 rounded-lg text-[14px] font-semibold" style="{{ request()->routeIs('agri.disease-map') ? 'background:#e2efd9;color:#1f6d3c' : 'color:#4a5245' }}">
      <i data-lucide="map" class="w-4 h-4"></i> Bản đồ dịch bệnh
    </a>

    @guest
      <a href="{{ route('agri.auth') }}" class="text-center mt-3 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #dbe8d2;color:#1f6d3c">Đăng nhập quản trị</a>
    @endguest

    @auth
      <div class="mt-3 pt-3 border-t" style="border-color:#eceae3">
        <p class="text-[12.5px] mb-2 px-1" style="color:#6b7268">Xin chào, {{ auth()->user()->name }}</p>
        <form method="POST" action="{{ route('agri.auth.logout') }}">
          @csrf
          <button class="w-full text-center px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #c1440e;color:#c1440e">Đăng xuất</button>
        </form>
      </div>
    @endauth
  </div>
</div>
