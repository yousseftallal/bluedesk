<?php
require_once __DIR__ . '/functions.php';

$footerQuick    = db_fetch_all("SELECT * FROM nav_items WHERE location = 'footer_quick' AND is_active = 1 ORDER BY sort_order, id");
$footerServices = db_fetch_all("SELECT * FROM nav_items WHERE location = 'footer_services' AND is_active = 1 ORDER BY sort_order, id");
$footerServiceTargets = db_fetch_all('SELECT slug, title_ar, title_en FROM services WHERE is_active = 1');
$footerBranches = db_fetch_all("SELECT * FROM branches WHERE is_active = 1 ORDER BY is_main DESC, sort_order, id");
$socials = [
    ['url' => 'https://www.facebook.com/bluedeskhosting', 'icon' => 'fa-facebook-f', 'label' => 'فيسبوك'],
    ['url' => 'https://wa.me/201505768013', 'icon' => 'fa-whatsapp', 'label' => 'واتساب'],
];
$phone    = setting('phone', '01505768013');
$phoneIntl= safe_tel(setting('phone_intl', '+201505768013')) ?: safe_tel($phone);
$email    = setting('email', 'support@bluedeskgroup.net');
$siteName = setting('site_name', 'BlueDesk');
$whatsappNumber = preg_replace('/\D+/', '', $phoneIntl) ?: '';
if (str_starts_with($whatsappNumber, '0')) {
    $whatsappNumber = '2' . $whatsappNumber;
}
$facebookUrl = safe_http_url(setting('facebook', 'https://www.facebook.com/bluedeskhosting'));
$scriptVersion = (string) (filemtime(APP_ROOT . '/js/main.js') ?: 1);
$localTimeScriptVersion = (string) (filemtime(APP_ROOT . '/js/local-time.js') ?: 1);
$analyticsId = strtoupper(trim(setting('google_analytics_id')));
$analyticsEnabled = setting('google_analytics_enabled', '0') === '1'
  && preg_match('/^G-[A-Z0-9]{4,30}$/D', $analyticsId) === 1;
