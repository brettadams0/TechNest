<?php

declare(strict_types=1);

/*
 * Generates the product illustrations in public/assets/img/products/.
 *
 *   php tools/product-art.php
 *
 * The original catalog hotlinked three Unsplash photos across all twenty
 * products, so most items showed a picture of something else, and the whole
 * catalog broke offline. These are flat SVG illustrations instead: one
 * drawing per device type, tinted per category, a few KB each, and served
 * from the store itself. Re-run after editing; the output is committed.
 */

$out = dirname(__DIR__) . '/public/assets/img/products';
if (!is_dir($out)) {
    mkdir($out, 0775, true);
}

/** Category palettes: background tint (light, deeper) and an accent. */
$palettes = [
    'phones' => ['#eef2ff', '#c7d2fe', '#6366f1'],
    'laptops' => ['#f0f9ff', '#bae6fd', '#0284c7'],
    'tablets' => ['#f5f3ff', '#ddd6fe', '#7c3aed'],
    'audio' => ['#fff1f2', '#fecdd3', '#e11d48'],
    'wearables' => ['#ecfdf5', '#a7f3d0', '#059669'],
    'gaming' => ['#fff7ed', '#fed7aa', '#ea580c'],
    'cameras' => ['#fffbeb', '#fde68a', '#d97706'],
    'accessories' => ['#f0fdfa', '#99f6e4', '#0d9488'],
    'smart-home' => ['#ecfeff', '#a5f3fc', '#0891b2'],
    'other' => ['#f8fafc', '#e2e8f0', '#64748b'],
];

function frame(array $palette, string $title, string $body, string $screenA = '#818cf8', string $screenB = '#f472b6'): string
{
    [$light, $deep, $accent] = $palette;
    $title = htmlspecialchars($title, ENT_XML1);

    return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" role="img" aria-label="{$title}">
          <defs>
            <radialGradient id="bg" cx="50%" cy="35%" r="80%">
              <stop offset="0" stop-color="{$light}"/>
              <stop offset="1" stop-color="{$deep}"/>
            </radialGradient>
            <linearGradient id="screen" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$screenA}"/>
              <stop offset="1" stop-color="{$screenB}"/>
            </linearGradient>
            <linearGradient id="sheen" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#fff" stop-opacity=".45"/>
              <stop offset=".45" stop-color="#fff" stop-opacity="0"/>
            </linearGradient>
            <linearGradient id="metal" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#f8fafc"/>
              <stop offset="1" stop-color="#cbd5e1"/>
            </linearGradient>
            <filter id="blur" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="12"/></filter>
          </defs>
          <rect width="600" height="600" fill="url(#bg)"/>
          <circle cx="470" cy="120" r="90" fill="{$accent}" opacity=".08"/>
          <circle cx="110" cy="470" r="70" fill="{$accent}" opacity=".07"/>
          <ellipse cx="300" cy="505" rx="190" ry="20" fill="#0f172a" opacity=".22" filter="url(#blur)"/>
        {$body}
        </svg>
        SVG;
}

/* ---- device drawings, each centred on a 600×600 canvas ---------------- */

function phone(string $bodyColor, bool $pill = true): string
{
    $notch = $pill
        ? '<rect x="266" y="138" width="68" height="18" rx="9" fill="#0b0f19"/>'
        : '<circle cx="300" cy="146" r="6" fill="#0b0f19"/>';

    return <<<SVG
          <rect x="208" y="112" width="184" height="372" rx="38" fill="{$bodyColor}"/>
          <rect x="216" y="120" width="168" height="356" rx="31" fill="#0b0f19"/>
          <rect x="222" y="126" width="156" height="344" rx="26" fill="url(#screen)"/>
          {$notch}
          <rect x="242" y="196" width="116" height="10" rx="5" fill="#fff" opacity=".7"/>
          <rect x="242" y="214" width="76" height="8" rx="4" fill="#fff" opacity=".45"/>
          <g fill="#fff" opacity=".85">
            <rect x="242" y="384" width="30" height="30" rx="9"/><rect x="285" y="384" width="30" height="30" rx="9"/><rect x="328" y="384" width="30" height="30" rx="9"/>
            <rect x="242" y="342" width="30" height="30" rx="9" opacity=".7"/><rect x="285" y="342" width="30" height="30" rx="9" opacity=".7"/><rect x="328" y="342" width="30" height="30" rx="9" opacity=".7"/>
          </g>
          <rect x="268" y="452" width="64" height="5" rx="2.5" fill="#fff" opacity=".8"/>
          <path d="M222 152 Q222 126 248 126 H330 L222 300 Z" fill="url(#sheen)"/>
        SVG;
}

