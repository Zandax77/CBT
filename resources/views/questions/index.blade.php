@extends('layouts.app')
@section('title', 'Kelola Soal — ' . $exam->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Kelola Soal</h2>
        <p class="text-secondary small mb-0">{{ $exam->title }} · {{ $exam->subject }}
            @if($exam->class_name) · <span class="badge bg-secondary">{{ $exam->class_name }}</span> @endif
        </p>
    </div>
    <a href="{{ route('exams.index') }}" class="btn btn-secondary">← Kembali ke Ujian</a>
</div>

{{-- ── Dua Cara Menambah Soal ───────────────────────────────────── --}}
<div class="row g-4 mb-4">
    {{-- Cara 1: Tambah Manual --}}
    <div class="col-md-5 col-lg-4">
        <div class="card h-100 p-3" style="border-color: rgba(0, 113, 227, 0.35) !important;">
            <div class="card-body d-flex flex-column justify-content-between p-2">
                <div>
                    <div class="apple-stat-icon mb-2" style="background: var(--apple-blue-subtle); color: var(--apple-blue);">
                        ✏️
                    </div>
                    <h5 class="fw-bold mb-1">Tambah Soal Manual</h5>
                    <p class="text-secondary small mb-3">
                        Buat soal satu per satu dengan dukungan <strong>upload gambar</strong> untuk ilustrasi, diagram, atau rumus.
                    </p>
                </div>
                <a href="{{ route('questions.create', $exam) }}" class="btn btn-primary w-100 py-2">
                    <span>+</span> Tambah Soal Baru
                </a>
            </div>
        </div>
    </div>

    {{-- Cara 2: Impor Excel --}}
    <div class="col-md-7 col-lg-8">
        <div class="card h-100 p-3" style="border-color: rgba(52, 199, 89, 0.35) !important;">
            <div class="card-body p-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="apple-stat-icon mb-0" style="width:38px;height:38px;font-size:1.1rem;background: var(--apple-green-subtle); color: var(--apple-green);">
                            📥
                        </div>
                        <h5 class="fw-bold mb-0">Impor Soal Excel</h5>
                    </div>
                    <a href="{{ route('questions.template') }}" class="btn btn-sm btn-outline-success">
                        <span>⬇</span> Unduh Template (.xlsx)
                    </a>
                </div>

                <p class="text-secondary small mb-3">
                    Unggah lembar kerja Excel untuk memasukkan puluhan butir soal sekaligus secara otomatis.
                </p>

                <form method="post" enctype="multipart/form-data"
                      action="{{ route('questions.import', $exam) }}"
                      id="importForm" class="glass-well p-3">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">File Excel (.xlsx)</label>
                            <input type="file" name="file" accept=".xlsx" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mode Impor</label>
                            <select name="mode" class="form-select form-select-sm" id="modeSelect">
                                <option value="append">➕ Tambahkan ke soal yang ada</option>
                                <option value="replace">🔄 Ganti semua soal (hapus lama)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-sm w-100" id="importBtn">
                                Validasi &amp; Impor
                            </button>
                        </div>
                    </div>
                    <small class="text-secondary d-block mt-2" id="modeHint" style="font-size: 0.78rem;">
                        Soal dari Excel akan <b>ditambahkan</b> setelah soal yang sudah ada.
                    </small>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── Daftar Soal ─────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="fw-bold mb-0">Daftar Soal</h5>
        <span class="badge bg-primary">{{ $questions->count() }} butir</span>
    </div>
    @if($questions->count())
    <span class="badge bg-secondary">Total Bobot: {{ $questions->sum('weight') }}</span>
    @endif
</div>

@if($questions->isEmpty())
    <div class="card p-5 text-center text-muted">
        <div style="font-size:3.5rem">📝</div>
        <h5 class="fw-bold mt-3 text-dark">Belum Ada Soal Ditambahkan</h5>
        <p class="mb-0 text-secondary">Gunakan tombol manual atau impor dari Excel di atas untuk mulai membuat bank soal.</p>
    </div>
@else
<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:50px" class="text-center">No.</th>
                    <th>Pertanyaan</th>
                    <th style="width:130px">Tipe</th>
                    <th style="width:70px" class="text-center">Bobot</th>
                    <th style="width:90px" class="text-center">Gambar</th>
                    <th style="width:1%; white-space:nowrap;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($questions as $q)
                <tr>
                    <td class="text-center text-secondary fw-bold">{{ $q->position }}</td>
                    <td>
                        <div style="max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                             title="{{ $q->question_text }}" class="fw-semibold">
                            {{ $q->question_text }}
                        </div>
                    </td>
                    <td>
                        @if($q->type === 'PG')
                            <span class="badge bg-primary">Pilihan Ganda</span>
                        @elseif($q->type === 'BENAR_SALAH')
                            <span class="badge bg-info">Benar/Salah</span>
                        @else
                            <span class="badge bg-warning">Menjodohkan</span>
                        @endif
                    </td>
                    <td class="text-center fw-bold">{{ $q->weight }}</td>
                    <td class="text-center">
                        @if($q->image_path)
                            <img src="{{ Storage::url($q->image_path) }}"
                                 alt="gambar soal"
                                 style="height:40px;width:60px;object-fit:cover;border-radius:8px;cursor:pointer;border:1px solid rgba(0,0,0,0.1);box-shadow:0 2px 6px rgba(0,0,0,0.06);"
                                 onclick="document.getElementById('prev-{{ $q->id }}').showModal()">
                            <dialog id="prev-{{ $q->id }}" style="border:none;border-radius:20px;padding:0;max-width:90vw;background:rgba(255,255,255,0.9);backdrop-filter:blur(24px);box-shadow:0 20px 60px rgba(0,0,0,0.3)">
                                <div style="position:relative;padding:12px;">
                                    <img src="{{ Storage::url($q->image_path) }}" style="max-width:80vw;max-height:80vh;display:block;border-radius:14px;">
                                    <button onclick="document.getElementById('prev-{{ $q->id }}').close()"
                                        style="position:absolute;top:18px;right:18px;background:rgba(0,0,0,0.6);color:#fff;border:none;border-radius:50%;width:34px;height:34px;font-size:1.1rem;cursor:pointer;">✕</button>
                                </div>
                            </dialog>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <div class="table-action-group justify-content-center">
                            <a href="{{ route('questions.edit', [$exam, $q]) }}" class="btn-action btn-action-secondary" title="Edit Soal">
                                <span>✏️</span> Edit
                            </a>
                            <form method="POST" action="{{ route('questions.destroy', [$exam, $q]) }}"
                                  onsubmit="return confirm('Hapus soal ini?')" class="m-0">
                                @csrf @method('DELETE')
                                <button class="btn-action btn-action-danger" title="Hapus Soal">
                                    <span>🗑️</span> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold text-secondary">Total Bobot Ujian:</td>
                    <td class="text-center fw-bold text-primary">{{ $questions->sum('weight') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Panduan format soal (collapsible) --}}
<div class="mt-4">
    <a class="btn btn-sm btn-secondary" data-bs-toggle="collapse" href="#panduanFormat">
        <span>📖</span> Panduan Format Soal Excel
    </a>
    <div class="collapse mt-2" id="panduanFormat">
        <div class="card p-3 small">
            <table class="table table-bordered mb-2">
                <thead>
                    <tr><th>Tipe</th><th>Penjelasan</th><th>Kunci Jawaban</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>PG</code></td>
                        <td>Pilihan ganda. Isi opsi_a – opsi_e (minimal A & B).</td>
                        <td>A / B / C / D / E</td>
                    </tr>
                    <tr>
                        <td><code>BENAR_SALAH</code></td>
                        <td>Pernyataan benar atau salah. opsi_a = "Benar", opsi_b = "Salah".</td>
                        <td>A (Benar) / B (Salah)</td>
                    </tr>
                    <tr>
                        <td><code>MENJODOHKAN</code></td>
                        <td>opsi_a–opsi_e = kolom kiri (premise). pasangan_1–pasangan_8 = kolom kanan.</td>
                        <td><code>A:1,B:3,C:2</code></td>
                    </tr>
                </tbody>
            </table>
            <div class="alert alert-warning py-2 mb-0">
                <span>ℹ️</span> <b>Catatan Menjodohkan:</b> Nilai bobot dibagi rata per pasangan benar (penilaian parsial).
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const modeSelect = document.getElementById('modeSelect');
const modeHint   = document.getElementById('modeHint');
const hints = {
    append:  'Soal dari Excel akan <b>ditambahkan</b> setelah soal yang sudah ada.',
    replace: '⚠️ <b>Semua soal yang ada (termasuk yang bergambar) akan dihapus</b> dan diganti soal dari Excel.',
};
if (modeSelect) {
    modeSelect.addEventListener('change', function() {
        modeHint.innerHTML = hints[this.value] || '';
        modeHint.style.color = this.value === 'replace' ? 'var(--apple-red)' : '';
    });
}

const importForm = document.getElementById('importForm');
if (importForm) {
    importForm.addEventListener('submit', function(e) {
        if (modeSelect && modeSelect.value === 'replace') {
            if (!confirm('⚠️ Mode "Ganti Semua": seluruh soal yang sudah ada (termasuk yang bergambar) akan dihapus permanen.\n\nYakin melanjutkan?')) {
                e.preventDefault();
            }
        }
    });
}
</script>
@endpush

@endsection
