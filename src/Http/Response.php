<?php

declare(strict_types=1);

namespace TechNest\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public array $headers = [],
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * 303 See Other, so a refresh after a POST re-GETs the page instead of
     * resubmitting the form (Post/Redirect/Get).
     */
    public static function redirect(string $location, int $status = 303): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
