<?php

declare(strict_types=1);

$topbarProfilePath = __DIR__ . '/topbar-profile.php';
?>
<header class="topbar">
    <div class="topbar-left-wrapper">
        <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Toggle Sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="8" x2="21" y2="8"></line>
                <line x1="3" y1="16" x2="21" y2="16"></line>
            </svg>
        </button>

        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" placeholder="Cari di sistem pangan..." id="topbarSearch" autocomplete="off">
        </div>
    </div>

    <div class="toolbar-right">
        <button type="button" class="icon-btn" aria-label="Notifikasi" title="Notifikasi sistem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </button>

        <?php require $topbarProfilePath; ?>
    </div>
</header>
