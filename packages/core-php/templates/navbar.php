<?php
$brand = $brand ?? 'Syntax App';
$brandUrl = $brand_url ?? '/';
$items = $items ?? [];
$rightHtml = $right_html ?? '';
?>
<header class="c-navbar">
  <div class="c-container c-navbar__container">
    <a href="<?= e($brandUrl) ?>" class="c-navbar__brand">
      <?php if (!empty($brand_icon)): ?>
        <i class="<?= e($brand_icon) ?>" style="margin-right: 8px;"></i>
      <?php else: ?>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M16 18l6-6-6-6M8 6l-6 6 6 6" />
        </svg>
      <?php endif; ?>
      <span><?= e($brand) ?></span>
    </a>

    <?php if (!empty($items)): ?>
    <nav>
      <ul class="c-navbar__nav">
        <?php foreach ($items as $item): ?>
          <li>
            <a href="<?= e($item['url'] ?? '#') ?>" class="c-navbar__link <?= (!empty($item['active'])) ? 'is-active' : '' ?>">
              <?php if (!empty($item['icon'])): ?>
                <i class="<?= e($item['icon']) ?>" style="margin-right: 6px;"></i>
              <?php endif; ?>
              <?= e($item['label'] ?? '') ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php if (!empty($rightHtml)): ?>
      <div class="c-navbar__right">
        <?= $rightHtml ?>
      </div>
    <?php endif; ?>
  </div>
</header>
