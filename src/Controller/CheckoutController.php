<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Auth\Auth;
use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Cart\PricedCart;
use TechNest\Checkout\CheckoutException;
use TechNest\Checkout\CheckoutService;
use TechNest\Checkout\ShippingAddress;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\Support\Validator;
use TechNest\View\View;

final class CheckoutController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Cart $cart,
        private readonly CartPricer $pricer,
        private readonly CheckoutService $checkout,
        private readonly Flash $flash,
    ) {}

    public function show(Request $request): Response
    {
        $priced = $this->pricer->price($this->cart);
        if ($priced->isEmpty() || $priced->notices !== []) {
            return $this->backToCart($priced);
        }

        return $this->form($priced, [], []);
    }

    public function place(Request $request): Response
    {
        $v = new Validator($request->body);
        $name = $v->string('name', 'Full name', 2, 80);
        $street = $v->string('street', 'Street address', 5, 120);
        $city = $v->string('city', 'City', 2, 60);
        $province = $v->oneOf('province', 'province', ShippingAddress::PROVINCES);
        $postal = ShippingAddress::normalisePostalCode($request->input('postal_code'));
        if ($postal === null) {
            $v->addError('postal_code', 'Enter a Canadian postal code, like N9B 3P4.');
        }

        if ($v->fails() || $postal === null) {
            return $this->form($this->pricer->price($this->cart), $v->errors(), $request->body, 422);
        }

        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        try {
            $orderId = $this->checkout->placeOrder($user, $this->cart, new ShippingAddress($name, $street, $city, $province, $postal));
        } catch (CheckoutException $e) {
            $this->flash->error($e->getMessage());

            return Response::redirect('/cart');
        }

        $this->flash->success('Thank you — your order is placed. A confirmation is below.');

        return Response::redirect('/account/orders/' . $orderId);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $old
     */
    private function form(PricedCart $priced, array $errors, array $old, int $status = 200): Response
    {
        return Response::html($this->view->render('shop/checkout', [
            'cart' => $priced,
            'errors' => $errors,
            'old' => $old,
            'provinces' => ShippingAddress::PROVINCES,
        ]), $status);
    }

    private function backToCart(PricedCart $priced): Response
    {
        if ($priced->isEmpty()) {
            $this->flash->info('Your cart is empty.');
        }
        foreach ($priced->notices as $notice) {
            $this->flash->error($notice);
        }

        return Response::redirect('/cart');
    }
}
