<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Dashboard vùng dịch (admin): thống kê số điểm phát hiện bệnh ĐANG HOẠT ĐỘNG
 * theo từng loại cây + preview qua bản đồ (dùng lại đúng dữ liệu/JS của bản
 * đồ dịch bệnh công khai - xem DiagnosisMapController + disease-map.js).
 * "Xóa vùng dịch" = đánh dấu report đã xử lý (resolved_at), KHÔNG xóa hẳn -
 * report biến mất khỏi bản đồ (cả public và dashboard này) nhưng vẫn còn lưu
 * lại trong hệ thống để tra cứu lịch sử nếu cần.
 */
class DiseaseZoneController extends Controller
{
    private array $crops = [
        'che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn',
        'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt',
    ];

    public function index(Request $request)
    {
        $counts = DiagnosisReport::query()->activeOnMap()
            ->selectRaw('crop, count(*) as total')
            ->groupBy('crop')
            ->pluck('total', 'crop');

        // Thống kê theo TÊN BỆNH của 1 cây cụ thể (bảng + biểu đồ cột dưới
        // bản đồ) giờ tính trực tiếp ở JS từ report đã lọc theo cây - chỉ
        // hiện khi bấm vào 1 dòng trong popup "Thống kê vùng dịch", không
        // cần tính sẵn ở đây nữa (xem renderDiseaseStatsForCrop() trong view).
        return view('admin.disease-zones.index', [
            'crops' => $this->crops,
            'counts' => $counts,
            'totalActive' => $counts->sum(),
        ]);
    }

    /**
     * Đánh dấu 1 report đã xử lý ("xóa" khỏi bản đồ, vẫn giữ lại record).
     * Gọi qua fetch() từ popup marker trên dashboard (xem
     * admin/disease-zones/index.blade.php) nên trả JSON.
     */
    public function resolve(DiagnosisReport $diagnosisReport): JsonResponse
    {
        $diagnosisReport->update(['resolved_at' => now()]);

        return response()->json(['message' => 'Đã đánh dấu xử lý, report không còn hiện trên bản đồ.']);
    }

    /**
     * Đánh dấu TOÀN BỘ report đang hoạt động của 1 loại cây là đã xử lý -
     * dùng khi cả vùng dịch của cây đó coi như đã dọn xong.
     */
    public function resolveByCrop(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'crop' => ['required', 'string', 'max:30'],
        ]);

        $count = DiagnosisReport::query()->activeOnMap()
            ->where('crop', $data['crop'])
            ->update(['resolved_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => "Đã đánh dấu xử lý {$count} điểm.", 'count' => $count]);
        }

        return redirect()->route('admin.vung-dich.index')->with('success', "Đã đánh dấu xử lý {$count} điểm.");
    }
}