function laptop(string $lidColor, string $deckColor): string
{
    return <<<SVG
          <rect x="138" y="150" width="324" height="214" rx="14" fill="{$lidColor}"/>
          <rect x="148" y="160" width="304" height="194" rx="6" fill="#0b0f19"/>
          <rect x="154" y="166" width="292" height="182" rx="3" fill="url(#screen)"/>
          <rect x="174" y="190" width="120" height="12" rx="6" fill="#fff" opacity=".75"/>
          <rect x="174" y="210" width="80" height="9" rx="4.5" fill="#fff" opacity=".5"/>
          <rect x="174" y="250" width="252" height="76" rx="10" fill="#fff" opacity=".2"/>
          <path d="M154 166 H300 L154 300 Z" fill="url(#sheen)" opacity=".7"/>
          <path d="M96 368 H504 L486 392 Q482 398 474 398 H126 Q118 398 114 392 Z" fill="{$deckColor}"/>
          <rect x="96" y="364" width="408" height="8" rx="4" fill="{$lidColor}"/>
          <rect x="268" y="368" width="64" height="6" rx="3" fill="#0f172a" opacity=".2"/>
        SVG;
}

function tablet(string $bodyColor): string
{
    return <<<SVG
          <rect x="118" y="168" width="364" height="268" rx="26" fill="{$bodyColor}"/>
          <rect x="128" y="178" width="344" height="248" rx="17" fill="#0b0f19"/>
          <rect x="136" y="186" width="328" height="232" rx="11" fill="url(#screen)"/>
          <g fill="#fff">
            <rect x="160" y="212" width="130" height="80" rx="12" opacity=".35"/>
            <rect x="304" y="212" width="136" height="38" rx="10" opacity=".55"/>
            <rect x="304" y="256" width="136" height="36" rx="10" opacity=".3"/>
            <rect x="160" y="306" width="280" height="86" rx="12" opacity=".22"/>
          </g>
          <path d="M136 197 Q136 186 147 186 H310 L136 360 Z" fill="url(#sheen)"/>
          <rect x="246" y="150" width="108" height="10" rx="5" fill="#475569"/>
        SVG;
}

function ereader(string $bodyColor): string
{
    $lines = '';
    for ($i = 0; $i < 11; ++$i) {
        $w = [136, 140, 128, 138, 90, 140, 132, 126, 140, 110, 70][$i];
        $lines .= sprintf('<rect x="232" y="%d" width="%d" height="6" rx="3" fill="#334155" opacity=".55"/>', 176 + $i * 20, $w);
    }

    return <<<SVG
          <rect x="202" y="118" width="196" height="372" rx="22" fill="{$bodyColor}"/>
          <rect x="218" y="140" width="164" height="308" rx="6" fill="#f4f1ea"/>
          {$lines}
          <rect x="232" y="410" width="60" height="6" rx="3" fill="#94a3b8"/>
          <text x="300" y="474" text-anchor="middle" font-family="Georgia, serif" font-size="13" fill="#94a3b8">kindle</text>
        SVG;
}

function headphones(string $color, string $cushion): string
{
    return <<<SVG
          <path d="M178 320 V266 Q178 150 300 150 Q422 150 422 266 V320" fill="none" stroke="{$color}" stroke-width="22" stroke-linecap="round"/>
          <path d="M196 300 V268 Q196 172 300 172 Q404 172 404 268 V300" fill="none" stroke="#fff" stroke-opacity=".18" stroke-width="6" stroke-linecap="round"/>
          <rect x="140" y="292" width="92" height="150" rx="42" fill="{$color}"/>
          <rect x="368" y="292" width="92" height="150" rx="42" fill="{$color}"/>
          <rect x="214" y="306" width="30" height="122" rx="15" fill="{$cushion}"/>
          <rect x="356" y="306" width="30" height="122" rx="15" fill="{$cushion}"/>
          <rect x="158" y="312" width="22" height="70" rx="11" fill="#fff" opacity=".18"/>
          <rect x="386" y="312" width="22" height="70" rx="11" fill="#fff" opacity=".12"/>
        SVG;
}

