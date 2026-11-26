@extends('layouts.exambro')

@section('exam-title', $attempt->exam->title)
@section('exam-info', $attempt->exam->subject . ' · ' . $questions->count() . ' soal')

@section('content')

<style>
.question-page {
    animation: fadeInQ 0.18s ease-in-out;
}
@keyframes fadeInQ {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-palette {
    background: #ffffff;
    border: 1.5px solid rgba(0, 0, 0, 0.12);
    color: #1d1d1f;
    transition: all 0.15s ease;
}
.btn-palette:hover {
    transform: translateY(-2px);
    border-color: var(--apple-blue);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.btn-palette.answered {
    background: #34c759 !important;
    border-color: #34c759 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(52, 199, 89, 0.35);
}
.btn-palette.doubtful {
    background: #ff9500 !important;
    border-color: #ff9500 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(255, 149, 0, 0.35);
}
.btn-palette.active-q {
    outline: 3px solid #0071e3 !important;
    outline-offset: 2px !important;
    box-shadow: 0 0 12px rgba(0, 113, 227, 0.4) !important;
}

#submitArea .btn {
    border-radius: 12px;
}
</style>

{{-- ── Container Soal per Halaman ── --}}
<div id="questionContainer">
    @foreach($questions as $i => $q)
    @php
        $isPairing    = $q->type === 'MENJODOHKAN';
        $isMulti      = $q->type === 'PG_MULTI';
        $premises     = $isPairing ? array_diff_key($q->options, ['__pairs' => '']) : $q->options;
        $pairs        = $isPairing ? ($q->options['__pairs'] ?? []) : [];
        $savedAnswer  = $answers[$q->id] ?? '';
        $savedMatches = [];
        if ($isPairing && $savedAnswer) {
            foreach (explode(',', $savedAnswer) as $part) {
                [$pk, $pv] = array_pad(explode(':', trim($part)), 2, '');
                $savedMatches[$pk] = $pv;
            }
        }
        $savedMulti = ($isMulti && $savedAnswer) ? array_map('trim', explode(',', $savedAnswer)) : [];
    @endphp

    <div class="question-page q-card"
         id="qpage_{{ $i }}"
         data-index="{{ $i }}"
         data-qid="{{ $q->id }}"
         style="{{ $i === 0 ? '' : 'display:none;' }}">

        {{-- Header Kartu Soal --}}
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2" style="border-color: rgba(0,0,0,0.08) !important;">
            <div class="d-flex align-items-center gap-1 gap-sm-2 flex-wrap">
                <span class="badge bg-primary px-2 px-sm-3 py-2 fs-6 fw-bold rounded-pill shadow-sm">
                    Soal No. {{ $i + 1 }} <span class="fw-normal opacity-75">/ {{ $questions->count() }}</span>
                </span>
                <span class="badge
                    @if($q->type==='PG') bg-primary-subtle text-primary border border-primary-subtle
                    @elseif($q->type==='PG_MULTI') bg-success-subtle text-success border border-success-subtle
                    @elseif($q->type==='BENAR_SALAH') bg-info-subtle text-info border border-info-subtle
                    @else bg-warning-subtle text-warning border border-warning-subtle @endif" style="font-size:.75rem">
                    @if($q->type === 'PG') Pilihan Ganda
                    @elseif($q->type === 'PG_MULTI') PG Kompleks
                    @elseif($q->type === 'BENAR_SALAH') B / S
                    @else Menjodohkan
                    @endif
                </span>
                <span class="badge bg-light text-secondary border d-none d-sm-inline-block" style="font-size:.72rem;">
                    Bobot: {{ $q->weight }}
                </span>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 rounded-pill px-2 px-sm-3 shadow-sm"
                    onclick="openQuestionPalette()" title="Buka Daftar Nomor Soal">
                <span>📋</span> <span class="d-none d-sm-inline">Daftar Soal</span> (<span class="answered-count-badge">0</span>/{{ $questions->count() }})
            </button>
        </div>

        {{-- Isi Teks Soal --}}
        <div class="q-text question-text mb-3">{!! nl2br(e($q->question_text)) !!}</div>

        {{-- Gambar Soal (jika ada) --}}
        @if($q->image_path)
        <img src="{{ Storage::url($q->image_path) }}" class="q-img"
             alt="Gambar soal {{ $i+1 }}"
             onclick="expandImg(this)">
        @endif

        {{-- Pilihan Ganda Biasa & Benar/Salah --}}
        @if(!$isPairing && !$isMulti)
        <div class="mt-3">
            @foreach($q->options as $key => $value)
            <label class="option-label {{ ($savedAnswer === $key) ? 'selected' : '' }}">
                <input class="answer-radio"
                       type="radio"
                       name="q{{ $q->id }}"
                       data-qid="{{ $q->id }}"
                       value="{{ $key }}"
                       @checked($savedAnswer === $key)>
                <span class="option-key">{{ $key }}.</span>
                <span class="option-label-text">{{ $value }}</span>
            </label>
            @endforeach
        </div>

        {{-- Pilihan Ganda Kompleks (Bisa Pilih > 1) --}}
        @elseif($isMulti)
        <div class="mt-3">
            <div class="small text-secondary fw-semibold mb-2">
                <span class="badge bg-success-subtle text-success me-1">Multichoice</span>
                Pilihlah satu atau lebih jawaban yang benar (centang opsi di bawah ini):
            </div>
            @foreach($q->options as $key => $value)
            @php $isSelected = in_array($key, $savedMulti, true); @endphp
            <label class="option-label {{ $isSelected ? 'selected' : '' }}">
                <input class="answer-checkbox"
                       type="checkbox"
                       name="q{{ $q->id }}[]"
                       data-qid="{{ $q->id }}"
                       value="{{ $key }}"
                       @checked($isSelected)>
                <span class="option-key">{{ $key }}.</span>
                <span class="option-label-text">{{ $value }}</span>
            </label>
            @endforeach
        </div>

        {{-- Menjodohkan --}}
        @else
        <div class="mt-3">
            <div class="small text-secondary fw-semibold mb-2">Pasangkan pernyataan di kolom kiri dengan jawaban di kolom kanan:</div>
            @foreach($premises as $premiseKey => $premiseText)
            <div class="d-flex align-items-stretch align-items-sm-center gap-2 mb-2 flex-column flex-sm-row">
                <div class="flex-grow-1 glass-well py-2 px-3 small">
                    <b>{{ $premiseKey }}.</b> {{ $premiseText }}
                </div>
                <select class="form-select form-select-sm match-select"
                        data-qid="{{ $q->id }}"
                        data-premise="{{ $premiseKey }}"
                        style="min-width: 170px;">
                    <option value="">— pilih pasangan —</option>
                    @foreach($pairs as $pairIdx => $pairText)
                    <option value="{{ $pairIdx + 1 }}"
                        @selected(($savedMatches[$premiseKey] ?? '') == ($pairIdx + 1))>
                        {{ $pairIdx + 1 }}. {{ $pairText }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endforeach
            <input type="hidden" class="match-hidden" data-qid="{{ $q->id }}"
                   id="match_hidden_{{ $q->id }}" value="{{ $savedAnswer }}">
        </div>
        @endif

        {{-- Penanda Khusus Soal Terakhir --}}
        @if($loop->last)
        <div class="mt-4 p-3 rounded-3 border border-danger border-opacity-25 bg-danger bg-opacity-10 text-center">
            <div class="fw-bold text-danger mb-1">🏁 Anda berada di butir soal terakhir (Soal No. {{ $questions->count() }})</div>
            <div class="small text-secondary mb-2">Pastikan semua jawaban telah terisi dan periksa kembali melalui menu <b>Nomor Soal</b> sebelum mengumpulkan ujian.</div>
            <button type="button" class="btn btn-sm btn-danger px-4 py-2 fw-bold shadow-sm" onclick="confirmSubmit()">
                <span>✓</span> Kumpulkan Jawaban Ujian
            </button>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- ── Modal Daftar Nomor Soal (Palette) ── --}}
