<?php

namespace App\Http\Controllers;

use App\Models\DiagnosisReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisMapController extends Controller
{
    /**
     * Dữ liệu cho bản đồ dịch bệnh công khai - CHỈ trả các report đã được
     * admin duyệt (status=verified), không bao giờ lộ report pending/rejected
     * hay thông tin liên hệ của người gửi.
     */
    public function data(Request $request): JsonResponse
    {
        $query = DiagnosisReport::query()->where('status', DiagnosisReport::STATUS_VERIFIED);

        if ($request->filled('crop')) {
            $query->where('crop', $request->crop);
        }

        $reports = $query->latest('verified_at')->limit(500)->get([
            'id', 'crop', 'crop_label', 'disease_name', 'level',
            'probability', 'disease_probability', 'pathogen', 'signs_in_photo',
            'symptoms', 'treatment', 'prevention',
            'latitude', 'longitude', 'image_path', 'verified_at', 'created_at',
        ]);

        // Trả luôn ĐẦY ĐỦ thông tin (không chỉ vài field như trước) để modal
        // "Xem chi tiết" ở marker popup dùng được ngay, không cần gọi thêm API -
        // vẫn an toàn vì chỉ lấy report đã verified, không có thông tin liên hệ
        // người gửi.
        return response()->json([
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
