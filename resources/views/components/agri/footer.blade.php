{{-- ================= FOOTER =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:).
     $totalVisits/$onlineNow là số liệu THẬT, cấp bởi View::composer trong
     AppServiceProvider::boot() (đọc từ bảng site_sessions - xem
     TrackSiteVisit middleware, chạy trên mọi route xem trang chính). --}}
<div class="shrink-0 flex flex-col md:flex-row items-center justify-between gap-1.5 px-5 md:px-7 2xl:px-10 py-3.5 2xl:py-4 text-[12.5px] 2xl:text-[13.5px]" style="background:#12341d;color:#dce7d5">
  <p>© 2026 Trung tâm Nghiên cứu Địa tin học</p>
  <div class="flex items-center gap-5">
    <span>Đang online: {{ number_format($onlineNow ?? 0) }}</span>
    <span>Tổng số truy cập: {{ number_format($totalVisits ?? 0) }}</span>
  </div>
</div>
