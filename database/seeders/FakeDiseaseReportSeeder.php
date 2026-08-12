<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\DiagnosisReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Seed dữ liệu GIẢ để xem thử bản đồ/dashboard vùng dịch hiển thị thế nào khi
 * có nhiều điểm (đặc biệt là lớp heatmap - cần nhiều điểm tụ lại mới thấy rõ
 * "vùng nóng"). KHÔNG chạy tự động, chỉ chạy tay khi cần demo/test:
 *
 *   php artisan db:seed --class=FakeDiseaseReportSeeder
 *
 * Mọi report seed ra đều có sender_name bắt đầu bằng "[DEMO]" để dễ nhận biết
 * và xóa sạch sau khi xem xong (không lẫn với báo cáo thật của người dùng):
 *
 *   php artisan tinker --execute="App\Models\DiagnosisReport::where('sender_name','like','[DEMO]%')->delete()"
 */
class FakeDiseaseReportSeeder extends Seeder
{
    private array $crops = [
        'che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn',
        'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt',
    ];

    // Ảnh minh họa dùng làm ảnh đại diện cho report giả (không phải ảnh chẩn
    // đoán thật) - lấy luôn ảnh cây trồng có sẵn ở public/image.
    private array $cropImages = [
        'che' => 'crop-che.png', 'lua' => 'crop-lua.png', 'ngo' => 'crop-ngo.png',
        'san' => 'crop-san.png', 'ca_chua' => 'crop-cachua.png',
        'xoai' => 'crop-xoai.jpg', 'ot' => 'crop-ot.jpg',
    ];

    public function run(): void
    {
        if (Disease::count() === 0) {
            $this->command?->warn('Bảng diseases đang trống - chạy "php artisan db:seed --class=CropDiseaseSeeder" trước đã, nếu không sẽ không tạo được report nào.');
        }

        $imagePathByCrop = $this->ensureDemoImages();
        $diseasesByCrop = $this->loadDiseasesByCrop();

        // Vài "tâm vùng dịch" quanh khu vực Thái Nguyên (đúng vị trí bản đồ
        // mặc định center vào) - mỗi tâm gán 1-2 loại cây, giống 1 đợt dịch
        // thật đang bùng ở khu vực đó. Có tâm gần nhau (Đại Từ x2) để test
        // heatmap nối 2 vùng nóng lại thành 1 dải khi zoom xa.
        $centers = [
            ['lat' => 21.5944, 'lng' => 105.8480, 'crops' => ['che', 'lua'], 'points' => [40, 60]],
            ['lat' => 21.6540, 'lng' => 105.8270, 'crops' => ['che'], 'points' => [50, 70]],
            ['lat' => 21.6100, 'lng' => 105.7900, 'crops' => ['che', 'xoai'], 'points' => [20, 35]],
            ['lat' => 21.5230, 'lng' => 105.9120, 'crops' => ['lua', 'ngo'], 'points' => [35, 55]],
            ['lat' => 21.7100, 'lng' => 105.7600, 'crops' => ['san', 'ngo'], 'points' => [15, 25]],
            ['lat' => 21.4700, 'lng' => 105.8000, 'crops' => ['ca_chua', 'ot'], 'points' => [20, 30]],
        ];

        $total = 0;
        $now = Carbon::now();

        foreach ($centers as $center) {
            $pointsInCluster = random_int($center['points'][0], $center['points'][1]);

            for ($i = 0; $i < $pointsInCluster; $i++) {
                $cropKey = $center['crops'][array_rand($center['crops'])];
                $diseases = $diseasesByCrop[$cropKey] ?? collect();
                if ($diseases->isEmpty()) {
                    continue;
                }
                $disease = $diseases->random();

                // Dồn điểm về gần tâm (nhân 2 số random để lệch càng lớn càng
                // hiếm) - lệch tối đa ~3km, để lớp heatmap thấy rõ vùng nóng ở
                // giữa nhạt dần ra ngoài, không rải đều vô hồn.
                $spread = (mt_rand() / mt_getrandmax()) * (mt_rand() / mt_getrandmax());
                $angle = mt_rand(0, 359) * M_PI / 180;
                $distanceDeg = $spread * 0.03;
                $lat = $center['lat'] + cos($angle) * $distanceDeg;
                $lng = $center['lng'] + sin($angle) * $distanceDeg;

                // 85% verified + chưa xử lý (active - hiện trên bản đồ/heatmap),
                // phần còn lại trộn thêm đã xử lý/chờ duyệt/đã hủy để có luôn
                // dữ liệu test 2 biểu đồ + trang duyệt báo cáo.
                $roll = mt_rand(1, 100);
                if ($roll <= 85) {
                    $status = DiagnosisReport::STATUS_VERIFIED;
                    $resolvedAt = null;
                } elseif ($roll <= 92) {
                    $status = DiagnosisReport::STATUS_VERIFIED;
                    $resolvedAt = $now->copy()->subDays(random_int(1, 20));
                } elseif ($roll <= 97) {
                    $status = DiagnosisReport::STATUS_PENDING;
                    $resolvedAt = null;
                } else {
                    $status = DiagnosisReport::STATUS_REJECTED;
                    $resolvedAt = null;
                }

                $steps = $disease->recommended_steps ?? [];

                DiagnosisReport::create([
                    'sender_name' => '[DEMO] Nông dân '.random_int(1, 999),
                    'crop' => $cropKey,
                    'crop_label' => $this->crops[$cropKey],
                    'disease_name' => $disease->name_vi,
                    'disease_key' => $disease->class_key,
                    'probability' => random_int(70, 99),
                    'disease_probability' => random_int(60, 98),
                    'level' => $disease->level ?? 'Trung bình',
                    'pathogen' => $disease->pathogen,
                    'signs_in_photo' => '(Dữ liệu demo) Dấu hiệu điển hình của '.$disease->name_vi.' trên '.$this->crops[$cropKey].'.',
                    'symptoms' => $disease->conditions,
                    'treatment' => implode('. ', $steps),
                    'prevention' => implode('. ', array_slice($steps, 0, 2)),
                    'image_path' => $imagePathByCrop[$cropKey],
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'status' => $status,
                    'verified_at' => $status === DiagnosisReport::STATUS_VERIFIED ? $now->copy()->subDays(random_int(0, 25)) : null,
                    'resolved_at' => $resolvedAt,
                ]);
                $total++;
            }
        }

        $this->command?->info("Đã seed {$total} báo cáo demo quanh ".count($centers).' vùng - vào /admin/vung-dich hoặc bản đồ công khai để xem heatmap.');
    }

