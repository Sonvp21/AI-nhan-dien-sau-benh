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
    <button class="text-[#6B7C71]">Năng suất</button>
    <button class="text-[#6B7C71]">Ruộng của tôi</button>
    <button class="text-[#6B7C71]">Cộng đồng</button>
  </nav>
  <div class="flex items-center gap-3">
    <button @click="startScan()" class="btn-press bg-[var(--forest)] text-white text-[13px] font-semibold px-4 py-2.5 rounded-xl flex items-center gap-2">
      📷 Chẩn đoán ngay
    </button>
    <div class="w-9 h-9 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center">👤</div>
  </div>
</header>

<!-- ================= MOBILE TOP BAR (<768px) ================= -->
<header class="md:hidden flex items-center justify-between px-5 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-40" x-show="tab!=='scan'">
  <div>
    <p class="text-[12px] text-[#5B6F63]">Xin chào,</p>
    <h1 class="font-display font-bold text-[19px] text-[var(--forest)]">Anh Sơn 🌾</h1>
  </div>
  <div class="w-10 h-10 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center">👤</div>
</header>

<!-- ================= MAIN ================= -->
<main class="max-w-6xl mx-auto px-5 md:px-10 pb-28 md:pb-16" x-show="tab!=='scan'">

  <!-- HOME -->
  <div x-show="tab==='home'" x-cloak>

    <!-- desktop hero -->
    <div class="hidden md:flex items-center justify-between mt-8 mb-8 rise rise-1">
      <div>
        <p class="text-[14px] text-[#5B6F63] font-medium">Thái Nguyên · Hôm nay 27°C, mưa rào nhẹ 🌦️</p>
        <h1 class="font-display font-bold text-[34px] text-[var(--forest)] mt-1">Chào Sơn, ruộng lúa của bạn thế nào?</h1>
      </div>
    </div>

    <!-- crop chips -->
    <div class="flex flex-wrap gap-2.5 mt-4 md:mt-0 pt-2 pb-1 rise rise-2">
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
        <h2 class="font-display font-bold text-[16px] md:text-[19px] mb-5">Chẩn đoán cây trồng bằng AI</h2>
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
          Chụp ảnh ngay
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

      <!-- quick actions -->
      <button class="btn-press card flex md:flex-col items-center gap-3 md:gap-2 p-4 md:py-6">
        <span class="text-xl md:text-2xl">📊</span>
        <span class="text-[13px] md:text-[13px] font-semibold text-left md:text-center leading-tight">Dự báo năng suất</span>
      </button>
      <button class="btn-press card flex md:flex-col items-center gap-3 md:gap-2 p-4 md:py-6">
        <span class="text-xl md:text-2xl">🐛</span>
        <span class="text-[13px] font-semibold text-left md:text-center leading-tight">Sâu bệnh & giải pháp</span>
      </button>
      <button class="btn-press card flex md:flex-col items-center gap-3 md:gap-2 p-4 md:py-6">
        <span class="text-xl md:text-2xl">📍</span>
        <span class="text-[13px] font-semibold text-left md:text-center leading-tight">Bản đồ đồng ruộng</span>
      </button>
    </div>
  </div>

  <!-- RESULT -->
  <div x-show="tab==='result'" x-cloak class="mt-6 md:mt-8">
    <button @click="tab='home'" class="flex items-center gap-1 text-[13px] font-semibold text-[var(--forest)] rise rise-1">← Về trang chủ</button>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
      <div class="card overflow-hidden md:col-span-2 rise rise-2">
        <div class="h-40 md:h-56 bg-gradient-to-br from-[var(--leaf-soft)] to-[var(--mist)] flex items-center justify-center text-6xl md:text-8xl">🍂</div>
        <div class="p-5 md:p-7">
          <div class="flex items-center justify-between">
            <h2 class="font-display font-bold text-[18px] md:text-[22px]">Đạo ôn lá lúa</h2>
            <span class="text-[11px] font-bold font-mono bg-[var(--soil-soft)] text-[var(--soil)] px-2.5 py-1 rounded-full">Mức nặng</span>
          </div>
          <p class="text-[12px] md:text-[13px] text-[#5B6F63] mt-1">Rice Blast · Độ tin cậy 94.2%</p>
          <div class="mt-4 h-2 rounded-full bg-[#E4EEE7] overflow-hidden">
            <div class="h-full bg-[var(--danger)] rounded-full" style="width:78%"></div>
          </div>
          <p class="text-[11px] md:text-[12px] text-[#5B6F63] mt-1.5">Mức độ lây lan ước tính: 78% khu vực</p>
        </div>
      </div>

      <div class="card p-5 md:p-7 rise rise-3">
        <h3 class="font-display font-bold text-[14px] md:text-[15px] mb-3">Khuyến nghị xử lý</h3>
        <div class="space-y-3">
          <div class="flex gap-3">
            <div class="w-7 h-7 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-[12px] font-bold font-mono shrink-0">1</div>
            <p class="text-[13px] leading-snug">Phun thuốc gốc Tricyclazole trong vòng 24–48 giờ tới</p>
          </div>
          <div class="flex gap-3">
            <div class="w-7 h-7 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-[12px] font-bold font-mono shrink-0">2</div>
            <p class="text-[13px] leading-snug">Giảm bón đạm, tăng kali để hạn chế lây lan</p>
          </div>
          <div class="flex gap-3">
            <div class="w-7 h-7 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-[12px] font-bold font-mono shrink-0">3</div>
            <p class="text-[13px] leading-snug">Theo dõi lại sau 5 ngày, chụp ảnh so sánh</p>
          </div>
        </div>
        <button class="btn-press w-full mt-5 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3 text-[13px]">
          Lưu vào nhật ký ruộng
        </button>
      </div>
    </div>
  </div>
</main>

<!-- ================= SCAN (fullscreen both) ================= -->
<div x-show="tab==='scan'" x-cloak class="fixed inset-0 bg-[var(--forest)] text-white z-50 flex flex-col">
  <button @click="tab='home'; scanState='idle'" class="absolute top-6 left-6 z-10 w-9 h-9 rounded-full bg-white/15 flex items-center justify-center">←</button>
  <div class="flex-1 flex flex-col items-center justify-center px-8">
    <template x-if="scanState==='idle'">
      <div class="w-full max-w-xs md:max-w-sm rise">
        <div class="aspect-square rounded-3xl border-2 border-dashed border-white/40 flex items-center justify-center relative bg-white/5">
          <span class="text-7xl">🍃</span>
          <div class="absolute inset-4 border border-white/20 rounded-2xl"></div>
        </div>
        <p class="text-center text-[13px] text-white/70 mt-5">Đưa lá cây vào khung hình, giữ ổn định camera</p>
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
  <button class="navbtn flex flex-col items-center gap-1 flex-1 text-[#9AA9A0]">
    <span class="text-[18px]">📈</span><span class="text-[10px] font-semibold">Năng suất</span>
  </button>
  <button @click="startScan()" class="flex flex-col items-center -mt-7">
    <span class="btn-press w-14 h-14 rounded-full bg-[var(--forest)] text-white flex items-center justify-center text-2xl shadow-lg shadow-[var(--forest)]/30">📷</span>
  </button>
  <button class="navbtn flex flex-col items-center gap-1 flex-1 text-[#9AA9A0]">
    <span class="text-[18px]">🗺️</span><span class="text-[10px] font-semibold">Ruộng</span>
  </button>
  <button class="navbtn flex flex-col items-center gap-1 flex-1 text-[#9AA9A0]">
    <span class="text-[18px]">👤</span><span class="text-[10px] font-semibold">Cá nhân</span>
  </button>
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