<div class="modal fade" id="paletteModal" tabindex="-1" aria-labelledby="paletteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card p-3 p-md-4 border-0 shadow-lg" style="border-radius: 24px !important; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(24px);">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="paletteModalLabel">📋 Daftar Nomor Soal</h5>
                    <p class="text-secondary small mb-0">Klik pada kotak nomor untuk langsung berpindah ke soal yang dituju.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                {{-- Keterangan / Legend --}}
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap p-2 rounded-3 bg-light bg-opacity-75 small border">
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-2 bg-success text-white text-center fw-bold shadow-sm" style="width:20px;height:20px;line-height:20px;font-size:0.7rem;">✓</span>
                        <span>Sudah Dijawab (<b id="statAnswered">0</b>)</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-2 bg-warning text-white text-center fw-bold shadow-sm" style="width:20px;height:20px;line-height:20px;font-size:0.7rem;">🏳️</span>
                        <span>Ragu-ragu (<b id="statDoubt">0</b>)</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-2 border bg-white text-secondary text-center fw-bold shadow-sm" style="width:20px;height:20px;line-height:18px;font-size:0.7rem;">—</span>
                        <span>Belum Dijawab (<b id="statUnanswered">0</b>)</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-2 border border-primary border-2 bg-white text-primary text-center fw-bold" style="width:20px;height:20px;line-height:16px;font-size:0.7rem;">•</span>
                        <span>Sedang Dibuka</span>
                    </div>
                </div>

                {{-- Grid Tombol Nomor Soal --}}
                <div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(54px, 1fr));" id="paletteGrid">
                    @foreach($questions as $i => $q)
                    <button type="button"
                            class="btn btn-palette p-1 d-flex flex-column align-items-center justify-content-center shadow-sm"
                            id="palette_btn_{{ $i }}"
                            data-index="{{ $i }}"
                            data-qid="{{ $q->id }}"
                            onclick="jumpToQuestion({{ $i }})"
                            style="height: 52px; border-radius: 12px; font-weight: 700; font-size: 0.95rem;">
                        <span class="palette-num">{{ $i + 1 }}</span>
                        <span class="palette-sub small" style="font-size:0.65rem; line-height:1;"></span>
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-secondary">
                    Total: <b>{{ $questions->count() }} butir soal</b>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="btnSubmitModal" class="btn btn-danger px-4 fw-bold {{ $questions->count() > 1 ? 'd-none' : '' }}" onclick="confirmSubmit()"
                            style="{{ $questions->count() > 1 ? 'display: none !important;' : '' }}">
                        <span>✓</span> Kumpulkan Ujian
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('submit-btn')
<form id="finishForm" method="post" action="{{ route('attempts.submit', $attempt) }}" style="display:none;">
    @csrf
