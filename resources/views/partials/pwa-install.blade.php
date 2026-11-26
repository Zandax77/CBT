{{-- CBT ExamBro PWA Install Component (Android & iOS Support) --}}

{{-- Floating Install Banner on Mobile (Discreet, Apple Glass Styled) --}}
<div id="pwaInstallBanner" class="pwa-install-banner d-none" aria-hidden="true">
    <div class="d-flex align-items-center gap-3">
        <div class="pwa-app-icon">
            @if($school->logo_url)
                <img src="{{ $school->logo_url }}" alt="App Icon" style="width: 100%; height: 100%; object-fit: contain; border-radius: 10px;">
            @else
                🎓
            @endif
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                {{ $school->school_name ?: 'CBT ExamBro' }}
            </div>
            <div class="text-secondary small text-truncate" style="font-size: 0.78rem;">
                Pasang aplikasi di layar HP (Android &amp; iOS)
            </div>
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <button type="button" id="btnPwaInstallAction" class="btn btn-sm btn-primary px-3 py-1 fw-bold shadow-sm" style="height: 32px; font-size: 0.78rem; border-radius: 9999px;">
                <span>📲</span> Pasang
            </button>
            <button type="button" id="btnPwaDismiss" class="btn btn-sm btn-link text-secondary text-decoration-none p-1" style="font-size: 1.1rem; line-height: 1;" aria-label="Tutup" title="Tutup">
                ✕
            </button>
        </div>
    </div>
</div>

{{-- iOS Safari Step-by-Step Installation Modal Sheet --}}
<div class="modal fade" id="iosPwaModal" tabindex="-1" aria-labelledby="iosPwaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content glass-card border-0 p-4 shadow-lg text-center" style="border-radius: 28px !important; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(28px);">
            <div class="modal-header border-0 p-0 justify-content-end">
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0 pt-1">
                <div class="d-inline-flex align-items-center justify-content-center mb-3 rounded-4" 
                     style="width: 64px; height: 64px; background: rgba(0, 113, 227, 0.1); color: var(--apple-blue); font-size: 2rem;">
                    📲
                </div>
                <h5 class="fw-bold mb-1" id="iosPwaModalLabel">Pasang di iPhone / iPad</h5>
                <p class="text-secondary small mb-4">
                    Ikuti 3 langkah mudah berikut untuk menginstal aplikasi CBT di perangkat iOS Anda:
                </p>

                <div class="text-start d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light bg-opacity-75 border">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">
                            1
                        </div>
                        <div class="small">
                            Ketuk tombol <strong>Bagikan (Share)</strong> 
                            <span class="d-inline-block px-1 bg-white rounded border fw-bold text-primary">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                            </span> 
                            di bilah bawah Safari.
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light bg-opacity-75 border">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">
                            2
                        </div>
                        <div class="small">
                            Gulir ke bawah dan pilih <strong>"Tambah ke Layar Utama"</strong> (Add to Home Screen <span class="fw-bold text-dark">⊞</span>).
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light bg-opacity-75 border">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.8rem;">
                            3
                        </div>
                        <div class="small">
                            Ketuk <strong>"Tambah" (Add)</strong> di sudut kanan atas layar. Selesai! Aplikasi CBT siap dibuka dari layar utama tanpa browser bar.
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary w-100 py-2 fw-semibold" data-bs-dismiss="modal">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.pwa-install-banner {
    position: fixed;
    bottom: 20px;
    left: 16px;
    right: 16px;
    max-width: 440px;
    margin: 0 auto;
    z-index: 1040;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(28px) saturate(190%);
    -webkit-backdrop-filter: blur(28px) saturate(190%);
    border: 1px solid rgba(255, 255, 255, 0.95);
    border-radius: 18px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.14), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    padding: 10px 14px;
    animation: slideUpPwa 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUpPwa {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.pwa-app-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0a84ff, #0071e3);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.25);
    flex-shrink: 0;
    overflow: hidden;
}
@media (display-mode: standalone) {
    .pwa-install-banner, .btn-pwa-install-nav {
        display: none !important;
    }
}
</style>

<script>
(function() {
    // Check if already in standalone mode
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        return; // Already installed as PWA!
    }

    // Detect platform
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    let deferredPrompt = null;
    const banner = document.getElementById('pwaInstallBanner');
    const btnAction = document.getElementById('btnPwaInstallAction');
    const btnDismiss = document.getElementById('btnPwaDismiss');
    const navInstallBtn = document.querySelectorAll('.btn-pwa-install-nav');

    // Dismissal state
    const isDismissed = sessionStorage.getItem('pwa_prompt_dismissed') === 'true';

    // Show banner after brief delay if not dismissed
    function showBanner() {
        if (banner && !isDismissed) {
            banner.classList.remove('d-none');
            banner.setAttribute('aria-hidden', 'false');
        }
        navInstallBtn.forEach(el => el.classList.remove('d-none'));
    }

    // Capture Android/Chrome install event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showBanner();
    });

    // If iOS Safari, we can prompt after user interaction or if not dismissed
    if (isIos && !isDismissed) {
        setTimeout(showBanner, 2000);
    } else {
        // Also show for desktop/mobile browsers after short delay if prompt caught
        setTimeout(() => {
            if (deferredPrompt) showBanner();
        }, 1500);
    }

    // Trigger installation
    function triggerInstall() {
        if (isIos) {
            // Show iOS modal guide
            const modalEl = document.getElementById('iosPwaModal');
            if (modalEl && window.bootstrap) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        } else if (deferredPrompt) {
            // Trigger native Android / Chrome prompt
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    if (banner) banner.classList.add('d-none');
                    navInstallBtn.forEach(el => el.classList.add('d-none'));
                }
                deferredPrompt = null;
            });
        } else {
            // Fallback: alert instructions
            alert('Untuk menginstal aplikasi ini, buka menu browser Anda (titik tiga) lalu pilih "Instal Aplikasi" atau "Tambahkan ke Layar Utama".');
        }
    }

    if (btnAction) {
        btnAction.addEventListener('click', triggerInstall);
    }
    navInstallBtn.forEach(btn => {
        btn.addEventListener('click', triggerInstall);
    });

    if (btnDismiss) {
        btnDismiss.addEventListener('click', () => {
            if (banner) banner.classList.add('d-none');
            sessionStorage.setItem('pwa_prompt_dismissed', 'true');
        });
    }
})();
</script>
