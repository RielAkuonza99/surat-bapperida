const initializePage = () => {
    const body = document.body;
    const app = document.querySelector('.app');
    const loadbar = document.querySelector('[data-navigation-loadbar]');
    const connection = navigator.connection ?? navigator.mozConnection ?? navigator.webkitConnection;

    if (loadbar) {
        const updateLoadTiming = () => {
            const roundTrip = Number(connection?.rtt);
            const cycle = Number.isFinite(roundTrip) && roundTrip > 0
                ? Math.min(2200, Math.max(500, roundTrip * 2))
                : 1000;
            loadbar.style.setProperty('--network-load-cycle', `${cycle}ms`);
        };
        const finishLoading = () => {
            loadbar.classList.add('is-complete');
            window.setTimeout(() => loadbar.remove(), 220);
        };

        updateLoadTiming();
        connection?.addEventListener?.('change', updateLoadTiming);
        if (navigator.onLine === false) loadbar.classList.add('is-offline');
        window.addEventListener('online', () => {
            loadbar.classList.remove('is-offline');
            updateLoadTiming();
        });
        window.addEventListener('offline', () => loadbar.classList.add('is-offline'));

        if (document.readyState === 'complete') {
            finishLoading();
        } else {
            window.addEventListener('load', finishLoading, { once: true });
        }
    }

    const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
    const mobileBackdrop = document.querySelector('[data-mobile-nav-backdrop]');
    const primaryNavigation = document.getElementById(mobileToggle?.getAttribute('aria-controls') ?? '');
    const sidebar = document.querySelector('.sidebar');

    if (app && sidebar && mobileToggle && mobileBackdrop && primaryNavigation) {
        const mobileViewport = window.matchMedia('(max-width: 992px)');
        const setMenuOpen = (isOpen) => {
            app.classList.toggle('mobile-nav-open', isOpen);
            body.classList.toggle('mobile-nav-open', isOpen);
            mobileBackdrop.hidden = !isOpen;
            mobileToggle.setAttribute('aria-expanded', String(isOpen));
            mobileToggle.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
            mobileToggle.setAttribute('title', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
            sidebar.inert = mobileViewport.matches && !isOpen;
            sidebar.setAttribute('aria-hidden', String(mobileViewport.matches && !isOpen));
        };

        setMenuOpen(false);
        mobileToggle.addEventListener('click', () => {
            const shouldOpen = mobileToggle.getAttribute('aria-expanded') !== 'true';
            setMenuOpen(shouldOpen);
            if (shouldOpen) primaryNavigation.querySelector('a')?.focus();
            else mobileToggle.focus();
        });
        mobileBackdrop.addEventListener('click', () => {
            setMenuOpen(false);
            mobileToggle.focus();
        });
        primaryNavigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenuOpen(false));
        });
        document.addEventListener('keydown', (event) => {
            if (mobileToggle.getAttribute('aria-expanded') !== 'true') return;
            if (event.key === 'Escape') {
                setMenuOpen(false);
                mobileToggle.focus();
                return;
            }
            if (event.key === 'Tab') {
                const links = [...primaryNavigation.querySelectorAll('a[href]')];
                if (links.length === 0) return;
                const first = links[0];
                const last = links[links.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
        mobileViewport.addEventListener('change', () => setMenuOpen(false));
    }

    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    if (app && sidebarToggle) {
        const updateSidebarControl = () => {
            const isOpen = !app.classList.contains('sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', String(isOpen));
            sidebarToggle.setAttribute('aria-label', isOpen ? 'Tutup navigasi samping' : 'Buka navigasi samping');
            sidebarToggle.setAttribute('title', isOpen ? 'Tutup navigasi samping' : 'Buka navigasi samping');
        };
        updateSidebarControl();
        sidebarToggle.addEventListener('click', () => {
            app.classList.toggle('sidebar-collapsed');
            updateSidebarControl();
            const secureCookie = location.protocol === 'https:' ? '; Secure' : '';
            const collapsed = app.classList.contains('sidebar-collapsed') ? '1' : '0';
            document.cookie = `bapperida_sidebar_collapsed=${collapsed}; path=/; SameSite=Lax${secureCookie}`;
        });
    }

    const settingsMenu = document.querySelector('.topbar-settings');
    if (settingsMenu) {
        document.addEventListener('click', (event) => {
            if (!settingsMenu.contains(event.target)) settingsMenu.open = false;
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && settingsMenu.open) {
                settingsMenu.open = false;
                settingsMenu.querySelector('summary')?.focus();
            }
        });
    }

    const networkState = document.querySelector('[data-network-state]');
    const networkType = document.querySelector('[data-network-type]');
    const networkRtt = document.querySelector('[data-network-rtt]');
    const updateNetworkReadings = () => {
        if (networkState) networkState.textContent = navigator.onLine ? 'Online menurut browser' : 'Offline menurut browser';
        if (networkType) {
            const type = connection?.effectiveType;
            const downlink = Number(connection?.downlink);
            const details = [];
            if (typeof type === 'string' && type !== '') details.push(type.toUpperCase());
            if (Number.isFinite(downlink) && downlink > 0) details.push(`${downlink} Mbps`);
            networkType.textContent = details.length ? `${details.join(' · ')} (perkiraan)` : 'Tidak tersedia di browser ini';
        }
        if (networkRtt) {
            const roundTrip = Number(connection?.rtt);
            networkRtt.textContent = Number.isFinite(roundTrip) && roundTrip > 0
                ? `sekitar ${Math.round(roundTrip)} ms`
                : 'Tidak tersedia';
        }
    };
    updateNetworkReadings();
    window.addEventListener('online', updateNetworkReadings);
    window.addEventListener('offline', updateNetworkReadings);
    connection?.addEventListener?.('change', updateNetworkReadings);

    const logoutDialog = document.querySelector('[data-logout-dialog]');
    if (logoutDialog) {
        let logoutTrigger = null;
        document.querySelectorAll('[data-open-logout]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                logoutTrigger = trigger;
                settingsMenu && (settingsMenu.open = false);
                if (typeof logoutDialog.showModal === 'function') {
                    logoutDialog.showModal();
                } else if (window.confirm('Keluar dari aplikasi dan akhiri sesi pada perangkat ini?')) {
                    document.getElementById('logout-form')?.requestSubmit();
                }
            });
        });
        document.querySelector('[data-cancel-logout]')?.addEventListener('click', () => logoutDialog.close());
        logoutDialog.addEventListener('close', () => {
            if (logoutTrigger && settingsMenu) settingsMenu.open = true;
            logoutTrigger?.focus();
        });
        logoutDialog.addEventListener('click', (event) => {
            if (event.target === logoutDialog) logoutDialog.close();
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