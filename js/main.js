(() => {
    'use strict';

    const menuToggle = document.querySelector('.menu-toggle');
    const siteMenu = document.querySelector('#site-menu');

    if (!menuToggle || !siteMenu) {
        return;
    }

    const closeMenu = () => {
        menuToggle.setAttribute('aria-expanded', 'false');
        siteMenu.classList.remove('is-open');
        document.body.classList.remove('menu-is-open');
    };

    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        siteMenu.classList.toggle('is-open', !isOpen);
        document.body.classList.toggle('menu-is-open', !isOpen);
    });

    siteMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 767) {
            closeMenu();
        }
    });
})();
