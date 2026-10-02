<?php
$brand = $brand ?? 'Syntax App';
$brandUrl = $brand_url ?? '/';
$items = $items ?? [];
$rightHtml = $right_html ?? '';
?>
<header class="c-navbar">
  <div class="c-container c-navbar__container">
    <a href="<?= e($brandUrl) ?>" class="c-navbar__brand">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M16 18l6-6-6-6M8 6l-6 6 6 6" />
      </svg>
      <span><?= e($brand) ?></span>
    </a>

    <nav>
      <ul class="c-navbar__nav">
        <?php foreach ($items as $item): ?>
          <li>
            <a href="<?= e($item['url'] ?? '#') ?>" class="c-navbar__link <?= (!empty($item['active'])) ? 'is-active' : '' ?>">
              <?= e($item['label'] ?? '') ?>
            </a>
          </li>
        <?php endforeach; ?>
        <?php if (!empty($rightHtml)): ?>
          <li class="d-flex align-center">
            <?= $rightHtml ?>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
