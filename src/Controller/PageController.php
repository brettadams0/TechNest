<?php

declare(strict_types=1);

namespace TechNest\Controller;

use TechNest\Checkout\PriceCalculator;
use TechNest\Database\Database;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\View\View;
use Throwable;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly Database $db,
        private readonly PriceCalculator $calculator,
    ) {}

    public function help(Request $request): Response
    {
        return Response::html($this->view->render('pages/help', ['calculator' => $this->calculator]));
    }

    /**
     * Liveness/readiness for a load balancer or uptime monitor. Reports only
     * up/down — unlike the original monitor.php, which printed the PHP
     * version to anyone who asked.
     */
    public function health(Request $request): Response
    {
        try {
            $this->db->fetchValue('SELECT 1');

            return Response::json(['status' => 'ok', 'database' => 'ok']);
        } catch (Throwable) {
            return Response::json(['status' => 'degraded', 'database' => 'unreachable'], 503);
        }
    }
}
