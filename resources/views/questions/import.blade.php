@extends('layouts.app')
@section('title', 'Impor Soal — ' . $exam->title)
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Impor Soal: {{ $exam->title }}</h2>
        <p class="text-secondary small mb-0">{{ $exam->subject }} · Unggah file format Excel (.xlsx)</p>
    </div>
    <a href="{{ route('questions.list', $exam) }}" class="btn btn-secondary">← Kembali ke Bank Soal</a>
</div>

<div class="card p-4 p-md-5">
    {{-- Tampilkan error per baris --}}
    @if($errors->has('file'))
    <div class="alert alert-danger mb-4">
        <span>⚠️</span>
        <div>
            <strong>Terdapat kesalahan pada file:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->get('file') as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="glass-well p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h6 class="fw-bold mb-1">Unduh Format Lembar Kerja Excel</h6>
            <p class="text-secondary small mb-0">Gunakan template resmi agar susunan kolom sesuai dengan pembaca otomatis.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('questions.template') }}">
            <span>⬇</span> Unduh Template Excel
        </a>
    </div>

    <form method="post" enctype="multipart/form-data" action="{{ route('questions.import', $exam) }}" id="importExcelForm">
        @csrf

        <div class="mb-4">
            <label class="form-label fw-bold">Pilih File Excel (.xlsx, maks 5 MB)</label>
            <input type="file" name="file" accept=".xlsx" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Pilih Mode Impor</label>
            <div class="d-flex flex-column gap-2">
                <div class="form-check glass-well p-3 m-0">
                    <input class="form-check-input" type="radio" name="mode" id="modeAppend" value="append" checked>
                    <label class="form-check-label fw-semibold ms-2" for="modeAppend" style="cursor:pointer;">
                        <strong>Tambahkan (Append)</strong> — Soal dari file akan ditambahkan setelah butir soal yang sudah ada.
                    </label>
                </div>
                <div class="form-check glass-well p-3 m-0" style="border-color: rgba(255, 59, 48, 0.3) !important;">
                    <input class="form-check-input" type="radio" name="mode" id="modeReplace" value="replace">
                    <label class="form-check-label ms-2 text-danger fw-semibold" for="modeReplace" style="cursor:pointer;">
                        <strong>Ganti Semua (Replace)</strong> — Seluruh soal lama akan dihapus permanen dan digantikan oleh soal baru.
                    </label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary px-4" id="submitImportBtn">
                <span>📤</span> Validasi &amp; Impor Sekarang
            </button>
            <a href="{{ route('questions.list', $exam) }}" class="btn btn-secondary px-4">Batal</a>
        </div>
    </form>

    <div class="mt-5 pt-4 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
        <h6 class="fw-bold mb-3">Panduan Format Kolom Excel</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-3">
                <thead>
                    <tr>
                        <th style="width: 140px;">Tipe</th>
                        <th>Penjelasan</th>
                        <th style="width: 160px;">Kunci Jawaban</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>PG</code></td>
                        <td>Pilihan ganda biasa. Isi kolom opsi_a sampai opsi_e (minimal A &amp; B).</td>
                        <td>A / B / C / D / E</td>
                    </tr>
                    <tr>
                        <td><code>BENAR_SALAH</code></td>
                        <td>Pernyataan logika benar atau salah. opsi_a = "Benar", opsi_b = "Salah".</td>
                        <td>A (Benar) / B (Salah)</td>
                    </tr>
                    <tr>
                        <td><code>MENJODOHKAN</code></td>
                        <td>
                            Hubungkan pasangan:
                            <ul class="mb-0 small ps-3">
                                <li><b>opsi_a – opsi_e</b>: kolom kiri (premise)</li>
                                <li><b>pasangan_1 – pasangan_8</b>: kolom kanan</li>
                            </ul>
                        </td>
                        <td><code>A:1,B:3,C:2</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning py-2 small mb-0">
            <span>ℹ️</span> <b>Catatan Menjodohkan:</b> Nilai bobot dibagi rata secara otomatis untuk setiap pasangan yang dijawab benar oleh peserta.
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('importExcelForm').addEventListener('submit', function(e) {
    const isReplace = document.getElementById('modeReplace').checked;
    if (isReplace) {
        if (!confirm('⚠️ PERINGATAN: Mode "Ganti Semua" akan menghapus seluruh butir soal yang sudah ada di ujian ini.\n\nApakah Anda yakin ingin melanjutkan?')) {
            e.preventDefault();
        }
    }
});
</script>
@endpush
@endsection
