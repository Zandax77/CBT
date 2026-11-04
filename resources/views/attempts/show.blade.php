@extends('layouts.exambro')

@section('exam-title', $attempt->exam->title)
@section('exam-info', $attempt->exam->subject . ' · ' . $questions->count() . ' soal')

@section('content')

@foreach($questions as $i => $q)
@php
    $isPairing  = $q->type === 'MENJODOHKAN';
    $premises   = $isPairing ? array_diff_key($q->options, ['__pairs' => '']) : $q->options;
    $pairs      = $isPairing ? ($q->options['__pairs'] ?? []) : [];
    $savedAnswer = $answers[$q->id] ?? '';
    $savedMatches = [];
    if ($isPairing && $savedAnswer) {
        foreach (explode(',', $savedAnswer) as $part) {
            [$pk, $pv] = array_pad(explode(':', trim($part)), 2, '');
            $savedMatches[$pk] = $pv;
        }
    }
@endphp

<div class="q-card">
    <div class="q-number">Soal {{ $i + 1 }}
        <span class="ms-2 badge
            @if($q->type==='PG') bg-primary
            @elseif($q->type==='BENAR_SALAH') bg-info
            @else bg-warning @endif" style="font-size:.65rem">
            {{ $q->type === 'PG' ? 'Pilihan Ganda' : ($q->type === 'BENAR_SALAH' ? 'Benar / Salah' : 'Menjodohkan') }}
        </span>
    </div>

    <div class="q-text question-text">{{ $q->question_text }}</div>

    @if($q->image_path)
    <img src="{{ Storage::url($q->image_path) }}" class="q-img"
         alt="Gambar soal {{ $i+1 }}"
         onclick="expandImg(this)">
    @endif

    {{-- Pilihan Ganda & Benar/Salah --}}
    @if(!$isPairing)
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

    {{-- Menjodohkan --}}
    @else
    <div class="mt-3">
        <div class="small text-secondary fw-semibold mb-2">Pasangkan pernyataan di kolom kiri dengan jawaban di kolom kanan:</div>
        @foreach($premises as $premiseKey => $premiseText)
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="flex-grow-1 glass-well py-2 px-3 small">
                <b>{{ $premiseKey }}.</b> {{ $premiseText }}
            </div>
            <select class="form-select form-select-sm match-select"
                    data-qid="{{ $q->id }}"
                    data-premise="{{ $premiseKey }}"
                    style="max-width:180px">
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
</div>
@endforeach

@endsection

@section('submit-btn')
<form id="finishForm" method="post" action="{{ route('attempts.submit', $attempt) }}">
    @csrf
    <button id="btnSubmit" type="button" onclick="confirmSubmit()">
        <span>✓</span> Kumpulkan Jawaban (Selesai Ujian)
    </button>
</form>
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
        saveAnswer(Number(el.dataset.qid), el.value);
    });
});

// Jawaban Menjodohkan
document.querySelectorAll('.match-select').forEach(sel => {
    sel.addEventListener('change', () => {
        const qid = sel.dataset.qid;
        const parts = [];
        document.querySelectorAll(`.match-select[data-qid="${qid}"]`).forEach(s => {
            if (s.value) parts.push(s.dataset.premise + ':' + s.value);
        });
        const combined = parts.join(',');
        const hidden = document.getElementById('match_hidden_' + qid);
        if (hidden) hidden.value = combined;
        if (parts.length) saveAnswer(Number(qid), combined);
    });
});

// ── Selesai Ujian & Kembali ke Tampilan Normal ─────────────────────────────
function confirmSubmit() {
    if (confirm('Kumpulkan jawaban sekarang? Sesi ujian akan berakhir dan tampilan browser akan kembali normal.')) {
        ended = true;
        exitFS();
        document.getElementById('btnSubmit').disabled = true;
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
        // N = new window, T = new tab, W = close tab, Q = quit
        // H = history, J = downloads, D = bookmark, P = print, U = source code
        // S = save, O = open, L = focus address bar
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
