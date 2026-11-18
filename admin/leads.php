<?php
require_once __DIR__ . '/includes/company_helpers.php';

if (is_post()) {
    require_csrf(); $action=input('action'); $id=(int)input('id');
    if ($action==='convert' && $id>0) {
        $lead=db_fetch('SELECT * FROM company_leads WHERE id=?',[$id]);
        if (!$lead) { company_flash_error('العميل المحتمل غير موجود.'); }
        elseif ($lead['lead_status']==='lost') { company_flash_error('لا يمكن تحويل فرصة مصنفة كمفقودة. غيّر حالتها أولًا إذا عادت للمسار.'); }
        elseif (!empty($lead['client_id'])) { flash('success','تم ربط هذا العميل المحتمل بملف عميل بالفعل.'); }
        else {
            db()->beginTransaction();
            try {
                db_query("INSERT INTO company_clients (name,company_name,email,phone,client_status,notes) VALUES (?,?,?,?, 'active',?)",[$lead['name'],$lead['company_name'],$lead['email'],$lead['phone'],'تم التحويل من عميل محتمل رقم '.$id.($lead['notes']?' — '.$lead['notes']:'')]);
                $clientId=(int)db()->lastInsertId(); db_query("UPDATE company_leads SET lead_status='won',client_id=? WHERE id=?",[$clientId,$id]);
                db()->commit(); company_activity('تحويل عميل محتمل إلى عميل',$lead['name']); flash('success','تم إنشاء ملف العميل وربطه بالعميل المحتمل. أضف مشروعه من قسم مشاريع الشركة.');
            } catch (Throwable $ex) { if(db()->inTransaction())db()->rollBack(); error_log('Lead conversion failed: '.$ex->getMessage()); company_flash_error('تعذر تحويل العميل المحتمل.'); }
        }
        redirect('leads.php');
    }
    $name=company_input('name',160); $company=company_input('company_name',190); $email=company_input('email',190); $phone=company_input('phone',40);
    $source=company_input('source',100); $status=company_enum(input('lead_status'),['new','contacted','qualified','proposal','won','lost'],'new');
    $value=company_decimal('estimated_value'); $follow=company_valid_date(company_input('next_follow_up',10)); $notes=company_input('notes',5000);
    if($name===''||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||$value===false||$follow===false){company_flash_error('راجع الاسم والبريد والقيمة المتوقعة وتاريخ المتابعة.');}
    else {
      if($id>0){db_query('UPDATE company_leads SET name=?,company_name=?,email=?,phone=?,source=?,lead_status=?,estimated_value=?,next_follow_up=?,notes=? WHERE id=?',[$name,$company?:null,$email?:null,$phone?:null,$source?:null,$status,$value,$follow,$notes?:null,$id]);company_activity('تعديل عميل محتمل',$name);flash('success','تم تحديث بيانات العميل المحتمل.');}
      else{db_query('INSERT INTO company_leads (name,company_name,email,phone,source,lead_status,estimated_value,next_follow_up,notes) VALUES (?,?,?,?,?,?,?,?,?)',[$name,$company?:null,$email?:null,$phone?:null,$source?:null,$status,$value,$follow,$notes?:null]);company_activity('إضافة عميل محتمل',$name);flash('success','تم إضافة العميل المحتمل.');}
      redirect('leads.php');
    }
}
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT)?:0; $lead=$editId?db_fetch('SELECT * FROM company_leads WHERE id=?',[$editId]):null;
$leads=db_fetch_all('SELECT l.*,c.name converted_name FROM company_leads l LEFT JOIN company_clients c ON c.id=l.client_id ORDER BY FIELD(l.lead_status,\'new\',\'contacted\',\'qualified\',\'proposal\',\'won\',\'lost\'),l.next_follow_up IS NULL,l.next_follow_up,l.id DESC');
$leadLabels=['new'=>'جديد','contacted'=>'تم التواصل','qualified'=>'مؤهل','proposal'=>'عرض سعر','won'=>'تم التعاقد','lost'=>'مفقود'];
$adminTitle='العملاء المحتملون'; $adminSubtitle='متابعة فرص البيع وتحويلها إلى عملاء'; $adminActive='leads'; require __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>العملاء المحتملون</h1><p class="sub">تابع الفرص ومواعيد التواصل وحوّل الفرصة الفائزة إلى ملف عميل.</p></div></div>
<form method="post" action="leads.php<?php echo $lead?'?edit='.(int)$lead['id']:''; ?>" data-loading><?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)($lead['id']??0); ?>">
<div class="panel"><div class="panel-head"><h2><?php echo $lead?'تعديل فرصة':'إضافة عميل محتمل'; ?></h2></div><div class="panel-body"><div class="form-grid">
<div class="form-group"><label>اسم جهة التواصل *</label><input class="form-control" name="name" maxlength="160" required value="<?php echo e($lead['name']??''); ?>"></div><div class="form-group"><label>الشركة</label><input class="form-control" name="company_name" maxlength="190" value="<?php echo e($lead['company_name']??''); ?>"></div>
<div class="form-group"><label>البريد</label><input class="form-control ltr" type="email" name="email" maxlength="190" value="<?php echo e($lead['email']??''); ?>"></div><div class="form-group"><label>الهاتف</label><input class="form-control ltr" name="phone" maxlength="40" value="<?php echo e($lead['phone']??''); ?>"></div>
<div class="form-group"><label>مصدر العميل المحتمل</label><input class="form-control" name="source" maxlength="100" placeholder="موقع، ترشيح، إعلان..." value="<?php echo e($lead['source']??''); ?>"></div><div class="form-group"><label>الحالة</label><select class="form-control" name="lead_status"><?php foreach($leadLabels as $key=>$label): ?><option value="<?php echo e($key); ?>" <?php echo ($lead['lead_status']??'new')===$key?'selected':''; ?>><?php echo e($label); ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>القيمة المتوقعة (ج.م)</label><input class="form-control ltr" type="number" min="0" step="0.01" name="estimated_value" value="<?php echo e($lead['estimated_value']??'0'); ?>"></div><div class="form-group"><label>تاريخ المتابعة القادمة</label><input class="form-control ltr" type="date" name="next_follow_up" value="<?php echo e($lead['next_follow_up']??''); ?>"></div>
<div class="form-group full"><label>ملاحظات</label><textarea class="form-control" name="notes" rows="2" maxlength="5000"><?php echo e($lead['notes']??''); ?></textarea></div>
</div></div><div class="form-actions"><a class="btn btn-outline" href="leads.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit">حفظ العميل المحتمل</button></div></div></div></form>
<div class="panel"><div class="panel-head"><h2>مسار المبيعات (<?php echo count($leads); ?>)</h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>الفرصة</th><th>التواصل</th><th>الحالة</th><th>القيمة</th><th>المتابعة</th><th></th></tr></thead><tbody>
<?php foreach($leads as $row): ?><tr><td><div class="cell-title"><?php echo e($row['name']); ?></div><div class="cell-sub"><?php echo e($row['company_name']?:''); ?> · <?php echo e($row['source']?:''); ?></div></td><td><?php echo e($row['email']?:'—'); ?><div class="cell-sub" dir="ltr"><?php echo e($row['phone']?:''); ?></div></td><td><?php echo e($leadLabels[$row['lead_status']]); ?><?php if($row['converted_name']): ?><div class="cell-sub">عميل: <?php echo e($row['converted_name']); ?></div><?php endif; ?></td><td><?php echo e(company_money($row['estimated_value'])); ?></td><td><?php echo e($row['next_follow_up']?:'—'); ?></td><td class="actions-cell"><a class="btn btn-outline btn-sm" href="leads.php?edit=<?php echo (int)$row['id']; ?>">تعديل</a><?php if($row['lead_status']!=='lost'&&!$row['client_id']): ?><form method="post" class="inline-form"><?php echo csrf_field(); ?><input type="hidden" name="action" value="convert"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"><button class="btn btn-primary btn-sm" type="submit" data-confirm="إنشاء ملف عميل من هذه الفرصة؟">تحويل لعميل</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$leads): ?><tr><td colspan="6" class="muted">لا توجد فرص مسجلة.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
