@extends('layouts.app')
@section('title', 'Data & Status Peserta: ' . $exam->title)
@section('content')

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
    0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7); }
    70%  { transform: scale(1); box-shadow: 0 0 0 6px rgba(52, 199, 89, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0); }
}
@keyframes livePulseBlue {
    0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 113, 227, 0.7); }
    70%  { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 113, 227, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 113, 227, 0); }
}
</style>

{{-- ── Header Navigasi & Judul ── --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ route('exams.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Kembali ke Kelola Ujian
            </a>
            <span class="badge bg-primary" style="font-size:0.75rem;">{{ $exam->getTargetBadgeLabel() }}</span>
            @if($exam->isActive())
                <span class="badge bg-success d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                    <span class="live-dot"></span> UJIAN AKTIF
                </span>
            @elseif($exam->ends_at < now())
                <span class="badge bg-secondary" style="font-size:0.75rem;">SELESAI</span>
            @else
                <span class="badge bg-info" style="font-size:0.75rem;">TERJADWAL</span>
            @endif
        </div>
        <h2 class="mb-0 fw-bold">{{ $exam->title }}</h2>
        <p class="text-secondary small mb-0">
            Mata Pelajaran: <b>{{ $exam->subject }}</b> · Jadwal: <b>{{ $exam->starts_at->format('d/m/Y H:i') }} - {{ $exam->ends_at->format('H:i') }} WIB</b> · Token: <code>{{ $exam->token }}</code>
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            🖨️ Cetak / PDF
        </button>
        <button class="btn btn-sm btn-primary" onclick="window.location.reload()">
            🔄 Segarkan Data
        </button>
    </div>
</div>

{{-- ── Ringkasan Kartu Statistik Peserta ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="apple-stat-card p-3">
            <div class="apple-stat-icon mb-1" style="background:rgba(0,113,227,0.1); color:var(--apple-blue);">
                👥
            </div>
            <div class="apple-stat-value fs-4">{{ $stats['total_eligible'] }}</div>
            <div class="apple-stat-label">Total Siswa Terdaftar</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="apple-stat-card p-3">
            <div class="apple-stat-icon mb-1" style="background:rgba(0,113,227,0.15); color:var(--apple-blue);">
                💻
            </div>
            <div class="apple-stat-value fs-4 text-primary">{{ $stats['in_progress_count'] }}</div>
            <div class="apple-stat-label">Sedang Mengerjakan</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="apple-stat-card p-3">
            <div class="apple-stat-icon mb-1" style="background:rgba(52,199,89,0.15); color:var(--apple-green);">
                ✅
            </div>
            <div class="apple-stat-value fs-4 text-success">{{ $stats['submitted_count'] }}</div>
            <div class="apple-stat-label">Sudah Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="apple-stat-icon mb-1" style="background:rgba(142,142,147,0.15); color:#8e8e93;">
                ⏳
            </div>
            <div class="apple-stat-value fs-4 text-secondary">{{ $stats['unstarted_count'] }}</div>
            <div class="apple-stat-label">Belum Memulai</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="apple-stat-card p-3">
            <div class="apple-stat-icon mb-1" style="background:rgba(255,59,48,0.15); color:var(--apple-red);">
                ⚠️
            </div>
            <div class="apple-stat-value fs-4 text-danger">{{ $stats['violations_count'] }}</div>
            <div class="apple-stat-label">Total Pelanggaran</div>
        </div>
    </div>
</div>

{{-- ── Tabel Data & Status Peserta ── --}}
<div class="card p-4">
    {{-- Filter Form --}}
    <form method="GET" action="{{ route('exams.participants', $exam) }}" class="row g-3 align-items-center mb-4">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Status Pengerjaan</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Status ({{ $stats['total_eligible'] }})</option>
                <option value="mengerjakan" @selected(request('status') === 'mengerjakan')>🟢 Sedang Mengerjakan ({{ $stats['in_progress_count'] }})</option>
                <option value="selesai" @selected(request('status') === 'selesai')>✓ Selesai ({{ $stats['submitted_count'] }})</option>
                <option value="force_submitted" @selected(request('status') === 'force_submitted')>🔴 Dikumpulkan Paksa</option>
                <option value="belum_mulai" @selected(request('status') === 'belum_mulai')>⏳ Belum Mulai ({{ $stats['unstarted_count'] }})</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Filter Kelas</label>
            <select name="kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c }}" @selected(request('kelas') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Cari Peserta</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nama / NIS / Kelas...">
        </div>

        <div class="col-md-2 d-flex gap-1 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Cari</button>
            <a href="{{ route('exams.participants', $exam) }}" class="btn btn-sm btn-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No.</th>
                    <th>Nama Peserta</th>
                    <th>Kelas</th>
                    <th>Status Pengerjaan</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Durasi Pengerjaan</th>
                    <th class="text-center">Nilai</th>
                    <th class="text-center">Integritas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $p)
                <tr>
                    <td class="text-center text-secondary small fw-bold">{{ $loop->iteration }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $p['name'] }}</div>
                        <div class="text-secondary small">NIS: {{ $p['student_number'] }}</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $p['class_name'] }}</span>
                    </td>
                    <td>
                        @if($p['status'] === 'mengerjakan')
                            <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                                <span class="live-dot live-dot-sm"></span> Mengerjakan
                            </span>
                        @elseif($p['status'] === 'selesai')
                            <span class="badge bg-success">✓ Selesai</span>
                        @elseif($p['status'] === 'force_submitted')
                            <span class="badge bg-danger">🔴 Dikumpulkan Paksa</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border">⏳ Belum Mulai</span>
                        @endif
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $p['started_time'] ?: '—' }}</div>
                        <small class="text-secondary">{{ $p['started_at'] ? explode(' ', $p['started_at'])[0] : '' }}</small>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $p['submitted_time'] ?: '—' }}</div>
                        <small class="text-secondary">{{ $p['submitted_at'] ? explode(' ', $p['submitted_at'])[0] : '' }}</small>
                    </td>
                    <td>
                        <span class="small">{{ $p['duration'] ?: '—' }}</span>
                    </td>
                    <td class="text-center">
                        @if($p['score'] !== null)
                            <span class="fw-bold fs-6 text-dark">{{ $p['score'] }}</span>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p['violations'] > 0)
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fw-semibold"
                                    style="font-size:0.75rem;"
                                    onclick="showViolationLog('{{ addslashes($p['name']) }}', {{ json_encode($p['violation_log']) }})">
                                ⚠️ {{ $p['violations'] }}x Pelanggaran
                            </button>
                        @elseif($p['status'] === 'belum_mulai')
                            <span class="text-secondary small">—</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">✓ Tertib</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        Tidak ada peserta yang cocok dengan kriteria filter saat ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top small text-secondary">
        <div>Menampilkan <b>{{ $participants->count() }}</b> peserta</div>
        <div>Total Siswa Terdaftar: <b>{{ $stats['total_eligible'] }}</b></div>
    </div>
</div>

{{-- ── Modal Rincian Pelanggaran ── --}}
<div class="modal fade" id="violationLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-3 border-0 shadow-lg">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold" id="violationLogTitle">⚠️ Log Pelanggaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1" id="violationLogBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showViolationLog(studentName, log) {
    document.getElementById('violationLogTitle').textContent = '⚠️ Log Pelanggaran — ' + studentName;
    const body = document.getElementById('violationLogBody');
    if (!log || log.length === 0) {
        body.innerHTML = '<p class="text-secondary small mb-0">Tidak ada rincian log catatan pelanggaran.</p>';
    } else {
        let html = '<ol class="mb-0 ps-3 small">';
        log.forEach(item => {
            const time = item.time ? `[${item.time}] ` : '';
            html += `<li class="mb-1"><b>${time}</b>${item.reason || item}</li>`;
        });
        html += '</ol>';
        body.innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('violationLogModal')).show();
}
</script>
@endpush
@endsection