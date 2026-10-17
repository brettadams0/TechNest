<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\PriceCalculator;
use TechNest\Security\ArraySession;
use TechNest\Tests\Support\DatabaseTestCase;

final class CartPricerTest extends DatabaseTestCase
{
    private CartPricer $pricer;
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricer = new CartPricer(new ProductRepository($this->db, $this->clock), new PriceCalculator());
        $this->cart = new Cart(new ArraySession());
    }

    public function testPricesLinesWithTheirOptions(): void
    {
        $phone = $this->fixtures->phone();
        $this->cart->add($phone->id, [$phone->options[0]->id, $phone->options[3]->id], 2);

        $priced = $this->pricer->price($this->cart);

        self::assertSame(109900, $priced->lines[0]->unitCents());
        self::assertSame(219800, $priced->totals->subtotalCents);
        self::assertSame(0, $priced->totals->shippingCents);
        self::assertSame([], $priced->notices);
    }

    public function testArchivedProductsAreRemovedWithANotice(): void
    {
        $product = $this->fixtures->product();
        $this->cart->add($product->id, [], 1);
        $this->db->execute('UPDATE products SET is_active = 0 WHERE id = ?', [$product->id]);

        $priced = $this->pricer->price($this->cart);

        self::assertTrue($priced->isEmpty());
        self::assertTrue($this->cart->isEmpty(), 'The session cart is reconciled too');
        self::assertSame(['An item in your cart is no longer sold and was removed.'], $priced->notices);
    }

    public function testADeletedOptionInvalidatesTheLine(): void
    {
        $phone = $this->fixtures->phone();
        $this->cart->add($phone->id, [$phone->options[0]->id, $phone->options[2]->id], 1);
        $this->db->execute('DELETE FROM product_options WHERE id = ?', [$phone->options[2]->id]);

        $priced = $this->pricer->price($this->cart);

        self::assertTrue($priced->isEmpty());
        self::assertStringContainsString('no longer available', $priced->notices[0]);
    }

    public function testQuantitiesAreReducedToWhatIsInStock(): void
    {
        $product = $this->fixtures->product(['name' => 'Rare', 'stock' => 3]);
        $this->cart->add($product->id, [], 8);

        $priced = $this->pricer->price($this->cart);

        self::assertSame(3, $priced->lines[0]->quantity);
        self::assertSame(3, $this->cart->lines()[0]->quantity);
        self::assertSame(['Only 3 Rare left in stock; your quantity was reduced.'], $priced->notices);
    }

    public function testStockIsSharedBetweenConfigurationsOfOneProduct(): void
    {
        $phone = $this->fixtures->phone(stock: 3);
        $this->cart->add($phone->id, [$phone->options[0]->id, $phone->options[2]->id], 2);
        $this->cart->add($phone->id, [$phone->options[1]->id, $phone->options[2]->id], 2);

        $priced = $this->pricer->price($this->cart);

        self::assertSame([2, 1], array_map(static fn($l): int => $l->quantity, $priced->lines));
    }
}
