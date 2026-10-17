<?php

declare(strict_types=1);

namespace TechNest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TechNest\Cart\Cart;
use TechNest\Security\ArraySession;

final class CartTest extends TestCase
{
    private ArraySession $session;
    private Cart $cart;

    protected function setUp(): void
    {
        $this->session = new ArraySession();
        $this->cart = new Cart($this->session);
    }

    public function testSameConfigurationMergesIntoOneLine(): void
    {
        $this->cart->add(1, [3, 7], 1);
        $this->cart->add(1, [7, 3], 2);

        self::assertCount(1, $this->cart->lines());
        self::assertSame(3, $this->cart->lines()[0]->quantity);
    }

    public function testDifferentOptionsAreSeparateLines(): void
    {
        $this->cart->add(1, [3, 7], 1);
        $this->cart->add(1, [4, 7], 1);

        self::assertCount(2, $this->cart->lines());
        self::assertSame(2, $this->cart->count());
    }

    public function testQuantitiesAreClampedToTheAllowedRange(): void
    {
        $key = $this->cart->add(1, [], 50);
        self::assertSame(Cart::MAX_QUANTITY, $this->cart->lines()[0]->quantity);

        $this->cart->update($key, -3);
        self::assertTrue($this->cart->isEmpty(), 'Updating to zero or below removes the line');
    }

    public function testUpdatingAnUnknownLineDoesNothing(): void
    {
        $this->cart->update('999:1', 2);

        self::assertTrue($this->cart->isEmpty());
    }

    public function testRemoveAndClear(): void
    {
        $key = $this->cart->add(1, [], 1);
        $this->cart->add(2, [], 1);
        $this->cart->remove($key);
        self::assertCount(1, $this->cart->lines());

        $this->cart->clear();
        self::assertTrue($this->cart->isEmpty());
    }

    public function testTamperedSessionDataIsIgnoredNotTrusted(): void
    {
        $this->session->set('cart', [
            '1:' => ['product_id' => 1, 'option_ids' => [], 'quantity' => 2],
            'bad-1' => ['product_id' => '1; DROP TABLE', 'option_ids' => [], 'quantity' => 1],
            'bad-2' => 'not an array',
            'bad-3' => ['product_id' => 2, 'option_ids' => [5, 'x'], 'quantity' => 999],
        ]);

        $lines = $this->cart->lines();

        self::assertCount(2, $lines);
        self::assertSame([5], $lines[1]->optionIds);
        self::assertSame(Cart::MAX_QUANTITY, $lines[1]->quantity);
    }

    public function testTheSessionNeverHoldsPrices(): void
    {
        $this->cart->add(1, [2], 1);

        self::assertSame(
            ['1:2' => ['product_id' => 1, 'option_ids' => [2], 'quantity' => 1]],
            $this->session->get('cart'),
        );
    }
}
