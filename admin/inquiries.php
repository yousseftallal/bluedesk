<?php
/**
 * Inquiries inbox.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$statusLabels = ['new' => 'جديدة', 'read' => 'مقروءة', 'replied' => 'تم الرد', 'archived' => 'مؤرشفة'];

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'delete') {
        $connection = null;
        try {
            $connection = db();
            $row = db_fetch('SELECT name FROM inquiries WHERE id = ?', [$id]);
            if (!$row) {
                flash('error', 'الرسالة غير موجودة أو تم حذفها بالفعل. حدّث القائمة.');
            } else {
                $connection->beginTransaction();
                $deleted = db_query('DELETE FROM inquiries WHERE id = ?', [$id])->rowCount();
                if ($deleted !== 1) {
                    $connection->rollBack();
                    flash('error', 'لم يتم حذف الرسالة. حدّث القائمة وحاول مرة أخرى.');
                } else {
                    log_activity('حذف رسالة', $row['name']);
                    $connection->commit();
                    flash('success', 'تم حذف الرسالة نهائيًا.');
                }
            }
        } catch (Throwable $exception) {
            if ($connection instanceof PDO && $connection->inTransaction()) $connection->rollBack();
            error_log('Inquiry delete failed for ID ' . $id . ': ' . $exception->getMessage());
            flash('error', 'تعذر حذف الرسالة بسبب خطأ في قاعدة البيانات. حدّث الصفحة وحاول مجددًا؛ إذا استمرت المشكلة سيظهر سببها في سجل أخطاء السيرفر.');
        }
    } elseif (input('action') === 'status') {
        $newStatus = input('status');
        if (isset($statusLabels[$newStatus])) {
            db_query('UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $id]);
            flash('success', 'تم تحديث حالة الرسالة.');
        }
    }
    $back = 'inquiries.php';
    $qs = array_filter(['status' => $_GET['status'] ?? '', 'q' => $_GET['q'] ?? '', 'p' => $_GET['p'] ?? '']);
    if ($qs) {
        $back .= '?' . http_build_query($qs);
    }
    redirect($back);
}

$status = $_GET['status'] ?? '';
$q = trim((string) ($_GET['q'] ?? ''));
$where = [];
$params = [];
if (isset($statusLabels[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR message LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) (db_fetch('SELECT COUNT(*) AS c FROM inquiries' . $whereSql, $params)['c'] ?? 0);
$pager = paginate($total, 15);
$inquiries = db_fetch_all(
    'SELECT * FROM inquiries' . $whereSql . ' ORDER BY created_at DESC LIMIT ' . $pager['per'] . ' OFFSET ' . $pager['offset'],
    $params
);
$query = array_filter(['status' => $status, 'q' => $q]);

$adminTitle = 'رسائل العملاء';
$adminSubtitle = 'الرسائل الواردة من نموذج التواصل';
$adminActive = 'inquiries';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>صندوق الرسائل</h1><p class="sub"><?php echo $total; ?> رسالة<?php echo $status ? ' بحالة «' . e($statusLabels[$status]) . '»' : ''; ?></p></div>
</div>

<div class="panel">
  <form method="get" action="inquiries.php" class="filters">
    <div class="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input class="form-control" type="search" name="q" value="<?php echo e($q); ?>" placeholder="ابحث بالاسم، البريد، الهاتف أو نص الرسالة...">
    </div>
    <select class="form-control" name="status" data-auto-submit>
      <option value="">كل الحالات</option>
      <?php foreach ($statusLabels as $key => $label): ?>
        <option value="<?php echo e($key); ?>" <?php echo $status === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-filter"></i> تصفية</button>
    <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-outline btn-sm" href="inquiries.php">مسح الفلاتر</a><?php endif; ?>
  </form>

  <div class="panel-body p0">
    <?php if (!$inquiries): ?>
      <div class="empty"><i class="fa-solid fa-inbox"></i><p>لا توجد رسائل مطابقة.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>المرسل</th><th>بيانات التواصل</th><th>الخدمة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($inquiries as $row): ?>
              <tr>
                <td>
                  <div class="cell-title"><?php echo e($row['name']); ?></div>
                  <div class="cell-sub"><?php echo e($row['company'] ?: 'فرد'); ?></div>
                </td>
                <td>
                  <div class="cell-sub" dir="ltr" style="text-align:start"><?php echo e($row['email']); ?></div>
                  <?php if (!empty($row['phone'])): ?>
                  <div class="inquiry-phone-line">
                    <span class="inquiry-phone-country" data-phone="<?php echo e($row['phone']); ?>" data-phone-country="<?php echo e($row['phone_country'] ?? ''); ?>" aria-label="الدولة ورمز الاتصال"></span>
                    <a class="cell-sub" dir="ltr" href="tel:<?php echo e($row['phone']); ?>"><?php echo e($row['phone']); ?></a>
                  </div>
                  <?php else: ?><div class="cell-sub" dir="ltr" style="text-align:start">—</div><?php endif; ?>
                </td>
                <td><?php echo e($row['service'] ?: '—'); ?></td>
                <td><span class="badge <?php echo e($row['status']); ?>"><?php echo e($statusLabels[$row['status']] ?? $row['status']); ?></span></td>
                <td class="muted" style="white-space:nowrap"><?php echo localized_datetime_html((string)$row['created_at']); ?></td>
                <td class="actions-cell">
                  <a class="btn btn-outline btn-sm" href="inquiry-view.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-eye"></i> عرض</a>
                  <form method="post" action="inquiries.php<?php echo $query ? '?' . e(http_build_query($query + ['p' => $pager['page']])) : ''; ?>" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف رسالة «<?php echo e($row['name']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php echo pagination_links($pager, $query); ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
