<?php
require_once __DIR__ . '/includes/functions.php';

/* ---------------- Handle contact form submission (POST) ---------------- */
if (is_post()) {
    require_csrf();

    // Honeypot: silently accept bots
    if (trim($_POST['website'] ?? '') !== '') {
        flash('success', 'contact_sent');
        redirect('contact.php?sent=1#contact-form');
    }

    $name    = input('name');
    $email   = input('email');
    $phoneDisplay = input('phone_display');
    $phone = input('phone');
    $phoneCountry = strtolower(input('phone_country'));
    if (!preg_match('/^[a-z]{2}$/D', $phoneCountry)) {
        $phoneCountry = 'eg';
    }
    if ($phone === '') {
        $phone = $phoneDisplay;
    }
    $phoneDigits = strtr($phone, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]);
    $normalizedPhone = safe_tel($phoneDigits);
    if ($phone !== '' && $normalizedPhone !== '') {
        $phone = $normalizedPhone;
    }
    $company = input('company');
    $service = input('service');
    $message = input('message');

    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'name';
    }
    if (!valid_email($email) || mb_strlen($email) > 190) {
        $errors[] = 'email';
    }
    if ($phone !== '' && ($normalizedPhone === '' || mb_strlen($phone) > 40)) {
        $errors[] = 'phone';
    }
    if ($phone !== '' && !preg_match('/^[a-z]{2}$/D', $phoneCountry)) {
        $errors[] = 'phone';
    }
    if ($company !== '' && mb_strlen($company) > 190) {
        $errors[] = 'company';
    }
    if ($service !== '' && mb_strlen($service) > 120) {
        $errors[] = 'service';
    }
    if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $errors[] = 'message';
    }

    // Simple rate limit: max 5 inquiries per IP per hour
    $recent = db_fetch(
        'SELECT COUNT(*) AS c FROM inquiries WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)',
        [client_ip()]
    );
    if ((int) ($recent['c'] ?? 0) >= 5) {
        flash('error', 'contact_rate');
        redirect('contact.php#contact-form');
    }

    if ($errors) {
        $_SESSION['old_contact'] = compact('name', 'email', 'phone', 'company', 'service', 'message') + [
            'phone_display' => $phoneDisplay,
            'phone_country' => $phoneCountry,
        ];
        flash('error', 'contact_invalid');
        redirect('contact.php#contact-form');
    }

    db_query(
        'INSERT INTO inquiries (name, email, phone, phone_country, company, service, message, ip, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $name, $email, $phone ?: null, $phone !== '' ? $phoneCountry : null, $company ?: null, $service ?: null, $message,
            client_ip(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]
    );
    unset($_SESSION['old_contact']);
    flash('success', 'contact_sent');
    redirect('contact.php?sent=1#contact-form');
}

