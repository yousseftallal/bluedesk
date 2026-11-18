<?php
require_once __DIR__ . '/includes/company_helpers.php';

$counts = [
    'employees' => (int) (db_fetch("SELECT COUNT(*) c FROM company_employees WHERE employment_status <> 'inactive'")['c'] ?? 0),
    'clients' => (int) (db_fetch("SELECT COUNT(*) c FROM company_clients WHERE client_status = 'active'")['c'] ?? 0),
    'leads' => (int) (db_fetch("SELECT COUNT(*) c FROM company_leads WHERE lead_status NOT IN ('won','lost')")['c'] ?? 0),
    'projects' => (int) (db_fetch("SELECT COUNT(*) c FROM company_projects WHERE project_status IN ('planned','in_progress','on_hold')")['c'] ?? 0),
];
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$finance = db_fetch("SELECT
    COALESCE(SUM(CASE WHEN transaction_kind='income' THEN amount ELSE 0 END),0) income,
    COALESCE(SUM(CASE WHEN transaction_kind IN ('expense','purchase') THEN amount ELSE 0 END),0) costs
    FROM company_transactions WHERE transaction_date BETWEEN ? AND ? AND voided_at IS NULL", [$monthStart, $monthEnd]);
$today = date('Y-m-d');
$payroll = db_fetch("SELECT COALESCE(SUM(base_salary + allowances + employer_contributions),0) payroll_cost,
    COALESCE(SUM(CASE WHEN payment_status='pending' THEN net_pay ELSE 0 END),0) payroll_due
    FROM company_payroll WHERE period_month = ?", [$monthStart]);
$tax = db_fetch("SELECT COALESCE(SUM(amount_due - amount_paid),0) tax_due FROM company_tax_records");
$capital = db_fetch("SELECT COALESCE(SUM(CASE WHEN entry_type='draw' THEN -amount ELSE amount END),0) balance FROM company_capital_entries WHERE entry_date<=CURDATE() AND voided_at IS NULL");
$cashBalance = db_fetch("SELECT
    COALESCE((SELECT SUM(CASE WHEN entry_type='draw' THEN -amount ELSE amount END)
        FROM company_capital_entries WHERE entry_date<=? AND voided_at IS NULL),0)
    + COALESCE((SELECT SUM(CASE
        WHEN transaction_kind IN ('income','loan_repayment') THEN amount + tax_amount
        WHEN transaction_kind IN ('expense','purchase','loan_advance') THEN -(amount + tax_amount)
        ELSE 0 END)
        FROM company_transactions
        WHERE payment_status='paid' AND transaction_date<=? AND voided_at IS NULL
          AND (payment_method IS NULL OR TRIM(payment_method)=''
               OR LOWER(payment_method) LIKE '%cash%'
               OR payment_method LIKE '%كاش%'
               OR payment_method LIKE '%نقد%'
               OR payment_method LIKE '%خزينة%')),0)
    - COALESCE((SELECT SUM(net_pay) FROM company_payroll
        WHERE payment_status='paid' AND COALESCE(payment_date,period_month)<=?),0)
    - COALESCE((SELECT SUM(amount_paid) FROM company_tax_records),0)
    AS balance", [$today, $today, $today]);
$showCashBalance = (string) ($_GET['show_cash'] ?? '') === '1';
$recentProjects = db_fetch_all("SELECT p.id,p.name,p.project_type,p.project_status,p.due_date,c.name client_name
    FROM company_projects p LEFT JOIN company_clients c ON c.id=p.client_id
    ORDER BY p.updated_at DESC, p.id DESC LIMIT 6");

$adminTitle = 'إدارة الشركة'; $adminSubtitle = 'العملاء والفريق والمشاريع والحسابات'; $adminActive = 'company';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>إدارة الشركة</h1><p class="sub">ملخص سريع للعملاء والفريق والمشاريع والوضع المالي للشهر الحالي.</p></div></div>
<div class="stats-grid">
  <div class="stat-card k-blue"><div class="stat-icon"><i class="fa-solid fa-users"></i></div><div><b><?php echo $counts['employees']; ?></b><span>موظف نشط</span></div></div>
  <div class="stat-card k-green"><div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div><div><b><?php echo $counts['clients']; ?></b><span>عميل نشط</span></div></div>
  <div class="stat-card k-yellow"><div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div><div><b><?php echo $counts['leads']; ?></b><span>عميل محتمل مفتوح</span></div></div>
  <div class="stat-card k-red"><div class="stat-icon"><i class="fa-solid fa-diagram-project"></i></div><div><b><?php echo $counts['projects']; ?></b><span>مشروع جارٍ</span></div></div>
</div>
<div class="stats-grid">
  <div class="stat-card k-green"><div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div><div><b><?php echo e(company_money($finance['income'] ?? 0)); ?></b><span>إيرادات الشهر</span></div></div>
  <div class="stat-card k-red"><div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div><div><b><?php echo e(company_money($finance['costs'] ?? 0)); ?></b><span>مصروفات ومشتريات الشهر</span></div></div>
  <div class="stat-card k-yellow"><div class="stat-icon"><i class="fa-solid fa-money-check-dollar"></i></div><div><b><?php echo e(company_money($payroll['payroll_cost'] ?? 0)); ?></b><span>تكلفة المرتبات للشهر</span></div></div>
  <div class="stat-card k-blue"><div class="stat-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div><div><b><?php echo e(company_money($tax['tax_due'] ?? 0)); ?></b><span>ضرائب غير مسددة</span></div></div>
  <div class="stat-card k-blue"><div class="stat-icon"><i class="fa-solid fa-coins"></i></div><div><b><?php echo e(company_money($capital['balance'] ?? 0)); ?></b><span>رأس المال المسجل · <a class="link" href="capital.php">إدارة</a></span></div></div>
</div>
<div class="panel">
  <div class="panel-head"><div><h2>خزينة الشركة الآن</h2></div>
    <form method="get" class="inline-form">
      <button class="btn btn-primary" type="submit" name="show_cash" value="<?php echo $showCashBalance ? '0' : '1'; ?>" aria-expanded="<?php echo $showCashBalance ? 'true' : 'false'; ?>" aria-controls="companyCashBalance">
        <i class="fa-solid <?php echo $showCashBalance ? 'fa-eye-slash' : 'fa-eye'; ?>" aria-hidden="true"></i>
        <?php echo $showCashBalance ? 'إخفاء الرصيد' : 'عرض رصيد الخزنة الآن'; ?>
      </button>
    </form>
  </div>
  <div class="panel-body" id="companyCashBalance" aria-live="polite" <?php echo $showCashBalance ? '' : 'hidden'; ?>>
    <div class="stat-card k-green"><div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div><div>
      <b><?php echo e(company_money($cashBalance['balance'] ?? 0)); ?></b><span>الرصيد النقدي المسجل</span>
    </div></div>
    <p class="hint" style="margin-top:.8rem">محسوب حتى <?php echo localized_datetime_html(app_now()); ?> من رأس المال والحركات المدفوعة نقدًا أو المسجلة بالخزينة، والمرتبات والضرائب المسددة. الحركات المعلقة ووسائل الدفع البنكية المعلّمة بوضوح مستبعدة؛ وسيلة الدفع غير المحددة تُحتسب على الخزينة.</p>
  </div>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>روابط سريعة</h2></div><div class="panel-body">
    <div class="actions">
      <a class="btn btn-primary" href="clients.php"><i class="fa-solid fa-user-plus"></i> إضافة عميل</a>
      <a class="btn btn-outline" href="leads.php"><i class="fa-solid fa-bullseye"></i> متابعة العملاء المحتملين</a>
      <a class="btn btn-outline" href="company-projects.php"><i class="fa-solid fa-diagram-project"></i> إدارة المشاريع</a>
      <a class="btn btn-outline" href="finance.php"><i class="fa-solid fa-money-bill-transfer"></i> تسجيل حركة مالية</a>
      <a class="btn btn-outline" href="company-reports.php"><i class="fa-solid fa-chart-column"></i> تقرير الأرباح والخسائر</a>
    </div>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>المشاريع الحالية</h2><a class="link" href="company-projects.php">عرض الكل</a></div><div class="panel-body p0">
    <?php if (!$recentProjects): ?><div class="empty"><i class="fa-solid fa-diagram-project"></i><p>لا توجد مشاريع مسجلة بعد.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>المشروع</th><th>العميل/النوع</th><th>الحالة</th></tr></thead><tbody>
    <?php foreach ($recentProjects as $p): ?><tr><td><a class="cell-title" href="company-projects.php?edit=<?php echo (int)$p['id']; ?>"><?php echo e($p['name']); ?></a></td><td><?php echo e($p['project_type']==='internal'?'داخلي':($p['client_name']??'عميل غير محدد')); ?></td><td><?php echo e($p['project_status']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
