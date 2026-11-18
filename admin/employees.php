<?php
require_once __DIR__ . '/includes/company_helpers.php';

if (is_post()) {
    require_csrf();
    $action = input('action'); $id = (int) input('id');
    if ($action === 'delete' && $id > 0) {
        if (db_count('company_payroll', 'employee_id = ?', [$id]) > 0 || db_count('company_transactions', 'employee_id = ?', [$id]) > 0) {
            company_flash_error('لا يمكن حذف موظف له سجلات مالية. غيّر حالته إلى غير نشط للحفاظ على السجلات.');
        } else {
            db_query('DELETE FROM company_employees WHERE id = ?', [$id]);
            company_activity('حذف موظف', 'رقم الموظف ' . $id); flash('success', 'تم حذف سجل الموظف.');
        }
        redirect('employees.php');
    }
    $name=company_input('full_name',160); $job=company_input('job_title',120); $email=company_input('email',190); $phone=company_input('phone',40);
    $hire=company_valid_date(company_input('hire_date',10)); $salary=company_decimal('base_salary');
    $status=company_enum(input('employment_status'),['active','leave','inactive'],'active'); $notes=company_input('notes',5000);
    if ($name==='' || $job==='' || $hire===false || $salary===false || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))) {
        company_flash_error('راجع الاسم والمسمى الوظيفي والبريد وتاريخ التعيين والراتب.');
    } else {
        try {
            if ($id>0) {
                db_query('UPDATE company_employees SET full_name=?,job_title=?,email=?,phone=?,hire_date=?,base_salary=?,employment_status=?,notes=? WHERE id=?',[$name,$job,$email?:null,$phone?:null,$hire,$salary,$status,$notes?:null,$id]);
                company_activity('تعديل موظف',$name); flash('success','تم تحديث بيانات الموظف.');
            } else {
                db_query('INSERT INTO company_employees (full_name,job_title,email,phone,hire_date,base_salary,employment_status,notes) VALUES (?,?,?,?,?,?,?,?)',[$name,$job,$email?:null,$phone?:null,$hire,$salary,$status,$notes?:null]);
                company_activity('إضافة موظف',$name); flash('success','تم إضافة الموظف.');
            }
            redirect('employees.php');
        } catch (PDOException $ex) { company_flash_error('تعذر الحفظ. تحقق من عدم تكرار البريد الإلكتروني.'); }
    }
}
$editId = filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT) ?: 0;
$employee = $editId ? db_fetch('SELECT * FROM company_employees WHERE id=?',[$editId]) : null;
$employees = db_fetch_all('SELECT * FROM company_employees ORDER BY FIELD(employment_status,\'active\',\'leave\',\'inactive\'),full_name');
$adminTitle='الموظفون'; $adminSubtitle='ملفات فريق الشركة والرواتب الأساسية'; $adminActive='employees'; require __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>الموظفون</h1><p class="sub">إدارة بيانات الفريق وحالات العمل والرواتب الأساسية.</p></div></div>
<form method="post" action="employees.php<?php echo $employee?'?edit='.(int)$employee['id']:''; ?>" data-loading>
<?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)($employee['id']??0); ?>">
<div class="panel"><div class="panel-head"><h2><?php echo $employee?'تعديل بيانات الموظف':'إضافة موظف'; ?></h2></div><div class="panel-body"><div class="form-grid">
  <div class="form-group"><label>الاسم الكامل *</label><input class="form-control" name="full_name" maxlength="160" value="<?php echo e($employee['full_name']??''); ?>" required></div>
  <div class="form-group"><label>المسمى الوظيفي *</label><input class="form-control" name="job_title" maxlength="120" value="<?php echo e($employee['job_title']??''); ?>" required></div>
  <div class="form-group"><label>البريد الإلكتروني</label><input class="form-control ltr" type="email" name="email" maxlength="190" value="<?php echo e($employee['email']??''); ?>"></div>
  <div class="form-group"><label>الهاتف</label><input class="form-control ltr" name="phone" maxlength="40" value="<?php echo e($employee['phone']??''); ?>"></div>
  <div class="form-group"><label>تاريخ التعيين</label><input class="form-control ltr" type="date" name="hire_date" value="<?php echo e($employee['hire_date']??''); ?>"></div>
  <div class="form-group"><label>الراتب الأساسي الشهري (ج.م)</label><input class="form-control ltr" type="number" name="base_salary" min="0" step="0.01" value="<?php echo e($employee['base_salary']??'0.00'); ?>" required></div>
  <div class="form-group"><label>الحالة</label><select class="form-control" name="employment_status"><option value="active" <?php echo ($employee['employment_status']??'active')==='active'?'selected':''; ?>>نشط</option><option value="leave" <?php echo ($employee['employment_status']??'')==='leave'?'selected':''; ?>>إجازة</option><option value="inactive" <?php echo ($employee['employment_status']??'')==='inactive'?'selected':''; ?>>غير نشط</option></select></div>
  <div class="form-group full"><label>ملاحظات</label><textarea class="form-control" name="notes" rows="2" maxlength="5000"><?php echo e($employee['notes']??''); ?></textarea></div>
</div></div><div class="form-actions"><a class="btn btn-outline" href="employees.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ الموظف</button></div></div></div></form>
<div class="panel"><div class="panel-head"><h2>سجل الموظفين (<?php echo count($employees); ?>)</h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>الموظف</th><th>التواصل</th><th>التعيين</th><th>الراتب الأساسي</th><th>الحالة</th><th></th></tr></thead><tbody>
<?php foreach($employees as $row): ?><tr><td><div class="cell-title"><?php echo e($row['full_name']); ?></div><div class="cell-sub"><?php echo e($row['job_title']); ?></div></td><td><?php echo e($row['email']?:'—'); ?><div class="cell-sub" dir="ltr"><?php echo e($row['phone']?:''); ?></div></td><td><?php echo e($row['hire_date']?:'—'); ?></td><td><?php echo e(company_money($row['base_salary'])); ?></td><td><?php echo e(['active'=>'نشط','leave'=>'إجازة','inactive'=>'غير نشط'][$row['employment_status']]); ?></td><td class="actions-cell"><a class="btn btn-outline btn-sm" href="employees.php?edit=<?php echo (int)$row['id']; ?>">تعديل</a><form method="post" class="inline-form"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="حذف سجل الموظف؟">حذف</button></form></td></tr><?php endforeach; ?>
<?php if(!$employees): ?><tr><td colspan="6" class="muted">لا يوجد موظفون مسجلون.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
