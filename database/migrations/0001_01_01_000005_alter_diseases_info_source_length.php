<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            // 'tai_lieu_chuyen_nganh' dài 21 ký tự, cột cũ chỉ cho varchar(20) nên báo lỗi truncated
            $table->string('info_source', 30)->default('tai_lieu_chuyen_nganh')->change();
        });
    }

    public function down(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            $table->string('info_source', 20)->default('tai_lieu_chuyen_nganh')->change();
        });
    }
};
