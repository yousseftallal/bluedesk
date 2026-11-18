# BlueDesk — دليل التثبيت والتشغيل

موقع شركة بلوديسك (BlueDesk) — موقع تعريفي ثنائي اللغة (عربي/إنجليزي) مع لوحة تحكم كاملة مبنية بـ PHP وMySQL.

---

## المتطلبات

- **PHP 8.0+** مع الإضافات: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, و`gd` مفعّل به WebP (`imagewebp`); إضافة `dom` مطلوبة لعرض HTML الآمن في الصفحات المخصصة (بدونها يظهر المحتوى كنص آمن).
- **MySQL 5.7+** أو **MariaDB 10.4+**
- **Apache 2.4** مع `mod_rewrite` و`mod_headers` (أو Nginx مع الإعدادات المكافئة)
- صلاحية كتابة على مجلد `uploads/`

تم التطوير والاختبار على XAMPP: Apache 2.4.58 + PHP 8.2.12 + MariaDB 10.4.32.

---

## هيكل المشروع

```
bluedesk/
├── admin/              لوحة التحكم (عربي RTL)
│   ├── assets/         CSS/JS الخاص باللوحة
│   ├── includes/       header/footer
│   └── *.php           صفحات الإدارة
├── assets/             الشعارات والأيقونات
├── config/             إعدادات التطبيق (config.php) — محمي
├── css/ js/            ملفات الموقع العام
├── database/           schema.sql — محمي
├── includes/           db, functions, auth, header, footer
├── scripts/            أدوات تهيئة تعمل من سطر الأوامر فقط
├── uploads/            الصور المرفوعة (يمنع تنفيذ السكربتات)
├── index.php about.php services.php portfolio.php contact.php
├── page.php            الصفحات المخصصة
├── blog.php blog-post.php  أرشيف المقالات وصفحة المقال
├── sitemap.php feed.php    خريطة الموقع وخلاصة RSS الديناميكيتان
└── robots.txt
```

---

## خطوات التثبيت

### 1) نسخ الملفات
انسخ المشروع إلى مجلد الموقع، مثال XAMPP:
`C:\xampp\htdocs\bluedesk`

### 2) إنشاء قاعدة البيانات
من phpMyAdmin أو سطر الأوامر:

```bash
mysql -u root -p -e "CREATE DATABASE bluedesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p bluedesk < database/schema.sql
```

> `schema.sql` ينشئ الجداول وبيانات المحتوى الأولية، ولا يضع حساب مدير أو كلمة مرور افتراضية.

> **تنبيه للبيانات القائمة:** استورد `schema.sql` مرة واحدة في قاعدة بيانات جديدة وفارغة فقط. لا تعِد تشغيله على موقع قائم؛ التحديثات تُطبَّق بملفات `database/migrate_*.sql` حتى لا تتأثر بيانات الموقع.

إذا كنت تحدّث تثبيتًا قائمًا، نفّذ ملفات الترحيل المناسبة مثل `database/migrate_session_version.sql` و`database/migrate_relationships.sql` و`database/migrate_blog.sql` و`database/migrate_project_demo_button.sql` قبل تشغيل النسخة الجديدة. ترحيل زر تجربة المشروع يضيف إعداد إظهار الزر لكل مشروع دون حذف بيانات الموقع، ويمكن إعادة تشغيله بأمان.

### 3) ضبط الإعدادات
```bash
copy config\config.example.php config\config.php
```
ثم عدّل `config/config.php`:

```php
'app' => [
    'name'     => 'BlueDesk',
    'base_url' => 'https://www.bluedeskgroup.net', // بدون / في النهاية
    'debug'    => false,
    'upload_dir' => APP_ROOT . '/uploads',
    'upload_url' => '/uploads',
    'max_upload' => 4 * 1024 * 1024,
],
'db' => [
    'host' => '127.0.0.1',
    'name' => 'bluedesk',
    'user' => 'bluedesk_user',
    'pass' => 'كلمة-مرور-قوية',
],
```

