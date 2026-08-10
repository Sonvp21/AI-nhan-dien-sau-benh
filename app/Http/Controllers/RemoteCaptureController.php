<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Chức năng "chế độ thuyết trình": người trình chiếu bấm chọn ảnh trên màn
 * hình lớn sẽ hiện mã QR, người dùng quét mã bằng điện thoại để mở trang
 * chụp ảnh riêng (agri-remote-capture). Toàn bộ thao tác (chọn mô hình cây
 * trồng, chụp ảnh, bấm chẩn đoán, gọi AI) diễn ra trên điện thoại; điện
 * thoại gửi kèm ảnh + mô hình đã chọn + kết quả chẩn đoán lên đây trong 1
 * lần upload. Màn hình trình chiếu chỉ polling endpoint status() để nhận
 * về và hiển thị thẳng kết quả, không cần xác nhận gì thêm.
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
     * Điện thoại gửi ảnh vừa chụp lên đây, kèm mô hình cây trồng đã chọn và
     * (nếu có) kết quả chẩn đoán AI đã tự tính sẵn trên điện thoại.
     */
    public function upload(Request $request, string $token)
    {
        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:10240',
            'crop' => 'nullable|string|max:50',
            'result' => 'nullable|string',
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

        $result = null;
        if ($request->filled('result')) {
            $decoded = json_decode((string) $request->input('result'), true);
            if (is_array($decoded)) {
                $result = $decoded;
            }
        }

        File::put($this->manifestPath($token), json_encode([
            'photos' => $urls,
            'crop' => $request->input('crop'),
            'result' => $result,
            'consumed' => false,
            'uploaded_at' => now()->toIso8601String(),
        ]));

        return response()->json(['ok' => true, 'photos' => $urls]);
    }

    /**
     * Màn hình trình chiếu gọi liên tục (polling) để kiểm tra đã có ảnh
     * (và kết quả) gửi từ điện thoại chưa. Mỗi lô chỉ được trả về đúng 1
     * lần (đánh dấu consumed) để tránh hiển thị lại kết quả cũ.
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

            return response()->json([
                'ready' => true,
                'photos' => $data['photos'],
                'crop' => $data['crop'] ?? null,
                'result' => $data['result'] ?? null,
            ]);
        }

        return response()->json(['ready' => false]);
    }
}
