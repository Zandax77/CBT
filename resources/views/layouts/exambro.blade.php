<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f5f7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $school->school_name ?: 'CBT Sekolah' }}">
    <meta name="mobile-web-app-capable" content="yes">
    {{-- Data siswa untuk watermark anti-screenshot --}}
    <meta name="student-name" content="{{ auth()->user()->name ?? '' }}">
    <meta name="student-user" content="{{ auth()->user()->username ?? auth()->user()->name ?? '' }}">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">

    {{-- iOS icons --}}
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="apple-touch-startup-image" href="/icons/icon-512.png">

    <title>@yield('title', $school->school_name ?: 'CBT Sekolah')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/liquid-glass.css" rel="stylesheet">

    <style>
        * { -webkit-tap-highlight-color: transparent; }

        html, body {
            height: 100%;
            overscroll-behavior: none;
        }

        body {
            user-select: none;
            -webkit-user-select: none;
        }

        /* ── Fullscreen Gateway & Lockdown Overlay ── */
        #fsLockOverlay {
            position: fixed; inset: 0; z-index: 10000;
            background: rgba(245, 245, 247, 0.88);
            backdrop-filter: blur(36px) saturate(200%);
            -webkit-backdrop-filter: blur(36px) saturate(200%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #fsLockOverlay.hide {
            display: none !important;
        }
        #fsLockCard {
            max-width: 480px;
            width: 100%;
            border-radius: 28px !important;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.15), inset 0 1px 1px #fff !important;
        }
        .fs-lock-icon {
            font-size: 3.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 82px;
            height: 82px;
            border-radius: 24px;
            background: rgba(0, 113, 227, 0.12);
            color: var(--apple-blue);
            margin: 0 auto;
            border: 1px solid rgba(0, 113, 227, 0.2);
        }

        /* ── Violation bar ── */
        #violationBar {
            background: rgba(255, 149, 0, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 8px 18px;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(255, 149, 0, 0.25);
            position: sticky;
            top: 60px;
            z-index: 99;
        }

        /* ── Warning overlay ── */
        #warnOverlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        #warnOverlay.show { display: flex; }
        #warnCard {
            padding: 32px 28px;
            max-width: 380px;
            width: 100%;
            text-align: center;
            border-radius: 24px !important;
        }
        #warnCard .warn-icon { font-size: 3.2rem; }
        #warnCard h4 { color: var(--apple-red); margin: 12px 0 8px; font-weight: 700; }

        .q-img {
            max-width: 100%;
            max-height: 280px;
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            cursor: zoom-in;
            margin: 12px 0;
            display: block;
        }

        /* ── Autosave status ── */
        #saveStatus {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--apple-text-secondary);
            padding: 6px 0;
            letter-spacing: -0.01em;
        }

        /* ── Screenshot watermark ── */
        #watermark {
            position: fixed; inset: 0; z-index: 8000;
            pointer-events: none;
            overflow: hidden;
        }
        #watermark span {
            position: absolute;
            font-size: .65rem;
            font-weight: 700;
            color: rgba(0,0,0,.06);
            white-space: nowrap;
            letter-spacing: .05em;
            transform: rotate(-30deg);
            transform-origin: center;
            user-select: none;
            -webkit-user-select: none;
        }

        /* ── Blok print/screenshot via browser ── */
        @media print {
            html, body { display: none !important; }
        }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* ── Safe area (iOS notch) ── */
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body>

{{-- Ambient Liquid Glow Aura --}}
<div class="liquid-glow-container" aria-hidden="true">
    <div class="liquid-glow-orb liquid-glow-orb-1" style="opacity: 0.45;"></div>
    <div class="liquid-glow-orb liquid-glow-orb-2" style="opacity: 0.40;"></div>
    <div class="liquid-glow-orb liquid-glow-orb-3" style="opacity: 0.35;"></div>
</div>

