<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ yêu cầu đăng nhập khi lưu report lên bản đồ: user_id chuyển thành
     * nullable (chỉ gán nếu tình cờ đang đăng nhập), thêm sender_name để lưu
     * tên người gửi tự nhập trong form (không cần tài khoản). Vì không còn
     * bắt buộc đăng nhập nên tính năng "lịch sử của tôi" cũng bị bỏ luôn.
     */
    public function up(): void
    {
        Schema::table('diagnosis_reports', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('sender_name')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_reports', function (Blueprint $table) {
            $table->dropColumn('sender_name');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
