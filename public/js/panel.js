(() => {
    // Profile Dropdown
    const profileMenu = document.getElementById('profileMenu');
    const profileTrigger = document.getElementById('profileTrigger');

    if (profileMenu && profileTrigger) {
        profileTrigger.addEventListener('click', () => {
            const isOpen = profileMenu.classList.toggle('is-open');
            profileTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (!profileMenu.contains(event.target)) {
                profileMenu.classList.remove('is-open');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('panelSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const closeSidebar = () => {
        if(sidebar) sidebar.classList.remove('is-open');
        if(sidebarOverlay) sidebarOverlay.classList.remove('is-active');
    };

    if (mobileToggle && sidebar && sidebarOverlay) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            sidebarOverlay.classList.toggle('is-active');
        });

        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    const initPopup = (overlay) => {
        if (!overlay) {
            return;
        }

        const redirectUrl = overlay.dataset.redirectUrl || '';
        const autoClose = overlay.dataset.autoClose === 'true';
        const closeButtons = overlay.querySelectorAll('[data-popup-close]');
        const confirmButton = overlay.querySelector('[data-popup-confirm]');

        const finishClose = () => {
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
                    targetForm.dataset.confirmBypass = 'true';
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

        if (autoClose) {
            window.setTimeout(closeOverlay, 2200);
        }
    };

    initPopup(document.getElementById('globalFlashPopup'));
    initPopup(document.getElementById('authPopupOverlay'));

    const initConfirmDialogs = () => {
        const openConfirmDialog = (form) => {
            if (document.getElementById('globalConfirmDialog')) {
                return;
            }

            const title = form.dataset.confirmTitle || 'Konfirmasi Aksi';
            const message = form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan aksi ini?';
            const badge = form.dataset.confirmBadge || 'Konfirmasi';
            const actionLabel = form.dataset.confirmActionLabel || 'Ya, Lanjutkan';
            const cancelLabel = form.dataset.confirmCancelLabel || 'Batal';
            const type = form.dataset.confirmType || 'warning';

            const overlay = document.createElement('div');
            overlay.id = 'globalConfirmDialog';
            overlay.className = 'popup-overlay';
            overlay.dataset.autoClose = 'false';
            overlay.innerHTML = `
                <div class="popup-card popup-card-${type} popup-card-dialog">
                    <div class="popup-orb popup-orb-a"></div>
                    <div class="popup-orb popup-orb-b"></div>
                    <button type="button" class="popup-dismiss" data-popup-close aria-label="Tutup popup">×</button>
                    <div class="popup-header-row">
                        <div class="popup-icon popup-icon-${type}" aria-hidden="true">
                            <svg viewBox="0 0 64 64">
                                <path d="M32 10L54 50H10L32 10Z"></path>
                                <path d="M32 24V36"></path>
                                <path d="M32 44H32.01"></path>
                            </svg>
                        </div>
                        <div class="popup-status-stack">
                            <div class="popup-badge popup-badge-${type}">${badge}</div>
                            <div class="popup-progress"><span></span></div>
                        </div>
                    </div>
                    <div class="popup-copy">
                        <h2>${title}</h2>
                        <p>${message}</p>
                    </div>
                    <div class="popup-actions">
                        <button type="button" class="popup-button popup-button-ghost" data-popup-close>${cancelLabel}</button>
                        <button type="button" class="popup-button popup-button-${type}" data-popup-confirm data-popup-form-id="${form.id}">${actionLabel}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            initPopup(overlay);
        };

        let confirmFormCounter = 0;

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.matches('form[data-confirm-dialog]')) {
                return;
            }

            if (form.dataset.confirmBypass === 'true') {
                delete form.dataset.confirmBypass;
                return;
            }

            if (!form.id) {
                confirmFormCounter += 1;
                form.id = `confirmForm${confirmFormCounter}`;
            }

            event.preventDefault();
            event.stopPropagation();
            openConfirmDialog(form);
        }, true);
    };

    initConfirmDialogs();
})();
