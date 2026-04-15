(() => {
    const passwordField = document.getElementById('passwordField');
    const togglePassword = document.getElementById('togglePassword');
    const loginForm = document.getElementById('loginForm');
    const loginSubmit = document.getElementById('loginSubmit');
    const authDialog = document.getElementById('authDialog');
    const dialogClose = document.getElementById('dialogClose');
    const dialogDismiss = document.getElementById('dialogDismiss');

    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', () => {
            const show = passwordField.type === 'password';
            passwordField.type = show ? 'text' : 'password';
            togglePassword.textContent = show ? 'Hide' : 'Show';
            togglePassword.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        });
    }

    if (loginForm && loginSubmit) {
        loginForm.addEventListener('submit', () => {
            loginSubmit.classList.add('is-loading');
            loginSubmit.disabled = true;
            loginSubmit.textContent = 'Memproses...';
        });
    }

    const animatedNodes = document.querySelectorAll('[data-animate]');
    if (animatedNodes.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            }
        }, { threshold: 0.15 });

        animatedNodes.forEach((node) => observer.observe(node));
    }

    if (authDialog && dialogClose) {
        const closeDialog = () => {
            authDialog.style.opacity = '0';
            const card = authDialog.querySelector('.dialog-card');
            if (card) {
                card.style.transform = 'translateY(14px) scale(0.96)';
            }

            window.setTimeout(() => authDialog.remove(), 160);
        };

        dialogClose.addEventListener('click', closeDialog);
        if (dialogDismiss) {
            dialogDismiss.addEventListener('click', closeDialog);
        }

        authDialog.addEventListener('click', (event) => {
            if (event.target === authDialog) {
                closeDialog();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.getElementById('authDialog')) {
                closeDialog();
            }
        });
    }
})();