- في الإنتاج: `debug => false` دائمًا.
- في الإنتاج: اترك `force_https => true`، وثبّت شهادة HTTPS. إذا كان الخادم خلف Proxy موثوق ينهي TLS، اضبط `trust_proxy_headers => true` فقط بعد التأكد أن الـ Proxy يستبدل `X-Forwarded-Proto` ولا يسمح بالوصول المباشر إلى التطبيق.
- استخدم مستخدم MySQL مخصصًا بصلاحيات محدودة بدل `root`.
- عند النشر على HTTPS حدّث `base_url` إلى `https://...`.
- نطاق الإنتاج مضبوط على `https://www.bluedeskgroup.net`؛ اضبط DNS للنطاقين `bluedeskgroup.net` و`www.bluedeskgroup.net` وشهادة SSL قبل فتح الموقع لمحركات البحث.
- الصور الجديدة بصيغ JPG/PNG وGIF الثابتة تتحول عند الرفع إلى WebP، وتُحفظ الأصول في `uploads/.originals/` المحمي من الوصول المباشر. صور GIF المتحركة تبقى GIF للحفاظ على الحركة. ارفع الصور من لوحة الإدارة؛ يجب أن يدعم PHP GD صيغة WebP.
- لتحويل الصور القديمة الموجودة في `uploads/` مرة واحدة بعد النشر، شغّل من مجلد الموقع: `php scripts/generate-upload-image-variants.php`.
- `sitemap.php` و`robots.txt` مهيآن تلقائيًا للدومين وخريطة المحتوى؛ الخريطة تتحدث مع الصفحات المنشورة والمشاريع والمقالات والصور المرتبطة بها. الصور التي لم تُربط بمحتوى منشور تبقى خاصة بمكتبة الوسائط ولا تُدرج في خريطة الموقع. أرسل `https://www.bluedeskgroup.net/sitemap.php` مرة واحدة إلى Google Search Console بعد تفعيل DNS وSSL؛ محرك البحث يقرر وقت إعادة الزحف ولا توجد طريقة مضمونة لفهرسة فورية.

### 4) صلاحيات المجلدات
تأكد أن `uploads/` قابلة للكتابة:
```bash
chmod -R 775 uploads
```
(على Windows/XAMPP لا يلزم عادةً.)

### 5) إنشاء حساب المدير
لا توجد كلمة مرور افتراضية. بعد استيراد قاعدة البيانات، افتح PowerShell أو موجه الأوامر داخل مجلد المشروع وشغّل:

```powershell
C:\xampp\php\php.exe scripts\setup-admin.php
```

إذا لم يوجد مدير، ستُنشأ بيانات أول مدير. إذا كان هناك مديرون بالفعل، اختر اسم المستخدم الذي تريد إعادة تعيين كلمة مروره. ستظهر كلمة مرور عشوائية مؤقتة مرة واحدة في الطرفية؛ احفظها ثم غيّرها من **ملفي الشخصي** بعد تسجيل الدخول.

- رابط الدخول: `https://موقعك/admin/login.php`
- الحد الأدنى لكلمة المرور الجديدة: 12 حرفًا.

### 6) الإعداد الأولي
من لوحة التحكم:
1. **الإعدادات** — اسم الموقع، الشعارات، الألوان، الهاتف، البريد، السوشيال، الفوتر.
2. **الفروع** — المقر الرئيسي (قليوب) وفرع الدقي، مع روابط وخرائط جوجل.
3. **الصفحة الرئيسية** — الهيرو، الأقسام، الإحصائيات، خطوات العمل.
4. **SEO** — العناوين والأوصاف الافتراضية، ثم SEO لكل صفحة.
5. **التنقل والقوائم** — روابط الهيدر والفوتر.

---

## لوحة التحكم — الوحدات

| الوحدة | الوظيفة |
|---|---|
| لوحة التحكم | إحصائيات، مخطط الرسائل (6 أشهر)، آخر النشاطات |
| الصفحة الرئيسية | الهيرو، لماذا بلوديسك، من نحن، العناوين، الإحصائيات، خطوات العمل |
| الفروع | إضافة/تعديل/حذف الفروع، تعيين الرئيسي، خرائط جوجل |
| الصفحات | الصفحات الأساسية + صفحات مخصصة (محتوى HTML وSEO) |
| المدونة | مقالات عربية/إنجليزية، وسوم وتصنيفات، مسودات وجدولة نشر، صور آمنة، وSEO لكل مقال |
| الخدمات | إدارة كاملة مع الأيقونات والألوان والمميزات والصور |
| الأعمال | معرض المشاريع وروابط تجربة يمكن إظهار زرها أو إخفاؤه لكل مشروع |
| آراء العملاء | الشهادات والتقييمات |
| الفريق | أعضاء الفريق |
| المزايا والقيم | بطاقات Why وقيم الشركة |
| التنقل والقوائم | Header + روابط الفوتر |
| الرسائل | صندوق وارد مع بحث وتصفية وتغيير الحالة والرد بالبريد |
| الوسائط | رفع/حذف الصور ونسخ الروابط |
| المستخدمون | مدير نظام / محرر محتوى (مدير فقط) |
| سجل النشاط | كل العمليات (مدير فقط) |
| الإعدادات | الهوية، الألوان، التواصل، السوشيال، الفوتر (مدير فقط) |

**الصلاحيات:** مدير النظام يصل لكل شيء. محرر المحتوى يدير المحتوى والرسائل والوسائط، ولا يصل إلى الإعدادات/المستخدمين/القوائم/SEO/سجل النشاط.

---

## الأمان المطبق

