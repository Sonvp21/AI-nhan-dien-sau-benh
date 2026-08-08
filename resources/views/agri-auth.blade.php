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
  .otp-box{width:48px;height:56px;text-align:center;font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:600;}
  [x-cloak]{display:none!important;}
</style>
</head>
<body x-data="authFlow()" class="min-h-screen flex items-center justify-center p-5">

<div class="w-full max-w-sm">

  <!-- WELCOME -->
  <div x-show="step==='welcome'" x-cloak class="rise text-center">
    <div class="w-20 h-20 rounded-3xl bg-[var(--forest)] flex items-center justify-center text-4xl mx-auto mb-6">🌾</div>
    <h1 class="font-display font-bold text-[26px] text-[var(--forest)]">AgriAI</h1>
    <p class="text-[13px] text-[#5B6F63] mt-2 leading-relaxed">Nhận diện sâu bệnh, dự báo năng suất<br>và phân tích dữ liệu nông nghiệp bằng AI</p>

    <button @click="step='login'" class="btn-press w-full mt-8 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">
      Đăng nhập
    </button>
    <button @click="step='register'" class="btn-press w-full mt-3 bg-white border border-[var(--forest)]/20 text-[var(--forest)] font-semibold rounded-2xl py-3.5 text-[14px]">
      Tạo tài khoản mới
    </button>
  </div>

  <!-- LOGIN -->
  <div x-show="step==='login'" x-cloak class="card p-7 rise">
    <button @click="step='welcome'" class="text-[13px] font-semibold text-[var(--forest)]">← Quay lại</button>
    <h2 class="font-display font-bold text-[20px] mt-4">Đăng nhập</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Nhập số điện thoại đã đăng ký</p>

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-5">Số điện thoại</label>
    <input type="tel" x-model="phone" placeholder="0912 345 678" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-4">Mật khẩu</label>
    <input type="password" x-model="password" placeholder="••••••••" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">

    <button @click="sendOtp()" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]" :disabled="!phone">
      Đăng nhập
    </button>
    <p class="text-center text-[12px] text-[#5B6F63] mt-4">Quên mật khẩu?</p>
  </div>

  <!-- REGISTER -->
  <div x-show="step==='register'" x-cloak class="card p-7 rise">
    <button @click="step='welcome'" class="text-[13px] font-semibold text-[var(--forest)]">← Quay lại</button>
    <h2 class="font-display font-bold text-[20px] mt-4">Tạo tài khoản</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Bắt đầu quản lý nông trại thông minh</p>

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-5">Họ và tên</label>
    <input type="text" x-model="fullname" placeholder="Nguyễn Văn Sơn" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-4">Số điện thoại</label>
    <input type="tel" x-model="phone" placeholder="0912 345 678" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] font-mono outline-none focus:ring-2 ring-[var(--forest)]">

    <label class="text-[12px] font-semibold text-[#5B6F63] block mt-4">Tỉnh/Thành phố</label>
    <select x-model="province" class="w-full mt-1.5 bg-[var(--mist)] rounded-xl px-4 py-3 text-[14px] outline-none focus:ring-2 ring-[var(--forest)]">
      <option>Thái Nguyên</option>
      <option>Hà Nội</option>
      <option>Lào Cai</option>
      <option>Khác</option>
    </select>

    <button @click="sendOtp()" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]" :disabled="!phone || !fullname">
      Tiếp tục
    </button>
  </div>

  <!-- OTP -->
  <div x-show="step==='otp'" x-cloak class="card p-7 rise">
    <button @click="step='welcome'" class="text-[13px] font-semibold text-[var(--forest)]">← Quay lại</button>
    <h2 class="font-display font-bold text-[20px] mt-4">Xác thực OTP</h2>
    <p class="text-[12px] text-[#5B6F63] mt-1">Mã gồm 4 số đã gửi tới <span class="font-mono font-semibold" x-text="phone"></span></p>

    <div class="flex gap-2.5 mt-6 justify-center">
      <template x-for="i in 4" :key="i">
        <input type="text" maxlength="1" x-model="otp[i-1]" @input="$event.target.value.length===1 && $event.target.nextElementSibling?.focus()"
               class="otp-box bg-[var(--mist)] rounded-xl outline-none focus:ring-2 ring-[var(--forest)]">
      </template>
    </div>

    <button @click="verifyOtp()" class="btn-press w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">
      Xác nhận
    </button>
    <p class="text-center text-[12px] text-[#5B6F63] mt-4">Không nhận được mã? <span class="font-semibold text-[var(--forest)]">Gửi lại</span></p>
  </div>

  <!-- SUCCESS -->
  <div x-show="step==='success'" x-cloak class="card p-8 rise text-center">
    <div class="w-16 h-16 rounded-full bg-[var(--leaf-soft)] flex items-center justify-center text-3xl mx-auto">✓</div>
    <h2 class="font-display font-bold text-[19px] mt-4">Chào mừng đến AgriAI!</h2>
    <p class="text-[13px] text-[#5B6F63] mt-1.5">Tài khoản của bạn đã sẵn sàng</p>
    <a href="{{ route('agri.index') }}" class="btn-press block w-full mt-6 bg-[var(--forest)] text-white font-semibold rounded-2xl py-3.5 text-[14px]">
      Vào ứng dụng
    </a>
  </div>
</div>

<script>
function authFlow(){
  return {
    step:'welcome',
    phone:'', password:'', fullname:'', province:'Thái Nguyên',
    otp:['','','',''],
    sendOtp(){ this.step='otp'; },
    verifyOtp(){ this.step='success'; }
  }
}
</script>
</body>
</html>