<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use Illuminate\Http\Request;

class DiseaseController extends Controller
{
    private array $crops = [
        'che' => 'Chè', 'lua' => 'Lúa', 'ngo' => 'Ngô', 'san' => 'Sắn',
        'ca_chua' => 'Cà chua', 'xoai' => 'Xoài', 'ot' => 'Ớt',
    ];

    public function index(Request $request)
    {
        $query = Disease::query();

        if ($request->filled('crop_key')) {
            $query->where('crop_key', $request->crop_key);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('q')) {
            $query->where('name_vi', 'ilike', '%' . $request->q . '%');
        }

        $diseases = $query->orderBy('crop_key')->orderBy('name_vi')->paginate(20)->withQueryString();

        return view('admin.diseases.index', ['diseases' => $diseases, 'crops' => $this->crops]);
    }

    public function create()
    {
        return view('admin.diseases.form', ['disease' => new Disease(), 'crops' => $this->crops]);
    }

    public function store(Request $request)
    {
        Disease::create($this->validated($request));
        return redirect()->route('admin.diseases.index')->with('success', 'Đã thêm bệnh mới.');
    }

    public function edit(Disease $disease)
    {
        return view('admin.diseases.form', ['disease' => $disease, 'crops' => $this->crops]);
    }

    public function update(Request $request, Disease $disease)
    {
        $disease->update($this->validated($request));
        return redirect()->route('admin.diseases.index')->with('success', 'Đã cập nhật bệnh.');
    }

    public function destroy(Disease $disease)
    {
        $disease->delete();
        return redirect()->route('admin.diseases.index')->with('success', 'Đã xóa bệnh.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'crop_key' => 'required|string|max:30',
            'class_key' => 'required|string|max:150',
            'name_vi' => 'required|string|max:150',
            'pathogen' => 'nullable|string',
            'conditions' => 'nullable|string',
            'level' => 'required|in:Nhẹ,Trung bình,Nặng',
            'affected_organ' => 'nullable|string|max:30',
            'info_source' => 'required|in:tai_lieu_chuyen_nganh,ai_bien_soan',
            'steps_text' => 'nullable|string',
        ]);

        $steps = collect(explode("\n", $validated['steps_text'] ?? ''))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();

        return [
            'crop_key' => $validated['crop_key'],
            'class_key' => $validated['class_key'],
            'name_vi' => $validated['name_vi'],
            'pathogen' => $validated['pathogen'] ?? null,
            'conditions' => $validated['conditions'] ?? null,
            'level' => $validated['level'],
            'affected_organ' => $validated['affected_organ'] ?? 'lá',
            'info_source' => $validated['info_source'],
            'recommended_steps' => $steps,
        ];
    }
}
