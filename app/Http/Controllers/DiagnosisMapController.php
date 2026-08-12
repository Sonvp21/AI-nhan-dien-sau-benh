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
            'latitude', 'longitude', 'image_path', 'verified_at', 'created_at',
        ]);

        return response()->json([
            'reports' => $reports->map(fn ($r) => [
                'id' => $r->id,
                'crop' => $r->crop,
                'crop_label' => $r->crop_label ?? $r->crop,
                'disease_name' => $r->disease_name,
                'level' => $r->level,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'image_url' => $r->imageUrl(),
                'date' => $r->verified_at?->format('d/m/Y') ?? $r->created_at->format('d/m/Y'),
            ]),
        ]);
    }
}
