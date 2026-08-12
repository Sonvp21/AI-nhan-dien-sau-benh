<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đếm truy cập trang thật (footer.blade.php): mỗi session trình duyệt
     * được ghi 1 dòng, cập nhật lại last_seen_at mỗi lần vào lại trang - xem
     * TrackSiteVisit middleware. "Tổng số truy cập" = tổng số dòng (mỗi
     * session tính 1 lần), "Đang online" = số session có last_seen_at trong
     * vài phút gần nhất.
     */
    public function up(): void
    {
        Schema::create('site_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sessions');
    }
};
