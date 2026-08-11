<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id(); // khóa chính tự tăng

            $table->string('crop_key', 30);
            // Tên cây trồng, dùng đúng key đang gọi API AI (vd: 'che', 'lua', 'ngo', 'san', 'ca_chua', 'xoai', 'ot')
            // Không tách bảng riêng cho cây trồng, lưu thẳng chuỗi ở đây cho gọn

            $table->string('class_key', 150);
            // Tên lớp gốc mà model AI trả về, phải khớp CHÍNH XÁC với class_names trong app.py
            // vd: 'brown blight', 'Blast', 'Cassava___mosaic_disease'

            $table->string('name_vi', 150);
            // Tên bệnh hiển thị cho người dùng, vd: 'Đốm nâu (chè)'

            $table->text('pathogen')->nullable();
            // Tác nhân gây bệnh (tên khoa học), vd: 'Nấm Colletotrichum camelliae'

            $table->text('conditions')->nullable();
            // Điều kiện môi trường khiến bệnh dễ phát sinh, vd: 'Mưa nhiều, ẩm độ cao...'

            $table->string('level', 20);
            // Mức độ nguy hiểm: 'Nhẹ' / 'Trung bình' / 'Nặng'

            $table->json('recommended_steps')->nullable();
            // Danh sách các bước xử lý, lưu dạng mảng JSON, vd: ["Bước 1", "Bước 2"]

            $table->string('affected_organ', 30)->default('lá');
            // Bộ phận cây bị bệnh: lá / thân / rễ / củ / quả / toàn cây

            $table->string('info_source', 20)->default('tai_lieu_chuyen_nganh');
            // Nguồn gốc thông tin: 'tai_lieu_chuyen_nganh' (đã kiểm chứng) hoặc 'ai_bien_soan' (cần kiểm tra lại)

            $table->timestamps();
            // created_at, updated_at tự động

            $table->unique(['crop_key', 'class_key']);
            // Đảm bảo không trùng lặp: mỗi cây chỉ có 1 dòng cho mỗi tên lớp bệnh
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diseases');
    }
};