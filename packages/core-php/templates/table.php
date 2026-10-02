<?php
$headers = $headers ?? [];
$rows = $rows ?? [];
$striped = $striped ?? true;
$hover = $hover ?? true;

$classes = ['c-table'];
if ($striped) $classes[] = 'c-table--striped';
if ($hover) $classes[] = 'c-table--hover';
?>
<div class="c-table-wrapper">
  <table class="<?= implode(' ', $classes) ?>">
    <?php if (!empty($headers)): ?>
      <thead>
        <tr>
          <?php foreach ($headers as $header): ?>
            <th><?= e($header) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
    <?php endif; ?>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="<?= count($headers) ?: 1 ?>" class="text-center text-muted py-4">
            Tidak ada data untuk ditampilkan.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($row as $cell): ?>
              <td><?= $cell ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
