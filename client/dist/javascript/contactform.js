"use strict";

document.addEventListener('DOMContentLoaded', function () {
  initContactForms();
});
var contactFormElems = document.querySelectorAll('.bbp-contact-form-element');
function initContactForms() {
  contactFormElems.forEach(function (formElem) {
    var contactForm = formElem.querySelector(':scope form');
    var submitButton = formElem.querySelector(':scope button[type=submit]');
    var messageField = formElem.querySelector(':scope div.form-message');
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var submittedForm = e.target;
      contactForm.classList.remove('form-validated');
      submitButton.setAttribute('disabled', 'disabled');
      messageField.innerHTML = 'Please wait a moment...';
      if (!contactForm.checkValidity()) {
        contactForm.classList.add('form-validated');
        submitButton.removeAttribute('disabled');
        messageField.innerHTML = 'Please ensure you have filled in all the required data, and try again.';
      } else {
        grecaptcha.ready(function () {
          grecaptcha.execute();
        });
      }
    });
  });
}
function submitContactForm(token, formID) {
  var formElem = document.getElementById(formID);
  if (formElem !== null) {
    var submitButton = formElem.querySelector(':scope button[type=submit]');
    var messageField = formElem.querySelector(':scope div.form-message');
    var formData = new FormData(formElem);
    fetch(formElem.action, {
      method: 'POST',
      headers: {
        'x-requested-with': 'XMLHttpRequest'
      },
      body: formData
    }).then(function (response) {
      return response.json();
    }).then(function (data) {
      submitButton.removeAttribute('disabled');
      messageField.innerHTML = data.message;
      if (data.success) {
        formElem.reset;
      }
    })["catch"](function (error) {
      submitButton.removeAttribute('disabled');
      messageField.innerHTML = 'An error occurred. Please try again.';
      console.error('Error submitting form:', error);
    });
  } else {
    console.log('No form', formID);
  }
}
//# sourceMappingURL=../../dist/javascript/maps/contactform.js.map
