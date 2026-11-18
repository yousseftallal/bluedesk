<?php
/**
 * Admin layout header.
 * Set before include: $adminTitle, $adminSubtitle (optional), $adminActive, $adminActions (optional HTML).
 */
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$adminTitle    = $adminTitle ?? 'لوحة التحكم';
$adminSubtitle = $adminSubtitle ?? '';
$adminActive   = $adminActive ?? '';
$adminActions  = $adminActions ?? '';
$adminUser     = current_user();
$adminStyleVersion = (string) (filemtime(__DIR__ . '/../assets/admin.css') ?: 1);

try {
    $newInquiries = (int) (db_fetch("SELECT COUNT(*) AS c FROM inquiries WHERE status = 'new'")['c'] ?? 0);
} catch (Throwable $ex) {
    $newInquiries = 0;
}

$navGroups = [
    [
        'label' => 'نظرة عامة',
        'items' => [
            ['key' => 'dashboard', 'url' => 'index.php', 'icon' => 'fa-gauge-high', 'label' => 'لوحة التحكم'],
        ],
    ],
    [
        'label' => 'إدارة المحتوى',
        'items' => [
            ['key' => 'homepage',   'url' => 'homepage.php',   'icon' => 'fa-house',              'label' => 'الصفحة الرئيسية'],
            ['key' => 'branches',   'url' => 'branches.php',   'icon' => 'fa-location-dot',       'label' => 'الفروع'],
            ['key' => 'pages',      'url' => 'pages.php',      'icon' => 'fa-file-lines',         'label' => 'الصفحات'],
            ['key' => 'blog',       'url' => 'blog.php',       'icon' => 'fa-newspaper',          'label' => 'المدونة'],
            ['key' => 'blog_categories', 'url' => 'blog-categories.php', 'icon' => 'fa-folder-open', 'label' => 'تصنيفات المدونة'],
            ['key' => 'services',   'url' => 'services.php',   'icon' => 'fa-layer-group',        'label' => 'الخدمات'],
            ['key' => 'projects',   'url' => 'projects.php',   'icon' => 'fa-briefcase',          'label' => 'الأعمال'],
            ['key' => 'testimonials','url' => 'testimonials.php','icon' => 'fa-quote-right',      'label' => 'آراء العملاء'],
            ['key' => 'team',       'url' => 'team.php',       'icon' => 'fa-user-group',         'label' => 'الفريق'],
            ['key' => 'features',   'url' => 'features.php',   'icon' => 'fa-star',               'label' => 'المزايا والقيم'],
            ['key' => 'nav',        'url' => 'nav.php',        'icon' => 'fa-bars-staggered',     'label' => 'التنقل والقوائم'],
            ['key' => 'seo',        'url' => 'seo.php',        'icon' => 'fa-magnifying-glass-chart', 'label' => 'تحسين محركات البحث'],
        ],
    ],
    [
        'label' => 'التواصل والنظام',
        'items' => [
            ['key' => 'inquiries', 'url' => 'inquiries.php', 'icon' => 'fa-envelope-open-text', 'label' => 'رسائل العملاء', 'count' => $newInquiries],
            ['key' => 'media',     'url' => 'media.php',     'icon' => 'fa-images',             'label' => 'مكتبة الوسائط'],
            ['key' => 'users',     'url' => 'users.php',     'icon' => 'fa-user-shield',        'label' => 'المستخدمون'],
            ['key' => 'activity',  'url' => 'activity.php',  'icon' => 'fa-clock-rotate-left',  'label' => 'سجل النشاط'],
            ['key' => 'settings',  'url' => 'settings.php',  'icon' => 'fa-gear',               'label' => 'الإعدادات'],
        ],
    ],
    [
        'label' => 'إدارة الشركة',
        'items' => [
            ['key' => 'company', 'url' => 'company.php', 'icon' => 'fa-chart-pie', 'label' => 'ملخص الشركة'],
            ['key' => 'employees', 'url' => 'employees.php', 'icon' => 'fa-users', 'label' => 'الموظفون'],
            ['key' => 'clients', 'url' => 'clients.php', 'icon' => 'fa-user-tie', 'label' => 'العملاء'],
            ['key' => 'leads', 'url' => 'leads.php', 'icon' => 'fa-bullseye', 'label' => 'العملاء المحتملون'],
            ['key' => 'company_projects', 'url' => 'company-projects.php', 'icon' => 'fa-diagram-project', 'label' => 'مشاريع الشركة'],
            ['key' => 'finance', 'url' => 'finance.php', 'icon' => 'fa-money-bill-transfer', 'label' => 'الحسابات والحركات'],
            ['key' => 'capital', 'url' => 'capital.php', 'icon' => 'fa-coins', 'label' => 'رأس المال'],
            ['key' => 'payroll', 'url' => 'payroll.php', 'icon' => 'fa-money-check-dollar', 'label' => 'المرتبات'],
            ['key' => 'taxes', 'url' => 'taxes.php', 'icon' => 'fa-file-invoice-dollar', 'label' => 'الضرائب'],
            ['key' => 'company_reports', 'url' => 'company-reports.php', 'icon' => 'fa-chart-column', 'label' => 'الأرباح والخسائر'],
        ],
    ],
];

