<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\CheckoutService;
use TechNest\Checkout\PriceCalculator;
use TechNest\Checkout\ShippingAddress;
use TechNest\Order\InvalidTransition;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderService;
use TechNest\Order\OrderStatus;
use TechNest\Security\ArraySession;
use TechNest\Tests\Support\DatabaseTestCase;
use TechNest\User\User;

final class OrderServiceTest extends DatabaseTestCase
{
    private OrderService $service;
    private OrderRepository $orders;
    private User $customer;
    private User $admin;
    private int $productId;
    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new OrderRepository($this->db);
        $this->service = new OrderService($this->db, $this->orders, $this->clock);
        $this->customer = $this->fixtures->user('carol');
        $this->admin = $this->fixtures->user('root', admin: true);

        $product = $this->fixtures->product(['stock' => 10]);
        $this->productId = $product->id;
        $cart = new Cart(new ArraySession());
        $cart->add($product->id, [], 3);
        $checkout = new CheckoutService($this->db, new CartPricer(new ProductRepository($this->db, $this->clock), new PriceCalculator()), $this->orders, $this->clock);
        $this->orderId = $checkout->placeOrder($this->customer, $cart, new ShippingAddress('Carol', '1 Main St', 'Windsor', 'ON', 'N9A 1B2'));
    }

    public function testWalksTheHappyPathAndRecordsWhoDidWhat(): void
    {
        $this->clock->advance('+1 hour');
        $this->service->transition($this->orderId, OrderStatus::Processing, $this->admin->id);
        $this->service->transition($this->orderId, OrderStatus::Shipped, $this->admin->id);
        $order = $this->service->transition($this->orderId, OrderStatus::Delivered, $this->admin->id);

        self::assertSame(OrderStatus::Delivered, $order->status);
        self::assertSame(
            [[null, 'pending', 'carol'], ['pending', 'processing', 'root'], ['processing', 'shipped', 'root'], ['shipped', 'delivered', 'root']],
            array_map(static fn($c): array => [$c->from?->value, $c->to->value, $c->changedBy], $order->history),
        );
        self::assertSame('2026-03-10 16:00:00', $order->updatedAt);
    }

    public function testIllegalTransitionsChangeNothing(): void
    {
        $this->service->transition($this->orderId, OrderStatus::Processing, $this->admin->id);
        $this->service->transition($this->orderId, OrderStatus::Shipped, $this->admin->id);

        try {
            $this->service->transition($this->orderId, OrderStatus::Cancelled, $this->admin->id);
            self::fail('A shipped order must not be cancellable');
        } catch (InvalidTransition $e) {
            self::assertStringContainsString('cannot be marked cancelled', $e->getMessage());
        }

        self::assertSame(OrderStatus::Shipped, $this->orders->find($this->orderId)?->status);
        self::assertSame(7, $this->fixtures->stock($this->productId), 'No stock was returned');
    }

    public function testCancellingReturnsExactlyTheOrderedStock(): void
    {
        self::assertSame(7, $this->fixtures->stock($this->productId));

        $this->service->transition($this->orderId, OrderStatus::Cancelled, $this->admin->id);

        self::assertSame(10, $this->fixtures->stock($this->productId));
    }

    public function testCustomersCanCancelTheirOwnPendingOrder(): void
    {
        $order = $this->service->cancelByCustomer($this->orderId, $this->customer->id);

        self::assertSame(OrderStatus::Cancelled, $order->status);
        $last = array_slice($order->history, -1)[0] ?? null;
        self::assertSame('carol', $last?->changedBy);
    }

    public function testCustomersCannotCancelSomeoneElsesOrder(): void
    {
        $this->expectException(InvalidTransition::class);
        $this->service->cancelByCustomer($this->orderId, $this->admin->id);
    }

    public function testCustomersCannotCancelOnceProcessingStarts(): void
    {
        $this->service->transition($this->orderId, OrderStatus::Processing, $this->admin->id);

        $this->expectException(InvalidTransition::class);
        $this->expectExceptionMessage('can no longer be cancelled online');
        $this->service->cancelByCustomer($this->orderId, $this->customer->id);
    }

    public function testAGuardedTransitionRefusesAnOrderThatMovedOn(): void
    {
        // The customer's cancel checked "pending", then an admin started
        // processing before the cancel's transaction ran.
        $this->service->transition($this->orderId, OrderStatus::Processing, $this->admin->id);

        try {
            $this->service->transition($this->orderId, OrderStatus::Cancelled, $this->customer->id, OrderStatus::Pending);
            self::fail('The guard must stop the cancel');
        } catch (InvalidTransition $e) {
            self::assertStringContainsString('is now processing', $e->getMessage());
        }
        self::assertSame(OrderStatus::Processing, $this->orders->find($this->orderId)?->status);
    }

    public function testCompareAndSetLosesToAConcurrentChange(): void
    {
        $now = '2026-03-10 15:00:00';

        self::assertTrue($this->orders->compareAndSetStatus($this->orderId, OrderStatus::Pending, OrderStatus::Processing, $now));
        // A second admin acting on the stale "pending" read loses the race.
        self::assertFalse($this->orders->compareAndSetStatus($this->orderId, OrderStatus::Pending, OrderStatus::Cancelled, $now));
        self::assertSame(OrderStatus::Processing, $this->orders->find($this->orderId)?->status);
    }

    public function testCountsByStatusIncludeEveryStatus(): void
    {
        self::assertSame(
            ['pending' => 1, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0],
            $this->orders->countByStatus(),
        );
    }
}
