/* Format stored UTC instants in each visitor's browser time zone. */
(function () {
  'use strict';

  let timezone = 'UTC';
  try {
    timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
  } catch (_) {}

  document.documentElement.dataset.timezone = timezone;
  const locale = document.documentElement.lang || navigator.language || 'en';
  const yearElement = document.querySelector('[data-local-current-year]');
  if (yearElement) {
    try {
      yearElement.textContent = new Intl.DateTimeFormat(locale, { year: 'numeric', timeZone: timezone }).format(new Date());
    } catch (_) {}
  }
  const formats = {
    date: { year: 'numeric', month: 'short', day: 'numeric' },
    time: { hour: 'numeric', minute: '2-digit' },
    datetime: { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
  };

  document.querySelectorAll('time[data-local-datetime][datetime]').forEach((element) => {
    const instant = new Date(element.getAttribute('datetime'));
    if (Number.isNaN(instant.getTime())) return;
    const format = element.dataset.localFormat || 'datetime';
    const options = formats[format] || formats.datetime;
    try {
      element.textContent = new Intl.DateTimeFormat(locale, { ...options, timeZone: timezone }).format(instant);
      element.title = new Intl.DateTimeFormat(locale, { ...options, timeZone: timezone, timeZoneName: 'long' }).format(instant);
    } catch (_) {}
  });

  document.querySelectorAll('[data-local-datetime-input]').forEach((input) => {
    const form = input.form;
    const zoneField = form && form.querySelector('input[name="client_timezone"]');
    if (zoneField) zoneField.value = timezone;

    const storedUtc = input.dataset.utcValue || '';
    if (!input.value && storedUtc) {
      const instant = new Date(storedUtc);
      if (!Number.isNaN(instant.getTime())) {
        const localValue = new Date(instant.getTime() - instant.getTimezoneOffset() * 60000)
          .toISOString()
          .slice(0, 16);
        input.value = localValue;
      }
    }

    if (form && zoneField && !form.dataset.timezoneBound) {
      form.dataset.timezoneBound = '1';
      form.addEventListener('submit', () => { zoneField.value = timezone; });
    }
  });
})();
