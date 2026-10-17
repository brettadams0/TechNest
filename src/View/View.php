<?php

declare(strict_types=1);

namespace TechNest\View;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use TechNest\Security\Csrf;
use TechNest\Support\Money;
use Throwable;

/**
 * Plain-PHP templates. A template can declare a layout with
 * $this->layout('layout'); its output is then passed to that layout as
 * $content. Helpers are methods on $this inside a template.
 *
 * Every dynamic value in a template goes through $this->e(). The test suite
 * backs that convention up by rendering each page with hostile data.
 */
final class View
{
    private ?string $layout = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /** @var array<string, mixed>|null */
    private ?array $shared = null;

    /**
     * @param Closure(): array<string, mixed> $sharedData data every template
     *                                                    sees (current user, cart count, theme…), computed once
     */
    public function __construct(
        private readonly string $templatesDir,
        private readonly string $publicDir,
        private readonly Closure $sharedData,
        private readonly DateTimeZone $displayTimezone = new DateTimeZone('America/Toronto'),
    ) {}

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->globals(), $data);
        $this->layout = null;
        $content = $this->renderFile($template, $data);

        while (($layout = $this->takeLayout()) !== null) {
            $content = $this->renderFile($layout, array_merge($data, $this->layoutData, ['content' => $content]));
        }

        return $content;
    }

    /**
     * Render a partial (no layout) with the shared data plus $data.
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $template, array $data = []): string
    {
        $outerLayout = $this->layout;
        $outerData = $this->layoutData;
        try {
            return $this->renderFile($template, array_merge($this->globals(), $data));
        } finally {
            $this->layout = $outerLayout;
            $this->layoutData = $outerData;
        }
    }

    /** @param array<string, mixed> $data */
    public function layout(string $template, array $data = []): void
    {
        $this->layout = $template;
        $this->layoutData = $data;
    }

    public function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public function money(int $cents): string
    {
        return Money::format($cents);
    }

    public function moneyShort(int $cents): string
    {
        return Money::formatShort($cents);
    }

    /** A stored UTC timestamp, shown in the store's local time. */
    public function date(string $utc, string $format = 'M j, Y'): string
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
            ->setTimezone($this->displayTimezone)
            ->format($format);
    }

    /** @param array<string, string|int> $params */
    public function url(string $path, array $params = []): string
    {
        return $params === [] ? $path : $path . '?' . http_build_query($params);
    }

    /** Asset URL with a modification-time version, so caches bust on deploy. */
    public function asset(string $path): string
    {
        $file = $this->publicDir . $path;
        $version = is_file($file) ? (string) filemtime($file) : '0';

        return $path . '?v=' . $version;
    }

    public function csrfField(): string
    {
        $token = $this->globals()['csrfToken'] ?? '';

        return '<input type="hidden" name="' . Csrf::FIELD . '" value="' . $this->e(is_string($token) ? $token : '') . '">';
    }

    /** aria-current for navigation links. */
    public function current(string $path, bool $prefix = false): string
    {
        $currentPath = $this->globals()['currentPath'] ?? '/';
        $currentPath = is_string($currentPath) ? $currentPath : '/';
        $match = $prefix ? str_starts_with($currentPath, $path) : $currentPath === $path;

        return $match ? ' aria-current="page"' : '';
    }

    /**
     * aria-invalid/aria-describedby for a form control with an error, so
     * screen readers announce the message with the field.
     *
     * @param array<string, string> $errors
     */
    public function fieldState(array $errors, string $field): string
    {
        return isset($errors[$field])
            ? ' aria-invalid="true" aria-describedby="' . $this->e($field) . '-error"'
            : '';
    }

    /**
     * A previously submitted value, for re-filling a form after an error.
     *
     * @param array<string, mixed> $old
     */
    public function old(array $old, string $key, string $default = ''): string
    {
        $value = $old[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function icon(string $name, string $class = 'icon'): string
    {
        return Icon::svg($name, $class);
    }

    public function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
    }

    /** The layout the template just rendered asked for, if any, consumed. */
    private function takeLayout(): ?string
    {
        $layout = $this->layout;
        $this->layout = null;

        return $layout;
    }

    /** @return array<string, mixed> */
    private function globals(): array
    {
        return $this->shared ??= ($this->sharedData)();
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        $path = $this->templatesDir . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException("Template not found: {$template}");
        }

        $render = function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            include $__path;
        };

        ob_start();
        try {
            $render->call($this, $path, $data);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