function speaker(string $color): string
{
    $dots = '';
    for ($row = 0; $row < 6; ++$row) {
        for ($col = 0; $col < 14; ++$col) {
            $dots .= sprintf('<circle cx="%d" cy="%d" r="4.2"/>', 186 + $col * 17.5 + ($row % 2) * 8, 256 + $row * 17);
        }
    }

    return <<<SVG
          <rect x="150" y="214" width="300" height="196" rx="72" fill="{$color}"/>
          <rect x="164" y="228" width="272" height="168" rx="62" fill="#0f172a" opacity=".12"/>
          <g fill="#0f172a" opacity=".35">{$dots}</g>
          <rect x="268" y="196" width="64" height="18" rx="9" fill="#334155"/>
          <rect x="430" y="276" width="34" height="60" rx="17" fill="#334155"/>
          <path d="M164 300 Q164 228 236 228 H300 L164 360 Z" fill="url(#sheen)" opacity=".6"/>
        SVG;
}

function watch(string $caseColor, string $band): string
{
    return <<<SVG
          <rect x="246" y="84" width="108" height="150" rx="26" fill="{$band}"/>
          <rect x="246" y="368" width="108" height="150" rx="26" fill="{$band}"/>
          <rect x="208" y="196" width="184" height="214" rx="52" fill="{$caseColor}"/>
          <rect x="220" y="208" width="160" height="190" rx="42" fill="#0b0f19"/>
          <circle cx="300" cy="303" r="62" fill="none" stroke="#ef4444" stroke-width="14" stroke-dasharray="300 90" stroke-linecap="round" transform="rotate(-90 300 303)"/>
          <circle cx="300" cy="303" r="44" fill="none" stroke="#a3e635" stroke-width="14" stroke-dasharray="200 80" stroke-linecap="round" transform="rotate(-90 300 303)"/>
          <circle cx="300" cy="303" r="26" fill="none" stroke="#22d3ee" stroke-width="14" stroke-dasharray="120 50" stroke-linecap="round" transform="rotate(-90 300 303)"/>
          <rect x="392" y="262" width="12" height="44" rx="5" fill="{$caseColor}"/>
          <path d="M220 250 Q220 208 262 208 H320 L220 330 Z" fill="url(#sheen)" opacity=".5"/>
        SVG;
}

function fitnessBand(string $band): string
{
    return <<<SVG
          <rect x="262" y="70" width="76" height="460" rx="38" fill="{$band}"/>
          <rect x="248" y="200" width="104" height="200" rx="34" fill="#1e293b"/>
          <rect x="260" y="214" width="80" height="172" rx="26" fill="#0b0f19"/>
          <text x="300" y="282" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="30" fill="#fff">9:41</text>
          <path d="M278 316 h10 l6 -14 l8 26 l6 -12 h14" fill="none" stroke="#f472b6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
          <text x="300" y="356" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="15" fill="#94a3b8">72 bpm</text>
          <circle cx="300" cy="120" r="5" fill="#0f172a" opacity=".3"/><circle cx="300" cy="460" r="5" fill="#0f172a" opacity=".3"/>
        SVG;
}

function handheld(string $left, string $right): string
{
    return <<<SVG
          <path d="M150 200 H196 V396 H150 Q104 396 104 350 V246 Q104 200 150 200 Z" fill="{$left}"/>
          <path d="M450 200 H404 V396 H450 Q496 396 496 350 V246 Q496 200 450 200 Z" fill="{$right}"/>
          <rect x="196" y="196" width="208" height="204" rx="8" fill="#111827"/>
          <rect x="206" y="214" width="188" height="168" rx="6" fill="url(#screen)"/>
          <path d="M206 220 Q206 214 212 214 H300 L206 320 Z" fill="url(#sheen)"/>
          <circle cx="150" cy="248" r="17" fill="#111827"/>
          <g fill="#111827"><circle cx="150" cy="314" r="8"/><circle cx="134" cy="330" r="8"/><circle cx="166" cy="330" r="8"/><circle cx="150" cy="346" r="8"/></g>
          <circle cx="450" cy="340" r="17" fill="#111827"/>
          <g fill="#111827"><circle cx="450" cy="230" r="8"/><circle cx="434" cy="246" r="8"/><circle cx="466" cy="246" r="8"/><circle cx="450" cy="262" r="8"/></g>
        SVG;
}

