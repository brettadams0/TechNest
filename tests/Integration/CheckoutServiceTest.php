<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\CheckoutException;
use TechNest\Checkout\CheckoutService;
use TechNest\Checkout\PriceCalculator;
use TechNest\Checkout\ShippingAddress;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderStatus;
use TechNest\Security\ArraySession;
use TechNest\Tests\Support\DatabaseTestCase;
use TechNest\Tests\Support\TestDatabase;
use TechNest\User\User;

final class CheckoutServiceTest extends DatabaseTestCase
{
    private CheckoutService $checkout;
    private CartPricer $pricer;
    private OrderRepository $orders;
    private ProductRepository $products;
    private User $customer;
    private ShippingAddress $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = new ProductRepository($this->db, $this->clock);
        $this->orders = new OrderRepository($this->db);
        $this->pricer = new CartPricer($this->products, new PriceCalculator());
        $this->checkout = new CheckoutService($this->db, $this->pricer, $this->orders, $this->clock);
        $this->customer = $this->fixtures->user();
        $this->address = new ShippingAddress('Alice Example', '401 Sunset Ave', 'Windsor', 'ON', 'N9B 3P4');
    }

    public function testAProductWithTwoOptionGroupsBecomesOneOrderLine(): void
    {
        // Regression: the original checkout wrote one order_items row per
        // *option*, so this phone (colour + storage) became two lines.
        $phone = $this->fixtures->phone();
        $cart = $this->cartWith($phone, ['Color' => 'White', 'Storage' => '256GB'], 2);

        $order = $this->orders->find($this->checkout->placeOrder($this->customer, $cart, $this->address));

        self::assertNotNull($order);
        self::assertCount(1, $order->items);
        self::assertSame('Color: White · Storage: 256GB', $order->items[0]->optionsLabel);
        self::assertSame(109900, $order->items[0]->unitPriceCents);
        self::assertSame(2, $order->items[0]->quantity);
        self::assertSame(219800, $order->subtotalCents);
    }

    public function testAProductWithNoOptionsStillGetsAnOrderLine(): void
    {
        // Regression: with no options, the original loop wrote zero lines.
        $cable = $this->fixtures->product(['name' => 'USB-C Cable', 'price' => 1999]);
        $cart = $this->cartWith($cable, [], 1);

        $order = $this->orders->find($this->checkout->placeOrder($this->customer, $cart, $this->address));

        self::assertNotNull($order);
        self::assertCount(1, $order->items);
        self::assertSame('', $order->items[0]->optionsLabel);
    }

    public function testPlacingAnOrderTakesStockRecordsHistoryAndClearsTheCart(): void
    {
        $phone = $this->fixtures->phone(stock: 5);
        $cart = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '128GB'], 2);

        $orderId = $this->checkout->placeOrder($this->customer, $cart, $this->address);
        $order = $this->orders->find($orderId);

        self::assertSame(3, $this->fixtures->stock($phone->id));
        self::assertTrue($cart->isEmpty());
        self::assertNotNull($order);
        self::assertSame(OrderStatus::Pending, $order->status);
        self::assertCount(1, $order->history);
        self::assertNull($order->history[0]->from);
        self::assertSame('N9B 3P4', $order->shipTo->postalCode);
        self::assertSame(
            $order->subtotalCents + $order->shippingCents + $order->taxCents,
            $order->totalCents,
        );
    }

    public function testTotalsComeFromTheDatabaseNotTheSession(): void
    {
        $phone = $this->fixtures->phone();
        $cart = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '128GB'], 1);

        // The price changes after the item went into the cart.
        $this->db->execute('UPDATE products SET price_cents = 50000 WHERE id = ?', [$phone->id]);
        $order = $this->orders->find($this->checkout->placeOrder($this->customer, $cart, $this->address));

        self::assertNotNull($order);
        self::assertSame(50000, $order->subtotalCents);
    }

    public function testAPriceChangeAfterTheCustomerReviewedTheCartIsStopped(): void
    {
        $phone = $this->fixtures->phone(stock: 3);
        $cart = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '128GB'], 1);
        $reviewed = $this->pricer->price($cart)->fingerprint();

        $this->db->execute('UPDATE products SET price_cents = price_cents + 100 WHERE id = ?', [$phone->id]);

        try {
            $this->checkout->placeOrder($this->customer, $cart, $this->address, $reviewed);
            self::fail('An order must not be placed at a price the customer did not see');
        } catch (CheckoutException $e) {
            self::assertStringContainsString('Your cart changed since you reviewed it', $e->getMessage());
        }
        self::assertSame(3, $this->fixtures->stock($phone->id));
        self::assertSame(0, $this->fixtures->count('orders'));

        $this->checkout->placeOrder($this->customer, $cart, $this->address, $this->pricer->price($cart)->fingerprint());
        self::assertSame(1, $this->fixtures->count('orders'));
    }

    public function testTheLongestOptionLabelsTheAdminFormAllowsCanBeOrdered(): void
    {
        // Regression: MySQL stored the label in VARCHAR(255) while the admin
        // form allows any number of 40-character groups with 60-character
        // values, so a configured PC failed to check out on MySQL only.
        $options = [];
        foreach (['Processor', 'Graphics card', 'Memory', 'Storage'] as $group) {
            $options[str_pad($group, 40, '.')] = [[str_repeat('x', 60), 0]];
        }
        $pc = $this->fixtures->product(['options' => $options]);
        $cart = new Cart(new ArraySession());
        $cart->add($pc->id, array_map(static fn($o): int => $o->id, $pc->options), 1);

        $order = $this->orders->find($this->checkout->placeOrder($this->customer, $cart, $this->address));

        self::assertNotNull($order);
        self::assertGreaterThan(255, strlen($order->items[0]->optionsLabel));
    }

    public function testOrderLinesAreSnapshotsThatSurviveCatalogEdits(): void
    {
        $phone = $this->fixtures->phone();
        $cart = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '256GB'], 1);
        $orderId = $this->checkout->placeOrder($this->customer, $cart, $this->address);

        $this->db->execute("UPDATE products SET name = 'Renamed', price_cents = 1 WHERE id = ?", [$phone->id]);
        $this->db->execute('DELETE FROM product_options WHERE product_id = ?', [$phone->id]);
        $order = $this->orders->find($orderId);

        self::assertNotNull($order);
        self::assertSame('Pixel Test', $order->items[0]->productName);
        self::assertSame('Color: Black · Storage: 256GB', $order->items[0]->optionsLabel);
        self::assertSame(109900, $order->items[0]->unitPriceCents);
    }

    public function testTheLastUnitCanOnlyBeSoldOnce(): void
    {
        $phone = $this->fixtures->phone(stock: 1);
        $first = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '128GB'], 1);
        $second = $this->cartWith($phone, ['Color' => 'White', 'Storage' => '128GB'], 1);

        $this->checkout->placeOrder($this->customer, $first, $this->address);

        try {
            $this->checkout->placeOrder($this->fixtures->user('bob'), $second, $this->address);
            self::fail('The second checkout should have been refused.');
        } catch (CheckoutException $e) {
            self::assertStringContainsString('sold out', $e->getMessage());
        }

        self::assertSame(0, $this->fixtures->stock($phone->id));
        self::assertSame(1, $this->fixtures->count('orders'));
    }

    public function testACartThatChangedIsStoppedForReviewInsteadOfCharged(): void
    {
        $phone = $this->fixtures->phone(stock: 2);
        $cart = $this->cartWith($phone, ['Color' => 'Black', 'Storage' => '128GB'], 5);
        $this->db->execute('UPDATE products SET stock = 2 WHERE id = ?', [$phone->id]);

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('Only 2 Pixel Test left');
        $this->checkout->placeOrder($this->customer, $cart, $this->address);
    }

    public function testATamperedCartLineIsRejected(): void
    {
        $phone = $this->fixtures->phone();
        $other = $this->fixtures->product(['options' => ['Color' => [['Red', 0]]]]);
        $session = new ArraySession();
        $cart = new Cart($session);
        // Hand-crafted session: the phone with another product's option id.
        $cart->add($phone->id, [$other->options[0]->id, $phone->options[2]->id], 1);

        $this->expectException(CheckoutException::class);
        $this->checkout->placeOrder($this->customer, $cart, $this->address);
    }

    public function testAnEmptyCartCannotBeCheckedOut(): void
    {
        $this->expectExceptionObject(new CheckoutException('Your cart is empty.'));
        $this->checkout->placeOrder($this->customer, new Cart(new ArraySession()), $this->address);
    }

    public function testAFailureMidwayRollsBackTheStockAlreadyTaken(): void
    {
        $first = $this->fixtures->product(['name' => 'First', 'stock' => 5]);
        $second = $this->fixtures->product(['name' => 'Second', 'stock' => 5]);
        $cart = new Cart(new ArraySession());
        $cart->add($first->id, [], 1);
        $cart->add($second->id, [], 1);

        // Make the order_items insert for the second product fail, after
        // both stock decrements have already run inside the transaction.
        $this->failInsertsOfOrderItemsFor($second->id);

        $thrown = null;
        try {
            $this->checkout->placeOrder($this->customer, $cart, $this->address);
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(\PDOException::class, $thrown);

        self::assertSame(5, $this->fixtures->stock($first->id));
        self::assertSame(5, $this->fixtures->stock($second->id));
        self::assertSame(0, $this->fixtures->count('orders'));
        self::assertSame(0, $this->fixtures->count('order_items'));
        self::assertFalse($cart->isEmpty(), 'The cart survives a failed checkout');
    }

    /** @param array<string, string> $choices group => value */
    private function cartWith(Product $product, array $choices, int $quantity): Cart
    {
        $ids = [];
        foreach ($product->options as $option) {
            if (($choices[$option->group] ?? null) === $option->value) {
                $ids[] = $option->id;
            }
        }

        $cart = new Cart(new ArraySession());
        $cart->add($product->id, $ids, $quantity);

        return $cart;
    }

    private function failInsertsOfOrderItemsFor(int $productId): void
    {
        if (TestDatabase::driver() === 'mysql') {
            $this->db->pdo()->exec('DROP TRIGGER IF EXISTS fail_order_items');
            try {
                $this->db->pdo()->exec(
                    "CREATE TRIGGER fail_order_items BEFORE INSERT ON order_items FOR EACH ROW
                     BEGIN IF NEW.product_id = {$productId} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'injected failure'; END IF; END",
                );
            } catch (\PDOException $e) {
                // 1419: binary logging is on and the user lacks SUPER; 1142: no
                // TRIGGER privilege. CI runs as root, where neither applies.
                if (in_array($e->errorInfo[1] ?? null, [1142, 1419], true)) {
                    self::markTestSkipped('The MySQL test user cannot create triggers: ' . $e->getMessage());
                }
                throw $e;
            }

            return;
        }

        $this->db->pdo()->exec(
            "CREATE TRIGGER fail_order_items BEFORE INSERT ON order_items
             WHEN NEW.product_id = {$productId} BEGIN SELECT RAISE(ABORT, 'injected failure'); END",
        );
    }

    protected function tearDown(): void
    {
        $this->db->pdo()->exec('DROP TRIGGER IF EXISTS fail_order_items');
        parent::tearDown();
    }
}