$flashes = get_flashes();
$initial = mb_substr($adminUser['name'] ?? 'م', 0, 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo e($adminTitle); ?> — <?php echo e(setting('site_name', 'BlueDesk')); ?> Admin</title>
<link rel="icon" href="<?php echo e(asset(setting('favicon', 'assets/favicon.svg'))); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="assets/admin.css?v=<?php echo e(rawurlencode($adminStyleVersion)); ?>">
<?php if ($adminActive === 'inquiries'): ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/intl-tel-input/css/intlTelInput.min.css') . '?v=29.5.8'); ?>">
<?php endif; ?>
</head>
<body>
<div class="layout">

  <aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <img src="<?php echo e(asset(setting('logo_white', 'assets/logo-white.svg'))); ?>" alt="<?php echo e(setting('site_name', 'BlueDesk')); ?>">
      <div>
        <small>لوحة التحكم</small>
      </div>
    </div>

    <nav class="sidebar-nav" aria-label="القائمة الرئيسية">
      <?php foreach ($navGroups as $group): ?>
        <?php
        $visibleItems = array_filter($group['items'], static function (array $item): bool {
            return is_admin() || (!in_array($item['key'], ['nav', 'seo', 'users', 'activity', 'settings'], true)
                && !in_array($item['key'], ['company', 'employees', 'clients', 'leads', 'company_projects', 'finance', 'capital', 'payroll', 'taxes', 'company_reports'], true));
        });
        if (!$visibleItems) continue;
        ?>
        <div class="nav-group-label"><?php echo e($group['label']); ?></div>
        <?php foreach ($visibleItems as $item): ?>
          <a href="<?php echo e($item['url']); ?>" class="<?php echo $adminActive === $item['key'] ? 'active' : ''; ?>">
            <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
            <span><?php echo e($item['label']); ?></span>
            <?php if (!empty($item['count'])): ?>
              <span class="badge-count"><?php echo (int) $item['count']; ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="nav-group-label">الحساب</div>
      <a href="profile.php" class="<?php echo $adminActive === 'profile' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-gear"></i><span>ملفي الشخصي</span>
      </a>
    </nav>

    <div class="sidebar-foot">
      <span class="dots" aria-hidden="true">
        <span style="background:#e53935"></span><span style="background:#fbbc04"></span><span style="background:#34a853"></span><span style="background:#4285f4"></span>
      </span>
      <?php echo e(setting('site_name', 'BlueDesk')); ?> &copy; <span data-local-current-year><?php echo date('Y'); ?></span>
    </div>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="sidebar-toggle" type="button" aria-label="فتح القائمة" aria-expanded="false" aria-controls="adminSidebar">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="page-title">
        <?php echo e($adminTitle); ?>
        <?php if ($adminSubtitle): ?><small><?php echo e($adminSubtitle); ?></small><?php endif; ?>
      </div>
      <div class="topbar-actions">
        <a class="btn btn-outline btn-sm" href="<?php echo e(url('index.php')); ?>" target="_blank" rel="noopener">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> زيارة الموقع
        </a>
        <a class="user-chip" href="profile.php" title="ملفي الشخصي">
          <span class="avatar"><?php echo e($initial); ?></span>
          <span>
            <span class="u-name"><?php echo e($adminUser['name'] ?? ''); ?></span>
            <span class="role"><?php echo $adminUser['role'] === 'admin' ? 'مدير النظام' : 'محرر محتوى'; ?></span>
          </span>
        </a>
        <form method="post" action="logout.php" class="inline-form">
          <?php echo csrf_field(); ?>
          <button class="btn btn-outline btn-icon" type="submit" title="تسجيل الخروج" aria-label="تسجيل الخروج">
            <i class="fa-solid fa-right-from-bracket"></i>
          </button>
        </form>
      </div>
    </header>

    <main class="content">
      <?php foreach ($flashes as $flashRow): ?>
        <div class="admin-flash <?php echo $flashRow['type'] === 'success' ? 'success' : 'error'; ?>" role="status">
          <i class="fa-solid <?php echo $flashRow['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
          <span><?php echo e($flashRow['message']); ?></span>
        </div>
      <?php endforeach; ?>
