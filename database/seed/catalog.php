<?php

declare(strict_types=1);

/*
 * The demo catalog: the original store's 20 products and their option sets,
 * now with categories, stock levels and local artwork. Prices are in cents.
 *
 * Options are [group => [[value, extra cents], ...]]; the first value in each
 * group is the default selection.
 */

return [
    'themes' => [
        ['regular', 'Regular', true],
        ['minimal', 'Minimal', false],
        ['holiday', 'Holiday', false],
    ],

    'categories' => [
        ['phones', 'Phones'],
        ['laptops', 'Laptops'],
        ['tablets', 'Tablets & E-readers'],
        ['audio', 'Audio'],
        ['wearables', 'Wearables'],
        ['gaming', 'Gaming'],
        ['cameras', 'Cameras'],
        ['accessories', 'Accessories'],
        ['smart-home', 'Smart Home & Networking'],
    ],

    'products' => [
        [
            'slug' => 'iphone-15-pro', 'name' => 'iPhone 15 Pro', 'category' => 'phones',
            'price' => 119999, 'stock' => 24, 'featured' => true,
            'description' => 'A titanium-frame flagship with the A17 Pro chip, a 6.1-inch ProMotion display and a 48 MP main camera. USB-C finally replaces Lightning, and the new Action button can be set to launch the camera, a shortcut or silent mode.',
            'options' => ['Color' => [['Silver', 0], ['Space Black', 0]], 'Storage' => [['128GB', 0], ['256GB', 10000]]],
        ],
        [
            'slug' => 'samsung-galaxy-s23', 'name' => 'Samsung Galaxy S23', 'category' => 'phones',
            'price' => 109999, 'stock' => 18, 'featured' => false,
            'description' => 'A compact Android flagship built around a 6.1-inch Dynamic AMOLED 2X display, Snapdragon 8 Gen 2 for Galaxy and a 50 MP triple camera that holds up well in low light. Four years of OS updates are included.',
            'options' => ['Color' => [['Phantom Black', 0], ['Cream', 0]], 'Storage' => [['128GB', 0], ['256GB', 10000]]],
        ],
        [
            'slug' => 'macbook-air-m2', 'name' => 'MacBook Air M2', 'category' => 'laptops',
            'price' => 129999, 'stock' => 12, 'featured' => true,
            'description' => 'Apple silicon in a fanless 11.3 mm chassis: the M2 chip, a 13.6-inch Liquid Retina display, MagSafe charging and up to 18 hours of battery. Silent under any load, which is the point.',
            'options' => ['Color' => [['Starlight', 0], ['Midnight', 0]], 'Storage' => [['256GB', 0], ['512GB', 20000]]],
        ],
        [
            'slug' => 'dell-xps-13', 'name' => 'Dell XPS 13', 'category' => 'laptops',
            'price' => 99999, 'stock' => 9, 'featured' => false,
            'description' => 'A premium Windows ultrabook with a near-borderless 13.4-inch InfinityEdge display, Intel Core processors and a machined aluminium body that weighs under 1.2 kg.',
            'options' => ['Color' => [['Platinum', 0], ['Frost', 0]], 'Storage' => [['256GB', 0], ['512GB', 20000]]],
        ],
        [
            'slug' => 'ipad-pro', 'name' => 'iPad Pro', 'category' => 'tablets',
            'price' => 89999, 'stock' => 15, 'featured' => true,
            'description' => 'The fastest iPad, with an M-series chip, a ProMotion display and Thunderbolt over USB-C. Pairs with Apple Pencil and Magic Keyboard when it needs to stand in for a laptop.',
            'options' => ['Color' => [['Space Gray', 0], ['Silver', 0]], 'Storage' => [['128GB', 0], ['256GB', 10000]]],
        ],
        [
            'slug' => 'kindle-paperwhite', 'name' => 'Kindle Paperwhite', 'category' => 'tablets',
            'price' => 14999, 'stock' => 40, 'featured' => false,
            'description' => 'A 6.8-inch glare-free 300 ppi display with an adjustable warm light, IPX8 waterproofing and weeks of battery from a single USB-C charge.',
            'options' => ['Color' => [['Black', 0], ['White', 0]], 'Storage' => [['8GB', 0], ['16GB', 3000]]],
        ],
        [
            'slug' => 'sony-wh-1000xm5', 'name' => 'Sony WH-1000XM5', 'category' => 'audio',
            'price' => 39999, 'stock' => 4, 'featured' => true,
            'description' => 'Industry-leading noise cancelling from eight microphones and two processors, 30 hours of battery, and a lighter, quieter design than the XM4. Multipoint Bluetooth keeps your laptop and phone connected at once.',
            'options' => ['Color' => [['Black', 0], ['Silver', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 4999]]],
        ],
        [
            'slug' => 'bose-soundlink-flex', 'name' => 'Bose SoundLink Flex', 'category' => 'audio',
            'price' => 14999, 'stock' => 30, 'featured' => true,
            'description' => 'A rugged, IP67 waterproof Bluetooth speaker that floats, with PositionIQ tuning the sound to however it is placed and 12 hours of playback per charge.',
            'options' => ['Color' => [['Black', 0], ['White Smoke', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 1999]]],
        ],
        [
            'slug' => 'apple-watch-series-9', 'name' => 'Apple Watch Series 9', 'category' => 'wearables',
            'price' => 49999, 'stock' => 16, 'featured' => true,
            'description' => 'A brighter always-on display, the S9 chip and the double-tap gesture for controlling the watch one-handed. Tracks heart rate, sleep, ECG and workouts.',
            'options' => ['Color' => [['Midnight', 0], ['Starlight', 0]], 'Size' => [['41mm', 0], ['45mm', 3000]]],
        ],
        [
            'slug' => 'fitbit-charge-6', 'name' => 'Fitbit Charge 6', 'category' => 'wearables',
            'price' => 12999, 'stock' => 3, 'featured' => false,
            'description' => 'A slim fitness tracker with built-in GPS, continuous heart-rate monitoring, sleep stages and seven days of battery. Works with Google Maps and Wallet on the wrist.',
            'options' => ['Color' => [['Black', 0], ['Rose Gold', 0]], 'Band' => [['Small', 0], ['Large', 0]]],
        ],
        [
            'slug' => 'nintendo-switch-oled', 'name' => 'Nintendo Switch OLED', 'category' => 'gaming',
            'price' => 34999, 'stock' => 21, 'featured' => true,
            'description' => 'The hybrid console with a vivid 7-inch OLED screen, a wide adjustable stand, 64 GB of storage and a dock with a wired LAN port. Plays docked on the TV or handheld anywhere.',
            'options' => ['Color' => [['White', 0], ['Neon Red/Blue', 0]], 'Bundle' => [['Standard', 0], ['Mario Kart', 5000]]],
        ],
        [
            'slug' => 'xbox-series-x', 'name' => 'Xbox Series X', 'category' => 'gaming',
            'price' => 49999, 'stock' => 0, 'featured' => false,
            'description' => 'True 4K gaming at up to 120 frames per second, a 1 TB custom NVMe SSD and Quick Resume for jumping between several games at once.',
            'options' => ['Bundle' => [['Standard', 0], ['Game Pass', 3000]], 'Warranty' => [['1 Year', 0], ['2 Years', 4999]]],
        ],
        [
            'slug' => 'razer-blackwidow-v4', 'name' => 'Razer BlackWidow V4', 'category' => 'gaming',
            'price' => 17999, 'stock' => 11, 'featured' => false,
            'description' => 'A full-size mechanical gaming keyboard with Razer switches, per-key Chroma RGB, a magnetic wrist rest and a multi-function roller for media control.',
            'options' => ['Switch' => [['Green', 0], ['Yellow', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 2999]]],
        ],
        [
            'slug' => 'gopro-hero-11', 'name' => 'GoPro HERO11', 'category' => 'cameras',
            'price' => 49999, 'stock' => 8, 'featured' => false,
            'description' => 'A 5.3K action camera with a taller 8:7 sensor, HyperSmooth 5.0 stabilisation and waterproofing to 10 m without a housing.',
            'options' => ['Bundle' => [['Standard', 0], ['Adventure', 5000]], 'Warranty' => [['1 Year', 0], ['2 Years', 3999]]],
        ],
        [
            'slug' => 'canon-eos-r10', 'name' => 'Canon EOS R10', 'category' => 'cameras',
            'price' => 109999, 'stock' => 6, 'featured' => true,
            'description' => 'A compact APS-C mirrorless camera with a 24.2 MP sensor, 15 fps mechanical shooting and Dual Pixel autofocus that tracks people, animals and vehicles.',
            'options' => ['Bundle' => [['Body Only', 0], ['w/ 18-45mm Lens', 20000]], 'Warranty' => [['1 Year', 0], ['2 Years', 9999]]],
        ],
        [
            'slug' => 'logitech-mx-master-3s', 'name' => 'Logitech MX Master 3S', 'category' => 'accessories',
            'price' => 9999, 'stock' => 35, 'featured' => false,
            'description' => 'An ergonomic wireless mouse with quiet clicks, an 8K DPI sensor that tracks on glass and a MagSpeed scroll wheel that spins through 1,000 lines a second.',
            'options' => ['Color' => [['Graphite', 0], ['Pale Gray', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 1999]]],
        ],
        [
            'slug' => 'anker-powercore-20000', 'name' => 'Anker PowerCore 20000', 'category' => 'accessories',
            'price' => 5999, 'stock' => 50, 'featured' => false,
            'description' => 'A 20,000 mAh power bank that charges a phone four to five times over, with PowerIQ fast charging on two ports.',
            'options' => ['Color' => [['Black', 0], ['White', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 999]]],
        ],
        [
            'slug' => 'wd-my-passport-2tb', 'name' => 'WD My Passport 2TB', 'category' => 'accessories',
            'price' => 8999, 'stock' => 27, 'featured' => false,
            'description' => 'Pocket-sized external storage with 256-bit AES hardware encryption, password protection and automatic backup software.',
            'options' => ['Color' => [['Blue', 0], ['Red', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 1499]]],
        ],
        [
            'slug' => 'google-nest-hub', 'name' => 'Google Nest Hub', 'category' => 'smart-home',
            'price' => 9999, 'stock' => 14, 'featured' => false,
            'description' => 'A 7-inch smart display for the kitchen or bedside: Google Assistant, smart-home controls, photo frame and Sleep Sensing without a camera.',
            'options' => ['Color' => [['Chalk', 0], ['Charcoal', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 1999]]],
        ],
        [
            'slug' => 'tp-link-archer-ax50', 'name' => 'TP-Link Archer AX50', 'category' => 'smart-home',
            'price' => 12999, 'stock' => 2, 'featured' => false,
            'description' => 'A dual-band Wi-Fi 6 router delivering up to 3 Gbps, with OFDMA and MU-MIMO to keep many devices fast at once, and HomeShield security built in.',
            'options' => ['Color' => [['Black', 0]], 'Warranty' => [['1 Year', 0], ['2 Years', 1499]]],
        ],
    ],
];
