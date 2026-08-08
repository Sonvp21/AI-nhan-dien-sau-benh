<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Thông báo</title>
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
  .chip.active{background:var(--forest);color:#fff;}
  .btn-press:active{transform:scale(.97);}
  .btn-press{transition:transform .12s ease;}
  @keyframes riseFade{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
  .rise{animation:riseFade .4s cubic-bezier(.16,1,.3,1) both;}
  [x-cloak]{display:none!important;}
</style>
</head>
<body x-data="notifFlow()" class="min-h-screen">

<header class="flex items-center justify-between px-5 md:px-10 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-10">
  <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold text-[var(--forest)]">← Trang chủ</a>
  <button @click="markAllRead()" class="text-[12px] font-semibold text-[var(--forest)]">Đánh dấu đã đọc</button>
</header>

<main class="max-w-2xl mx-auto px-5 md:px-10 pb-16">
  <h1 class="font-display font-bold text-[22px] md:text-[28px] text-[var(--forest)] mt-2 rise">Thông báo</h1>

  <div class="flex flex-wrap gap-2 mt-4 rise">
    <template x-for="f in ['Tất cả','Cảnh báo','Nhắc nhở','Hệ thống']" :key="f">
      <button @click="filter=f" class="chip btn-press px-4 py-2 rounded-xl bg-white text-[12px] font-semibold"
              :class="filter===f ? 'chip active' : ''" x-text="f"></button>
    </template>
  </div>

  <div class="mt-5 space-y-2.5">
    <template x-for="n in filteredNotifs" :key="n.id">
      <div @click="n.read=true" class="card p-4 flex items-center gap-3.5 rise cursor-pointer relative"
           :class="!n.read ? 'ring-2 ring-[var(--forest)]/15' : ''">
        <span class="w-1.5 h-1.5 rounded-full bg-[var(--forest)] absolute top-4 right-4" x-show="!n.read"></span>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg shrink-0"
             :class="n.type==='Cảnh báo' ? 'bg-[var(--danger)]/10' : n.type==='Nhắc nhở' ? 'bg-[var(--soil-soft)]' : 'bg-[var(--leaf-soft)]'"
             x-text="n.emoji"></div>
        <div class="flex-1 min-w-0 pr-3">
          <p class="text-[13px] font-semibold" x-text="n.title"></p>
          <p class="text-[12px] text-[#6B7C71] mt-0.5" x-text="n.body"></p>
          <p class="text-[11px] font-mono text-[#9AA9A0] mt-1" x-text="n.time"></p>
        </div>
      </div>
    </template>
    <template x-if="!filteredNotifs.length">
      <div class="card p-10 text-center text-[13px] text-[#6B7C71]">Không có thông báo nào</div>
    </template>
  </div>
</main>

<script>
function notifFlow(){
  return {
    filter:'Tất cả',
    notifs:[
      {id:1, type:'Cảnh báo', emoji:'🐛', title:'Nghi phát hiện đạo ôn lá', body:'Ruộng lúa số 1 - Đông Anh · Mức nặng', time:'2 giờ trước', read:false},
      {id:2, type:'Cảnh báo', emoji:'🍅', title:'Xuất hiện đốm nâu lá', body:'Vườn cà chua nhà kính · Mức trung bình', time:'Hôm qua', read:false},
      {id:3, type:'Nhắc nhở', emoji:'💧', title:'Đến lịch tưới nước', body:'Vườn chè Tân Cương cần tưới trong hôm nay', time:'Hôm qua', read:true},
      {id:4, type:'Nhắc nhở', emoji:'📸', title:'Đã 5 ngày chưa chụp ảnh theo dõi', body:'Ruộng lúa số 1 cần cập nhật tình trạng bệnh', time:'2 ngày trước', read:true},
      {id:5, type:'Hệ thống', emoji:'🧠', title:'Mô hình AI đã cập nhật', body:'Cải thiện độ chính xác nhận diện bệnh lúa lên 96%', time:'3 ngày trước', read:true},
      {id:6, type:'Hệ thống', emoji:'🌦️', title:'Cảnh báo thời tiết khu vực', body:'Mưa lớn dự kiến trong 48 giờ tới tại Thái Nguyên', time:'4 ngày trước', read:true},
    ],
    get filteredNotifs(){
      if(this.filter==='Tất cả') return this.notifs;
      return this.notifs.filter(n=>n.type===this.filter);
    },
    markAllRead(){ this.notifs.forEach(n=>n.read=true); }
  }
}
</script>
</body>
</html>