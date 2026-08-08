<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#16261F; --forest:#1B4332; --leaf:#52B788; --leaf-soft:#DCEFE3;
    --mist:#EEF4EF; --soil:#C9762C; --soil-soft:#FBE9D8; --paper:#FFFFFF; --danger:#C1503F;
  }
  *{-webkit-tap-highlight-color:transparent;}
  body{font-family:'Inter',sans-serif;color:var(--ink);background:var(--mist);}
  .font-display{font-family:'Space Grotesk',sans-serif;}
  .font-mono{font-family:'IBM Plex Mono',monospace;}
  .card{background:var(--paper);border-radius:20px;box-shadow:0 6px 20px -10px rgba(20,50,35,.15);}
  .chip{transition:all .25s cubic-bezier(.34,1.56,.64,1);}
  .chip.active{background:var(--forest);color:#fff;transform:scale(1.05);}
  .navbtn{transition:color .2s ease, transform .15s ease;}
  .navbtn.active{color:var(--forest);}
  .navbtn:active{transform:scale(.92);}
  .btn-press:active{transform:scale(.97);}
  .btn-press{transition:transform .12s ease;}
  .scan-ring{border-radius:9999px;background:conic-gradient(var(--leaf) calc(var(--p,0)*1%), #E4EEE7 0);transition:background 1s linear;}
  @keyframes riseFade{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
  .rise{animation:riseFade .5s cubic-bezier(.16,1,.3,1) both;}
  .rise-1{animation-delay:.03s;} .rise-2{animation-delay:.08s;} .rise-3{animation-delay:.13s;} .rise-4{animation-delay:.18s;} .rise-5{animation-delay:.23s;}
  @keyframes pulseSoft{0%,100%{opacity:1;}50%{opacity:.55;}}
  .pulse{animation:pulseSoft 1.6s ease-in-out infinite;}
  [x-cloak]{display:none!important;}
  ::-webkit-scrollbar{width:0;height:0;}
</style>
</head>
<body x-data="agriApp()" class="min-h-screen">

<!-- ================= DESKTOP TOP NAV (>=768px) ================= -->
<header class="hidden md:flex items-center justify-between px-10 py-4 bg-white/80 backdrop-blur border-b border-black/5 sticky top-0 z-40">
  <div class="flex items-center gap-2">
    <span class="text-2xl">🌾</span>
    <span class="font-display font-bold text-[19px] text-[var(--forest)]">AgriAI</span>
  </div>
  <nav class="flex items-center gap-8 font-semibold text-[14px]">
    <button @click="tab='home'" :class="tab==='home'||tab==='result' ? 'text-[var(--forest)]':'text-[#6B7C71]'">Cây trồng</button>
    <button @click="tab='yield'" :class="tab==='yield' ? 'text-[var(--forest)]':'text-[#6B7C71]'">Năng suất</button>
    <button @click="tab='diary'" :class="tab==='diary' ? 'text-[var(--forest)]':'text-[#6B7C71]'">Nhật ký</button>
    <a href="{{ route('agri.community') }}" class="text-[#6B7C71]">Cộng đồng</a>
  </nav>
  <div class="flex items-center gap-3">
    <button @click="startScan()" class="btn-press bg-[var(--forest)] text-white text-[13px] font-semibold px-4 py-2.5 rounded-xl flex items-center gap-2">
      📷 Chẩn đoán ngay
    </button>
    <a href="{{ route('agri.notifications') }}" class="btn-press w-9 h-9 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center relative">
      🔔<span class="w-2 h-2 rounded-full bg-[var(--danger)] absolute top-1.5 right-1.5"></span>
    </a>
    <div class="w-9 h-9 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center cursor-pointer" @click="tab='profile'">👤</div>
  </div>
</header>

<!-- ================= MOBILE TOP BAR (<768px) ================= -->
<header class="md:hidden flex items-center justify-between px-5 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-40" x-show="tab!=='scan'">
  <div>
    <p class="text-[12px] text-[#5B6F63]">Xin chào,</p>
    <h1 class="font-display font-bold text-[19px] text-[var(--forest)]">Anh Sơn 🌾</h1>
  </div>
  <div class="flex items-center gap-2.5">
    <a href="{{ route('agri.notifications') }}" class="btn-press w-10 h-10 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center relative">
      🔔<span class="w-2 h-2 rounded-full bg-[var(--danger)] absolute top-1.5 right-1.5"></span>
    </a>
    <div class="w-10 h-10 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center" @click="tab='profile'">👤</div>
  </div>
</header>

<!-- ================= MAIN ================= -->
<main class="max-w-6xl mx-auto px-5 md:px-10 pb-28 md:pb-16" x-show="tab!=='scan'">

  <!-- HOME -->
  <div x-show="tab==='home'" x-cloak>

    <!-- desktop hero -->
    <div class="hidden md:flex items-center justify-between mt-8 mb-8 rise rise-1">
      <div>
        <p class="text-[14px] text-[#5B6F63] font-medium">Thái Nguyên · Hôm nay 27°C, mưa rào nhẹ 🌦️</p>
        <h1 class="font-display font-bold text-[34px] text-[var(--forest)] mt-1">Chào Sơn, ruộng vườn của bạn thế nào?</h1>
      </div>
    </div>

    <!-- alerts banner -->
    <template x-if="alerts.length">
      <div class="mt-4 md:mt-0 rise rise-2 space-y-2.5">
        <template x-for="a in alerts" :key="a.id">
          <div class="flex items-center gap-3 rounded-2xl px-4 py-3"
               :class="a.level==='Nặng' ? 'bg-[var(--danger)]/10' : 'bg-[var(--soil-soft)]'">
            <span class="text-xl shrink-0" x-text="a.emoji"></span>
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold truncate"><span x-text="a.field"></span> — <span x-text="a.issue"></span></p>
              <p class="text-[11px] text-[#6B7C71]" x-text="a.time"></p>
            </div>
            <span class="text-[10px] font-bold font-mono px-2 py-1 rounded-full shrink-0"
                  :class="a.level==='Nặng' ? 'bg-[var(--danger)] text-white' : 'bg-[var(--soil)] text-white'"
                  x-text="a.level"></span>
          </div>
        </template>
      </div>
    </template>

    <!-- crop chips -->
    <div class="flex flex-wrap gap-2.5 mt-5 pt-2 pb-1 rise rise-2">
      <template x-for="c in crops" :key="c.name">
        <button @click="selectedCrop=c.name" class="chip btn-press shrink-0 flex flex-col md:flex-row items-center gap-1.5 md:gap-2 px-4 md:px-5 py-3 rounded-2xl bg-white"
                :class="selectedCrop===c.name ? 'chip active' : ''">
          <span class="text-[22px] md:text-[18px]" x-text="c.emoji"></span>
          <span class="text-[11px] md:text-[13px] font-semibold whitespace-nowrap" x-text="c.name"></span>
        </button>
      </template>
    </div>

    <!-- grid: desktop 12-col, mobile stacked -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mt-5">

      <!-- diagnosis CTA card -->
      <div class="card p-5 md:p-7 md:col-span-2 rise rise-3">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-display font-bold text-[16px] md:text-[19px]">Chẩn đoán cây trồng bằng AI</h2>
          <span class="text-[11px] font-semibold bg-[var(--leaf-soft)] text-[var(--forest)] px-3 py-1.5 rounded-full flex items-center gap-1.5 shrink-0">
            <span x-text="currentCrop.emoji"></span><span x-text="selectedCrop"></span>
          </span>
        </div>
        <div class="flex items-center justify-between text-center max-w-md mx-auto md:mx-0 md:max-w-none">
          <div class="flex-1 flex flex-col items-center gap-1.5">
            <div class="w-11 h-11 md:w-14 md:h-14 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-lg md:text-2xl">📷</div>
            <span class="text-[11px] md:text-[13px] font-medium text-[#5B6F63]">Chụp ảnh</span>
          </div>
          <div class="text-[#B9C7BE] pb-4 md:pb-6">›</div>
          <div class="flex-1 flex flex-col items-center gap-1.5">
            <div class="w-11 h-11 md:w-14 md:h-14 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-lg md:text-2xl">🧠</div>
            <span class="text-[11px] md:text-[13px] font-medium text-[#5B6F63]">AI chẩn đoán</span>
          </div>
          <div class="text-[#B9C7BE] pb-4 md:pb-6">›</div>
          <div class="flex-1 flex flex-col items-center gap-1.5">
            <div class="w-11 h-11 md:w-14 md:h-14 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-lg md:text-2xl">💊</div>
            <span class="text-[11px] md:text-[13px] font-medium text-[#5B6F63]">Khuyến nghị</span>
          </div>
        </div>
        <button @click="startScan()" class="btn-press w-full md:w-auto mt-5 md:mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 px-8 text-[14px] shadow-lg shadow-[var(--forest)]/20">
          Chụp ảnh <span x-text="selectedCrop"></span> ngay
        </button>
      </div>

      <!-- weather -->
      <div class="card p-5 md:p-7 flex items-center justify-between rise rise-4">
        <div>
          <p class="text-[12px] text-[#5B6F63]">Thái Nguyên · Hôm nay</p>
          <p class="font-display font-bold text-[26px] md:text-[32px] mt-1">27°C</p>
          <p class="text-[11px] md:text-[12px] text-[#5B6F63] mt-0.5">Mưa rào nhẹ · Độ ẩm 82%</p>
        </div>
        <span class="text-4xl md:text-5xl">🌦️</span>
      </div>

      <!-- my fields -->
      <div class="card p-5 md:p-7 md:col-span-2 rise rise-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-display font-bold text-[15px] md:text-[16px]">Ruộng & vườn của tôi</h3>
          <button @click="tab='diary'" class="text-[12px] font-semibold text-[var(--forest)]">Xem tất cả</button>
        </div>
        <div class="space-y-3">
          <template x-for="f in fields" :key="f.id">
            <div class="flex items-center gap-3 pb-3 border-b border-black/5 last:border-0 last:pb-0">
              <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl shrink-0"
                   :class="f.status==='healthy' ? 'bg-[var(--leaf-soft)]' : f.status==='warning' ? 'bg-[var(--soil-soft)]' : 'bg-[var(--danger)]/10'"
                   x-text="f.emoji"></div>
              <div class="flex-1 min-w-0">
                <p class="text-[13px] font-semibold truncate" x-text="f.name"></p>
                <p class="text-[11px] text-[#6B7C71]" x-text="f.crop+' · '+f.area"></p>
              </div>
              <span class="w-2.5 h-2.5 rounded-full shrink-0"
                    :class="f.status==='healthy' ? 'bg-[var(--leaf)]' : f.status==='warning' ? 'bg-[var(--soil)]' : 'bg-[var(--danger)]'"></span>
            </div>
          </template>
        </div>
      </div>

      <!-- quick actions -->
      <div class="grid grid-cols-3 md:grid-cols-1 gap-3">
        <button @click="tab='yield'" class="btn-press card flex flex-col md:flex-row items-center gap-2 md:gap-3 p-4">
          <span class="text-xl md:text-2xl">📊</span>
          <span class="text-[11px] md:text-[13px] font-semibold text-center md:text-left leading-tight">Dự báo năng suất</span>
        </button>
        <a href="{{ route('agri.library') }}" class="btn-press card flex flex-col md:flex-row items-center gap-2 md:gap-3 p-4">
          <span class="text-xl md:text-2xl">🐛</span>
          <span class="text-[11px] md:text-[13px] font-semibold text-center md:text-left leading-tight">Sâu bệnh & giải pháp</span>
        </a>
        <button @click="tab='map'" class="btn-press card flex flex-col md:flex-row items-center gap-2 md:gap-3 p-4">
          <span class="text-xl md:text-2xl">📍</span>
          <span class="text-[11px] md:text-[13px] font-semibold text-center md:text-left leading-tight">Bản đồ đồng ruộng</span>
        </button>
      </div>

      <!-- recent diagnosis history -->
      <div class="card p-5 md:p-7 md:col-span-3 rise rise-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-display font-bold text-[15px] md:text-[16px]">Lịch sử chẩn đoán gần đây</h3>
          <button @click="tab='diary'" class="text-[12px] font-semibold text-[var(--forest)]">Xem nhật ký</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <template x-for="h in history" :key="h.id">
            <div class="flex items-center gap-3 bg-[var(--mist)] rounded-2xl p-3">
              <div class="w-11 h-11 rounded-xl bg-white flex items-center justify-center text-xl shrink-0" x-text="h.emoji"></div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] font-semibold truncate" x-text="h.disease"></p>
                <p class="text-[11px] text-[#6B7C71]" x-text="h.crop+' · '+h.date"></p>
              </div>
              <span class="text-[10px] font-bold font-mono px-2 py-1 rounded-full shrink-0"
                    :class="h.level==='Nặng' ? 'bg-[var(--danger)]/10 text-[var(--danger)]' : h.level==='Nhẹ' ? 'bg-[var(--leaf-soft)] text-[var(--forest)]' : 'bg-[var(--soil-soft)] text-[var(--soil)]'"
                    x-text="h.level"></span>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- RESULT -->
  <div x-show="tab==='result'" x-cloak class="mt-6 md:mt-8">
    <button @click="tab='home'" class="flex items-center gap-1 text-[13px] font-semibold text-[var(--forest)] rise rise-1">← Về trang chủ</button>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
      <div class="card overflow-hidden md:col-span-2 rise rise-2">
        <div class="h-40 md:h-56 bg-gradient-to-br from-[var(--leaf-soft)] to-[var(--mist)] flex items-center justify-center text-6xl md:text-8xl" x-text="currentResult.emoji"></div>
        <div class="p-5 md:p-7">
          <div class="flex items-center justify-between">
            <h2 class="font-display font-bold text-[18px] md:text-[22px]" x-text="currentResult.disease"></h2>
            <span class="text-[11px] font-bold font-mono bg-[var(--soil-soft)] text-[var(--soil)] px-2.5 py-1 rounded-full" x-text="currentResult.level"></span>
          </div>
          <p class="text-[12px] md:text-[13px] text-[#5B6F63] mt-1" x-text="currentResult.nameEn+' · Độ tin cậy '+currentResult.confidence"></p>
          <div class="mt-4 h-2 rounded-full bg-[#E4EEE7] overflow-hidden">
            <div class="h-full bg-[var(--danger)] rounded-full" :style="`width:${currentResult.spread}%`"></div>
          </div>
          <p class="text-[11px] md:text-[12px] text-[#5B6F63] mt-1.5" x-text="'Mức độ lây lan ước tính: '+currentResult.spread+'% khu vực'"></p>
        </div>
      </div>

      <div class="card p-5 md:p-7 rise rise-3">
        <h3 class="font-display font-bold text-[14px] md:text-[15px] mb-3">Khuyến nghị xử lý</h3>
        <div class="space-y-3">
          <template x-for="(step, i) in currentResult.steps" :key="i">
            <div class="flex gap-3">
              <div class="w-7 h-7 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-[12px] font-bold font-mono shrink-0" x-text="i+1"></div>
              <p class="text-[13px] leading-snug" x-text="step"></p>
            </div>
          </template>
        </div>
        <button @click="showToast('Đã lưu vào nhật ký ruộng')" class="btn-press w-full mt-5 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3 text-[13px]">
          Lưu vào nhật ký ruộng
        </button>
      </div>
    </div>
  </div>

  <!-- YIELD FORECAST -->
  <div x-show="tab==='yield'" x-cloak class="mt-6 md:mt-8">
    <div class="hidden md:block rise rise-1">
      <h1 class="font-display font-bold text-[28px] text-[var(--forest)]">Dự báo năng suất</h1>
      <p class="text-[14px] text-[#5B6F63] mt-1">Nhập thông số đất & thời tiết để ước lượng sản lượng thu hoạch</p>
    </div>
    <h1 class="md:hidden font-display font-bold text-[19px] text-[var(--forest)] mt-1 rise rise-1">Dự báo năng suất</h1>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mt-5">

      <!-- input form -->
      <div class="card p-5 md:p-7 md:col-span-3 rise rise-2">
        <!-- crop select for this form -->
        <label class="text-[12px] font-semibold text-[#5B6F63]">Loại cây trồng</label>
        <div class="flex flex-wrap gap-2 mt-2 mb-5">
          <template x-for="c in crops" :key="c.name">
            <button @click="selectedCrop=c.name" class="chip btn-press shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[var(--mist)] text-[12px] font-semibold"
                    :class="selectedCrop===c.name ? 'chip active' : ''">
              <span x-text="c.emoji"></span><span x-text="c.name"></span>
            </button>
          </template>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Diện tích (ha)</label>
            <input type="number" x-model.number="yieldForm.area" step="0.1" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Đạm N (kg/ha)</label>
            <input type="number" x-model.number="yieldForm.n" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Lân P (kg/ha)</label>
            <input type="number" x-model.number="yieldForm.p" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Kali K (kg/ha)</label>
            <input type="number" x-model.number="yieldForm.k" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Nhiệt độ TB (°C)</label>
            <input type="number" x-model.number="yieldForm.temp" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
          <div>
            <label class="text-[12px] font-semibold text-[#5B6F63]">Lượng mưa (mm)</label>
            <input type="number" x-model.number="yieldForm.rainfall" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-3.5 py-2.5 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
          </div>
        </div>

        <div class="mt-4">
          <label class="text-[12px] font-semibold text-[#5B6F63]">Độ ẩm đất (%)</label>
          <input type="range" x-model.number="yieldForm.humidity" min="0" max="100" class="w-full mt-2 accent-[var(--forest)]">
          <div class="flex justify-between text-[11px] text-[#5B6F63] font-mono mt-0.5">
            <span>0%</span><span x-text="yieldForm.humidity+'%'" class="font-bold text-[var(--forest)]"></span><span>100%</span>
          </div>
        </div>

        <button @click="predictYield()" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px] shadow-lg shadow-[var(--forest)]/20">
          Dự báo năng suất
        </button>
      </div>

      <!-- result panel -->
      <div class="md:col-span-2">
        <template x-if="!yieldResult">
          <div class="card p-7 h-full flex flex-col items-center justify-center text-center rise rise-3">
            <span class="text-4xl mb-3">📈</span>
            <p class="text-[13px] text-[#5B6F63] leading-relaxed">Nhập thông số bên trái rồi bấm<br>"Dự báo năng suất" để xem kết quả</p>
          </div>
        </template>
        <template x-if="yieldResult">
          <div class="card p-6 md:p-7 rise rise-3">
            <p class="text-[12px] font-semibold text-[#5B6F63]">Sản lượng dự báo</p>
            <p class="font-display font-bold text-[38px] text-[var(--forest)] mt-1">
              <span x-text="yieldResult.perHa"></span> <span class="text-[16px] font-semibold text-[#5B6F63]">tấn/ha</span>
            </p>
            <p class="text-[12px] text-[#5B6F63] mt-0.5">Tổng sản lượng ước tính: <span class="font-semibold font-mono" x-text="yieldResult.total+' tấn'"></span> trên <span x-text="yieldForm.area+' ha'"></span></p>

            <div class="mt-5 h-2 rounded-full bg-[#E4EEE7] overflow-hidden">
              <div class="h-full bg-[var(--leaf)] rounded-full transition-all duration-700" :style="`width:${yieldResult.scorePct}%`"></div>
            </div>
            <p class="text-[11px] text-[#5B6F63] mt-1.5" x-text="'So với năng suất trung bình vùng: '+yieldResult.comparison"></p>

            <div class="mt-5 pt-5 border-t border-black/5 space-y-2.5">
              <p class="text-[12px] font-semibold mb-2">Yếu tố ảnh hưởng chính</p>
              <template x-for="f in yieldResult.factors" :key="f.label">
                <div class="flex items-center justify-between text-[12px]">
                  <span class="text-[#5B6F63]" x-text="f.label"></span>
                  <span class="font-semibold font-mono" :class="f.positive ? 'text-[var(--forest)]' : 'text-[var(--soil)]'" x-text="f.value"></span>
                </div>
              </template>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- DIARY / HISTORY -->
  <div x-show="tab==='diary'" x-cloak class="mt-6 md:mt-8">
    <button @click="tab='home'" class="flex items-center gap-1 text-[13px] font-semibold text-[var(--forest)] rise rise-1">← Về trang chủ</button>

    <h1 class="font-display font-bold text-[19px] md:text-[28px] text-[var(--forest)] mt-3 rise rise-1">Nhật ký chẩn đoán</h1>

    <!-- filter chips -->
    <div class="flex flex-wrap gap-2 mt-4 rise rise-2">
      <template x-for="f in ['Tất cả','Nặng','Trung bình','Nhẹ']" :key="f">
        <button @click="diaryFilter=f" class="chip btn-press px-4 py-2 rounded-xl bg-white text-[12px] font-semibold"
                :class="diaryFilter===f ? 'chip active' : ''" x-text="f"></button>
      </template>
    </div>

    <div class="mt-5 space-y-3 max-w-3xl">
      <template x-for="h in filteredHistory" :key="h.id">
        <div class="card p-4 md:p-5 flex items-center gap-4 rise">
          <div class="w-14 h-14 rounded-2xl bg-[var(--mist)] flex items-center justify-center text-2xl shrink-0" x-text="h.emoji"></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="text-[14px] font-semibold truncate" x-text="h.disease"></p>
              <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded-full shrink-0"
                    :class="h.level==='Nặng' ? 'bg-[var(--danger)]/10 text-[var(--danger)]' : h.level==='Nhẹ' ? 'bg-[var(--leaf-soft)] text-[var(--forest)]' : 'bg-[var(--soil-soft)] text-[var(--soil)]'"
                    x-text="h.level"></span>
            </div>
            <p class="text-[12px] text-[#6B7C71] mt-0.5" x-text="h.field+' · '+h.crop"></p>
          </div>
          <p class="text-[11px] font-mono text-[#9AA9A0] shrink-0" x-text="h.date"></p>
        </div>
      </template>
      <template x-if="!filteredHistory.length">
        <div class="card p-8 text-center text-[13px] text-[#6B7C71]">Không có mục nào ở mức độ này</div>
      </template>
    </div>
  </div>

  <!-- FIELD MAP -->
  <div x-show="tab==='map'" x-cloak class="mt-6 md:mt-8">
    <button @click="tab='home'" class="flex items-center gap-1 text-[13px] font-semibold text-[var(--forest)] rise rise-1">← Về trang chủ</button>
    <h1 class="font-display font-bold text-[19px] md:text-[28px] text-[var(--forest)] mt-3 rise rise-1">Bản đồ đồng ruộng</h1>
    <a href="{{ route('agri.add-field') }}" class="inline-flex btn-press items-center gap-1.5 mt-3 text-[12px] font-semibold text-[var(--forest)] bg-[var(--leaf-soft)] px-3.5 py-2 rounded-xl">+ Thêm ruộng/vườn mới</a>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mt-5">
      <!-- stylised map -->
      <div class="card md:col-span-3 overflow-hidden rise rise-2" style="min-height:320px;">
        <div class="relative w-full h-full" style="min-height:320px;background:
          linear-gradient(135deg, var(--leaf-soft) 0%, var(--mist) 45%, #E3EEE6 100%);">
          <svg class="absolute inset-0 w-full h-full opacity-40" viewBox="0 0 400 320" preserveAspectRatio="none">
            <path d="M0,80 Q100,40 200,90 T400,70" stroke="#9FC7AE" stroke-width="3" fill="none"/>
            <path d="M0,220 Q120,260 240,210 T400,240" stroke="#9FC7AE" stroke-width="3" fill="none"/>
          </svg>
          <template x-for="f in fields" :key="f.id">
            <button @click="selectedField=f" class="absolute btn-press flex flex-col items-center -translate-x-1/2 -translate-y-full"
                    :style="`left:${f.x}%; top:${f.y}%`">
              <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded-full bg-white shadow mb-1 whitespace-nowrap" x-text="f.crop"></span>
              <span class="w-10 h-10 rounded-full flex items-center justify-center text-lg shadow-lg ring-4"
                    :class="f.status==='healthy' ? 'bg-[var(--leaf)] ring-[var(--leaf-soft)]' : f.status==='warning' ? 'bg-[var(--soil)] ring-[var(--soil-soft)]' : 'bg-[var(--danger)] ring-[var(--danger)]/20'"
                    x-text="f.emoji"></span>
            </button>
          </template>
        </div>
      </div>

      <!-- field detail panel -->
      <div class="md:col-span-2">
        <div class="card p-6 rise rise-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0"
                 :class="selectedField.status==='healthy' ? 'bg-[var(--leaf-soft)]' : selectedField.status==='warning' ? 'bg-[var(--soil-soft)]' : 'bg-[var(--danger)]/10'"
                 x-text="selectedField.emoji"></div>
            <div class="min-w-0">
              <p class="font-display font-bold text-[15px] truncate" x-text="selectedField.name"></p>
              <p class="text-[12px] text-[#6B7C71]" x-text="selectedField.crop+' · '+selectedField.area"></p>
            </div>
          </div>

          <div class="mt-5 flex items-center gap-2 px-3 py-2.5 rounded-xl"
               :class="selectedField.status==='healthy' ? 'bg-[var(--leaf-soft)]' : selectedField.status==='warning' ? 'bg-[var(--soil-soft)]' : 'bg-[var(--danger)]/10'">
            <span class="w-2 h-2 rounded-full shrink-0"
                  :class="selectedField.status==='healthy' ? 'bg-[var(--leaf)]' : selectedField.status==='warning' ? 'bg-[var(--soil)]' : 'bg-[var(--danger)]'"></span>
            <span class="text-[12px] font-semibold" x-text="selectedField.status==='healthy' ? 'Đang phát triển tốt' : selectedField.status==='warning' ? 'Cần theo dõi' : 'Đang xử lý bệnh'"></span>
          </div>

          <button @click="selectedCrop=selectedField.crop; startScan()" class="btn-press w-full mt-5 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3 text-[13px]">
            Chẩn đoán khu vực này
          </button>
        </div>

        <!-- legend -->
        <div class="card p-5 mt-4 rise rise-4">
          <p class="text-[12px] font-semibold mb-3">Chú giải</p>
          <div class="space-y-2 text-[12px] text-[#5B6F63]">
            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[var(--leaf)]"></span> Khỏe mạnh</div>
            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[var(--soil)]"></span> Cần theo dõi</div>
            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[var(--danger)]"></span> Đang xử lý bệnh</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PROFILE -->
  <div x-show="tab==='profile'" x-cloak class="mt-6 md:mt-8">
    <h1 class="font-display font-bold text-[19px] md:text-[28px] text-[var(--forest)] rise rise-1">Cá nhân</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
      <!-- user card -->
      <div class="card p-6 md:col-span-1 flex flex-col items-center text-center rise rise-2">
        <div class="w-20 h-20 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-4xl">👤</div>
        <p class="font-display font-bold text-[17px] mt-3">Nguyễn Văn Sơn</p>
        <p class="text-[12px] text-[#6B7C71] mt-0.5">Thái Nguyên, Việt Nam</p>
        <span class="text-[11px] font-semibold bg-[var(--leaf-soft)] text-[var(--forest)] px-3 py-1 rounded-full mt-3">Nông hộ xác thực</span>

        <div class="grid grid-cols-3 gap-2 w-full mt-5 pt-5 border-t border-black/5">
          <div>
            <p class="font-display font-bold text-[18px]" x-text="fields.length"></p>
            <p class="text-[10px] text-[#6B7C71]">Ruộng/vườn</p>
          </div>
          <div>
            <p class="font-display font-bold text-[18px]" x-text="history.length"></p>
            <p class="text-[10px] text-[#6B7C71]">Lần chẩn đoán</p>
          </div>
          <div>
            <p class="font-display font-bold text-[18px]">3</p>
            <p class="text-[10px] text-[#6B7C71]">Loại cây</p>
          </div>
        </div>
      </div>

      <!-- settings list -->
      <div class="card p-2 md:col-span-2 rise rise-3">
        <template x-for="item in profileMenu" :key="item.label">
          <button @click="item.label==='Đăng xuất' ? (tab='home', showToast('Đã đăng xuất')) : showToast('Tính năng đang phát triển')" class="btn-press w-full flex items-center gap-3.5 px-4 py-3.5 rounded-2xl hover:bg-[var(--mist)] text-left">
            <span class="w-9 h-9 rounded-xl bg-[var(--mist)] flex items-center justify-center text-[16px] shrink-0" x-text="item.emoji"></span>
            <span class="flex-1 text-[13px] font-medium" x-text="item.label"></span>
            <span class="text-[#B9C7BE]">›</span>
          </button>
        </template>
      </div>
    </div>
  </div>
</main>

<!-- ================= SCAN (fullscreen both) ================= -->
<div x-show="tab==='scan'" x-cloak class="fixed inset-0 bg-[var(--forest)] text-white z-50 flex flex-col">
  <button @click="tab='home'; scanState='idle'" class="absolute top-6 left-6 z-10 w-9 h-9 rounded-full bg-white/15 flex items-center justify-center">←</button>
  <span class="absolute top-6 right-6 z-10 text-[12px] font-semibold bg-white/15 px-3 py-2 rounded-full flex items-center gap-1.5">
    <span x-text="currentCrop.emoji"></span><span x-text="selectedCrop"></span>
  </span>
  <div class="flex-1 flex flex-col items-center justify-center px-8">
    <template x-if="scanState==='idle'">
      <div class="w-full max-w-xs md:max-w-sm rise">
        <div class="aspect-square rounded-3xl border-2 border-dashed border-white/40 flex items-center justify-center relative bg-white/5">
          <span class="text-7xl" x-text="currentCrop.emoji"></span>
          <div class="absolute inset-4 border border-white/20 rounded-2xl"></div>
        </div>
        <p class="text-center text-[13px] text-white/70 mt-5">Đưa lá <span x-text="selectedCrop.toLowerCase()"></span> vào khung hình, giữ ổn định camera</p>
      </div>
    </template>
    <template x-if="scanState==='analyzing'">
      <div class="flex flex-col items-center rise">
        <div class="scan-ring w-40 h-40 flex items-center justify-center" :style="`--p:${scanProgress}`">
          <div class="w-32 h-32 rounded-full bg-[var(--forest)] flex items-center justify-center font-display font-bold text-2xl font-mono" x-text="scanProgress+'%'"></div>
        </div>
        <p class="text-[13px] text-white/80 mt-6 pulse" x-text="scanLabel"></p>
      </div>
    </template>
  </div>
  <div class="pb-14 px-8 max-w-xs md:max-w-sm mx-auto w-full" x-show="scanState==='idle'">
    <button @click="scanState='analyzing'; runScan()" class="btn-press w-full bg-white text-[var(--forest)] font-semibold rounded-2xl py-4 text-[15px]">
      Chụp ảnh
    </button>
  </div>
</div>

<!-- ================= MOBILE BOTTOM NAV ================= -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur border-t border-black/5 pt-2.5 pb-7 px-6 flex justify-between z-40" x-show="tab!=='scan'" x-cloak>
  <button @click="tab='home'" class="navbtn flex flex-col items-center gap-1 flex-1" :class="tab==='home'||tab==='result' ? 'active':'text-[#9AA9A0]'">
    <span class="text-[18px]">🌱</span><span class="text-[10px] font-semibold">Cây trồng</span>
  </button>
  <button @click="tab='yield'" class="navbtn flex flex-col items-center gap-1 flex-1" :class="tab==='yield' ? 'active':'text-[#9AA9A0]'">
    <span class="text-[18px]">📈</span><span class="text-[10px] font-semibold">Năng suất</span>
  </button>
  <button @click="startScan()" class="flex flex-col items-center -mt-7">
    <span class="btn-press w-14 h-14 rounded-full bg-[var(--forest)] text-white flex items-center justify-center text-2xl shadow-lg shadow-[var(--forest)]/30">📷</span>
  </button>
  <button @click="tab='diary'" class="navbtn flex flex-col items-center gap-1 flex-1" :class="tab==='diary' ? 'active':'text-[#9AA9A0]'">
    <span class="text-[18px]">🗺️</span><span class="text-[10px] font-semibold">Nhật ký</span>
  </button>
  <button @click="tab='profile'" class="navbtn flex flex-col items-center gap-1 flex-1" :class="tab==='profile' ? 'active':'text-[#9AA9A0]'">
    <span class="text-[18px]">👤</span><span class="text-[10px] font-semibold">Cá nhân</span>
  </button>
</div>

<!-- ================= TOAST ================= -->
<div x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3"
     x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-3"
     x-cloak class="fixed left-1/2 -translate-x-1/2 bottom-24 md:bottom-8 z-50 bg-[var(--forest)] text-white text-[13px] font-semibold px-5 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 max-w-[90vw]">
  <span class="text-[15px]">✓</span>
  <span x-text="toast.message"></span>
</div>

<script>
function agriApp(){
  return {
    tab:'home',
    selectedCrop:'Lúa',
    crops:[
      {name:'Lúa', emoji:'🌾'},
      {name:'Ngô', emoji:'🌽'},
      {name:'Sắn', emoji:'🍠'},
      {name:'Cà chua', emoji:'🍅'},
      {name:'Khoai tây', emoji:'🥔'},
      {name:'Nho', emoji:'🍇'},
      {name:'Táo', emoji:'🍎'},
      {name:'Chè', emoji:'🍃'},
      {name:'Cà phê', emoji:'🫘'},
      {name:'Xoài', emoji:'🥭'},
    ],
    get currentCrop(){
      return this.crops.find(c=>c.name===this.selectedCrop) || this.crops[0];
    },

    fields:[
      {id:1, name:'Ruộng lúa số 1 - Đông Anh', crop:'Lúa', area:'0.8 ha', status:'warning', emoji:'🌾', x:28, y:65},
      {id:2, name:'Vườn chè Tân Cương', crop:'Chè', area:'1.2 ha', status:'healthy', emoji:'🍃', x:60, y:35},
      {id:3, name:'Vườn cà chua nhà kính', crop:'Cà chua', area:'0.3 ha', status:'danger', emoji:'🍅', x:78, y:70},
    ],
    selectedField:null,
    init(){ this.selectedField = this.fields[0]; },
    profileMenu:[
      {emoji:'✏️', label:'Chỉnh sửa hồ sơ'},
      {emoji:'🔔', label:'Cài đặt thông báo'},
      {emoji:'🌐', label:'Ngôn ngữ · Tiếng Việt'},
      {emoji:'💾', label:'Dữ liệu ngoại tuyến'},
      {emoji:'❓', label:'Trợ giúp & phản hồi'},
      {emoji:'🚪', label:'Đăng xuất'},
    ],
    toast:{show:false, message:''},
    showToast(msg){
      this.toast = {show:true, message:msg};
      clearTimeout(this._toastTimer);
      this._toastTimer = setTimeout(()=>{ this.toast.show=false; }, 2600);
    },

    alerts:[
      {id:1, emoji:'🐛', field:'Ruộng lúa số 1', issue:'Nghi phát hiện đạo ôn lá', level:'Nặng', time:'2 giờ trước'},
      {id:2, emoji:'🍅', field:'Vườn cà chua nhà kính', issue:'Xuất hiện đốm nâu lá', level:'Trung bình', time:'Hôm qua'},
    ],

    history:[
      {id:1, emoji:'🌾', crop:'Lúa', field:'Ruộng lúa số 1', disease:'Đạo ôn lá', date:'08/08', level:'Nặng'},
      {id:2, emoji:'🍃', crop:'Chè', field:'Vườn chè Tân Cương', disease:'Cây khỏe mạnh', date:'06/08', level:'Nhẹ'},
      {id:3, emoji:'🍅', crop:'Cà chua', field:'Vườn cà chua nhà kính', disease:'Đốm nâu lá', date:'05/08', level:'Trung bình'},
      {id:4, emoji:'🌾', crop:'Lúa', field:'Ruộng lúa số 1', disease:'Cây khỏe mạnh', date:'01/08', level:'Nhẹ'},
      {id:5, emoji:'🍃', crop:'Chè', field:'Vườn chè Tân Cương', disease:'Rầy xanh gây hại', date:'29/07', level:'Trung bình'},
      {id:6, emoji:'🍅', crop:'Cà chua', field:'Vườn cà chua nhà kính', disease:'Héo xanh vi khuẩn', date:'24/07', level:'Nặng'},
    ],
    diaryFilter:'Tất cả',
    get filteredHistory(){
      if(this.diaryFilter==='Tất cả') return this.history;
      return this.history.filter(h=>h.level===this.diaryFilter);
    },

    diseaseDB:{
      'Lúa': {emoji:'🍂', disease:'Đạo ôn lá', nameEn:'Rice Blast', confidence:'94.2%', level:'Nặng', spread:78,
        steps:['Phun thuốc gốc Tricyclazole trong vòng 24–48 giờ tới','Giảm bón đạm, tăng kali để hạn chế lây lan','Theo dõi lại sau 5 ngày, chụp ảnh so sánh']},
      'Ngô': {emoji:'🌽', disease:'Khô vằn lá ngô', nameEn:'Southern Corn Leaf Blight', confidence:'91.5%', level:'Trung bình', spread:52,
        steps:['Phun thuốc gốc Azoxystrobin khi phát hiện triệu chứng đầu tiên','Luân canh cây trồng vụ sau để cắt nguồn bệnh','Vệ sinh tàn dư cây bệnh sau thu hoạch']},
      'Sắn': {emoji:'🍠', disease:'Khảm lá sắn', nameEn:'Cassava Mosaic Disease', confidence:'96.1%', level:'Nặng', spread:85,
        steps:['Nhổ bỏ và tiêu hủy cây bị bệnh nặng để tránh lây lan','Dùng giống sắn kháng bệnh cho vụ sau','Kiểm soát bọ phấn trắng — trung gian truyền bệnh']},
      'Cà chua': {emoji:'🍅', disease:'Đốm nâu lá', nameEn:'Septoria Leaf Spot', confidence:'89.7%', level:'Trung bình', spread:44,
        steps:['Cắt tỉa lá bệnh, tránh tưới lên tán lá','Phun thuốc gốc đồng (Copper-based fungicide)','Tăng khoảng cách trồng để thông thoáng']},
      'Khoai tây': {emoji:'🥔', disease:'Mốc sương', nameEn:'Late Blight', confidence:'93.4%', level:'Nặng', spread:70,
        steps:['Phun thuốc gốc Metalaxyl ngay khi phát hiện','Tránh tưới vào chiều tối, giữ ruộng thoáng khí','Thu hoạch sớm nếu bệnh lan rộng trên 50% diện tích']},
      'Nho': {emoji:'🍇', disease:'Thán thư', nameEn:'Black Rot', confidence:'90.2%', level:'Trung bình', spread:38,
        steps:['Loại bỏ quả và lá bị nhiễm bệnh khỏi vườn','Phun thuốc phòng trước mùa mưa','Cắt tỉa tán để tăng lưu thông không khí']},
      'Táo': {emoji:'🍎', disease:'Ghẻ táo', nameEn:'Apple Scab', confidence:'88.9%', level:'Nhẹ', spread:22,
        steps:['Phun thuốc phòng vào đầu vụ xuân','Dọn sạch lá rụng dưới gốc vào mùa đông','Chọn giống kháng bệnh cho lứa trồng mới']},
      'Chè': {emoji:'🍃', disease:'Cây khỏe mạnh', nameEn:'Healthy', confidence:'97.8%', level:'Nhẹ', spread:0,
        steps:['Duy trì chế độ chăm sóc hiện tại','Kiểm tra định kỳ 2 tuần/lần vào mùa mưa','Bón phân cân đối NPK theo giai đoạn sinh trưởng']},
      'Cà phê': {emoji:'☕', disease:'Gỉ sắt lá cà phê', nameEn:'Coffee Leaf Rust', confidence:'92.6%', level:'Trung bình', spread:48,
        steps:['Phun thuốc gốc đồng hoặc Triazole','Tỉa cành tạo tán thông thoáng, giảm độ ẩm','Bón phân cân đối, tránh dư đạm']},
      'Xoài': {emoji:'🥭', disease:'Thán thư xoài', nameEn:'Anthracnose', confidence:'90.8%', level:'Trung bình', spread:41,
        steps:['Phun thuốc gốc Mancozeb trước và sau ra hoa','Tỉa cành thông thoáng, loại bỏ quả bệnh rụng','Tránh tưới nước lên hoa và quả non']},
    },
    get currentResult(){
      return this.diseaseDB[this.selectedCrop] || this.diseaseDB['Lúa'];
    },

    yieldForm:{area:1, n:90, p:42, k:43, temp:26, rainfall:180, humidity:75},
    yieldResult:null,
    yieldBaseline:{
      'Lúa':6.2, 'Ngô':6.8, 'Sắn':21, 'Cà chua':35, 'Khoai tây':18,
      'Nho':12, 'Táo':15, 'Chè':8.5, 'Cà phê':2.8, 'Xoài':9,
    },
    predictYield(){
      const base = this.yieldBaseline[this.selectedCrop] || 6;
      const f = this.yieldForm;
      // demo heuristic: điều chỉnh baseline theo N/P/K, nhiệt độ, mưa, độ ẩm quanh mức lý tưởng
      let mult = 1;
      mult += (f.n - 90) / 900;
      mult += (f.p - 42) / 900;
      mult += (f.k - 43) / 900;
      mult -= Math.abs(f.temp - 26) * 0.012;
      mult -= Math.abs(f.rainfall - 180) * 0.0008;
      mult += (f.humidity - 75) / 700;
      mult = Math.max(0.55, Math.min(1.35, mult));

      const perHa = +(base * mult).toFixed(2);
      const total = +(perHa * (f.area || 1)).toFixed(2);
      const scorePct = Math.round(Math.min(100, Math.max(8, mult * 74)));
      const diffPct = Math.round((mult - 1) * 100);

      this.yieldResult = {
        perHa, total, scorePct,
        comparison: diffPct >= 0 ? `Cao hơn ${diffPct}%` : `Thấp hơn ${Math.abs(diffPct)}%`,
        factors:[
          {label:'Dinh dưỡng N-P-K', value: (f.n+f.p+f.k) > 165 ? 'Thuận lợi' : 'Cần bổ sung', positive: (f.n+f.p+f.k) > 165},
          {label:'Nhiệt độ', value: Math.abs(f.temp-26)<=3 ? 'Phù hợp' : 'Lệch mức lý tưởng', positive: Math.abs(f.temp-26)<=3},
          {label:'Độ ẩm đất', value: f.humidity>=60 ? 'Đủ ẩm' : 'Thiếu ẩm', positive: f.humidity>=60},
        ]
      };
    },

    scanState:'idle', scanProgress:0, scanLabel:'Đang phân tích ảnh...',
    startScan(){ this.tab='scan'; this.scanState='idle'; this.scanProgress=0; },
    runScan(){
      const labels=['Đang phân tích ảnh...','Nhận diện vùng tổn thương...','Đối chiếu mô hình AI...','Hoàn tất chẩn đoán'];
      const interval=setInterval(()=>{
        this.scanProgress+=4;
        const idx=Math.min(Math.floor(this.scanProgress/26),labels.length-1);
        this.scanLabel=labels[idx];
        if(this.scanProgress>=100){
          clearInterval(interval);
          setTimeout(()=>{ this.tab='result'; this.scanState='idle'; }, 400);
        }
      },70);
    }
  }
}
</script>
</body>
</html>