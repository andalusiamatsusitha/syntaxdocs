<?php
$id = $id ?? 'modalDialog';
$title = $title ?? 'Dialog Modal';
$body = $body ?? '';
$cancelLabel = $cancel_label ?? 'Batal';
$confirmLabel = $confirm_label ?? null;
$confirmAction = $confirm_action ?? null;
$confirmMethod = strtoupper($confirm_method ?? 'POST');
$variant = $variant ?? 'primary';
?>
<div class="c-modal" id="<?= e($id) ?>" aria-hidden="true" role="dialog">
  <div class="c-modal__backdrop" data-dismiss="modal"></div>
  <div class="c-modal__dialog">
    <div class="c-modal__header">
      <h4 class="c-modal__title"><?= e($title) ?></h4>
      <button type="button" class="c-modal__close" data-dismiss="modal" aria-label="Tutup">&times;</button>
    </div>
    <div class="c-modal__body">
      <?= $body ?>
    </div>
    <div class="c-modal__footer">
      <button type="button" class="c-btn c-btn--secondary" data-dismiss="modal"><?= e($cancelLabel) ?></button>
      <?php if ($confirmLabel && $confirmAction): ?>
        <form method="<?= $confirmMethod === 'GET' ? 'GET' : 'POST' ?>" action="<?= e($confirmAction) ?>" style="display:inline;">
          <?php if ($confirmMethod !== 'GET'): ?>
            <?= csrf_field() ?>
          <?php endif; ?>
          <button type="submit" class="c-btn c-btn--<?= e($variant) ?>"><?= e($confirmLabel) ?></button>
        </form>
      <?php elseif ($confirmLabel): ?>
        <button type="button" class="c-btn c-btn--<?= e($variant) ?>"><?= e($confirmLabel) ?></button>
      <?php endif; ?>
    </div>
  </div>
</div>
