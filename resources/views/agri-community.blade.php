<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Cộng đồng</title>
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
  @keyframes heartPop{0%{transform:scale(1);}40%{transform:scale(1.35);}100%{transform:scale(1);}}
  .heart-pop{animation:heartPop .35s ease;}
  [x-cloak]{display:none!important;}
</style>
</head>
<body x-data="communityFlow()" class="min-h-screen">

<header class="flex items-center justify-between px-5 md:px-10 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-10">
  <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold text-[var(--forest)]">← Trang chủ</a>
  <button @click="showComposer=true" class="btn-press bg-[var(--forest)] text-white text-[12px] font-semibold px-4 py-2 rounded-xl">+ Đăng bài</button>
</header>

<main class="max-w-2xl mx-auto px-5 md:px-10 pb-16">
  <h1 class="font-display font-bold text-[22px] md:text-[28px] text-[var(--forest)] mt-2 rise">Cộng đồng nông dân</h1>
  <p class="text-[13px] text-[#5B6F63] mt-1 rise">Chia sẻ kinh nghiệm, hỏi đáp cùng nông dân khắp cả nước</p>

  <div class="flex flex-wrap gap-2 mt-4 rise">
    <template x-for="c in ['Tất cả','Hỏi đáp','Kinh nghiệm','Mùa vụ']" :key="c">
      <button @click="filter=c" class="chip btn-press px-4 py-2 rounded-xl bg-white text-[12px] font-semibold"
              :class="filter===c ? 'chip active' : ''" x-text="c"></button>
    </template>
  </div>

  <div class="mt-5 space-y-4">
    <template x-for="p in filteredPosts" :key="p.id">
      <div class="card p-5 rise">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-lg shrink-0" x-text="p.avatar"></div>
          <div class="min-w-0">
            <p class="text-[13px] font-semibold truncate" x-text="p.author"></p>
            <p class="text-[11px] text-[#9AA9A0]" x-text="p.location+' · '+p.time"></p>
          </div>
          <span class="ml-auto text-[10px] font-bold font-mono px-2 py-1 rounded-full bg-[var(--mist)] text-[var(--forest)] shrink-0" x-text="p.tag"></span>
        </div>

        <p class="text-[13px] leading-relaxed mt-3" x-text="p.content"></p>

        <template x-if="p.image">
          <div class="mt-3 h-40 rounded-2xl bg-gradient-to-br from-[var(--leaf-soft)] to-[var(--mist)] flex items-center justify-center text-5xl" x-text="p.image"></div>
        </template>

        <div class="flex items-center gap-5 mt-4 pt-3 border-t border-black/5">
          <button @click="toggleLike(p)" class="flex items-center gap-1.5 text-[12px] font-semibold" :class="p.liked ? 'text-[var(--danger)]' : 'text-[#6B7C71]'">
            <span :class="p.liked ? 'heart-pop' : ''" x-text="p.liked ? '❤️' : '🤍'"></span>
            <span x-text="p.likes"></span>
          </button>
          <button @click="p.showComments=!p.showComments" class="flex items-center gap-1.5 text-[12px] font-semibold text-[#6B7C71]">
            💬 <span x-text="p.comments.length+' bình luận'"></span>
          </button>
        </div>

        <!-- comments -->
        <div x-show="p.showComments" x-cloak class="mt-3 pt-3 border-t border-black/5 space-y-2.5">
          <template x-for="c in p.comments" :key="c.id">
            <div class="flex gap-2.5">
              <div class="w-7 h-7 rounded-full bg-[var(--mist)] flex items-center justify-center text-[12px] shrink-0" x-text="c.avatar"></div>
              <div class="bg-[var(--mist)] rounded-xl px-3 py-2 flex-1">
                <p class="text-[11px] font-semibold" x-text="c.author"></p>
                <p class="text-[12px] text-[#5B6F63]" x-text="c.text"></p>
              </div>
            </div>
          </template>
          <div class="flex gap-2 mt-2">
            <input type="text" x-model="p.newComment" @keyup.enter="addComment(p)" placeholder="Viết bình luận..."
                   class="flex-1 bg-[var(--mist)] rounded-xl px-3.5 py-2 text-[12px] outline-none focus:ring-2 ring-[var(--forest)]">
            <button @click="addComment(p)" class="btn-press bg-[var(--forest)] text-white text-[12px] font-semibold px-3.5 rounded-xl">Gửi</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</main>

