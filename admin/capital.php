<?php
require_once __DIR__ . '/includes/company_helpers.php';

if (is_post()) {
    require_csrf();
    $action = input('action');
    if ($action === 'opening') {
        $amount = company_decimal('amount');
        $date = company_valid_date(company_input('entry_date', 10));
        $description = company_input('description', 255);
        $opening = db_fetch("SELECT * FROM company_capital_entries WHERE entry_type='opening' ORDER BY id LIMIT 1");
        if ($amount === false || $amount <= 0 || $date === false || $date === null || $description === '') {
            company_flash_error('أدخل مبلغًا موجبًا وتاريخًا ووصفًا صحيحًا لرأس المال الافتتاحي.');
        } elseif ($opening) {
            db_query('UPDATE company_capital_entries SET amount=?,entry_date=?,description=? WHERE id=? AND entry_type=\'opening\' AND voided_at IS NULL', [$amount, $date, $description, $opening['id']]);
            company_activity('تعديل رأس المال الافتتاحي', company_money($amount) . ' من ' . $date);
            flash('success', 'تم تحديث رأس المال الافتتاحي.');
            redirect('capital.php');
        } else {
            db_query("INSERT INTO company_capital_entries (entry_type,amount,entry_date,description,reference_no,created_by) VALUES ('opening',?,?,?,?,?)", [$amount, $date, $description, 'OPENING-2026-07', (int) current_user()['id']]);
            company_activity('تسجيل رأس المال الافتتاحي', company_money($amount) . ' من ' . $date);
            flash('success', 'تم تسجيل رأس المال الافتتاحي.');
            redirect('capital.php');
        }
    } elseif ($action === 'void') {
        $id = (int) input('id');
        $reason = company_input('void_reason', 255);
        $row = db_fetch('SELECT entry_type,amount,description,voided_at FROM company_capital_entries WHERE id=?', [$id]);
        if (!$row || $row['entry_type'] === 'opening') {
            company_flash_error('لا يمكن إلغاء رأس المال الافتتاحي من هذه الشاشة.');
        } elseif ($row['voided_at']) {
            company_flash_error('تم إلغاء حركة رأس المال من قبل.');
        } elseif (mb_strlen($reason, 'UTF-8') < 3) {
            company_flash_error('اكتب سببًا مختصرًا لإلغاء الحركة.');
        } else {
            db_query('UPDATE company_capital_entries SET voided_at=NOW(),void_reason=? WHERE id=? AND voided_at IS NULL AND entry_type<>\'opening\'', [$reason, $id]);
            company_activity('إلغاء حركة رأس مال', $row['description'] . ' — ' . company_money($row['amount']) . ' — ' . $reason);
            flash('success', 'تم إلغاء الحركة مع الاحتفاظ بسجلها.');
        }
        redirect('capital.php');
    } elseif ($action === 'save') {
        $id = (int) input('id');
        $type = company_enum(input('entry_type'), ['contribution', 'draw'], 'contribution');
        $amount = company_decimal('amount');
        $date = company_valid_date(company_input('entry_date', 10));
        $description = company_input('description', 255);
        $reference = company_input('reference_no', 120);
        $existing = $id > 0 ? db_fetch('SELECT id,entry_type,voided_at FROM company_capital_entries WHERE id=?', [$id]) : null;
        if (($id > 0 && (!$existing || $existing['entry_type'] === 'opening' || $existing['voided_at'])) || $amount === false || $amount <= 0 || $date === false || $date === null || $description === '') {
            company_flash_error('راجع نوع الحركة والمبلغ والتاريخ والوصف.');
        } else {
            if ($id > 0) {
                db_query('UPDATE company_capital_entries SET entry_type=?,amount=?,entry_date=?,description=?,reference_no=? WHERE id=? AND voided_at IS NULL AND entry_type<>\'opening\'', [$type, $amount, $date, $description, $reference ?: null, $id]);
                company_activity('تعديل حركة رأس مال', $description . ' — ' . company_money($amount));
                flash('success', 'تم تحديث حركة رأس المال.');
            } else {
                db_query('INSERT INTO company_capital_entries (entry_type,amount,entry_date,description,reference_no,created_by) VALUES (?,?,?,?,?,?)', [$type, $amount, $date, $description, $reference ?: null, (int) current_user()['id']]);
                company_activity('إضافة حركة رأس مال', $description . ' — ' . company_money($amount));
                flash('success', 'تم تسجيل حركة رأس المال.');
            }
            redirect('capital.php');
        }
    }
}

$opening = db_fetch("SELECT * FROM company_capital_entries WHERE entry_type='opening' AND voided_at IS NULL ORDER BY id LIMIT 1");
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
$entry = $editId ? db_fetch("SELECT * FROM company_capital_entries WHERE id=? AND entry_type<>'opening' AND voided_at IS NULL", [$editId]) : null;
$capital = db_fetch("SELECT COALESCE(SUM(CASE WHEN entry_type='draw' THEN -amount ELSE amount END),0) AS balance FROM company_capital_entries WHERE entry_date<=CURDATE() AND voided_at IS NULL");
$entries = db_fetch_all('SELECT * FROM company_capital_entries ORDER BY entry_date DESC,id DESC LIMIT 300');
$typeLabels = ['opening' => 'رأس مال افتتاحي', 'contribution' => 'إضافة رأس مال', 'draw' => 'سحب مالك'];

