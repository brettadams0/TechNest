<?php
/**
 * @var \TechNest\View\View $this
 * @var array<string, string> $errors
 * @var string $field
 */
if (isset($errors[$field])): ?>
    <p class="field__error" id="<?= $this->e($field) ?>-error"><?= $this->e($errors[$field]) ?></p>
<?php endif;
