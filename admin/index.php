<?php
/**
 * Admin dashboard.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$counts = [
    'inquiries'  => (int) (db_fetch('SELECT COUNT(*) AS c FROM inquiries')['c'] ?? 0),
    'new'        => (int) (db_fetch("SELECT COUNT(*) AS c FROM inquiries WHERE status = 'new'")['c'] ?? 0),
    'month'      => (int) (db_fetch("SELECT COUNT(*) AS c FROM inquiries WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")['c'] ?? 0),
    'services'   => (int) (db_fetch('SELECT COUNT(*) AS c FROM services WHERE is_active = 1')['c'] ?? 0),
    'projects'   => (int) (db_fetch('SELECT COUNT(*) AS c FROM projects WHERE is_active = 1')['c'] ?? 0),
    'pages'      => (int) (db_fetch('SELECT COUNT(*) AS c FROM pages')['c'] ?? 0),
    'media'      => (int) (db_fetch('SELECT COUNT(*) AS c FROM media')['c'] ?? 0),
    'users'      => (int) (db_fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0),
];

$latest = db_fetch_all(
    "SELECT id, name, email, service, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 5"
);

$activity = is_admin() ? db_fetch_all(
    'SELECT a.action, a.details, a.created_at, u.name FROM activity_log a
     LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 7'
) : [];

$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $label = date('m/Y', strtotime("-$i months"));
    $monthly[$key] = ['label' => $label, 'count' => 0];
}
$rows = db_fetch_all(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
     FROM inquiries WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 MONTH), '%Y-%m-01')
     GROUP BY ym"
);
foreach ($rows as $row) {
    if (isset($monthly[$row['ym']])) {
        $monthly[$row['ym']]['count'] = (int) $row['c'];
    }
}
$maxCount = max(1, max(array_column($monthly, 'count')));

$statusLabels = ['new' => 'جديدة', 'read' => 'مقروءة', 'replied' => 'تم الرد', 'archived' => 'مؤرشفة'];

$adminTitle = 'لوحة التحكم';
$adminSubtitle = 'نظرة سريعة على نشاط الموقع والرسائل';
$adminActive = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>مرحبًا، <?php echo e(explode(' ', current_user()['name'])[0]); ?> 👋</h1>
    <p class="sub">هذه أحدث الإحصائيات والرسائل الواردة خلال الفترة الأخيرة.</p>
  </div>
  <div class="actions">
    <a class="btn btn-outline" href="inquiries.php"><i class="fa-solid fa-envelope-open-text"></i> الرسائل</a>
    <a class="btn btn-primary" href="service-edit.php"><i class="fa-solid fa-plus"></i> خدمة جديدة</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card k-blue">
    <div class="stat-icon"><i class="fa-solid fa-envelope"></i></div>
    <div><b><?php echo $counts['inquiries']; ?></b><span>إجمالي الرسائل</span></div>
  </div>
  <div class="stat-card k-red">
    <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
    <div><b><?php echo $counts['new']; ?></b><span>رسائل جديدة</span></div>
  </div>
  <div class="stat-card k-green">
    <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
    <div><b><?php echo $counts['services']; ?></b><span>خدمات نشطة</span></div>
  </div>
  <div class="stat-card k-yellow">
    <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
    <div><b><?php echo $counts['projects']; ?></b><span>أعمال منشورة</span></div>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head">
      <h2>الرسائل خلال آخر 6 أشهر</h2>
      <span class="muted"><?php echo $counts['month']; ?> رسالة هذا الشهر</span>
    </div>
    <div class="panel-body">
      <div class="chart" role="img" aria-label="مخطط الرسائل الشهرية">
        <?php $colors = ['', ' c-green', ' c-yellow', ' c-red', ' c-green', '']; $ci = 0; ?>
        <?php foreach ($monthly as $m): ?>
          <div class="bar">
            <b><?php echo (int) $m['count']; ?></b>
            <div class="fill<?php echo $colors[$ci % count($colors)]; ?>" style="height: <?php echo max(5, (int) round($m['count'] / $maxCount * 100)); ?>%"></div>
            <small><?php echo e($m['label']); ?></small>
          </div>
          <?php $ci++; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if (is_admin()): ?><div class="panel">
    <div class="panel-head">
      <h2>آخر النشاطات</h2>
      <a class="link" href="activity.php">عرض الكل</a>
    </div>
    <div class="panel-body p0">
      <?php if (!$activity): ?>
        <div class="empty"><i class="fa-solid fa-clock-rotate-left"></i><p>لا يوجد نشاط مسجل بعد.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table" style="min-width:0">
            <tbody>
              <?php foreach ($activity as $item): ?>
                <tr>
                  <td>
                    <div class="cell-title"><?php echo e($item['action']); ?></div>
                    <div class="cell-sub"><?php echo e($item['name'] ?: 'النظام'); ?> · <?php echo localized_datetime_html((string)$item['created_at']); ?></div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div><?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>أحدث الرسائل الواردة</h2>
    <a class="link" href="inquiries.php">إدارة الرسائل</a>
  </div>
  <div class="panel-body p0">
    <?php if (!$latest): ?>
      <div class="empty"><i class="fa-solid fa-inbox"></i><p>لا توجد رسائل بعد. ستظهر هنا رسائل نموذج التواصل.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>الاسم</th><th>البريد</th><th>الخدمة</th><th>الحالة</th><th>التاريخ</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($latest as $row): ?>
              <tr>
                <td class="cell-title"><?php echo e($row['name']); ?></td>
                <td><span dir="ltr"><?php echo e($row['email']); ?></span></td>
                <td><?php echo e($row['service'] ?: '—'); ?></td>
                <td><span class="badge <?php echo e($row['status']); ?>"><?php echo e($statusLabels[$row['status']] ?? $row['status']); ?></span></td>
                <td class="muted"><?php echo localized_datetime_html((string)$row['created_at']); ?></td>
                <td><a class="btn btn-outline btn-sm" href="inquiry-view.php?id=<?php echo (int) $row['id']; ?>">عرض</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="grid-3">
  <a class="panel" href="media.php" style="margin:0">
    <div class="panel-head"><h2><i class="fa-solid fa-images" style="color:var(--blue);margin-inline-end:.4rem"></i> مكتبة الوسائط</h2><span class="link"><?php echo $counts['media']; ?></span></div>
  </a>
  <a class="panel" href="pages.php" style="margin:0">
    <div class="panel-head"><h2><i class="fa-solid fa-file-lines" style="color:var(--green);margin-inline-end:.4rem"></i> الصفحات</h2><span class="link"><?php echo $counts['pages']; ?></span></div>
  </a>
  <?php if (is_admin()): ?>
  <a class="panel" href="users.php" style="margin:0">
    <div class="panel-head"><h2><i class="fa-solid fa-user-shield" style="color:var(--yellow-dark);margin-inline-end:.4rem"></i> المستخدمون</h2><span class="link"><?php echo $counts['users']; ?></span></div>
  </a>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
