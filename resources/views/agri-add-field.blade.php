<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Thêm ruộng/vườn</title>
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
  .btn-press:active{transform:scale(.97);}
  .btn-press{transition:transform .12s ease;}
  @keyframes riseFade{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
  .rise{animation:riseFade .45s cubic-bezier(.16,1,.3,1) both;}
  .step-dot{width:8px;height:8px;border-radius:9999px;background:#D8E3DB;transition:all .25s ease;}
  .step-dot.active{background:var(--forest);width:22px;}
  [x-cloak]{display:none!important;}
</style>
</head>
<body x-data="addFieldFlow()" class="min-h-screen flex items-start md:items-center justify-center p-5">

<div class="w-full max-w-md">

  <div class="flex items-center gap-2 mb-5">
    <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold text-[var(--forest)] mr-auto">← Hủy</a>
    <template x-for="i in 3" :key="i">
      <span class="step-dot" :class="step>=i ? 'active' : ''"></span>
    </template>
  </div>

  <!-- STEP 1: crop + name -->
  <div x-show="step===1" x-cloak class="card p-6 rise">
    <h2 class="font-display font-bold text-[19px]">Thêm ruộng/vườn mới</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Bước 1/3 — Thông tin cơ bản</p>

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-5">Tên ruộng/vườn</label>
    <input type="text" x-model="form.name" placeholder="VD: Ruộng lúa số 2 - Đông Anh" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-4">Loại cây trồng</label>
    <div class="flex flex-wrap gap-2 mt-2">
      <template x-for="c in crops" :key="c.name">
        <button @click="form.crop=c.name" class="chip btn-press flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[var(--mist)] text-[12px] font-semibold"
                :class="form.crop===c.name ? 'chip active' : ''">
          <span x-text="c.emoji"></span><span x-text="c.name"></span>
        </button>
      </template>
    </div>

    <button @click="step=2" :disabled="!form.name || !form.crop" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px] disabled:opacity-40">
      Tiếp tục
    </button>
  </div>

  <!-- STEP 2: area + location -->
  <div x-show="step===2" x-cloak class="card p-6 rise">
    <h2 class="font-display font-bold text-[19px]">Diện tích & vị trí</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Bước 2/3</p>

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-5">Diện tích (ha)</label>
    <input type="number" step="0.1" x-model.number="form.area" placeholder="0.8" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-4">Vị trí trên bản đồ</label>
    <div class="relative mt-1.5 rounded-2xl overflow-hidden" style="height:180px;background:linear-gradient(135deg, var(--leaf-soft) 0%, var(--mist) 100%);">
      <button @click="pinPlaced=true" class="absolute" :style="`left:${pinX}%; top:${pinY}%`"
              @click.stop="" x-ref="pinArea">
      </button>
      <div class="absolute inset-0 cursor-crosshair" @click="placePin($event)"></div>
      <template x-if="pinPlaced">
        <span class="absolute text-2xl -translate-x-1/2 -translate-y-full pointer-events-none" :style="`left:${pinX}%; top:${pinY}%`">📍</span>
      </template>
      <template x-if="!pinPlaced">
        <p class="absolute inset-0 flex items-center justify-center text-[12px] text-[#5B6F63] font-medium">Chạm vào bản đồ để đánh dấu vị trí</p>
      </template>
    </div>

    <div class="flex gap-3 mt-6">
      <button @click="step=1" class="btn-press flex-1 bg-[var(--mist)] text-[var(--forest)] font-semibold rounded-2xl py-3.5 text-[14px]">Quay lại</button>
      <button @click="step=3" :disabled="!form.area" class="btn-press flex-1 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px] disabled:opacity-40">Tiếp tục</button>
    </div>
  </div>

  <!-- STEP 3: confirm -->
  <div x-show="step===3" x-cloak class="card p-6 rise">
    <h2 class="font-display font-bold text-[19px]">Xác nhận thông tin</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Bước 3/3</p>

    <div class="mt-5 bg-[var(--mist)] rounded-2xl p-4 space-y-3">
      <div class="flex justify-between text-[13px]"><span class="text-[#6B7C71]">Tên</span><span class="font-semibold" x-text="form.name || '—'"></span></div>
      <div class="flex justify-between text-[13px]"><span class="text-[#6B7C71]">Cây trồng</span><span class="font-semibold" x-text="form.crop || '—'"></span></div>
      <div class="flex justify-between text-[13px]"><span class="text-[#6B7C71]">Diện tích</span><span class="font-semibold font-mono" x-text="(form.area || 0)+' ha'"></span></div>
      <div class="flex justify-between text-[13px]"><span class="text-[#6B7C71]">Vị trí</span><span class="font-semibold" x-text="pinPlaced ? 'Đã đánh dấu' : 'Chưa đánh dấu'"></span></div>
    </div>

    <div class="flex gap-3 mt-6">
      <button @click="step=2" class="btn-press flex-1 bg-[var(--mist)] text-[var(--forest)] font-semibold rounded-2xl py-3.5 text-[14px]">Quay lại</button>
      <button @click="submitField()" class="btn-press flex-1 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">Hoàn tất</button>
    </div>
  </div>

  <!-- DONE -->
  <div x-show="step===4" x-cloak class="card p-8 rise text-center">
    <div class="w-16 h-16 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-3xl mx-auto">✓</div>
    <h2 class="font-display font-bold text-[18px] mt-4">Đã thêm ruộng mới!</h2>
    <p class="text-[13px] text-[#5B6F63] mt-1.5"><span x-text="form.name"></span> đã sẵn sàng theo dõi</p>
    <a href="{{ route('agri.index') }}" class="btn-press block w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">Về trang chủ</a>
  </div>
</div>

<script>
function addFieldFlow(){
  return {
    step:1,
    form:{name:'', crop:'', area:null},
    pinPlaced:false, pinX:50, pinY:50,
    crops:[
      {name:'Lúa', emoji:'🌾'}, {name:'Ngô', emoji:'🌽'}, {name:'Sắn', emoji:'🍠'},
      {name:'Cà chua', emoji:'🍅'}, {name:'Chè', emoji:'🍃'},
    ],
    placePin(e){
      const rect = e.currentTarget.getBoundingClientRect();
      this.pinX = ((e.clientX - rect.left) / rect.width) * 100;
      this.pinY = ((e.clientY - rect.top) / rect.height) * 100;
      this.pinPlaced = true;
    },
    submitField(){ this.step=4; }
  }
}
</script>
</body>
</html>