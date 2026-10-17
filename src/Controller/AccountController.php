<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Auth\Auth;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Order\InvalidTransition;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderService;
use TechNest\Support\Flash;
use TechNest\Support\Validator;
use TechNest\User\User;
use TechNest\User\UserRepository;
use TechNest\View\View;

final class AccountController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly OrderRepository $orders,
        private readonly OrderService $orderService,
        private readonly Flash $flash,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->user();

        return Response::html($this->view->render('account/index', [
            'user' => $user,
            'orders' => $this->orders->forUser($user->id),
            'errors' => [],
        ]));
    }

    public function order(Request $request, string $id): Response
    {
        $order = $this->orders->findForUser((int) $id, $this->user()->id)
            ?? throw HttpException::notFound('We could not find that order on your account.');

        return Response::html($this->view->render('account/order', ['order' => $order]));
    }

    public function cancel(Request $request, string $id): Response
    {
        try {
            $order = $this->orderService->cancelByCustomer((int) $id, $this->user()->id);
            $this->flash->success("Order {$order->number()} is cancelled.");
        } catch (InvalidTransition $e) {
            $this->flash->error($e->getMessage());
        }

        return Response::redirect('/account/orders/' . (int) $id);
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->user();
        $v = new Validator($request->body);

        if (!$this->auth->verifyPassword($user, $request->input('current_password'))) {
            $v->addError('current_password', 'Your current password is not correct.');
        }
        $password = $v->password('password', 10, 'New password');
        $v->matches('password_confirmation', 'password', 'The new passwords do not match.');

        if ($v->fails()) {
            return Response::html($this->view->render('account/index', [
                'user' => $user,
                'orders' => $this->orders->forUser($user->id),
                'errors' => $v->errors(),
            ]), 422);
        }

        $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        $this->auth->login($user);
        $this->flash->success('Your password has been changed.');

        return Response::redirect('/account');
    }

    private function user(): User
    {
        // The route guard guarantees a user; this narrows the type.
        return $this->auth->user() ?? throw HttpException::forbidden();
    }
}
