<?php

declare(strict_types=1);

namespace TechNest\Admin;

/**
 * Charts rendered on the server as inline SVG: no JavaScript library, nothing
 * for the Content-Security-Policy to allow, and they print. Colours come from
 * CSS classes, so each theme restyles them.
 *
 * Every bar carries a <title> (a native tooltip, also read by screen readers)
 * and the whole chart has an accessible name.
 */
final class Chart
{
    /**
     * Vertical bars over time, e.g. revenue per day.
     *
     * @param list<array{label: string, value: int, title: string}> $points
     * @param callable(int): string                                 $formatAxis
     */
    public static function columns(array $points, callable $formatAxis, string $ariaLabel, int $width = 720, int $height = 240): string
    {
        $padLeft = 64;
        $padBottom = 28;
        $padTop = 12;
        $plotWidth = $width - $padLeft - 8;
        $plotHeight = $height - $padBottom - $padTop;

        $max = self::niceMax(max([1, ...array_map(static fn(array $p): int => $p['value'], $points)]));
        $count = max(1, count($points));
        $slot = $plotWidth / $count;
        $barWidth = max(2.0, $slot * 0.7);

        $svg = sprintf(
            '<svg class="chart chart--columns" viewBox="0 0 %d %d" role="img" aria-label="%s">',
            $width,
            $height,
            self::esc($ariaLabel),
        );

        // Four horizontal gridlines with axis labels.
        for ($i = 0; $i <= 4; ++$i) {
            $value = (int) round($max * $i / 4);
            $y = $padTop + $plotHeight - $plotHeight * $i / 4;
            $svg .= sprintf('<line class="chart__grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $padLeft, $width - 8, $y, $y);
            $svg .= sprintf('<text class="chart__axis" x="%d" y="%.1f" text-anchor="end">%s</text>', $padLeft - 8, $y + 4, self::esc($formatAxis($value)));
        }

        $labelEvery = (int) max(1, ceil($count / 8));
        foreach ($points as $i => $point) {
            $barHeight = $plotHeight * $point['value'] / $max;
            $x = $padLeft + $slot * $i + ($slot - $barWidth) / 2;
            $y = $padTop + $plotHeight - $barHeight;
            $svg .= sprintf(
                '<rect class="chart__bar" x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="2"><title>%s</title></rect>',
                $x,
                $y,
                $barWidth,
                max(0.0, $barHeight),
                self::esc($point['title']),
            );
            if ($i % $labelEvery === 0) {
                $svg .= sprintf(
                    '<text class="chart__axis" x="%.1f" y="%d" text-anchor="middle">%s</text>',
                    $x + $barWidth / 2,
                    $height - 8,
                    self::esc($point['label']),
                );
            }
        }

        return $svg . '</svg>';
    }

    /**
     * A ranked breakdown, e.g. revenue by category, as an HTML list whose
     * bars are SVG rects sized in percent. Text stays real HTML text, so it
     * wraps and scales like the rest of the page instead of shrinking with
     * an SVG viewBox.
     *
     * @param list<array{label: string, value: int, display: string}> $rows
     */
    public static function bars(array $rows, string $ariaLabel): string
    {
        $max = max([1, ...array_map(static fn(array $r): int => $r['value'], $rows)]);

        $html = sprintf('<ul class="bar-list" aria-label="%s">', self::esc($ariaLabel));
        foreach ($rows as $row) {
            $percent = max(1.0, 100 * $row['value'] / $max);
            $html .= sprintf(
                '<li><span class="bar-list__label">%s</span>'
                . '<svg class="bar-list__bar" width="100%%" height="10" aria-hidden="true" focusable="false">'
                . '<rect class="chart__track" width="100%%" height="10" rx="5"/>'
                . '<rect class="chart__bar" width="%.1f%%" height="10" rx="5"/></svg>'
                . '<span class="bar-list__value">%s</span></li>',
                self::esc($row['label']),
                $percent,
                self::esc($row['display']),
            );
        }

        return $html . '</ul>';
    }

    /**
     * Round an axis maximum up to 1, 2, 2.5 or 5 times a power of ten, so the
     * gridlines land on readable numbers.
     */
    public static function niceMax(int $value): int
    {
        if ($value <= 1) {
            return 1;
        }

        $magnitude = 10 ** (int) floor(log10($value));
        foreach ([1, 2, 2.5, 5, 10] as $step) {
            $candidate = (int) ceil($step * $magnitude);
            if ($candidate >= $value) {
                return $candidate;
            }
        }

        return 10 * $magnitude;
    }

    private static function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }
}
