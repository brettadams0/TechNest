<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use TechNest\Auth\Auth;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Order\InvalidTransition;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderService;
use TechNest\Order\OrderStatus;
use TechNest\Support\Flash;
use TechNest\View\View;

final class OrderController
{
    public function __construct(
        private readonly View $view,
        private readonly OrderRepository $orders,
        private readonly OrderService $service,
        private readonly Auth $auth,
        private readonly Flash $flash,
    ) {}

    public function index(Request $request): Response
    {
        $status = OrderStatus::tryFrom($request->query('status'));
        $orders = $this->orders->paginate($status, $request->queryPage());
        if ($orders->page > $orders->lastPage()) {
            $params = array_filter(['status' => $status?->value, 'page' => $orders->lastPage() > 1 ? $orders->lastPage() : null]);

            return Response::redirect('/admin/orders' . ($params === [] ? '' : '?' . http_build_query($params)));
        }

        return Response::html($this->view->render('admin/orders/index', [
            'orders' => $orders,
            'status' => $status,
            'counts' => $this->orders->countByStatus(),
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        $order = $this->orders->find((int) $id) ?? throw HttpException::notFound('That order does not exist.');

        return Response::html($this->view->render('admin/orders/show', ['order' => $order]));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $to = OrderStatus::tryFrom($request->input('status'));
        if ($to === null) {
            $this->flash->error('Choose a valid status.');
        } else {
            try {
                $order = $this->service->transition((int) $id, $to, $this->auth->id());
                $this->flash->success("Order {$order->number()} is now {$to->value}."
                    . ($to === OrderStatus::Cancelled ? ' Its stock has been returned.' : ''));
            } catch (InvalidTransition $e) {
                $this->flash->error($e->getMessage());
            }
        }

        return Response::redirect('/admin/orders/' . (int) $id);
    }
}
