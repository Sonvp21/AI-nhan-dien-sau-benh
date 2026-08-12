<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Đánh dấu đã xử lý" cho dashboard vùng dịch (admin): report đã verified
     * vẫn được GIỮ LẠI trong hệ thống (không xoá), chỉ gắn resolved_at để ẩn
     * khỏi bản đồ dịch bệnh (cả public và admin) - coi như "xoá vùng dịch"
     * theo đúng nghĩa "đã xử lý xong", nhưng vẫn tra cứu lại được nếu cần.
     */
    public function up(): void
    {
        Schema::table('diagnosis_reports', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('verified_by');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_reports', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
