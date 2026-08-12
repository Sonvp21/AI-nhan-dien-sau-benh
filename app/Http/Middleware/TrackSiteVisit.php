<?php

namespace App\Http\Middleware;

use App\Models\SiteSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    /**
     * Ghi nhận 1 session trình duyệt đang xem trang - dùng cho số liệu thật ở
     * footer.blade.php (xem AppServiceProvider::boot()). Chỉ gắn vào các
     * route xem TRANG THẬT (không gắn vào route JSON/polling/admin) để số
     * liệu phản ánh đúng lượt xem trang, không bị phồng lên vì gọi API ngầm.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $sessionId = $request->session()->getId();
            $now = now();

            // Đã có session này rồi -> chỉ cập nhật last_seen_at (giữ nguyên
            // first_seen_at từ lần đầu). Chưa có -> tạo mới, tính là 1 lượt
            // truy cập mới ("Tổng số truy cập" = COUNT(*) bảng này).
            $updated = SiteSession::query()
                ->where('session_id', $sessionId)
                ->update(['last_seen_at' => $now]);

            if (! $updated) {
                SiteSession::query()->insertOrIgnore([
                    'session_id' => $sessionId,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            // Chưa chạy migrate hoặc DB lỗi tạm thời - không để việc đếm
            // truy cập làm sập cả trang chính.
        }

        return $next($request);
    }
}
