    </main>
  </div>
</div>
<div class="overlay" id="adminOverlay"></div>
<?php $adminScriptVersion = (string) (filemtime(__DIR__ . '/../assets/admin.js') ?: 1); ?>
<?php $localTimeScriptVersion = (string) (filemtime(APP_ROOT . '/js/local-time.js') ?: 1); ?>
<?php if (($adminActive ?? '') === 'blog' && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'blog-edit.php'): ?>
<?php $blogEditorScriptVersion = (string) (filemtime(__DIR__ . '/../assets/blog-editor.js') ?: 1); ?>
<script src="<?php echo e(asset('admin/assets/blog-editor.js') . '?v=' . rawurlencode($blogEditorScriptVersion)); ?>" defer></script>
<?php endif; ?>
<?php if (($adminActive ?? '') === 'inquiries'): ?>
<?php $inquiryPhoneScriptVersion = (string) (filemtime(__DIR__ . '/../assets/inquiry-phone.js') ?: 1); ?>
<script src="<?php echo e(asset('assets/vendor/intl-tel-input/js/intlTelInputWithUtils.min.js') . '?v=29.5.8'); ?>"></script>
<script src="<?php echo e(asset('admin/assets/inquiry-phone.js') . '?v=' . rawurlencode($inquiryPhoneScriptVersion)); ?>"></script>
<?php endif; ?>
<script src="assets/admin.js?v=<?php echo e(rawurlencode($adminScriptVersion)); ?>"></script>
<script src="<?php echo e(asset('js/local-time.js') . '?v=' . rawurlencode($localTimeScriptVersion)); ?>" defer></script>
</body>
</html>