</form>

{{-- ── Bar Navigasi Pengerjaan Per Halaman (Mobile Ergonomic & Clean) ── --}}
<div class="exam-bottom-nav" style="max-width: 760px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center gap-1 gap-sm-2 w-100 flex-nowrap">
        {{-- Sisi Kiri: Sebelumnya --}}
        <button type="button" id="btnPrev" class="btn btn-secondary px-2 px-sm-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm flex-shrink-0"
                onclick="navigateQuestion(-1)">
            <span>⬅</span> <span class="d-none d-sm-inline">Sebelumnya</span>
        </button>

        {{-- Bagian Tengah: Ragu-ragu & Nomor Soal --}}
        <div class="d-flex align-items-center gap-1 gap-sm-2 justify-content-center">
            <button type="button" id="btnDoubt" class="btn btn-outline-warning px-2 px-sm-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm flex-shrink-0"
                    onclick="toggleDoubtful()" title="Tandai ragu-ragu untuk ditinjau ulang nanti">
                <span id="doubtIcon">🏳️</span> <span id="doubtLabel" class="d-none d-sm-inline">Ragu-ragu</span>
            </button>
            <button type="button" class="btn btn-outline-primary px-2 px-sm-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm flex-shrink-0"
                    onclick="openQuestionPalette()" title="Buka daftar nomor soal">
                <span>📋</span> <span class="d-none d-sm-inline">Nomor</span> Soal
            </button>
        </div>

        {{-- Sisi Kanan: Selanjutnya & Kumpulkan --}}
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <button type="button" id="btnNext" class="btn btn-primary px-2 px-sm-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm"
                    onclick="navigateQuestion(1)">
                <span class="d-none d-sm-inline">Selanjutnya</span> <span>➡</span>
            </button>
            <div class="{{ $questions->count() > 1 ? 'd-none' : '' }}" id="submitBtnContainer"
                 style="{{ $questions->count() > 1 ? 'display: none !important;' : '' }}">
                <button type="button" id="btnSubmitPage" class="btn btn-danger px-2 px-sm-3 py-2 fw-bold d-flex align-items-center gap-1 shadow-sm"
                        onclick="confirmSubmit()" title="Kumpulkan jawaban dan selesaikan ujian">
                    <span>✓</span> <span class="d-none d-sm-inline">Kumpulkan</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Konfigurasi Sesi Ujian ────────────────────────────────────────────────
const DEADLINE       = new Date(@json(
    $attempt->started_at->copy()->addMinutes($attempt->exam->duration_minutes)->min($attempt->exam->ends_at)->toIso8601String()
)).getTime();
const SAVE_URL       = @json(route('attempts.save', $attempt));
const SUBMIT_URL     = @json(route('attempts.submit', $attempt));
const VIOLATION_URL  = @json(route('attempts.violation', $attempt));
const RESULT_URL     = @json(route('results.student'));
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;
const MAX_VIOLATIONS = 3;

