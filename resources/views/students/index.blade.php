@extends('layouts.app')
@section('title', 'Pengelolaan Siswa')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Pengelolaan Siswa</h2>
        <p class="text-secondary small mb-0">Kelola akun, kelas, nomor induk, cetak kartu, dan impor massal siswa</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
            <span>+</span> Tambah Siswa
        </a>
        <a href="{{ route('admin.students.template') }}" class="btn btn-outline-success">
            <span>⬇</span> Template Excel
        </a>
        <a href="{{ route('admin.students.print_cards') }}{{ request()->filled('kelas') ? '?kelas='.urlencode(request('kelas')) : '' }}"
           target="_blank" class="btn btn-secondary">
            <span>🖨️</span> Cetak Kartu Massal
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
                        <th>Nomor Induk</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(session('import_credentials') as $cred)
                        <tr>
                            <td>{{ $cred['nomor_induk'] }}</td>
                            <td>{{ $cred['nama'] }}</td>
                            <td><span class="badge bg-secondary">{{ $cred['kelas'] }}</span></td>
                            <td><code>{{ $cred['username'] }}</code></td>
                            <td><code class="text-danger fw-bold">{{ $cred['password'] }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-secondary mt-2">Salin atau cetak daftar ini dan bagikan secara privat kepada masing-masing siswa.</small>
    </div>
@endif

{{-- Card Impor Excel --}}
<div class="card p-4 mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="apple-stat-icon mb-0" style="width:36px;height:36px;font-size:1rem;background:var(--apple-green-subtle);color:var(--apple-green);">
            📥
        </div>
        <h5 class="fw-bold mb-0">Impor Data Siswa dari Excel</h5>
    </div>
    <p class="text-secondary small mb-3">
        Unggah file Excel dengan kolom <strong>nomor_induk</strong>, <strong>nama</strong>, dan <strong>kelas</strong> (opsional: <strong>tingkat</strong>, <strong>jurusan</strong>). Username dan password dibuat otomatis bila dikosongkan.
    </p>
    <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="glass-well p-3">
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
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Cari Siswa</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                placeholder="Cari berdasarkan nama, username, atau nomor induk...">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Filter Kelas</label>
            <select name="kelas" class="form-select">
                <option value="">Semua Kelas</option>
                @foreach($classes as $class)
                    <option value="{{ $class }}" @selected(request('kelas') === $class)>
                        {{ $class }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Cari</button>
            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Tabel Siswa dengan Form Hapus Massal --}}
<div class="card p-4">
    <form method="POST" action="{{ route('admin.students.bulk_destroy') }}" id="bulkStudentForm"
          onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh siswa yang dipilih? Seluruh data riwayat ujian dan jawaban siswa tersebut juga akan ikut dihapus secara permanen.')">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0">Daftar Siswa</h5>
                <span class="badge bg-secondary">{{ $students->total() }} siswa</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-outline-danger" id="btnBulkDeleteStudents" disabled>
                    🗑️ Hapus Siswa Terpilih (<span id="selectedCountStudents">0</span>)
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px" class="text-center">
                            <input type="checkbox" class="form-check-input" id="checkAllStudents" onclick="toggleAllStudents(this)">
                        </th>
                        <th style="width:50px" class="text-center">No.</th>
                        <th>Nomor Induk</th>
                        <th>Nama Lengkap</th>
                        <th>Username Login</th>
                        <th>Kelas &amp; Jurusan</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="form-check-input student-checkbox" onclick="updateBulkDeleteStudentsState()">
                            </td>
                            <td class="text-center text-secondary fw-bold">{{ $students->firstItem() + $loop->index }}</td>
                            <td><code class="fw-bold">{{ $student->student_number }}</code></td>
                            <td class="fw-bold text-dark">{{ $student->name }}</td>
                            <td><code>{{ $student->username }}</code></td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span class="badge bg-primary">{{ $student->class_name }}</span>
                                    @php
                                        $resolvedLevel = $student->getResolvedGradeLevel();
                                        $resolvedMajor = $student->getResolvedMajor();
                                    @endphp
                                    @if($resolvedLevel || $resolvedMajor)
                                        <small class="text-secondary" style="font-size:0.75rem;">
                                             {{ $resolvedLevel ? 'Tingkat ' . $resolvedLevel : '' }}
                                             {{ $resolvedLevel && $resolvedMajor ? ' • ' : '' }}
                                             {{ $resolvedMajor ? 'Jurusan ' . $resolvedMajor : '' }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div class="table-action-group justify-content-center">
                                    <a href="{{ route('admin.students.print_card', $student) }}"
                                       target="_blank" class="btn-action btn-action-primary" title="Cetak Kartu Peserta">
                                        <span>🖨️</span> Kartu
                                    </a>
                                    <a href="{{ route('admin.students.edit', $student) }}" class="btn-action btn-action-secondary" title="Edit Data Siswa">
                                        <span>✏️</span> Edit
                                    </a>
                                    <button type="button" class="btn-action btn-action-danger"
                                        onclick="confirmSingleDelete('{{ $student->id }}', '{{ addslashes($student->name) }}')" title="Hapus Siswa">
                                        <span>🗑️</span> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <div style="font-size:2.5rem;">👥</div>
                                <div class="mt-2">Belum ada data siswa ditemukan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Form Single Delete tersembunyi agar tidak bertabrakan dengan form bulk --}}
    <form id="singleDeleteForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    @if($students->hasPages())
    <div class="mt-3">
        {{ $students->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleAllStudents(master) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBulkDeleteStudentsState();
}

function updateBulkDeleteStudentsState() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.student-checkbox');
    const master = document.getElementById('checkAllStudents');
    const btn = document.getElementById('btnBulkDeleteStudents');
    const countSpan = document.getElementById('selectedCountStudents');

    if (countSpan) countSpan.textContent = checkboxes.length;
    if (btn) btn.disabled = checkboxes.length === 0;
    if (master && totalCheckboxes.length > 0) {
        master.checked = checkboxes.length === totalCheckboxes.length;
    }
}

function confirmSingleDelete(id, name) {
    if (confirm('Yakin ingin menghapus siswa ' + name + '? Seluruh riwayat ujian dan jawabannya akan ikut dihapus.')) {
        const form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("/admin/siswa") }}/' + id;
        form.submit();
    }
}
</script>
@endpush

@endsection
