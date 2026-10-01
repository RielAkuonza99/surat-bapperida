const initializePage = () => {
    const body = document.body;
    const loader = document.getElementById('page-loader');

    if (loader) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                loader.classList.add('hidden');
                body.classList.add('loaded');
                setTimeout(() => loader.remove(), 400);
            }, 250);
        });
    }

    const navToggle = document.querySelector('[data-nav-toggle]');
    const sidebar = navToggle?.closest('.sidebar');
    const primaryNavigation = document.getElementById(navToggle?.getAttribute('aria-controls') ?? '');

    if (navToggle && sidebar && primaryNavigation) {
        const setMenuOpen = (isOpen) => {
            sidebar.classList.toggle('menu-open', isOpen);
            navToggle.setAttribute('aria-expanded', String(isOpen));
            navToggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
            navToggle.setAttribute('title', isOpen ? 'Tutup menu' : 'Buka menu');
        };

        body.classList.add('menu-toggle-ready');
        navToggle.addEventListener('click', () => {
            setMenuOpen(navToggle.getAttribute('aria-expanded') !== 'true');
        });

        primaryNavigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenuOpen(false));
        });

        document.addEventListener('click', (event) => {
            if (!sidebar.contains(event.target)) setMenuOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
                setMenuOpen(false);
                navToggle.focus();
            }
        });

        window.matchMedia('(min-width: 577px)').addEventListener('change', (event) => {
            if (event.matches) setMenuOpen(false);
        });
    }

    document.querySelectorAll('[data-confirm-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const modal = document.getElementById('confirm-modal');
            const confirmText = document.getElementById('confirm-message');
            const confirmBtn = document.getElementById('confirm-delete-btn');
            const cancelBtn = document.getElementById('cancel-delete-btn');
            if (!modal || !confirmText || !confirmBtn || !cancelBtn) {
                if (window.confirm('Yakin ingin menghapus surat ini?')) {
                    HTMLFormElement.prototype.submit.call(form);
                }
                return;
            }

            confirmText.textContent = 'Yakin ingin menghapus surat ini?';
            modal.classList.add('show');
            body.classList.add('modal-open');

            const closeModal = () => {
                modal.classList.remove('show');
                body.classList.remove('modal-open');
            };

            confirmBtn.onclick = () => {
                closeModal();
                HTMLFormElement.prototype.submit.call(form);
            };

            cancelBtn.onclick = closeModal;
            modal.onclick = (e) => {
                if (e.target === modal) closeModal();
            };
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage, { once: true });
} else {
    initializePage();
}