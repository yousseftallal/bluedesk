<?php
/**
 * Branches manager.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

/** Keep one active headquarters selected whenever active branches exist. */
function enforce_single_main_branch(?int $preferredId = null): bool
{
    $candidate = null;
    if ($preferredId !== null && $preferredId > 0) {
        $candidate = db_fetch('SELECT id FROM branches WHERE id = ? AND is_active = 1', [$preferredId]);
    }
    if (!$candidate) {
        $candidate = db_fetch('SELECT id FROM branches WHERE is_active = 1 AND is_main = 1 ORDER BY sort_order, id LIMIT 1');
    }
    if (!$candidate) {
        $candidate = db_fetch('SELECT id FROM branches WHERE is_active = 1 ORDER BY sort_order, id LIMIT 1');
    }
    if (!$candidate) {
        return false;
    }
    db_query('UPDATE branches SET is_main = IF(id = ?, 1, 0)', [(int) $candidate['id']]);
    return true;
}

$action = input('action');

if (is_post()) {
    require_csrf();

    if ($action === 'save') {
        $id = (int) input('id');
        $nameAr = input('name_ar');
        $nameEn = input('name_en');
        $addressAr = input('address_ar');
        $addressEn = input('address_en');
        $phone = input('phone');
        $email = input('email');
        $mapUrl = input('map_url');
        $mapEmbed = input('map_embed');
        $errors = [];

        if ($nameAr === '' || $nameEn === '' || $addressAr === '' || $addressEn === '') {
            $errors[] = 'اسم الفرع والعنوان مطلوبان باللغتين.';
        }
        if (mb_strlen($nameAr) > 190 || mb_strlen($nameEn) > 190 || mb_strlen($addressAr) > 320 || mb_strlen($addressEn) > 320) {
            $errors[] = 'تجاوز اسم الفرع أو عنوانه الحد المسموح.';
        }
        if ($phone !== '' && (mb_strlen($phone) > 40 || safe_tel($phone) === '')) {
            $errors[] = 'رقم هاتف الفرع غير صالح.';
        }
        if ($email !== '' && (mb_strlen($email) > 190 || !valid_email($email))) {
            $errors[] = 'البريد الإلكتروني للفرع غير صالح.';
        }
        if ($mapUrl !== '' && safe_http_url($mapUrl) === '') {
            $errors[] = 'رابط الخريطة يجب أن يكون رابط HTTP أو HTTPS صحيحًا.';
        }
        if ($mapEmbed !== '' && safe_map_embed($mapEmbed) === '') {
            $errors[] = 'رابط التضمين يجب أن يكون من خرائط Google.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
        } else {
            $id = (int) input('id');
            $isNew = $id <= 0;
            $isMain = isset($_POST['is_main']) ? 1 : 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($isMain && !$isActive) {
                flash('error', 'لا يمكن إيقاف الفرع الرئيسي قبل تعيين فرع نشط آخر كرئيسي.');
                redirect('branches.php');
            }
            try {
                db()->beginTransaction();
                if ($id > 0 && !db_fetch('SELECT id FROM branches WHERE id = ? FOR UPDATE', [$id])) {
                    throw new DomainException('الفرع المطلوب غير موجود.');
                }
                $data = [
                    $nameAr, $nameEn, $addressAr, $addressEn, $phone, $email, $mapUrl, $mapEmbed,
                    $isMain, (int) input('sort_order', '0'), $isActive,
                ];
                if ($id > 0) {
                    db_query(
                        'UPDATE branches SET name_ar=?, name_en=?, address_ar=?, address_en=?, phone=?, email=?, map_url=?, map_embed=?, is_main=?, sort_order=?, is_active=? WHERE id=?',
                        array_merge($data, [$id])
                    );
                    log_activity('تعديل فرع', $nameAr);
                } else {
                    db_query(
                        'INSERT INTO branches (name_ar, name_en, address_ar, address_en, phone, email, map_url, map_embed, is_main, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                        $data
                    );
                    $id = (int) db()->lastInsertId();
                    log_activity('إضافة فرع', $nameAr);
                }
                if (!enforce_single_main_branch($isMain ? $id : null)) {
                    throw new DomainException('يجب أن يبقى في الموقع فرع نشط واحد على الأقل.');
                }
                db()->commit();
                flash('success', $isNew ? 'تمت إضافة الفرع.' : 'تم حفظ بيانات الفرع.');
            } catch (Throwable $ex) {
                if (db()->inTransaction()) db()->rollBack();
                if ($ex instanceof DomainException) {
                    flash('error', $ex->getMessage());
                } else {
                    error_log('Branch save failed: ' . $ex->getMessage());
                    flash('error', 'تعذر حفظ بيانات الفرع. لم يتم تطبيق أي تغيير.');
                }
            }
        }
        redirect('branches.php');
    }

    if ($action === 'delete') {
        $id = (int) input('id');
        try {
            db()->beginTransaction();
            $branch = db_fetch('SELECT * FROM branches WHERE id = ? FOR UPDATE', [$id]);
            if (!$branch) throw new DomainException('الفرع المطلوب غير موجود.');
            db_query('DELETE FROM branches WHERE id = ?', [$id]);
            if (!enforce_single_main_branch()) {
                throw new DomainException('لا يمكن حذف آخر فرع نشط في الموقع.');
            }
            log_activity('حذف فرع', $branch['name_ar']);
            db()->commit();
            flash('success', 'تم حذف الفرع.');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            if ($ex instanceof DomainException) {
                flash('error', $ex->getMessage());
            } else {
                error_log('Branch delete failed: ' . $ex->getMessage());
                flash('error', 'تعذر حذف الفرع. لم يتم تطبيق أي تغيير.');
            }
        }
        redirect('branches.php');
    }

    if ($action === 'main') {
        $id = (int) input('id');
        try {
            db()->beginTransaction();
            if (!db_fetch('SELECT id FROM branches WHERE id = ? AND is_active = 1 FOR UPDATE', [$id]) || !enforce_single_main_branch($id)) {
                throw new DomainException('يجب اختيار فرع موجود ونشط.');
            }
            log_activity('تعيين الفرع الرئيسي', '#' . $id);
            db()->commit();
            flash('success', 'تم تعيين الفرع الرئيسي.');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            if ($ex instanceof DomainException) {
                flash('error', $ex->getMessage());
            } else {
                error_log('Main branch update failed: ' . $ex->getMessage());
                flash('error', 'تعذر تحديث الفرع الرئيسي. لم يتم تطبيق أي تغيير.');
            }
        }
        redirect('branches.php');
    }
}

