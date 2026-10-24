(() => {
    'use strict';

    const filterForm = document.querySelector('.shop-filter-form');
    const searchForm = document.querySelector('.shop-search');
    const sortForm = document.querySelector('.shop-sort');
    const range = document.querySelector('.shop-price-range');

    if (!filterForm) {
        return;
    }

    const submitFilters = () => filterForm.requestSubmit();
    const setHiddenValue = (form, name, value) => {
        const field = form?.querySelector(`[name="${name}"]`);
        if (field) {
            field.value = value;
        }
    };

    filterForm.querySelectorAll('input[name="category[]"], input[name="colour[]"]').forEach((input) => {
        input.addEventListener('change', submitFilters);
    });

    const searchInput = searchForm?.querySelector('input[type="search"]');
    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => searchForm.requestSubmit(), 350);
        });
    }

    const sortSelect = sortForm?.querySelector('select[name="sort"]');
    sortSelect?.addEventListener('change', () => sortForm.requestSubmit());

    if (range) {
        const controls = range.querySelector('#price-range-controls');
        const minHandle = range.querySelector('#min-price-range');
        const maxHandle = range.querySelector('#max-price-range');
        const minValue = range.querySelector('#price-min-value');
        const maxValue = range.querySelector('#price-max-value');
        const minimum = Number(range.dataset.min);
        const maximum = Number(range.dataset.max);
        let lower = Number(minHandle.getAttribute('aria-valuenow'));
        let upper = Number(maxHandle.getAttribute('aria-valuenow'));
        let rangeTimer;

        const updateRange = (shouldSubmit = true) => {
            minValue.textContent = `Min: $${lower.toLocaleString()}`;
            maxValue.textContent = `Max: $${upper.toLocaleString()}`;
            const span = maximum - minimum;
            const startPercent = span > 0 ? ((lower - minimum) / span) * 100 : 0;
            const endPercent = span > 0 ? ((upper - minimum) / span) * 100 : 100;
            range.style.setProperty('--price-range-start', `${startPercent}%`);
            range.style.setProperty('--price-range-end', `${endPercent}%`);
            minHandle.style.left = `${startPercent}%`;
            maxHandle.style.left = `${endPercent}%`;
            minHandle.setAttribute('aria-valuenow', String(lower));
            maxHandle.setAttribute('aria-valuenow', String(upper));
            minHandle.setAttribute('aria-valuetext', `$${lower.toLocaleString()}`);
            maxHandle.setAttribute('aria-valuetext', `$${upper.toLocaleString()}`);

            [filterForm, searchForm, sortForm].forEach((form) => {
                setHiddenValue(form, 'min_price', lower === minimum ? '' : String(lower));
                setHiddenValue(form, 'max_price', upper === maximum ? '' : String(upper));
            });

            if (shouldSubmit) {
                window.clearTimeout(rangeTimer);
                rangeTimer = window.setTimeout(submitFilters, 350);
            }
        };

        const setHandleValue = (handle, value, shouldSubmit = true) => {
            const next = Math.max(minimum, Math.min(maximum, Math.round(value)));
            if (handle === minHandle) {
                lower = Math.min(next, upper);
            } else {
                upper = Math.max(next, lower);
            }
            updateRange(shouldSubmit);
        };

        [minHandle, maxHandle].forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => {
                window.clearTimeout(rangeTimer);
                handle.setPointerCapture(event.pointerId);
                handle.dataset.dragging = 'true';
                event.preventDefault();
            });

            handle.addEventListener('pointermove', (event) => {
                if (handle.dataset.dragging !== 'true') {
                    return;
                }
                const bounds = controls.getBoundingClientRect();
                const fraction = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
                setHandleValue(handle, minimum + fraction * (maximum - minimum), false);
            });

            const stopDragging = () => {
                if (handle.dataset.dragging === 'true') {
                    updateRange();
                }
                delete handle.dataset.dragging;
            };
            handle.addEventListener('pointerup', stopDragging);
            handle.addEventListener('pointercancel', stopDragging);

            handle.addEventListener('keydown', (event) => {
                const step = event.shiftKey ? 10 : 1;
                const current = handle === minHandle ? lower : upper;
                let next = current;
                if (event.key === 'ArrowRight' || event.key === 'ArrowUp') next += step;
                else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') next -= step;
                else if (event.key === 'PageUp') next += 10;
                else if (event.key === 'PageDown') next -= 10;
                else if (event.key === 'Home') next = handle === minHandle ? minimum : lower;
                else if (event.key === 'End') next = handle === maxHandle ? maximum : upper;
                else return;
                event.preventDefault();
                setHandleValue(handle, next);
            });
        });

        controls.addEventListener('pointerdown', (event) => {
            if (event.target.closest('.shop-price-range__handle')) return;
            const bounds = controls.getBoundingClientRect();
            const fraction = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
            const value = minimum + fraction * (maximum - minimum);
            const closestHandle = Math.abs(value - lower) <= Math.abs(value - upper) ? minHandle : maxHandle;
            closestHandle.focus();
            setHandleValue(closestHandle, value);
        });

        updateRange(false);
    }
})();