{{-- ── Fullscreen Gateway & Lockdown Overlay ── --}}
<div id="fsLockOverlay">
    <div id="fsLockCard" class="card glass-card text-center p-4 p-md-5">
        <div class="fs-lock-icon mb-3">🛡️</div>
        <h3 class="fw-bold mb-2" id="fsLockTitle">Mode Ujian Layar Penuh</h3>
        <p class="text-secondary small mb-4" id="fsLockDesc">
            Sistem CBT mewajibkan mode Layar Penuh Terkunci (Exambro). Anda tidak diperkenankan membuka tab, jendela browser, atau aplikasi lain selama ujian berlangsung.
        </p>
        <button id="btnEnterFS" type="button" class="btn btn-primary py-3 px-4 fs-6 w-100 fw-bold shadow-lg" onclick="triggerEnterFS()">
            <span>⛶</span> Masuk Layar Penuh &amp; Mulai Ujian
        </button>
    </div>
</div>

{{-- ── Watermark nama siswa (identifikasi screenshot) ── --}}
<div id="watermark"></div>

{{-- ── Warning overlay (ganti tab/keluar fullscreen) ── --}}
<div id="warnOverlay">
    <div id="warnCard" class="card glass-card">
        <div class="warn-icon">⚠️</div>
        <h4>Peringatan!</h4>
        <p id="warnMsg" class="text-secondary small mb-3">Anda terdeteksi meninggalkan halaman ujian.</p>
        <div id="warnCount" class="fw-bold mb-3" style="color:var(--apple-red); font-size: 1.1rem;"></div>
        <button id="warnContinue" class="btn btn-primary w-100" onclick="triggerEnterFS()">Kembali ke Ujian</button>
    </div>
</div>

{{-- ── Top exam bar (Apple VisionOS / macOS Frosted Glass HUD) ── --}}
<div id="exambar">
    <div class="d-flex align-items-center gap-2" style="min-width:0">
        @include('partials.school-brand', ['asLink' => false])
        <div style="min-width:0; border-left: 1px solid rgba(0,0,0,0.1); padding-left: 10px; margin-left: 6px;">
            <div class="exam-title">@yield('exam-title','Ujian')</div>
            <div class="exam-info">@yield('exam-info','')</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button id="fsBtn" onclick="triggerEnterFS()" title="Layar Penuh">⛶ Layar Penuh</button>
        <div id="timerBox">
            <div class="timer-label">Sisa Waktu</div>
            <div class="timer-value" id="timerVal">--:--</div>
        </div>
    </div>
</div>

{{-- ── Violation bar ── --}}
<div id="violationBar">
    <span>🚨</span>
    <span id="violationMsg">Pelanggaran terdeteksi</span>
</div>

{{-- ── Autosave status ── --}}
<div id="saveStatus">
    <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--apple-green); margin-right:5px;"></span>
    Autosave aktif
</div>

{{-- ── Konten utama ── --}}
<div class="container-fluid px-3 py-3" style="max-width:760px;margin:0 auto">
    @yield('content')
</div>

<div id="submitArea" class="safe-bottom">
    @yield('submit-btn')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Register PWA service worker --}}
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}
</script>

{{-- Watermark dinamis: nama siswa tampil di seluruh halaman agar identifikasi screenshot mudah --}}
<script>
(function () {
    const wm    = document.getElementById('watermark');
    if (!wm) return;
    const name  = document.querySelector('meta[name="student-name"]')?.content ?? '';
    const user  = document.querySelector('meta[name="student-user"]')?.content ?? '';
    const label = name ? `${name} (${user})` : user;
    if (!label) return;

    const W = window.innerWidth, H = window.innerHeight;
    const cols = Math.ceil(W / 260) + 1;
    const rows = Math.ceil(H / 120) + 1;

    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const span = document.createElement('span');
            span.textContent = label;
            span.style.left = (c * 260 - 60) + 'px';
            span.style.top  = (r * 120 - 20) + 'px';
            wm.appendChild(span);
        }
    }
})();
</script>

@stack('scripts')
</body>
</html>
