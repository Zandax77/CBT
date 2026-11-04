@extends('layouts.app')
@section('title', 'Daftar Ujian')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Daftar Ujian</h2>
        <p class="text-secondary small mb-0">
            Menampilkan ujian untuk kelas:
            <span class="badge bg-primary ms-1">{{ $userClass ?: 'Belum diatur' }}</span>
        </p>
    </div>
    <div class="user-glass-pill">
        <span>👤</span>
        <span>{{ auth()->user()->name }}</span>
    </div>
</div>

@if(!$userClass)
    <div class="alert alert-warning mb-4">
        <span class="fs-4">⚠️</span>
        <div>
            <strong>Data kelas Anda belum diatur.</strong>
            <p class="mb-0 small text-secondary">Hubungi admin atau guru untuk memperbarui data kelas Anda agar dapat mengikuti ujian.</p>
        </div>
    </div>
@endif

<div class="row g-4">
@forelse($exams as $exam)
<div class="col-md-6">
    <div class="card p-4 h-100 d-flex flex-column justify-content-between">
        <div>
            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                <h5 class="mb-0 fw-bold">{{ $exam->title }}</h5>
                @if($exam->class_name)
                    <span class="badge bg-primary" style="white-space:nowrap">
                        {{ $exam->class_name }}
                    </span>
                @else
                    <span class="badge bg-secondary">Semua kelas</span>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-2 text-secondary small mb-3">
                <span class="badge bg-info">📚 {{ $exam->subject }}</span>
                <span class="badge bg-secondary">⏱ {{ $exam->duration_minutes }} menit</span>
                <span class="badge bg-secondary">📝 {{ $exam->questions()->count() }} soal</span>
            </div>

            <div class="glass-well mb-3 py-2 px-3 small text-secondary">
                <span>🕐 Batas Pengumpulan: <strong>{{ $exam->ends_at->format('d/m/Y H:i') }}</strong></span>
            </div>
        </div>

        @php
            $myAttempt = $exam->attempts()->where('user_id', auth()->id())->first();
        @endphp

        <div>
            @if($myAttempt && $myAttempt->submitted_at)
                {{-- Sudah dikerjakan --}}
                <div class="alert alert-success py-2 px-3 mb-0 small justify-content-between">
                    <div>
                        <span>✅</span> <strong>Selesai</strong> · Skor: <strong>{{ $myAttempt->score ?? '—' }}</strong>
                    </div>
                    <a href="{{ route('results.student') }}" class="btn btn-sm btn-outline-success">Lihat Hasil</a>
                </div>
            @elseif($myAttempt && !$myAttempt->submitted_at)
                {{-- Sedang dikerjakan --}}
                <div class="glass-well p-3">
                    <p class="text-warning small mb-2 fw-semibold">⏳ Ujian sedang berlangsung — lanjutkan sebelum waktu habis.</p>
                    <a href="{{ route('attempts.show', $myAttempt) }}" class="btn btn-primary w-100 py-2" onclick="requestFS()">
                        Lanjutkan Pengerjaan Ujian →
                    </a>
                </div>
            @else
                {{-- Belum mulai --}}
                <form method="post" action="{{ route('attempts.start', $exam) }}" onsubmit="requestFS()">
                    @csrf
                    <div class="input-group">
                        <input class="form-control" name="token"
                               placeholder="Masukkan token dari guru" required autocomplete="off"
                               style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;">
                        <button class="btn btn-primary px-3" style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">
                            Mulai Ujian
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@empty
<div class="col-12">
    <div class="card p-5 text-center text-muted">
        <div style="font-size:3.5rem">📋</div>
        <h5 class="fw-bold mt-3 text-dark">Tidak Ada Ujian Aktif</h5>
        <p class="mb-0 text-secondary">
            @if($userClass)
                Saat ini belum ada ujian yang dibuka untuk kelas <strong>{{ $userClass }}</strong>.
            @else
                Tidak ada ujian yang tersedia. Pastikan profil kelas Anda sudah diatur.
            @endif
        </p>
    </div>
</div>
@endforelse
</div>

@push('scripts')
<script>
function requestFS() {
    const el = document.documentElement;
    if (el.requestFullscreen) {
        el.requestFullscreen().catch(() => {});
    } else if (el.webkitRequestFullscreen) {
        el.webkitRequestFullscreen().catch(() => {});
    }
}
</script>
@endpush

@endsection
