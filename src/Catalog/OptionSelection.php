<?php

declare(strict_types=1);

namespace TechNest\Catalog;

/**
 * Turns the option ids a customer submitted into a validated configuration.
 *
 * The original version of this store trusted the posted ids completely: you
 * could omit the "256GB" choice and pay the 128GB price, or submit an option
 * id that belonged to a different product. Here every option group must have
 * exactly one choice, and every choice must belong to this product and that
 * group.
 */
final class OptionSelection
{
    /**
     * @param array<string, int> $submitted group name => option id
     *
     * @return list<ProductOption> one per group, in the product's group order
     *
     * @throws InvalidSelection
     */
    public static function resolve(Product $product, array $submitted): array
    {
        $chosen = [];
        foreach ($product->optionGroups() as $group => $options) {
            $id = $submitted[$group] ?? null;
            if ($id === null) {
                throw new InvalidSelection("Choose a {$group} for {$product->name}.");
            }

            $match = null;
            foreach ($options as $option) {
                if ($option->id === $id) {
                    $match = $option;
                    break;
                }
            }
            if ($match === null) {
                throw new InvalidSelection("That {$group} is not available for {$product->name}.");
            }

            $chosen[] = $match;
        }

        return $chosen;
    }

    /**
     * Same check for a cart line, which stores a flat list of option ids.
     *
     * @param list<int> $optionIds
     *
     * @return list<ProductOption>
     *
     * @throws InvalidSelection
     */
    public static function resolveIds(Product $product, array $optionIds): array
    {
        $byGroup = [];
        foreach ($product->options as $option) {
            if (in_array($option->id, $optionIds, true)) {
                $byGroup[$option->group] = $option->id;
            }
        }

        if (count($byGroup) !== count($optionIds)) {
            throw new InvalidSelection("The options chosen for {$product->name} are no longer available.");
        }

        return self::resolve($product, $byGroup);
    }

    /** @param list<ProductOption> $options */
    public static function label(array $options): string
    {
        return implode(' · ', array_map(static fn(ProductOption $o): string => $o->label(), $options));
    }

    /** @param list<ProductOption> $options */
    public static function extraCents(array $options): int
    {
        return array_sum(array_map(static fn(ProductOption $o): int => $o->extraCents, $options));
    }
}
