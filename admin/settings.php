<?php
/**
 * General site settings: identity, colors, contact, social, footer.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

if (is_post() && input('action') === 'save') {
    require_csrf();

    $textKeys = [
        'site_name', 'site_tagline_ar', 'site_tagline_en',
        'phone', 'phone_intl', 'email', 'hours_ar', 'hours_en',
        'facebook', 'twitter', 'instagram', 'linkedin',
        'footer_desc_ar', 'footer_desc_en', 'footer_rights_ar', 'footer_rights_en', 'footer_made_ar', 'footer_made_en',
    ];
    $values = [];
    foreach ($textKeys as $key) {
        if (array_key_exists($key, $_POST)) {
            $values[$key] = input($key);
        }
    }

    $errors = [];
    if (($values['site_name'] ?? '') === '' || mb_strlen($values['site_name'] ?? '') > 120) {
        $errors[] = 'اسم الموقع مطلوب ولا يتجاوز 120 حرفًا.';
    }
    if (!valid_email($values['email'] ?? '') || mb_strlen($values['email'] ?? '') > 190) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }
    if (safe_tel($values['phone'] ?? '') === '') {
        $errors[] = 'رقم الهاتف غير صالح.';
    }
    if (safe_tel($values['phone_intl'] ?? '') === '' || !str_starts_with(safe_tel($values['phone_intl'] ?? ''), '+')) {
        $errors[] = 'رقم الهاتف الدولي يجب أن يبدأ بعلامة + ويتكوّن من 7 إلى 15 رقمًا.';
    }
    foreach (['facebook', 'twitter', 'instagram', 'linkedin'] as $key) {
        $value = $values[$key] ?? '';
        if ($value !== '' && safe_http_url($value) !== $value) {
            $errors[] = 'روابط التواصل الاجتماعي يجب أن تكون عناوين HTTPS أو HTTP صحيحة.';
            break;
        }
    }

    $colorValues = [];
    foreach (['color_red', 'color_blue', 'color_yellow', 'color_green'] as $colorKey) {
        if (!array_key_exists($colorKey, $_POST)) continue;
        $value = input($colorKey);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            $errors[] = 'أحد ألوان الهوية غير صالح.';
        } else {
            $colorValues[$colorKey] = $value;
        }
    }

    $analyticsId = strtoupper(trim((string) input('google_analytics_id')));
    $analyticsEnabled = input('google_analytics_enabled') === '1' ? '1' : '0';
    $analyticsConsentRequired = input('google_analytics_consent_required') === '1' ? '1' : '0';
    if ($analyticsId !== '' && !preg_match('/^G-[A-Z0-9]{4,30}$/D', $analyticsId)) {
        $errors[] = 'رقم قياس GA4 غير صالح. استخدم رقمًا يبدأ بـ G- متبوعًا بحروف وأرقام فقط.';
    }
    if ($analyticsEnabled === '1' && $analyticsId === '') {
        $errors[] = 'أدخل رقم قياس GA4 قبل تفعيل Google Analytics.';
    }
    $values['google_analytics_id'] = $analyticsId;
    $values['google_analytics_enabled'] = $analyticsEnabled;
    $values['google_analytics_consent_required'] = $analyticsConsentRequired;

    $adsensePublisherId = strtolower(trim((string) input('adsense_publisher_id')));
    $adsenseEnabled = input('adsense_enabled') === '1' ? '1' : '0';
    $adsenseHomeEnabled = input('adsense_home_enabled') === '1' ? '1' : '0';
    $adsenseBlogEnabled = input('adsense_blog_enabled') === '1' ? '1' : '0';
    $adsenseArticleEnabled = input('adsense_article_enabled') === '1' ? '1' : '0';
    if ($adsensePublisherId !== '' && !preg_match('/^ca-pub-[0-9]{16,20}$/D', $adsensePublisherId)) {
        $errors[] = 'رقم ناشر AdSense غير صالح. استخدم ca-pub- ثم أرقام رقم الناشر.';
    }
    if ($adsenseEnabled === '1' && $adsensePublisherId === '') {
        $errors[] = 'أدخل رقم ناشر AdSense قبل تفعيل الإعلانات.';
    }
    $values['adsense_publisher_id'] = $adsensePublisherId;
    $values['adsense_enabled'] = $adsenseEnabled;
    $values['adsense_home_enabled'] = $adsenseHomeEnabled;
    $values['adsense_blog_enabled'] = $adsenseBlogEnabled;
    $values['adsense_article_enabled'] = $adsenseArticleEnabled;

    if ($errors) {
        flash('error', implode(' ', array_unique($errors)));
        redirect('settings.php');
    }

    $newUploads = [];
    $oldUploads = [];
    try {
        db()->beginTransaction();
        foreach ($values as $key => $value) {
            save_setting($key, $value);
        }
        foreach ($colorValues as $key => $value) {
            save_setting($key, $value);
        }

        foreach (['logo', 'logo_white', 'favicon'] as $imgKey) {
            $oldValue = setting($imgKey);
            $value = array_key_exists($imgKey, $_POST) ? input($imgKey) : $oldValue;
            if (!empty($_FILES[$imgKey . '_file']['name'])) {
                $uploaded = upload_image($_FILES[$imgKey . '_file'], $uploadError);
                if ($uploaded === null) {
                    throw new DomainException($uploadError ?: 'تعذر رفع ملف الصورة.');
                }
                $newUploads[] = $uploaded;
                $value = $uploaded;
            }
            if ($value !== '' && asset($value) === '') {
                throw new DomainException('مسار الشعار أو الأيقونة غير صالح.');
            }
            if ($oldValue !== '' && $oldValue !== $value) {
                $oldUploads[] = $oldValue;
            }
            save_setting($imgKey, $value);
        }

        log_activity('تحديث الإعدادات', 'تم تحديث إعدادات الموقع العامة');
        db()->commit();
        foreach (array_unique($oldUploads) as $oldUpload) {
            delete_upload($oldUpload);
        }
        flash('success', 'تم حفظ الإعدادات بنجاح.');
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        foreach ($newUploads as $newUpload) {
            delete_upload($newUpload);
        }
        if ($ex instanceof DomainException) {
            $error = $ex->getMessage();
        } else {
            error_log('Site settings save failed: ' . $ex->getMessage());
            $error = 'تعذر حفظ الإعدادات. لم يتم تطبيق أي تغيير؛ حاول مرة أخرى.';
        }
        flash('error', $error);
    }
    redirect('settings.php');
}

$adminTitle = 'الإعدادات';
$adminSubtitle = 'الهوية، الألوان، بيانات التواصل والفوتر';
$adminActive = 'settings';
require __DIR__ . '/includes/header.php';
?>

<form method="post" action="settings.php" enctype="multipart/form-data" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="save">

  <div class="page-head">
    <div><h1>إعدادات الموقع</h1><p class="sub">كل ما تغيّره هنا يظهر فورًا على الموقع العام.</p></div>
    <div class="actions">
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات</button>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-shapes" style="color:var(--blue);margin-inline-end:.45rem"></i> هوية الموقع</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="site_name">اسم الموقع <span class="req">*</span></label>
          <input class="form-control" id="site_name" name="site_name" value="<?php echo e(setting('site_name')); ?>" required>
        </div>
        <div class="form-group">
          <label for="email">البريد الإلكتروني <span class="req">*</span></label>
          <input class="form-control ltr" type="email" id="email" name="email" value="<?php echo e(setting('email')); ?>" required>
        </div>
        <div class="form-group">
          <label for="site_tagline_ar">الوصف المختصر (عربي)</label>
          <input class="form-control" id="site_tagline_ar" name="site_tagline_ar" value="<?php echo e(setting('site_tagline_ar')); ?>">
        </div>
        <div class="form-group">
          <label for="site_tagline_en">الوصف المختصر (إنجليزي)</label>
          <input class="form-control ltr" id="site_tagline_en" name="site_tagline_en" value="<?php echo e(setting('site_tagline_en')); ?>">
        </div>
      </div>

      <div class="form-grid" style="margin-top:.6rem">
        <?php
        $logos = [
            'logo' => ['label' => 'الشعار الأساسي (خلفية فاتحة)', 'value' => setting('logo', 'assets/logo.svg')],
            'logo_white' => ['label' => 'الشعار الأبيض (خلفية داكنة)', 'value' => setting('logo_white', 'assets/logo-white.svg')],
            'favicon' => ['label' => 'أيقونة المتصفح (Favicon)', 'value' => setting('favicon', 'assets/favicon.svg')],
        ];
        foreach ($logos as $key => $meta): ?>
          <div class="form-group">
            <label for="<?php echo e($key); ?>"><?php echo e($meta['label']); ?></label>
            <div class="copy-field" style="margin-bottom:.45rem">
              <input class="form-control ltr" id="<?php echo e($key); ?>" name="<?php echo e($key); ?>" value="<?php echo e($meta['value']); ?>">
            </div>
            <input class="form-control" type="file" name="<?php echo e($key); ?>_file" accept="image/png,image/jpeg,image/webp,image/gif">
            <span class="hint">اترك الحقل كما هو أو ارفع ملفًا جديدًا (PNG/JPG/WEBP/GIF).</span>
            <?php if (preg_match('/\.(svg|png|jpe?g|webp|gif)$/i', $meta['value'])): ?>
              <img class="preview-img" src="<?php echo e(asset($meta['value'])); ?>" alt="معاينة" style="max-height:70px;width:auto;background:#f8f9fb;padding:.4rem">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-palette" style="color:var(--yellow-dark);margin-inline-end:.45rem"></i> ألوان العلامة التجارية</h2></div>
    <div class="panel-body">
      <p class="help-box"><b>ملاحظة:</b> هذه الألوان مستخرجة من شعار بلوديسك وتُستخدم في أزرار الموقع والعناوين والتفاصيل.</p>
      <div class="form-grid">
        <?php
        $colors = [
            'color_blue' => 'الأزرق الأساسي',
            'color_red' => 'الأحمر',
            'color_yellow' => 'الأصفر',
            'color_green' => 'الأخضر',
        ];
        foreach ($colors as $key => $label): ?>
          <div class="form-group">
            <label for="<?php echo e($key); ?>"><?php echo e($label); ?></label>
            <div class="color-field">
              <input type="color" value="<?php echo e(setting($key)); ?>" data-color-sync="#<?php echo e($key); ?>_text" aria-label="اختيار اللون">
              <input class="form-control ltr" id="<?php echo e($key); ?>_text" name="<?php echo e($key); ?>" value="<?php echo e(setting($key)); ?>" pattern="#[0-9a-fA-F]{6}">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-address-book" style="color:var(--green-dark);margin-inline-end:.45rem"></i> بيانات التواصل</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="phone">رقم الهاتف (كما يظهر) <span class="req">*</span></label>
          <input class="form-control ltr" id="phone" name="phone" value="<?php echo e(setting('phone')); ?>" required>
        </div>
        <div class="form-group">
          <label for="phone_intl">رقم الهاتف الدولي (للروابط)</label>
          <input class="form-control ltr" id="phone_intl" name="phone_intl" value="<?php echo e(setting('phone_intl')); ?>" placeholder="+201505768013">
          <span class="hint">يُستخدم في رابط الاتصال: tel:+201505768013</span>
        </div>
        <div class="form-group">
          <label for="hours_ar">ساعات العمل (عربي)</label>
          <input class="form-control" id="hours_ar" name="hours_ar" value="<?php echo e(setting('hours_ar')); ?>">
        </div>
        <div class="form-group">
          <label for="hours_en">ساعات العمل (إنجليزي)</label>
          <input class="form-control ltr" id="hours_en" name="hours_en" value="<?php echo e(setting('hours_en')); ?>">
        </div>
        <div class="form-group full">
          <label>الفروع</label>
          <p class="hint" style="margin:0">يمكنك إدارة عناوين الفروع وخرائط جوجل من صفحة <a href="branches.php" style="color:var(--blue-dark);font-weight:700">الفروع</a>.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-brands fa-facebook" style="color:var(--blue);margin-inline-end:.45rem"></i> حسابات التواصل الاجتماعي</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <?php foreach (['facebook' => 'فيسبوك', 'twitter' => 'تويتر / X', 'instagram' => 'إنستجرام', 'linkedin' => 'لينكد إن'] as $key => $label): ?>
          <div class="form-group">
            <label for="<?php echo e($key); ?>"><?php echo e($label); ?></label>
            <input class="form-control ltr" id="<?php echo e($key); ?>" name="<?php echo e($key); ?>" value="<?php echo e(setting($key)); ?>" placeholder="https://">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-chart-line" style="color:var(--green-dark);margin-inline-end:.45rem"></i> Google Analytics 4</h2></div>
    <div class="panel-body">
      <p class="help-box"><b>الربط:</b> أدخل Measurement ID الخاص ببيانات الويب في GA4، وسيُضاف Google tag تلقائيًا إلى صفحات الموقع العامة عند التفعيل. لا تحتاج إلى لصق كود برمجي يدويًا.</p>
      <div class="form-grid">
        <div class="form-group full">
          <label for="google_analytics_id">Measurement ID</label>
          <input class="form-control ltr" id="google_analytics_id" name="google_analytics_id" value="<?php echo e(setting('google_analytics_id')); ?>" placeholder="G-XXXXXXXXXX" pattern="G-[A-Za-z0-9]{4,30}" maxlength="32" autocomplete="off" spellcheck="false">
          <span class="hint">تجده من Google Analytics ← المشرف ← مصادر البيانات ← مصدر بيانات الويب. يبدأ عادةً بـ G-.</span>
        </div>
        <div class="form-group">
          <input type="hidden" name="google_analytics_enabled" value="0">
          <label for="google_analytics_enabled" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="google_analytics_enabled" name="google_analytics_enabled" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('google_analytics_enabled', '0') === '1' ? 'checked' : ''; ?>>
            تفعيل Google Analytics على الموقع
          </label>
          <span class="hint">إزالة علامة الاختيار توقف تحميل Google tag على الصفحات العامة.</span>
        </div>
        <div class="form-group">
          <input type="hidden" name="google_analytics_consent_required" value="0">
          <label for="google_analytics_consent_required" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="google_analytics_consent_required" name="google_analytics_consent_required" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('google_analytics_consent_required', '1') === '1' ? 'checked' : ''; ?>>
            اطلب موافقة الزائر قبل بدء القياس
          </label>
          <span class="hint">عند التفعيل، لن يُحمّل التتبع قبل موافقة الزائر. عند إيقافه، يبدأ القياس مباشرةً بعد فتح الصفحة.</span>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-rectangle-ad" style="color:var(--blue);margin-inline-end:.45rem"></i> إعلانات Google AdSense</h2></div>
    <div class="panel-body">
      <p class="help-box"><b>طريقة العرض:</b> هذا كود Auto ads؛ يحمّله الموقع في الصفحات التي تختارها فقط. من حساب AdSense نفسه اضبط كثافة الإعلانات، وفعّل إعلانات الجوانب للأجهزة العريضة، وخيار العثور على مواضع إضافية داخل المقالات. Google يختار المواضع الفعلية وقد لا يعرض إعلانًا في المقالات القصيرة. للزوار من المنطقة الاقتصادية الأوروبية والمملكة المتحدة وسويسرا، اضبط رسالة موافقة معتمدة من AdSense إذا كنت تعرض إعلانات مخصصة.</p>
      <div class="form-grid">
        <div class="form-group full">
          <label for="adsense_publisher_id">رقم الناشر</label>
          <input class="form-control ltr" id="adsense_publisher_id" name="adsense_publisher_id" value="<?php echo e(setting('adsense_publisher_id', 'ca-pub-8622942134176055')); ?>" placeholder="ca-pub-1234567890123456" pattern="ca-pub-[0-9]{16,20}" maxlength="28" autocomplete="off" spellcheck="false">
          <span class="hint">تم إدخال الرقم الموجود في الكود الذي أرسلته. لا تلصق وسم &lt;script&gt; هنا.</span>
        </div>
        <div class="form-group">
          <input type="hidden" name="adsense_enabled" value="0">
          <label for="adsense_enabled" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="adsense_enabled" name="adsense_enabled" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('adsense_enabled', '1') === '1' ? 'checked' : ''; ?>>
            تفعيل AdSense على الصفحات المحددة
          </label>
          <span class="hint">إزالة الاختيار توقف تحميل كود الإعلانات بالكامل.</span>
        </div>
        <div class="form-group">
          <input type="hidden" name="adsense_home_enabled" value="0">
          <label for="adsense_home_enabled" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="adsense_home_enabled" name="adsense_home_enabled" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('adsense_home_enabled', '1') === '1' ? 'checked' : ''; ?>>
            الصفحة الرئيسية
          </label>
        </div>
        <div class="form-group">
          <input type="hidden" name="adsense_blog_enabled" value="0">
          <label for="adsense_blog_enabled" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="adsense_blog_enabled" name="adsense_blog_enabled" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('adsense_blog_enabled', '1') === '1' ? 'checked' : ''; ?>>
            قائمة المدونة والتصنيفات
          </label>
        </div>
        <div class="form-group">
          <input type="hidden" name="adsense_article_enabled" value="0">
          <label for="adsense_article_enabled" style="display:flex;align-items:center;gap:.55rem;cursor:pointer">
            <input type="checkbox" id="adsense_article_enabled" name="adsense_article_enabled" value="1" style="width:1.1rem;height:1.1rem" <?php echo setting('adsense_article_enabled', '1') === '1' ? 'checked' : ''; ?>>
            صفحات المقالات
          </label>
        </div>
        <div class="form-group full">
          <span class="hint">الإعلانات الجانبية من Auto ads تظهر على الشاشات العريضة فقط. ظهور الإعلانات وربحها يعتمدان على قبول الموقع في AdSense وإعداداته؛ تحميل الكود وحده لا يضمن ملء الإعلان.</span>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-shoe-prints" style="color:var(--ink-soft);margin-inline-end:.45rem"></i> نصوص الفوتر</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="footer_desc_ar">وصف الشركة (عربي)</label>
          <textarea class="form-control" id="footer_desc_ar" name="footer_desc_ar" rows="3"><?php echo e(setting('footer_desc_ar')); ?></textarea>
        </div>
        <div class="form-group">
          <label for="footer_desc_en">وصف الشركة (إنجليزي)</label>
          <textarea class="form-control ltr" id="footer_desc_en" name="footer_desc_en" rows="3"><?php echo e(setting('footer_desc_en')); ?></textarea>
        </div>
        <div class="form-group">
          <label for="footer_rights_ar">نص الحقوق (عربي)</label>
          <input class="form-control" id="footer_rights_ar" name="footer_rights_ar" value="<?php echo e(setting('footer_rights_ar')); ?>">
        </div>
        <div class="form-group">
          <label for="footer_rights_en">نص الحقوق (إنجليزي)</label>
          <input class="form-control ltr" id="footer_rights_en" name="footer_rights_en" value="<?php echo e(setting('footer_rights_en')); ?>">
        </div>
        <div class="form-group">
          <label for="footer_made_ar">نص "صُنع في" (عربي)</label>
          <input class="form-control" id="footer_made_ar" name="footer_made_ar" value="<?php echo e(setting('footer_made_ar')); ?>">
        </div>
        <div class="form-group">
          <label for="footer_made_en">نص "صُنع في" (إنجليزي)</label>
          <input class="form-control ltr" id="footer_made_en" name="footer_made_en" value="<?php echo e(setting('footer_made_en')); ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions" style="border:1px solid var(--line);border-radius:var(--radius)">
    <span class="muted"><i class="fa-solid fa-circle-info"></i> آخر تحديث يُسجَّل تلقائيًا في سجل النشاط.</span>
    <div class="right">
      <a class="btn btn-outline" href="index.php">إلغاء</a>
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات</button>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