function consoleTower(): string
{
    $holes = '';
    for ($i = 0; $i < 7; ++$i) {
        for ($j = 0; $j < 3; ++$j) {
            $holes .= sprintf('<circle cx="%d" cy="%d" r="3"/>', 268 + $i * 11 + $j * 5, 136 + $j * 9);
        }
    }

    return <<<SVG
          <path d="M218 150 L262 112 H424 L380 150 Z" fill="#334155"/>
          <ellipse cx="322" cy="131" rx="58" ry="14" fill="#16a34a" opacity=".85"/>
          <g fill="#0b0f19" opacity=".9">{$holes}</g>
          <path d="M380 150 L424 112 V440 L380 478 Z" fill="#111827"/>
          <rect x="218" y="150" width="162" height="328" rx="4" fill="#1f2937"/>
          <circle cx="242" cy="176" r="8" fill="#e2e8f0"/>
          <rect x="236" y="438" width="40" height="8" rx="4" fill="#0b0f19"/>
          <path d="M218 150 H300 L218 300 Z" fill="url(#sheen)" opacity=".25"/>
        SVG;
}

function mouse(string $color): string
{
    return <<<SVG
          <path d="M300 112 C392 112 416 196 416 290 C416 410 368 476 300 476 C232 476 184 410 184 290 C184 196 208 112 300 112 Z" fill="{$color}"/>
          <path d="M300 112 V250" stroke="#0f172a" stroke-opacity=".35" stroke-width="3"/>
          <path d="M184 270 Q240 252 300 250 Q360 252 416 270" fill="none" stroke="#0f172a" stroke-opacity=".2" stroke-width="3"/>
          <rect x="288" y="150" width="24" height="64" rx="12" fill="#0f172a" opacity=".75"/>
          <g stroke="#94a3b8" stroke-width="2"><path d="M292 162 H308"/><path d="M292 174 H308"/><path d="M292 186 H308"/><path d="M292 198 H308"/></g>
          <path d="M184 300 Q170 330 182 372 Q196 350 200 316 Z" fill="#0f172a" opacity=".35"/>
          <path d="M210 190 Q240 124 300 116 L230 320 Z" fill="url(#sheen)" opacity=".7"/>
        SVG;
}

function keyboard(): string
{
    $keys = '';
    $rows = [[15, 0], [15, 0], [14, 12], [13, 18], [10, 26]];
    foreach ($rows as $r => [$count, $offset]) {
        for ($k = 0; $k < $count; ++$k) {
            $keys .= sprintf('<rect x="%d" y="%d" width="25" height="25" rx="5"/>', 94 + $offset + $k * 28, 222 + $r * 32);
        }
    }

    return <<<SVG
          <defs>
            <linearGradient id="rgb" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0" stop-color="#22d3ee"/><stop offset=".35" stop-color="#a78bfa"/>
              <stop offset=".7" stop-color="#f472b6"/><stop offset="1" stop-color="#facc15"/>
            </linearGradient>
          </defs>
          <rect x="78" y="196" width="444" height="196" rx="18" fill="#111827"/>
          <rect x="78" y="196" width="444" height="196" rx="18" fill="none" stroke="#334155" stroke-width="2"/>
          <g fill="url(#rgb)" opacity=".9">{$keys}</g>
          <rect x="220" y="350" width="160" height="25" rx="5" fill="url(#rgb)" opacity=".9"/>
          <circle cx="490" cy="176" r="12" fill="#1f2937" stroke="#334155" stroke-width="2"/>
          <rect x="70" y="392" width="460" height="44" rx="16" fill="#1f2937"/>
        SVG;
}

function actionCam(): string
{
    return <<<SVG
          <rect x="166" y="196" width="268" height="206" rx="34" fill="#1f2937"/>
          <rect x="176" y="206" width="248" height="186" rx="26" fill="#111827"/>
          <rect x="196" y="230" width="86" height="72" rx="10" fill="url(#screen)"/>
          <circle cx="352" cy="300" r="62" fill="#0b0f19"/>
          <circle cx="352" cy="300" r="46" fill="#1e293b"/>
          <circle cx="352" cy="300" r="30" fill="#0f172a"/>
          <circle cx="340" cy="288" r="9" fill="#93c5fd" opacity=".7"/>
          <rect x="196" y="176" width="44" height="22" rx="8" fill="#ef4444"/>
          <rect x="196" y="350" width="40" height="8" rx="4" fill="#475569"/>
          <text x="196" y="380" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="16" fill="#64748b">HERO</text>
        SVG;
}

