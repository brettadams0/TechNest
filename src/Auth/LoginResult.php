<?php

declare(strict_types=1);

namespace TechNest\Auth;

use TechNest\User\User;

final class LoginResult
{
    private function __construct(
        public readonly LoginOutcome $outcome,
        public readonly ?User $user = null,
        public readonly int $retryAfterSeconds = 0,
    ) {}

    public static function success(User $user): self
    {
        return new self(LoginOutcome::Success, $user);
    }

    public static function invalid(): self
    {
        return new self(LoginOutcome::InvalidCredentials);
    }

    public static function disabled(): self
    {
        return new self(LoginOutcome::Disabled);
    }

    public static function throttled(int $seconds): self
    {
        return new self(LoginOutcome::Throttled, null, $seconds);
    }

    public function succeeded(): bool
    {
        return $this->outcome === LoginOutcome::Success;
    }

    public function message(): string
    {
        return match ($this->outcome) {
            LoginOutcome::Success => '',
            // Deliberately the same message for "no such user" and "wrong
            // password", so the form cannot be used to discover accounts.
            LoginOutcome::InvalidCredentials => 'That username and password do not match.',
            LoginOutcome::Disabled => 'This account has been disabled. Contact support if you think that is a mistake.',
            LoginOutcome::Throttled => sprintf(
                'Too many failed attempts. Try again in %d minute%s.',
                $m = (int) ceil($this->retryAfterSeconds / 60),
                $m === 1 ? '' : 's',
            ),
        };
    }
}
