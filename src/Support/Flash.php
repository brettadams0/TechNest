<?php

declare(strict_types=1);

namespace TechNest\Support;

use TechNest\Security\Session;

/** One-shot messages shown on the next page after a redirect. */
final class Flash
{
    private const KEY = '_flash';

    public function __construct(private readonly Session $session) {}

    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    public function info(string $message): void
    {
        $this->add('info', $message);
    }

    /** @return list<array{type: string, message: string}> and forget them */
    public function consume(): array
    {
        $messages = $this->session->pull(self::KEY, []);
        if (!is_array($messages)) {
            return [];
        }

        return array_values(array_filter(
            $messages,
            static fn($m): bool => is_array($m) && is_string($m['type'] ?? null) && is_string($m['message'] ?? null),
        ));
    }

    private function add(string $type, string $message): void
    {
        $messages = $this->session->get(self::KEY, []);
        $messages = is_array($messages) ? $messages : [];
        $messages[] = ['type' => $type, 'message' => $message];
        $this->session->set(self::KEY, $messages);
    }
}
