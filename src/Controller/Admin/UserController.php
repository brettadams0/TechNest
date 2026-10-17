<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use TechNest\Auth\Auth;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Support\Flash;
use TechNest\User\User;
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

    /** Sets the posted state (active=1 or 0), so a repeat cannot undo it. */
    public function setActive(Request $request, string $id): Response
    {
        $user = $this->find($id);
        $active = $request->inputFlag('active') ?? throw HttpException::badRequest();

        if ($user->id === $this->auth->id()) {
            $this->flash->error('You cannot disable your own account.');
        } elseif ($active === $user->isActive) {
            $this->flash->info($active ? "{$user->username} is already active." : "{$user->username} is already disabled.");
        } else {
            $this->users->setActive($user->id, $active);
            $this->flash->success($active
                ? "{$user->username} can sign in again."
                : "{$user->username} is disabled and signed out everywhere.");
        }

        return Response::redirect('/admin/users');
    }

    /** Sets the posted state (admin=1 or 0), so a repeat cannot undo it. */
    public function setAdmin(Request $request, string $id): Response
    {
        $user = $this->find($id);
        $admin = $request->inputFlag('admin') ?? throw HttpException::badRequest();

        if ($user->id === $this->auth->id()) {
            // Guarantees the store can never lose its last administrator.
            $this->flash->error('You cannot remove your own admin access.');
        } elseif ($admin === $this->users->isAdmin($user->id)) {
            $this->flash->info($admin ? "{$user->username} is already an administrator." : "{$user->username} is not an administrator.");
        } elseif ($admin) {
            $this->users->grantAdmin($user->id, $this->auth->id());
            $this->flash->success("{$user->username} is now an administrator.");
        } else {
            $this->users->revokeAdmin($user->id);
            $this->flash->success("{$user->username} is no longer an administrator.");
        }

        return Response::redirect('/admin/users');
    }

    private function find(string $id): User
    {
        return $this->users->find((int) $id) ?? throw HttpException::notFound('That account does not exist.');
    }
}
