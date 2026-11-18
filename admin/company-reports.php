<?php
require_once __DIR__ . '/includes/company_helpers.php';
[$from,$to]=company_period();
$movement=db_fetch("SELECT
 COALESCE(SUM(CASE WHEN transaction_kind='income' THEN amount ELSE 0 END),0) revenue,
 COALESCE(SUM(CASE WHEN transaction_kind='income' AND payment_status='pending' THEN amount ELSE 0 END),0) receivables,
 COALESCE(SUM(CASE WHEN transaction_kind='expense' THEN amount ELSE 0 END),0) expenses,
 COALESCE(SUM(CASE WHEN transaction_kind='purchase' THEN amount ELSE 0 END),0) purchases,
 COALESCE(SUM(CASE WHEN transaction_kind IN ('expense','purchase') AND payment_status='pending' THEN amount ELSE 0 END),0) payables,
 COALESCE(SUM(CASE WHEN transaction_kind IN ('expense','purchase') THEN tax_amount ELSE 0 END),0) input_tax,
 COALESCE(SUM(CASE WHEN transaction_kind='income' THEN tax_amount ELSE 0 END),0) output_tax
 FROM company_transactions WHERE transaction_date BETWEEN ? AND ? AND voided_at IS NULL",[$from,$to]);
$pay=db_fetch("SELECT COALESCE(SUM(base_salary+allowances+employer_contributions),0) payroll_cost,
 COALESCE(SUM(CASE WHEN payment_status='pending' THEN net_pay ELSE 0 END),0) payroll_due
 FROM company_payroll WHERE period_month>=DATE_FORMAT(?, '%Y-%m-01') AND period_month<=DATE_FORMAT(?, '%Y-%m-01')",[$from,$to]);
$tax=db_fetch("SELECT COALESCE(SUM(amount_due),0) tax_due,COALESCE(SUM(amount_paid),0) tax_paid FROM company_tax_records
 WHERE (period_start IS NOT NULL AND period_start BETWEEN ? AND ?) OR (period_start IS NULL AND due_date BETWEEN ? AND ?)",[$from,$to,$from,$to]);
$capital=db_fetch("SELECT COALESCE(SUM(CASE WHEN entry_type='draw' THEN -amount ELSE amount END),0) balance FROM company_capital_entries WHERE entry_date<=? AND voided_at IS NULL",[$to]);
$loans=db_fetch("SELECT COALESCE(SUM(CASE WHEN transaction_kind='loan_advance' THEN amount WHEN transaction_kind='loan_repayment' THEN -amount ELSE 0 END),0) balance FROM company_transactions WHERE transaction_kind IN ('loan_advance','loan_repayment') AND transaction_date<=? AND payment_status='paid' AND voided_at IS NULL",[$to]);
$capitalChanges=db_fetch("SELECT COALESCE(SUM(CASE WHEN entry_type='contribution' THEN amount ELSE 0 END),0) contributions,COALESCE(SUM(CASE WHEN entry_type='draw' THEN amount ELSE 0 END),0) draws FROM company_capital_entries WHERE entry_date BETWEEN ? AND ? AND voided_at IS NULL",[$from,$to]);
$costRows=db_fetch_all("SELECT category,transaction_kind,SUM(amount) amount FROM company_transactions WHERE transaction_date BETWEEN ? AND ? AND transaction_kind IN ('expense','purchase') AND voided_at IS NULL GROUP BY category,transaction_kind ORDER BY amount DESC",[$from,$to]);
$projectRows=db_fetch_all("SELECT p.id,p.name,p.project_type,p.contract_value,
 COALESCE(t.income,0) income,COALESCE(t.cost,0) costs,COALESCE(w.payroll,0) payroll
 FROM company_projects p
 LEFT JOIN (SELECT project_id,SUM(CASE WHEN transaction_kind='income' THEN amount ELSE 0 END) income,SUM(CASE WHEN transaction_kind IN ('expense','purchase') THEN amount ELSE 0 END) cost FROM company_transactions WHERE project_id IS NOT NULL AND transaction_date BETWEEN ? AND ? AND voided_at IS NULL GROUP BY project_id) t ON t.project_id=p.id
 LEFT JOIN (SELECT project_id,SUM(base_salary+allowances+employer_contributions) payroll FROM company_payroll WHERE project_id IS NOT NULL AND period_month>=DATE_FORMAT(?, '%Y-%m-01') AND period_month<=DATE_FORMAT(?, '%Y-%m-01') GROUP BY project_id) w ON w.project_id=p.id
 WHERE p.project_status<>'cancelled' ORDER BY (COALESCE(t.income,0)-COALESCE(t.cost,0)-COALESCE(w.payroll,0)) DESC,p.id DESC",[$from,$to,$from,$to]);
$revenue=(float)$movement['revenue'];$expenses=(float)$movement['expenses'];$purchases=(float)$movement['purchases'];$payrollCost=(float)$pay['payroll_cost'];$taxDue=(float)$tax['tax_due'];$preTax=$revenue-$expenses-$purchases-$payrollCost;$net=$preTax-$taxDue;
$adminTitle='الأرباح والخسائر';$adminSubtitle='التقرير المالي حسب الفترة';$adminActive='company_reports';require __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>الأرباح والخسائر</h1><p class="sub">ملخص الإيرادات والتكاليف والمرتبات والضرائب للفترة المحددة، مع تفصيل أداء المشاريع.</p></div></div>
<div class="panel"><div class="panel-body"><form method="get" class="form-grid" style="align-items:end"><div class="form-group"><label>من تاريخ</label><input class="form-control ltr" type="date" name="from" value="<?php echo e($from); ?>" required></div><div class="form-group"><label>إلى تاريخ</label><input class="form-control ltr" type="date" name="to" value="<?php echo e($to); ?>" required></div><div class="form-group"><button class="btn btn-primary" type="submit">عرض التقرير</button></div></form></div></div>
<div class="panel"><div class="panel-head"><h2>ملخص الفترة من <?php echo e($from); ?> إلى <?php echo e($to); ?></h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><tbody>
<tr><td>إجمالي الإيرادات قبل الضريبة</td><td><strong><?php echo e(company_money($revenue)); ?></strong></td><td>غير المحصل منها</td><td><?php echo e(company_money($movement['receivables'])); ?></td></tr>
<tr><td>مصروفات التشغيل</td><td><?php echo e(company_money($expenses)); ?></td><td>غير المسدد</td><td><?php echo e(company_money($movement['payables'])); ?></td></tr>
<tr><td>المشتريات</td><td><?php echo e(company_money($purchases)); ?></td><td>تكلفة المرتبات والمساهمات</td><td><?php echo e(company_money($payrollCost)); ?></td></tr>
<tr><td>الربح قبل الضرائب</td><td><strong><?php echo e(company_money($preTax)); ?></strong></td><td>ضرائب مستحقة للفترة</td><td><?php echo e(company_money($taxDue)); ?></td></tr>
<tr><td><strong>صافي الربح / (الخسارة) بعد الضرائب</strong></td><td colspan="3"><strong style="font-size:1.15rem;color:<?php echo $net>=0?'#1e8e3e':'#e53935'; ?>"><?php echo e(company_money($net)); ?></strong></td></tr>
<tr><td>ضريبة مخرجات مسجلة على الإيرادات</td><td><?php echo e(company_money($movement['output_tax'])); ?></td><td>ضريبة مدخلات مسجلة على المشتريات والمصروفات</td><td><?php echo e(company_money($movement['input_tax'])); ?></td></tr>
<tr><td>ضرائب مدفوعة (من سجلات الضرائب)</td><td colspan="3"><?php echo e(company_money($tax['tax_paid'])); ?></td></tr>
<tr><td>رأس المال المسجل حتى نهاية الفترة</td><td><strong><?php echo e(company_money($capital['balance'])); ?></strong></td><td>إضافات رأس المال خلال الفترة</td><td><?php echo e(company_money($capitalChanges['contributions'])); ?></td></tr>
<tr><td>سحوبات الملاك خلال الفترة</td><td colspan="3"><?php echo e(company_money($capitalChanges['draws'])); ?> <span class="hint">رأس المال والإضافات والسحوبات لا تدخل في حساب ربح النشاط.</span></td></tr>
<tr><td>سلف وقروض مستحقة للشركة حتى نهاية الفترة</td><td colspan="3"><strong><?php echo e(company_money($loans['balance'])); ?></strong> <span class="hint">تُعرض كذمم على المستفيد، ولا تدخل في الربح أو رأس المال.</span></td></tr>
</tbody></table></div></div><div class="panel-body"><p class="hint">التقرير على أساس الاستحقاق: يتضمن الحركات المعلقة ضمن الإيرادات أو التكاليف، ويعرض المبالغ غير المحصلة/المسددة منفصلة. تُدرج الضريبة حسب بداية فترة الإقرار أو تاريخ الاستحقاق إذا لم تُسجل بدايتها. لا تسجل رواتب الموظفين كمصروف هنا لأنها محسوبة من كشوف المرتبات. لا يُعد هذا بديلًا عن دفتر أستاذ معتمد أو إقرار ضريبي.</p></div></div>
<div class="grid-2"><div class="panel"><div class="panel-head"><h2>تفصيل التكاليف حسب التصنيف</h2><a class="link" href="finance.php?from=<?php echo e($from); ?>&to=<?php echo e($to); ?>">كل الحركات</a></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>التصنيف</th><th>النوع</th><th>القيمة</th></tr></thead><tbody>
<?php foreach($costRows as $row): ?><tr><td><?php echo e($row['category']); ?></td><td><?php echo $row['transaction_kind']==='purchase'?'مشتريات':'مصروف'; ?></td><td><?php echo e(company_money($row['amount'])); ?></td></tr><?php endforeach; ?><?php if(!$costRows): ?><tr><td colspan="3" class="muted">لا توجد تكاليف في هذه الفترة.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="panel"><div class="panel-head"><h2>موقف التحصيل والسداد</h2></div><div class="panel-body"><p>ذمم العملاء غير المحصلة: <strong><?php echo e(company_money($movement['receivables'])); ?></strong></p><p>مصروفات ومشتريات معلقة: <strong><?php echo e(company_money($movement['payables'])); ?></strong></p><p>رواتب معلقة: <strong><?php echo e(company_money($pay['payroll_due'])); ?></strong></p><p>متبقي ضرائب مسجلة: <strong><?php echo e(company_money($tax['tax_due']-$tax['tax_paid'])); ?></strong></p></div></div></div>
<div class="panel"><div class="panel-head"><h2>ربحية المشاريع خلال الفترة</h2></div><div class="panel-body p0"><div class="table-wrap"><table class="table"><thead><tr><th>المشروع</th><th>النوع</th><th>قيمة العقد</th><th>الإيراد</th><th>مصروفات ومشتريات</th><th>عمالة مخصصة</th><th>هامش المساهمة</th></tr></thead><tbody>
<?php foreach($projectRows as $row): $margin=(float)$row['income']-(float)$row['costs']-(float)$row['payroll']; ?><tr><td><a class="cell-title" href="company-projects.php?edit=<?php echo (int)$row['id']; ?>"><?php echo e($row['name']); ?></a></td><td><?php echo $row['project_type']==='internal'?'داخلي':'عميل'; ?></td><td><?php echo e(company_money($row['contract_value'])); ?></td><td><?php echo e(company_money($row['income'])); ?></td><td><?php echo e(company_money($row['costs'])); ?></td><td><?php echo e(company_money($row['payroll'])); ?></td><td><strong><?php echo e(company_money($margin)); ?></strong></td></tr><?php endforeach; ?>
<?php if(!$projectRows): ?><tr><td colspan="7" class="muted">لا توجد مشاريع مسجلة.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
