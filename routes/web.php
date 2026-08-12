<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiagnosisReportController as AdminDiagnosisReportController;
use App\Http\Controllers\Admin\DiseaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiagnosisMapController;
use App\Http\Controllers\DiagnosisReportController;
use App\Http\Controllers\RemoteCaptureController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'agri-index')->name('agri.index');
Route::view('/thong-bao', 'agri-notifications')->name('agri.notifications');
Route::view('/them-ruong', 'agri-add-field')->name('agri.add-field');
Route::view('/thu-vien-sau-benh', 'agri-library')->name('agri.library');
Route::view('/cong-dong', 'agri-community')->name('agri.community');

// ==== Auth: SĐT + mật khẩu, phone dùng làm username, KHÔNG có bước OTP ====
Route::view('/auth', 'agri-auth')->name('agri.auth')->middleware('guest');
Route::post('/auth/dang-nhap', [AuthController::class, 'login'])->name('agri.auth.login')->middleware('guest');
Route::post('/auth/dang-ky', [AuthController::class, 'register'])->name('agri.auth.register')->middleware('guest');
Route::post('/auth/dang-xuat', [AuthController::class, 'logout'])->name('agri.auth.logout')->middleware('auth');

// ==== Lưu kết quả chẩn đoán ("Lưu" ngay sau khi chẩn đoán xong) + lịch sử
//      của riêng người dùng đang đăng nhập ====
Route::middleware('auth')->group(function () {
    Route::post('/bao-cao-benh', [DiagnosisReportController::class, 'store'])->name('agri.reports.store');
    Route::get('/lich-su-chan-doan', [DiagnosisReportController::class, 'history'])->name('agri.reports.history');
});

// ==== Bản đồ dịch bệnh công khai: chỉ hiện các report admin đã duyệt ====
Route::view('/ban-do-dich-benh', 'agri-disease-map')->name('agri.disease-map');
Route::get('/ban-do-dich-benh/du-lieu', [DiagnosisMapController::class, 'data'])->name('agri.disease-map.data');

// ==== Chụp ảnh từ xa bằng điện thoại (chế độ thuyết trình): quét mã QR
//      trên màn hình lớn để mở trang chụp ảnh riêng trên điện thoại, ảnh
//      chụp xong sẽ tự động đẩy về modal xác nhận trên màn hình trình chiếu. ====
Route::get('/quet-anh/{token}', [RemoteCaptureController::class, 'show'])
    ->where('token', '[A-Za-z0-9\-]{6,64}')
    ->name('agri.remote-capture');

Route::post('/quet-anh/{token}/upload', [RemoteCaptureController::class, 'upload'])
    ->where('token', '[A-Za-z0-9\-]{6,64}')
    ->name('agri.remote-capture.upload');

Route::get('/quet-anh/{token}/status', [RemoteCaptureController::class, 'status'])
    ->where('token', '[A-Za-z0-9\-]{6,64}')
    ->name('agri.remote-capture.status');

// ==== Khu vực quản trị: yêu cầu đăng nhập + is_admin=true ====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('diseases', DiseaseController::class);

    Route::get('diagnosis-reports', [AdminDiagnosisReportController::class, 'index'])->name('diagnosis-reports.index');
    Route::get('diagnosis-reports/{diagnosisReport}', [AdminDiagnosisReportController::class, 'show'])->name('diagnosis-reports.show');
    Route::post('diagnosis-reports/{diagnosisReport}/duyet', [AdminDiagnosisReportController::class, 'approve'])->name('diagnosis-reports.approve');
    Route::post('diagnosis-reports/{diagnosisReport}/tu-choi', [AdminDiagnosisReportController::class, 'reject'])->name('diagnosis-reports.reject');
});
