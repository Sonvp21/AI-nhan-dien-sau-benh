<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisReport;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DiagnosisReportController extends Controller
{
    private array $crops = [
        'che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn',
        'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt',
    ];

    public function index(Request $request)
    {
        $query = DiagnosisReport::query()->with('user');

        $status = $request->filled('status') ? $request->status : 'pending';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('crop')) {
            $query->where('crop', $request->crop);
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        return view('admin.diagnosis-reports.index', [
            'reports' => $reports,
            'crops' => $this->crops,
            'status' => $status,
        ]);
    }

    public function show(DiagnosisReport $diagnosisReport)
    {
        $diagnosisReport->load('user', 'verifier');

        return view('admin.diagnosis-reports.show', ['report' => $diagnosisReport]);
    }

    public function approve(DiagnosisReport $diagnosisReport): RedirectResponse
    {
        $diagnosisReport->update([
            'status' => DiagnosisReport::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Đã duyệt, report hiện đã lên bản đồ dịch bệnh.');
    }

    public function reject(Request $request, DiagnosisReport $diagnosisReport): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $diagnosisReport->update([
            'status' => DiagnosisReport::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Đã từ chối report này.');
    }

    /**
     * Xóa hẳn 1 yêu cầu (bất kể trạng thái pending/verified/rejected) - xóa cả
     * ảnh đã lưu trên storage để tránh rác file, không thể khôi phục lại.
     */
    public function destroy(DiagnosisReport $diagnosisReport): RedirectResponse
    {
        if ($diagnosisReport->image_path) {
            Storage::disk('public')->delete($diagnosisReport->image_path);
        }

        $diagnosisReport->delete();

        return redirect()->route('admin.diagnosis-reports.index')->with('success', 'Đã xóa yêu cầu.');
    }
}
