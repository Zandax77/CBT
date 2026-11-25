@extends('layouts.app')
@section('title', 'Kelola Ujian')
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
    display: inline-block;
    border-radius: 50%;
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
.modal-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(0, 113, 227, 0.1);
    color: var(--apple-blue);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.btn-white-glass {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    color: #1d1d1f !important;
    border-radius: 10px !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    padding: 6px 14px !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
}
.btn-white-glass:hover {
    background: #ffffff !important;
    border-color: var(--apple-blue) !important;
    color: var(--apple-blue) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}
.btn-primary-glass {
    background: var(--apple-blue) !important;
    border: 1px solid var(--apple-blue) !important;
    color: #ffffff !important;
    border-radius: 10px !important;
    font-size: 0.82rem !important;
    font-weight: 600 !important;
    padding: 6px 14px !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
}
.btn-primary-glass:hover {
    background: #0077ed !important;
    border-color: #0077ed !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.35) !important;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 0.8s linear infinite;
}
@media (min-width: 992px) {
    .border-end-lg {
        border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
    }
}
.apple-token-card-wide {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1.5px solid rgba(52, 199, 89, 0.35);
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 4px 16px rgba(52, 199, 89, 0.10), inset 0 1px 1px rgba(255, 255, 255, 0.8);
}
.clickable-cell {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.clickable-cell:hover {
    background-color: rgba(0, 113, 227, 0.04) !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Kelola Ujian</h2>
        <p class="text-secondary small mb-0">Atur jadwal, target kelompok peserta (tingkat/jurusan/kelas/umum), token, dan bank soal</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="{{ route('exams.create') }}">
            <span>✨</span> Buat Ujian Baru
        </a>
    </div>
</div>

{{-- ── Ujian Aktif dengan Token ── --}}
@php $activeExams = $exams->filter(fn($e) => $e->isActive()); @endphp
@if($activeExams->count())
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success px-2 py-1 fs-6">🟢</span>
            <h5 class="fw-bold mb-0">Ujian Sedang Berlangsung</h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">{{ $activeExams->count() }} Aktif</span>
        </div>
        <div class="text-secondary small d-none d-sm-block">
            Monitoring &amp; Token Live Real-time
        </div>
    </div>

    @if($activeExams->count() === 1)
        {{-- Layout 1 Ujian: Full-width Proportional Executive Card (Tanpa Ruang Kosong) --}}
        @php $e = $activeExams->first(); @endphp
        <div class="card p-4 shadow-sm border-0 mb-2" style="border-left: 5px solid #34c759 !important; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border-radius: 20px;">
            <div class="row g-4 align-items-center">
                {{-- Kolom 1: Informasi Ujian & Target --}}
                <div class="col-lg-4 border-end-lg pe-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center gap-1 fw-bold" style="font-size:0.75rem;">
                            <span class="live-dot"></span> Ujian Berlangsung
                        </span>
                        @if($e->randomize_by_type)
                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.72rem;">🔀 Acak per Jenis</span>
                        @endif
                    </div>
                    <h4 class="fw-bold text-dark mb-1">{{ $e->title }}</h4>
                    <div class="text-secondary fw-medium mb-3 fs-6">{{ $e->subject }}</div>

                    <div class="mb-3">
                        <span class="badge bg-primary px-3 py-2" style="font-size: 0.8rem; border-radius: 8px;">
                            🎯 {{ $e->getTargetBadgeLabel() }}
                        </span>
                    </div>

                    <div class="p-2.5 rounded-3 bg-light bg-opacity-75 border text-secondary small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>📅 Jadwal:</span>
                            <span class="fw-semibold text-dark">{{ $e->starts_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>⏱️ Waktu:</span>
                            <span class="fw-semibold text-dark">{{ $e->starts_at->format('H:i') }} &ndash; {{ $e->ends_at->format('H:i') }} WIB</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>⏳ Durasi / Selesai:</span>
                            <span class="fw-semibold text-dark">{{ $e->duration_minutes }}m (Selesai {{ $e->ends_at->format('H:i') }} WIB)</span>
                        </div>
                    </div>
                </div>

                {{-- Kolom 2: Token Akses Siswa & Rotasi --}}
                <div class="col-lg-4 border-end-lg px-lg-4">
                    <div class="apple-token-card-wide text-center">
                        <div class="text-secondary small fw-bold mb-1" style="letter-spacing: 0.1em; font-size: 0.72rem;">
                            🔑 TOKEN AKSES SISWA
                        </div>
                        <div class="apple-token-text my-2" id="token-{{ $e->id }}" style="font-size: 2.2rem; letter-spacing: 0.28em;">
                            {{ $e->token }}
                        </div>

                        {{-- Masa Aktif Token --}}
                        @if($e->token_valid_minutes)
                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                            <span class="small text-secondary">Masa aktif: <b>{{ $e->token_valid_minutes }}m</b></span>
                            @if($e->isTokenExpired())
                                <span class="badge bg-danger" id="badge-expiry-{{ $e->id }}">Token Kedaluwarsa</span>
                            @else
                                <span class="badge bg-info" id="badge-expiry-{{ $e->id }}">
                                    Berlaku s.d. {{ $e->token_expires_at?->format('H:i') }}
                                </span>
                            @endif
                        </div>
                        @endif

                        @if($e->token_rotate_minutes)
                        <div class="d-flex justify-content-center align-items-center gap-2 mt-1">
                            <span class="text-secondary" style="font-size:0.75rem;">Rotasi otomatis tiap <b>{{ $e->token_rotate_minutes }}m</b></span>
                            <span class="badge bg-warning text-dark fw-bold"
                                  id="countdown-{{ $e->id }}"
                                  data-seconds="{{ $e->secondsUntilNextRotation() }}"
                                  data-exam-id="{{ $e->id }}"
                                  data-rotate-url="{{ route('exams.rotate_token', $e) }}"
                                  data-csrf="{{ csrf_token() }}">
                                —
                            </span>
                        </div>
                        @endif

                        <div class="mt-3">
                            <form method="post" action="{{ route('exams.rotate_token', $e) }}" class="m-0 d-inline-block">
                                @csrf
                                <button class="btn btn-sm btn-outline-success py-1.5 px-3 fw-semibold" style="font-size:0.8rem; border-radius: 8px;">
                                    🔄 Generate Ulang Token
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Kolom 3: Status Peserta & Aksi Cepat --}}
                <div class="col-lg-4 ps-lg-4">
                    <div class="p-3 rounded-3 bg-light bg-opacity-75 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark fs-6">Status Peserta</span>
                            <span class="fw-bold text-dark fs-6">{{ $e->attempts_count }} <span class="text-secondary fw-normal small">/ {{ $e->eligible_count ?? 0 }} Siswa</span></span>
                        </div>
                        @php
                            $attCount = $e->attempts_count ?? 0;
                            $eligCount = $e->eligible_count ?? 0;
                            $pct = $eligCount > 0 ? round(($attCount / $eligCount) * 100) : 0;
                        @endphp
                        <div class="progress mb-2" style="height: 6px; border-radius: 999px; background: rgba(0,0,0,0.08);">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-2" style="font-size:0.75rem;">
                            <span>Tingkat Kehadiran</span>
                            <span class="fw-semibold text-dark">{{ $pct }}% Hadir</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @if(($e->in_progress_count ?? 0) > 0)
                                <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:0.72rem;">
                                    <span class="live-dot live-dot-sm"></span> {{ $e->in_progress_count }} Mengerjakan
                                </span>
                            @endif
                            @if(($e->submitted_count ?? 0) > 0)
                                <span class="badge bg-success" style="font-size:0.72rem;">
                                    ✓ {{ $e->submitted_count }} Selesai
                                </span>
                            @endif
                            @php $unstartedCard = max(0, ($e->eligible_count ?? 0) - ($e->attempts_count ?? 0)); @endphp
                            @if($unstartedCard > 0)
                                <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.72rem;">
                                    ⏳ {{ $unstartedCard }} Belum Mulai
                                </span>
                            @endif
                            @if(($e->violations_count ?? 0) > 0)
                                <span class="badge bg-danger" style="font-size:0.72rem;">
                                    ⚠️ {{ $e->violations_count }} Melanggar
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 py-2 fw-semibold"
                                style="border-radius: 10px; font-size: 0.88rem;"
                                onclick="openParticipantsModal({{ $e->id }}, '{{ addslashes($e->title) }}')">
                            <span>👥</span> Data &amp; Status Peserta ({{ $e->attempts_count }})
                        </button>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary w-100 d-inline-flex align-items-center justify-content-center gap-1 py-1.5"
                               style="border-radius: 10px; font-size: 0.82rem;"
                               href="{{ route('questions.list', $e) }}">
                                <span>📝</span> Soal ({{ $e->questions()->count() }})
                            </a>
                            @if(auth()->user()->role === 'admin' || $e->created_by == auth()->id())
                            <a class="btn btn-outline-success w-100 d-inline-flex align-items-center justify-content-center gap-1 py-1.5"
                               style="border-radius: 10px; font-size: 0.82rem;"
                               href="{{ route('results.admin', ['exam_id' => $e->id]) }}">
                                <span>📊</span> Nilai
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeExams->count() === 2)
        {{-- Layout 2 Ujian: 2 Kolom Proporsional (50% / 50%) --}}
        <div class="row g-4 mb-2">
            @foreach($activeExams as $e)
            <div class="col-lg-6">
                <div class="card p-4 h-100 shadow-sm border-0" style="border-left: 5px solid #34c759 !important; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 d-inline-flex align-items-center gap-1 fw-bold" style="font-size:0.72rem;">
                                    <span class="live-dot"></span> Ujian Berlangsung
                                </span>
                                @if($e->randomize_by_type)
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.68rem;">🔀 Acak</span>
                                @endif
                            </div>
                            <h5 class="fw-bold text-dark mb-0 fs-5">{{ $e->title }}</h5>
                            <div class="text-secondary small">{{ $e->subject }}</div>
                        </div>
                        <span class="badge bg-primary px-2.5 py-1.5" style="font-size: 0.75rem; border-radius: 6px;">{{ $e->getTargetBadgeLabel() }}</span>
                    </div>

                    <div class="row g-3 my-2 align-items-stretch">
                        <div class="col-sm-6">
                            <div class="apple-token-card text-center p-3 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="text-secondary small fw-bold" style="font-size:0.68rem; letter-spacing:0.08em;">TOKEN AKSES SISWA</div>
                                    <div class="apple-token-text my-1" id="token-{{ $e->id }}" style="font-size:1.75rem; letter-spacing:0.25em;">
                                        {{ $e->token }}
                                    </div>
                                    @if($e->token_valid_minutes)
                                        <div class="text-secondary" style="font-size:0.72rem;">
                                            Masa aktif: <b>{{ $e->token_valid_minutes }}m</b>
                                            @if($e->isTokenExpired())
                                                <span class="badge bg-danger ms-1" id="badge-expiry-{{ $e->id }}">Kedaluwarsa</span>
                                            @else
                                                <span class="badge bg-info ms-1" id="badge-expiry-{{ $e->id }}">s.d. {{ $e->token_expires_at?->format('H:i') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($e->token_rotate_minutes)
                                        <div class="mt-1 small" style="font-size:0.72rem;">
                                            Rotasi:
                                            <span class="badge bg-warning text-dark fw-bold"
                                                  id="countdown-{{ $e->id }}"
                                                  data-seconds="{{ $e->secondsUntilNextRotation() }}"
                                                  data-exam-id="{{ $e->id }}"
                                                  data-rotate-url="{{ route('exams.rotate_token', $e) }}"
                                                  data-csrf="{{ csrf_token() }}">
                                                —
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <form method="post" action="{{ route('exams.rotate_token', $e) }}" class="m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success py-1 px-2.5 w-100" style="font-size:0.75rem; border-radius: 8px;">
                                            🔄 Generate Ulang
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light bg-opacity-75 border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold text-dark">Status Peserta</span>
                                        <span class="small text-secondary fw-semibold">{{ $e->attempts_count }} / {{ $e->eligible_count ?? 0 }}</span>
                                    </div>
                                    @php
                                        $pct = ($e->eligible_count ?? 0) > 0 ? round(($e->attempts_count / $e->eligible_count) * 100) : 0;
                                    @endphp
                                    <div class="progress mb-2" style="height: 5px; border-radius: 999px; background: rgba(0,0,0,0.08);">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        @if(($e->in_progress_count ?? 0) > 0)
                                            <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:0.68rem;">
                                                <span class="live-dot live-dot-sm"></span> {{ $e->in_progress_count }} Mengerjakan
                                            </span>
                                        @endif
                                        @if(($e->submitted_count ?? 0) > 0)
                                            <span class="badge bg-success" style="font-size:0.68rem;">
                                                ✓ {{ $e->submitted_count }} Selesai
                                            </span>
                                        @endif
                                        @php $unstartedCard = max(0, ($e->eligible_count ?? 0) - ($e->attempts_count ?? 0)); @endphp
                                        @if($unstartedCard > 0)
                                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.68rem;">
                                                ⏳ {{ $unstartedCard }} Belum Mulai
                                            </span>
                                        @endif
                                        @if(($e->violations_count ?? 0) > 0)
                                            <span class="badge bg-danger" style="font-size:0.68rem;">
                                                ⚠️ {{ $e->violations_count }} Melanggar
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-secondary small pt-2 border-top" style="font-size:0.75rem;">
                                    ⏱️ Selesai: <b>{{ $e->ends_at->format('H:i') }} WIB</b>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top mt-2">
                        <button type="button" class="btn btn-sm btn-primary flex-fill d-inline-flex align-items-center justify-content-center gap-1 py-1.5 fw-semibold"
                                style="border-radius: 8px;"
                                onclick="openParticipantsModal({{ $e->id }}, '{{ addslashes($e->title) }}')">
                            <span>👥</span> Peserta ({{ $e->attempts_count }})
                        </button>
                        <a class="btn btn-sm btn-outline-primary flex-fill d-inline-flex align-items-center justify-content-center gap-1 py-1.5 fw-semibold"
                           style="border-radius: 8px;"
                           href="{{ route('questions.list', $e) }}">
                            <span>📝</span> Soal ({{ $e->questions()->count() }})
                        </a>
                        @if(auth()->user()->role === 'admin' || $e->created_by == auth()->id())
                        <a class="btn btn-sm btn-outline-success flex-fill d-inline-flex align-items-center justify-content-center gap-1 py-1.5 fw-semibold"
                           style="border-radius: 8px;"
                           href="{{ route('results.admin', ['exam_id' => $e->id]) }}">
                            <span>📊</span> Nilai
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        {{-- Layout 3+ Ujian: 3 Kolom Grid --}}
        <div class="row g-4 mb-2">
            @foreach($activeExams as $e)
            <div class="col-md-6 col-lg-4">
                <div class="card p-3.5 h-100 shadow-sm border-0" style="border-left: 4px solid #34c759 !important; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border-radius: 18px;">
                    <div class="card-body d-flex flex-column justify-content-between p-1">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="fw-bold mb-0 fs-6 text-dark">{{ $e->title }}</h6>
                                    <div class="text-secondary small">{{ $e->subject }}</div>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" style="font-size:0.7rem;">
                                    <span class="live-dot"></span> Aktif
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-primary" style="font-size: 0.72rem;">{{ $e->getTargetBadgeLabel() }}</span>
                            </div>

                            <div class="apple-token-card text-center mb-3 p-2.5">
                                <div class="text-secondary small fw-bold mb-1" style="letter-spacing: 0.08em; font-size: 0.68rem;">TOKEN AKSES SISWA</div>
                                <div class="apple-token-text" id="token-{{ $e->id }}" style="font-size: 1.8rem; letter-spacing: 0.25em;">
                                    {{ $e->token }}
                                </div>

                                @if($e->token_valid_minutes)
                                <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <div class="small">
                                        Masa aktif: <b>{{ $e->token_valid_minutes }}m</b>
                                    </div>
                                    <div class="mt-1">
                                        @if($e->isTokenExpired())
                                            <span class="badge bg-danger" id="badge-expiry-{{ $e->id }}">Token Kedaluwarsa</span>
                                        @else
                                            <span class="badge bg-info" id="badge-expiry-{{ $e->id }}">
                                                Berlaku s.d. {{ $e->token_expires_at?->format('H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($e->token_rotate_minutes)
                                <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <div class="text-secondary" style="font-size:0.72rem;">Rotasi tiap <b>{{ $e->token_rotate_minutes }}m</b></div>
                                    <div class="small fw-semibold mt-1">
                                        Ganti dalam: 
                                        <span class="badge bg-warning text-dark fw-bold"
                                              id="countdown-{{ $e->id }}"
                                              data-seconds="{{ $e->secondsUntilNextRotation() }}"
                                              data-exam-id="{{ $e->id }}"
                                              data-rotate-url="{{ route('exams.rotate_token', $e) }}"
                                              data-csrf="{{ csrf_token() }}">
                                            —
                                        </span>
                                    </div>
                                </div>
                                @endif

                                <div class="mt-2">
                                    <form method="post" action="{{ route('exams.rotate_token', $e) }}" class="m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success py-1 px-3" style="font-size:0.75rem; border-radius: 8px;">
                                            🔄 Generate Ulang Token
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="p-2.5 rounded-3 bg-light bg-opacity-75 border mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark">Status Peserta</span>
                                    <span class="small text-secondary fw-semibold">{{ $e->attempts_count }} / {{ $e->eligible_count ?? 0 }} Siswa</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    @if(($e->in_progress_count ?? 0) > 0)
                                        <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:0.68rem;">
                                            <span class="live-dot live-dot-sm"></span> {{ $e->in_progress_count }} Mengerjakan
                                        </span>
                                    @endif
                                    @if(($e->submitted_count ?? 0) > 0)
                                        <span class="badge bg-success" style="font-size:0.68rem;">
                                            ✓ {{ $e->submitted_count }} Selesai
                                        </span>
                                    @endif
                                    @php $unstartedCard = max(0, ($e->eligible_count ?? 0) - ($e->attempts_count ?? 0)); @endphp
                                    @if($unstartedCard > 0)
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.68rem;">
                                            ⏳ {{ $unstartedCard }} Belum Mulai
                                        </span>
                                    @endif
                                    @if(($e->violations_count ?? 0) > 0)
                                        <span class="badge bg-danger" style="font-size:0.68rem;">
                                            ⚠️ {{ $e->violations_count }} Melanggar
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-1 justify-content-center mb-2">
                                <a class="btn btn-sm btn-outline-primary flex-fill d-inline-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; font-size: 0.78rem;" href="{{ route('questions.list', $e) }}">
                                    <span>📝</span> Soal
                                </a>
                                <button type="button" class="btn btn-sm btn-primary flex-fill d-inline-flex align-items-center justify-content-center gap-1"
                                        style="border-radius: 8px; font-size: 0.78rem;"
                                        onclick="openParticipantsModal({{ $e->id }}, '{{ addslashes($e->title) }}')">
                                    <span>👥</span> Peserta
                                </button>
                                @if(auth()->user()->role === 'admin' || $e->created_by == auth()->id())
                                <a class="btn btn-sm btn-outline-success flex-fill d-inline-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; font-size: 0.78rem;" href="{{ route('results.admin', ['exam_id' => $e->id]) }}">
                                    <span>📊</span> Nilai
                                </a>
                                @endif
                            </div>
                            <div class="text-center text-secondary" style="font-size:0.75rem;">
                                Selesai: <b>{{ $e->ends_at->format('H:i') }} WIB</b>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif

{{-- ── Semua Ujian (Tabel Frosted Glass dengan Fitur Hapus Massal) ── --}}
<div class="card p-4">
    <form method="POST" action="{{ route('exams.bulk_destroy') }}" id="bulkExamForm"
          onsubmit="return confirm('Yakin ingin menghapus seluruh ujian yang dipilih? Seluruh soal dan riwayat ujian terkait akan ikut terhapus permanen.')">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0">Daftar Semua Ujian</h5>
                <span class="badge bg-secondary">{{ $exams->total() }} total</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-outline-danger" id="btnBulkDeleteExams" disabled>
                    🗑️ Hapus Ujian Terpilih
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="checkAllExams" onclick="toggleAllExams(this)">
                        </th>
                        <th>Nama Ujian</th>
                        @if(auth()->user()->role === 'admin') <th>Pembuat</th> @endif
                        <th style="white-space: nowrap;">Jadwal Pelaksanaan</th>
                        <th>Status Ujian</th>
                        <th style="min-width: 170px;">Data &amp; Status Peserta</th>
                        <th style="white-space: nowrap;">Token &amp; Masa Aktif</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $e)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="exam_ids[]" value="{{ $e->id }}" class="form-check-input exam-checkbox" onclick="updateBulkDeleteState()">
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $e->title }}</div>
                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                <span class="text-secondary small fw-medium">{{ $e->subject }}</span>
                                <span class="badge bg-primary" style="font-size: 0.72rem;">{{ $e->getTargetBadgeLabel() }}</span>
                                @if($e->randomize_by_type)
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.68rem;">🔀 Acak per Jenis</span>
                                @endif
                            </div>
                        </td>
                        @if(auth()->user()->role === 'admin')
                        <td style="white-space: nowrap;"><small class="text-secondary">{{ $e->creator->name ?? '-' }}</small></td>
                        @endif
                        <td style="white-space: nowrap;">
                            <div class="small fw-semibold text-dark">{{ $e->starts_at->format('d/m/Y') }}</div>
                            <div class="text-secondary" style="font-size: 0.76rem;">{{ $e->starts_at->format('H:i') }} &ndash; {{ $e->ends_at->format('H:i') }} WIB</div>
                        </td>
                        <td>
                            @if($e->isActive())
                                <span class="badge bg-success">Aktif</span>
                            @elseif($e->is_published && $e->starts_at > now())
                                <span class="badge bg-info">Terjadwal</span>
                            @elseif($e->is_published && $e->ends_at < now())
                                <span class="badge bg-secondary">Selesai</span>
                            @else
                                <span class="badge bg-warning">Draft</span>
                            @endif
                        </td>
                        <td class="clickable-cell" onclick="openParticipantsModal({{ $e->id }}, '{{ addslashes($e->title) }}')" title="Klik untuk melihat rincian peserta">
                            @php
                                $attCount   = $e->attempts_count ?? 0;
                                $eligCount  = $e->eligible_count ?? 0;
                                $inProg     = $e->in_progress_count ?? 0;
                                $subm       = $e->submitted_count ?? 0;
                                $viol       = $e->violations_count ?? 0;
                                $unstarted  = max(0, $eligCount - $attCount);
                            @endphp
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="fw-bold text-dark fs-6">{{ $attCount }}</span>
                                <span class="text-secondary small">/ {{ $eligCount }} Siswa</span>
                                @if($eligCount > 0)
                                    <span class="badge bg-light text-secondary border ms-1" style="font-size:0.68rem;">
                                        {{ round(($attCount / $eligCount) * 100) }}% Hadir
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @if($inProg > 0)
                                    <span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:0.68rem;">
                                        <span class="live-dot live-dot-sm"></span> {{ $inProg }} Mengerjakan
                                    </span>
                                @endif
                                @if($subm > 0)
                                    <span class="badge bg-success" style="font-size:0.68rem;">
                                        ✓ {{ $subm }} Selesai
                                    </span>
                                @endif
                                @if($unstarted > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.68rem;">
                                        ⏳ {{ $unstarted }} Belum Mulai
                                    </span>
                                @endif
                                @if($viol > 0)
                                    <span class="badge bg-danger" style="font-size:0.68rem;">
                                        ⚠️ {{ $viol }} Melanggar
                                    </span>
                                @endif
                                @if($attCount == 0 && $unstarted == 0)
                                    <span class="text-muted small" style="font-size:0.72rem;">Belum ada target peserta</span>
                                @endif
                            </div>
                        </td>
                        <td style="white-space: nowrap;">
                            <div class="d-flex align-items-center gap-1">
                                <code class="px-2 py-1 rounded" style="background:rgba(0,113,227,0.08); color:var(--apple-blue); font-weight:700; font-size:0.85rem; letter-spacing:0.04em;">{{ $e->token }}</code>
                            </div>
                            @if($e->token_valid_minutes)
                                <div class="mt-1">
                                    @if($e->isTokenExpired())
                                        <span class="badge bg-danger" style="font-size:0.65rem;">Kedaluwarsa</span>
                                    @else
                                        <span class="badge bg-info" style="font-size:0.65rem;">Masa Aktif: {{ $e->token_valid_minutes }}m</span>
                                    @endif
                                </div>
                            @endif
                            @if($e->token_rotate_minutes)
                                <div class="text-secondary mt-1" style="font-size:0.68rem;">↺ Rotasi {{ $e->token_rotate_minutes }}m</div>
                            @endif
                        </td>
                        <td class="text-center" style="white-space: nowrap;">
                            <div class="table-action-group justify-content-center">
                                <button type="button" class="btn-action btn-action-info"
                                        onclick="openParticipantsModal({{ $e->id }}, '{{ addslashes($e->title) }}')"
                                        title="Lihat data dan status seluruh peserta ujian">
                                    <span>👥</span> Peserta ({{ $e->attempts_count ?? 0 }})
                                </button>
                                <a class="btn-action btn-action-primary" href="{{ route('questions.list', $e) }}" title="Kelola butir soal">
                                    <span>📝</span> Soal ({{ $e->questions()->count() }})
                                </a>
                                <a class="btn-action btn-action-secondary" href="{{ route('exams.edit', $e) }}" title="Edit jadwal dan pengaturan">
                                    <span>✏️</span> Edit
                                </a>
                                @if(auth()->user()->role === 'admin' || $e->created_by == auth()->id())
                                <a class="btn-action btn-action-success" href="{{ route('results.admin', ['exam_id' => $e->id]) }}" title="Lihat hasil &amp; rilis nilai ujian ini">
                                    <span>📊</span> Nilai ({{ $e->attempts()->whereNotNull('submitted_at')->count() }})
                                </a>
                                @endif
                                @if(auth()->user()->role === 'admin' || $e->created_by == auth()->id())
                                <button type="button" class="btn-action btn-action-danger"
                                        onclick="deleteSingleExam({{ $e->id }}, '{{ addslashes($e->title) }}')" title="Hapus ujian ini">
                                    <span>🗑️</span> Hapus
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center text-muted py-4">Belum ada ujian. Klik tombol di atas untuk membuat ujian baru.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    
    <div class="mt-3">
        {{ $exams->links() }}
    </div>
</div>

{{-- Hidden Form for Single Exam Delete --}}
<form id="singleExamDeleteForm" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

{{-- ── Modal Data & Status Peserta Ujian ── --}}
<div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0 shadow-lg" style="border-radius: 24px !important; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(28px); overflow: hidden;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3" style="border-color: rgba(0,0,0,0.07) !important;">
                <div class="w-100">
                    {{-- Baris Atas: Judul Ujian dan Tombol Aksi --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                            <div class="modal-header-icon shadow-sm">
                                👥
                            </div>
                            <div style="min-width: 0;">
                                <div class="text-secondary small fw-semibold" style="font-size: 0.74rem; letter-spacing: 0.04em; text-transform: uppercase;">
                                    Data &amp; Status Peserta Ujian
                                </div>
                                <h4 class="modal-title fw-bold text-dark text-truncate mb-0" id="participantsModalLabel" style="font-size: 1.25rem;">
                                    Memuat Data Ujian...
                                </h4>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 ms-auto">
                            <button type="button" class="btn-white-glass d-inline-flex align-items-center gap-1 shadow-sm"
                                    onclick="reloadParticipantsData()" title="Segarkan Data Peserta">
                                <span id="refreshIcon">🔄</span>
                                <span>Refresh</span>
                            </button>
                            <a href="#" id="btnFullParticipantsPage" class="btn-primary-glass d-inline-flex align-items-center gap-1 shadow-sm text-decoration-none"
                               target="_blank" title="Buka Halaman Lengkap di Tab Baru">
                                <span>↗️</span>
                                <span>Halaman Penuh</span>
                            </a>
                            <button type="button" class="btn-close ms-2 shadow-none" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                    </div>

                    {{-- Baris Bawah: Meta Badges Rapi (Mapel, Target, Token, Durasi, Status) --}}
                    <div class="d-flex align-items-center flex-wrap gap-2 mt-2 pt-2 border-top" id="participantsModalSub" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="text-secondary small">Memuat rincian ujian...</span>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4 pt-3">
                {{-- Stat Ringkasan Peserta --}}
                <div class="row g-2 mb-3" id="modalStatsContainer">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="p-2 rounded-3 border text-center bg-light">
                            <div class="text-secondary small" style="font-size:0.72rem;">Total Siswa</div>
                            <div class="fw-bold fs-5 text-dark" id="modalStatTotal">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="p-2 rounded-3 border border-primary text-center bg-primary-subtle">
                            <div class="text-primary small fw-semibold" style="font-size:0.72rem;">Mengerjakan</div>
                            <div class="fw-bold fs-5 text-primary" id="modalStatInProgress">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="p-2 rounded-3 border border-success text-center bg-success-subtle">
                            <div class="text-success small fw-semibold" style="font-size:0.72rem;">Selesai</div>
                            <div class="fw-bold fs-5 text-success" id="modalStatSubmitted">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="p-2 rounded-3 border text-center bg-secondary-subtle">
                            <div class="text-secondary small fw-semibold" style="font-size:0.72rem;">Belum Mulai</div>
                            <div class="fw-bold fs-5 text-secondary" id="modalStatUnstarted">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="p-2 rounded-3 border border-danger text-center bg-danger-subtle">
                            <div class="text-danger small fw-semibold" style="font-size:0.72rem;">Ada Pelanggaran</div>
                            <div class="fw-bold fs-5 text-danger" id="modalStatViolations">0</div>
                        </div>
                    </div>
                </div>

                {{-- Filter & Search Controls --}}
                <div class="p-3 rounded-3 bg-light bg-opacity-75 border mb-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Filter Status:</label>
                            <select id="modalFilterStatus" class="form-select form-select-sm" onchange="renderFilteredParticipants()">
                                <option value="">Semua Status Peserta</option>
                                <option value="mengerjakan">🟢 Sedang Mengerjakan</option>
                                <option value="selesai">✓ Selesai</option>
                                <option value="force_submitted">🔴 Dikumpulkan Paksa</option>
                                <option value="belum_mulai">⏳ Belum Mulai</option>
                                <option value="violations">⚠️ Ada Pelanggaran</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Filter Kelas:</label>
                            <select id="modalFilterClass" class="form-select form-select-sm" onchange="renderFilteredParticipants()">
                                <option value="">Semua Kelas</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">Cari Peserta:</label>
                            <input type="text" id="modalSearchInput" class="form-control form-control-sm"
                                   placeholder="Ketik nama siswa atau NIS..." oninput="renderFilteredParticipants()">
                        </div>
                    </div>
                </div>

                {{-- Tabel Peserta --}}
                <div id="modalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-secondary small mt-2">Memuat data peserta ujian...</div>
                </div>

                <div class="table-responsive" id="modalTableWrap" style="display:none; max-height: 480px;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th style="width: 40px;" class="text-center">No.</th>
                                <th>Nama Peserta</th>
                                <th>Kelas</th>
                                <th>Status Pengerjaan</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Durasi</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Integritas</th>
                            </tr>
                        </thead>
                        <tbody id="modalParticipantsTbody">
                            {{-- Diisi secara dinamis via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top px-4 pb-4 pt-3 d-flex justify-content-between align-items-center" style="border-color: rgba(0,0,0,0.07) !important;">
                <div class="small text-secondary" id="modalFooterCount">
                    Menampilkan 0 peserta
                </div>
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal Detail Pelanggaran ── --}}
<div class="modal fade" id="violationLogModal" tabindex="-1" style="z-index: 1060;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-3 border-0 shadow-lg">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold" id="violationLogTitle">⚠️ Rincian Pelanggaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1" id="violationLogBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Checkbox toggle untuk hapus massal ujian
function toggleAllExams(master) {
    document.querySelectorAll('.exam-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkDeleteState();
}

function updateBulkDeleteState() {
    const checkedCount = document.querySelectorAll('.exam-checkbox:checked').length;
    const btn = document.getElementById('btnBulkDeleteExams');
    if (btn) {
        btn.disabled = checkedCount === 0;
        btn.innerHTML = `🗑️ Hapus Ujian Terpilih (${checkedCount})`;
    }
}

function deleteSingleExam(examId, title) {
    if (confirm(`Yakin ingin menghapus ujian "${title}"? Semua butir soal dan data pengerjaan ujian akan ikut terhapus.`)) {
        const form = document.getElementById('singleExamDeleteForm');
        form.action = '{{ url("/exams") }}/' + examId;
        form.submit();
    }
}

// Countdown untuk rotasi token otomatis
document.querySelectorAll('[id^="countdown-"]').forEach(function(el) {
    var seconds   = parseInt(el.dataset.seconds) || 0;
    var examId    = el.dataset.examId;
    var rotateUrl = el.dataset.rotateUrl;
    var csrf      = el.dataset.csrf;
    var tokenEl   = document.getElementById('token-' + examId);

    function pad(n) { return n < 10 ? '0' + n : n; }

    function tick() {
        if (seconds <= 0) {
            fetch(rotateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (tokenEl) {
                    tokenEl.textContent = data.token;
                    tokenEl.style.transition = 'transform 0.3s ease, color 0.3s ease';
                    tokenEl.style.transform = 'scale(1.08)';
                    setTimeout(function(){ tokenEl.style.transform = 'scale(1)'; }, 400);
                }
                seconds = data.seconds_remaining || 0;
                el.textContent = pad(Math.floor(seconds/60)) + ':' + pad(seconds%60);
                setTimeout(tick, 1000);
            })
            .catch(function(){ el.textContent = 'Error'; });
            return;
        }
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        el.textContent = pad(m) + ':' + pad(s);
        seconds--;
        setTimeout(tick, 1000);
    }

    tick();
});

// ── Penanganan Modal Data & Status Peserta Ujian ──────────────────────────
let currentExamParticipants = [];
let activeExamId = null;

function openParticipantsModal(examId, examTitle) {
    activeExamId = examId;
    const modalEl = document.getElementById('participantsModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.getElementById('participantsModalLabel').textContent = examTitle;
    document.getElementById('participantsModalSub').innerHTML = '<span class="text-secondary small"><span class="spinner-border spinner-border-sm me-1"></span>Memuat rincian informasi ujian...</span>';
    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalTableWrap').style.display = 'none';
    document.getElementById('modalFilterStatus').value = '';
    document.getElementById('modalSearchInput').value = '';
    document.getElementById('btnFullParticipantsPage').href = '{{ url("/exams") }}/' + examId + '/peserta';

    modal.show();
    reloadParticipantsData();
}

function reloadParticipantsData() {
    if (!activeExamId) return;
    const refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) refreshIcon.classList.add('spin');

    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalTableWrap').style.display = 'none';

    fetch('{{ url("/exams") }}/' + activeExamId + '/peserta', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (refreshIcon) refreshIcon.classList.remove('spin');
        currentExamParticipants = data.participants || [];

        // Title update
        if (data.exam && data.exam.title) {
            document.getElementById('participantsModalLabel').textContent = data.exam.title;
        }

        // Subtitle Meta Badges
        let statusBadgeHtml = '';
        if (data.exam.is_active) {
            statusBadgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-inline-flex align-items-center gap-1"><span class="live-dot live-dot-sm"></span> Ujian Aktif</span>';
        } else if (data.exam.status_label === 'Selesai') {
            statusBadgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">⚪ Ujian Selesai</span>';
        } else {
            statusBadgeHtml = '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">🔵 Terjadwal</span>';
        }

        document.getElementById('participantsModalSub').innerHTML = `
            <span class="badge bg-light text-secondary border px-2 py-1">📚 Mapel: <b class="text-dark">${escapeHtml(data.exam.subject)}</b></span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">${escapeHtml(data.exam.target_badge)}</span>
            <span class="badge bg-light text-secondary border px-2 py-1">🔑 Token: <code class="px-1.5 py-0.5 rounded fw-bold" style="background:rgba(0,113,227,0.08); color:var(--apple-blue); letter-spacing:0.04em;">${escapeHtml(data.exam.token)}</code></span>
            <span class="badge bg-light text-secondary border px-2 py-1">⏱️ Durasi: <b class="text-dark">${data.exam.duration_minutes} Menit</b></span>
            ${statusBadgeHtml}
        `;

        // Ringkasan Statistik
        document.getElementById('modalStatTotal').textContent      = data.stats.total_eligible;
        document.getElementById('modalStatInProgress').textContent = data.stats.in_progress_count;
        document.getElementById('modalStatSubmitted').textContent  = data.stats.submitted_count;
        document.getElementById('modalStatUnstarted').textContent  = data.stats.unstarted_count;
        document.getElementById('modalStatViolations').textContent = data.stats.violations_count;

        // Opsi Dropdown Kelas
        const classSelect = document.getElementById('modalFilterClass');
        classSelect.innerHTML = '<option value="">Semua Kelas</option>';
        (data.classes || []).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            classSelect.appendChild(opt);
        });

        document.getElementById('modalLoading').style.display = 'none';
        document.getElementById('modalTableWrap').style.display = 'block';
        renderFilteredParticipants();
    })
    .catch(err => {
        if (refreshIcon) refreshIcon.classList.remove('spin');
        document.getElementById('modalLoading').innerHTML = '<div class="text-danger small py-3">Gagal memuat data peserta. Pastikan koneksi dan sesi login aktif.</div>';
    });
}

function renderFilteredParticipants() {
    const status = document.getElementById('modalFilterStatus').value;
    const kelas  = document.getElementById('modalFilterClass').value;
    const q      = document.getElementById('modalSearchInput').value.trim().toLowerCase();

    let filtered = currentExamParticipants.filter(p => {
        if (status) {
            if (status === 'violations') {
                if (p.violations <= 0) return false;
            } else if (p.status !== status) {
                return false;
            }
        }
        if (kelas && p.class_name !== kelas) return false;
        if (q) {
            const nameMatch = (p.name || '').toLowerCase().includes(q);
            const nisMatch  = (p.student_number || '').toLowerCase().includes(q);
            const clsMatch  = (p.class_name || '').toLowerCase().includes(q);
            if (!nameMatch && !nisMatch && !clsMatch) return false;
        }
        return true;
    });

    const tbody = document.getElementById('modalParticipantsTbody');
    tbody.innerHTML = '';

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data peserta yang cocok dengan filter saat ini.</td></tr>';
        document.getElementById('modalFooterCount').textContent = 'Menampilkan 0 peserta';
        return;
    }

    filtered.forEach((p, index) => {
        let statusBadge = '';
        if (p.status === 'mengerjakan') {
            statusBadge = '<span class="badge bg-primary d-inline-flex align-items-center gap-1"><span class="live-dot live-dot-sm"></span> Mengerjakan</span>';
        } else if (p.status === 'selesai') {
            statusBadge = '<span class="badge bg-success">✓ Selesai</span>';
        } else if (p.status === 'force_submitted') {
            statusBadge = '<span class="badge bg-danger">🔴 Dikumpulkan Paksa</span>';
        } else {
            statusBadge = '<span class="badge bg-secondary-subtle text-secondary border">⏳ Belum Mulai</span>';
        }

        let integrityHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle">✓ Tertib</span>';
        if (p.violations > 0) {
            const logSafe = encodeURIComponent(JSON.stringify(p.violation_log || []));
            const nameSafe = encodeURIComponent(p.name);
            integrityHtml = `<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fw-semibold" style="font-size:0.75rem;" onclick="showViolationLogModal(decodeURIComponent('${nameSafe}'), decodeURIComponent('${logSafe}'))">⚠️ ${p.violations}x Pelanggaran</button>`;
        } else if (p.status === 'belum_mulai') {
            integrityHtml = '<span class="text-secondary small">—</span>';
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-secondary small fw-bold">${index + 1}</td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(p.name)}</div>
                <div class="text-secondary small">NIS: ${escapeHtml(p.student_number)}</div>
            </td>
            <td><span class="badge bg-secondary">${escapeHtml(p.class_name)}</span></td>
            <td>${statusBadge}</td>
            <td>
                <div class="small fw-semibold">${p.started_time || '—'}</div>
                <small class="text-secondary">${p.started_at ? p.started_at.split(' ')[0] : ''}</small>
            </td>
            <td>
                <div class="small fw-semibold">${p.submitted_time || '—'}</div>
                <small class="text-secondary">${p.submitted_at ? p.submitted_at.split(' ')[0] : ''}</small>
            </td>
            <td><span class="small">${p.duration || '—'}</span></td>
            <td class="text-center">${p.score !== null ? '<span class="fw-bold text-dark">' + p.score + '</span>' : '<span class="text-secondary small">—</span>'}</td>
            <td class="text-center">${integrityHtml}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('modalFooterCount').textContent = `Menampilkan ${filtered.length} dari ${currentExamParticipants.length} total peserta`;
}

function showViolationLogModal(name, logJson) {
    document.getElementById('violationLogTitle').textContent = '⚠️ Log Pelanggaran — ' + name;
    const body = document.getElementById('violationLogBody');
    let log = [];
    try { log = JSON.parse(logJson); } catch(_) {}
    if (!log || log.length === 0) {
        body.innerHTML = '<p class="text-secondary small mb-0">Tidak ada rincian log catatan pelanggaran.</p>';
    } else {
        let html = '<ol class="mb-0 ps-3 small">';
        log.forEach(item => {
            const time = item.time ? `[${item.time}] ` : '';
            html += `<li class="mb-1"><b>${time}</b>${escapeHtml(item.reason || item)}</li>`;
        });
        html += '</ol>';
        body.innerHTML = html;
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('violationLogModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>
@endpush
@endsection
