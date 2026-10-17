<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Catalog\CatalogQuery;
use TechNest\Catalog\CategoryRepository;
use TechNest\Catalog\ProductRepository;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\View\View;

final class ShopController
{
    public function __construct(
        private readonly View $view,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
    ) {}

    public function home(Request $request): Response
    {
        return Response::html($this->view->render('shop/home', [
            'featured' => $this->products->featured(8),
            'categories' => $this->categories->all(),
        ]));
    }

    public function catalog(Request $request): Response
    {
        $query = CatalogQuery::fromRequest($request);

        $category = null;
        if ($query->category !== '') {
            $category = $this->categories->findBySlug($query->category) ?? throw HttpException::notFound('That category does not exist.');
        }

        $results = $this->products->search($query);
        if ($query->page > $results->lastPage()) {
            // A stale link past the end, e.g. after products were archived.
            $params = $query->params(['page' => $results->lastPage()]);

            return Response::redirect('/catalog' . ($params === [] ? '' : '?' . http_build_query($params)));
        }

        return Response::html($this->view->render('shop/catalog', [
            'query' => $query,
            'category' => $category,
            'categories' => $this->categories->all(),
            'results' => $results,
        ]));
    }

    public function product(Request $request, string $slug): Response
    {
        $product = $this->products->findBySlug($slug) ?? throw HttpException::notFound('That product is not in our catalog.');

        return Response::html($this->view->render('shop/product', [
            'product' => $product,
            'related' => $this->products->related($product),
        ]));
    }
}
