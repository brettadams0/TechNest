<?php

declare(strict_types=1);

namespace TechNest\Database;

use DateTimeImmutable;
use TechNest\Cart\PricedLine;
use TechNest\Catalog\Product;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\PriceCalculator;
use TechNest\Checkout\ShippingAddress;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderStatus;
use TechNest\Support\Clock;
use TechNest\User\UserRepository;

/**
 * Loads the catalog, and — outside production only — demo accounts and ten
 * weeks of plausible order history so the admin dashboard has something to
 * show. The order history is generated from a fixed random seed relative to
 * "now", so every fresh install looks the same and is always current.
 */
final class Seeder
{
    public const DEMO_ADMIN = ['admin', 'admin@example.com', 'technest-admin'];
    public const DEMO_CUSTOMER = ['demo', 'demo@example.com', 'technest-demo'];

    private const CUSTOMERS = [
        ['alex.chen', 'Alex Chen', 'Toronto', 'ON', 'M5V 2T6', '88 Queens Quay W'],
        ['priya.s', 'Priya Sharma', 'Ottawa', 'ON', 'K1P 5G3', '150 Elgin St'],
        ['marcus_w', 'Marcus Webb', 'Vancouver', 'BC', 'V6B 1A1', '401 W Georgia St'],
        ['sofia.r', 'Sofia Rossi', 'Montreal', 'QC', 'H3B 2Y5', '1000 Rue de la Gauchetière'],
        ['jordan.k', 'Jordan Kim', 'Calgary', 'AB', 'T2P 1J9', '225 6 Ave SW'],
        ['lena.m', 'Lena Müller', 'Halifax', 'NS', 'B3J 3K5', '1800 Argyle St'],
        ['omar.h', 'Omar Haddad', 'Windsor', 'ON', 'N9A 1B2', '350 City Hall Sq W'],
        ['grace.t', 'Grace Thompson', 'Winnipeg', 'MB', 'R3C 4A5', '201 Portage Ave'],
    ];

    public function __construct(
        private readonly Database $db,
        private readonly Clock $clock,
        private readonly string $catalogFile,
    ) {}

