(() => {
    'use strict';

    const form = document.querySelector('#mockup-review-form');

    if (!form) {
        return;
    }

    const decisionInputs = Array.from(form.querySelectorAll('input[name="review_decision"]'));
    const changeRequestField = form.querySelector('#change-request-field');
    const changeRequest = form.querySelector('#change-request');
    const decisionError = form.querySelector('#decision-error');
    const changeRequestError = form.querySelector('#change-request-error');
    const feedback = document.querySelector('#review-feedback');

    if (!changeRequestField || !changeRequest || !decisionError || !changeRequestError) {
        return;
    }

    const clearFeedback = () => {
        if (!feedback) {
            return;
        }

        feedback.hidden = true;
        feedback.textContent = '';
        feedback.classList.remove('is-success', 'is-error');
    };

    const clearDecisionError = () => {
        decisionError.hidden = true;
    };

    const clearChangeRequestError = () => {
        changeRequestError.hidden = true;
        changeRequest.setAttribute('aria-invalid', 'false');
        changeRequest.setCustomValidity('');
    };

    const updateChangeRequestVisibility = () => {
        const selectedDecision = form.querySelector('input[name="review_decision"]:checked');
        const isRequestingChanges = selectedDecision?.value === 'request_changes';

        changeRequestField.hidden = !isRequestingChanges;
        changeRequest.disabled = !isRequestingChanges;
        changeRequest.required = isRequestingChanges;
        clearDecisionError();

        if (!isRequestingChanges) {
            clearChangeRequestError();
        }
    };

    decisionInputs.forEach((input) => {
        input.addEventListener('change', () => {
            clearFeedback();
            updateChangeRequestVisibility();
        });
    });

    changeRequest.addEventListener('input', () => {
        if (changeRequest.value.trim() !== '') {
            clearChangeRequestError();
        }
    });

    form.addEventListener('submit', (event) => {
        clearFeedback();

        const selectedDecision = form.querySelector('input[name="review_decision"]:checked');

        if (!selectedDecision) {
            event.preventDefault();
            decisionError.hidden = false;
            decisionInputs[0]?.focus();
            return;
        }

        clearDecisionError();

        if (selectedDecision.value === 'request_changes' && changeRequest.value.trim() === '') {
            event.preventDefault();
            changeRequest.setCustomValidity('Please describe the changes you would like to request.');
            changeRequest.setAttribute('aria-invalid', 'true');
            changeRequestError.hidden = false;
            changeRequest.focus();
            return;
        }

        clearChangeRequestError();
        // Valid submissions intentionally continue to mockup-action.php.
    });

    updateChangeRequestVisibility();
})();