function mirrorless(): string
{
    return <<<SVG
          <path d="M248 170 H352 L370 206 H230 Z" fill="#1f2937"/>
          <rect x="128" y="200" width="344" height="222" rx="28" fill="#1f2937"/>
          <path d="M128 228 Q128 200 156 200 H206 V422 H156 Q128 422 128 394 Z" fill="#111827"/>
          <rect x="164" y="178" width="38" height="22" rx="6" fill="#334155"/>
          <circle cx="322" cy="312" r="98" fill="#0b0f19"/>
          <circle cx="322" cy="312" r="84" fill="#1e293b"/>
          <circle cx="322" cy="312" r="84" fill="none" stroke="#dc2626" stroke-width="5"/>
          <circle cx="322" cy="312" r="60" fill="#0f172a"/>
          <circle cx="322" cy="312" r="38" fill="#1e3a8a" opacity=".55"/>
          <circle cx="302" cy="292" r="14" fill="#bfdbfe" opacity=".55"/>
          <text x="160" y="250" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="14" fill="#e2e8f0" opacity=".8">EOS</text>
        SVG;
}

function powerBank(string $color): string
{
    return <<<SVG
          <rect x="206" y="128" width="188" height="344" rx="32" fill="{$color}"/>
          <rect x="220" y="142" width="160" height="316" rx="24" fill="#fff" opacity=".06"/>
          <rect x="256" y="114" width="36" height="14" rx="4" fill="#334155"/>
          <rect x="306" y="114" width="36" height="14" rx="4" fill="#334155"/>
          <g fill="#38bdf8"><circle cx="270" cy="420" r="6"/><circle cx="290" cy="420" r="6"/><circle cx="310" cy="420" r="6"/><circle cx="330" cy="420" r="6" opacity=".3"/></g>
          <text x="300" y="300" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="30" fill="#e2e8f0" opacity=".85">20000</text>
          <text x="300" y="326" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="15" fill="#94a3b8">mAh</text>
          <path d="M220 170 Q220 142 248 142 H330 L220 320 Z" fill="url(#sheen)" opacity=".35"/>
        SVG;
}

function drive(string $color): string
{
    $ridges = '';
    for ($i = 0; $i < 9; ++$i) {
        $ridges .= sprintf('<path d="M%d 310 L%d 462" />', 214 + $i * 20, 196 + $i * 20);
    }

    return <<<SVG
          <rect x="186" y="128" width="228" height="344" rx="26" fill="{$color}"/>
          <path d="M186 310 H414 V446 Q414 472 388 472 H212 Q186 472 186 446 Z" fill="#0f172a" opacity=".18"/>
          <g stroke="#fff" stroke-opacity=".18" stroke-width="7" stroke-linecap="round">{$ridges}</g>
          <circle cx="386" cy="160" r="6" fill="#f8fafc"/>
          <text x="214" y="176" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="18" fill="#fff" opacity=".85">WD</text>
          <path d="M200 160 Q200 142 220 142 H330 L200 290 Z" fill="url(#sheen)" opacity=".45"/>
        SVG;
}

function smartDisplay(): string
{
    $dots = '';
    for ($i = 0; $i < 22; ++$i) {
        for ($j = 0; $j < 4; ++$j) {
            $dots .= sprintf('<circle cx="%d" cy="%d" r="2.4"/>', 196 + $i * 10 + ($j % 2) * 5, 412 + $j * 10);
        }
    }

    return <<<SVG
          <path d="M180 398 H420 L436 452 Q438 462 428 462 H172 Q162 462 164 452 Z" fill="#cbd5e1"/>
          <g fill="#64748b" opacity=".35">{$dots}</g>
          <rect x="124" y="150" width="352" height="248" rx="22" fill="#f8fafc"/>
          <rect x="140" y="166" width="320" height="216" rx="10" fill="url(#screen)"/>
          <path d="M140 330 L210 268 L262 312 L330 236 L460 340 V372 Q460 382 450 382 H150 Q140 382 140 372 Z" fill="#0f172a" opacity=".22"/>
          <circle cx="400" cy="214" r="20" fill="#fef9c3" opacity=".9"/>
          <text x="164" y="208" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="30" fill="#fff">7:15</text>
        SVG;
}

