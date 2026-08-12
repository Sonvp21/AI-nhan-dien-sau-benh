<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriAI — Lịch sử chẩn đoán của tôi</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --forest:#1f6d3c; --mist:#f2f7ee; --soil:#c9762c; --danger:#c1440e; }
  body{font-family:'Inter',sans-serif;background:var(--mist);color:#1c231d;}
  .font-display{font-family:'Space Grotesk',sans-serif;}
</style>
</head>
<body class="min-h-screen">

<header class="flex items-center justify-between px-5 md:px-10 pt-6 pb-3 sticky top-0 bg-[var(--mist)]/95 backdrop-blur z-10">
  <a href="{{ route('agri.index') }}" class="text-[13px] font-semibold" style="color:var(--forest)">← Trang chủ</a>
</header>

<x-agri.nav-tabs />

<main class="max-w-3xl mx-auto px-5 md:px-10 pb-16">
  <h1 class="font-display font-bold text-[22px] md:text-[26px] mt-2" style="color:var(--forest)">Lịch sử chẩn đoán của tôi</h1>
  <p class="text-[13px] text-gray-500 mt-1">Các lần bạn đã lưu kết quả chẩn đoán để admin kiểm duyệt</p>

  <div class="mt-6 space-y-4">
    @forelse ($reports as $report)
      <div class="bg-white rounded-xl p-4 flex gap-4 items-start" style="border:1px solid #e5e9df">
        <img src="{{ $report->imageUrl() }}" alt="Ảnh chẩn đoán" class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover shrink-0">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-[15px]" style="color:#12341d">{{ $report->disease_name }}</p>
            @if ($report->status === 'pending')
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#fff7ed;color:var(--soil)">Chờ duyệt</span>
            @elseif ($report->status === 'verified')
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#e2efd9;color:var(--forest)">Đã duyệt</span>
            @else
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#fbe3dc;color:var(--danger)">Từ chối</span>
            @endif
          </div>
          <p class="text-[12.5px] text-gray-500 mt-1">{{ $report->crop_label ?? $report->crop }} · {{ $report->created_at->format('d/m/Y H:i') }}</p>
          <p class="text-[12px] text-gray-400 mt-0.5">Vị trí: {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
          @if ($report->status === 'rejected' && $report->rejection_reason)
            <p class="text-[12px] mt-1" style="color:var(--danger)">Lý do từ chối: {{ $report->rejection_reason }}</p>
          @endif
        </div>
      </div>
    @empty
      <div class="bg-white rounded-xl p-8 text-center text-[13px] text-gray-400" style="border:1px dashed #dbe8d2">
        Bạn chưa lưu lần chẩn đoán nào. Sau khi chẩn đoán ở trang chủ, bấm "Lưu kết quả" để lưu lại.
      </div>
    @endforelse
  </div>

  <div class="mt-6">
    {{ $reports->links() }}
  </div>
</main>

<script>
  if (typeof lucide !== 'undefined') lucide.createIcons();
</script>

</body>
</html>
