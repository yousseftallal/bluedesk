<?php
/**
 * Pages list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post() && input('action') === 'delete') {
    require_csrf();
    $id = (int) input('id');
    $page = db_fetch('SELECT * FROM pages WHERE id = ?', [$id]);
    if (!$page) {
        flash('error', 'الصفحة غير موجودة.');
    } elseif ((int) $page['is_core']) {
        flash('error', 'لا يمكن حذف الصفحات الأساسية للموقع.');
    } else {
        db_query('DELETE FROM pages WHERE id = ?', [$id]);
        log_activity('حذف صفحة', $page['title_ar']);
        flash('success', 'تم حذف الصفحة.');
    }
    redirect('pages.php');
}

$pages = db_fetch_all('SELECT * FROM pages ORDER BY is_core DESC, sort_order, id');
$customUrls = ['home' => 'index.php', 'about' => 'about.php', 'services' => 'services.php', 'portfolio' => 'portfolio.php', 'contact' => 'contact.php'];

$adminTitle = 'الصفحات';
$adminSubtitle = 'الصفحات الأساسية والصفحات المخصصة';
$adminActive = 'pages';
$adminActions = '<a class="btn btn-primary" href="page-edit.php"><i class="fa-solid fa-plus"></i> صفحة جديدة</a>';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>إدارة الصفحات</h1><p class="sub">عدّل العناوين والوصف التعريفي وSEO لكل صفحة، أو أنشئ صفحات جديدة.</p></div>
  <div class="actions"><a class="btn btn-primary" href="page-edit.php"><i class="fa-solid fa-plus"></i> صفحة جديدة</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>كل الصفحات (<?php echo count($pages); ?>)</h2></div>
  <div class="panel-body p0">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>الصفحة</th><th>الرابط</th><th>الحالة</th><th>الترتيب</th><th>آخر تحديث</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pages as $row): ?>
            <tr>
              <td>
                <div class="cell-title"><?php echo e($row['title_ar']); ?> <?php if ($row['is_core']): ?><span class="badge main">أساسية</span><?php endif; ?></div>
                <div class="cell-sub"><?php echo e($row['title_en']); ?></div>
              </td>
              <td><span class="muted" dir="ltr"><?php echo e($customUrls[$row['slug']] ?? ('page.php?slug=' . $row['slug'])); ?></span></td>
              <td><span class="badge <?php echo $row['status'] === 'published' ? 'published' : 'draft'; ?>"><?php echo $row['status'] === 'published' ? 'منشورة' : 'مسودة'; ?></span></td>
              <td><?php echo (int) $row['sort_order']; ?></td>
              <td class="muted"><?php echo localized_datetime_html($row['updated_at'] ? (string)$row['updated_at'] : null); ?></td>
              <td class="actions-cell">
                <a class="btn btn-outline btn-sm" href="<?php echo e(url($customUrls[$row['slug']] ?? ('page.php?slug=' . $row['slug']))); ?>" target="_blank" rel="noopener" title="عرض"><i class="fa-solid fa-eye"></i></a>
                <a class="btn btn-outline btn-sm" href="page-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                <?php if (!$row['is_core']): ?>
                  <form method="post" action="pages.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف صفحة «<?php echo e($row['title_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
