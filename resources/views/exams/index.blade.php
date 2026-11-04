@extends('layouts.app')
@section('title', 'Kelola Ujian')
@section('content')

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
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge bg-success px-2 py-1 fs-6">🟢</span>
        <h5 class="fw-bold mb-0">Ujian Sedang Berlangsung</h5>
    </div>
    <div class="row g-4">
        @foreach($activeExams as $e)
        <div class="col-md-6 col-lg-4">
            <div class="card p-3 h-100" style="border-color: rgba(52, 199, 89, 0.4) !important;">
                <div class="card-body d-flex flex-column justify-content-between p-2">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="fw-bold mb-0 fs-6">{{ $e->title }}</h6>
                            <span class="badge bg-success">Aktif</span>
                        </div>
                        <div class="text-secondary small mb-2">
                            {{ $e->subject }}
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-primary" style="font-size: 0.72rem;">{{ $e->getTargetBadgeLabel() }}</span>
                        </div>
                        
                        <div class="apple-token-card text-center mb-3">
                            <div class="text-secondary small fw-bold mb-1" style="letter-spacing: 0.08em; font-size: 0.7rem;">TOKEN AKSES SISWA</div>
                            <div class="apple-token-text" id="token-{{ $e->id }}">
                                {{ $e->token }}
                            </div>

                            {{-- Info Masa Aktif Token --}}
                            @if($e->token_valid_minutes)
                            <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                                <div class="small">
                                    Masa aktif token: <b>{{ $e->token_valid_minutes }}m</b>
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
                                <div class="text-secondary" style="font-size:0.75rem;">Rotasi otomatis tiap <b>{{ $e->token_rotate_minutes }}m</b></div>
                                <div class="small fw-semibold mt-1">
                                    Ganti dalam: 
                                    <span class="badge bg-warning"
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
                                    <button class="btn btn-sm btn-outline-success py-1 px-3" style="font-size:0.75rem;">
                                        🔄 Generate Ulang Token
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex gap-2 justify-content-center mb-2">
                            <a class="btn btn-sm btn-outline-primary w-100" href="{{ route('questions.list', $e) }}">
                                <span>📝</span> Kelola Soal ({{ $e->questions()->count() }})
                            </a>
                        </div>
                        <div class="text-center text-secondary" style="font-size:0.78rem;">
                            Selesai: <b>{{ $e->ends_at->format('H:i') }}</b> · {{ $e->attempts()->count() }} peserta
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
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
                        <th>Target Kelompok Peserta</th>
                        @if(auth()->user()->role === 'admin') <th>Pembuat</th> @endif
                        <th style="white-space: nowrap;">Jadwal Pelaksanaan</th>
                        <th>Status</th>
                        <th>Token &amp; Masa Aktif</th>
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
                            <div class="fw-bold text-dark">{{ $e->title }}</div>
                            <div class="text-secondary small">{{ $e->subject }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary" style="font-size: 0.75rem;">
                                {{ $e->getTargetBadgeLabel() }}
                            </span>
                        </td>
                        @if(auth()->user()->role === 'admin')
                        <td><small class="text-secondary">{{ $e->creator->name ?? '-' }}</small></td>
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
                        <td>
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
                                <a class="btn-action btn-action-primary" href="{{ route('questions.list', $e) }}" title="Kelola butir soal">
                                    <span>📝</span> Soal ({{ $e->questions()->count() }})
                                </a>
                                <a class="btn-action btn-action-secondary" href="{{ route('exams.edit', $e) }}" title="Edit jadwal dan pengaturan">
                                    <span>✏️</span> Edit
                                </a>
                                @if(auth()->user()->role === 'admin')
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
                        <td colspan="8" class="text-center text-muted py-4">Belum ada ujian. Klik tombol di atas untuk membuat ujian baru.</td>
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
</script>
@endpush
@endsection
