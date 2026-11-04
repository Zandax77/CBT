@extends('layouts.app')
@section('title', ($question->exists ? 'Edit' : 'Tambah') . ' Soal — ' . $exam->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">{{ $question->exists ? 'Edit' : 'Tambah' }} Soal</h2>
        <p class="text-secondary small mb-0">{{ $exam->title }} · {{ $exam->subject }}</p>
    </div>
    <a href="{{ route('questions.list', $exam) }}" class="btn btn-secondary">← Kembali ke Bank Soal</a>
</div>

<div class="card p-4 p-md-5">
<form method="POST"
      action="{{ $question->exists ? route('questions.update', [$exam, $question]) : route('questions.store', $exam) }}"
      enctype="multipart/form-data">
    @csrf
    @if($question->exists) @method('PUT') @endif

    <div class="row g-4">
        {{-- Tipe Soal --}}
        <div class="col-md-5">
            <label class="form-label" for="typeSelect">Tipe Butir Soal <span class="text-danger">*</span></label>
            <select name="type" id="typeSelect" class="form-select" required>
                <option value="PG"          @selected(old('type', $question->type) === 'PG')>🔘 Pilihan Ganda</option>
                <option value="BENAR_SALAH" @selected(old('type', $question->type) === 'BENAR_SALAH')>⚖️ Benar / Salah</option>
                <option value="MENJODOHKAN" @selected(old('type', $question->type) === 'MENJODOHKAN')>🔗 Menjodohkan (Matching)</option>
            </select>
        </div>

        {{-- Bobot --}}
        <div class="col-md-3">
            <label class="form-label" for="weight">Bobot Skor <span class="text-danger">*</span></label>
            <input type="number" name="weight" id="weight" class="form-control" step="0.1" min="0.1" max="100"
                   value="{{ old('weight', $question->weight ?? 1) }}" required>
        </div>

        {{-- Teks Soal --}}
        <div class="col-12">
            <label class="form-label" for="question_text">Teks Pertanyaan <span class="text-danger">*</span></label>
            <textarea name="question_text" id="question_text" class="form-control" rows="4" required
                      maxlength="5000" placeholder="Tuliskan teks pertanyaan di sini...">{{ old('question_text', $question->question_text) }}</textarea>
        </div>

        {{-- Upload Gambar --}}
        <div class="col-12">
            <label class="form-label">Ilustrasi Gambar <small class="text-secondary fw-normal">(opsional, maks. 3 MB)</small></label>

            @if($question->exists && $question->image_path)
                <div class="glass-well p-3 mb-2 d-inline-block">
                    <img src="{{ Storage::url($question->image_path) }}"
                         alt="gambar soal"
                         style="max-height:160px; border-radius:12px; border:1px solid rgba(0,0,0,0.1); box-shadow:0 4px 12px rgba(0,0,0,0.06); display:block;">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="remove_image" value="1" id="removeImg" class="form-check-input">
                        <label for="removeImg" class="form-check-label text-danger small fw-semibold">Hapus gambar ini</label>
                    </div>
                </div>
            @endif

            <input type="file" name="image" class="form-control" accept="image/*" id="imageInput">
            <div id="imagePreview" class="mt-2" style="display:none">
                <div class="glass-well p-2 d-inline-block">
                    <img id="previewImg" src="" alt="preview" style="max-height:160px; border-radius:12px; border:1px solid rgba(0,0,0,0.1);">
                </div>
            </div>
        </div>
    </div>

    {{-- ──────────────── PG ──────────────── --}}
    <div id="section-PG" class="type-section glass-well mt-4 p-4">
        <h6 class="fw-bold mb-3">Pilihan Jawaban (A-E)</h6>
        <div class="row g-3">
            @foreach(['A'=>'opt_a','B'=>'opt_b','C'=>'opt_c','D'=>'opt_d','E'=>'opt_e'] as $key => $field)
            @php $optVal = old($field, ($question->exists && $question->type==='PG') ? ($question->options[$key] ?? '') : ''); @endphp
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text fw-bold" style="background:rgba(255,255,255,0.7); border-color:rgba(0,0,0,0.1); color:var(--apple-blue);">
                        {{ $key }}
                    </span>
                    <input type="text" name="{{ $field }}" class="form-control"
                           value="{{ $optVal }}"
                           placeholder="Opsi {{ $key }}{{ in_array($key,['C','D','E']) ? ' (opsional)' : '' }}">
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
            <label class="form-label fw-bold mb-2">Kunci Jawaban Benar</label>
            <div class="d-flex gap-3 flex-wrap">
                @foreach(['A','B','C','D','E'] as $k)
                @php $pgKey = old('answer_pg', ($question->exists && $question->type==='PG') ? $question->answer_key : 'A'); @endphp
                <div class="form-check user-select-none">
                    <input class="form-check-input" type="radio" name="answer_pg" value="{{ $k }}"
                           id="pg_{{ $k }}" @checked($pgKey === $k)>
                    <label class="form-check-label fw-bold ms-1" for="pg_{{ $k }}">{{ $k }}</label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ──────────────── Benar/Salah ──────────────── --}}
    <div id="section-BENAR_SALAH" class="type-section glass-well mt-4 p-4">
        <h6 class="fw-bold mb-3">Kunci Jawaban Benar / Salah</h6>
        <div class="d-flex gap-4">
            @php $bsKey = old('answer_bs', ($question->exists && $question->type==='BENAR_SALAH') ? $question->answer_key : 'A'); @endphp
            <div class="form-check user-select-none">
                <input class="form-check-input" type="radio" name="answer_bs" value="A"
                       id="bs_benar" @checked($bsKey === 'A')>
                <label class="form-check-label fw-semibold ms-1" for="bs_benar">✅ Benar</label>
            </div>
            <div class="form-check user-select-none">
                <input class="form-check-input" type="radio" name="answer_bs" value="B"
                       id="bs_salah" @checked($bsKey === 'B')>
                <label class="form-check-label fw-semibold ms-1" for="bs_salah">❌ Salah</label>
            </div>
        </div>
    </div>

    {{-- ──────────────── Menjodohkan ──────────────── --}}
    <div id="section-MENJODOHKAN" class="type-section glass-well mt-4 p-4">
        <h6 class="fw-bold mb-2">Premise (Kolom Kiri)</h6>
        @php
            $existingPremises = [];
            $existingPairs    = [];
            if ($question->exists && $question->type === 'MENJODOHKAN') {
                foreach ($question->options as $k => $v) {
                    if ($k !== '__pairs') $existingPremises[] = $v;
                }
                $existingPairs = $question->options['__pairs'] ?? [];
            }
        @endphp
        <div class="row g-2 mb-3" id="premiseList">
            @for($i = 0; $i < 5; $i++)
            @php $pVal = old("premise.$i", $existingPremises[$i] ?? ''); @endphp
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text fw-bold" style="background:rgba(255,255,255,0.7); border-color:rgba(0,0,0,0.1); color:var(--apple-blue);">
                        {{ chr(65+$i) }}
                    </span>
                    <input type="text" name="premise[]" class="form-control"
                           value="{{ $pVal }}"
                           placeholder="Premise {{ chr(65+$i) }}{{ $i >= 2 ? ' (opsional)' : '' }}">
                </div>
            </div>
            @endfor
        </div>

        <h6 class="fw-bold mb-2 mt-4">Pasangan Jawaban (Kolom Kanan)</h6>
        <div class="row g-2 mb-3" id="pairList">
            @for($i = 0; $i < 8; $i++)
            @php $pairVal = old("pair.$i", $existingPairs[$i] ?? ''); @endphp
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text fw-bold" style="background:rgba(255,255,255,0.7); border-color:rgba(0,0,0,0.1); color:var(--apple-green);">
                        {{ $i+1 }}
                    </span>
                    <input type="text" name="pair[]" class="form-control"
                           value="{{ $pairVal }}"
                           placeholder="Pasangan {{ $i+1 }}{{ $i >= 2 ? ' (opsional)' : '' }}">
                </div>
            </div>
            @endfor
        </div>

        <div class="mt-4 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
            <label class="form-label fw-bold" for="match_key">Kunci Pasangan Jawaban</label>
            @php $matchKey = old('match_key', ($question->exists && $question->type==='MENJODOHKAN') ? $question->answer_key : ''); @endphp
            <input type="text" name="match_key" id="match_key" class="form-control" style="max-width:320px"
                   value="{{ $matchKey }}"
                   placeholder="Contoh: A:1,B:3,C:2">
            <small class="text-secondary mt-1 d-block">Format: <code>A:1,B:3,C:2</code> — premise:nomor_pasangan.</small>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top d-flex gap-2" style="border-color:rgba(0,0,0,0.06) !important;">
        <button class="btn btn-primary px-4" type="submit">
            <span>💾</span> Simpan Soal
        </button>
        <a href="{{ route('questions.list', $exam) }}" class="btn btn-secondary px-4">Batal</a>
    </div>
</form>
</div>

@push('scripts')
<script>
const typeSelect = document.getElementById('typeSelect');
const sections   = document.querySelectorAll('.type-section');

function showSection(val) {
    sections.forEach(s => s.style.display = 'none');
    const el = document.getElementById('section-' + val);
    if (el) el.style.display = 'block';
}

if (typeSelect) {
    typeSelect.addEventListener('change', () => showSection(typeSelect.value));
    showSection(typeSelect.value);
}

// Preview gambar
const imageInput = document.getElementById('imageInput');
if (imageInput) {
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}
</script>
@endpush
@endsection
