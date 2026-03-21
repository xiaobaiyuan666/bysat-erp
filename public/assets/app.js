document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const modals = Array.from(document.querySelectorAll('.modal'));
    const windowStorageKey = `ys-admin-workspace-windows:${window.location.pathname}`;

    const closeAllModals = () => {
        modals.forEach((modal) => modal.classList.remove('is-open'));
        body.classList.remove('modal-open');
    };

    if (modals.some((modal) => modal.classList.contains('is-open'))) {
        body.classList.add('modal-open');
    }

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const targetId = trigger.getAttribute('data-modal-open');
            const modal = document.getElementById(targetId);

            if (!modal) {
                return;
            }

            closeAllModals();
            modal.classList.add('is-open');
            body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            closeAllModals();
        });
    });

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    document.querySelectorAll('[data-fill-target][data-fill-value]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetName = trigger.getAttribute('data-fill-target');
            const target = document.getElementById(targetName)
                || document.querySelector(`[name="${targetName}"]`);

            if (!(target instanceof HTMLInputElement) && !(target instanceof HTMLTextAreaElement)) {
                return;
            }

            target.value = trigger.getAttribute('data-fill-value') || '';
            target.focus();
        });
    });

    const workspaceWindows = Array.from(document.querySelectorAll('[data-window]'));
    const toggles = Array.from(document.querySelectorAll('[data-window-toggle]'));

    if (workspaceWindows.length > 0 && toggles.length > 0) {
        let visibility = {};

        try {
            visibility = JSON.parse(localStorage.getItem(windowStorageKey) || '{}');
        } catch (error) {
            visibility = {};
        }

        const syncWindowState = () => {
            workspaceWindows.forEach((windowEl) => {
                const id = windowEl.id;
                const isVisible = visibility[id] !== false;
                windowEl.classList.toggle('is-hidden', !isVisible);
            });

            toggles.forEach((toggle) => {
                const targetId = toggle.getAttribute('data-window-toggle');
                const isVisible = visibility[targetId] !== false;
                toggle.classList.toggle('is-active', isVisible);
            });

            localStorage.setItem(windowStorageKey, JSON.stringify(visibility));
        };

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.getAttribute('data-window-toggle');
                const targetWindow = document.getElementById(targetId);
                const willShow = visibility[targetId] === false;

                visibility[targetId] = !willShow ? false : true;
                syncWindowState();

                if (willShow && targetWindow) {
                    targetWindow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        syncWindowState();
    }
});