<!-- composer -->
<div x-show="showComposer" x-cloak @click.self="showComposer=false" class="fixed inset-0 bg-black/40 z-40 flex items-end md:items-center justify-center">
  <div class="bg-white rounded-t-3xl md:rounded-3xl w-full md:max-w-md p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-display font-bold text-[17px]">Đăng bài mới</h2>
      <button @click="showComposer=false" class="w-8 h-8 rounded-full bg-[var(--mist)] flex items-center justify-center text-[14px]">✕</button>
    </div>
    <div class="flex flex-wrap gap-2 mb-3">
      <template x-for="t in ['Hỏi đáp','Kinh nghiệm','Mùa vụ']" :key="t">
        <button @click="newPostTag=t" class="chip btn-press px-3.5 py-1.5 rounded-xl bg-[var(--mist)] text-[11px] font-semibold"
                :class="newPostTag===t ? 'chip active' : ''" x-text="t"></button>
      </template>
    </div>
    <textarea x-model="newPostContent" rows="4" placeholder="Chia sẻ câu hỏi hoặc kinh nghiệm của bạn..."
              class="w-full bg-[var(--mist)] rounded-2xl px-4 py-3 text-[13px] outline-none focus:ring-2 ring-[var(--forest)] resize-none"></textarea>
    <button @click="publishPost()" :disabled="!newPostContent" class="btn-press w-full mt-4 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3 text-[13px] disabled:opacity-40">
      Đăng bài
    </button>
  </div>
</div>

<script>
function communityFlow(){
  return {
    filter:'Tất cả',
    showComposer:false, newPostTag:'Hỏi đáp', newPostContent:'',
    posts:[
      {id:1, author:'Chị Hoa - Đông Anh', location:'Thái Nguyên', time:'1 giờ trước', avatar:'👩‍🌾', tag:'Hỏi đáp',
        content:'Ruộng lúa nhà em xuất hiện vết đốm hình thoi trên lá, có phải đạo ôn không ạ? Có bác nào gặp chưa cho em xin kinh nghiệm với.',
        image:'🌾', liked:false, likes:12, showComments:false, newComment:'',
        comments:[{id:1, author:'Anh Tuấn', avatar:'👨‍🌾', text:'Đúng là triệu chứng đạo ôn rồi bạn, phun Tricyclazole sớm nhé'}]},
      {id:2, author:'Anh Bình - Tân Cương', location:'Thái Nguyên', time:'3 giờ trước', avatar:'👨‍🌾', tag:'Kinh nghiệm',
        content:'Chia sẻ kinh nghiệm phòng rầy xanh cho chè: hái đọt đúng lứa kết hợp bẫy dính vàng, giảm hẳn mật độ rầy mà không cần phun nhiều.',
        image:'🍃', liked:true, likes:34, showComments:false, newComment:'',
        comments:[]},
      {id:3, author:'Chị Lan - Sông Công', location:'Thái Nguyên', time:'Hôm qua', avatar:'👩‍🌾', tag:'Mùa vụ',
        content:'Năm nay mưa nhiều, mọi người canh thời điểm bón phân đợt 2 cho ngô thế nào để tránh rửa trôi vậy?',
        image:null, liked:false, likes:8, showComments:false, newComment:'',
        comments:[{id:1, author:'Chị Hoa', avatar:'👩‍🌾', text:'Nhà em đợi tạnh 2-3 ngày mới bón, đỡ trôi hẳn'}]},
    ],
    get filteredPosts(){
      if(this.filter==='Tất cả') return this.posts;
      return this.posts.filter(p=>p.tag===this.filter);
    },
    toggleLike(p){
      p.liked = !p.liked;
      p.likes += p.liked ? 1 : -1;
    },
    addComment(p){
      if(!p.newComment?.trim()) return;
      p.comments.push({id:Date.now(), author:'Bạn', avatar:'🧑‍🌾', text:p.newComment});
      p.newComment='';
    },
    publishPost(){
      if(!this.newPostContent.trim()) return;
      this.posts.unshift({
        id:Date.now(), author:'Bạn', location:'Thái Nguyên', time:'Vừa xong', avatar:'🧑‍🌾',
        tag:this.newPostTag, content:this.newPostContent, image:null,
        liked:false, likes:0, showComments:false, newComment:'', comments:[]
      });
      this.newPostContent=''; this.showComposer=false;
    }
  }
}
</script>
</body>
</html>