- استعلامات **PDO Prepared Statements** في كل مكان.
- حماية **CSRF** لكل نماذج POST (بما فيها تسجيل الخروج).
- تشفير كلمات المرور بـ `password_hash` (bcrypt).
- جلسات آمنة: `HttpOnly`, `SameSite=Lax`, وضع جلسة صارم وتجديد المعرّف عند الدخول، إبطال بعد 30 دقيقة من الخمول، و`secure` على HTTPS؛ تغيير كلمة المرور يبطل الجلسات الأخرى.
- حد لمحاولات الدخول: 5 محاولات فاشلة لكل IP خلال 15 دقيقة.
- رفع الصور: التحقق من MIME وبنية الصورة وأبعادها وحجمها الفعلي، أسماء عشوائية، ومنع تنفيذ السكربتات في `uploads/`.
- تنقية HTML الصفحات المخصصة، ومنع الروابط ذات البروتوكولات الخطرة.
- حجب الوصول المباشر إلى `.git/`, `config/`, `database/`, `includes/`, `scripts/` والملفات المخفية.
- رؤوس أمان: سياسة CSP، `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` وHSTS على HTTPS.
- حد لنموذج التواصل: 5 رسائل/ساعة لكل IP + حقل Honeypot + CSRF.

### توصيات إضافية للإنتاج
- شهادة SSL/HTTPS إلزامية + تحويل دائم من HTTP.
- نسخ احتياطي يومي لقاعدة البيانات ومجلد `uploads/`:
  ```bash
  mysqldump -u bluedesk_user -p bluedesk > backup-$(date +%F).sql
  ```
- إبقاء `debug=false` ومراجعة سجلات الأخطاء.
- تحديث PHP وMySQL بانتظام.

---

## Nginx (بديل Apache)

```nginx
server {
    listen 80;
    server_name bluedeskgroup.net www.bluedeskgroup.net;
    root /var/www/bluedesk;
    index index.php;

    location / {
        rewrite ^/blog/?$ /blog.php last;
        rewrite ^/blog/category/([a-z0-9-]+)/?$ /blog.php?category=$1 last;
        rewrite ^/blog/([a-z0-9-]+)/?$ /blog-post.php?slug=$1 last;
        try_files $uri $uri/ =404;
        rewrite ^/(index|about|services|portfolio|contact)\.html$ /$1.php permanent;
    }

    location ~* ^/(config|database|includes|scripts|\.git)(/|$) { deny all; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(sql|log|ini|md|env|bak|dist)$ { deny all; }
    location ~* ^/uploads/.*\.(php[0-9]?|phtml|phar|cgi|pl|py|sh|asp|aspx|jsp)$ { deny all; }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
    add_header Content-Security-Policy "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; frame-src https://www.google.com https://maps.google.com; connect-src 'self'" always;
    # Add HSTS only inside the HTTPS server block after HTTPS is working.
    # add_header Strict-Transport-Security "max-age=15552000" always;
}
```

على Nginx لا تُقرأ ملفات `.htaccess`، لذلك يجب تطبيق قواعد الحجب والرؤوس الأمنية في إعدادات الخادم نفسه.

---

## استكشاف الأخطاء

| المشكلة | الحل |
|---|---|
| خطأ 500 | راجع `C:\xampp\apache\logs\error.log` أو سجل PHP. مؤقتًا فعّل `debug => true`. |
| خطأ اتصال قاعدة البيانات | تأكد من تشغيل MySQL وصحة بيانات `config/config.php`. |
| الصور لا تظهر بعد الرفع | تأكد من صلاحيات `uploads/` وصحة `upload_url`. |
| الدخول لا يعمل | شغّل `C:\xampp\php\php.exe scripts\setup-admin.php` لإنشاء أول مدير أو إعادة تعيين كلمة مرور مدير قائم. |
| MySQL لا يعمل في XAMPP | `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini` |

---

## ملاحظات

- الموقع العام عربي افتراضيًا مع تبديل فوري للإنجليزية عبر `data-ar` / `data-en` في `js/main.js`.
- خريطة الموقع الديناميكية: `/sitemap.php` (حدّث رابطها في `robots.txt` عند تغيير الدومين).
- المدونة تستخدم روابط صديقة لمحركات البحث `/blog` و`/blog/slug` و`/blog/category/slug`، وخلاصة RSS على `/feed.php`.
- عند تحديث موقع قائم، نفّذ `database/migrate_blog.sql` مرة واحدة لإضافة جداول المدونة وإظهار روابطها في القوائم.
- عند تحديث موقع قائم، نفّذ `database/migrate_project_demo_button.sql` لإضافة إعداد زر تجربة المشروع لكل مشروع.
- روابط `.html` القديمة تُحوَّل تلقائيًا إلى `.php` عبر `.htaccess`.
- رقم التواصل الرسمي: **01505768013** — المقر: قليوب البلد — الفرع: الدقي، الجيزة.
