<?php

declare(strict_types=1);

$flashPopup = $flashPopup ?? null;

if (!is_array($flashPopup)) {
    return;
}

$popupType = (string) ($flashPopup['type'] ?? 'neutral');
$popupTitle = (string) ($flashPopup['title'] ?? 'Informasi');
$popupMessage = (string) ($flashPopup['message'] ?? '');
$popupRedirect = (string) ($flashPopup['redirect'] ?? '');
$popupBadge = (string) ($flashPopup['badge'] ?? ($popupType === 'success' ? 'Berhasil' : ($popupType === 'error' ? 'Perhatian' : 'Informasi Sistem')));
$popupActionLabel = (string) ($flashPopup['action_label'] ?? 'Lanjut');
$popupCancelLabel = (string) ($flashPopup['cancel_label'] ?? 'Batal');
$popupMode = (string) ($flashPopup['mode'] ?? 'notice');
$popupFormId = (string) ($flashPopup['form_id'] ?? '');
?>
<div class="popup-overlay" id="globalFlashPopup" data-redirect-url="<?= e($popupRedirect) ?>" data-auto-close="false">
    <div class="popup-card popup-card-<?= e($popupType) ?> popup-card-dialog">
        <div class="popup-orb popup-orb-a"></div>
        <div class="popup-orb popup-orb-b"></div>
        <button type="button" class="popup-dismiss" data-popup-close aria-label="Tutup popup">×</button>
        <div class="popup-header-row">
            <div class="popup-icon popup-icon-<?= e($popupType) ?>" aria-hidden="true">
                <?php if ($popupType === 'success'): ?>
                    <svg viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="24"></circle>
                        <path d="M21 33L28 40L43 25"></path>
                    </svg>
                <?php elseif ($popupType === 'error'): ?>
                    <svg viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="24"></circle>
                        <path d="M32 20V35"></path>
                        <path d="M32 44H32.01"></path>
                    </svg>
                <?php elseif ($popupType === 'warning'): ?>
                    <svg viewBox="0 0 64 64">
                        <path d="M32 10L54 50H10L32 10Z"></path>
                        <path d="M32 24V36"></path>
                        <path d="M32 44H32.01"></path>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="24"></circle>
                        <path d="M32 22V34"></path>
                        <path d="M21 39C24 34 28 32 32 32C36 32 40 34 43 39"></path>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="popup-status-stack">
                <div class="popup-badge popup-badge-<?= e($popupType) ?>"><?= e($popupBadge) ?></div>
                <div class="popup-progress"><span></span></div>
            </div>
        </div>
        <div class="popup-copy">
            <h2><?= e($popupTitle) ?></h2>
            <p><?= nl2br(e($popupMessage)) ?></p>
        </div>
        <div class="popup-actions">
            <?php if ($popupMode === 'confirm'): ?>
                <button type="button" class="popup-button popup-button-ghost" data-popup-close><?= e($popupCancelLabel) ?></button>
                <button type="button" class="popup-button popup-button-<?= e($popupType) ?>" data-popup-confirm data-popup-form-id="<?= e($popupFormId) ?>"><?= e($popupActionLabel) ?></button>
            <?php else: ?>
                <button type="button" class="popup-button popup-button-<?= e($popupType) ?>" data-popup-close><?= e($popupActionLabel) ?></button>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    (() => {
        const overlay = document.getElementById('globalFlashPopup');

        if (!overlay || overlay.dataset.initialized === 'true') {
            return;
        }

        overlay.dataset.initialized = 'true';

        const redirectUrl = overlay.dataset.redirectUrl || '';
        const closeButtons = overlay.querySelectorAll('[data-popup-close]');
        const confirmButton = overlay.querySelector('[data-popup-confirm]');

        const finishClose = () => {
            if (!document.body.contains(overlay)) {
                return;
            }

            overlay.remove();

            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        };

        const closeOverlay = () => {
            overlay.classList.add('is-leaving');
            window.setTimeout(finishClose, 220);
        };

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeOverlay);
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                const formId = confirmButton.dataset.popupFormId || '';
                const targetForm = formId !== '' ? document.getElementById(formId) : null;

                if (targetForm) {
                    HTMLFormElement.prototype.submit.call(targetForm);
                }
            });
        }

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeOverlay();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.body.contains(overlay)) {
                closeOverlay();
            }
        });
    })();
</script>
