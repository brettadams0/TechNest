<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use TechNest\Catalog\Category;
use TechNest\Catalog\CategoryRepository;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductRepository;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\Support\Str;
use TechNest\Support\Validator;
use TechNest\View\View;

/**
 * Product management. There are no image uploads by design: an admin picks
 * from the images already deployed with the store, so no user-supplied file
 * ever lands in the web root.
 */
final class ProductController
{
    public function __construct(
        private readonly View $view,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly Flash $flash,
        private readonly string $imageDir,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->query('q');

        return Response::html($this->view->render('admin/products/index', [
            'products' => $this->products->adminList($search),
            'search' => $search,
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->form(null, [], []);
    }

    public function store(Request $request): Response
    {
        [$data, $errors] = $this->validate($request, null);
        if ($errors !== []) {
            return $this->form(null, $errors, $request->body, 422);
        }

        $id = $this->products->create($data);
        $this->flash->success("{$data['name']} was added. Add its options below.");

        return Response::redirect("/admin/products/{$id}/edit");
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->form($this->find($id), [], []);
    }

    public function update(Request $request, string $id): Response
    {
        $product = $this->find($id);
        [$data, $errors] = $this->validate($request, $product->id);
        if ($errors !== []) {
            return $this->form($product, $errors, $request->body, 422);
        }

        $this->products->update($product->id, $data);
        $this->flash->success("{$data['name']} was saved.");

        return Response::redirect("/admin/products/{$product->id}/edit");
    }

    public function toggleArchived(Request $request, string $id): Response
    {
        $product = $this->find($id);
        $this->products->setActive($product->id, !$product->isActive);
        $this->flash->success($product->isActive
            ? "{$product->name} is archived: hidden from the store, kept in order history."
            : "{$product->name} is back on sale.");

        return Response::redirect('/admin/products');
    }

    public function addOption(Request $request, string $id): Response
    {
        $product = $this->find($id);
        $v = new Validator($request->body);
        $group = $v->string('group', 'Option name', 1, 40);
        $value = $v->string('value', 'Choice', 1, 60);
        $extra = $v->money('extra', 'Extra price');

        if (!$v->fails() && $this->products->optionExists($product->id, $group, $value)) {
            $v->addError('value', "{$group}: {$value} already exists.");
        }

        if ($v->fails()) {
            $this->flash->error(implode(' ', $v->errors()));
        } else {
            $this->products->addOption($product->id, $group, $value, $extra);
            $this->flash->success("Added {$group}: {$value}.");
        }

        return Response::redirect("/admin/products/{$product->id}/edit#options");
    }

    public function deleteOption(Request $request, string $id, string $optionId): Response
    {
        $product = $this->find($id);
        if ($this->products->deleteOption($product->id, (int) $optionId)) {
            $this->flash->success('Option removed. Carts holding it will ask the customer to choose again.');
        }

        return Response::redirect("/admin/products/{$product->id}/edit#options");
    }

    private function find(string $id): Product
    {
        return $this->products->find((int) $id) ?? throw HttpException::notFound('That product does not exist.');
    }

    /**
     * @return array{0: array{category_id: int, slug: string, name: string, description: string, image: ?string, price_cents: int, stock: int, is_featured: bool}, 1: array<string, string>}
     */
    private function validate(Request $request, ?int $productId): array
    {
        $v = new Validator($request->body);
        $name = $v->string('name', 'Name', 2, 120);
        $slugInput = trim($request->input('slug'));
        $slug = $slugInput === '' ? Str::slug($name) : $slugInput;
        $description = $v->string('description', 'Description', 0, 2000);
        $price = $v->money('price', 'Price');
        $stock = $v->int('stock', 'Stock', 0, 100000);

        $categoryIds = array_map(static fn(Category $c): int => $c->id, $this->categories->all());
        $categoryId = $request->inputInt('category_id');
        if (!in_array($categoryId, $categoryIds, true)) {
            $v->addError('category_id', 'Choose a category.');
        }

        $image = $request->input('image');
        if ($image !== '' && !in_array($image, $this->images(), true)) {
            $v->addError('image', 'Choose one of the listed images.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1 || strlen($slug) > 120) {
            $v->addError('slug', 'Use lowercase letters, numbers and single dashes, e.g. pixel-9-pro.');
        } elseif ($this->products->slugExists($slug, $productId)) {
            $v->addError('slug', 'Another product already uses that URL.');
        }

        return [[
            'category_id' => $categoryId,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'image' => $image === '' ? null : $image,
            'price_cents' => $price,
            'stock' => $stock,
            'is_featured' => $v->checkbox('is_featured'),
        ], $v->errors()];
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $old
     */
    private function form(?Product $product, array $errors, array $old, int $status = 200): Response
    {
        return Response::html($this->view->render('admin/products/form', [
            'product' => $product,
            'categories' => $this->categories->all(),
            'images' => $this->images(),
            'errors' => $errors,
            'old' => $old,
        ]), $status);
    }

    /** @return list<string> */
    private function images(): array
    {
        $files = glob($this->imageDir . '/*.{svg,webp,png,jpg}', GLOB_BRACE) ?: [];
        $names = array_map('basename', $files);
        $names = array_values(array_filter($names, static fn(string $n): bool => $n !== 'placeholder.svg'));
        sort($names);

        return $names;
    }
}
