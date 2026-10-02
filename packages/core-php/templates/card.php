<?php
$title = $title ?? null;
$subtitle = $subtitle ?? null;
$badge = $badge ?? null;
$body = $body ?? '';
$footer = $footer ?? null;
?>
<div class="c-card">
  <?php if ($title || $badge): ?>
    <div class="c-card__header">
      <div>
        <?php if ($title): ?>
          <h4 class="c-card__title"><?= e($title) ?></h4>
        <?php endif; ?>
        <?php if ($subtitle): ?>
          <small class="text-muted"><?= e($subtitle) ?></small>
        <?php endif; ?>
      </div>
      <?php if ($badge): ?>
        <span class="c-badge c-badge--<?= e($badge['variant'] ?? 'primary') ?>">
          <?= e($badge['label'] ?? '') ?>
        </span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="c-card__body">
    <?= $body ?>
  </div>

  <?php if ($footer): ?>
    <div class="c-card__footer">
      <?= $footer ?>
    </div>
  <?php endif; ?>
</div>