$branches = db_fetch_all('SELECT * FROM branches ORDER BY is_main DESC, sort_order, id');
$edit = !empty($_GET['edit']) ? db_fetch('SELECT * FROM branches WHERE id = ?', [(int) $_GET['edit']]) : null;

$adminTitle = 'الفروع';
$adminSubtitle = 'المقر الرئيسي وفرع الدقي وبيانات الخرائط';
$adminActive = 'branches';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>إدارة الفروع</h1><p class="sub">تظهر الفروع في صفحة التواصل والفوتر وخريطة الموقع.</p></div>
  <div class="actions"><a class="btn btn-outline" href="<?php echo e(url('contact.php')); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> عرض صفحة التواصل</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2><?php echo $edit ? 'تعديل الفرع: ' . e($edit['name_ar']) : 'إضافة فرع جديد'; ?></h2></div>
  <form method="post" action="branches.php" data-loading>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="name_ar">اسم الفرع (عربي) <span class="req">*</span></label><input class="form-control" id="name_ar" name="name_ar" value="<?php echo e($edit['name_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="name_en">اسم الفرع (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="name_en" name="name_en" value="<?php echo e($edit['name_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="address_ar">العنوان (عربي) <span class="req">*</span></label><textarea class="form-control" id="address_ar" name="address_ar" rows="2" required><?php echo e($edit['address_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="address_en">العنوان (إنجليزي) <span class="req">*</span></label><textarea class="form-control ltr" id="address_en" name="address_en" rows="2" required><?php echo e($edit['address_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="phone">الهاتف</label><input class="form-control ltr" id="phone" name="phone" value="<?php echo e($edit['phone'] ?? setting('phone')); ?>"></div>
        <div class="form-group"><label for="email">البريد الإلكتروني</label><input class="form-control ltr" type="email" id="email" name="email" value="<?php echo e($edit['email'] ?? setting('email')); ?>"></div>
        <div class="form-group full"><label for="map_url">رابط الخريطة (يفتح في جوجل ماب)</label><input class="form-control ltr" id="map_url" name="map_url" value="<?php echo e($edit['map_url'] ?? ''); ?>" placeholder="https://www.google.com/maps/search/?api=1&query=..."></div>
        <div class="form-group full">
          <label for="map_embed">رابط تضمين الخريطة (Embed)</label>
          <input class="form-control ltr" id="map_embed" name="map_embed" value="<?php echo e($edit['map_embed'] ?? ''); ?>" placeholder="https://maps.google.com/maps?q=...&output=embed">
          <span class="hint">من جوجل ماب: مشاركة ← تضمين خريطة ← انسخ قيمة <code>src</code> فقط.</span>
        </div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($edit['sort_order'] ?? (count($branches) + 1)); ?>"></div>
        <div class="form-group">
          <label>الحالة</label>
          <label class="switch" style="margin-inline-end:1rem"><input type="checkbox" name="is_active" <?php echo (!$edit || (int) $edit['is_active']) ? 'checked' : ''; ?>> نشط</label>
          <label class="switch"><input type="checkbox" name="is_main" <?php echo !empty($edit['is_main']) ? 'checked' : ''; ?>> الفرع الرئيسي</label>
        </div>
      </div>
      <p class="help-box" style="margin-top:.6rem"><b>نصيحة:</b> تعيين فرع كرئيسي سيلغي تعيين أي فرع آخر تلقائيًا.</p>
    </div>
    <div class="form-actions">
      <?php if ($edit): ?><a class="btn btn-outline" href="branches.php">إلغاء التعديل</a><?php endif; ?>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?php echo $edit ? 'تحديث الفرع' : 'إضافة الفرع'; ?></button></div>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>الفروع الحالية (<?php echo count($branches); ?>)</h2></div>
  <div class="panel-body p0">
    <?php if (!$branches): ?>
      <div class="empty"><i class="fa-solid fa-location-dot"></i><p>لا توجد فروع بعد.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>الفرع</th><th>العنوان</th><th>الهاتف</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($branches as $row): ?>
              <tr>
                <td>
                  <div class="cell-title"><?php echo e($row['name_ar']); ?> <?php if ($row['is_main']): ?><span class="badge main">رئيسي</span><?php endif; ?></div>
                  <div class="cell-sub"><?php echo e($row['name_en']); ?></div>
                </td>
                <td class="muted" style="max-width:300px"><?php echo e($row['address_ar']); ?></td>
                <td><span dir="ltr"><?php echo e($row['phone'] ?: '—'); ?></span></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
                <td class="actions-cell">
                  <?php if (!$row['is_main']): ?>
                    <form method="post" action="branches.php" class="inline-form">
                      <?php echo csrf_field(); ?><input type="hidden" name="action" value="main"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                      <button class="btn btn-success btn-sm" type="submit" title="تعيين كرئيسي"><i class="fa-solid fa-house-flag"></i></button>
                    </form>
                  <?php endif; ?>
                  <a class="btn btn-outline btn-sm btn-icon" href="branches.php?edit=<?php echo (int) $row['id']; ?>" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="branches.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف فرع «<?php echo e($row['name_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
