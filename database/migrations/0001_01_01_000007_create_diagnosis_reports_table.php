<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mỗi lần người dùng bấm "Lưu" sau khi chẩn đoán xong sẽ tạo 1 dòng ở
     * đây với status=pending. Chỉ khi admin duyệt (status=verified) thì
     * report mới được đưa lên bản đồ dịch bệnh công khai.
     */
    public function up(): void
    {
        Schema::create('diagnosis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Snapshot thông tin cây trồng + bệnh, lấy từ kết quả AI ngay lúc lưu
            // (không tham chiếu sang bảng diseases để tránh vỡ dữ liệu nếu sau
            // này danh mục bệnh được admin sửa/xoá).
            $table->string('crop', 30);
            $table->string('crop_label', 50)->nullable();
            $table->string('disease_name');
            $table->string('disease_key')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->unsignedTinyInteger('disease_probability')->nullable();
            $table->string('level', 30)->nullable();
            $table->text('pathogen')->nullable();
            $table->text('signs_in_photo')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('treatment')->nullable();
            $table->text('prevention')->nullable();

            $table->string('image_path');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('status', 20)->default('pending'); // pending|verified|rejected
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_reports');
    }
};