let ended      = false;
let violations = {{ $attempt->violations }};

// ── Status Pengerjaan Per Halaman & Palette ────────────────────────────────
let currentIndex     = 0;
const totalQuestions = {{ $questions->count() }};
const attemptId      = {{ $attempt->id }};
const doubtfulKey    = 'cbt_doubt_' + attemptId;
let doubtfulList     = new Set();

try {
    const raw = localStorage.getItem(doubtfulKey);
    if (raw) doubtfulList = new Set(JSON.parse(raw));
} catch (_) {}

// Peta status jawaban (question_id => true/false)
const answeredMap = new Map();
@foreach($questions as $i => $q)
    @php
        $ans = $answers[$q->id] ?? '';
    @endphp
    @if(!empty($ans))
        answeredMap.set({{ $q->id }}, true);
    @endif
@endforeach

let paletteModalInstance = null;
function openQuestionPalette() {
    const modalEl = document.getElementById('paletteModal');
    if (!paletteModalInstance && modalEl) {
        paletteModalInstance = new bootstrap.Modal(modalEl);
    }
    const modalSubmitBtn = document.getElementById('btnSubmitModal');
    if (modalSubmitBtn) {
        const isLastQuestion = (currentIndex === totalQuestions - 1);
        if (isLastQuestion) {
            modalSubmitBtn.style.setProperty('display', '', '');
            modalSubmitBtn.classList.remove('d-none');
        } else {
            modalSubmitBtn.style.setProperty('display', 'none', 'important');
            modalSubmitBtn.classList.add('d-none');
        }
    }
    updatePaletteView();
    if (paletteModalInstance) paletteModalInstance.show();
}
window.openQuestionPalette = openQuestionPalette;

function closeQuestionPalette() {
    if (paletteModalInstance) {
        paletteModalInstance.hide();
    }
}

function showQuestion(idx) {
    if (idx < 0 || idx >= totalQuestions) return;
    currentIndex = idx;
    try {
        sessionStorage.setItem('cbt_active_q_' + attemptId, currentIndex);
    } catch (_) {}

    document.querySelectorAll('.question-page').forEach((el, i) => {
        el.style.display = (i === currentIndex) ? 'block' : 'none';
    });

    const prevBtn = document.getElementById('btnPrev');
    const nextBtn = document.getElementById('btnNext');
    const submitBtnContainer = document.getElementById('submitBtnContainer');
    const modalSubmitBtn = document.getElementById('btnSubmitModal');
    const isLastQuestion = (currentIndex === totalQuestions - 1);

    if (prevBtn) {
        prevBtn.disabled = (currentIndex === 0);
        prevBtn.classList.toggle('opacity-50', currentIndex === 0);
    }

    if (nextBtn) {
        if (isLastQuestion) {
            nextBtn.disabled = true;
            nextBtn.classList.add('opacity-50');
            nextBtn.innerHTML = 'Soal Terakhir ⇥';
            nextBtn.title = 'Anda sudah berada di butir soal terakhir';
        } else {
            nextBtn.disabled = false;
            nextBtn.classList.remove('opacity-50');
            nextBtn.innerHTML = 'Selanjutnya <span>➡</span>';
            nextBtn.title = 'Lanjut ke butir soal berikutnya';
        }
    }

    // Tombol kumpulkan jawaban hanya ditampilkan di butir/halaman soal terakhir
    if (submitBtnContainer) {
        if (isLastQuestion) {
            submitBtnContainer.style.setProperty('display', '', '');
            submitBtnContainer.classList.remove('d-none');
        } else {
            submitBtnContainer.style.setProperty('display', 'none', 'important');
            submitBtnContainer.classList.add('d-none');
        }
    }

    if (modalSubmitBtn) {
        if (isLastQuestion) {
            modalSubmitBtn.style.setProperty('display', '', '');
            modalSubmitBtn.classList.remove('d-none');
        } else {
            modalSubmitBtn.style.setProperty('display', 'none', 'important');
            modalSubmitBtn.classList.add('d-none');
        }
    }

    syncDoubtButton();
    updatePaletteView();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}
window.showQuestion = showQuestion;

function navigateQuestion(offset) {
    showQuestion(currentIndex + offset);
}
window.navigateQuestion = navigateQuestion;

function jumpToQuestion(idx) {
    showQuestion(idx);
    closeQuestionPalette();
}
window.jumpToQuestion = jumpToQuestion;

