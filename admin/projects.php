<?php
/**
 * Projects list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'toggle') {
        db_query('UPDATE projects SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة المشروع.');
    } elseif (input('action') === 'delete') {
        $row = db_fetch('SELECT title_ar, image FROM projects WHERE id = ?', [$id]);
        $galleryRows = db_fetch_all('SELECT image_path FROM project_images WHERE project_id = ?', [$id]);
        db_query('DELETE FROM projects WHERE id = ?', [$id]);
        if ($row) {
            delete_upload($row['image']);
            foreach ($galleryRows as $galleryRow) {
                delete_upload($galleryRow['image_path']);
            }
            log_activity('حذف مشروع', $row['title_ar']);
        }
        flash('success', 'تم حذف المشروع.');
    }
    redirect('projects.php');
}

$projects = db_fetch_all('SELECT * FROM projects ORDER BY sort_order, id');

$adminTitle = 'الأعمال';
$adminSubtitle = 'مشاريع معرض الأعمال';
$adminActive = 'projects';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>إدارة الأعمال</h1><p class="sub">المشاريع التي تظهر في صفحة أعمالنا.</p></div>
  <div class="actions"><a class="btn btn-primary" href="project-edit.php"><i class="fa-solid fa-plus"></i> مشروع جديد</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>كل المشاريع (<?php echo count($projects); ?>)</h2></div>
  <div class="panel-body p0">
    <?php if (!$projects): ?>
      <div class="empty"><i class="fa-solid fa-briefcase"></i><p>لا توجد مشاريع بعد.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>المشروع</th><th>التصنيف</th><th>التقنيات</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($projects as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.7rem">
                    <?php if ($row['image']): ?>
                      <img class="thumb wide" src="<?php echo e(asset($row['image'])); ?>" alt="">
                    <?php endif; ?>
                    <div>
                      <div class="cell-title"><?php echo e($row['title_ar']); ?></div>
                      <div class="cell-sub"><?php echo e($row['title_en']); ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge main"><?php echo e($row['tag_ar']); ?></span></td>
                <td class="muted" dir="ltr"><?php echo e($row['stack'] ?: '—'); ?></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'منشور' : 'موقوف'; ?></span></td>
                <td class="actions-cell">
                  <form method="post" action="projects.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'نشر'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="project-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                  <form method="post" action="projects.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف مشروع «<?php echo e($row['title_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
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