    public function hasCatalog(): bool
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM products') > 0;
    }

    public function seedCatalog(): int
    {
        /** @var array{themes: list<array{0: string, 1: string, 2: bool}>, categories: list<array{0: string, 1: string}>, products: list<array{slug: string, name: string, category: string, price: int, stock: int, featured: bool, description: string, options: array<string, list<array{0: string, 1: int}>>}>} $catalog */
        $catalog = require $this->catalogFile;
        $now = $this->now();

        return $this->db->transaction(function (Database $db) use ($catalog, $now): int {
            foreach ($catalog['themes'] as [$slug, $name, $active]) {
                $db->execute('INSERT INTO themes (slug, name, is_active) VALUES (?, ?, ?)', [$slug, $name, $active ? 1 : 0]);
            }

            $categoryIds = [];
            foreach ($catalog['categories'] as $i => [$slug, $name]) {
                $categoryIds[$slug] = $db->insert(
                    'INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, ?)',
                    [$slug, $name, $i],
                );
            }

            foreach ($catalog['products'] as $i => $p) {
                // Stagger creation dates so "Newest" sorting is meaningful.
                $created = $this->clock->now()->modify('-' . (count($catalog['products']) - $i) . ' days')->format('Y-m-d H:i:s');
                $productId = $db->insert(
                    'INSERT INTO products (category_id, slug, name, description, image, price_cents, stock, is_featured, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
                    [
                        $categoryIds[$p['category']], $p['slug'], $p['name'], $p['description'], $p['slug'] . '.svg',
                        $p['price'], $p['stock'], $p['featured'] ? 1 : 0, $created, $now,
                    ],
                );

                $sort = 0;
                foreach ($p['options'] as $group => $values) {
                    foreach ($values as [$value, $extra]) {
                        $db->execute(
                            'INSERT INTO product_options (product_id, group_name, value, extra_cents, sort_order) VALUES (?, ?, ?, ?, ?)',
                            [$productId, $group, $value, $extra, $sort++],
                        );
                    }
                }
            }

            return count($catalog['products']);
        });
    }

    /**
     * Demo accounts with published passwords, plus order history. Never call
     * this for a production database; the CLI refuses to.
     *
     * @return int number of orders created
     */
    public function seedDemo(UserRepository $users, ProductRepository $products, OrderRepository $orders, PriceCalculator $calculator): int
    {
        mt_srand(20_260_815);

        [$adminName, $adminEmail, $adminPassword] = self::DEMO_ADMIN;
        $admin = $users->create($adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT));
        $users->grantAdmin($admin->id, null);

        [$demoName, $demoEmail, $demoPassword] = self::DEMO_CUSTOMER;
        $demo = $users->create($demoName, $demoEmail, password_hash($demoPassword, PASSWORD_DEFAULT));

        // Customers who only exist to have placed orders: unusable passwords.
        $customers = [];
        foreach (self::CUSTOMERS as [$username, $name, $city, $province, $postal, $street]) {
            $user = $users->create($username, $username . '@example.com', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
            $customers[] = [$user->id, new ShippingAddress($name, $street, $city, $province, $postal)];
        }

        $catalog = array_values(array_filter(
            $products->findMany(range(1, $this->db->fetchInt('SELECT MAX(id) FROM products'))),
            static fn(Product $p): bool => $p->isActive,
        ));

        $count = 0;
        $today = $this->clock->now()->setTime(0, 0);
        // 75 days, so the dashboard's "previous 30 days" comparison has a
        // full window of data behind it.
        for ($daysAgo = 74; $daysAgo >= 0; --$daysAgo) {
            $day = $today->modify("-{$daysAgo} days");
            $weekend = (int) $day->format('N') >= 6;
            // A gentle upward trend, busier weekends, and some quiet days.
            $orderCount = max(0, mt_rand(0, 3) + ($weekend ? 2 : 0) + intdiv(74 - $daysAgo, 30) - 1);

            for ($n = 0; $n < $orderCount; ++$n) {
                [$userId, $address] = $customers[mt_rand(0, count($customers) - 1)];
                $placed = $day->setTime(mt_rand(13, 23), mt_rand(0, 59), mt_rand(0, 59));
                if ($placed > $this->clock->now()) {
                    $placed = $this->clock->now()->modify('-' . mt_rand(5, 300) . ' minutes');
                }
                $this->createOrder($orders, $calculator, $userId, $address, $this->randomLines($catalog), $placed, $this->statusForAge($daysAgo));
                ++$count;
            }
        }

        // The demo customer gets a small, readable history: one delivered,
        // one on its way, and one still pending (so "Cancel order" shows).
        $demoAddress = new ShippingAddress('Demo Customer', '401 Sunset Ave', 'Windsor', 'ON', 'N9B 3P4');
        $bySlug = [];
        foreach ($catalog as $product) {
            $bySlug[$product->slug] = $product;
        }
        $history = [
            [[['logitech-mx-master-3s', 1], ['anker-powercore-20000', 1]], 18, OrderStatus::Delivered],
            [[['sony-wh-1000xm5', 1]], 3, OrderStatus::Shipped],
            [[['nintendo-switch-oled', 1], ['bose-soundlink-flex', 2]], 0, OrderStatus::Pending],
        ];
        foreach ($history as [$items, $daysAgo, $status]) {
            $lines = [];
            foreach ($items as [$slug, $qty]) {
                $product = $bySlug[$slug];
                $lines[] = new PricedLine('demo', $product, $this->defaultOptions($product), $qty);
            }
            $placed = $this->clock->now()->modify("-{$daysAgo} days -2 hours");
            $this->createOrder($orders, $calculator, $demo->id, $demoAddress, $lines, $placed, $status);
            ++$count;
        }

        return $count;
    }

    /**
     * @param list<Product> $catalog
     *
     * @return list<PricedLine>
     */
    private function randomLines(array $catalog): array
    {
        $lines = [];
        $used = [];
        $lineCount = mt_rand(1, 100) <= 70 ? 1 : mt_rand(2, 3);

        while (count($lines) < $lineCount) {
            // Cheaper products sell more often: pick twice, keep the cheaper.
            $a = $catalog[mt_rand(0, count($catalog) - 1)];
            $b = $catalog[mt_rand(0, count($catalog) - 1)];
            $product = $a->priceCents <= $b->priceCents ? $a : $b;
            if (isset($used[$product->id])) {
                continue;
            }
            $used[$product->id] = true;

            $options = [];
            foreach ($product->optionGroups() as $choices) {
                $options[] = $choices[mt_rand(0, count($choices) - 1)];
            }
            $lines[] = new PricedLine('seed', $product, $options, mt_rand(1, 100) <= 85 ? 1 : 2);
        }

        return $lines;
    }

    /** @return list<\TechNest\Catalog\ProductOption> */
    private function defaultOptions(Product $product): array
    {
        return array_map(static fn(array $choices) => $choices[0], array_values($product->optionGroups()));
    }

    private function statusForAge(int $daysAgo): OrderStatus
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 6 => OrderStatus::Cancelled,
            $daysAgo >= 8 => OrderStatus::Delivered,
            $daysAgo >= 4 => $roll <= 50 ? OrderStatus::Delivered : OrderStatus::Shipped,
            $daysAgo >= 1 => $roll <= 60 ? OrderStatus::Shipped : OrderStatus::Processing,
            default => $roll <= 50 ? OrderStatus::Processing : OrderStatus::Pending,
        };
    }

    /**
     * Insert an order and walk it through the lifecycle to $final, writing a
     * history row for each step as the real application would.
     *
     * @param list<PricedLine> $lines
     */
    private function createOrder(OrderRepository $orders, PriceCalculator $calculator, int $userId, ShippingAddress $address, array $lines, DateTimeImmutable $placed, OrderStatus $final): void
    {
        $subtotal = array_sum(array_map(static fn(PricedLine $l): int => $l->totalCents(), $lines));
        $at = $placed->format('Y-m-d H:i:s');
        $orderId = $orders->create($userId, $calculator->totals($subtotal), $address, $lines, $at);
        $orders->recordStatusChange($orderId, null, OrderStatus::Pending, $userId, $at);

        $path = match ($final) {
            OrderStatus::Pending => [],
            OrderStatus::Processing => [OrderStatus::Processing],
            OrderStatus::Shipped => [OrderStatus::Processing, OrderStatus::Shipped],
            OrderStatus::Delivered => [OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered],
            OrderStatus::Cancelled => [OrderStatus::Cancelled],
        };

        $current = OrderStatus::Pending;
        $time = $placed;
        foreach ($path as $step) {
            $time = $time->modify('+' . mt_rand(3, 30) . ' hours');
            if ($time > $this->clock->now()) {
                $time = $this->clock->now();
            }
            $stamp = $time->format('Y-m-d H:i:s');
            $orders->compareAndSetStatus($orderId, $current, $step, $stamp);
            $orders->recordStatusChange($orderId, $current, $step, null, $stamp);
            $current = $step;
        }
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
