<?php

namespace App\Http\Controllers;

use App\Models\DiagnosisReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisMapController extends Controller
{
    /**
     * Dữ liệu cho bản đồ dịch bệnh công khai - CHỈ trả các report "đang hoạt
     * động" (đã admin duyệt VÀ chưa bị đánh dấu "đã xử lý" - xem
     * DiagnosisReport::scopeActiveOnMap()), không bao giờ lộ report
     * pending/rejected hay thông tin liên hệ của người gửi. Report đã xử lý
     * (resolved_at) coi như "vùng dịch đã dọn xong", biến mất khỏi bản đồ
     * nhưng vẫn còn lưu trong DB (xem Admin\DiseaseZoneController).
     */
    public function data(Request $request): JsonResponse
    {
        $query = DiagnosisReport::query()->activeOnMap();

        if ($request->filled('crop')) {
            $query->where('crop', $request->crop);
        }

        $reports = $query->latest('verified_at')->limit(500)->get([
            'id', 'crop', 'crop_label', 'disease_name', 'level',
            'probability', 'disease_probability', 'pathogen', 'signs_in_photo',
            'symptoms', 'treatment', 'prevention',
            'latitude', 'longitude', 'image_path', 'verified_at', 'created_at',
        ]);

        // Thống kê tổng số điểm đang hoạt động THEO CÂY - tính riêng, KHÔNG bị
        // giới hạn bởi limit(500) ở trên, để số liệu luôn đúng dù bản đồ có
        // nhiều điểm hơn mức hiển thị.
        $stats = DiagnosisReport::query()->activeOnMap()
            ->selectRaw('crop, count(*) as total')
            ->groupBy('crop')
            ->pluck('total', 'crop');

        // Trả luôn ĐẦY ĐỦ thông tin (không chỉ vài field như trước) để modal
        // "Xem chi tiết" ở marker popup dùng được ngay, không cần gọi thêm API -
        // vẫn an toàn vì chỉ lấy report đã verified, không có thông tin liên hệ
        // người gửi.
        return response()->json([
            'stats' => $stats,
            'reports' => $reports->map(fn ($r) => [
                'id' => $r->id,
                'crop' => $r->crop,
                'crop_label' => $r->crop_label ?? $r->crop,
                'disease_name' => $r->disease_name,
                'level' => $r->level,
                'probability' => $r->probability,
                'disease_probability' => $r->disease_probability,
                'pathogen' => $r->pathogen,
                'signs_in_photo' => $r->signs_in_photo,
                'symptoms' => $r->symptoms,
                'treatment' => $r->treatment,
                'prevention' => $r->prevention,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'image_url' => $r->imageUrl(),
                'date' => $r->verified_at?->format('d/m/Y') ?? $r->created_at->format('d/m/Y'),
            ]),
        ]);
    }
}
