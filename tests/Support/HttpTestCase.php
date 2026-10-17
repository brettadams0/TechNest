<?php

declare(strict_types=1);

namespace TechNest\Tests\Support;

use TechNest\Application;
use TechNest\Config;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Security\ArraySession;
use TechNest\Security\Csrf;
use TechNest\User\User;

/**
 * Drives the whole application in-process: routing, CSRF, guards,
 * controllers, templates and the database, with no web server. Requests in
 * one test share a session, like a browser keeping its cookie.
 */
abstract class HttpTestCase extends DatabaseTestCase
{
    protected ArraySession $session;
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new ArraySession();
        $this->app = new Application(
            TestDatabase::root(),
            Config::fromArray(['APP_ENV' => 'testing', 'APP_DEBUG' => '1']),
            $this->db,
            $this->session,
            $this->clock,
        );
    }

    protected function get(string $uri): Response
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        /** @var array<string, mixed> $query */
        return $this->app->handle(new Request('GET', Request::normalisePath($path), $query, [], ['REMOTE_ADDR' => '203.0.113.7']));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function post(string $path, array $data = [], bool $withCsrfToken = true): Response
    {
        if ($withCsrfToken) {
            $data[Csrf::FIELD] ??= (new Csrf($this->session))->token();
        }

        return $this->app->handle(new Request('POST', $path, [], $data, ['REMOTE_ADDR' => '203.0.113.7']));
    }

    protected function actingAs(User $user): void
    {
        $this->session->set('user_id', $user->id);
        $this->session->set('session_version', $user->sessionVersion);
    }

    protected function assertStatus(int $expected, Response $response): void
    {
        self::assertSame($expected, $response->status, sprintf(
            "Expected HTTP %d, got %d.\n%s",
            $expected,
            $response->status,
            mb_substr(strip_tags($response->body), 0, 1500),
        ));
    }

    protected function assertRedirect(string $location, Response $response): void
    {
        self::assertContains($response->status, [301, 302, 303], "Expected a redirect, got HTTP {$response->status}.");
        self::assertSame($location, $response->header('Location'));
    }

    protected function assertSee(string $text, Response $response): void
    {
        self::assertStringContainsString(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5), $response->body);
    }

    protected function assertDontSee(string $text, Response $response): void
    {
        self::assertStringNotContainsString(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5), $response->body);
    }
}