function toggleDoubtful() {
    const activePage = document.getElementById('qpage_' + currentIndex);
    if (!activePage) return;
    const qid = Number(activePage.dataset.qid);

    if (doubtfulList.has(qid)) {
        doubtfulList.delete(qid);
    } else {
        doubtfulList.add(qid);
    }

    try {
        localStorage.setItem(doubtfulKey, JSON.stringify([...doubtfulList]));
    } catch (_) {}

    syncDoubtButton();
    updatePaletteView();
}
window.toggleDoubtful = toggleDoubtful;

function syncDoubtButton() {
    const activePage = document.getElementById('qpage_' + currentIndex);
    if (!activePage) return;
    const qid = Number(activePage.dataset.qid);
    const btn = document.getElementById('btnDoubt');
    const lbl = document.getElementById('doubtLabel');
    const isDoubt = doubtfulList.has(qid);

    if (btn) {
        if (isDoubt) {
            btn.className = 'btn btn-warning px-3 py-2 fw-bold d-flex align-items-center gap-1 shadow-sm';
            if (lbl) lbl.textContent = 'Ragu-ragu (Aktif)';
        } else {
            btn.className = 'btn btn-outline-warning px-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm';
            if (lbl) lbl.textContent = 'Ragu-ragu';
        }
    }
}

function updatePaletteView() {
    let answeredCount = 0;
    let doubtCount    = 0;

    document.querySelectorAll('.question-page').forEach((el, i) => {
        const qid = Number(el.dataset.qid);
        const btn = document.getElementById('palette_btn_' + i);
        if (!btn) return;

        const isAnswered = answeredMap.has(qid) && Boolean(answeredMap.get(qid));
        const isDoubt    = doubtfulList.has(qid);
        const isCurrent  = (i === currentIndex);

        btn.classList.remove('answered', 'doubtful', 'active-q');

        if (isCurrent) {
            btn.classList.add('active-q');
        }

        if (isDoubt) {
            btn.classList.add('doubtful');
            doubtCount++;
        } else if (isAnswered) {
            btn.classList.add('answered');
            answeredCount++;
        }

        const sub = btn.querySelector('.palette-sub');
        if (sub) {
            if (isDoubt) sub.textContent = 'Ragu';
            else if (isAnswered) sub.textContent = '✓';
            else sub.textContent = '';
        }
    });

    const unansweredCount = totalQuestions - answeredCount - doubtCount;

    const elAns = document.getElementById('statAnswered');
    if (elAns) elAns.textContent = answeredCount;
    const elDoubt = document.getElementById('statDoubt');
    if (elDoubt) elDoubt.textContent = doubtCount;
    const elUnans = document.getElementById('statUnanswered');
    if (elUnans) elUnans.textContent = Math.max(0, unansweredCount);

    document.querySelectorAll('.answered-count-badge').forEach(badge => {
        badge.textContent = answeredCount + doubtCount;
    });
}

// ── Navigasi Keyboard (Panah Kiri / Kanan) ─────────────────────────────────
window.addEventListener('keydown', function(e) {
    if (ended) return;
    const tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

    if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
        if (currentIndex > 0) {
            e.preventDefault();
            navigateQuestion(-1);
        }
    } else if (e.key === 'ArrowRight' || e.key === 'PageDown') {
        if (currentIndex < totalQuestions - 1) {
            e.preventDefault();
            navigateQuestion(1);
        }
    }
});

// Restore posisi soal aktif saat halaman siap
window.addEventListener('DOMContentLoaded', () => {
    let savedIdx = 0;
    try {
        const s = sessionStorage.getItem('cbt_active_q_' + attemptId);
        if (s !== null) {
            const parsed = parseInt(s, 10);
            if (!isNaN(parsed) && parsed >= 0 && parsed < totalQuestions) {
                savedIdx = parsed;
            }
        }
    } catch (_) {}
    showQuestion(savedIdx);
});

// ── DOM Elemen Fullscreen & Lockdown ──────────────────────────────────────
const fsLockOverlay = document.getElementById('fsLockOverlay');
const fsLockTitle   = document.getElementById('fsLockTitle');
const fsLockDesc    = document.getElementById('fsLockDesc');
const btnEnterFS    = document.getElementById('btnEnterFS');
const overlay       = document.getElementById('warnOverlay');
const warnMsg       = document.getElementById('warnMsg');
const warnCount     = document.getElementById('warnCount');
const vBar          = document.getElementById('violationBar');
const vMsg          = document.getElementById('violationMsg');

