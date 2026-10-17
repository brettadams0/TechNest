<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Catalog\InvalidSelection;
use TechNest\Catalog\OptionSelection;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\PriceCalculator;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\View\View;

final class CartController
{
    public function __construct(
        private readonly View $view,
        private readonly Cart $cart,
        private readonly CartPricer $pricer,
        private readonly ProductRepository $products,
        private readonly PriceCalculator $calculator,
        private readonly Flash $flash,
    ) {}

    public function show(Request $request): Response
    {
        return Response::html($this->view->render('shop/cart', [
            'cart' => $this->pricer->price($this->cart),
            'calculator' => $this->calculator,
        ]));
    }

    public function add(Request $request): Response
    {
        $product = $this->products->find($request->inputInt('product_id'));
        if ($product === null || !$product->isActive) {
            $this->flash->error('That product is no longer available.');

            return Response::redirect('/catalog');
        }

        if (!$product->isInStock()) {
            $this->flash->error("{$product->name} is sold out.");

            return Response::redirect($product->url());
        }

        try {
            $options = OptionSelection::resolve($product, $request->inputIntMap('options'));
        } catch (InvalidSelection $e) {
            $this->flash->error($e->getMessage());

            return Response::redirect($product->url());
        }

        $quantity = max(1, min(Cart::MAX_QUANTITY, $request->inputInt('quantity', 1)));
        $this->cart->add($product->id, array_map(static fn($o): int => $o->id, $options), $quantity);
        $this->flash->success("Added {$product->name} to your cart.");

        return Response::redirect('/cart');
    }

    public function update(Request $request): Response
    {
        $this->cart->update($request->input('key'), $request->inputInt('quantity', 1));

        return Response::redirect('/cart');
    }

    public function remove(Request $request): Response
    {
        $this->cart->remove($request->input('key'));
        $this->flash->info('Item removed from your cart.');

        return Response::redirect('/cart');
    }
}
