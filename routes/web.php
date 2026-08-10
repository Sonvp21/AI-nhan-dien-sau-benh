<?php

use App\Http\Controllers\RemoteCaptureController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'agri-index')->name('agri.index');
Route::view('/auth', 'agri-auth')->name('agri.auth');
Route::view('/thong-bao', 'agri-notifications')->name('agri.notifications');
Route::view('/them-ruong', 'agri-add-field')->name('agri.add-field');
Route::view('/thu-vien-sau-benh', 'agri-library')->name('agri.library');
Route::view('/cong-dong', 'agri-community')->name('agri.community');

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