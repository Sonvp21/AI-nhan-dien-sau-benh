<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Chức năng "chế độ thuyết trình": người trình chiếu bấm chọn ảnh trên màn
 * hình lớn sẽ hiện mã QR, người dùng quét mã bằng điện thoại để mở trang
 * chụp ảnh riêng (agri-remote-capture). Ảnh chụp xong được gửi (upload) lên
 * đây, lưu tạm theo từng "token" (mã phiên chụp), sau đó màn hình trình
 * chiếu sẽ polling endpoint status() để lấy ảnh về và tự mở modal xác nhận.
 *
 * Không dùng database, chỉ lưu 1 file manifest.json nhỏ theo từng token để
 * đơn giản hóa, phù hợp cho nhu cầu demo / thuyết trình.
 */
class RemoteCaptureController extends Controller
{
    protected function baseDir(string $token): string
    {
        return public_path('remote-uploads/'.$token);
    }

    protected function manifestPath(string $token): string
    {
        return $this->baseDir($token).'/manifest.json';
    }

    /**
     * Trang chụp ảnh hiển thị trên điện thoại sau khi quét mã QR.
     */
    public function show(string $token)
    {
        return view('agri-remote-capture', ['token' => $token]);
    }

    /**
     * Điện thoại gửi ảnh vừa chụp lên đây.
     */
    public function upload(Request $request, string $token)
    {
        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:10240',
        ]);

        $dir = $this->baseDir($token);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $urls = [];
        foreach ($request->file('photos') as $photo) {
            $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $photo->getClientOriginalExtension()));
            $ext = $ext !== '' ? $ext : 'jpg';
            $filename = Str::random(16).'.'.$ext;
            $photo->move($dir, $filename);
            $urls[] = asset('remote-uploads/'.$token.'/'.$filename);
        }

        File::put($this->manifestPath($token), json_encode([
            'photos' => $urls,
            'consumed' => false,
            'uploaded_at' => now()->toIso8601String(),
        ]));

        return response()->json(['ok' => true, 'photos' => $urls]);
    }

    /**
     * Màn hình trình chiếu gọi liên tục (polling) để kiểm tra đã có ảnh
     * gửi từ điện thoại chưa. Mỗi lô ảnh chỉ được trả về đúng 1 lần
     * (đánh dấu consumed) để tránh mở lại modal xác nhận nhiều lần.
     */
    public function status(string $token)
    {
        $path = $this->manifestPath($token);

        if (! File::exists($path)) {
            return response()->json(['ready' => false]);
        }

        $data = json_decode(File::get($path), true) ?: [];
        $hasPhotos = ! empty($data['photos']);
        $consumed = $data['consumed'] ?? false;

        if ($hasPhotos && ! $consumed) {
            $data['consumed'] = true;
            File::put($path, json_encode($data));

            return response()->json(['ready' => true, 'photos' => $data['photos']]);
        }

        return response()->json(['ready' => false]);
    }
}
