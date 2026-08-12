<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteSessionSeeder extends Seeder
{
    /**
     * Seed khoảng 1.000 lượt truy cập "lịch sử" để footer không hiện số 0 lúc
     * mới bật tính năng đếm truy cập thật. Rải ngẫu nhiên trong 90 ngày gần
     * đây (nhiều hơn ở các ngày gần hiện tại cho tự nhiên), và để lại một vài
     * chục session có last_seen_at trong 5 phút gần nhất để "Đang online"
     * cũng có số ngay sau khi seed - xem TrackSiteVisit + AppServiceProvider.
     *
     * Chạy: php artisan db:seed --class=SiteSessionSeeder
     * (không gọi trong DatabaseSeeder::run() để tránh bị seed lại nhiều lần
     * mỗi khi chạy db:seed cho việc khác).
     */
    public function run(): void
    {
        $total = 1000;
        $onlineNowCount = 25; // vài chục session coi như "đang online" ngay sau khi seed
        $now = Carbon::now();
        $rows = [];

        for ($i = 0; $i < $total; $i++) {
            if ($i < $onlineNowCount) {
                // Đang "online": lần cuối hoạt động trong vòng 5 phút gần nhất.
                $lastSeen = $now->copy()->subSeconds(random_int(0, 280));
                $firstSeen = $lastSeen->copy()->subMinutes(random_int(0, 30));
            } else {
                // Lượt truy cập trong 90 ngày gần đây, dồn nhiều hơn về gần
                // hiện tại (bình phương số ngẫu nhiên) cho giống truy cập thật.
                $daysAgo = (int) (pow(random_int(0, 1000) / 1000, 2) * 90);
                $firstSeen = $now->copy()->subDays($daysAgo)->subMinutes(random_int(0, 1439));
                $lastSeen = $firstSeen->copy()->addMinutes(random_int(0, 20));
            }

            $rows[] = [
                'session_id' => Str::random(40).'_'.$i,
                'first_seen_at' => $firstSeen,
                'last_seen_at' => $lastSeen,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('site_sessions')->insert($chunk);
        }

        $this->command?->info('Đã seed '.$total.' session ('.$onlineNowCount.' đang "online").');
    }
}
