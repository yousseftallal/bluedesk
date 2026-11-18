<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $action = input('action');
    if ($id > 0 && $action === 'toggle') {
        $now = app_now();
        db_query("UPDATE blog_posts SET published_at=IF(status='draft',COALESCE(published_at,?),published_at), status=IF(status='published','draft','published'), updated_at=NOW(), updated_by=? WHERE id=?", [$now, (int)($_SESSION['uid'] ?? 0), $id]);
        log_activity('تغيير حالة مقال', (string)$id);
        flash('success', 'تم تحديث حالة المقال.');
    } elseif ($id > 0 && $action === 'delete') {
        $post = db_fetch('SELECT title_ar, cover_image FROM blog_posts WHERE id=?', [$id]);
        if ($post) {
            db_query('DELETE FROM blog_posts WHERE id=?', [$id]);
            delete_upload($post['cover_image']);
            log_activity('حذف مقال', $post['title_ar']);
            flash('success', 'تم حذف المقال.');
        }
    }
    redirect('blog.php');
}

$search = mb_substr(input('q'), 0, 100);
$status = in_array($_GET['status'] ?? '', ['draft','published'], true) ? $_GET['status'] : '';
$categoryFilter = max(0, (int)($_GET['category'] ?? 0));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$where = ['1=1'];
$params = [];
if ($search !== '') { $where[] = '(p.title_ar LIKE ? OR p.title_en LIKE ? OR p.slug LIKE ?)'; $needle = '%' . $search . '%'; array_push($params, $needle, $needle, $needle); }
if ($status !== '') { $where[] = 'p.status = ?'; $params[] = $status; }
if ($categoryFilter > 0) { $where[] = 'p.category_id = ?'; $params[] = $categoryFilter; }
$whereSql = implode(' AND ', $where);
$total = (int)(db_fetch('SELECT COUNT(*) AS c FROM blog_posts p WHERE ' . $whereSql, $params)['c'] ?? 0);
$page = min($page, max(1, (int)ceil($total / $perPage)));
$offset = ($page - 1) * $perPage;
$rows = db_fetch_all('SELECT p.*, c.title_ar AS category_ar FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE ' . $whereSql . ' ORDER BY COALESCE(p.published_at,p.created_at) DESC, p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);
$categories = db_fetch_all('SELECT id,title_ar FROM blog_categories ORDER BY sort_order,id');
$draftCount = (int)(db_fetch("SELECT COUNT(*) AS c FROM blog_posts WHERE status='draft'")['c'] ?? 0);
$publishedCount = (int)(db_fetch("SELECT COUNT(*) AS c FROM blog_posts WHERE status='published' AND published_at <= ?", [app_now()])['c'] ?? 0);
$adminTitle = 'المدونة';
$adminSubtitle = 'إدارة المقالات والنشر وتهيئة SEO';
$adminActive = 'blog';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>إدارة المدونة</h1><p class="sub">اكتب وانشر محتوى عربيًا وإنجليزيًا، وأدر الوصف والصورة والرابط ومحركات البحث لكل مقال.</p></div><div class="actions"><a class="btn btn-outline" href="blog-categories.php"><i class="fa-solid fa-folder-open"></i> التصنيفات</a><a class="btn btn-primary" href="blog-edit.php"><i class="fa-solid fa-plus"></i> مقال جديد</a></div></div>
<div class="stats-grid" style="margin-bottom:1.2rem"><div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-newspaper"></i></div><div><b><?= $total ?></b><span>نتائج القائمة</span></div></div><div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-globe"></i></div><div><b><?= $publishedCount ?></b><span>مقالات منشورة</span></div></div><div class="stat-card"><div class="stat-icon yellow"><i class="fa-solid fa-file-pen"></i></div><div><b><?= $draftCount ?></b><span>مسودات</span></div></div></div>
<div class="panel"><div class="panel-head"><h2>المقالات</h2><a class="btn btn-outline btn-sm" href="<?= e(url('blog')) ?>" target="_blank" rel="noopener">عرض المدونة <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
  <div class="panel-body"><form class="filters" method="get" action="blog.php"><div class="search"><i class="fa-solid fa-magnifying-glass"></i><input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="ابحث بالعنوان أو الرابط"></div><select class="form-control" name="status"><option value="">كل الحالات</option><option value="published" <?= $status==='published'?'selected':'' ?>>منشور</option><option value="draft" <?= $status==='draft'?'selected':'' ?>>مسودة</option></select><select class="form-control" name="category"><option value="0">كل التصنيفات</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter===(int)$cat['id']?'selected':'' ?>><?= e($cat['title_ar']) ?></option><?php endforeach; ?></select><button class="btn btn-primary btn-sm" type="submit">تصفية</button></form></div>
  <div class="panel-body p0">
  <?php if (!$rows): ?><div class="empty"><i class="fa-solid fa-newspaper"></i><p>لا توجد مقالات مطابقة بعد.</p><a class="btn btn-primary" href="blog-edit.php">إنشاء أول مقال</a></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>المقال</th><th>التصنيف</th><th>النشر</th><th>SEO</th><th></th></tr></thead><tbody>
      <?php foreach($rows as $row): $published = $row['status']==='published' && $row['published_at'] && strtotime($row['published_at']) <= time(); ?>
      <tr><td><div style="display:flex;align-items:center;gap:.7rem"><?php if($row['cover_image']): ?><img class="thumb wide" src="<?= e(asset($row['cover_image'])) ?>" alt=""><?php endif; ?><div><div class="cell-title"><?= e($row['title_ar']) ?></div><div class="cell-sub" dir="ltr"><?= e($row['title_en']) ?> · <?= e($row['slug']) ?></div></div></div></td><td><?= e($row['category_ar'] ?: 'بدون تصنيف') ?></td><td><span class="badge <?= $published?'active':'inactive' ?>"><?= $published?'منشور':($row['status']==='published'?'مجدول':'مسودة') ?></span><div class="cell-sub"><?= localized_datetime_html($row['published_at'] ? (string)$row['published_at'] : null) ?></div></td><td><span class="badge <?= ($row['meta_title_ar'] && $row['meta_desc_ar'])?'active':'inactive' ?>"><?= ($row['meta_title_ar'] && $row['meta_desc_ar'])?'مكتمل':'راجع البيانات' ?></span></td><td class="actions-cell"><a class="btn btn-outline btn-sm btn-icon" href="<?= e(url('blog/' . rawurlencode($row['slug']))) ?>" target="_blank" rel="noopener" title="معاينة"><i class="fa-solid fa-eye"></i></a><a class="btn btn-outline btn-sm" href="blog-edit.php?id=<?= (int)$row['id'] ?>"><i class="fa-solid fa-pen"></i> تعديل</a><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?= $row['status']==='published'?'تحويل لمسودة':'نشر' ?>"><i class="fa-solid <?= $row['status']==='published'?'fa-toggle-on':'fa-toggle-off' ?>"></i></button></form><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف مقال «<?= e($row['title_ar']) ?>» نهائيًا؟" title="حذف"><i class="fa-solid fa-trash"></i></button></form></td></tr>
      <?php endforeach; ?></tbody></table></div>
      <?php if ($total > $perPage): $query = $_GET; ?><div class="blog-pagination admin-pagination"><?php for($p=1;$p<=ceil($total/$perPage);$p++): $query['page']=$p; ?><a class="<?= $p===$page?'active':'' ?>" href="?<?= e(http_build_query($query)) ?>"><?= $p ?></a><?php endfor; ?></div><?php endif; ?>
  <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
