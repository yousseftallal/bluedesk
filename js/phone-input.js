(function () {
  'use strict';

  var input = document.getElementById('phone');
  var form = document.getElementById('contactForm');
  var internationalInput = document.getElementById('phone-international');
  var countryInput = document.getElementById('phone-country');
  var errorMessage = document.getElementById('phone-error');

  if (!input || !form || !internationalInput || !window.intlTelInput) return;

  var instance = null;
  var arTranslations = {
    selectedCountryAriaLabel: 'تغيير دولة رقم الهاتف، المحددة ${countryName} (${dialCode})',
    noCountrySelected: 'اختر دولة لرقم الهاتف',
    countryListAriaLabel: 'قائمة الدول',
    searchPlaceholder: 'ابحث باسم الدولة أو كود الاتصال',
    clearSearchAriaLabel: 'مسح البحث',
    closeCountrySelectorAriaLabel: 'إغلاق قائمة الدول',
    searchEmptyState: 'لم يتم العثور على نتائج',
    searchSummaryAria: function (count) {
      if (count === 0) return 'لم يتم العثور على نتائج';
      if (count === 1) return 'تم العثور على نتيجة واحدة';
      if (count === 2) return 'تم العثور على نتيجتين';
      return 'تم العثور على ' + count + ' دولة';
    }
  };

  function currentLanguage() {
    return document.documentElement.lang === 'en' ? 'en' : 'ar';
  }

  function clearPhoneError() {
    input.setCustomValidity('');
    input.removeAttribute('aria-invalid');
    if (errorMessage) {
      errorMessage.hidden = true;
      errorMessage.textContent = '';
    }
  }

  function showPhoneError() {
    var message = currentLanguage() === 'en'
      ? 'Enter a valid phone number for the selected country.'
      : 'أدخل رقم هاتف صحيحًا للدولة المختارة.';
    input.setCustomValidity(message);
    input.setAttribute('aria-invalid', 'true');
    if (errorMessage) {
      errorMessage.textContent = message;
      errorMessage.hidden = false;
    }
    input.reportValidity();
  }

  function initialize(countryCode) {
    var selectedCountry = countryCode || (countryInput.value || 'eg').toLowerCase();
    var options = {
      initialCountry: selectedCountry,
      countryOrder: ['eg', 'sa', 'ae', 'kw', 'qa', 'bh', 'om'],
      countryNameLocale: currentLanguage(),
      separateDialCode: true,
      showFlags: true,
      countrySearch: true,
      countrySelectorMode: 'AUTO',
      formatAsYouType: true,
      dropdownParent: document.body
    };
    if (currentLanguage() === 'ar') options.uiTranslations = arTranslations;

    instance = window.intlTelInput(input, options);
    if (internationalInput.value && !input.value) {
      instance.setNumber(internationalInput.value);
    }
    var selected = instance.getSelectedCountry();
    if (selected && selected.iso2) countryInput.value = selected.iso2;
  }

  initialize();

  input.addEventListener('input', function () {
    internationalInput.value = '';
    clearPhoneError();
  });

  input.addEventListener('countrychange', function () {
    internationalInput.value = '';
    if (instance) {
      var selected = instance.getSelectedCountry();
      if (selected && selected.iso2) countryInput.value = selected.iso2;
    }
    clearPhoneError();
  });

  form.addEventListener('submit', function (event) {
    if (!input.value.trim()) {
      internationalInput.value = '';
      return;
    }

    if (!instance.isValidNumber()) {
      event.preventDefault();
      showPhoneError();
      return;
    }

    var number = instance.getNumber();
    if (!number || number.charAt(0) !== '+') {
      event.preventDefault();
      showPhoneError();
      return;
    }

    internationalInput.value = number;
    var selected = instance.getSelectedCountry();
    if (selected && selected.iso2) countryInput.value = selected.iso2;
    clearPhoneError();
  });

  var languageObserver = new MutationObserver(function () {
    if (!instance || instance._bluedeskLanguage === currentLanguage()) return;

    var previousNumber = input.value;
    var selected = instance.getSelectedCountry();
    var selectedCountry = selected && selected.iso2 ? selected.iso2 : 'eg';
    instance.destroy();
    input.value = previousNumber;
    initialize(selectedCountry);
    instance._bluedeskLanguage = currentLanguage();
  });
  instance._bluedeskLanguage = currentLanguage();
  languageObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
})();
