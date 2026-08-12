<?php

namespace App\Providers;

use App\Models\SiteSession;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Số liệu thật cho footer.blade.php (Tổng số truy cập / Đang online) -
        // xem TrackSiteVisit middleware ghi dữ liệu vào bảng site_sessions.
        // Bọc try/catch để nếu quên chạy migrate thì footer chỉ hiện 0 thay
        // vì làm sập luôn cả trang chủ.
        View::composer('components.agri.footer', function ($view): void {
            try {
                $totalVisits = SiteSession::query()->count();
                $onlineNow = SiteSession::query()
                    ->where('last_seen_at', '>=', now()->subMinutes(SiteSession::ONLINE_WINDOW_MINUTES))
                    ->count();
            } catch (\Throwable $e) {
                $totalVisits = 0;
                $onlineNow = 0;
            }

            $view->with(['totalVisits' => $totalVisits, 'onlineNow' => $onlineNow]);
        });
    }
}
