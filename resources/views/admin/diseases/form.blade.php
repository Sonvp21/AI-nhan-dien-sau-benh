@extends('layouts.admin')

@section('title', $disease->exists ? 'Sửa bệnh' : 'Thêm bệnh mới')

@section('content')
  <a href="{{ route('admin.diseases.index') }}" class="text-sm font-semibold" style="color:var(--forest)">← Quay lại danh sách bệnh</a>

  <h1 class="text-xl font-bold mt-4 mb-6">{{ $disease->exists ? 'Sửa bệnh: '.$disease->name_vi : 'Thêm bệnh mới' }}</h1>

  @if($errors->any())
    <div class="mb-5 bg-red-50 text-red-700 text-sm px-4 py-3 rounded-lg max-w-2xl">
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ $disease->exists ? route('admin.diseases.update', $disease) : route('admin.diseases.store') }}" class="bg-white p-6 rounded-xl border space-y-5 max-w-2xl">
    @csrf
    @if($disease->exists) @method('PUT') @endif

    <div>
      <label class="block text-sm font-semibold mb-1.5">Cây trồng *</label>
      <select name="crop_key" required class="w-full border rounded-lg px-3 py-2.5 text-sm">
        <option value="">-- Chọn cây --</option>
        @foreach($crops as $key => $label)
          <option value="{{ $key }}" @selected(old('crop_key', $disease->crop_key)==$key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold mb-1.5">Class key (tên lớp trong dataset) *</label>
        <input type="text" name="class_key" value="{{ old('class_key', $disease->class_key) }}" required
               placeholder="vd: brown blight" class="w-full border rounded-lg px-3 py-2.5 text-sm font-mono">
        <p class="text-xs text-gray-400 mt-1">Phải khớp chính xác với tên lớp model AI trả về</p>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1.5">Tên bệnh (tiếng Việt) *</label>
        <input type="text" name="name_vi" value="{{ old('name_vi', $disease->name_vi) }}" required
               placeholder="vd: Đốm nâu" class="w-full border rounded-lg px-3 py-2.5 text-sm">
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1.5">Tác nhân gây bệnh</label>
      <input type="text" name="pathogen" value="{{ old('pathogen', $disease->pathogen) }}"
             placeholder="vd: Nấm Colletotrichum camelliae" class="w-full border rounded-lg px-3 py-2.5 text-sm">
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1.5">Điều kiện phát sinh</label>
      <textarea name="conditions" rows="2" class="w-full border rounded-lg px-3 py-2.5 text-sm">{{ old('conditions', $disease->conditions) }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold mb-1.5">Mức độ *</label>
        <select name="level" required class="w-full border rounded-lg px-3 py-2.5 text-sm">
          @foreach(['Nhẹ','Trung bình','Nặng'] as $lv)
            <option value="{{ $lv }}" @selected(old('level', $disease->level)==$lv)>{{ $lv }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1.5">Cơ quan bị ảnh hưởng</label>
        <select name="affected_organ" class="w-full border rounded-lg px-3 py-2.5 text-sm">
          @foreach(['lá','thân','rễ','củ','quả','toàn cây'] as $organ)
            <option value="{{ $organ }}" @selected(old('affected_organ', $disease->affected_organ ?? 'lá')==$organ)>{{ ucfirst($organ) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1.5">Nguồn thông tin *</label>
      <select name="info_source" required class="w-full border rounded-lg px-3 py-2.5 text-sm">
        <option value="tai_lieu_chuyen_nganh" @selected(old('info_source', $disease->info_source)=='tai_lieu_chuyen_nganh')>Tài liệu chuyên ngành (đã kiểm chứng)</option>
        <option value="ai_bien_soan" @selected(old('info_source', $disease->info_source)=='ai_bien_soan')>AI biên soạn (cần kiểm chứng lại)</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1.5">Các bước xử lý (mỗi dòng 1 bước)</label>
      <textarea name="steps_text" rows="5" class="w-full border rounded-lg px-3 py-2.5 text-sm"
        placeholder="Bước 1&#10;Bước 2&#10;Bước 3">{{ old('steps_text', $disease->exists ? implode("\n", $disease->recommended_steps ?? []) : '') }}</textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <button class="text-sm font-semibold text-white px-5 py-2.5 rounded-lg" style="background:var(--forest)">
        {{ $disease->exists ? 'Lưu thay đổi' : 'Thêm bệnh' }}
      </button>
      <a href="{{ route('admin.diseases.index') }}" class="text-sm font-semibold px-5 py-2.5 rounded-lg border">Hủy</a>
    </div>
  </form>
@endsection
