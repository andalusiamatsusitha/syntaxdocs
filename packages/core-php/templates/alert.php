<?php
$message = $message ?? '';
$type = $type ?? 'info';
$dismissible = $dismissible ?? true;
?>
<div class="c-alert c-alert--<?= e($type) ?>" role="alert">
  <div><?= $message ?></div>
  <?php if ($dismissible): ?>
    <button type="button" class="c-alert__close" data-dismiss="alert" aria-label="Tutup">&times;</button>
  <?php endif; ?>
</div>
