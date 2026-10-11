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
        });
    });

    sizeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            sizeButtons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
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
        });
    });

    addToCart?.addEventListener('click', () => {
        const activeColour = productPage.querySelector('.product-swatch[aria-pressed="true"]')?.dataset.colour;
        const activeSize = productPage.querySelector('.product-size-grid button[aria-pressed="true"]')?.dataset.size;

        if (!activeColour || !activeSize) {
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

    const cartPage = document.querySelector('.cart-page');

    if (!cartPage) {
        return;
    }

    const cartItems = cartPage.querySelectorAll('[data-cart-item]');
    const cartSubtotal = cartPage.querySelector('#cart-subtotal');
    const cartDiscount = cartPage.querySelector('#cart-discount');
    const cartDelivery = cartPage.querySelector('#cart-delivery');
    const cartTotal = cartPage.querySelector('#cart-total');
    const cartItemsCount = cartPage.querySelector('#cart-items-count');
    const cartItemsHeader = cartPage.querySelector('#cart-items-header');
    const cartSummary = cartPage.querySelector('#cart-summary');
    const cartEmpty = cartPage.querySelector('#cart-empty');

    const formatCurrency = (value) => '$' + value.toFixed(2);
    const discountRate = Number(cartSummary?.dataset.discountRate) || 0;
    const deliveryFee = Number(cartSummary?.dataset.deliveryFee) || 0;

    const updateCart = () => {
        let subtotal = 0;
        let itemCount = 0;
        let visibleItemCount = 0;

        cartItems.forEach((item) => {
            if (item.hidden) {
                return;
            }

            const price = Number(item.dataset.price) || 0;
            const quantity = Math.max(1, Number(item.dataset.quantity) || 1);
            item.dataset.quantity = String(quantity);

            const quantityOutput = item.querySelector('[data-cart-quantity-value]');
            const lineTotal = item.querySelector('[data-cart-line-total]');

            if (quantityOutput) {
                quantityOutput.textContent = String(quantity);
            }

            if (lineTotal) {
                lineTotal.textContent = formatCurrency(price * quantity);
            }

            subtotal += price * quantity;
            itemCount += quantity;
            visibleItemCount += 1;
        });

        const discount = subtotal * discountRate;
        const delivery = visibleItemCount > 0 ? deliveryFee : 0;
        const total = subtotal - discount + delivery;

        if (cartSubtotal) {
            cartSubtotal.textContent = formatCurrency(subtotal);
        }

        if (cartDiscount) {
            cartDiscount.textContent = '-' + formatCurrency(discount);
        }

        if (cartDelivery) {
            cartDelivery.textContent = formatCurrency(delivery);
        }

        if (cartTotal) {
            cartTotal.textContent = formatCurrency(total);
        }

        if (cartItemsCount) {
            cartItemsCount.textContent = itemCount + ' ' + (itemCount === 1 ? 'item' : 'items');
        }

        if (cartEmpty) {
            cartEmpty.hidden = visibleItemCount !== 0;
        }

        if (cartSummary) {
            cartSummary.hidden = visibleItemCount === 0;
        }

        if (cartItemsHeader) {
            cartItemsHeader.hidden = visibleItemCount === 0;
        }
    };

    cartItems.forEach((item) => {
        item.querySelectorAll('[data-cart-quantity]').forEach((button) => {
            button.addEventListener('click', () => {
                const current = Number(item.dataset.quantity) || 1;
                const delta = button.dataset.cartQuantity === 'increase' ? 1 : -1;
                item.dataset.quantity = String(Math.max(1, current + delta));
                updateCart();
            });
        });

        item.querySelector('[data-cart-remove]')?.addEventListener('click', () => {
            item.hidden = true;
            updateCart();
        });
    });

    updateCart();
})();
