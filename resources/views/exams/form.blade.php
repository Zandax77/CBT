@extends('layouts.app')
@section('title', ($exam->exists ? 'Edit' : 'Buat') . ' Ujian')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">{{ $exam->exists ? 'Edit' : 'Buat' }} Ujian</h2>
        <p class="text-secondary small mb-0">Atur parameter ujian, target kelompok kelas/jurusan, alokasi waktu, dan masa aktif token</p>
    </div>
    <a href="{{ route('exams.index') }}" class="btn btn-secondary">← Kembali ke Daftar</a>
</div>

<div class="card p-4 p-md-5">
    <form method="post" action="{{ $exam->exists ? route('exams.update', $exam) : route('exams.store') }}">
        @csrf
        @if($exam->exists) @method('PUT') @endif

        <div class="row g-4">
            {{-- Informasi Dasar --}}
            <div class="col-md-8">
                <label class="form-label" for="title">Nama Ujian <span class="text-danger">*</span></label>
                <input class="form-control @error('title') is-invalid @enderror" 
                       id="title"
                       name="title" 
                       required 
                       value="{{ old('title', $exam->title) }}" 
                       placeholder="Contoh: Penilaian Akhir Semester Ganjil 2026/2027">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="subject">Mata Pelajaran <span class="text-danger">*</span></label>
                <input class="form-control @error('subject') is-invalid @enderror" 
                       id="subject"
                       name="subject" 
                       required 
                       value="{{ old('subject', $exam->subject) }}" 
                       placeholder="Contoh: Matematika">
                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- ── Target Kelompok Peserta (Tingkat, Jurusan, Kelas, Umum) ── --}}
            <div class="col-12">
                <div class="glass-well p-4">
                    <h6 class="fw-bold mb-1">🎯 Target Kelompok Peserta Ujian</h6>
                    <p class="text-secondary small mb-3">Tentukan siapa saja peserta yang berhak melihat dan mengikuti ujian ini</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="target_type">Kategori Target <span class="text-danger">*</span></label>
                            <select name="target_type" id="target_type" class="form-select" onchange="toggleTargetFields()">
                                <option value="umum" @selected(old('target_type', $exam->target_type ?? 'umum') === 'umum')>🌐 Umum (Semua Siswa)</option>
                                <option value="tingkat" @selected(old('target_type', $exam->target_type) === 'tingkat')>📚 Kelompok Tingkat (Misal: Kelas 10 / 11 / 12)</option>
                                <option value="jurusan" @selected(old('target_type', $exam->target_type) === 'jurusan')>🔧 Jurusan (Misal: RPL, TKJ, IPA, IPS)</option>
                                <option value="kelas" @selected(old('target_type', $exam->target_type) === 'kelas')>🏷️ Kelas Tertentu (Misal: XI RPL 1)</option>
                            </select>
                        </div>

                        {{-- Target Tingkat --}}
                        <div class="col-md-4" id="wrap_target_level" style="display:none;">
                            <label class="form-label fw-semibold" for="target_level">Pilih / Tulis Tingkat</label>
                            <input class="form-control" 
                                   id="target_level"
                                   name="target_level" 
                                   value="{{ old('target_level', $exam->target_level) }}" 
                                   placeholder="Contoh: X, XI, XII atau 10, 11, 12">
                            <small class="text-secondary">Ujian akan terbuka untuk seluruh kelas di tingkat ini.</small>
                        </div>

                        {{-- Target Jurusan --}}
                        <div class="col-md-4" id="wrap_target_major" style="display:none;">
                            <label class="form-label fw-semibold" for="target_major">Pilih / Tulis Jurusan</label>
                            <input class="form-control" 
                                   id="target_major"
                                   name="target_major" 
                                   value="{{ old('target_major', $exam->target_major) }}" 
                                   placeholder="Contoh: RPL, TKJ, AKL, IPA, dsb">
                            <small class="text-secondary">Ujian ditujukan khusus program keahlian ini.</small>
                        </div>

                        {{-- Target Kelas Spesifik --}}
                        <div class="col-md-8" id="wrap_target_class" style="display:none;">
                            <label class="form-label fw-semibold" for="class_name">Nama Kelas Tertentu</label>
                            <input class="form-control" 
                                   id="class_name"
                                   name="class_name" 
                                   value="{{ old('class_name', $exam->class_name) }}" 
                                   placeholder="Contoh: XI RPL 1 (bisa multiple dipisah koma: XI RPL 1, XI RPL 2)">
                            <small class="text-secondary">Hanya siswa dengan nama kelas tersebut yang dapat mengakses ujian.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Waktu & Durasi --}}
            <div class="col-md-4">
                <label class="form-label" for="starts_at">Waktu Mulai <span class="text-danger">*</span></label>
                <input class="form-control @error('starts_at') is-invalid @enderror" 
                       type="datetime-local" 
                       id="starts_at"
                       name="starts_at" 
                       required
                       value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}">
                @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="ends_at">Waktu Selesai <span class="text-danger">*</span></label>
                <input class="form-control @error('ends_at') is-invalid @enderror" 
                       type="datetime-local" 
                       id="ends_at"
                       name="ends_at" 
                       required
                       value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}">
                @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="duration_minutes">Durasi Pengerjaan (menit) <span class="text-danger">*</span></label>
                <input class="form-control @error('duration_minutes') is-invalid @enderror" 
                       type="number" 
                       min="1" 
                       max="600" 
                       id="duration_minutes"
                       name="duration_minutes" 
                       required
                       value="{{ old('duration_minutes', $exam->duration_minutes ?? 60) }}">
                @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- ── Token & Pengaturan Masa Aktif Token ── --}}
            <div class="col-12">
                <div class="glass-well p-4">
                    <h6 class="fw-bold mb-1">🔑 Pengaturan Token &amp; Masa Aktif</h6>
                    <p class="text-secondary small mb-3">Atur kode token, batas durasi kedaluwarsa token, atau rotasi berkala</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="token">Kode Token</label>
                            <input class="form-control font-monospace fw-bold" 
                                   id="token"
                                   name="token" 
                                   value="{{ old('token', $exam->token) }}" 
                                   placeholder="Kosongkan = token acak 6 digit">
                            <small class="text-secondary">Dapat diisi manual atau dibiarkan kosong agar digenerate acak.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="token_valid_minutes">Masa Aktif Token (menit)</label>
                            <input class="form-control" 
                                   type="number" 
                                   min="1" 
                                   max="1440" 
                                   id="token_valid_minutes"
                                   name="token_valid_minutes"
                                   value="{{ old('token_valid_minutes', $exam->token_valid_minutes) }}"
                                   placeholder="Misal: 15 (kosong = tanpa batas)">
                            <small class="text-secondary">Token otomatis kedaluwarsa setelah N menit. Siswa harus meminta token baru jika terlewat.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="token_rotate_minutes">Rotasi Otomatis (menit)</label>
                            <input class="form-control" 
                                   type="number" 
                                   min="1" 
                                   max="120" 
                                   id="token_rotate_minutes"
                                   name="token_rotate_minutes"
                                   value="{{ old('token_rotate_minutes', $exam->token_rotate_minutes) }}"
                                   placeholder="Kosongkan jika tidak rotasi">
                            <small class="text-secondary">Sistem akan mengganti token acak baru setiap N menit saat ujian berlangsung.</small>
                        </div>

                        @if($exam->exists && $exam->token_valid_minutes)
                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reset_token_expiry" value="1" id="reset_token_expiry">
                                <label class="form-check-label small fw-semibold" for="reset_token_expiry">
                                    🔄 Perbarui &amp; reset hitungan masa aktif token mulai dari sekarang
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="glass-well p-3 d-flex align-items-center">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="published"
                            @checked(old('is_published', $exam->is_published))>
                        <label class="form-check-label fw-semibold ms-2" for="published" style="cursor: pointer;">
                            Terbitkan Ujian Sekarang (Siswa dapat melihat jadwal ujian ini di daftar ujian mereka)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
            <button class="btn btn-primary px-4">
                <span>💾</span> Simpan Ujian
            </button>
            <a href="{{ route('exams.index') }}" class="btn btn-secondary px-4">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleTargetFields() {
    const type = document.getElementById('target_type').value;
    const wrapLevel = document.getElementById('wrap_target_level');
    const wrapMajor = document.getElementById('wrap_target_major');
    const wrapClass = document.getElementById('wrap_target_class');

    wrapLevel.style.display = 'none';
    wrapMajor.style.display = 'none';
    wrapClass.style.display = 'none';

    if (type === 'tingkat') {
        wrapLevel.style.display = 'block';
    } else if (type === 'jurusan') {
        wrapMajor.style.display = 'block';
        wrapLevel.style.display = 'block'; // Jurusan bisa dikombinasikan dengan tingkat misal XII RPL
    } else if (type === 'kelas') {
        wrapClass.style.display = 'block';
    }
}
document.addEventListener('DOMContentLoaded', toggleTargetFields);
</script>
@endpush
@endsection