// Cek apakah browser saat ini dalam mode fullscreen
function isFSActive() {
    return Boolean(
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
    );
}

// Masuk ke mode fullscreen
function triggerEnterFS() {
    if (ended) return;
    const el = document.documentElement;
    const requestMethod = el.requestFullscreen ||
                          el.webkitRequestFullscreen ||
                          el.mozRequestFullScreen ||
                          el.msRequestFullscreen;

    if (requestMethod) {
        try {
            const p = requestMethod.call(el);
            if (p && p.then) {
                p.then(() => {
                    syncFSState();
                }).catch(() => {
                    syncFSState();
                });
            } else {
                syncFSState();
            }
        } catch (_) {
            syncFSState();
        }
    } else {
        syncFSState();
    }
}
window.triggerEnterFS = triggerEnterFS;
window.requestFS      = triggerEnterFS;

// Keluar dari mode fullscreen saat ujian selesai
function exitFS() {
    try {
        if (isFSActive()) {
            const exitMethod = document.exitFullscreen ||
                               document.webkitExitFullscreen ||
                               document.mozCancelFullScreen ||
                               document.msExitFullscreen;
            if (exitMethod) {
                const p = exitMethod.call(document);
                if (p && p.catch) p.catch(() => {});
            }
        }
    } catch (_) {}
}

// Sinkronisasi status tampilan & kunci layar jika fullscreen mati
function syncFSState() {
    if (ended) {
        if (fsLockOverlay) fsLockOverlay.classList.add('hide');
        return;
    }

    if (isFSActive()) {
        if (fsLockOverlay) fsLockOverlay.classList.add('hide');
    } else {
        if (fsLockOverlay) {
            fsLockOverlay.classList.remove('hide');
            if (fsLockTitle) fsLockTitle.textContent = 'Layar Penuh Terhenti!';
            if (fsLockDesc) {
                fsLockDesc.innerHTML = 'Anda harus berada dalam <b>mode layar penuh</b> selama ujian berlangsung.<br><span class="text-danger">Membuka tab, jendela, atau aplikasi lain dilarang dan dicatat sebagai pelanggaran.</span>';
            }
            if (btnEnterFS) btnEnterFS.innerHTML = '<span>⛶</span> Masuk Kembali ke Layar Penuh';
        }
    }
}

// Pantau perubahan mode fullscreen
['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
    document.addEventListener(evt, () => {
        if (ended) return;
        syncFSState();
        if (!isFSActive()) {
            reportViolation('Anda keluar dari mode layar penuh');
        }
    });
});

// Auto-request / cek fullscreen saat halaman siap
window.addEventListener('load', () => {
    setTimeout(() => {
        triggerEnterFS();
        syncFSState();
    }, 400);
});

// ── Timer Pengerjaan ──────────────────────────────────────────────────────
const timerBox = document.getElementById('timerBox');
const timerVal = document.getElementById('timerVal');

function pad(n) { return String(n).padStart(2, '0'); }

function tickTimer() {
    if (ended) return;
    const sec = Math.max(0, Math.floor((DEADLINE - Date.now()) / 1000));
    const m = Math.floor(sec / 60), s = sec % 60;
    if (timerVal) timerVal.textContent = pad(m) + ':' + pad(s);

    if (timerBox) {
        timerBox.classList.remove('warning', 'danger');
        if (sec <= 60)       timerBox.classList.add('danger');
        else if (sec <= 300) timerBox.classList.add('warning');
    }

    if (sec <= 0 && !ended) {
        ended = true;
        exitFS();
        document.getElementById('finishForm').submit();
    }
}
setInterval(tickTimer, 1000);
tickTimer();

// ── Autosave Jawaban ──────────────────────────────────────────────────────
const saveStatus = document.getElementById('saveStatus');

async function saveAnswer(qid, val) {
    if (ended) return;
    if (saveStatus) saveStatus.innerHTML = '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--apple-orange);margin-right:5px;"></span>Menyimpan jawaban...';
    try {
        const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ question_id: qid, answer: val }),
        });
        const d = await res.json();
        if (saveStatus) {
            saveStatus.innerHTML = res.ok
                ? '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--apple-green);margin-right:5px;"></span>Tersimpan otomatis ' + d.saved_at
                : '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--apple-red);margin-right:5px;"></span>Gagal menyimpan';
        }
    } catch {
        if (saveStatus) saveStatus.innerHTML = '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--apple-red);margin-right:5px;"></span>Koneksi terputus';
    }
}

