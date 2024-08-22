document.addEventListener('DOMContentLoaded', () => {
    initContactForms();
});

const contactFormElems = document.querySelectorAll('.bbp-contact-form-element');

function initContactForms() {
    contactFormElems.forEach((formElem) => {
        let contactForm = formElem.querySelector(':scope form');
        let submitButton = formElem.querySelector(':scope [type=submit]');
        let messageField = formElem.querySelector(':scope div.form-message');

        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const submittedForm = e.target;
            contactForm.classList.remove('form-validated');
            submitButton.setAttribute('disabled', 'disabled');
            messageField.innerHTML = 'Please wait a moment...';
            if (!contactForm.checkValidity()) {
                contactForm.classList.add('form-validated');
                submitButton.removeAttribute('disabled');
                messageField.innerHTML = 'Please ensure you have filled in all the required data, and try again.';
            } else {
                grecaptcha.ready(() => {
                    grecaptcha.execute();
                });
            }
        });
    });
}

function submitContactForm(token, formID) {

    const formElem = document.getElementById(formID);

    if (formElem !== null) {

        const submitButton = formElem.querySelector(':scope button[type=submit]');
        const messageField = formElem.querySelector(':scope div.form-message');
        const formData = new FormData(formElem);

        fetch(formElem.action, {
            method: 'POST',
            headers: {
                'x-requested-with': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                submitButton.removeAttribute('disabled');
                messageField.innerHTML = data.message;
                if (data.success) {
                    formElem.reset();
                }
            })
            .catch(error => {
                submitButton.removeAttribute('disabled');
                messageField.innerHTML = 'An error occurred. Please try again.';
                console.error('Error submitting form:', error);
            });
    } else {
        console.log('No form', formID);
    }
}

