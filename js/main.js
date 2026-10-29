(() => {
    'use strict';

    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const revealTargets = document.querySelectorAll('[data-scroll-reveal]');
    let revealObserver = null;

    if (revealTargets.length && 'IntersectionObserver' in window && !reducedMotionQuery.matches) {
        document.documentElement.classList.add('has-scroll-motion');
        revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

        revealTargets.forEach((target) => revealObserver.observe(target));
    }

    const brandStrip = document.querySelector('.brand-strip');
    const brandTrack = brandStrip?.querySelector('.brand-strip__track');
    const brandSequence = brandTrack?.querySelector('.brand-strip__sequence');
    let stopMarquee = () => {};

    if (brandStrip && brandTrack && brandSequence && 'requestAnimationFrame' in window && !reducedMotionQuery.matches) {
        const before = brandSequence.cloneNode(true);
        const after = brandSequence.cloneNode(true);
        before.setAttribute('aria-hidden', 'true');
        after.setAttribute('aria-hidden', 'true');
        brandTrack.prepend(before);
        brandTrack.append(after);
        brandStrip.classList.add('brand-strip--animated');

        const getSpeed = () => window.innerWidth <= 767 ? 12 : 18;
        let sequenceWidth = 0;
        let phase = 0;
        let lastFrameTime = 0;
        let frameId = 0;
        let paused = false;
        let destroyed = false;

        const measureSequence = () => {
            sequenceWidth = brandSequence.getBoundingClientRect().width;
            phase = sequenceWidth ? phase % sequenceWidth : 0;
            if (phase < 0) phase += sequenceWidth;
        };

        const animate = (now) => {
            if (destroyed) return;
            const deltaTime = lastFrameTime ? Math.min(now - lastFrameTime, 50) : 16;
            lastFrameTime = now;

            if (!paused && sequenceWidth > 0) {
                phase = (phase + getSpeed() * deltaTime / 1000) % sequenceWidth;
                if (phase < 0) phase += sequenceWidth;
                brandTrack.style.transform = `translate3d(${-sequenceWidth - phase}px, 0, 0)`;
            }

            frameId = window.requestAnimationFrame(animate);
        };

        const onVisibilityChange = () => {
            paused = document.hidden;
            if (!paused) lastFrameTime = 0;
        };

        measureSequence();
        const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(measureSequence) : null;
        resizeObserver?.observe(brandSequence);
        document.addEventListener('visibilitychange', onVisibilityChange);
        frameId = window.requestAnimationFrame(animate);

        stopMarquee = () => {
            destroyed = true;
            window.cancelAnimationFrame(frameId);
            resizeObserver?.disconnect();
            document.removeEventListener('visibilitychange', onVisibilityChange);
            brandTrack.style.transform = '';
            before.remove();
            after.remove();
            brandStrip.classList.remove('brand-strip--animated');
        };

        const onMotionPreferenceChange = (event) => {
            if (event.matches) {
                stopMarquee();
                revealObserver?.disconnect();
                document.documentElement.classList.remove('has-scroll-motion');
            }
        };
        reducedMotionQuery.addEventListener?.('change', onMotionPreferenceChange);
        window.addEventListener('pagehide', () => {
            stopMarquee();
            revealObserver?.disconnect();
        }, { once: true });
    }

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

    if (!reducedMotionQuery.matches) {
        document.querySelectorAll('[data-hover-gallery]').forEach((gallery) => {
            const track = gallery.querySelector('.product-card__gallery-track');
            const slides = track?.querySelectorAll('.product-card__image-art') || [];
            const trigger = gallery.closest('a');

            if (!track || slides.length < 2 || !trigger) {
                return;
            }

            let activeIndex = 0;
            let direction = 1;
            let timer = null;

            const showSlide = (index) => {
                activeIndex = index;
                track.style.transform = `translate3d(${-activeIndex * 100}%, 0, 0)`;
            };

            const start = () => {
                if (timer !== null) {
                    return;
                }

                timer = window.setInterval(() => {
                    if (activeIndex === slides.length - 1) direction = -1;
                    if (activeIndex === 0) direction = 1;
                    showSlide(activeIndex + direction);
                }, 1050);
            };

            const stop = () => {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
                direction = 1;
                showSlide(0);
            };

            trigger.addEventListener('mouseenter', start);
            trigger.addEventListener('mouseleave', stop);
            trigger.addEventListener('focusin', start);
            trigger.addEventListener('focusout', (event) => {
                if (!trigger.contains(event.relatedTarget)) {
                    stop();
                }
            });
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
        const cartForm = productPage.querySelector('#product-cart-form');
        const parsedMaxQuantity = Number.parseInt(cartForm?.dataset.maxQuantity || '99', 10);
        const maxQuantity = Number.isInteger(parsedMaxQuantity) ? parsedMaxQuantity : 99;
        const productName = cartForm?.dataset.productName || 'Product';
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
                quantity = Math.min(maxQuantity, Math.max(1, quantity + direction));

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
                feedback.textContent = `${quantity} × ${activeColour} ${productName} (${activeSize}) is ready for your bag.`;
            }
        });
    }
})();
