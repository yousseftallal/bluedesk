<?php
/**
 * Homepage content: hero, why, about, section titles, stats, process.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$action = input('action');

if (is_post()) {
    require_csrf();

    if ($action === 'save_hero' || $action === 'save_why' || $action === 'save_about' || $action === 'save_labels') {
        $map = [
            'save_hero' => [
                'hero_badge_ar', 'hero_badge_en',
                'hero_title1_ar', 'hero_title2_ar', 'hero_title3_ar', 'hero_title4_ar',
                'hero_title1_en', 'hero_title2_en', 'hero_title3_en', 'hero_title4_en',
                'hero_lead_ar', 'hero_lead_en',
                'hero_cta1_ar', 'hero_cta1_en', 'hero_cta2_ar', 'hero_cta2_en',
            ],
            'save_why' => [
                'why_title_ar', 'why_title_en', 'why_desc_ar', 'why_desc_en',
                'why_badge_num', 'why_badge_label_ar', 'why_badge_label_en',
            ],
            'save_about' => [
                'about_h2_ar', 'about_h2_en', 'about_p1_ar', 'about_p1_en', 'about_p2_ar', 'about_p2_en',
                'about_ck1_ar', 'about_ck1_en', 'about_ck2_ar', 'about_ck2_en',
                'about_ck3_ar', 'about_ck3_en', 'about_ck4_ar', 'about_ck4_en',
            ],
            'save_labels' => ['values_title_ar', 'values_title_en', 'team_title_ar', 'team_title_en', 'cta_title_ar', 'cta_title_en', 'cta_desc_ar', 'cta_desc_en'],
        ];
        $imgKey = ['save_hero' => 'hero_image', 'save_why' => 'why_image', 'save_about' => 'about_image'][$action] ?? null;
        $imageValue = $imgKey ? input($imgKey) : null;
        if ($imgKey) {
            if (!empty($_FILES[$imgKey . '_file']['name'])) {
                $uploaded = upload_image($_FILES[$imgKey . '_file'], $err);
                if ($uploaded !== null) {
                    $imageValue = $uploaded;
                } else {
                    flash('error', $err);
                    redirect('homepage.php');
                }
            }
        }
        foreach ($map[$action] as $key) {
            save_setting($key, input($key));
        }
        if ($imgKey) {
            save_setting($imgKey, $imageValue);
        }
        log_activity('تحديث الصفحة الرئيسية', 'تم تحديث قسم ' . $action);
        flash('success', 'تم حفظ القسم بنجاح.');
        redirect('homepage.php');
    }

    if ($action === 'stat_save') {
        $id = (int) input('id');
        $data = [
            (int) input('value', '0'),
            input('suffix', '+'),
            input('label_ar'),
            input('label_en'),
            (int) input('sort_order', '0'),
            isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($data[2] === '' || $data[3] === '') {
            flash('error', 'عنوان الإحصائية مطلوب باللغتين.');
        } elseif ($id > 0) {
            db_query('UPDATE stats SET value=?, suffix=?, label_ar=?, label_en=?, sort_order=?, is_active=? WHERE id=?', array_merge($data, [$id]));
            log_activity('تعديل إحصائية', $data[2]);
            flash('success', 'تم تحديث الإحصائية.');
        } else {
            db_query('INSERT INTO stats (value, suffix, label_ar, label_en, sort_order, is_active) VALUES (?,?,?,?,?,?)', $data);
            log_activity('إضافة إحصائية', $data[2]);
            flash('success', 'تمت إضافة الإحصائية.');
        }
        redirect('homepage.php#stats');
    }

    if ($action === 'stat_delete') {
        $id = (int) input('id');
        db_query('DELETE FROM stats WHERE id = ?', [$id]);
        log_activity('حذف إحصائية', '#' . $id);
        flash('success', 'تم حذف الإحصائية.');
        redirect('homepage.php#stats');
    }

    if ($action === 'process_save') {
        $id = (int) input('id');
        $data = [input('title_ar'), input('title_en'), input('desc_ar'), input('desc_en'), (int) input('sort_order', '0'), isset($_POST['is_active']) ? 1 : 0];
        if ($data[0] === '' || $data[1] === '') {
            flash('error', 'عنوان الخطوة مطلوب باللغتين.');
        } elseif ($id > 0) {
            db_query('UPDATE process_steps SET title_ar=?, title_en=?, desc_ar=?, desc_en=?, sort_order=?, is_active=? WHERE id=?', array_merge($data, [$id]));
            log_activity('تعديل خطوة عمل', $data[0]);
            flash('success', 'تم تحديث الخطوة.');
        } else {
            db_query('INSERT INTO process_steps (title_ar, title_en, desc_ar, desc_en, sort_order, is_active) VALUES (?,?,?,?,?,?)', $data);
            log_activity('إضافة خطوة عمل', $data[0]);
            flash('success', 'تمت إضافة الخطوة.');
        }
        redirect('homepage.php#process');
    }

    if ($action === 'process_delete') {
        $id = (int) input('id');
        db_query('DELETE FROM process_steps WHERE id = ?', [$id]);
        log_activity('حذف خطوة عمل', '#' . $id);
        flash('success', 'تم حذف الخطوة.');
        redirect('homepage.php#process');
    }
}

$stats = db_fetch_all('SELECT * FROM stats ORDER BY sort_order, id');
$process = db_fetch_all('SELECT * FROM process_steps ORDER BY sort_order, id');
$editStat = !empty($_GET['edit_stat']) ? db_fetch('SELECT * FROM stats WHERE id = ?', [(int) $_GET['edit_stat']]) : null;
$editProcess = !empty($_GET['edit_process']) ? db_fetch('SELECT * FROM process_steps WHERE id = ?', [(int) $_GET['edit_process']]) : null;

$imageField = function (string $key, string $label, string $value) {
    ?>
    <div class="form-group">
      <label for="<?php echo e($key); ?>"><?php echo e($label); ?></label>
      <input class="form-control ltr" id="<?php echo e($key); ?>" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
      <input class="form-control" style="margin-top:.45rem" type="file" name="<?php echo e($key); ?>_file" accept="image/png,image/jpeg,image/webp,image/gif">
      <span class="hint">يمكنك لصق رابط صورة أو رفع صورة جديدة.</span>
      <?php if ($value): ?><img class="preview-img" src="<?php echo e(asset($value)); ?>" alt="معاينة"><?php endif; ?>
    </div>
    <?php
};

$adminTitle = 'الصفحة الرئيسية';
$adminSubtitle = 'تحكم في الأقسام الظاهرة بالصفحة الرئيسية';
$adminActive = 'homepage';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>محتوى الصفحة الرئيسية</h1><p class="sub">عدّل النصوص والصور والإحصائيات وخطوات العمل.</p></div>
  <div class="actions"><a class="btn btn-outline" href="<?php echo e(url('index.php')); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> معاينة الصفحة</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>قسم الهيرو (أعلى الصفحة)</h2></div>
  <form method="post" action="homepage.php" enctype="multipart/form-data" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_hero">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="hero_badge_ar">الشارة العلوية (عربي)</label><input class="form-control" id="hero_badge_ar" name="hero_badge_ar" value="<?php echo e(setting('hero_badge_ar')); ?>"></div>
        <div class="form-group"><label for="hero_badge_en">الشارة العلوية (إنجليزي)</label><input class="form-control ltr" id="hero_badge_en" name="hero_badge_en" value="<?php echo e(setting('hero_badge_en')); ?>"></div>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <?php for ($i = 1; $i <= 4; $i++): ?>
          <div class="form-group">
            <label for="hero_title<?php echo $i; ?>_ar">العنوان الرئيسي - جزء <?php echo $i; ?> (عربي)</label>
            <input class="form-control" id="hero_title<?php echo $i; ?>_ar" name="hero_title<?php echo $i; ?>_ar" value="<?php echo e(setting('hero_title' . $i . '_ar')); ?>">
          </div>
          <div class="form-group">
            <label for="hero_title<?php echo $i; ?>_en">العنوان الرئيسي - جزء <?php echo $i; ?> (إنجليزي)</label>
            <input class="form-control ltr" id="hero_title<?php echo $i; ?>_en" name="hero_title<?php echo $i; ?>_en" value="<?php echo e(setting('hero_title' . $i . '_en')); ?>">
          </div>
        <?php endfor; ?>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <div class="form-group"><label for="hero_lead_ar">النص التعريفي (عربي)</label><textarea class="form-control" id="hero_lead_ar" name="hero_lead_ar" rows="3"><?php echo e(setting('hero_lead_ar')); ?></textarea></div>
        <div class="form-group"><label for="hero_lead_en">النص التعريفي (إنجليزي)</label><textarea class="form-control ltr" id="hero_lead_en" name="hero_lead_en" rows="3"><?php echo e(setting('hero_lead_en')); ?></textarea></div>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <div class="form-group"><label for="hero_cta1_ar">زر الإجراء الأول (عربي)</label><input class="form-control" id="hero_cta1_ar" name="hero_cta1_ar" value="<?php echo e(setting('hero_cta1_ar')); ?>"></div>
        <div class="form-group"><label for="hero_cta1_en">زر الإجراء الأول (إنجليزي)</label><input class="form-control ltr" id="hero_cta1_en" name="hero_cta1_en" value="<?php echo e(setting('hero_cta1_en')); ?>"></div>
        <div class="form-group"><label for="hero_cta2_ar">زر الإجراء الثاني (عربي)</label><input class="form-control" id="hero_cta2_ar" name="hero_cta2_ar" value="<?php echo e(setting('hero_cta2_ar')); ?>"></div>
        <div class="form-group"><label for="hero_cta2_en">زر الإجراء الثاني (إنجليزي)</label><input class="form-control ltr" id="hero_cta2_en" name="hero_cta2_en" value="<?php echo e(setting('hero_cta2_en')); ?>"></div>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <?php $imageField('hero_image', 'صورة الهيرو', setting('hero_image')); ?>
        <div class="form-group">
          <label>معاينة مباشرة</label>
          <p class="hint">تظهر الصورة على الجانب في الشاشات الكبيرة وأسفل النص على الموبايل.</p>
        </div>
      </div>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ الهيرو</button></div></div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>قسم "لماذا بلوديسك؟"</h2><a class="link" href="features.php?section=why">إدارة المزايا (البطاقات)</a></div>
  <form method="post" action="homepage.php" enctype="multipart/form-data" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_why">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="why_title_ar">العنوان (عربي)</label><input class="form-control" id="why_title_ar" name="why_title_ar" value="<?php echo e(setting('why_title_ar')); ?>"></div>
        <div class="form-group"><label for="why_title_en">العنوان (إنجليزي)</label><input class="form-control ltr" id="why_title_en" name="why_title_en" value="<?php echo e(setting('why_title_en')); ?>"></div>
        <div class="form-group"><label for="why_desc_ar">الوصف (عربي)</label><textarea class="form-control" id="why_desc_ar" name="why_desc_ar" rows="2"><?php echo e(setting('why_desc_ar')); ?></textarea></div>
        <div class="form-group"><label for="why_desc_en">الوصف (إنجليزي)</label><textarea class="form-control ltr" id="why_desc_en" name="why_desc_en" rows="2"><?php echo e(setting('why_desc_en')); ?></textarea></div>
        <div class="form-group"><label for="why_badge_num">رقم الشارة</label><input class="form-control ltr" id="why_badge_num" name="why_badge_num" value="<?php echo e(setting('why_badge_num')); ?>" placeholder="98%"></div>
        <div class="form-group"><label for="why_badge_label_ar">نص الشارة (عربي)</label><input class="form-control" id="why_badge_label_ar" name="why_badge_label_ar" value="<?php echo e(setting('why_badge_label_ar')); ?>"></div>
        <div class="form-group"><label for="why_badge_label_en">نص الشارة (إنجليزي)</label><input class="form-control ltr" id="why_badge_label_en" name="why_badge_label_en" value="<?php echo e(setting('why_badge_label_en')); ?>"></div>
        <?php $imageField('why_image', 'صورة القسم', setting('why_image')); ?>
      </div>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ القسم</button></div></div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>قسم "من نحن" المختصر</h2><a class="link" href="page-edit.php?slug=about">تعديل صفحة من نحن</a></div>
  <form method="post" action="homepage.php" enctype="multipart/form-data" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_about">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="about_h2_ar">العنوان (عربي)</label><input class="form-control" id="about_h2_ar" name="about_h2_ar" value="<?php echo e(setting('about_h2_ar')); ?>"></div>
        <div class="form-group"><label for="about_h2_en">العنوان (إنجليزي)</label><input class="form-control ltr" id="about_h2_en" name="about_h2_en" value="<?php echo e(setting('about_h2_en')); ?>"></div>
        <div class="form-group"><label for="about_p1_ar">الفقرة الأولى (عربي)</label><textarea class="form-control" id="about_p1_ar" name="about_p1_ar" rows="2"><?php echo e(setting('about_p1_ar')); ?></textarea></div>
        <div class="form-group"><label for="about_p1_en">الفقرة الأولى (إنجليزي)</label><textarea class="form-control ltr" id="about_p1_en" name="about_p1_en" rows="2"><?php echo e(setting('about_p1_en')); ?></textarea></div>
        <div class="form-group"><label for="about_p2_ar">الفقرة الثانية (عربي)</label><textarea class="form-control" id="about_p2_ar" name="about_p2_ar" rows="2"><?php echo e(setting('about_p2_ar')); ?></textarea></div>
        <div class="form-group"><label for="about_p2_en">الفقرة الثانية (إنجليزي)</label><textarea class="form-control ltr" id="about_p2_en" name="about_p2_en" rows="2"><?php echo e(setting('about_p2_en')); ?></textarea></div>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <?php for ($i = 1; $i <= 4; $i++): ?>
          <div class="form-group"><label for="about_ck<?php echo $i; ?>_ar">نقطة <?php echo $i; ?> (عربي)</label><input class="form-control" id="about_ck<?php echo $i; ?>_ar" name="about_ck<?php echo $i; ?>_ar" value="<?php echo e(setting('about_ck' . $i . '_ar')); ?>"></div>
          <div class="form-group"><label for="about_ck<?php echo $i; ?>_en">نقطة <?php echo $i; ?> (إنجليزي)</label><input class="form-control ltr" id="about_ck<?php echo $i; ?>_en" name="about_ck<?php echo $i; ?>_en" value="<?php echo e(setting('about_ck' . $i . '_en')); ?>"></div>
        <?php endfor; ?>
      </div>
      <div class="form-grid" style="margin-top:.4rem">
        <?php $imageField('about_image', 'صورة القسم', setting('about_image')); ?>
      </div>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ القسم</button></div></div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>عناوين الأقسام (القيم، الفريق، دعوة التواصل)</h2></div>
  <form method="post" action="homepage.php" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="save_labels">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="values_title_ar">عنوان القيم (عربي)</label><textarea class="form-control" id="values_title_ar" name="values_title_ar" rows="2"><?php echo e(setting('values_title_ar')); ?></textarea></div>
        <div class="form-group"><label for="values_title_en">عنوان القيم (إنجليزي)</label><textarea class="form-control ltr" id="values_title_en" name="values_title_en" rows="2"><?php echo e(setting('values_title_en')); ?></textarea></div>
        <div class="form-group"><label for="team_title_ar">عنوان الفريق (عربي)</label><textarea class="form-control" id="team_title_ar" name="team_title_ar" rows="2"><?php echo e(setting('team_title_ar')); ?></textarea></div>
        <div class="form-group"><label for="team_title_en">عنوان الفريق (إنجليزي)</label><textarea class="form-control ltr" id="team_title_en" name="team_title_en" rows="2"><?php echo e(setting('team_title_en')); ?></textarea></div>
        <div class="form-group"><label for="cta_title_ar">عنوان دعوة التواصل (عربي)</label><input class="form-control" id="cta_title_ar" name="cta_title_ar" value="<?php echo e(setting('cta_title_ar')); ?>"></div>
        <div class="form-group"><label for="cta_title_en">عنوان دعوة التواصل (إنجليزي)</label><input class="form-control ltr" id="cta_title_en" name="cta_title_en" value="<?php echo e(setting('cta_title_en')); ?>"></div>
        <div class="form-group"><label for="cta_desc_ar">وصف دعوة التواصل (عربي)</label><textarea class="form-control" id="cta_desc_ar" name="cta_desc_ar" rows="2"><?php echo e(setting('cta_desc_ar')); ?></textarea></div>
        <div class="form-group"><label for="cta_desc_en">وصف دعوة التواصل (إنجليزي)</label><textarea class="form-control ltr" id="cta_desc_en" name="cta_desc_en" rows="2"><?php echo e(setting('cta_desc_en')); ?></textarea></div>
      </div>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ العناوين</button></div></div>
  </form>
</div>

<div class="panel" id="stats">
  <div class="panel-head"><h2>الإحصائيات والأرقام</h2></div>
  <form method="post" action="homepage.php" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="stat_save"><input type="hidden" name="id" value="<?php echo (int) ($editStat['id'] ?? 0); ?>">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="stat_value">الرقم</label><input class="form-control ltr" type="number" id="stat_value" name="value" value="<?php echo (int) ($editStat['value'] ?? 0); ?>" required></div>
        <div class="form-group"><label for="stat_suffix">اللاحقة</label><input class="form-control ltr" id="stat_suffix" name="suffix" value="<?php echo e($editStat['suffix'] ?? '+'); ?>" maxlength="10"></div>
        <div class="form-group"><label for="stat_label_ar">التسمية (عربي)</label><input class="form-control" id="stat_label_ar" name="label_ar" value="<?php echo e($editStat['label_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="stat_label_en">التسمية (إنجليزي)</label><input class="form-control ltr" id="stat_label_en" name="label_en" value="<?php echo e($editStat['label_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="stat_sort">الترتيب</label><input class="form-control ltr" type="number" id="stat_sort" name="sort_order" value="<?php echo (int) ($editStat['sort_order'] ?? 0); ?>"></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$editStat || (int) $editStat['is_active']) ? 'checked' : ''; ?>> نشطة</label></div>
      </div>
      <?php if ($editStat): ?>
        <p class="help-box"><b>تعديل الإحصائية #<?php echo (int) $editStat['id']; ?>:</b> <a href="homepage.php#stats" style="font-weight:700">إلغاء التعديل</a></p>
      <?php endif; ?>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><?php echo $editStat ? 'تحديث' : 'إضافة'; ?> الإحصائية</button></div></div>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>الرقم</th><th>التسمية</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($stats as $row): ?>
          <tr>
            <td class="cell-title"><?php echo (int) $row['value'] . e($row['suffix']); ?></td>
            <td><?php echo e($row['label_ar']); ?> <span class="cell-sub">— <?php echo e($row['label_en']); ?></span></td>
            <td><?php echo (int) $row['sort_order']; ?></td>
            <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
            <td class="actions-cell">
              <a class="btn btn-outline btn-sm btn-icon" href="homepage.php?edit_stat=<?php echo (int) $row['id']; ?>#stats" title="تعديل"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="homepage.php" class="inline-form">
                <?php echo csrf_field(); ?><input type="hidden" name="action" value="stat_delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف هذه الإحصائية؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel" id="process">
  <div class="panel-head"><h2>خطوات العمل</h2></div>
  <form method="post" action="homepage.php" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="process_save"><input type="hidden" name="id" value="<?php echo (int) ($editProcess['id'] ?? 0); ?>">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="ptitle_ar">العنوان (عربي)</label><input class="form-control" id="ptitle_ar" name="title_ar" value="<?php echo e($editProcess['title_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="ptitle_en">العنوان (إنجليزي)</label><input class="form-control ltr" id="ptitle_en" name="title_en" value="<?php echo e($editProcess['title_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="pdesc_ar">الوصف (عربي)</label><textarea class="form-control" id="pdesc_ar" name="desc_ar" rows="2"><?php echo e($editProcess['desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="pdesc_en">الوصف (إنجليزي)</label><textarea class="form-control ltr" id="pdesc_en" name="desc_en" rows="2"><?php echo e($editProcess['desc_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="psort">الترتيب</label><input class="form-control ltr" type="number" id="psort" name="sort_order" value="<?php echo (int) ($editProcess['sort_order'] ?? 0); ?>"></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$editProcess || (int) $editProcess['is_active']) ? 'checked' : ''; ?>> نشطة</label></div>
      </div>
      <?php if ($editProcess): ?>
        <p class="help-box"><b>تعديل الخطوة #<?php echo (int) $editProcess['id']; ?>:</b> <a href="homepage.php#process" style="font-weight:700">إلغاء التعديل</a></p>
      <?php endif; ?>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><?php echo $editProcess ? 'تحديث' : 'إضافة'; ?> الخطوة</button></div></div>
  </form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>العنوان</th><th>الوصف</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($process as $row): ?>
          <tr>
            <td class="cell-title"><?php echo (int) $row['sort_order']; ?></td>
            <td><?php echo e($row['title_ar']); ?> <span class="cell-sub">— <?php echo e($row['title_en']); ?></span></td>
            <td class="muted" style="max-width:320px"><?php echo e(mb_strimwidth($row['desc_ar'], 0, 80, '…')); ?></td>
            <td><?php echo (int) $row['sort_order']; ?></td>
            <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
            <td class="actions-cell">
              <a class="btn btn-outline btn-sm btn-icon" href="homepage.php?edit_process=<?php echo (int) $row['id']; ?>#process" title="تعديل"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="homepage.php" class="inline-form">
                <?php echo csrf_field(); ?><input type="hidden" name="action" value="process_delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف هذه الخطوة؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
