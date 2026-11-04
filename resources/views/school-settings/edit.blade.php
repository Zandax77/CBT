@extends('layouts.app')
@section('title', 'Pengaturan Sekolah — ' . ($setting->school_name ?: 'CBT Sekolah'))

@section('content')
<div class="container py-2" style="max-width:720px">

    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="apple-stat-icon mb-0" style="background:var(--apple-blue-subtle); color:var(--apple-blue);">
            ⚙️
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Pengaturan Sekolah</h2>
            <p class="text-secondary small mb-0">Nama dan logo institusi ditampilkan di semua halaman aplikasi dan kartu identitas siswa</p>
        </div>
    </div>

    <div class="card p-4 p-md-5">
        <form method="POST" action="{{ route('admin.school-settings.update') }}" enctype="multipart/form-data">
            @csrf

            {{-- Nama Sekolah --}}
            <div class="mb-4">
                <label class="form-label" for="school_name">Nama Sekolah / Madrasah <span class="text-danger">*</span></label>
                <input type="text" name="school_name" id="school_name"
                       class="form-control @error('school_name') is-invalid @enderror"
                       value="{{ old('school_name', $setting->school_name) }}"
                       placeholder="Contoh: SMK Negeri 1 Jakarta" required>
                @error('school_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Alamat Sekolah --}}
            <div class="mb-4">
                <label class="form-label" for="school_address">Alamat Sekolah</label>
                <textarea name="school_address" id="school_address" rows="2"
                          class="form-control @error('school_address') is-invalid @enderror"
                          placeholder="Contoh: Jl. Pendidikan No. 1, Kota">{{ old('school_address', $setting->school_address) }}</textarea>
                @error('school_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Logo Sekolah --}}
            <div class="mb-4">
                <label class="form-label">Logo Sekolah</label>

                @if($setting->school_logo)
                <div class="glass-well p-3 mb-3 d-flex align-items-center gap-3">
                    <img src="{{ Storage::url($setting->school_logo) }}"
                         alt="Logo Sekolah"
                         style="height:60px;width:60px;object-fit:contain;border-radius:12px;background:#fff;padding:4px;border:1px solid rgba(0,0,0,0.1);box-shadow:0 4px 12px rgba(0,0,0,0.06);">
                    <div>
                        <div class="fw-semibold small">Logo Saat Ini</div>
                        <div class="form-check mt-1">
                            <input type="checkbox" name="remove_logo" id="remove_logo"
                                   class="form-check-input" value="1">
                            <label class="form-check-label text-danger small fw-semibold" for="remove_logo">Hapus logo ini</label>
                        </div>
                    </div>
                </div>
                @endif

                <input type="file" name="school_logo" id="school_logo"
                       class="form-control @error('school_logo') is-invalid @enderror"
                       accept="image/png,image/jpeg,image/svg+xml"
                       onchange="previewLogo(this)">
                <small class="text-secondary mt-1 d-block">Format: PNG, JPG, SVG. Maks 2MB. Disarankan rasio 1:1.</small>
                @error('school_logo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                {{-- Preview upload baru --}}
                <div id="logoPreviewWrap" class="glass-well p-2 mt-3" style="display:none">
                    <img id="logoPreview" src=""
                         style="height:64px;width:64px;object-fit:contain;border-radius:12px;
                                border:1px solid rgba(0,0,0,0.1);background:#fff;padding:4px"
                         alt="Preview">
                    <small class="text-secondary ms-2 fw-semibold">Preview logo baru terpilih</small>
                </div>
            </div>

            {{-- Preview Kartu --}}
            <div class="mb-4 pt-2 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
                <div class="form-label mb-3">
                    Pratinjau Kartu Siswa
                </div>
                <div style="transform-origin:top left;transform:scale(1.5);width:86mm;height:54mm;
                            border-radius:12px;overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,.12);
                            display:flex;flex-direction:column;background:#fff;border:1px solid rgba(0,0,0,.08);
                            margin-bottom:45px">
                    {{-- Header --}}
                    <div style="background:linear-gradient(135deg,#0071e3,#005bb5);
                                padding:5px 8px 4px;display:flex;align-items:center;gap:6px;flex-shrink:0">
                        <div id="preview-logo-wrap" style="{{ $setting->school_logo ? '' : 'display:none' }}">
                            <img id="preview-logo-img"
                                 src="{{ $setting->school_logo ? Storage::url($setting->school_logo) : '' }}"
                                 style="height:20px;width:20px;object-fit:contain;border-radius:4px;background:rgba(255,255,255,.2);padding:2px"
                                 alt="">
                        </div>
                        <div>
                            <div id="preview-school-name"
                                 style="font-size:7px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.5px;line-height:1.2">
                                {{ $setting->school_name }}
                            </div>
                            <div id="preview-school-address"
                                 style="font-size:5.5px;color:rgba(255,255,255,.8);line-height:1.2;margin-top:1px">
                                {{ $setting->school_address ?? '' }}
                            </div>
                            <div style="font-size:5.5px;color:rgba(255,255,255,.7);margin-top:1px;font-style:italic">
                                Kartu Identitas Siswa
                            </div>
                        </div>
                    </div>
                    {{-- Body --}}
                    <div style="flex:1;padding:5px 8px;display:flex;gap:7px;align-items:stretch">
                        {{-- Left: avatar --}}
                        <div style="width:28px;flex-shrink:0;display:flex;align-items:center;justify-content:center">
                            <div style="width:28px;height:28px;background:#e0e7ff;border-radius:50%;
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:12px">👤</div>
                        </div>
                        {{-- Right: info --}}
                        <div style="flex:1;display:flex;flex-direction:column;justify-content:center;gap:3px">
                            <div style="font-size:9px;font-weight:700;color:#0071e3;line-height:1.2">Nama Siswa</div>
                            <div style="font-size:6px;color:#64748b">Kelas XI RPL 1</div>
                            <div style="background:#eff6ff;border-radius:4px;padding:3px 5px;display:flex;flex-direction:column;gap:2px;margin-top:2px">
                                <div style="display:flex;gap:4px;font-size:6px">
                                    <span style="color:#64748b;width:36px;flex-shrink:0">No. Induk</span>
                                    <span style="color:#64748b">:</span>
                                    <span style="color:#0071e3;font-weight:700">2026001</span>
                                </div>
                                <div style="display:flex;gap:4px;font-size:6px">
                                    <span style="color:#64748b;width:36px;flex-shrink:0">Username</span>
                                    <span style="color:#64748b">:</span>
                                    <span style="color:#0071e3;font-weight:700">siswa2026</span>
                                </div>
                                <div style="display:flex;gap:4px;font-size:6px">
                                    <span style="color:#64748b;width:36px;flex-shrink:0">Password</span>
                                    <span style="color:#64748b">:</span>
                                    <span style="color:#ff3b30;font-weight:700;letter-spacing:.5px">••••••••</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Footer --}}
                    <div style="height:3px;background:linear-gradient(90deg,#0071e3,#5856d6);flex-shrink:0"></div>
                </div>
            </div>

            {{-- ── Akun Kepala Sekolah (Monitoring & Hasil) ── --}}
            <div class="mb-4 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge user-role-kepsek px-2 py-1">👑 KEPALA SEKOLAH</span>
                    <h6 class="fw-bold mb-0">Akun Kepala Sekolah (Monitoring &amp; Evaluasi)</h6>
                </div>
                <p class="text-secondary small mb-3">
                    Akun ini digunakan oleh Kepala Sekolah untuk memantau aktivitas ujian secara live (monitoring) dan mengevaluasi hasil nilai ujian.
                </p>

                <div class="glass-well p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Kepala Sekolah</label>
                            <input type="text" name="kepsek_name" class="form-control form-control-sm"
                                   value="{{ old('kepsek_name', $kepsek->name ?? 'Kepala Sekolah') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username Login</label>
                            <input type="text" class="form-control form-control-sm font-monospace" value="kepsek" disabled>
                            <small class="text-secondary" style="font-size:0.75rem;">Username baku untuk akun Kepala Sekolah.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Kata Sandi Baru (Opsional)</label>
                            <input type="password" name="kepsek_password" class="form-control form-control-sm"
                                   placeholder="Kosongkan jika tidak ingin mengubah kata sandi">
                            <small class="text-secondary" style="font-size:0.75rem;">Default awal: <code>Password123!</code> (minimal 6 karakter).</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
                <button type="submit" class="btn btn-primary px-4">
                    <span>💾</span> Simpan Pengaturan
                </button>
                <a href="{{ route('admin.students.print_cards') }}" target="_blank"
                   class="btn btn-secondary px-4">
                    <span>🖨️</span> Cetak Kartu
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function previewLogo(input) {
    const wrap = document.getElementById('logoPreviewWrap');
    const img  = document.getElementById('logoPreview');
    const prevLogoWrap = document.getElementById('preview-logo-wrap');
    const prevLogoImg  = document.getElementById('preview-logo-img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            wrap.style.display = 'flex';
            wrap.style.alignItems = 'center';
            prevLogoImg.src = e.target.result;
            prevLogoWrap.style.display = '';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Live preview: school name & address
document.getElementById('school_name').addEventListener('input', function() {
    document.getElementById('preview-school-name').textContent = this.value || 'Nama Sekolah';
});
document.getElementById('school_address').addEventListener('input', function() {
    document.getElementById('preview-school-address').textContent = this.value;
});
</script>
@endpush
@endsection