    /**
     * Mỗi loại cây lấy sẵn danh sách bệnh (ưu tiên bỏ nhãn "healthy" cho có
     * vẻ "dịch bệnh" thật, cây nào chỉ có nhãn khỏe mạnh thì dùng luôn).
     */
    private function loadDiseasesByCrop(): array
    {
        $result = [];
        foreach (array_keys($this->crops) as $key) {
            $all = Disease::where('crop_key', $key)->get();
            $sick = $all->reject(fn ($d) => str_contains(strtolower($d->class_key ?? ''), 'healthy'));
            $result[$key] = $sick->isNotEmpty() ? $sick : $all;
        }

        return $result;
    }

    /**
     * Copy ảnh cây trồng có sẵn ở public/image vào storage/app/public/diagnosis-reports
     * (chỉ copy nếu chưa có) để dùng làm ảnh đại diện - đúng định dạng lưu
     * ảnh (disk "public", path tương đối) như DiagnosisReportController::store()
     * để asset('storage/...') load được, không bị ảnh vỡ trong popup.
     */
    private function ensureDemoImages(): array
    {
        $paths = [];
        foreach ($this->cropImages as $crop => $filename) {
            $source = public_path('image/'.$filename);
            $destRelative = 'diagnosis-reports/demo-'.$crop.'.'.pathinfo($filename, PATHINFO_EXTENSION);

            if (! Storage::disk('public')->exists($destRelative) && file_exists($source)) {
                Storage::disk('public')->put($destRelative, file_get_contents($source));
            }

            $paths[$crop] = $destRelative;
        }

        return $paths;
    }
}
