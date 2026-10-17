<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use TechNest\Auth\Auth;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\User\UserRepository;
use TechNest\View\View;

final class UserController
{
    public function __construct(
        private readonly View $view,
        private readonly UserRepository $users,
        private readonly Auth $auth,
        private readonly Flash $flash,
    ) {}

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/users/index', [
            'users' => $this->users->listWithStats(),
            'selfId' => $this->auth->id(),
        ]));
    }

    public function toggleActive(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id) ?? throw HttpException::notFound('That account does not exist.');

        if ($user->id === $this->auth->id()) {
            $this->flash->error('You cannot disable your own account.');
        } else {
            $this->users->setActive($user->id, !$user->isActive);
            $this->flash->success($user->isActive
                ? "{$user->username} is disabled and signed out everywhere."
                : "{$user->username} can sign in again.");
        }

        return Response::redirect('/admin/users');
    }

    public function toggleAdmin(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id) ?? throw HttpException::notFound('That account does not exist.');

        if ($user->id === $this->auth->id()) {
            // Guarantees the store can never lose its last administrator.
            $this->flash->error('You cannot remove your own admin access.');
        } elseif ($this->users->isAdmin($user->id)) {
            $this->users->revokeAdmin($user->id);
            $this->flash->success("{$user->username} is no longer an administrator.");
        } else {
            $this->users->grantAdmin($user->id, $this->auth->id());
            $this->flash->success("{$user->username} is now an administrator.");
        }

        return Response::redirect('/admin/users');
    }
}
