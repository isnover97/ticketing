(() => {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('workspace-sidebar');
    const mobile = matchMedia('(max-width: 750px)');
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const preference = 'prei-sidebar-collapsed';
    let desktopCollapsed = false;

    try {
        desktopCollapsed = localStorage.getItem(preference) === 'true';
    } catch {}

    function setCollapsed(collapsed) {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand menu' : 'Collapse menu');
        if (collapsed && sidebar.contains(document.activeElement)) toggle.focus();
    }

    function saveDesktopPreference(collapsed) {
        desktopCollapsed = collapsed;
        try {
            localStorage.setItem(preference, String(collapsed));
        } catch {}
    }

    function applyViewport() {
        setCollapsed(mobile.matches || desktopCollapsed);
    }

    applyViewport();
    mobile.addEventListener('change', applyViewport);

    toggle.addEventListener('click', () => {
        const collapsed = toggle.getAttribute('aria-expanded') === 'true';
        setCollapsed(collapsed);
        if (!mobile.matches) saveDesktopPreference(collapsed);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches && toggle.getAttribute('aria-expanded') === 'true') {
            setCollapsed(true);
            toggle.focus();
        }
    });

    sidebar.querySelectorAll('details').forEach((group) => {
        const summary = group.querySelector('summary');
        const list = group.querySelector('ul');
        let animation;
        let closing = false;

        summary.addEventListener('click', (event) => {
            if (!mobile.matches && desktopCollapsed) {
                event.preventDefault();
                animation?.cancel();
                animation = undefined;
                closing = false;
                list.inert = false;
                list.style.overflow = '';
                group.open = true;
                setCollapsed(false);
                saveDesktopPreference(false);
                return;
            }

            if (!list.animate || reducedMotion.matches) return;

            event.preventDefault();
            const height = list.getBoundingClientRect().height;
            const wasOpen = group.open;
            animation?.cancel();
            closing = wasOpen && !closing;
            group.open = true;
            list.inert = closing;
            if (closing && list.contains(document.activeElement)) summary.focus();

            animation = list.animate(
                { height: [height + 'px', (closing ? 0 : list.getBoundingClientRect().height) + 'px'] },
                { duration: 190, easing: 'ease-out' }
            );
            list.style.overflow = 'hidden';
            animation.onfinish = () => {
                group.open = !closing;
                list.inert = false;
                list.style.overflow = '';
                closing = false;
                animation = undefined;
            };
        });
    });
})();
