(function () {
  'use strict';

  var labels = document.querySelectorAll('.inquiry-phone-country[data-phone]');
  if (!labels.length || !window.intlTelInput) return;

  var scratch = document.createElement('div');
  scratch.setAttribute('aria-hidden', 'true');
  scratch.style.cssText = 'position:fixed;left:-10000px;top:0;width:1px;height:1px;overflow:hidden;visibility:hidden;';
  var input = document.createElement('input');
  input.type = 'tel';
  scratch.appendChild(input);
  document.body.appendChild(scratch);

  var phoneParser = window.intlTelInput(input, {
    initialCountry: 'eg',
    countryNameLocale: 'ar',
    countrySelectorMode: 'OFF',
    showFlags: true
  });
  var allCountries = window.intlTelInput.getAllCountries();

  function asInternationalNumber(value) {
    var raw = String(value || '').trim().replace(/[٠-٩۰-۹]/g, function (digit) {
      var westernDigits = '0123456789';
      var arabicIndic = '٠١٢٣٤٥٦٧٨٩';
      var persian = '۰۱۲۳۴۵۶۷۸۹';
      var index = arabicIndic.indexOf(digit);
      if (index < 0) index = persian.indexOf(digit);
      return index < 0 ? digit : westernDigits.charAt(index);
    });
    var digits = raw.replace(/\D/g, '');
    if (raw.charAt(0) === '+' && digits.length >= 7 && digits.length <= 15) return '+' + digits;
    if (/^01[0125]\d{8}$/.test(digits)) return '+20' + digits.slice(1);
    if (/^1[0125]\d{8}$/.test(digits)) return '+20' + digits;
    if (/^20(1[0125]\d{8})$/.test(digits)) return '+' + digits;
    return '';
  }

  labels.forEach(function (label) {
    var internationalNumber = asInternationalNumber(label.getAttribute('data-phone'));
    var selectedCountryCode = String(label.getAttribute('data-phone-country') || '').toLowerCase();
    var hasSavedCountry = /^[a-z]{2}$/.test(selectedCountryCode)
      && allCountries.some(function (country) { return country.iso2 === selectedCountryCode; });

    if (!hasSavedCountry && !internationalNumber) {
      label.classList.add('inquiry-phone-country-unknown');
      label.textContent = 'تعذر تحديد الدولة';
      return;
    }

    if (hasSavedCountry) {
      phoneParser.setSelectedCountry(selectedCountryCode);
    } else {
      phoneParser.setNumber(internationalNumber);
    }
    var country = phoneParser.getSelectedCountry();
    var numberDigits = internationalNumber ? internationalNumber.replace(/\D/g, '') : '';
    if (!country || !country.iso2 || !country.dialCode || (!hasSavedCountry && !numberDigits.startsWith(country.dialCode))) {
      label.classList.add('inquiry-phone-country-unknown');
      label.textContent = 'تعذر تحديد الدولة';
      return;
    }

    var flag = document.createElement('span');
    flag.className = 'iti__flag iti__' + country.iso2;
    flag.setAttribute('aria-hidden', 'true');

    var countryName = document.createElement('span');
    countryName.className = 'inquiry-phone-country-name';
    countryName.textContent = country.name || country.iso2.toUpperCase();

    var dialCode = document.createElement('bdi');
    dialCode.className = 'inquiry-phone-dial-code';
    dialCode.dir = 'ltr';
    dialCode.textContent = '+' + country.dialCode;

    label.replaceChildren(flag, countryName, dialCode);
    label.setAttribute('aria-label', country.name + '، رمز الاتصال +' + country.dialCode);
    label.title = country.name + ' · +' + country.dialCode;
  });

  phoneParser.destroy();
  scratch.remove();
})();
