<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\DiagnosisReport;

class DashboardController extends Controller
{
    /**
     * Trang chủ khu vực admin - nơi admin được đưa vào NGAY sau khi đăng
     * nhập (xem AuthController::login). Chỉ là 2 lối vào 2 khu quản trị,
     * không cần phức tạp hơn.
     */
    public function index()
    {
        return view('admin.dashboard', [
            'pendingCount' => DiagnosisReport::where('status', DiagnosisReport::STATUS_PENDING)->count(),
            'diseaseCount' => Disease::count(),
        ]);
    }
}
