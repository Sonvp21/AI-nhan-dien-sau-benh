<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Đăng nhập</title>
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
  .btn-press:active{transform:scale(.97);}
  .btn-press{transition:transform .12s ease;}
  @keyframes riseFade{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
  .rise{animation:riseFade .5s cubic-bezier(.16,1,.3,1) both;}
  [x-cloak]{display:none!important;}
</style>
</head>
{{-- step ban đầu: nếu form đăng ký vừa submit bị lỗi validate thì mở lại
     đúng form đó (đọc qua hidden input _form), không thì vào THẲNG màn hình
     đăng nhập (không còn bước "welcome" chọn giữa đăng nhập/đăng ký nữa). --}}
<body x-data="authFlow('{{ old('_form') === 'register' ? 'register' : 'login' }}')" class="min-h-screen flex items-center justify-center p-5">

<div class="w-full max-w-sm">

  @if (session('success'))
    <div class="card p-4 mb-4 rise text-center text-[13px] font-semibold" style="color:var(--forest)">{{ session('success') }}</div>
  @endif

  <!-- LOGO + TAGLINE: luôn hiện, phía trên cả 2 form -->
  <div class="text-center mb-5 rise">
    <img src="{{ asset('image/logo.jpg') }}" alt="Logo GIRC" class="w-20 h-20 rounded-3xl object-cover mx-auto mb-4 shadow-lg">
    <h1 class="font-display font-bold text-[22px] text-[var(--forest)]">Bác sĩ cây trồng AI</h1>
    <p class="text-[13px] text-[#5B6F69] mt-1.5">Nhận diện sâu bệnh ở cây trồng bằng công nghệ AI</p>
  </div>

  <!-- LOGIN -->
  <form method="POST" action="{{ route('agri.auth.login') }}" x-show="step==='login'" x-cloak class="card p-7 rise">
    @csrf
    <input type="hidden" name="_form" value="login">
    <h2 class="font-display font-bold text-[20px]">Đăng nhập</h2>
    <p class="text-[12px] text-[#5B6F69] mt-1">Nhập số điện thoại đã đăng ký</p>

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-5">Số điện thoại</label>
    <input type="tel" name="phone" value="{{ old('_form') === 'login' ? old('phone') : '' }}" placeholder="0912345678" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]" autofocus>
    @if (old('_form') === 'login')
      @error('phone')<p class="text-[12px] mt-1.5" style="color:var(--danger)">{{ $message }}</p>@enderror
    @endif

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-4">Mật khẩu</label>
    <input type="password" name="password" placeholder="••••••••" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">
    @if (old('_form') === 'login')
      @error('password')<p class="text-[12px] mt-1.5" style="color:var(--danger)">{{ $message }}</p>@enderror
    @endif

    <button type="submit" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">
      Đăng nhập
    </button>
    <button type="button" @click="step='register'" class="btn-press w-full mt-3 bg-white border border-[var(--forest)]/20 text-[var(--forest)] font-semibold rounded-2xl py-3.5 text-[14px]">
      Tạo tài khoản mới
    </button>
  </form>

  <!-- REGISTER -->
  <form method="POST" action="{{ route('agri.auth.register') }}" x-show="step==='register'" x-cloak class="card p-7 rise">
    @csrf
    <input type="hidden" name="_form" value="register">
    <button type="button" @click="step='login'" class="text-[13px] font-semibold text-[var(--forest)]">← Quay lại đăng nhập</button>
    <h2 class="font-display font-bold text-[20px] mt-4">Tạo tài khoản</h2>
    <p class="text-[12px] text-[#5B6F69] mt-1">Bắt đầu quản lý nông trại thông minh</p>

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-5">Họ và tên</label>
    <input type="text" name="name" value="{{ old('_form') === 'register' ? old('name') : '' }}" placeholder="Nguyễn Văn Sơn" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">
    @if (old('_form') === 'register')
      @error('name')<p class="text-[12px] mt-1.5" style="color:var(--danger)">{{ $message }}</p>@enderror
    @endif

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-4">Số điện thoại</label>
    <input type="tel" name="phone" value="{{ old('_form') === 'register' ? old('phone') : '' }}" placeholder="0912345678" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">
    @if (old('_form') === 'register')
      @error('phone')<p class="text-[12px] mt-1.5" style="color:var(--danger)">{{ $message }}</p>@enderror
    @endif

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-4">Mật khẩu</label>
    <input type="password" name="password" placeholder="Ít nhất 6 ký tự" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">
    @if (old('_form') === 'register')
      @error('password')<p class="text-[12px] mt-1.5" style="color:var(--danger)">{{ $message }}</p>@enderror
    @endif

    <label class="text-[12px] font-semibold text-[#5B6F69] block mt-4">Nhập lại mật khẩu</label>
    <input type="password" name="password_confirmation" placeholder="••••••••" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">

    <button type="submit" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">
      Tạo tài khoản
    </button>
  </form>
</div>

<script>
function authFlow(initialStep){
  return {
    step: initialStep || 'login',
  }
}
</script>
</body>
</html>