function router(): string
{
    return <<<SVG
          <g fill="#1f2937">
            <rect x="150" y="150" width="20" height="170" rx="10" transform="rotate(-14 160 320)"/>
            <rect x="240" y="126" width="20" height="190" rx="10" transform="rotate(-5 250 316)"/>
            <rect x="340" y="126" width="20" height="190" rx="10" transform="rotate(5 350 316)"/>
            <rect x="430" y="150" width="20" height="170" rx="10" transform="rotate(14 440 320)"/>
          </g>
          <path d="M130 312 H470 L500 352 H100 Z" fill="#334155"/>
          <rect x="100" y="352" width="400" height="74" rx="14" fill="#111827"/>
          <g fill="#22c55e"><circle cx="196" cy="390" r="5"/><circle cx="226" cy="390" r="5"/><circle cx="256" cy="390" r="5"/><circle cx="286" cy="390" r="5"/></g>
          <circle cx="316" cy="390" r="5" fill="#38bdf8"/>
          <text x="440" y="396" text-anchor="end" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="15" fill="#64748b">AX</text>
        SVG;
}

function placeholder(): string
{
    return <<<SVG
          <path d="M300 160 L420 222 V362 L300 424 L180 362 V222 Z" fill="#cbd5e1"/>
          <path d="M300 160 L420 222 L300 284 L180 222 Z" fill="#e2e8f0"/>
          <path d="M300 284 V424" stroke="#94a3b8" stroke-width="3"/>
        SVG;
}

/* ---- the catalog ------------------------------------------------------- */

$art = [
    'iphone-15-pro' => ['phones', 'iPhone 15 Pro', phone('url(#metal)'), '#6366f1', '#ec4899'],
    'samsung-galaxy-s23' => ['phones', 'Samsung Galaxy S23', phone('#1f2937', false), '#0ea5e9', '#22d3ee'],
    'macbook-air-m2' => ['laptops', 'MacBook Air M2', laptop('#d6d3cb', '#e7e5df'), '#0ea5e9', '#a855f7'],
    'dell-xps-13' => ['laptops', 'Dell XPS 13', laptop('#94a3b8', '#cbd5e1'), '#2563eb', '#06b6d4'],
    'ipad-pro' => ['tablets', 'iPad Pro', tablet('#475569'), '#8b5cf6', '#f472b6'],
    'kindle-paperwhite' => ['tablets', 'Kindle Paperwhite', ereader('#1f2937')],
    'sony-wh-1000xm5' => ['audio', 'Sony WH-1000XM5', headphones('#1f2937', '#475569')],
    'bose-soundlink-flex' => ['audio', 'Bose SoundLink Flex', speaker('#1f2937')],
    'apple-watch-series-9' => ['wearables', 'Apple Watch Series 9', watch('#1e293b', '#334155')],
    'fitbit-charge-6' => ['wearables', 'Fitbit Charge 6', fitnessBand('#1f2937')],
    'nintendo-switch-oled' => ['gaming', 'Nintendo Switch OLED', handheld('#e2e8f0', '#e2e8f0'), '#f97316', '#facc15'],
    'xbox-series-x' => ['gaming', 'Xbox Series X', consoleTower()],
    'razer-blackwidow-v4' => ['gaming', 'Razer BlackWidow V4', keyboard()],
    'gopro-hero-11' => ['cameras', 'GoPro HERO11', actionCam(), '#0ea5e9', '#22c55e'],
    'canon-eos-r10' => ['cameras', 'Canon EOS R10', mirrorless()],
    'logitech-mx-master-3s' => ['accessories', 'Logitech MX Master 3S', mouse('#334155')],
    'anker-powercore-20000' => ['accessories', 'Anker PowerCore 20000', powerBank('#1f2937')],
    'wd-my-passport-2tb' => ['accessories', 'WD My Passport 2TB', drive('#2563eb')],
    'google-nest-hub' => ['smart-home', 'Google Nest Hub', smartDisplay(), '#38bdf8', '#f59e0b'],
    'tp-link-archer-ax50' => ['smart-home', 'TP-Link Archer AX50', router()],
    'placeholder' => ['other', 'Product image', placeholder()],
];

foreach ($art as $slug => $spec) {
    [$category, $title, $body] = $spec;
    $svg = frame($palettes[$category], $title, $body, $spec[3] ?? '#818cf8', $spec[4] ?? '#f472b6');
    // Collapse indentation; these are served, not read.
    $svg = preg_replace('/^\s+/m', '', $svg) . "\n";
    file_put_contents("{$out}/{$slug}.svg", $svg);
    printf("%-28s %5d bytes\n", "{$slug}.svg", strlen($svg));
}
