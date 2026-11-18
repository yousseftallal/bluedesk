<?php
/**
 * Inquiry detail.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$inquiry = db_fetch('SELECT * FROM inquiries WHERE id = ?', [$id]);
if (!$inquiry) {
    flash('error', 'الرسالة غير موجودة.');
    redirect('inquiries.php');
}

$statusLabels = ['new' => 'جديدة', 'read' => 'مقروءة', 'replied' => 'تم الرد', 'archived' => 'مؤرشفة'];

if (is_post()) {
    require_csrf();
    if (input('action') === 'delete') {
        $connection = null;
        try {
            $connection = db();
            $connection->beginTransaction();
            $deleted = db_query('DELETE FROM inquiries WHERE id = ?', [$id])->rowCount();
            if ($deleted !== 1) {
                $connection->rollBack();
                flash('error', 'لم يتم حذف الرسالة؛ ربما حُذفت بالفعل من نافذة أخرى.');
                redirect('inquiries.php');
            }
            log_activity('حذف رسالة', $inquiry['name']);
            $connection->commit();
            flash('success', 'تم حذف الرسالة نهائيًا.');
        } catch (Throwable $exception) {
            if ($connection instanceof PDO && $connection->inTransaction()) $connection->rollBack();
            error_log('Inquiry delete failed for ID ' . $id . ': ' . $exception->getMessage());
            flash('error', 'تعذر حذف الرسالة بسبب خطأ في قاعدة البيانات. حدّث الصفحة وحاول مجددًا؛ إذا استمرت المشكلة سيظهر سببها في سجل أخطاء السيرفر.');
            redirect('inquiry-view.php?id=' . $id);
        }
        redirect('inquiries.php');
    }
    if (input('action') === 'status' && isset($statusLabels[input('status')])) {
        db_query('UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?', [input('status'), $id]);
        log_activity('تحديث حالة رسالة', $inquiry['name'] . ' → ' . $statusLabels[input('status')]);
        flash('success', 'تم تحديث حالة الرسالة.');
        redirect('inquiry-view.php?id=' . $id);
    }
}

if ($inquiry['status'] === 'new') {
    db_query("UPDATE inquiries SET status = 'read', updated_at = NOW() WHERE id = ?", [$id]);
    $inquiry['status'] = 'read';
}

$mailSubject = rawurlencode('رد على استفسارك — ' . setting('site_name', 'BlueDesk'));
$mailBody = rawurlencode("مرحبًا {$inquiry['name']},\n\nشكرًا لتواصلك مع " . setting('site_name', 'BlueDesk') . ".\n\n");
$mailto = 'mailto:' . $inquiry['email'] . '?subject=' . $mailSubject . '&body=' . $mailBody;

$adminTitle = 'تفاصيل الرسالة';
$adminSubtitle = 'من ' . $inquiry['name'];
$adminActive = 'inquiries';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1><?php echo e($inquiry['name']); ?></h1>
    <p class="sub">وصلت في <?php echo localized_datetime_html((string)$inquiry['created_at']); ?> · <span class="badge <?php echo e($inquiry['status']); ?>"><?php echo e($statusLabels[$inquiry['status']] ?? $inquiry['status']); ?></span></p>
  </div>
  <div class="actions">
    <a class="btn btn-outline" href="inquiries.php"><i class="fa-solid fa-arrow-right"></i> رجوع للرسائل</a>
    <a class="btn btn-primary" href="<?php echo e($mailto); ?>"><i class="fa-solid fa-reply"></i> رد عبر البريد</a>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>نص الرسالة</h2></div>
    <div class="panel-body">
      <div style="background:#f8f9fb;border:1px solid var(--line);border-radius:12px;padding:1.2rem;white-space:pre-wrap;line-height:1.9"><?php echo e($inquiry['message']); ?></div>
      <?php if (!empty($inquiry['service'])): ?>
        <p class="muted" style="margin-top:1rem"><i class="fa-solid fa-layer-group"></i> الخدمة المطلوبة: <b><?php echo e($inquiry['service']); ?></b></p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="panel">
      <div class="panel-head"><h2>بيانات التواصل</h2></div>
      <div class="panel-body p0">
        <table class="table" style="min-width:0">
          <tbody>
            <tr><th style="width:40%">الاسم</th><td><?php echo e($inquiry['name']); ?></td></tr>
            <tr><th>البريد</th><td><a dir="ltr" style="color:var(--blue-dark);font-weight:600" href="mailto:<?php echo e($inquiry['email']); ?>"><?php echo e($inquiry['email']); ?></a></td></tr>
            <tr><th>الهاتف</th><td>
              <?php if (!empty($inquiry['phone'])): ?>
              <div class="inquiry-phone-line inquiry-phone-detail">
                <span class="inquiry-phone-country" data-phone="<?php echo e($inquiry['phone']); ?>" data-phone-country="<?php echo e($inquiry['phone_country'] ?? ''); ?>" aria-label="الدولة ورمز الاتصال"></span>
                <a dir="ltr" style="color:var(--blue-dark);font-weight:600" href="tel:<?php echo e($inquiry['phone']); ?>"><?php echo e($inquiry['phone']); ?></a>
              </div>
              <?php else: ?>—<?php endif; ?>
            </td></tr>
            <tr><th>الشركة</th><td><?php echo e($inquiry['company'] ?: '—'); ?></td></tr>
            <tr><th>الخدمة</th><td><?php echo e($inquiry['service'] ?: '—'); ?></td></tr>
            <tr><th>IP</th><td class="muted"><?php echo e($inquiry['ip'] ?: '—'); ?></td></tr>
            <tr><th>المتصفح</th><td class="muted" style="font-size:.72rem"><?php echo e($inquiry['user_agent'] ?: '—'); ?></td></tr>
            <tr><th>التحديث الأخير</th><td class="muted"><?php echo localized_datetime_html($inquiry['updated_at'] ? (string)$inquiry['updated_at'] : null); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>إجراءات</h2></div>
      <div class="panel-body">
        <form method="post" action="inquiry-view.php?id=<?php echo $id; ?>" data-loading>
          <?php echo csrf_field(); ?><input type="hidden" name="action" value="status">
          <div class="form-group">
            <label for="status">حالة الرسالة</label>
            <select class="form-control" id="status" name="status">
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?php echo e($key); ?>" <?php echo $inquiry['status'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk"></i> تحديث الحالة</button>
        </form>
        <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
        <form method="post" action="inquiry-view.php?id=<?php echo $id; ?>" data-loading>
          <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete">
          <button class="btn btn-danger btn-block" type="submit" data-confirm="حذف الرسالة نهائيًا؟"><i class="fa-solid fa-trash"></i> حذف الرسالة</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
