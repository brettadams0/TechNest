<?php

declare(strict_types=1);

namespace TechNest\Http;

use RuntimeException;

/**
 * Thrown anywhere during a request to stop and render an error page with the
 * given status. The message is shown to the user, so keep it human.
 */
final class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public static function badRequest(string $message = ''): self
    {
        return new self(400, $message);
    }

    public static function notFound(string $message = ''): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = ''): self
    {
        return new self(403, $message);
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'That request could not be understood.',
            403 => 'You do not have access to that page.',
            404 => 'We could not find that page.',
            405 => 'That action is not allowed here.',
            419 => 'Your session expired before the form was sent. Please try again.',
            429 => 'Too many attempts. Please wait and try again.',
            default => 'Something went wrong on our side.',
        };
    }
}
