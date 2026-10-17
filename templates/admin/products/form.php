<?php
/**
 * @var \TechNest\View\View $this
 * @var ?\TechNest\Catalog\Product $product
 * @var list<\TechNest\Catalog\Category> $categories
 * @var list<string> $images
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 */
use TechNest\Support\Money;

$editing = $product !== null;
$this->layout('admin/shell', ['title' => $editing ? 'Edit ' . $product->name : 'New product']);

$value = fn (string $field, string $current): string => $old !== [] ? $this->old($old, $field) : $current;
$name = $value('name', $product->name ?? '');
$slug = $value('slug', $product->slug ?? '');
$description = $value('description', $product->description ?? '');
$price = $value('price', $editing ? Money::toDecimal($product->priceCents) : '');
$stock = $value('stock', $editing ? (string) $product->stock : '0');
$categoryId = (int) $value('category_id', (string) ($product->categoryId ?? 0));
$image = $value('image', $product->image ?? '');
$featured = $old !== [] ? $this->old($old, 'is_featured') === '1' : ($product->isFeatured ?? false);
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="/admin/products">Products</a> <?= $this->icon('chevron-right') ?> <span aria-current="page"><?= $editing ? $this->e($product->name) : 'New product' ?></span>
</nav>

<div class="page-head">
    <h1 class="page-title"><?= $editing ? $this->e($product->name) : 'New product' ?></h1>
    <?php if ($editing && $product->isActive): ?>
        <a class="button button--ghost" href="<?= $this->e($product->url()) ?>"><?= $this->icon('external') ?> View in store</a>
    <?php endif ?>
</div>

<div class="product-editor">
    <form class="card" method="post" action="<?= $editing ? '/admin/products/' . $product->id : '/admin/products' ?>" novalidate>
        <?= $this->csrfField() ?>
        <?php if ($editing): ?>
            <input type="hidden" name="stock_original" value="<?= $this->e($value('stock_original', (string) $product->stock)) ?>">
        <?php endif ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert"><?= $this->icon('alert') ?><p>Please fix the highlighted fields.</p></div>
        <?php endif ?>

        <div class="form-grid">
            <div class="field field--full">
                <label for="name">Name</label>
                <input id="name" name="name" required maxlength="120" value="<?= $this->e($name) ?>"<?= $this->fieldState($errors, 'name') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'name']) ?>
            </div>
            <div class="field field--full">
                <label for="slug">URL</label>
                <div class="input-prefix"><span>/products/</span><input id="slug" name="slug" maxlength="120" value="<?= $this->e($slug) ?>" placeholder="generated from the name" aria-describedby="slug-hint"<?= $this->fieldState($errors, 'slug') ?>></div>
                <p class="field__hint" id="slug-hint">Leave blank to generate it from the name.</p>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'slug']) ?>
            </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id"<?= $this->fieldState($errors, 'category_id') ?>>
                    <option value="">Choose…</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->id ?>"<?= $categoryId === $category->id ? ' selected' : '' ?>><?= $this->e($category->name) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'category_id']) ?>
            </div>
            <div class="field">
                <label for="image">Image</label>
                <select id="image" name="image"<?= $this->fieldState($errors, 'image') ?>>
                    <option value="">Placeholder</option>
                    <?php foreach ($images as $file): ?>
                        <option value="<?= $this->e($file) ?>"<?= $image === $file ? ' selected' : '' ?>><?= $this->e($file) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'image']) ?>
            </div>
            <div class="field">
                <label for="price">Base price (CAD)</label>
                <div class="input-prefix"><span>$</span><input id="price" name="price" inputmode="decimal" required value="<?= $this->e($price) ?>" placeholder="0.00"<?= $this->fieldState($errors, 'price') ?>></div>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'price']) ?>
            </div>
            <div class="field">
                <label for="stock">Stock</label>
                <input id="stock" name="stock" type="number" min="0" required value="<?= $this->e($stock) ?>"<?= $this->fieldState($errors, 'stock') ?>>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'stock']) ?>
            </div>
            <div class="field field--full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" maxlength="2000"<?= $this->fieldState($errors, 'description') ?>><?= $this->e($description) ?></textarea>
                <?= $this->insert('partials/field-error', ['errors' => $errors, 'field' => 'description']) ?>
            </div>
            <label class="checkbox field--full">
                <input type="checkbox" name="is_featured" value="1"<?= $featured ? ' checked' : '' ?>> Feature on the homepage
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button--primary"><?= $editing ? 'Save changes' : 'Create product' ?></button>
            <a class="button button--ghost" href="/admin/products">Cancel</a>
        </div>
    </form>

    <?php if ($editing): ?>
        <aside class="stack">
            <div class="card product-editor__preview">
                <img src="<?= $this->e($product->imageUrl()) ?>" alt="" width="600" height="600">
            </div>

            <section class="card" id="options" aria-labelledby="options-title">
                <h2 id="options-title">Options</h2>
                <p class="muted small">Customers choose one value from each option group. Extra prices add to the base price.</p>
                <?php if ($product->optionGroups() === []): ?>
                    <p class="muted">No options: the product sells as-is.</p>
                <?php endif ?>
                <?php foreach ($product->optionGroups() as $group => $options): ?>
                    <h3 class="option-group"><?= $this->e($group) ?></h3>
                    <ul class="option-list">
                        <?php foreach ($options as $option): ?>
                            <li>
                                <span><?= $this->e($option->value) ?></span>
                                <span class="muted"><?= $option->extraCents > 0 ? '+' . $this->money($option->extraCents) : 'included' ?></span>
                                <form method="post" action="/admin/products/<?= $product->id ?>/options/<?= $option->id ?>/delete" data-confirm="Remove <?= $this->e($group . ': ' . $option->value) ?>?">
                                    <?= $this->csrfField() ?>
                                    <button type="submit" class="icon-button" aria-label="Remove <?= $this->e($group . ': ' . $option->value) ?>"><?= $this->icon('trash') ?></button>
                                </form>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endforeach ?>

                <form method="post" action="/admin/products/<?= $product->id ?>/options" class="option-form">
                    <?= $this->csrfField() ?>
                    <h3 class="option-group">Add an option</h3>
                    <div class="form-grid form-grid--3">
                        <div class="field">
                            <label for="group">Group</label>
                            <input id="group" name="group" list="option-groups" required maxlength="40" placeholder="Color">
                            <datalist id="option-groups">
                                <?php foreach (array_keys($product->optionGroups()) as $group): ?><option value="<?= $this->e($group) ?>"><?php endforeach ?>
                            </datalist>
                        </div>
                        <div class="field">
                            <label for="value">Choice</label>
                            <input id="value" name="value" required maxlength="60" placeholder="Blue">
                        </div>
                        <div class="field">
                            <label for="extra">Extra</label>
                            <div class="input-prefix"><span>$</span><input id="extra" name="extra" inputmode="decimal" value="0.00"></div>
                        </div>
                    </div>
                    <button type="submit" class="button button--secondary"><?= $this->icon('plus') ?> Add option</button>
                </form>
            </section>
        </aside>
    <?php endif ?>
</div>
