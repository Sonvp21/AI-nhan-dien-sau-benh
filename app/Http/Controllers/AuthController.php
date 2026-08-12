<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Đăng nhập bằng số điện thoại + mật khẩu (phone dùng làm username,
     * không có bước xác thực OTP).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['phone' => $credentials['phone'], 'password' => $credentials['password']], $remember)) {
            throw ValidationException::withMessages([
                'phone' => 'Số điện thoại hoặc mật khẩu không đúng.',
            ]);
        }

        $request->session()->regenerate();

        // Admin đăng nhập -> vào THẲNG giao diện quản trị (layout riêng),
        // không qua trang chủ người dùng thường. Nếu admin bị chặn ở 1 URL
        // /admin/... cụ thể rồi mới bị đưa về /auth thì intended() vẫn ưu
        // tiên đưa họ về đúng URL đó.
        if ($request->user()->is_admin) {
            return redirect()->intended(route('admin.dashboard'))->with('success', 'Đăng nhập thành công.');
        }

        return redirect()->intended(route('agri.index'))->with('success', 'Đăng nhập thành công.');
    }

    /**
     * Đăng ký tài khoản mới, chỉ cần họ tên + số điện thoại + mật khẩu.
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+ ]{8,20}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'phone.unique' => 'Số điện thoại này đã được đăng ký.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('agri.index'))->with('success', 'Đăng ký thành công, chào mừng bạn!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agri.index')->with('success', 'Đã đăng xuất.');
    }
}
