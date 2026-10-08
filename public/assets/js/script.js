const initializePage = () => {
    const app = document.querySelector('.app');
    const loadbar = document.querySelector('[data-navigation-loadbar]');
    const connection = navigator.connection ?? navigator.mozConnection ?? navigator.webkitConnection;

    if (loadbar) {
        let progressTimer = null;
        let loadingStartedAt = 0;
        const setProgress = (progress) => {
            loadbar.style.setProperty('--navigation-progress', `${progress}%`);
        };
        const beginLoading = () => {
            window.clearInterval(progressTimer);
            loadbar.classList.remove('is-complete');
            loadbar.classList.add('is-loading', 'is-visible');
            loadingStartedAt = performance.now();
            setProgress(8);
            progressTimer = window.setInterval(() => {
                const current = Number.parseFloat(loadbar.style.getPropertyValue('--navigation-progress')) || 8;
                setProgress(Math.min(90, current + Math.max(1, (90 - current) * 0.08)));
            }, 120);
        };
        const finishLoading = () => {
            const finish = () => {
                window.clearInterval(progressTimer);
                setProgress(100);
                loadbar.classList.remove('is-loading');
                loadbar.classList.add('is-complete');
                window.setTimeout(() => {
                    loadbar.classList.remove('is-visible', 'is-complete');
                    setProgress(0);
                }, 260);
            };
            window.setTimeout(finish, Math.max(0, 180 - (performance.now() - loadingStartedAt)));
        };

        const navigateInternally = (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;

            const destination = new URL(link.href, location.href);
            if (destination.origin !== location.origin) return;
            if (destination.pathname === location.pathname && destination.search === location.search) return;
            beginLoading();
        };

        beginLoading();
        document.addEventListener('click', navigateInternally, true);
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) finishLoading();
        });
        if (navigator.onLine === false) loadbar.classList.add('is-offline');
        window.addEventListener('online', () => {
            loadbar.classList.remove('is-offline');
        });
        window.addEventListener('offline', () => loadbar.classList.add('is-offline'));

        if (document.readyState === 'complete') {
            loadbar.classList.add('is-complete');
            finishLoading();
        } else {
            window.addEventListener('load', finishLoading, { once: true });
        }
    }

    const mobileNavMore = document.querySelector('[data-mobile-nav-more]');
    if (mobileNavMore) {
        const mobileViewport = window.matchMedia('(max-width: 992px)');
        const syncMoreMenu = () => {
            mobileNavMore.open = !mobileViewport.matches;
        };

        syncMoreMenu();
        document.addEventListener('pointerdown', (event) => {
            if (mobileViewport.matches && mobileNavMore.open && !mobileNavMore.contains(event.target)) {
                mobileNavMore.open = false;
            }
        });
        document.addEventListener('keydown', (event) => {
            if (mobileViewport.matches && mobileNavMore.open && event.key === 'Escape') {
                mobileNavMore.open = false;
                mobileNavMore.querySelector('summary')?.focus();
            }
        });
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

    const developerTools = document.querySelector('[data-devtools-live]');
    if (developerTools) {
        const updatedLabel = developerTools.querySelector('[data-live-updated]');
        const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
        let metricsRequest = null;
        const formatBytes = (bytes) => {
            if (!Number.isFinite(bytes)) return 'Tidak tersedia';
            const units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
            let value = bytes;
            let unit = 0;
            while (value >= 1024 && unit < units.length - 1) {
                value /= 1024;
                unit++;
            }
            return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: unit === 0 ? 0 : 1 }).format(value)} ${units[unit]}`;
        };
        const refreshDeveloperMetrics = async () => {
            if (metricsRequest) return;
            metricsRequest = (async () => {
                const response = await fetch(developerTools.dataset.metricsUrl, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('metrics_unavailable');
                const metrics = await response.json();

                developerTools.querySelectorAll('[data-live-metric]').forEach((element) => {
                    const key = element.dataset.liveMetric;
                    const isQueueMetric = key.startsWith('queue_');
                    const value = isQueueMetric ? metrics.queue[key.slice(6)] : metrics.current[key];
                    if (value === null || value === undefined) {
                        element.textContent = 'Tidak tersedia';
                    } else if (element.dataset.liveFormat === 'bytes') {
                        element.textContent = formatBytes(Number(value));
                    } else if (element.dataset.liveFormat === 'milliseconds') {
                        element.textContent = `${numberFormat.format(Number(value))} ms`;
                    } else {
                        element.textContent = numberFormat.format(Number(value));
                    }
                });

                const diskFree = Number(metrics.current.disk_free_bytes);
                const diskTotal = Number(metrics.current.disk_total_bytes);
                const diskUsed = developerTools.querySelector('[data-live-disk-used]');
                if (diskUsed) {
                    diskUsed.textContent = diskTotal > 0 && Number.isFinite(diskFree)
                        ? `${Math.max(0, Math.min(100, Math.round((1 - diskFree / diskTotal) * 100)))}% terpakai.`
                        : 'Kapasitas volume tidak tersedia.';
                }
                if (updatedLabel) {
                    updatedLabel.textContent = `Diperbarui ${new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date())}`;
                }
            })().catch(() => {
                if (updatedLabel) updatedLabel.textContent = 'Pembaruan metrik tertunda';
            }).finally(() => {
                metricsRequest = null;
            });
            await metricsRequest;
        };

        refreshDeveloperMetrics();
        window.setInterval(() => {
            if (!document.hidden) refreshDeveloperMetrics();
        }, 15000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshDeveloperMetrics();
        });
    }

    document.querySelectorAll('[data-confirm-clear-app-log]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Bersihkan isi app.log? Change log dan riwayat sesi tetap tersimpan.')) {
                event.preventDefault();
            }
        });
    });

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
            document.body.classList.add('modal-open');

            const closeModal = () => {
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
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