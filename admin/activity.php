<?php
/**
 * Activity log (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = ' WHERE (a.action LIKE ? OR a.details LIKE ? OR u.name LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}

$total = (int) (db_fetch('SELECT COUNT(*) AS c FROM activity_log a LEFT JOIN users u ON u.id = a.user_id' . $where, $params)['c'] ?? 0);
$pager = paginate($total, 30);
$logs = db_fetch_all(
    'SELECT a.*, u.name, u.username FROM activity_log a LEFT JOIN users u ON u.id = a.user_id' . $where .
    ' ORDER BY a.id DESC LIMIT ' . $pager['per'] . ' OFFSET ' . $pager['offset'],
    $params
);

$adminTitle = 'سجل النشاط';
$adminSubtitle = 'كل العمليات التي تمت داخل لوحة التحكم';
$adminActive = 'activity';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>سجل النشاط</h1><p class="sub"><?php echo $total; ?> عملية مسجلة.</p></div>
</div>

<div class="panel">
  <form method="get" action="activity.php" class="filters">
    <div class="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input class="form-control" type="search" name="q" value="<?php echo e($q); ?>" placeholder="ابحث في العمليات أو المستخدمين...">
    </div>
    <button class="btn btn-outline btn-sm" type="submit">بحث</button>
    <?php if ($q !== ''): ?><a class="btn btn-outline btn-sm" href="activity.php">مسح</a><?php endif; ?>
  </form>
  <div class="panel-body p0">
    <?php if (!$logs): ?>
      <div class="empty"><i class="fa-solid fa-clock-rotate-left"></i><p>لا توجد عمليات مسجلة.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>العملية</th><th>التفاصيل</th><th>المستخدم</th><th>IP</th><th>التاريخ</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $row): ?>
              <tr>
                <td class="cell-title"><?php echo e($row['action']); ?></td>
                <td class="muted" style="max-width:380px"><?php echo e($row['details'] ?: '—'); ?></td>
                <td><?php echo e($row['name'] ?: 'النظام'); ?> <?php if ($row['username']): ?><span class="cell-sub" dir="ltr">@<?php echo e($row['username']); ?></span><?php endif; ?></td>
                <td class="muted" dir="ltr"><?php echo e($row['ip'] ?: '—'); ?></td>
                <td class="muted" style="white-space:nowrap"><?php echo localized_datetime_html((string)$row['created_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php echo pagination_links($pager, $q !== '' ? ['q' => $q] : []); ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
