<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Tạo (hoặc cập nhật) 1 tài khoản admin để đăng nhập vào /admin duyệt
     * báo cáo chẩn đoán + quản lý danh mục bệnh. Đăng nhập bằng SĐT + mật
     * khẩu ở trang /auth như user thường, chỉ khác is_admin=true.
     *
     * Đổi ADMIN_PHONE / ADMIN_PASSWORD trong .env trước khi chạy seeder ở
     * môi trường thật (VPS), hoặc đổi mật khẩu ngay sau khi đăng nhập lần đầu.
     */
    public function run(): void
    {
        $phone = env('ADMIN_PHONE', '0900000000');
        $password = env('ADMIN_PASSWORD', 'admin123');

        User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Quản trị viên',
                'password' => $password,
                'is_admin' => true,
            ]
        );

        $this->command?->info("Đã tạo/cập nhật tài khoản admin với SĐT: {$phone}");
    }
}
