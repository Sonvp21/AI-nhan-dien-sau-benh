<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisReport;

class DashboardController extends Controller
{
    /**
     * Cùng danh sách cây trồng dùng ở DiagnosisReportController/DiseaseZoneController
     * (bảng diagnosis_reports lưu "crop" theo đúng các key này) - lặp lại
     * literal ở đây theo đúng convention hiện có của các controller khác
     * trong app (không có bảng "crops" riêng để tra).
     */
    private array $crops = [
        'che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn',
        'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt',
    ];

    /**
     * Trang chủ khu vực admin - nơi admin được đưa vào NGAY sau khi đăng
     * nhập (xem AuthController::login). Gồm: vài số liệu tổng quan (tổng/đã
     * duyệt/chờ duyệt/đã hủy báo cáo), 2 biểu đồ cột (báo cáo theo cây, vùng
     * dịch đang hoạt động theo cây) và 1 bản đồ preview vùng dịch (thu nhỏ,
     * chỉ xem - xem đầy đủ ở /admin/vung-dich, vào bằng menu sidebar).
     */
    public function index()
    {
        $reportsByCropRaw = DiagnosisReport::query()
            ->selectRaw('crop, count(*) as total')
            ->groupBy('crop')
            ->pluck('total', 'crop');

        // "Vùng dịch": điểm phát hiện bệnh ĐANG HOẠT ĐỘNG - đã được admin
        // duyệt (verified) VÀ chưa đánh dấu xử lý (resolved_at null) - xem
        // DiagnosisReport::scopeActiveOnMap(). Đếm theo từng loại cây giống
        // hệt số liệu ở dashboard Vùng dịch (DiseaseZoneController) để 2 nơi
        // luôn khớp nhau.
        $zonesByCropRaw = DiagnosisReport::query()->activeOnMap()
            ->selectRaw('crop, count(*) as total')
            ->groupBy('crop')
            ->pluck('total', 'crop');

        $cropLabels = array_values($this->crops);
        $reportsByCrop = [];
        $zonesByCrop = [];
        foreach ($this->crops as $key => $label) {
            $reportsByCrop[] = (int) ($reportsByCropRaw[$key] ?? 0);
            $zonesByCrop[] = (int) ($zonesByCropRaw[$key] ?? 0);
        }

        return view('admin.dashboard', [
            'pendingCount' => DiagnosisReport::where('status', DiagnosisReport::STATUS_PENDING)->count(),
            'approvedCount' => DiagnosisReport::where('status', DiagnosisReport::STATUS_VERIFIED)->count(),
            'rejectedCount' => DiagnosisReport::where('status', DiagnosisReport::STATUS_REJECTED)->count(),
            'totalReports' => DiagnosisReport::count(),
            'cropLabels' => $cropLabels,
            'reportsByCrop' => $reportsByCrop,
            'zonesByCrop' => $zonesByCrop,
        ]);
    }
}
