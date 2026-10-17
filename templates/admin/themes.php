<?php
/**
 * @var \TechNest\View\View $this
 * @var list<array{slug: string, name: string, active: bool}> $themes
 */
$this->layout('admin/shell', ['title' => 'Themes']);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Themes</h1>
        <p class="muted">The active theme applies to every visitor immediately. Each preview below is drawn with that theme’s own colours.</p>
    </div>
</div>

<form method="post" action="/admin/themes">
    <?= $this->csrfField() ?>
    <div class="theme-grid">
        <?php foreach ($themes as $theme): ?>
            <label class="theme-option">
                <input type="radio" name="theme" value="<?= $this->e($theme['slug']) ?>"<?= $theme['active'] ? ' checked' : '' ?>>
                <span class="theme-preview" data-theme="<?= $this->e($theme['slug']) ?>" aria-hidden="true">
                    <span class="theme-preview__bar"><span class="theme-preview__logo"></span><span class="theme-preview__nav"></span></span>
                    <span class="theme-preview__body">
                        <span class="theme-preview__card"></span><span class="theme-preview__card"></span><span class="theme-preview__card"></span>
                        <span class="theme-preview__button"></span>
                    </span>
                </span>
                <span class="theme-option__name"><?= $this->e($theme['name']) ?><?= $theme['active'] ? ' <span class="tag tag--success">Active</span>' : '' ?></span>
            </label>
        <?php endforeach ?>
    </div>
    <button type="submit" class="button button--primary">Apply theme</button>
</form>
