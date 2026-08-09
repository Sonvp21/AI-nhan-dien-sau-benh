<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Thư viện sâu bệnh</title>
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
<body x-data="libraryFlow()" class="min-h-screen">

<header class="flex items-center justify-between px-5 md:px-10 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-10">
  <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold text-[var(--forest)]">← Trang chủ</a>
</header>

<main class="max-w-5xl mx-auto px-5 md:px-10 pb-16">
  <h1 class="font-display font-bold text-[22px] md:text-[28px] text-[var(--forest)] mt-2 rise">Thư viện sâu bệnh</h1>
  <p class="text-[13px] text-[#5B6F63] mt-1 rise">Tra cứu triệu chứng & cách xử lý theo từng loại cây</p>

  <!-- search -->
  <div class="mt-4 rise">
    <input type="text" x-model="query" placeholder="Tìm theo tên bệnh, ví dụ: đạo ôn, mốc sương..."
           class="w-full bg-white rounded-2xl px-4 py-3 text-[13px] outline-none focus:ring-2 ring-[var(--forest)] shadow-sm">
  </div>

  <!-- crop filter -->
  <div class="flex flex-wrap gap-2 mt-4 rise">
    <template x-for="c in ['Tất cả','Lúa','Ngô','Sắn','Cà chua','Chè']" :key="c">
      <button @click="cropFilter=c" class="chip btn-press px-3.5 py-2 rounded-xl bg-white text-[12px] font-semibold"
              :class="cropFilter===c ? 'chip active' : ''" x-text="c"></button>
    </template>
  </div>

  <!-- grid -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
    <template x-for="d in filtered" :key="d.id">
      <button @click="selected=d" class="card p-5 text-left rise hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-3xl" x-text="d.emoji"></span>
          <span class="text-[10px] font-bold font-mono px-2 py-1 rounded-full shrink-0"
                :class="d.level==='Nặng' ? 'bg-[var(--danger)]/10 text-[var(--danger)]' : d.level==='Nhẹ' ? 'bg-[var(--leaf-soft)] text-[var(--forest)]' : 'bg-[var(--soil-soft)] text-[var(--soil)]'"
                x-text="d.level"></span>
        </div>
        <p class="font-display font-bold text-[15px] mt-3" x-text="d.disease"></p>
        <p class="text-[11px] text-[#6B7C71] mt-0.5" x-text="d.crop+' · '+d.nameEn"></p>
        <p class="text-[12px] text-[#5B6F63] mt-2 line-clamp-2" x-text="d.symptom"></p>
      </button>
    </template>
    <template x-if="!filtered.length">
      <div class="card p-10 text-center text-[13px] text-[#6B7C71] md:col-span-3">Không tìm thấy kết quả phù hợp</div>
    </template>
  </div>
</main>

<!-- detail panel -->
<div x-show="selected" x-cloak @click.self="selected=null" class="fixed inset-0 bg-black/40 z-40 flex items-end md:items-center justify-center p-0 md:p-5">
  <div x-show="selected" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-6"
       x-transition:enter-end="opacity-100 translate-y-0" class="bg-white rounded-t-3xl md:rounded-3xl w-full md:max-w-md max-h-[85vh] overflow-y-auto p-6 md:p-7">
    <template x-if="selected">
      <div>
        <div class="flex items-start justify-between">
          <span class="text-5xl" x-text="selected.emoji"></span>
          <button @click="selected=null" class="w-8 h-8 rounded-full bg-[var(--mist)] flex items-center justify-center text-[14px]">✕</button>
        </div>
        <h2 class="font-display font-bold text-[20px] mt-3" x-text="selected.disease"></h2>
        <p class="text-[12px] text-[#6B7C71] mt-0.5" x-text="selected.crop+' · '+selected.nameEn"></p>
        <span class="inline-block text-[11px] font-bold font-mono px-2.5 py-1 rounded-full mt-2"
              :class="selected.level==='Nặng' ? 'bg-[var(--danger)]/10 text-[var(--danger)]' : selected.level==='Nhẹ' ? 'bg-[var(--leaf-soft)] text-[var(--forest)]' : 'bg-[var(--soil-soft)] text-[var(--soil)]'"
              x-text="selected.level"></span>

        <div class="mt-5">
          <p class="text-[12px] font-semibold mb-1.5">Triệu chứng</p>
          <p class="text-[13px] text-[#5B6F63] leading-relaxed" x-text="selected.symptom"></p>
        </div>

        <div class="mt-4">
          <p class="text-[12px] font-semibold mb-2">Cách xử lý</p>
          <div class="space-y-2.5">
            <template x-for="(s,i) in selected.steps" :key="i">
              <div class="flex gap-2.5">
                <div class="w-6 h-6 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-[11px] font-bold font-mono shrink-0" x-text="i+1"></div>
                <p class="text-[13px] leading-snug" x-text="s"></p>
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</div>

