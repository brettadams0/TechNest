<?php

declare(strict_types=1);

namespace TechNest\Cart;

use TechNest\Security\Session;

/**
 * The cart lives in the session and holds only identifiers and quantities —
 * never prices. Prices are read from the database every time the cart is
 * shown and again inside the checkout transaction, so nothing a client can
 * tamper with ever reaches an order total.
 */
final class Cart
{
    public const MAX_QUANTITY = 10;
    private const KEY = 'cart';

    public function __construct(private readonly Session $session) {}

    /** @param list<int> $optionIds */
    public static function lineKey(int $productId, array $optionIds): string
    {
        sort($optionIds);

        return $productId . ':' . implode(',', $optionIds);
    }

    /**
     * Add a configured product. The same product with the same options
     * merges into one line; different options make a separate line.
     *
     * @param list<int> $optionIds
     */
    public function add(int $productId, array $optionIds, int $quantity): string
    {
        $key = self::lineKey($productId, $optionIds);
        $lines = $this->raw();
        $existing = $lines[$key]['quantity'] ?? 0;

        sort($optionIds);
        $lines[$key] = [
            'product_id' => $productId,
            'option_ids' => $optionIds,
            'quantity' => self::clamp($existing + $quantity),
        ];
        $this->session->set(self::KEY, $lines);

        return $key;
    }

    public function update(string $key, int $quantity): void
    {
        $lines = $this->raw();
        if (!isset($lines[$key])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($key);

            return;
        }

        $lines[$key]['quantity'] = self::clamp($quantity);
        $this->session->set(self::KEY, $lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->raw();
        unset($lines[$key]);
        $this->session->set(self::KEY, $lines);
    }

    public function clear(): void
    {
        $this->session->remove(self::KEY);
    }

    /** @return list<CartLine> */
    public function lines(): array
    {
        $lines = [];
        foreach ($this->raw() as $key => $line) {
            $lines[] = new CartLine($key, $line['product_id'], $line['option_ids'], $line['quantity']);
        }

        return $lines;
    }

    /** Total number of items, for the header badge. */
    public function count(): int
    {
        return array_sum(array_column($this->raw(), 'quantity'));
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    private static function clamp(int $quantity): int
    {
        return max(1, min(self::MAX_QUANTITY, $quantity));
    }

    /**
     * The session contents, defensively re-validated: anything malformed is
     * dropped rather than trusted.
     *
     * @return array<string, array{product_id: int, option_ids: list<int>, quantity: int}>
     */
    private function raw(): array
    {
        $stored = $this->session->get(self::KEY, []);
        if (!is_array($stored)) {
            return [];
        }

        $lines = [];
        foreach ($stored as $key => $line) {
            if (
                !is_string($key)
                || !is_array($line)
                || !is_int($line['product_id'] ?? null)
                || !is_int($line['quantity'] ?? null)
                || !is_array($line['option_ids'] ?? null)
            ) {
                continue;
            }

            $optionIds = array_values(array_filter($line['option_ids'], 'is_int'));
            $lines[$key] = [
                'product_id' => $line['product_id'],
                'option_ids' => $optionIds,
                'quantity' => self::clamp($line['quantity']),
            ];
        }

        return $lines;
    }
}
