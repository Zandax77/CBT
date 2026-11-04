@extends('layouts.app')
@section('title', 'Dashboard — ' . ($school->school_name ?: 'CBT Sekolah'))
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1 fw-bold">
            @if($isKepsek ?? false)
                Dashboard Eksekutif Kepala Sekolah
            @else
                Dashboard
            @endif
        </h2>
        <p class="text-secondary mb-0">Selamat datang di Sistem Ujian Berbasis Komputer <strong>{{ $school->school_name }}</strong></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ ($isKepsek ?? false) ? 'user-role-kepsek' : 'bg-primary' }} px-3 py-2 fs-6">
            {{ auth()->user()->name }} · {{ auth()->user()->role === 'kepala_sekolah' ? 'KEPALA SEKOLAH' : strtoupper(auth()->user()->role) }}
        </span>
    </div>
</div>

@if($student)
    {{-- ── 1. Tampilan Siswa ── --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(0, 113, 227, 0.12); color: var(--apple-blue);">
                    📝
                </div>
                <div class="apple-stat-value">{{ $exams->count() }}</div>
                <div class="apple-stat-label">Ujian Tersedia Sekarang</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(52, 199, 89, 0.12); color: var(--apple-green);">
                    ✅
                </div>
                <div class="apple-stat-value">{{ $attemptCount }}</div>
                <div class="apple-stat-label">Riwayat Pengerjaan Ujian</div>
            </div>
        </div>
        <div class="col-md-12 col-lg-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(175, 82, 222, 0.12); color: var(--apple-purple);">
                    🏫
                </div>
                <div class="apple-stat-value" style="font-size: 1.4rem; padding-top: 6px;">{{ $student->class_name ?? 'Kelas Aktif' }}</div>
                <div class="apple-stat-label">No. Induk: {{ $student->student_number ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold">Aksi Cepat</h5>
        <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-primary px-4 py-2" href="{{ route('attempts.index') }}">
                <span>✍️</span> Buka Daftar Ujian
            </a>
            <a class="btn btn-secondary px-4 py-2" href="{{ route('results.student') }}">
                <span>🏆</span> Lihat Hasil Ujian
            </a>
        </div>
    </div>

@elseif($isKepsek ?? false)
    {{-- ── 2. Tampilan Kepala Sekolah (Executive Overview & Monitoring) ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="apple-stat-card p-3">
                <div class="apple-stat-icon mb-2" style="background: var(--apple-green-subtle); color: var(--apple-green); width:40px;height:40px;font-size:1.1rem;">
                    🟢
                </div>
                <div class="apple-stat-value fs-2">{{ $activeExamsCount }}</div>
                <div class="apple-stat-label">Ujian Aktif Saat Ini</div>
                <div class="text-secondary small mt-1" style="font-size:0.75rem;">Dari total {{ $examCount }} ujian dibuat</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="apple-stat-card p-3">
                <div class="apple-stat-icon mb-2" style="background: var(--apple-blue-subtle); color: var(--apple-blue); width:40px;height:40px;font-size:1.1rem;">
                    💻
                </div>
                <div class="apple-stat-value fs-2">{{ $inProgressCount }}</div>
                <div class="apple-stat-label">Siswa Sedang Ujian</div>
                <div class="text-secondary small mt-1" style="font-size:0.75rem;">Sesi pengerjaan berlangsung</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="apple-stat-card p-3">
                <div class="apple-stat-icon mb-2" style="background: var(--apple-purple-subtle); color: var(--apple-purple); width:40px;height:40px;font-size:1.1rem;">
                    🏁
                </div>
                <div class="apple-stat-value fs-2">{{ $completedCount }}</div>
                <div class="apple-stat-label">Sesi Ujian Selesai</div>
                <div class="text-secondary small mt-1" style="font-size:0.75rem;">Terkumpul di sistem</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="apple-stat-card p-3">
                <div class="apple-stat-icon mb-2" style="background: rgba(255, 149, 0, 0.12); color: var(--apple-orange); width:40px;height:40px;font-size:1.1rem;">
                    📈
                </div>
                <div class="apple-stat-value fs-2 font-monospace">{{ $averageScore }}</div>
                <div class="apple-stat-label">Rata-rata Nilai CBT</div>
                <div class="text-secondary small mt-1" style="font-size:0.75rem;">Pelanggaran terdeteksi: <b>{{ $totalViolations }}</b></div>
            </div>
        </div>
    </div>

    {{-- Tombol Tindakan Eksekutif --}}
    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a class="btn btn-primary" href="{{ route('monitoring.index') }}">
                <span>📡</span> Buka Live Monitoring Ujian
            </a>
            <a class="btn btn-secondary" href="{{ route('results.admin') }}">
                <span>📋</span> Hasil &amp; Rincian Ujian Siswa
            </a>
            <a class="btn btn-outline-success" href="{{ route('admin.grades.index') }}">
                <span>📈</span> Rekap &amp; Ekspor Nilai
            </a>
        </div>
    </div>

    {{-- Dua Panel Informasi: Ujian Terbaru & Hasil Terkini --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Status Pelaksanaan Ujian</h5>
                    <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-secondary">Pantau Live →</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama Ujian</th>
                                <th>Target</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exams as $exam)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $exam->title }}</div>
                                        <div class="text-secondary small">{{ $exam->subject }}</div>
                                    </td>
                                    <td><span class="badge bg-primary" style="font-size:0.7rem;">{{ $exam->getTargetBadgeLabel() }}</span></td>
                                    <td>
                                        <small class="text-secondary">
                                            {{ $exam->starts_at->format('d/m/Y') }}<br>
                                            {{ $exam->starts_at->format('H:i') }} - {{ $exam->ends_at->format('H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($exam->isActive())
                                            <span class="badge bg-success">Aktif</span>
                                        @elseif($exam->ends_at < now())
                                            <span class="badge bg-secondary">Selesai</span>
                                        @else
                                            <span class="badge bg-info">Terjadwal</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada ujian dibuat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Hasil Pengumpulan Terbaru</h5>
                    <a href="{{ route('results.admin') }}" class="btn btn-sm btn-secondary">Semua Hasil →</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Ujian</th>
                                <th class="text-center">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAttempts as $ra)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark small">{{ $ra->user->name }}</div>
                                        <span class="badge bg-secondary" style="font-size:0.68rem;">{{ $ra->user->class_name }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-truncate" style="max-width:130px;" title="{{ $ra->exam->title }}">
                                            {{ $ra->exam->title }}
                                        </div>
                                        <small class="text-secondary">{{ $ra->submitted_at?->format('H:i') }} WIB</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge fs-6 px-2 py-1 bg-primary font-monospace fw-bold">
                                            {{ number_format($ra->score, 1) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada nilai yang terkumpul.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@else
    {{-- ── 3. Tampilan Admin / Guru ── --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(0, 113, 227, 0.12); color: var(--apple-blue);">
                    📑
                </div>
                <div class="apple-stat-value">{{ $examCount }}</div>
                <div class="apple-stat-label">Total Ujian Dibuat</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(175, 82, 222, 0.12); color: var(--apple-purple);">
                    👥
                </div>
                <div class="apple-stat-value">{{ $userCount }}</div>
                <div class="apple-stat-label">Total Akun Terdaftar</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="apple-stat-card">
                <div class="apple-stat-icon" style="background: rgba(52, 199, 89, 0.12); color: var(--apple-green);">
                    🎯
                </div>
                <div class="apple-stat-value">{{ $attemptCount }}</div>
                <div class="apple-stat-label">Sesi Ujian Selesai</div>
            </div>
        </div>
    </div>

    {{-- Tombol Utama --}}
    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a class="btn btn-primary" href="{{ route('exams.index') }}">
                <span>📝</span> Kelola Ujian
            </a>
            @if(auth()->user()->role === 'admin')
                <a class="btn btn-secondary" href="{{ route('monitoring.index') }}">
                    <span>📡</span> Monitoring Ujian
                </a>
            @endif
            <a class="btn btn-secondary" href="{{ route('questions.template') }}">
                <span>📥</span> Unduh Template Soal Excel
            </a>
            @if(auth()->user()->role === 'admin')
                <a class="btn btn-secondary" href="{{ route('results.admin') }}">
                    <span>📋</span> Rilis Nilai &amp; Hasil
                </a>
            @endif
        </div>
    </div>

    {{-- Menu Admin Quick Launch --}}
    @if(auth()->user()->role === 'admin')
    <div class="mb-4">
        <h5 class="mb-3 fw-bold">Menu Manajemen Admin</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('admin.students.index') }}" class="text-decoration-none">
                    <div class="card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="apple-stat-icon mb-0" style="background: var(--apple-blue-subtle); color: var(--apple-blue);">
                                👤
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Kelola Siswa</div>
                                <div class="text-secondary small">Tambah, edit, impor data siswa</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.teachers.index') }}" class="text-decoration-none">
                    <div class="card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="apple-stat-icon mb-0" style="background: var(--apple-purple-subtle); color: var(--apple-purple);">
                                👨‍🏫
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Kelola Guru</div>
                                <div class="text-secondary small">Tambah, edit, impor data guru</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.grades.index') }}" class="text-decoration-none">
                    <div class="card p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="apple-stat-icon mb-0" style="background: var(--apple-green-subtle); color: var(--apple-green);">
                                📊
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Rekap Nilai</div>
                                <div class="text-secondary small">Lihat &amp; ekspor rekap nilai ujian</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Daftar Ujian Terbaru --}}
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Ujian Terbaru</h5>
            <a href="{{ route('exams.index') }}" class="btn btn-sm btn-secondary">Semua Ujian →</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Ujian</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $exam)
                        <tr>
                            <td class="fw-semibold">{{ $exam->title }}</td>
                            <td><span class="badge bg-secondary">{{ $exam->subject }}</span></td>
                            <td>{{ $exam->class_name ?: 'Semua Kelas' }}</td>
                            <td>
                                @if($exam->is_published)
                                    <span class="badge bg-success">Terbit</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada ujian dibuat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
