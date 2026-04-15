<div class="profile-menu" id="profileMenu">
    <button type="button" class="profile-trigger" id="profileTrigger" aria-haspopup="true" aria-expanded="false">
        <span class="profile-avatar"><?= e(strtoupper(substr((string) $username, 0, 1))) ?></span>
        <span class="profile-meta">
            <strong><?= e((string) $username) ?></strong>
            <small><?= e((string) $role) ?></small>
        </span>
        <svg class="profile-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </button>

    <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-header">
            <strong><?= e((string) $username) ?></strong>
            <small><?= e((string) $role) ?></small>
        </div>
        <a class="profile-link" href="<?= e(base_url('/profile')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Lihat Profile
        </a>
        <a class="profile-link" href="<?= e(base_url('/dashboard')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            Pengaturan
        </a>
        <form
            class="profile-logout-form"
            action="<?= e(base_url('/logout')) ?>"
            method="POST"
            style="margin-top: 8px; border-top: 1px solid var(--line); padding-top: 8px;"
            data-confirm-dialog
            data-confirm-title="Akhiri Sesi Login"
            data-confirm-message="Anda akan keluar dari panel sistem dan harus login kembali untuk mengakses dashboard. Lanjutkan logout?"
            data-confirm-badge="Konfirmasi Logout"
            data-confirm-action-label="Ya, Keluar"
            data-confirm-cancel-label="Tetap di Sini"
            data-confirm-type="neutral"
        >
            <?= csrf_field() ?>
            <button class="profile-link profile-link-danger" type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>
