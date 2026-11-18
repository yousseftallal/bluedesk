(function () {
  'use strict';

  var loader = document.currentScript;
  if (!loader) return;

  var measurementId = (loader.getAttribute('data-measurement-id') || '').toUpperCase();
  if (!/^G-[A-Z0-9]{4,30}$/.test(measurementId)) return;

  var consentRequired = loader.getAttribute('data-require-consent') !== '0';
  var storageKey = 'bluedesk-ga-consent-' + measurementId;
  var banner = document.getElementById('analyticsConsentBanner');
  var choice = null;
  var loaded = false;

  try {
    choice = window.localStorage.getItem(storageKey);
  } catch (error) {
    // The choice still works for the current page when storage is unavailable.
  }

  function saveChoice(value) {
    choice = value;
    try {
      window.localStorage.setItem(storageKey, value);
    } catch (error) {
      // Keep the choice in memory for this page when storage is unavailable.
    }
  }

  function ensureGtag() {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () {
      window.dataLayer.push(arguments);
    };
  }

  function setConsent(analyticsState) {
    ensureGtag();
    window.gtag('consent', 'update', {
      analytics_storage: analyticsState,
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied'
    });
  }

  function denyByDefault() {
    ensureGtag();
    window.gtag('consent', 'default', {
      analytics_storage: 'denied',
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied'
    });
  }

  function startAnalytics() {
    if (loaded || window.__bluedeskGaInitialized === measurementId) {
      setConsent('granted');
      return;
    }

    ensureGtag();
    denyByDefault();
    setConsent('granted');
    window.gtag('js', new Date());
    window.gtag('config', measurementId);

    var googleTag = document.createElement('script');
    googleTag.async = true;
    googleTag.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
    document.head.appendChild(googleTag);

    loaded = true;
    window.__bluedeskGaInitialized = measurementId;
  }

  function hideBanner() {
    if (banner) banner.hidden = true;
  }

  function showBanner() {
    if (!banner) return;
    banner.hidden = false;
    var acceptButton = banner.querySelector('[data-analytics-choice="accept"]');
    if (acceptButton) acceptButton.focus();
  }

  if (choice === 'accepted') {
    startAnalytics();
  } else if (choice === 'rejected') {
    // A previous rejection remains respected even when direct measurement is enabled.
    if (window.__bluedeskGaInitialized === measurementId) setConsent('denied');
    else denyByDefault();
  } else if (consentRequired) {
    showBanner();
  } else {
    startAnalytics();
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof Element)) return;

    var choiceButton = target.closest('[data-analytics-choice]');
    if (choiceButton) {
      if (choiceButton.getAttribute('data-analytics-choice') === 'accept') {
        saveChoice('accepted');
        startAnalytics();
      } else {
        saveChoice('rejected');
        if (loaded || window.__bluedeskGaInitialized === measurementId) setConsent('denied');
        else denyByDefault();
      }
      hideBanner();
      return;
    }

    if (target.closest('[data-analytics-open]')) showBanner();
  });
})();
