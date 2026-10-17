<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Auth\Auth;
use TechNest\Auth\LoginOutcome;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Security\SafeRedirect;
use TechNest\Security\Session;
use TechNest\Support\Flash;
use TechNest\Support\Validator;
use TechNest\User\UserRepository;
use TechNest\View\View;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly Session $session,
        private readonly Flash $flash,
    ) {}

    public function showLogin(Request $request): Response
    {
        $this->rememberNext($request);

        return Response::html($this->view->render('auth/login', ['login' => '', 'error' => '']));
    }

    public function login(Request $request): Response
    {
        $login = trim($request->input('login'));
        $result = $this->auth->attempt($login, $request->input('password'), $request->ip());

        if (!$result->succeeded()) {
            $status = $result->outcome === LoginOutcome::Throttled ? 429 : 422;

            return Response::html(
                $this->view->render('auth/login', ['login' => $login, 'error' => $result->message()]),
                $status,
            );
        }

        $this->flash->success('Welcome back, ' . ($result->user->username ?? '') . '.');

        return Response::redirect($this->intended('/account'));
    }

    public function showRegister(Request $request): Response
    {
        $this->rememberNext($request);

        return Response::html($this->view->render('auth/register', ['errors' => [], 'old' => []]));
    }

    public function register(Request $request): Response
    {
        $v = new Validator($request->body);
        $username = $v->string('username', 'Username', 3, 30, '/^[A-Za-z0-9_.-]+$/', 'Use letters, numbers, dots, dashes and underscores only.');
        $email = $v->email('email');
        $password = $v->password('password');
        $v->matches('password_confirmation', 'password', 'The passwords do not match.');

        if (!$v->fails()) {
            $taken = $this->users->taken($username, $email);
            if ($taken['username']) {
                $v->addError('username', 'That username is taken.');
            }
            if ($taken['email']) {
                $v->addError('email', 'An account already uses that email. Try signing in.');
            }
        }

        if ($v->fails()) {
            return Response::html($this->view->render('auth/register', [
                'errors' => $v->errors(),
                'old' => ['username' => $username, 'email' => $email],
            ]), 422);
        }

        $user = $this->users->create($username, $email, password_hash($password, PASSWORD_DEFAULT));
        $this->auth->login($user);
        $this->flash->success("Welcome to TechNest, {$user->username}.");

        return Response::redirect($this->intended('/account'));
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        $this->flash->info('You have been signed out.');

        return Response::redirect('/');
    }

    /** Support /login?next=/checkout links, validated against open redirects. */
    private function rememberNext(Request $request): void
    {
        $next = SafeRedirect::localPath($request->query('next'), '');
        if ($next !== '') {
            $this->session->set('intended', $next);
        }
    }

    private function intended(string $fallback): string
    {
        $intended = $this->session->pull('intended');

        return SafeRedirect::localPath(is_string($intended) ? $intended : null, $fallback);
    }
}