// Jawaban PG & Benar/Salah
document.querySelectorAll('.answer-radio').forEach(el => {
    el.addEventListener('change', () => {
        document.querySelectorAll(`[name="${el.name}"]`).forEach(r => {
            r.closest('.option-label').classList.toggle('selected', r === el);
        });
        const qid = Number(el.dataset.qid);
        answeredMap.set(qid, true);
        updatePaletteView();
        saveAnswer(qid, el.value);
    });
});

// Jawaban PG_MULTI (Pilihan Ganda Kompleks - Checkbox)
document.querySelectorAll('.answer-checkbox').forEach(el => {
    el.addEventListener('change', () => {
        el.closest('.option-label').classList.toggle('selected', el.checked);
        const qid = Number(el.dataset.qid);
        const checkedVals = [];
        document.querySelectorAll(`.answer-checkbox[data-qid="${qid}"]:checked`).forEach(cb => {
            checkedVals.push(cb.value);
        });
        checkedVals.sort();
        const combined = checkedVals.join(',');
        if (checkedVals.length > 0) {
            answeredMap.set(qid, true);
            saveAnswer(qid, combined);
        } else {
            answeredMap.delete(qid);
            saveAnswer(qid, '');
        }
        updatePaletteView();
    });
});

// Jawaban Menjodohkan
document.querySelectorAll('.match-select').forEach(sel => {
    sel.addEventListener('change', () => {
        const qid = Number(sel.dataset.qid);
        const parts = [];
        document.querySelectorAll(`.match-select[data-qid="${qid}"]`).forEach(s => {
            if (s.value) parts.push(s.dataset.premise + ':' + s.value);
        });
        const combined = parts.join(',');
        const hidden = document.getElementById('match_hidden_' + qid);
        if (hidden) hidden.value = combined;
        if (parts.length > 0) {
            answeredMap.set(qid, true);
            saveAnswer(qid, combined);
        } else {
            answeredMap.delete(qid);
        }
        updatePaletteView();
    });
});

// ── Selesai Ujian & Kembali ke Tampilan Normal ─────────────────────────────
function confirmSubmit() {
    let answeredCount = 0;
    answeredMap.forEach((v) => { if (v) answeredCount++; });
    const remaining = totalQuestions - answeredCount;

    let msg = 'Kumpulkan jawaban sekarang? Sesi ujian akan berakhir dan tampilan browser akan kembali normal.';
    if (remaining > 0) {
        msg = `⚠️ Perhatian!\nMasih ada ${remaining} soal yang BELUM dijawab dari total ${totalQuestions} soal.\n\nYakin ingin mengumpulkan jawaban sekarang? Sesi ujian akan berakhir.`;
    }

    if (confirm(msg)) {
        ended = true;
        exitFS();
        try { localStorage.removeItem(doubtfulKey); } catch (_) {}
        try { sessionStorage.removeItem('cbt_active_q_' + attemptId); } catch (_) {}
        const b = document.getElementById('btnSubmitPage');
        if (b) b.disabled = true;
        const bm = document.getElementById('btnSubmitModal');
        if (bm) bm.disabled = true;
        document.getElementById('finishForm').submit();
    }
}
window.confirmSubmit = confirmSubmit;

// ── Anti-Cheat: Pelaporan Pelanggaran ──────────────────────────────────────
async function reportViolation(reason) {
    if (ended) return;
    try {
        const res = await fetch(VIOLATION_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason: reason }),
        });
        const d = await res.json();
        violations = d.violations;

        if (vBar && vMsg) {
            vBar.style.display = 'flex';
            vMsg.textContent = `Pelanggaran ke-${violations}: ${reason}. Sisa toleransi: ${d.remaining} kali.`;
            clearTimeout(vBar._t);
            vBar._t = setTimeout(() => vBar.style.display = 'none', 6000);
        }

        if (d.force_submit) {
            ended = true;
            exitFS();
            if (overlay) overlay.classList.remove('show');
            alert('⛔ Jawaban dikumpulkan paksa karena batas pelanggaran telah tercapai.');
            window.location.href = RESULT_URL;
            return;
        }

        if (warnMsg && warnCount && overlay) {
            warnMsg.textContent = reason;
            warnCount.textContent = `Pelanggaran: ${violations} / ${d.max}`;
            overlay.classList.add('show');
        }
    } catch { /* abaikan jika jaringan terputus sementara */ }
}

if (document.getElementById('warnContinue')) {
    document.getElementById('warnContinue').addEventListener('click', () => {
        if (overlay) overlay.classList.remove('show');
        triggerEnterFS();
    });
}

