<?php

declare(strict_types=1);

namespace TechNest\Controller\Admin;

use DateTimeImmutable;
use TechNest\Admin\Chart;
use TechNest\Admin\Reports;
use TechNest\Catalog\ProductRepository;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Order\OrderRepository;
use TechNest\Support\Money;
use TechNest\View\View;

final class DashboardController
{
    private const DAYS = 30;

    public function __construct(
        private readonly View $view,
        private readonly Reports $reports,
        private readonly OrderRepository $orders,
        private readonly ProductRepository $products,
    ) {}

    public function index(Request $request): Response
    {
        $daily = $this->reports->dailyRevenue(self::DAYS);
        $categories = $this->reports->revenueByCategory(self::DAYS);

        $revenueChart = Chart::columns(
            array_map(static fn(array $d): array => [
                'label' => (new DateTimeImmutable($d['date']))->format('M j'),
                'value' => $d['revenue'],
                'title' => sprintf(
                    '%s: %s from %d order%s',
                    (new DateTimeImmutable($d['date']))->format('D M j'),
                    Money::format($d['revenue']),
                    $d['orders'],
                    $d['orders'] === 1 ? '' : 's',
                ),
            ], $daily),
            static fn(int $cents): string => '$' . number_format(intdiv($cents, 100)),
            'Revenue per day for the last ' . self::DAYS . ' days',
        );

        $categoryChart = Chart::bars(
            array_map(static fn(array $c): array => [
                'label' => $c['name'],
                'value' => $c['revenue'],
                'display' => Money::format($c['revenue']),
            ], $categories),
            'Revenue by category for the last ' . self::DAYS . ' days',
        );

        return Response::html($this->view->render('admin/dashboard', [
            'days' => self::DAYS,
            'summary' => $this->reports->summary(self::DAYS),
            'revenueChart' => $revenueChart,
            'categoryChart' => $categories === [] ? '' : $categoryChart,
            'topProducts' => $this->reports->topProducts(self::DAYS),
            'statusCounts' => $this->orders->countByStatus(),
            'recentOrders' => $this->orders->recent(6),
            'lowStock' => $this->products->lowStock(),
        ]));
    }
}