<script>
function libraryFlow(){
  return {
    query:'', cropFilter:'Tất cả', selected:null,
    diseases:[
      {id:1, crop:'Lúa', emoji:'🌾', disease:'Đạo ôn lá', nameEn:'Rice Blast', level:'Nặng',
        symptom:'Vết bệnh hình thoi, tâm xám trắng, viền nâu trên lá; lan nhanh trong điều kiện ẩm và nhiệt độ 24–28°C.',
        steps:['Phun thuốc gốc Tricyclazole trong 24–48 giờ','Giảm bón đạm, tăng kali','Theo dõi lại sau 5 ngày']},
      {id:2, crop:'Lúa', emoji:'🐛', disease:'Sâu cuốn lá nhỏ', nameEn:'Rice Leaf Folder', level:'Trung bình',
        symptom:'Lá bị cuốn thành ống, sâu non ăn phần thịt lá bên trong khiến lá trắng bạc, khô đầu.',
        steps:['Phun thuốc trừ sâu sinh học khi mật độ cao','Thăm đồng thường xuyên giai đoạn đẻ nhánh','Bảo vệ thiên địch, hạn chế phun tràn lan']},
      {id:3, crop:'Ngô', emoji:'🌽', disease:'Sâu keo mùa thu', nameEn:'Fall Armyworm', level:'Nặng',
        symptom:'Sâu non đục vào nõn ngô, để lại lỗ thủng hàng ngang trên lá non, phân sâu dạng mùn cưa.',
        steps:['Phun thuốc đặc trị khi phát hiện sớm','Luân canh, vệ sinh đồng ruộng sau vụ','Sử dụng bẫy pheromone theo dõi mật độ']},
      {id:4, crop:'Sắn', emoji:'🍠', disease:'Khảm lá sắn', nameEn:'Cassava Mosaic Disease', level:'Nặng',
        symptom:'Lá loang lổ vàng xanh không đều, phiến lá biến dạng nhăn nheo, cây còi cọc.',
        steps:['Nhổ bỏ và tiêu hủy cây bệnh nặng','Dùng giống kháng bệnh cho vụ sau','Kiểm soát bọ phấn trắng trung gian truyền bệnh']},
      {id:5, crop:'Cà chua', emoji:'🍅', disease:'Đốm nâu lá', nameEn:'Septoria Leaf Spot', level:'Trung bình',
        symptom:'Đốm tròn nhỏ màu nâu viền vàng, xuất hiện trước ở lá già rồi lan dần lên trên.',
        steps:['Cắt tỉa lá bệnh, tránh tưới lên tán lá','Phun thuốc gốc đồng','Tăng khoảng cách trồng để thông thoáng']},
      {id:6, crop:'Chè', emoji:'🍃', disease:'Rầy xanh gây hại', nameEn:'Tea Green Leafhopper', level:'Trung bình',
        symptom:'Lá non bị chích hút, mép lá cong xuống, chuyển màu nâu đỏ như bị cháy nắng.',
        steps:['Phun thuốc sinh học đúng ngưỡng mật độ','Hái đọt kịp thời để cắt nguồn thức ăn','Bón phân cân đối tránh dư đạm']},
    ],
    get filtered(){
      return this.diseases.filter(d=>{
        const matchCrop = this.cropFilter==='Tất cả' || d.crop===this.cropFilter;
        const q = this.query.trim().toLowerCase();
        const matchQuery = !q || d.disease.toLowerCase().includes(q) || d.nameEn.toLowerCase().includes(q);
        return matchCrop && matchQuery;
      });
    }
  }
}
</script>
</body>
</html>