// ── Kunci Navigasi: Cegah Membuka Halaman Lain / Tab Lain ──────────────────

// 1. Jebak tombol Back / Forward browser
history.pushState(null, null, location.href);
window.addEventListener('popstate', function () {
    if (!ended) {
        history.pushState(null, null, location.href);
        reportViolation('Mencoba menggunakan tombol navigasi browser');
        triggerEnterFS();
    }
});

// 2. Peringatan sebelum reload atau menutup tab
window.addEventListener('beforeunload', function (e) {
    if (!ended) {
        e.preventDefault();
        e.returnValue = 'Ujian sedang berlangsung! Jangan tinggalkan halaman ini.';
        return e.returnValue;
    }
});

// 3. Blokir semua kombinasi tombol keyboard berbahaya
window.addEventListener('keydown', function (e) {
    if (ended) return;

    // F12 (DevTools)
    if (e.key === 'F12') {
        e.preventDefault();
        e.stopPropagation();
        reportViolation('Mencoba membuka Developer Tools (F12)');
        return false;
    }

    // F11 (Fullscreen bawaan browser)
    if (e.key === 'F11') {
        e.preventDefault();
        e.stopPropagation();
        triggerEnterFS();
        return false;
    }

    // F5 / Ctrl+R / Ctrl+Shift+R (Reload)
    if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'))) {
        e.preventDefault();
        e.stopPropagation();
        reportViolation('Mencoba me-refresh halaman ujian');
        return false;
    }

    // Alt + Tab, Alt + F4, Alt + Panah
    if (e.altKey) {
        e.preventDefault();
        e.stopPropagation();
        reportViolation('Mencoba berpindah jendela/aplikasi (kombinasi Alt)');
        triggerEnterFS();
        return false;
    }

    // Ctrl / Cmd Shortcuts
    if (e.ctrlKey || e.metaKey) {
        const k = e.key.toLowerCase();
        if (['n', 't', 'w', 'q', 'h', 'j', 'd', 'p', 'u', 's', 'o', 'l'].includes(k)) {
            e.preventDefault();
            e.stopPropagation();
            reportViolation(`Mencoba membuka tab/fungsi browser (Ctrl+${k.toUpperCase()})`);
            return false;
        }

        // DevTools: Ctrl+Shift+I, J, C
        if (e.shiftKey && ['i', 'j', 'c'].includes(k)) {
            e.preventDefault();
            e.stopPropagation();
            reportViolation('Mencoba membuka Developer Tools');
            return false;
        }
    }

    // PrintScreen
    if (e.key === 'PrintScreen') {
        e.preventDefault();
        try { navigator.clipboard.writeText(''); } catch (_) {}
        reportViolation('Mencoba mengambil screenshot (PrintScreen)');
        return false;
    }
}, true);

// Deteksi PrintScreen pada keyup
document.addEventListener('keyup', e => {
    if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
        try { navigator.clipboard.writeText(''); } catch (_) {}
        reportViolation('Mencoba mengambil screenshot (PrintScreen)');
    }
});

// 4. Deteksi perpindahan tab (Page Visibility API)
let firstVisible = true;
document.addEventListener('visibilitychange', () => {
    if (document.hidden && !ended) {
        if (firstVisible) { firstVisible = false; return; }
        reportViolation('Anda meninggalkan halaman ujian (berpindah tab/aplikasi)');
        syncFSState();
    }
});

// 5. Deteksi kehilangan fokus jendela (Window Blur / Alt-Tab)
let blurTimer;
window.addEventListener('blur', () => {
    if (ended) return;
    blurTimer = setTimeout(() => {
        reportViolation('Anda berpindah aplikasi atau kehilangan fokus layar ujian');
        syncFSState();
    }, 450);
});
window.addEventListener('focus', () => {
    clearTimeout(blurTimer);
    syncFSState();
});

// 6. Blokir klik kanan, copy, paste, select, dan drag
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('copy', e => e.preventDefault());
document.addEventListener('cut', e => e.preventDefault());
document.addEventListener('paste', e => e.preventDefault());
document.addEventListener('selectstart', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());

// 7. Cegah print dialog
window.addEventListener('beforeprint', () => {
    reportViolation('Mencoba mencetak halaman ujian');
});

// ── Image Zoom ────────────────────────────────────────────────────────────
function expandImg(img) {
    img.style.maxHeight = img.style.maxHeight === 'none' ? '280px' : 'none';
    img.style.cursor    = img.style.maxHeight === 'none' ? 'zoom-out' : 'zoom-in';
}
window.expandImg = expandImg;
</script>
@endpush
