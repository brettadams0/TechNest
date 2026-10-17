/*
 * Progressive enhancement only: every page works without this file. It is
 * served from the store's own origin because the Content-Security-Policy
 * forbids inline script.
 */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  // Mobile navigation.
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
  }

  // <form data-confirm="..."> asks before submitting a destructive action.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });

  // Controls marked data-autosubmit submit their form on change, so sort,
  // filters and cart quantities need no separate button with JS enabled.
  document.querySelectorAll('[data-autosubmit]').forEach(function (control) {
    control.addEventListener('change', function () {
      if (control.form) {
        control.form.requestSubmit();
      }
    });
  });

  // Live price on the product page. The server recalculates on add-to-cart
  // and again at checkout; this is display only.
  var priceForm = document.querySelector('[data-price-form]');
  if (priceForm) {
    var output = priceForm.querySelector('[data-price-output]');
    var base = Number(priceForm.getAttribute('data-base-price')) || 0;
    var money = new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD' });
    var update = function () {
      var cents = base;
      priceForm.querySelectorAll('input[type="radio"]:checked').forEach(function (radio) {
        cents += Number(radio.getAttribute('data-extra')) || 0;
      });
      if (output) {
        output.textContent = money.format(cents / 100);
      }
    };
    priceForm.addEventListener('change', update);
    update();
  }

  // Dismissible flash messages.
  document.querySelectorAll('[data-dismiss]').forEach(function (button) {
    button.addEventListener('click', function () {
      var alert = button.closest('.alert');
      if (alert) {
        alert.remove();
      }
    });
  });
})();