$analyticsConsentRequired = setting('google_analytics_consent_required', '1') !== '0';
$analyticsScriptVersion = (string) (filemtime(APP_ROOT . '/js/google-analytics.js') ?: 1);
?>
  <footer class="footer" role="contentinfo">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-about">
          <img src="<?= e(asset(setting('logo_white', 'assets/logo-white.svg'))) ?>" alt="<?= e($siteName) ?>" width="150" height="40" loading="lazy">
          <p data-ar="<?= e(setting('footer_desc_ar')) ?>" data-en="<?= e(setting('footer_desc_en')) ?>"><?= e(setting('footer_desc_ar')) ?></p>
          <div class="socials" aria-label="حسابات التواصل الاجتماعي">
            <?php foreach ($socials as $social): ?>
            <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($social['label']) ?>" title="<?= e($social['label']) ?>"><i class="fa-brands <?= e($social['icon']) ?>" aria-hidden="true"></i></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <h3 data-i18n="footer.links">روابط سريعة</h3>
          <ul class="footer-links">
            <?php foreach ($footerQuick as $item): ?>
            <li><a href="<?= e(site_href($item['url']) ?: '#') ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i> <span data-ar="<?= e($item['label_ar']) ?>" data-en="<?= e($item['label_en']) ?>"><?= e($item['label_ar']) ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div>
          <h3 data-i18n="footer.services">خدماتنا</h3>
          <ul class="footer-links">
            <?php foreach ($footerServices as $item):
              $serviceUrl = trim((string) $item['url']);
              if ($serviceUrl === 'services.php') {
                  foreach ($footerServiceTargets as $serviceTarget) {
                      if (trim((string) $item['label_ar']) === trim((string) $serviceTarget['title_ar']) || trim((string) $item['label_en']) === trim((string) $serviceTarget['title_en'])) {
                          $serviceUrl = 'services.php#' . rawurlencode((string) $serviceTarget['slug']);
                          break;
                      }
                  }
              }
            ?>
            <li><a href="<?= e(site_href($serviceUrl) ?: '#') ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i> <span data-ar="<?= e($item['label_ar']) ?>" data-en="<?= e($item['label_en']) ?>"><?= e($item['label_ar']) ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div>
          <h3 data-i18n="footer.contact">معلومات التواصل</h3>
          <ul class="footer-contact">
            <li><i class="fa-solid fa-phone" aria-hidden="true"></i> <a href="tel:<?= e($phoneIntl) ?>" dir="ltr"><?= e($phone) ?></a></li>
            <li><i class="fa-solid fa-envelope" aria-hidden="true"></i> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
            <li><i class="fa-solid fa-clock" aria-hidden="true"></i> <span data-ar="<?= e(setting('hours_ar')) ?>" data-en="<?= e(setting('hours_en')) ?>"><?= e(setting('hours_ar')) ?></span></li>
            <?php foreach ($footerBranches as $branch): ?>
            <li>
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
              <span>
                <b class="branch-mini-title" data-ar="<?= e($branch['name_ar']) ?>" data-en="<?= e($branch['name_en']) ?>"><?= e($branch['name_ar']) ?></b><br>
                <span data-ar="<?= e($branch['address_ar']) ?>" data-en="<?= e($branch['address_en']) ?>"><?= e($branch['address_ar']) ?></span>
              </span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span id="year" data-local-current-year><?= date('Y') ?></span> <?= e($siteName) ?>. <span data-i18n="footer.rights">جميع الحقوق محفوظة</span>. <span data-i18n="footer.made">صُنع بحب في مصر</span> <i class="fa-solid fa-heart" style="color:var(--red)" aria-hidden="true"></i></p>
        <?php if ($analyticsEnabled): ?>
        <button class="analytics-privacy-trigger" type="button" data-analytics-open data-ar="إعدادات الخصوصية" data-en="Privacy settings">إعدادات الخصوصية</button>
        <?php endif; ?>
      </div>
    </div>
  </footer>

  <?php if (($phoneIntl !== '' && $whatsappNumber !== '') || $facebookUrl !== ''): ?>
  <div class="contact-fabs" aria-label="طرق التواصل">
    <?php if ($phoneIntl !== '' && $whatsappNumber !== ''): ?>
    <a class="contact-fab contact-fab-whatsapp" href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" rel="noopener noreferrer" data-i18n-attr="aria-label:contact.whatsapp;title:contact.whatsapp">
      <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </a>
    <a class="contact-fab contact-fab-phone" href="tel:<?= e($phoneIntl) ?>" data-i18n-attr="aria-label:contact.phone;title:contact.phone">
      <i class="fa-solid fa-phone" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <?php if ($facebookUrl !== ''): ?>
    <a class="contact-fab contact-fab-facebook" href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" data-i18n-attr="aria-label:contact.facebook;title:contact.facebook">
      <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <button class="to-top" type="button" aria-label="العودة إلى الأعلى"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>

  <?php if ($analyticsEnabled): ?>
  <section id="analyticsConsentBanner" class="analytics-consent-banner" role="dialog" aria-modal="false" aria-labelledby="analyticsConsentTitle" aria-describedby="analyticsConsentDescription" hidden>
    <div class="analytics-consent-copy">
      <h2 id="analyticsConsentTitle" data-ar="قياس استخدام الموقع" data-en="Help us improve this site">قياس استخدام الموقع</h2>
      <p id="analyticsConsentDescription" data-ar="نستخدم Google Analytics لفهم استخدام الموقع وتحسينه. لن يبدأ القياس قبل موافقتك، ويمكنك تغيير اختيارك من إعدادات الخصوصية." data-en="We use Google Analytics to understand and improve this site. Measurement will not start until you agree. You can change your choice in Privacy settings.">نستخدم Google Analytics لفهم استخدام الموقع وتحسينه. لن يبدأ القياس قبل موافقتك، ويمكنك تغيير اختيارك من إعدادات الخصوصية.</p>
    </div>
    <div class="analytics-consent-actions">
      <button type="button" class="analytics-consent-accept" data-analytics-choice="accept" data-ar="موافقة" data-en="Accept">موافقة</button>
      <button type="button" class="analytics-consent-reject" data-analytics-choice="reject" data-ar="رفض" data-en="Reject">رفض</button>
    </div>
  </section>
  <script src="<?= e(asset('js/google-analytics.js') . '?v=' . rawurlencode($analyticsScriptVersion)) ?>" data-measurement-id="<?= e($analyticsId) ?>" data-require-consent="<?= $analyticsConsentRequired ? '1' : '0' ?>" defer></script>
  <?php endif; ?>

  <script src="<?= e(asset('js/main.php') . '?v=' . rawurlencode($scriptVersion)) ?>" defer></script>
  <script src="<?= e(asset('js/local-time.js') . '?v=' . rawurlencode($localTimeScriptVersion)) ?>" defer></script>
</body>
</html>
