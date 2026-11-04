@extends('layouts.app')
@section('title', 'Pengelolaan Guru')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Pengelolaan Guru</h2>
        <p class="text-secondary small mb-0">Kelola akun guru pengampu, pembuatan ujian, dan hak akses soal</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
            <span>+</span> Tambah Guru
        </a>
        <a href="{{ route('admin.teachers.template') }}" class="btn btn-outline-success">
            <span>⬇</span> Template Excel
        </a>
    </div>
</div>

{{-- Tampilkan kredensial hasil import --}}
@if(session('import_credentials'))
    <div class="alert alert-warning mb-4 flex-column align-items-start">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="fs-4">⚠️</span>
            <strong>Simpan data berikut sekarang! Username &amp; password tidak dapat dilihat lagi setelah halaman ditutup.</strong>
        </div>
        <div class="table-responsive w-100 mt-2">
            <table class="table table-sm bg-white rounded-3 mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">No.</th>
                        <th>Nama Lengkap</th>
                        <th>Username Login</th>
                        <th>Password</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(session('import_credentials') as $index => $cred)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold">{{ $cred['nama'] }}</td>
                            <td><code>{{ $cred['username'] }}</code></td>
                            <td><code class="text-danger fw-bold">{{ $cred['password'] }}</code></td>
                            <td>{{ $cred['email'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-secondary mt-2">Salin atau catat tabel ini dan bagikan secara langsung kepada guru yang bersangkutan.</small>
    </div>
@endif

{{-- Card Impor Excel --}}
<div class="card p-4 mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="apple-stat-icon mb-0" style="width:36px;height:36px;font-size:1rem;background:var(--apple-green-subtle);color:var(--apple-green);">
            📥
        </div>
        <h5 class="fw-bold mb-0">Impor Akun Guru dari Excel</h5>
    </div>
    <p class="text-secondary small mb-3">
        Unggah file Excel dengan kolom <strong>nama</strong> (wajib), <strong>username</strong>, <strong>password</strong> (minimal 8 karakter), dan <strong>email</strong>.
    </p>
    <form method="POST" action="{{ route('admin.teachers.import') }}" enctype="multipart/form-data" class="glass-well p-3">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small fw-semibold">Pilih File Spreadsheet (.xlsx, .xls)</label>
                <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success btn-sm w-100">
                    <span>📤</span> Unggah &amp; Impor
                </button>
            </div>
        </div>
        @error('file')
            <div class="text-danger small mt-2">{!! nl2br(e($message)) !!}</div>
        @enderror
    </form>
</div>

{{-- Filter & Pencarian --}}
<div class="card p-4 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-9">
            <label class="form-label small fw-semibold">Cari Guru</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                placeholder="Cari berdasarkan nama atau username...">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Cari</button>
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Tabel Guru dengan Form Hapus Massal --}}
<div class="card p-4">
    <form method="POST" action="{{ route('admin.teachers.bulk_destroy') }}" id="bulkTeacherForm"
          onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh akun guru yang dipilih? Seluruh ujian yang dibuat oleh guru tersebut dan riwayat pengerjaannya akan ikut dihapus secara permanen.')">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0">Daftar Guru Terdaftar</h5>
                <span class="badge bg-secondary">{{ $teachers->total() }} guru</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-outline-danger" id="btnBulkDeleteTeachers" disabled>
                    🗑️ Hapus Guru Terpilih (<span id="selectedCountTeachers">0</span>)
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px" class="text-center">
                            <input type="checkbox" class="form-check-input" id="checkAllTeachers" onclick="toggleAllTeachers(this)">
                        </th>
                        <th style="width:50px" class="text-center">No.</th>
                        <th>Nama Lengkap</th>
                        <th>Username Login</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" class="form-check-input teacher-checkbox" onclick="updateBulkDeleteTeachersState()">
                            </td>
                            <td class="text-center text-secondary fw-bold">{{ $teachers->firstItem() + $loop->index }}</td>
                            <td class="fw-bold text-dark">{{ $teacher->name }}</td>
                            <td><code>{{ $teacher->username }}</code></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div class="table-action-group justify-content-center">
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn-action btn-action-secondary" title="Edit Data Guru">
                                        <span>✏️</span> Edit
                                    </a>
                                    <button type="button" class="btn-action btn-action-danger"
                                        onclick="confirmSingleDeleteTeacher('{{ $teacher->id }}', '{{ addslashes($teacher->name) }}')" title="Hapus Guru">
                                        <span>🗑️</span> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <div style="font-size:2.5rem;">👨‍🏫</div>
                                <div class="mt-2">Belum ada data guru terdaftar.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Form Single Delete tersembunyi agar tidak bertabrakan dengan form bulk --}}
    <form id="singleDeleteTeacherForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    @if($teachers->hasPages())
    <div class="mt-3">
        {{ $teachers->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleAllTeachers(master) {
    const checkboxes = document.querySelectorAll('.teacher-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBulkDeleteTeachersState();
}

function updateBulkDeleteTeachersState() {
    const checkboxes = document.querySelectorAll('.teacher-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.teacher-checkbox');
    const master = document.getElementById('checkAllTeachers');
    const btn = document.getElementById('btnBulkDeleteTeachers');
    const countSpan = document.getElementById('selectedCountTeachers');

    if (countSpan) countSpan.textContent = checkboxes.length;
    if (btn) btn.disabled = checkboxes.length === 0;
    if (master && totalCheckboxes.length > 0) {
        master.checked = checkboxes.length === totalCheckboxes.length;
    }
}

function confirmSingleDeleteTeacher(id, name) {
    if (confirm('Yakin menghapus akun guru ' + name + '? Semua ujian yang dibuat beserta riwayat pengerjaannya akan ikut terhapus.')) {
        const form = document.getElementById('singleDeleteTeacherForm');
        form.action = '{{ url("/admin/guru") }}/' + id;
        form.submit();
    }
}
</script>
@endpush

@endsection
