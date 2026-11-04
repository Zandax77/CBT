@extends('layouts.app')
@section('title', 'Monitoring Kegiatan Ujian — ' . ($school->school_name ?: 'CBT Sekolah'))
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-0 fw-bold">Monitoring Kegiatan Ujian</h2>
            <span class="badge bg-success d-inline-flex align-items-center gap-1 px-3 py-1" style="font-size:0.75rem;">
                <span class="live-dot"></span> LIVE MONITOR
            </span>
        </div>
        <p class="text-secondary small mb-0">Pantau jalannya ujian, kehadiran peserta, status pengerjaan, dan integritas pelaksanaan CBT secara langsung</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2 user-glass-pill py-1 px-3" style="cursor: pointer;">
            <input class="form-check-input mt-0" type="checkbox" id="autoRefreshToggle" checked>
            <label class="form-check-label small fw-semibold text-secondary" for="autoRefreshToggle" style="cursor: pointer;">
                Auto-Refresh (<span id="refreshTimer">15</span>s)
            </label>
        </div>
        <button class="btn btn-sm btn-secondary" onclick="window.location.reload()">
            <span>🔄</span> Segarkan
        </button>
    </div>
</div>

{{-- ── 1. KPI / Ringkasan Global Aktivitas ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="apple-stat-icon mb-0" style="width:38px;height:38px;font-size:1.1rem;background:var(--apple-green-subtle);color:var(--apple-green);">
                    🟢
                </div>
                <span class="badge bg-success" style="font-size:0.68rem;">Hari Ini</span>
            </div>
            <div class="apple-stat-value fs-3">{{ $globalStats['active_exams'] }}</div>
            <div class="apple-stat-label">Ujian Sedang Berlangsung</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="apple-stat-icon mb-0" style="width:38px;height:38px;font-size:1.1rem;background:var(--apple-blue-subtle);color:var(--apple-blue);">
                    💻
                </div>
                <span class="badge bg-primary" style="font-size:0.68rem;">Online</span>
            </div>
            <div class="apple-stat-value fs-3">{{ $globalStats['students_online'] }}</div>
            <div class="apple-stat-label">Siswa Sedang Mengerjakan</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="apple-stat-icon mb-0" style="width:38px;height:38px;font-size:1.1rem;background:var(--apple-purple-subtle);color:var(--apple-purple);">
                    🏁
                </div>
                <span class="badge bg-secondary" style="font-size:0.68rem;">Selesai</span>
            </div>
            <div class="apple-stat-value fs-3">{{ $globalStats['total_submitted'] }}</div>
            <div class="apple-stat-label">Total Sesi Terkumpul</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="apple-stat-icon mb-0" style="width:38px;height:38px;font-size:1.1rem;background:var(--apple-red-subtle);color:var(--apple-red);">
                    ⚠️
                </div>
                <span class="badge bg-danger" style="font-size:0.68rem;">Integritas</span>
            </div>
            <div class="apple-stat-value fs-3">{{ $globalStats['total_violations'] }}</div>
            <div class="apple-stat-label">Total Pelanggaran Terdeteksi</div>
        </div>
    </div>
</div>

{{-- ── 2. Pemilihan Ujian yang Dipantau ── --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('monitoring.index') }}" id="examSelectForm" class="row g-3 align-items-center">
        <div class="col-md-7">
            <label class="form-label small fw-bold mb-1" for="select_exam">Pilih Ujian untuk Dipantau:</label>
            <select name="exam_id" id="select_exam" class="form-select" onchange="document.getElementById('examSelectForm').submit()">
                @forelse($exams as $ex)
                    <option value="{{ $ex->id }}" @selected($selectedExam && $selectedExam->id == $ex->id)>
                        {{ $ex->isActive() ? '🟢 [AKTIF] ' : ($ex->ends_at < now() ? '⚪ [SELESAI] ' : '🔵 [TERJADWAL] ') }}
                        {{ $ex->title }} &mdash; {{ $ex->subject }} ({{ $ex->getTargetBadgeLabel() }})
                    </option>
                @empty
                    <option value="">Belum ada ujian yang dipublikasikan</option>
                @endforelse
            </select>
        </div>
        <div class="col-md-5 d-flex gap-2 justify-content-md-end mt-3 mt-md-auto">
            @if($selectedExam)
                <a href="{{ route('admin.grades.index', ['exam_id' => $selectedExam->id]) }}" class="btn btn-sm btn-outline-success">
                    <span>📈</span> Rekap Nilai Ujian
                </a>
                <a href="{{ route('results.admin', ['exam_id' => $selectedExam->id]) }}" class="btn btn-sm btn-outline-primary">
                    <span>📋</span> Hasil &amp; Rilis
                </a>
            @endif
        </div>
    </form>

    @if($selectedExam)
    <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-color: rgba(0,0,0,0.06) !important;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <span class="text-secondary small">Jadwal:</span>
                <span class="fw-semibold small ms-1">{{ $selectedExam->starts_at->format('d/m/Y H:i') }} - {{ $selectedExam->ends_at->format('H:i') }} WIB</span>
            </div>
            <div>
                <span class="text-secondary small">Durasi:</span>
                <span class="fw-semibold small ms-1">{{ $selectedExam->duration_minutes }} Menit</span>
            </div>
            <div>
                <span class="text-secondary small">Token:</span>
                <code class="px-2 py-0 rounded ms-1" style="background:rgba(0,113,227,0.08); color:var(--apple-blue); font-weight:700;">{{ $selectedExam->token }}</code>
            </div>
        </div>
        <div>
            @if($selectedExam->isActive())
                <span class="badge bg-success">🟢 Ujian Sedang Berlangsung</span>
            @elseif($selectedExam->ends_at < now())
                <span class="badge bg-secondary">⚪ Ujian Telah Selesai</span>
            @else
                <span class="badge bg-info">🔵 Ujian Belum Dimulai</span>
            @endif
        </div>
    </div>
    @endif
</div>

@if($selectedExam)
{{-- ── 3. Metrik Statistik Ujian Terpilih ── --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card p-3 h-100">
            <h6 class="fw-bold mb-2">Statistik Partisipasi &amp; Kehadiran Peserta</h6>
            <div class="row g-2 text-center my-auto">
                <div class="col-3">
                    <div class="glass-well p-2">
                        <div class="fs-4 fw-bold text-dark">{{ $stats['total_eligible'] }}</div>
                        <div class="text-secondary" style="font-size:0.72rem;">Peserta Terdaftar</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="glass-well p-2" style="background:rgba(0,113,227,0.06);">
                        <div class="fs-4 fw-bold text-primary">{{ $stats['in_progress_count'] }}</div>
                        <div class="text-secondary" style="font-size:0.72rem;">Sedang Mengerjakan</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="glass-well p-2" style="background:rgba(52,199,89,0.06);">
                        <div class="fs-4 fw-bold text-success">{{ $stats['submitted_count'] }}</div>
                        <div class="text-secondary" style="font-size:0.72rem;">Sudah Selesai</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="glass-well p-2">
                        <div class="fs-4 fw-bold text-muted">{{ $stats['unstarted_count'] }}</div>
                        <div class="text-secondary" style="font-size:0.72rem;">Belum Mulai</div>
                    </div>
                </div>
            </div>

            @php
                $participationPercent = $stats['total_eligible'] > 0 ? round(($stats['started_count'] / $stats['total_eligible']) * 100) : 0;
            @endphp
            <div class="mt-3">
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <span>Tingkat Kehadiran: <b>{{ $participationPercent }}%</b></span>
                    <span>{{ $stats['started_count'] }} dari {{ $stats['total_eligible'] }} Siswa Telah Masuk</span>
                </div>
                <div class="progress" style="height: 7px; background: rgba(0,0,0,0.06); border-radius: 9999px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $participationPercent }}%; border-radius: 9999px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3 h-100">
            <h6 class="fw-bold mb-2">Evaluasi Nilai &amp; Integritas</h6>
            <div class="d-flex flex-column justify-content-between h-100">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                    <span class="small text-secondary">Rata-rata Nilai:</span>
                    <span class="fw-bold fs-5 text-dark font-monospace">{{ number_format($stats['avg_score'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                    <span class="small text-secondary">Nilai Tertinggi / Terendah:</span>
                    <span class="small fw-bold"><span class="text-success">{{ $stats['max_score'] }}</span> / <span class="text-danger">{{ $stats['min_score'] }}</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2" style="border-color: rgba(0,0,0,0.05) !important;">
                    <span class="small text-secondary">Pelanggaran / Submit Paksa:</span>
                    <span class="badge {{ $stats['violations_count'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
                        {{ $stats['violations_count'] }} / {{ $stats['force_submitted_count'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 4. Tabel Pemantauan Peserta Real-Time ── --}}
<div class="card p-4">
    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('monitoring.index') }}" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="exam_id" value="{{ $selectedExam->id }}">
        
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Filter Status Pengerjaan</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Status Peserta</option>
                <option value="in_progress" @selected(request('status') === 'in_progress')>🟢 Sedang Mengerjakan</option>
                <option value="submitted" @selected(request('status') === 'submitted')>🔵 Selesai Mengumpulkan</option>
                <option value="violations" @selected(request('status') === 'violations')>⚠️ Ada Pelanggaran</option>
                <option value="force_submitted" @selected(request('status') === 'force_submitted')>🔴 Dihentikan Paksa</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold">Filter Kelas</label>
            <select name="kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c }}" @selected(request('kelas') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-semibold">Cari Siswa</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nama / Nomor Induk...">
        </div>

        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Cari</button>
            <a href="{{ route('monitoring.index', ['exam_id' => $selectedExam->id]) }}" class="btn btn-sm btn-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No.</th>
                    <th>Nama Peserta</th>
                    <th>Kelas</th>
                    <th>Waktu Mulai</th>
                    <th>Status Pengerjaan</th>
                    <th>Integritas &amp; Pelanggaran</th>
                    <th class="text-center">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $a)
                <tr>
                    <td class="text-center text-secondary small fw-bold">{{ $attempts->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $a->user->name }}</div>
                        <div class="text-secondary small">NIS: {{ $a->user->student_number ?? '—' }}</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $a->user->class_name ?? '—' }}</span>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $a->started_at ? $a->started_at->format('H:i:s') : '—' }}</div>
                        <small class="text-secondary">{{ $a->started_at ? $a->started_at->format('d/m/Y') : '' }}</small>
                    </td>
                    <td>
                        @if($a->submitted_at)
                            @if($a->force_submitted)
                                <span class="badge bg-danger">🔴 Dikumpulkan Paksa</span>
                            @else
                                <span class="badge bg-success">✓ Selesai ({{ $a->submitted_at->format('H:i') }})</span>
                            @endif
                        @else
                            <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                                <span class="live-dot live-dot-sm"></span> Mengerjakan
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($a->violations > 0)
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-danger">⚠️ {{ $a->violations }}x Pelanggaran</span>
                                @if(!empty($a->violation_log))
                                    <button type="button" class="btn-action btn-action-danger"
                                            onclick="showViolationLog('{{ addslashes($a->user->name) }}', {{ json_encode($a->violation_log) }})"
                                            title="Lihat Rincian Pelanggaran">
                                        Log
                                    </button>
                                @endif
                            </div>
                        @else
                            <span class="badge bg-success">✓ Tertib (0)</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($a->submitted_at)
                            <span class="badge fs-6 px-3 py-1 bg-primary font-monospace fw-bold">
                                {{ number_format($a->score, 2) }}
                            </span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <div style="font-size:2.5rem;">📡</div>
                        <div class="mt-2">Belum ada aktivitas peserta yang tercatat untuk kriteria ini.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attempts->hasPages())
    <div class="mt-3">
        {{ $attempts->links() }}
    </div>
    @endif
</div>
@endif

{{-- Modal Detail Log Pelanggaran --}}
<div class="modal fade" id="violationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-3" style="border-radius:20px;">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">⚠️ Rincian Pelanggaran Ujian</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="small text-secondary mb-3">Peserta: <strong id="violatorName" class="text-dark"></strong></p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px;">No.</th>
                                <th>Waktu Kejadian</th>
                                <th>Jenis Pelanggaran</th>
                            </tr>
                        </thead>
                        <tbody id="violationListBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-Refresh Functionality
let refreshInterval = 15;
let timerSeconds = refreshInterval;
let timerElement = document.getElementById('refreshTimer');
let toggleElement = document.getElementById('autoRefreshToggle');

setInterval(function() {
    if (toggleElement && toggleElement.checked) {
        timerSeconds--;
        if (timerElement) timerElement.textContent = timerSeconds;
        if (timerSeconds <= 0) {
            window.location.reload();
        }
    }
}, 1000);

if (toggleElement) {
    toggleElement.addEventListener('change', function() {
        if (this.checked) {
            timerSeconds = refreshInterval;
            if (timerElement) timerElement.textContent = timerSeconds;
        }
    });
}

// Show Violation Log Modal
function showViolationLog(studentName, logs) {
    document.getElementById('violatorName').textContent = studentName;
    const tbody = document.getElementById('violationListBody');
    tbody.innerHTML = '';

    if (!logs || logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada rincian.</td></tr>';
    } else {
        logs.forEach(function(item, idx) {
            const tr = document.createElement('tr');
            let dateStr = '—';
            if (item.at) {
                const d = new Date(item.at);
                dateStr = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            tr.innerHTML = `
                <td class="text-center fw-bold">${idx + 1}</td>
                <td class="small font-monospace">${dateStr}</td>
                <td class="small text-danger fw-semibold">${item.reason || 'Pelanggaran jendela ujian'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('violationModal'));
    modal.show();
}
</script>
@endpush

<style>
.live-dot {
    width: 8px;
    height: 8px;
    background-color: #34c759;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 rgba(52, 199, 89, 0.4);
    animation: livePulse 1.8s infinite;
}
.live-dot-sm {
    width: 6px;
    height: 6px;
    background-color: #0071e3;
    animation: livePulseBlue 1.8s infinite;
}
@keyframes livePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(52, 199, 89, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0); }
}
@keyframes livePulseBlue {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 113, 227, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(0, 113, 227, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 113, 227, 0); }
}
</style>

@endsection
