(function () {
    'use strict';

    const mobileBreakpoint = window.matchMedia('(max-width: 768px)');
    const body = document.body;
    const menu = document.getElementById('menu');
    const menuToggle = document.getElementById('menu-toggle');
    const menuBackdrop = document.getElementById('menu-backdrop');

    function setMenuOpen(open) {
        if (!menu || !menuToggle || !menuBackdrop) return;

        body.classList.toggle('menu-open', open);
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
        menu.setAttribute('aria-hidden', String(mobileBreakpoint.matches && !open));
        menuBackdrop.hidden = !open;
    }

    if (menu && menuToggle && menuBackdrop) {
        menuToggle.addEventListener('click', function () {
            setMenuOpen(!body.classList.contains('menu-open'));
        });

        menuBackdrop.addEventListener('click', function () {
            setMenuOpen(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setMenuOpen(false);
                menuToggle.focus();
            }
        });

        menu.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileBreakpoint.matches && !link.closest('.dropdown-toggle')) {
                    setMenuOpen(false);
                }
            });
        });

        const handleBreakpointChange = function () {
            setMenuOpen(false);
        };
        if (typeof mobileBreakpoint.addEventListener === 'function') {
            mobileBreakpoint.addEventListener('change', handleBreakpointChange);
        } else {
            mobileBreakpoint.addListener(handleBreakpointChange);
        }

        setMenuOpen(false);
    }

    document.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            const dropdown = toggle.closest('.dropdown');
            if (dropdown) dropdown.classList.toggle('active');
        });
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.dropdown.active').forEach(function (dropdown) {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    });

    async function incrementHitCount() {
        const counters = document.querySelectorAll('#hitCount');
        if (!counters.length) return;

        try {
            const response = await fetch('/visit_count/hit_counter.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': 'application/json'}
            });
            if (!response.ok) throw new Error('Counter returned HTTP ' + response.status);

            const data = await response.json();
            counters.forEach(function (counter) {
                counter.textContent = data.count;
            });
        } catch (error) {
            console.error('Unable to update visit count:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', incrementHitCount, {once: true});
    } else {
        incrementHitCount();
    }
})();
