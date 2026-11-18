<?php
/**
 * Project create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$project = !empty($_GET['id']) ? db_fetch('SELECT * FROM projects WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$project) {
    flash('error', 'المشروع غير موجود.');
    redirect('projects.php');
}
$projectGallery = $project
    ? db_fetch_all('SELECT id, image_path, sort_order FROM project_images WHERE project_id = ? ORDER BY sort_order, id', [(int) $project['id']])
    : [];

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM projects WHERE id = ?', [$id]) : null;

    $image = input('image');
    $newUploads = [];
    if (!empty($_FILES['image_file']['name'])) {
        $uploaded = upload_image($_FILES['image_file'], $err);
        if ($uploaded !== null) {
            $image = $uploaded;
            $newUploads[] = $uploaded;
        } else {
            flash('error', $err);
            redirect('project-edit.php' . ($id ? '?id=' . $id : ''));
        }
    }

    $data = [
        input('title_ar'), input('title_en'),
        input('desc_ar'), input('desc_en'),
        input('tag_ar'), input('tag_en'),
        $image, input('stack'), input('link'),
        isset($_POST['show_demo']) ? 1 : 0,
        (int) input('sort_order', '0'),
        isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data[0] === '' || $data[1] === '' || $data[2] === '' || $data[3] === '') {
        flash('error', 'عنوان المشروع ووصفه مطلوبان باللغتين.');
    } elseif ($data[8] !== '' && safe_http_url($data[8]) === '') {
        flash('error', 'رابط المشروع يجب أن يكون عنوان HTTP أو HTTPS صحيحًا.');
    } elseif ($data[9] && $data[8] === '') {
        flash('error', 'أضف رابط تجربة المشروع أو أخفِ الزر قبل الحفظ.');
    } else {
        $galleryUploads = [];
        $galleryField = $_FILES['gallery_files'] ?? null;
        if (is_array($galleryField) && is_array($galleryField['name'] ?? null)) {
            foreach (array_keys($galleryField['name']) as $index) {
                $galleryFile = [
                    'name' => $galleryField['name'][$index] ?? '',
                    'type' => $galleryField['type'][$index] ?? '',
                    'tmp_name' => $galleryField['tmp_name'][$index] ?? '',
                    'error' => $galleryField['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $galleryField['size'][$index] ?? 0,
                ];
                if ((int) $galleryFile['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $galleryImage = upload_image($galleryFile, $err);
                if ($galleryImage === null) {
                    foreach ($newUploads as $newUpload) {
                        delete_upload($newUpload);
                    }
                    flash('error', $err ?: 'تعذر رفع إحدى صور المشروع.');
                    redirect('project-edit.php' . ($id ? '?id=' . $id : ''));
                }
                $galleryUploads[] = $galleryImage;
                $newUploads[] = $galleryImage;
            }
        }

        $removedGalleryRows = [];
        if ($existing) {
            db_query(
                'UPDATE projects SET title_ar=?, title_en=?, desc_ar=?, desc_en=?, tag_ar=?, tag_en=?, image=?, stack=?, link=?, show_demo=?, sort_order=?, is_active=? WHERE id=?',
                array_merge($data, [$id])
            );
            if ($image !== $existing['image']) {
                delete_upload($existing['image']);
            }
            $removeIds = $_POST['remove_gallery'] ?? [];
            if (!is_array($removeIds)) {
                $removeIds = [];
            }
            $removeIds = array_values(array_unique(array_filter(array_map('intval', $removeIds), static fn (int $imageId): bool => $imageId > 0)));
            if ($removeIds) {
                $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
                $removeParams = array_merge([$id], $removeIds);
                $removedGalleryRows = db_fetch_all(
                    "SELECT id, image_path FROM project_images WHERE project_id = ? AND id IN ($placeholders)",
                    $removeParams
                );
                db_query("DELETE FROM project_images WHERE project_id = ? AND id IN ($placeholders)", $removeParams);
            }
            log_activity('تعديل مشروع', $data[0]);
            flash('success', 'تم تحديث المشروع.');
        } else {
            db_query(
                'INSERT INTO projects (title_ar, title_en, desc_ar, desc_en, tag_ar, tag_en, image, stack, link, show_demo, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                $data
            );
            $id = (int) db()->lastInsertId();
            log_activity('إضافة مشروع', $data[0]);
            flash('success', 'تمت إضافة المشروع.');
        }

        foreach ($removedGalleryRows as $removedGalleryRow) {
            delete_upload($removedGalleryRow['image_path']);
        }
        if ($galleryUploads) {
            $nextOrder = (int) (db_fetch('SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM project_images WHERE project_id = ?', [$id])['next_order'] ?? 0);
            foreach ($galleryUploads as $offset => $galleryImage) {
                db_query(
                    'INSERT INTO project_images (project_id, image_path, sort_order) VALUES (?, ?, ?)',
                    [$id, $galleryImage, $nextOrder + $offset]
                );
            }
        }
        redirect('projects.php');
    }
    foreach ($newUploads as $newUpload) {
        delete_upload($newUpload);
    }
    redirect('project-edit.php' . ($id ? '?id=' . $id : ''));
}

$title = $project['title_ar'] ?? 'مشروع جديد';
$adminTitle = $project ? 'تعديل مشروع' : 'مشروع جديد';
$adminSubtitle = $title;
$adminActive = 'projects';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub">تفاصيل المشروع كما تظهر في معرض الأعمال.</p></div>
  <div class="actions"><a class="btn btn-outline" href="projects.php"><i class="fa-solid fa-arrow-right"></i> رجوع للأعمال</a></div>
</div>

<form method="post" action="project-edit.php" enctype="multipart/form-data" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($project['id'] ?? 0); ?>">
  <div class="panel">
    <div class="panel-head"><h2>بيانات المشروع</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="title_ar">اسم المشروع (عربي) <span class="req">*</span></label><input class="form-control" id="title_ar" name="title_ar" value="<?php echo e($project['title_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="title_en">اسم المشروع (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="title_en" name="title_en" value="<?php echo e($project['title_en'] ?? ''); ?>" required></div>
        <div class="form-group full"><label for="desc_ar">الوصف (عربي) <span class="req">*</span></label><textarea class="form-control" id="desc_ar" name="desc_ar" rows="2" required><?php echo e($project['desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group full"><label for="desc_en">الوصف (إنجليزي) <span class="req">*</span></label><textarea class="form-control ltr" id="desc_en" name="desc_en" rows="2" required><?php echo e($project['desc_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="tag_ar">التصنيف (عربي) <span class="req">*</span></label><input class="form-control" id="tag_ar" name="tag_ar" value="<?php echo e($project['tag_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="tag_en">التصنيف (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="tag_en" name="tag_en" value="<?php echo e($project['tag_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="stack">التقنيات المستخدمة</label><input class="form-control ltr" id="stack" name="stack" value="<?php echo e($project['stack'] ?? ''); ?>" placeholder="Laravel, Vue.js, MySQL"></div>
        <div class="form-group"><label for="link">رابط تجربة المشروع</label><input class="form-control ltr" type="url" id="link" name="link" maxlength="255" value="<?php echo e($project['link'] ?? ''); ?>" placeholder="https://example.com" autocomplete="url"><span class="hint">لازم يكون رابطًا كاملًا يبدأ بـ https:// أو http://.</span></div>
        <div class="form-group"><label>زر تجربة المشروع</label><label class="switch"><input type="checkbox" name="show_demo" <?php echo ($project && !empty($project['link']) && (int) ($project['show_demo'] ?? 1)) ? 'checked' : ''; ?>> إظهار الزر في بطاقة المشروع وصفحته</label></div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($project['sort_order'] ?? 0); ?>"></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$project || (int) $project['is_active']) ? 'checked' : ''; ?>> منشور بالموقع</label></div>
        <div class="form-group">
          <label for="image">صورة المشروع</label>
          <input class="form-control ltr" id="image" name="image" value="<?php echo e($project['image'] ?? ''); ?>" placeholder="https://...">
          <input class="form-control" type="file" name="image_file" style="margin-top:.45rem" accept="image/png,image/jpeg,image/webp,image/gif">
          <span class="hint">يفضل صورة أفقية بنسبة 4:3.</span>
          <?php if (!empty($project['image'])): ?><img class="preview-img" src="<?php echo e(asset($project['image'])); ?>" alt="معاينة"><?php endif; ?>
        </div>
        <div class="form-group full">
          <label for="gallery_files">صور إضافية داخل صفحة المشروع</label>
          <input class="form-control" type="file" id="gallery_files" name="gallery_files[]" multiple accept="image/png,image/jpeg,image/webp,image/gif">
          <span class="hint">تقدر تختار أكثر من صورة مرة واحدة. الحد الأقصى 4 ميجابايت لكل صورة.</span>
          <?php if ($projectGallery): ?>
            <div class="project-gallery-admin">
              <?php foreach ($projectGallery as $galleryImage): ?>
                <label class="project-gallery-admin-item">
                  <img src="<?php echo e(asset($galleryImage['image_path'])); ?>" alt="صورة إضافية للمشروع" loading="lazy">
                  <span><input type="checkbox" name="remove_gallery[]" value="<?php echo (int) $galleryImage['id']; ?>"> حذف الصورة</span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="projects.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ المشروع</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
