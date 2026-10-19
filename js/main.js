(() => {
    'use strict';

    const menuToggle = document.querySelector('.menu-toggle');
    const siteMenu = document.querySelector('#site-menu');

    if (menuToggle && siteMenu) {
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
    }

    const productPage = document.querySelector('.product-page');

    if (productPage) {
        const mainImage = productPage.querySelector('#product-main-image');
        const thumbnails = productPage.querySelectorAll('.product-thumbnail');
        const colourButtons = productPage.querySelectorAll('.product-swatch');
        const sizeButtons = productPage.querySelectorAll('.product-size-grid button');
        const selectedColour = productPage.querySelector('#selected-colour');
        const quantityValue = productPage.querySelector('#product-quantity-value');
        const colourInput = productPage.querySelector('#product-colour');
        const sizeInput = productPage.querySelector('#product-size');
        const quantityInput = productPage.querySelector('#product-quantity');
        const feedback = productPage.querySelector('#product-feedback');
        const addToCart = productPage.querySelector('#add-to-cart');
        let quantity = 1;

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            if (!mainImage) {
                return;
            }

            mainImage.src = thumbnail.dataset.imageSrc;
            mainImage.alt = thumbnail.dataset.imageAlt;

            thumbnails.forEach((item) => {
                const isSelected = item === thumbnail;
                item.classList.toggle('is-active', isSelected);
                item.setAttribute('aria-pressed', String(isSelected));
            });
        });
    });

    colourButtons.forEach((button) => {
        button.addEventListener('click', () => {
            colourButtons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));

            if (selectedColour) {
                selectedColour.textContent = button.dataset.colour;
            }

            if (colourInput) {
                colourInput.value = button.dataset.colour;
            }
        });
    });

    sizeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            sizeButtons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));

            if (sizeInput) {
                sizeInput.value = button.dataset.size;
            }
        });
    });

    productPage.querySelectorAll('[data-quantity-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const direction = button.dataset.quantityAction === 'increase' ? 1 : -1;
            quantity = Math.max(1, quantity + direction);

            if (quantityValue) {
                quantityValue.value = quantity;
                quantityValue.textContent = String(quantity);
            }

            if (quantityInput) {
                quantityInput.value = String(quantity);
            }
        });
    });

    addToCart?.addEventListener('click', (event) => {
        const activeColour = productPage.querySelector('.product-swatch[aria-pressed="true"]')?.dataset.colour;
        const activeSize = productPage.querySelector('.product-size-grid button[aria-pressed="true"]')?.dataset.size;

        if (!activeColour || !activeSize) {
            event.preventDefault();
            if (feedback) {
                feedback.textContent = 'Choose a colour and size before adding this item.';
            }
            return;
        }

        if (feedback) {
            feedback.textContent = `${quantity} × ${activeColour} Studio Overshirt (${activeSize}) is ready for your bag.`;
        }
    });
    }

})();