/* ---------------- Page data ---------------- */
$pageSlug  = 'contact';
$branches  = db_fetch_all('SELECT * FROM branches WHERE is_active = 1 ORDER BY is_main DESC, sort_order, id');
$services  = db_fetch_all('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$phone     = setting('phone', '01505768013');
$phoneIntl = safe_tel(setting('phone_intl', '+201505768013')) ?: safe_tel($phone);
$email     = setting('email', 'support@bluedeskgroup.net');
$old       = $_SESSION['old_contact'] ?? [];
unset($_SESSION['old_contact']);
$flashes   = get_flashes();
$flashMap  = [
    'contact_sent'    => ['تم إرسال رسالتك بنجاح! سنتواصل معك قريبًا.', 'Your message has been sent successfully! We will contact you soon.'],
    'contact_invalid' => ['من فضلك تحقق من البيانات المدخلة وحاول مرة أخرى.', 'Please check the entered data and try again.'],
    'contact_rate'    => ['تم إرسال عدد كبير من الرسائل. من فضلك حاول لاحقًا.', 'Too many messages sent. Please try again later.'],
];
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <section class="page-header">
      <div class="container">
        <h1 data-i18n="contact.title">اتصل بنا</h1>
        <p data-i18n="contact.desc">يسعدنا تواصلك معنا. أرسل رسالتك وسنتواصل معك في أقرب وقت ممكن.</p>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-i18n="contact.title">اتصل بنا</span>
        </nav>
      </div>
    </section>

    <!-- BRANCHES -->
    <section class="section" aria-labelledby="branches-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <span data-i18n="branches.tag">فروعنا</span></p>
          <h2 id="branches-title" data-i18n="branches.title">مقرّان في خدمتك: قليوب والدقي</h2>
          <p data-i18n="branches.desc">تفضل بزيارتنا في المقر الرئيسي أو الفرع الثاني، أو تواصل معنا هاتفيًا في أي وقت.</p>
        </div>
        <div class="branches-grid">
          <?php foreach ($branches as $branch): ?>
          <?php $branchTel = safe_tel((string) $branch['phone']) ?: $phoneIntl; ?>
          <article class="branch-card reveal<?= (int) $branch['is_main'] ? ' is-main' : '' ?>">
            <span class="branch-badge<?= (int) $branch['is_main'] ? '' : ' secondary' ?>">
              <span data-ar="<?= (int) $branch['is_main'] ? 'المقر الرئيسي' : 'الفرع الثاني' ?>" data-en="<?= (int) $branch['is_main'] ? 'Main Headquarters' : 'Second Branch' ?>"><?= (int) $branch['is_main'] ? 'المقر الرئيسي' : 'الفرع الثاني' ?></span>
            </span>
            <h3 data-ar="<?= e($branch['name_ar']) ?>" data-en="<?= e($branch['name_en']) ?>"><?= e($branch['name_ar']) ?></h3>
            <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i>
              <span data-ar="<?= e($branch['address_ar']) ?>" data-en="<?= e($branch['address_en']) ?>"><?= e($branch['address_ar']) ?></span>
            </p>
            <?php if (!empty($branch['phone'])): ?>
            <p><i class="fa-solid fa-phone" aria-hidden="true"></i>
              <a href="tel:<?= e($branchTel) ?>" dir="ltr"><?= e($branch['phone']) ?></a>
            </p>
            <?php endif; ?>
            <?php if (!empty($branch['email'])): ?>
            <p><i class="fa-solid fa-envelope" aria-hidden="true"></i>
              <a href="mailto:<?= e($branch['email']) ?>"><?= e($branch['email']) ?></a>
            </p>
            <?php endif; ?>
            <div class="branch-actions">
              <?php $safeMapUrl = safe_http_url((string) $branch['map_url']); $safeMapEmbed = safe_map_embed((string) $branch['map_embed']); ?>
              <?php if ($safeMapUrl !== ''): ?>
              <a class="btn btn-primary" href="<?= e($safeMapUrl) ?>" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                <span data-i18n="branches.open_map">فتح في خرائط جوجل</span>
              </a>
              <?php endif; ?>
              <?php if ($branchTel !== ''): ?><a class="btn btn-outline" href="tel:<?= e($branchTel) ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i> <span data-i18n="branches.call">اتصل بنا</span></a><?php endif; ?>
            </div>
            <?php if ($safeMapEmbed !== ''): ?>
            <div class="branch-map">
              <iframe src="<?= e($safeMapEmbed) ?>" title="<?= e($branch['name_ar']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" sandbox="allow-scripts allow-same-origin allow-popups" allowfullscreen></iframe>
            </div>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CONTACT + FORM -->
    <section class="section section-alt" id="contact-form" aria-labelledby="contact-h2">
      <div class="container contact-grid">
        <div>
          <div class="section-head" style="text-align:start;margin:0 0 1.6rem">
            <p class="section-tag"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Contact</p>
            <h2 id="contact-h2" data-i18n="contact.info.h2">معلومات التواصل</h2>
          </div>
          <div class="contact-info">
            <div class="ci-card">
              <div class="ci-icon grad-blue"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
              <div><h3 data-i18n="contact.phone.t">الهاتف</h3><a href="tel:<?= e($phoneIntl) ?>" dir="ltr"><?= e($phone) ?></a></div>
            </div>
            <div class="ci-card">
              <div class="ci-icon grad-green"><i class="fa-solid fa-envelope" aria-hidden="true"></i></div>
              <div><h3 data-i18n="contact.email.t">البريد الإلكتروني</h3><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div>
            </div>
            <div class="ci-card">
              <div class="ci-icon grad-yellow"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
              <div><h3 data-i18n="contact.hours.t">ساعات العمل</h3><p data-ar="<?= e(setting('hours_ar')) ?>" data-en="<?= e(setting('hours_en')) ?>"><?= e(setting('hours_ar')) ?></p></div>
            </div>
            <?php foreach ($branches as $branch): ?>
            <div class="ci-card">
              <div class="ci-icon grad-red"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
              <div>
                <h3 data-ar="<?= e($branch['name_ar']) ?>" data-en="<?= e($branch['name_en']) ?>"><?= e($branch['name_ar']) ?></h3>
                <p data-ar="<?= e($branch['address_ar']) ?>" data-en="<?= e($branch['address_en']) ?>"><?= e($branch['address_ar']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <div class="section-head" style="text-align:start;margin:0 0 1.6rem">
            <p class="section-tag"><i class="fa-solid fa-message" aria-hidden="true"></i> <span data-i18n="contact.h2">لنتحدث عن مشروعك</span></p>
            <p style="color:var(--ink-soft);line-height:1.8" data-i18n="contact.lead">املأ النموذج وسيتواصل معك فريقنا خلال 24 ساعة لمناقشة احتياجاتك وإرسال عرض سعر مخصص.</p>
          </div>

          <?php foreach ($flashes as $f):
            $pair = $flashMap[$f['message']] ?? null; ?>
          <div class="alert <?= $f['type'] === 'success' ? 'success' : 'error' ?>" role="status" aria-live="polite">
            <i class="fa-solid <?= $f['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
            <?php if ($pair): ?>
            <span data-ar="<?= e($pair[0]) ?>" data-en="<?= e($pair[1]) ?>"><?= e($pair[0]) ?></span>
            <?php else: ?>
            <span><?= e($f['message']) ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

          <form class="contact-form" id="contactForm" method="post" action="contact.php">
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true">
              <label for="website">Website</label>
              <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-msg" role="status" aria-live="polite"></div>
            <div class="form-row">
              <div class="form-group">
                <label for="name"><span data-i18n="contact.f.name">الاسم الكامل</span> <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="name" name="name" required maxlength="120" autocomplete="name" value="<?= e($old['name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label for="email"><span data-i18n="contact.f.email">البريد الإلكتروني</span> <span class="req" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($old['email'] ?? '') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group" id="phone-group">
                <label for="phone" data-i18n="contact.f.phone">رقم الهاتف</label>
                <div class="phone-input-wrap">
                  <input type="tel" id="phone" name="phone_display" maxlength="40" autocomplete="tel-national" inputmode="tel" dir="ltr" aria-describedby="phone-hint phone-error" value="<?= e($old['phone_display'] ?? $old['phone'] ?? '') ?>">
                  <input type="hidden" id="phone-international" name="phone" value="<?= e($old['phone'] ?? '') ?>">
                  <input type="hidden" id="phone-country" name="phone_country" value="<?= e($old['phone_country'] ?? 'eg') ?>">
                </div>
                <span class="phone-input-hint" id="phone-hint" data-ar="اختر الدولة، وسيظهر كود الاتصال بجوار العلم." data-en="Choose a country; its calling code will appear beside the flag.">اختر الدولة، وسيظهر كود الاتصال بجوار العلم.</span>
                <span class="phone-input-error" id="phone-error" role="status" aria-live="polite" hidden></span>
              </div>
              <div class="form-group">
                <label for="company" data-i18n="contact.f.company">اسم الشركة</label>
                <input type="text" id="company" name="company" maxlength="190" autocomplete="organization" value="<?= e($old['company'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label for="service" data-i18n="contact.f.service">الخدمة المطلوبة</label>
              <select id="service" name="service">
                <option value="" data-i18n="contact.f.select">اختر الخدمة</option>
                <?php foreach ($services as $service): ?>
                <option value="<?= e($service['slug']) ?>"<?= (($old['service'] ?? '') === $service['slug']) ? ' selected' : '' ?> data-ar="<?= e($service['title_ar']) ?>" data-en="<?= e($service['title_en']) ?>"><?= e($service['title_ar']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="message"><span data-i18n="contact.f.message">تفاصيل المشروع</span> <span class="req" aria-hidden="true">*</span></label>
              <textarea id="message" name="message" required maxlength="5000" placeholder="اكتب تفاصيل مشروعك هنا..." data-i18n-attr="placeholder:contact.f.placeholder"><?= e($old['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
              <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
              <span data-i18n="contact.f.send">إرسال الرسالة</span>
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>
  <?php
    $phoneInputVersion = (string) (filemtime(APP_ROOT . '/js/phone-input.js') ?: 1);
    $phoneLibraryVersion = '29.5.8';
  ?>
  <script src="<?= e(asset('assets/vendor/intl-tel-input/js/intlTelInputWithUtils.min.js') . '?v=' . rawurlencode($phoneLibraryVersion)) ?>" defer></script>
  <script src="<?= e(asset('js/phone-input.js') . '?v=' . rawurlencode($phoneInputVersion)) ?>" defer></script>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
