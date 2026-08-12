<?php

namespace App\Http\Controllers;

use App\Models\DiagnosisReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisReportController extends Controller
{
    /**
     * Lưu 1 lần chẩn đoán (nút "Lưu" hiện ngay sau khi AI trả kết quả).
     * Thông tin bệnh được client gửi lên đúng như những gì AI vừa trả (auto-fill),
     * ảnh là ảnh vừa dùng để chẩn đoán, vị trí là GPS hiện tại hoặc marker người
     * dùng tự kéo trên bản đồ nhỏ trong modal. Report luôn tạo ở trạng thái
     * "pending" - chỉ lên bản đồ công khai sau khi admin duyệt.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'crop' => ['required', 'string', 'max:30'],
            'crop_label' => ['nullable', 'string', 'max:50'],
            'disease_name' => ['required', 'string', 'max:255'],
            'disease_key' => ['nullable', 'string', 'max:255'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'disease_probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'level' => ['nullable', 'string', 'max:30'],
            'pathogen' => ['nullable', 'string'],
            'signs_in_photo' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'prevention' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $imagePath = $request->file('image')->store('diagnosis-reports', 'public');
        unset($data['image']);

        $report = $request->user()->diagnosisReports()->create([
            ...$data,
            'image_path' => $imagePath,
            'status' => DiagnosisReport::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Đã lưu kết quả chẩn đoán. Report sẽ hiện lên bản đồ sau khi admin kiểm duyệt.',
            'report' => $report,
        ], 201);
    }

    /**
     * Lịch sử các lần chẩn đoán người dùng hiện tại đã lưu (mọi trạng thái).
     */
    public function history(Request $request)
    {
        $reports = $request->user()->diagnosisReports()->latest()->paginate(12);

        return view('agri-diagnosis-history', ['reports' => $reports]);
    }
}
