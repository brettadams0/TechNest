<?php

declare(strict_types=1);

namespace TechNest\Checkout;

final class ShippingAddress
{
    public const PROVINCES = [
        'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
        'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
        'SK' => 'Saskatchewan', 'YT' => 'Yukon',
    ];

    /** Canadian postal codes: letter-digit-letter digit-letter-digit, no D, F, I, O, Q or U. */
    private const POSTAL_CODE = '/^([ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z])\s?(\d[ABCEGHJ-NPRSTV-Z]\d)$/i';

    public function __construct(
        public readonly string $name,
        public readonly string $street,
        public readonly string $city,
        public readonly string $province,
        public readonly string $postalCode,
    ) {}

    /** "n9b3p4" → "N9B 3P4", or null if it is not a valid postal code. */
    public static function normalisePostalCode(string $input): ?string
    {
        if (preg_match(self::POSTAL_CODE, trim($input), $m) !== 1) {
            return null;
        }

        return strtoupper($m[1] . ' ' . $m[2]);
    }

    public function oneLine(): string
    {
        return "{$this->street}, {$this->city}, {$this->province} {$this->postalCode}";
    }
}
