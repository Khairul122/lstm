<aside class="sidebar" id="panelSidebar">
    <div class="brand-wrapper" style="padding: 28px 22px 22px; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <div class="brand-logo">
            <svg viewBox="0 0 24 24">
                <path d="M3 3v18h18"></path>
                <path d="M7 16l4-7 4 4 3-6"></path>
            </svg>
        </div>
        <div class="brand-info">
            <p class="brand">LSTM Pangan</p>
            <p class="brand-sub">Forecasting Stok · Dinas Pangan Lhokseumawe</p>
        </div>
    </div>

    <nav class="nav-menu" style="padding: 22px 16px; flex: 1;">
        <div class="nav-group-title">Menu Utama</div>
        <div class="nav-list">
            <a class="nav-pill" href="<?= e(base_url('/')) ?>" target="_blank" rel="noreferrer">
                <svg viewBox="0 0 24 24">
                    <path d="M3 12l9-9 9 9"></path>
                    <path d="M5 10v10h14V10"></path>
                </svg>
                Landing Page
            </a>
            <a class="nav-pill<?= ($activeNav ?? 'dashboard') === 'dashboard' ? ' is-active' : '' ?>" href="<?= e(base_url('/dashboard')) ?>">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
                Dashboard
            </a>
            <a class="nav-pill<?= ($activeNav ?? '') === 'profile' ? ' is-active' : '' ?>" href="<?= e(base_url('/profile')) ?>">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
            </a>
        </div>

        <div class="nav-group-title" style="margin-top: 20px;">Data Master</div>
        <div class="nav-list">
            <a class="nav-pill<?= ($activeNav ?? '') === 'komoditas' ? ' is-active' : '' ?>" href="<?= e(base_url('/komoditas')) ?>">
                <svg viewBox="0 0 24 24">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Komoditas
            </a>
            <a class="nav-pill<?= ($activeNav ?? '') === 'stok-historis' ? ' is-active' : '' ?>" href="<?= e(base_url('/stok-historis')) ?>">
                <svg viewBox="0 0 24 24">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                Stok Historis
            </a>
        </div>

        <div class="nav-group-title" style="margin-top: 20px;">Machine Learning</div>
        <div class="nav-list">
            <a class="nav-pill<?= ($activeNav ?? '') === 'preprocessing' ? ' is-active' : '' ?>" href="<?= e(base_url('/preprocessing')) ?>">
                <svg viewBox="0 0 24 24">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Preprocessing
            </a>
            <a class="nav-pill<?= ($activeNav ?? '') === 'lstm' ? ' is-active' : '' ?>" href="<?= e(base_url('/lstm')) ?>">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                </svg>
                Training LSTM
            </a>
            <a class="nav-pill<?= ($activeNav ?? '') === 'evaluasi' ? ' is-active' : '' ?>" href="<?= e(base_url('/evaluasi')) ?>">
                <svg viewBox="0 0 24 24">
                    <path d="M4 19h16"></path>
                    <path d="M7 16V8"></path>
                    <path d="M12 16V5"></path>
                    <path d="M17 16v-4"></path>
                </svg>
                Evaluasi Model
            </a>
        </div>
    </nav>

    <div style="padding: 16px; border-top: 1px solid rgba(255,255,255,0.07);">
        <div style="display:flex; align-items:center; gap:8px; padding: 10px 12px; border-radius: 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#2dd4bf" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                <line x1="12" y1="20" x2="12.01" y2="20"></line>
            </svg>
            <span style="font-size:0.7rem; color:rgba(255,255,255,0.4); font-weight:500; line-height:1.4;">LSTM v1.0 · Dinas Pangan<br>Kota Lhokseumawe</span>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
