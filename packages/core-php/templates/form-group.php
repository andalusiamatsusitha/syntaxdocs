<?php
$name = $name ?? '';
$label = $label ?? null;
$type = $type ?? 'text';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$required = !empty($required);
$help = $help ?? null;
$error = $error ?? null;
$options = $options ?? [];
$id = $id ?? ('input_' . $name);
?>
<div class="c-form-group">
  <?php if ($label): ?>
    <label class="c-form-label" for="<?= e($id) ?>">
      <?= e($label) ?>
      <?php if ($required): ?>
        <span style="color: var(--color-danger);">*</span>
      <?php endif; ?>
    </label>
  <?php endif; ?>

  <?php if ($type === 'select'): ?>
    <select class="c-form-select" id="<?= e($id) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?>>
      <?php foreach ($options as $optVal => $optLabel): ?>
        <?php
        $actualVal = is_array($optLabel) ? ($optLabel['value'] ?? $optVal) : $optVal;
        $actualText = is_array($optLabel) ? ($optLabel['label'] ?? $optVal) : $optLabel;
        $selected = ($actualVal == $value) ? 'selected' : '';
        ?>
        <option value="<?= e($actualVal) ?>" <?= $selected ?>><?= e($actualText) ?></option>
      <?php endforeach; ?>
    </select>
  <?php elseif ($type === 'textarea'): ?>
    <textarea class="c-form-textarea" id="<?= e($id) ?>" name="<?= e($name) ?>" placeholder="<?= e($placeholder) ?>" <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>
  <?php else: ?>
    <input type="<?= e($type) ?>" class="c-form-input" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="<?= e($placeholder) ?>" <?= $required ? 'required' : '' ?>>
  <?php endif; ?>

  <?php if ($help): ?>
    <span class="c-form-help"><?= e($help) ?></span>
  <?php endif; ?>

  <?php if ($error): ?>
    <span class="c-form-error"><?= e($error) ?></span>
  <?php endif; ?>
</div>
