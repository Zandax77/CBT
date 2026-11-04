@extends('layouts.app')
@section('title', 'Hasil Ujian Saya')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Hasil Ujian Saya</h2>
        <p class="text-secondary small mb-0">Riwayat pengerjaan dan perolehan skor ujian yang telah dirilis guru</p>
    </div>
    <a href="{{ route('attempts.index') }}" class="btn btn-secondary">
        <span>✍️</span> Daftar Ujian
    </a>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama Ujian</th>
                    <th>Waktu Dikumpulkan</th>
                    <th class="text-center">Nilai Ujian</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $a)
                <tr>
                    <td>
                        <div class="fw-bold text-dark">{{ $a->exam->title }}</div>
                        <div class="text-secondary small">{{ $a->exam->subject }}</div>
                    </td>
                    <td>
                        <small class="text-secondary">{{ $a->submitted_at ? $a->submitted_at->format('d/m/Y H:i') : '—' }}</small>
                    </td>
                    <td class="text-center">
                        @if($a->is_released && $a->exam->results_released)
                            <span class="badge bg-primary fs-5 px-3 py-1" style="font-family:'JetBrains Mono', monospace; font-weight:700;">
                                {{ number_format($a->score, 2) }}
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <span>🔒</span> Belum Dirilis
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success">
                            <span>✓</span> Selesai
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        <div style="font-size:3rem;">🎯</div>
                        <h6 class="fw-bold mt-3 text-dark">Belum Ada Hasil Ujian</h6>
                        <p class="text-secondary small mb-0">Anda belum menyelesaikan ujian atau nilai belum dirilis oleh pihak sekolah.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
