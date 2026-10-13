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
    const feedback = form.querySelector('#review-feedback');

    if (!changeRequestField || !changeRequest || !decisionError || !changeRequestError || !feedback) {
        return;
    }

    const clearFeedback = () => {
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
        clearFeedback();

        if (!isRequestingChanges) {
            clearChangeRequestError();
        }
    };

    decisionInputs.forEach((input) => {
        input.addEventListener('change', updateChangeRequestVisibility);
    });

    changeRequest.addEventListener('input', () => {
        if (changeRequest.value.trim() !== '') {
            clearChangeRequestError();
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearFeedback();

        const selectedDecision = form.querySelector('input[name="review_decision"]:checked');

        if (!selectedDecision) {
            decisionError.hidden = false;
            decisionInputs[0]?.focus();
            return;
        }

        clearDecisionError();

        if (selectedDecision.value === 'request_changes' && changeRequest.value.trim() === '') {
            changeRequest.setCustomValidity('Please describe the changes you would like to request.');
            changeRequest.setAttribute('aria-invalid', 'true');
            changeRequestError.hidden = false;
            changeRequest.focus();
            return;
        }

        clearChangeRequestError();
        feedback.classList.add('is-success');
        feedback.hidden = false;
        feedback.textContent = selectedDecision.value === 'approve'
            ? 'Demo: Your design approval has been selected. The review will be saved when backend functionality is implemented.'
            : 'Demo: Your change request has been entered. It will be saved when backend functionality is implemented.';
        feedback.focus({ preventScroll: true });
    });

    updateChangeRequestVisibility();
})();
