<?php
require_once __DIR__ . '/includes/company_helpers.php';

if (is_post()) {
    require_csrf(); $id=(int)input('id'); $name=company_input('name',160); $company=company_input('company_name',190);
    $email=company_input('email',190); $phone=company_input('phone',40); $address=company_input('address',500);
    $tax=company_input('tax_number',80); $status=company_enum(input('client_status'),['active','inactive'],'active'); $notes=company_input('notes',5000);
    if ($name==='' || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))) { company_flash_error('أدخل اسم العميل وبريدًا إلكترونيًا صحيحًا.'); }
    else {
        if ($id>0) {
            db_query('UPDATE company_clients SET name=?,company_name=?,email=?,phone=?,address=?,tax_number=?,client_status=?,notes=? WHERE id=?',[$name,$company?:null,$email?:null,$phone?:null,$address?:null,$tax?:null,$status,$notes?:null,$id]);
            company_activity('تعديل عميل',$name); flash('success','تم تحديث بيانات العميل.');
        } else {
            db_query('INSERT INTO company_clients (name,company_name,email,phone,address,tax_number,client_status,notes) VALUES (?,?,?,?,?,?,?,?)',[$name,$company?:null,$email?:null,$phone?:null,$address?:null,$tax?:null,$status,$notes?:null]);
            company_activity('إضافة عميل',$name); flash('success','تم إضافة العميل.');
        }
        redirect('clients.php');
    }
}
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT)?:0; $client=$editId?db_fetch('SELECT * FROM company_clients WHERE id=?',[$editId]):null;
$clients=db_fetch_all('SELECT c.*,COUNT(DISTINCT p.id) project_count FROM company_clients c LEFT JOIN company_projects p ON p.client_id=c.id GROUP BY c.id ORDER BY c.created_at DESC');
$adminTitle='العملاء'; $adminSubtitle='قاعدة بيانات العملاء ومشاريعهم'; $adminActive='clients'; require __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>العملاء</h1><p class="sub">ملفات التواصل والبيانات الضريبية وربط كل عميل بمشاريعه.</p></div></div>
<form method="post" action="clients.php<?php echo $client?'?edit='.(int)$client['id']:''; ?>" data-loading>
<?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)($client['id']??0); ?>">
<div class="panel"><div class="panel-head"><h2><?php echo $client?'تعديل العميل':'إضافة عميل'; ?></h2></div><div class="panel-body"><div class="form-grid">
<div class="form-group"><label>اسم جهة التواصل *</label><input class="form-control" name="name" maxlength="160" required value="<?php echo e($client['name']??''); ?>"></div>
<div class="form-group"><label>الشركة</label><input class="form-control" name="company_name" maxlength="190" value="<?php echo e($client['company_name']??''); ?>"></div>
<div class="form-group"><label>البريد الإلكتروني</label><input class="form-control ltr" type="email" name="email" maxlength="190" value="<?php echo e($client['email']??''); ?>"></div>
<div class="form-group"><label>الهاتف</label><input class="form-control ltr" name="phone" maxlength="40" value="<?php echo e($client['phone']??''); ?>"></div>
<div class="form-group"><label>الرقم الضريبي</label><input class="form-control ltr" name="tax_number" maxlength="80" value="<?php echo e($client['tax_number']??''); ?>"></div>
<div class="form-group"><label>الحالة</label><select class="form-control" name="client_status"><option value="active" <?php echo ($client['client_status']??'active')==='active'?'selected':''; ?>>نشط</option><option value="inactive" <?php echo ($client['client_status']??'')==='inactive'?'selected':''; ?>>غير نشط</option></select></div>
<div class="form-group full"><label>العنوان</label><input class="form-control" name="address" maxlength="500" value="<?php echo e($client['address']??''); ?>"></div>
<div class="form-group full"><label>ملاحظات</label><textarea class="form-control" name="notes" rows="2" maxlength="5000"><?php echo e($client['notes']??''); ?></textarea></div>
</div></div><div class="form-actions"><a class="btn btn-outline" href="clients.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit">حفظ العميل</button></div></div></div></form>
<div class="panel"><div class="panel-head"><h2>العملاء (<?php echo count($clients); ?>)</h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>العميل</th><th>التواصل</th><th>المشاريع</th><th>الحالة</th><th></th></tr></thead><tbody>
<?php foreach($clients as $row): ?><tr><td><div class="cell-title"><?php echo e($row['name']); ?></div><div class="cell-sub"><?php echo e($row['company_name']?:'—'); ?></div></td><td><?php echo e($row['email']?:'—'); ?><div class="cell-sub" dir="ltr"><?php echo e($row['phone']?:''); ?></div></td><td><a class="link" href="company-projects.php?client=<?php echo (int)$row['id']; ?>"><?php echo (int)$row['project_count']; ?> مشروع</a></td><td><?php echo $row['client_status']==='active'?'نشط':'غير نشط'; ?></td><td><a class="btn btn-outline btn-sm" href="clients.php?edit=<?php echo (int)$row['id']; ?>">تعديل</a> <a class="btn btn-outline btn-sm" href="company-projects.php?client=<?php echo (int)$row['id']; ?>">المشاريع</a></td></tr><?php endforeach; ?>
<?php if(!$clients): ?><tr><td colspan="5" class="muted">لا يوجد عملاء. سجّل أول عميل من النموذج أعلاه.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