$adminTitle = 'رأس المال'; $adminSubtitle = 'الرصيد الافتتاحي ومساهمات الملاك والسحوبات'; $adminActive = 'capital';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>رأس المال</h1><p class="sub">رأس المال والمساهمات والسحوبات تظهر منفصلة عن إيرادات النشاط، لذلك لا ترفع صافي الربح.</p></div></div>
<div class="stats-grid"><div class="stat-card k-blue"><div class="stat-icon"><i class="fa-solid fa-coins"></i></div><div><b><?php echo e(company_money($capital['balance'] ?? 0)); ?></b><span>صافي رأس المال المسجل حتى اليوم</span></div></div></div>
<div class="panel"><div class="panel-head"><h2>رأس المال عند بدء التسجيل</h2></div><div class="panel-body"><p class="hint">المسجل حاليًا: <?php echo e(company_money($opening['amount'] ?? 120000)); ?> اعتبارًا من <?php echo e($opening['entry_date'] ?? '2026-07-01'); ?>. غيّر المبلغ أو تاريخ البداية إذا كانت بياناتك مختلفة.</p></div>
<form method="post" action="capital.php" data-loading><?php echo csrf_field(); ?><input type="hidden" name="action" value="opening"><div class="panel-body"><div class="form-grid">
<div class="form-group"><label>رأس المال الافتتاحي (ج.م)</label><input class="form-control ltr" type="number" name="amount" min="0.01" step="0.01" required value="<?php echo e($opening['amount'] ?? '120000.00'); ?>"></div>
<div class="form-group"><label>تاريخ بداية التسجيل</label><input class="form-control ltr" type="date" name="entry_date" required value="<?php echo e($opening['entry_date'] ?? '2026-07-01'); ?>"></div>
<div class="form-group full"><label>الوصف</label><input class="form-control" name="description" maxlength="255" required value="<?php echo e($opening['description'] ?? 'رأس المال الافتتاحي عند بدء استخدام النظام'); ?>"></div>
</div></div><div class="form-actions"><span class="hint">هذا المبلغ لا يدخل ضمن الإيرادات أو الأرباح.</span><div class="right"><button class="btn btn-primary" type="submit">حفظ رأس المال</button></div></div></form></div>
<form method="post" action="capital.php<?php echo $entry ? '?edit=' . (int)$entry['id'] : ''; ?>" data-loading><?php echo csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int)($entry['id'] ?? 0); ?>"><div class="panel"><div class="panel-head"><h2><?php echo $entry ? 'تعديل حركة رأس مال' : 'تسجيل مساهمة أو سحب'; ?></h2></div><div class="panel-body"><div class="form-grid">
<div class="form-group"><label>نوع الحركة</label><select class="form-control" name="entry_type"><option value="contribution" <?php echo ($entry['entry_type'] ?? 'contribution') === 'contribution' ? 'selected' : ''; ?>>إضافة رأس مال</option><option value="draw" <?php echo ($entry['entry_type'] ?? '') === 'draw' ? 'selected' : ''; ?>>سحب مالك</option></select></div>
<div class="form-group"><label>المبلغ (ج.م)</label><input class="form-control ltr" type="number" name="amount" min="0.01" step="0.01" required value="<?php echo e($entry['amount'] ?? ''); ?>"></div>
<div class="form-group"><label>التاريخ</label><input class="form-control ltr" type="date" name="entry_date" required value="<?php echo e($entry['entry_date'] ?? date('Y-m-d')); ?>"></div>
<div class="form-group"><label>رقم مرجعي</label><input class="form-control ltr" name="reference_no" maxlength="120" value="<?php echo e($entry['reference_no'] ?? ''); ?>"></div>
<div class="form-group full"><label>الوصف *</label><input class="form-control" name="description" maxlength="255" required value="<?php echo e($entry['description'] ?? ''); ?>"></div>
</div></div><div class="form-actions"><a class="btn btn-outline" href="capital.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit">حفظ الحركة</button></div></div></div></form>
<div class="panel"><div class="panel-head"><h2>سجل رأس المال</h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>التاريخ والوصف</th><th>نوع الحركة</th><th>القيمة</th><th>المرجع</th><th>الحالة</th><th></th></tr></thead><tbody>
<?php foreach ($entries as $row): ?><tr<?php echo $row['voided_at'] ? ' style="opacity:.58"' : ''; ?>><td><div class="cell-title"><?php echo e($row['description']); ?></div><div class="cell-sub"><?php echo e($row['entry_date']); ?><?php if ($row['voided_at']): ?> · سبب الإلغاء: <?php echo e($row['void_reason']); ?><?php endif; ?></div></td><td><?php echo e($typeLabels[$row['entry_type']]); ?></td><td><?php echo $row['entry_type'] === 'draw' ? '− ' : '+ '; ?><?php echo e(company_money($row['amount'])); ?></td><td><?php echo e($row['reference_no'] ?: '—'); ?></td><td><?php echo $row['voided_at'] ? 'ملغاة' : 'سارية'; ?></td><td class="actions-cell"><?php if (!$row['voided_at'] && $row['entry_type'] !== 'opening'): ?><a class="btn btn-outline btn-sm" href="capital.php?edit=<?php echo (int)$row['id']; ?>">تعديل</a><form method="post" class="inline-form" data-void-confirm="اكتب سبب إلغاء حركة رأس المال:"><?php echo csrf_field(); ?><input type="hidden" name="action" value="void"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"><input type="hidden" name="void_reason"><button class="btn btn-danger btn-sm" type="submit">إلغاء</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$entries): ?><tr><td colspan="6" class="muted">لا توجد حركات رأس مال مسجلة.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
