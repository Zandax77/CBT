@extends('layouts.app')
@section('title', 'Hasil Ujian & Rilis Nilai')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Hasil Ujian &amp; Rilis Nilai</h2>
        <p class="text-secondary small mb-0">Kelola dan rilis perolehan nilai peserta secara individual maupun massal</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary px-3 py-2 fs-6">
            {{ $attempts->total() }} Sesi Ujian Selesai
        </span>
    </div>
</div>

{{-- Filter Card --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('results.admin') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="filter_exam">Filter Ujian</label>
            <select name="exam_id" id="filter_exam" class="form-select">
                <option value="">Semua Ujian</option>
                @foreach($exams as $ex)
                    <option value="{{ $ex->id }}" @selected((string)request('exam_id') === (string)$ex->id)>
                        {{ $ex->title }} ({{ $ex->subject }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="filter_status">Status Rilis</label>
            <select name="status" id="filter_status" class="form-select">
                <option value="">Semua Status</option>
                <option value="unreleased" @selected(request('status') === 'unreleased')>🔒 Belum Dirilis</option>
                <option value="released" @selected(request('status') === 'released')>✓ Sudah Dirilis</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="filter_q">Cari Siswa</label>
            <input type="text" name="q" id="filter_q" class="form-control" value="{{ request('q') }}" placeholder="Nama / No. Induk / Kelas...">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Filter</button>
            <a href="{{ route('results.admin') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Panel Aksi Rilis Massal & Tabel --}}
<div class="card p-4">
    <form method="POST" action="{{ route('results.bulk_release') }}" id="bulkReleaseForm">
        @csrf
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0">Daftar Hasil Ujian Siswa</h5>
            </div>
            
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(auth()->user()->role === 'admin')
                    {{-- Tombol Rilis Terpilih --}}
                    <button type="submit" class="btn btn-sm btn-primary" id="btnReleaseSelected" disabled>
                        <span>📢</span> Rilis Nilai Terpilih (<span id="selectedCount">0</span>)
                    </button>

                    {{-- Tombol Rilis Semua Ujian Terpilih --}}
                    @if(request()->filled('exam_id'))
                        <button type="button" class="btn btn-sm btn-success" onclick="releaseByExam({{ request('exam_id') }})">
                            <span>📢</span> Rilis Semua Nilai Ujian Ini
                        </button>
                    @endif

                    {{-- Tombol Rilis Seluruh Nilai Selesai di Sistem --}}
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="releaseAllOverall()">
                        <span>✨</span> Rilis Seluruh Nilai Selesai
                    </button>
                @endif
                <a href="{{ route('admin.grades.index', request()->only(['exam_id', 'kelas'])) }}" class="btn btn-sm btn-secondary">
                    <span>📈</span> Buka Rekap Nilai
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="checkAllAttempts" onclick="toggleAllAttempts(this)">
                        </th>
                        <th>Peserta Ujian</th>
                        <th>Kelas</th>
                        <th>Nama Ujian</th>
                        <th>Waktu Selesai</th>
                        <th class="text-center">Nilai Akhir</th>
                        <th>Status Publikasi</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $a)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="attempt_ids[]" value="{{ $a->id }}" 
                                   class="form-check-input attempt-checkbox" 
                                   @if($a->is_released) data-released="1" @endif
                                   onclick="updateReleaseState()">
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $a->user->name }}</div>
                            <div class="text-secondary small">No. Induk: {{ $a->user->student_number ?? '—' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $a->user->class_name ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $a->exam->title }}</div>
                            <div class="text-secondary small">{{ $a->exam->subject }}</div>
                        </td>
                        <td>
                            <small class="text-secondary">{{ $a->submitted_at ? $a->submitted_at->format('d/m/Y H:i') : '—' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge fs-6 px-3 py-1 bg-primary" style="font-family:'JetBrains Mono', monospace; font-weight:700;">
                                {{ number_format($a->score, 2) }}
                            </span>
                        </td>
                        <td>
                            @if($a->is_released && $a->exam->results_released)
                                <span class="badge bg-success">
                                    <span>✓</span> Dirilis ke Siswa
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <span>🔒</span> Belum Dirilis
                                </span>
                            @endif
                        </td>
                        <td class="text-center" style="white-space: nowrap;">
                            <div class="table-action-group justify-content-center">
                                @if(auth()->user()->role === 'admin')
                                    @if(!$a->is_released)
                                        <button type="button" class="btn-action btn-action-primary" onclick="releaseSingle({{ $a->id }}, '{{ addslashes($a->user->name) }}')">
                                            <span>📢</span> Rilis Nilai
                                        </button>
                                    @else
                                        <span class="btn-action btn-action-secondary" style="opacity: 0.75; cursor: default; pointer-events: none;">
                                            <span>✓</span> Telah Rilis
                                        </span>
                                    @endif
                                @else
                                    <span class="btn-action btn-action-secondary" style="opacity: 0.85; cursor: default; pointer-events: none;">
                                        <span>👁️</span> Selesai
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <div style="font-size:2.5rem;">📋</div>
                            <div class="mt-2">Belum ada sesi ujian yang selesai sesuai kriteria filter.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($attempts->hasPages())
    <div class="mt-3">
        {{ $attempts->links() }}
    </div>
    @endif
</div>

{{-- Hidden Form for Single Release & Exam/All Release --}}
<form id="singleReleaseForm" method="POST" action="" style="display:none;">
    @csrf
</form>

<form id="examReleaseForm" method="POST" action="{{ route('results.bulk_release') }}" style="display:none;">
    @csrf
    <input type="hidden" name="exam_id" id="hiddenExamId" value="">
    <input type="hidden" name="release_all" id="hiddenReleaseAll" value="">
</form>

<div class="glass-well p-3 mt-3 d-flex align-items-center gap-2">
    <span>💡</span>
    <small class="text-secondary">
        Nilai baru dapat dilihat oleh masing-masing siswa setelah admin merilis nilai dan mengaktifkan publikasi ujian terkait. Anda dapat merilis nilai per peserta atau secara massal sekaligus.
    </small>
</div>

@push('scripts')
<script>
function toggleAllAttempts(master) {
    document.querySelectorAll('.attempt-checkbox').forEach(cb => cb.checked = master.checked);
    updateReleaseState();
}

function updateReleaseState() {
    const checkedBoxes = document.querySelectorAll('.attempt-checkbox:checked');
    const btn = document.getElementById('btnReleaseSelected');
    const counter = document.getElementById('selectedCount');
    if (counter) counter.textContent = checkedBoxes.length;
    if (btn) btn.disabled = checkedBoxes.length === 0;
}

function releaseSingle(attemptId, studentName) {
    if (confirm(`Rilis nilai untuk siswa "${studentName}" sekarang? Nilai akan langsung dapat dilihat di akun siswa.`)) {
        const form = document.getElementById('singleReleaseForm');
        form.action = '{{ url("/admin/hasil") }}/' + attemptId + '/rilis';
        form.submit();
    }
}

function releaseByExam(examId) {
    if (confirm('Yakin ingin merilis SEMUA nilai peserta untuk ujian ini secara massal? Nilai akan langsung muncul di akun seluruh siswa terkait.')) {
        document.getElementById('hiddenExamId').value = examId;
        document.getElementById('hiddenReleaseAll').value = '';
        document.getElementById('examReleaseForm').submit();
    }
}

function releaseAllOverall() {
    if (confirm('Yakin ingin merilis SELURUH nilai ujian yang telah selesai di sistem? Seluruh siswa akan dapat melihat nilai mereka.')) {
        document.getElementById('hiddenExamId').value = '';
        document.getElementById('hiddenReleaseAll').value = '1';
        document.getElementById('examReleaseForm').submit();
    }
}
</script>
@endpush
@endsection
