<?php

declare(strict_types=1);

namespace TechNest;

use DateTimeZone;
use TechNest\Admin\Reports;
use TechNest\Auth\Auth;
use TechNest\Cart\Cart;
use TechNest\Cart\CartPricer;
use TechNest\Catalog\CategoryRepository;
use TechNest\Catalog\ProductRepository;
use TechNest\Checkout\CheckoutService;
use TechNest\Checkout\PriceCalculator;
use TechNest\Database\Database;
use TechNest\Http\HttpException;
use TechNest\Http\Request;
use TechNest\Http\Response;
use TechNest\Http\Route;
use TechNest\Http\Router;
use TechNest\Order\OrderRepository;
use TechNest\Order\OrderService;
use TechNest\Security\Csrf;
use TechNest\Security\LoginThrottle;
use TechNest\Security\SecurityHeaders;
use TechNest\Security\Session;
use TechNest\Support\Clock;
use TechNest\Support\Flash;
use TechNest\Theme\ThemeRepository;
use TechNest\User\UserRepository;
use TechNest\View\View;
use Throwable;

/**
 * The request pipeline, in order:
 *
 *   match route → verify CSRF (every non-GET) → enforce the route's guard →
 *   call the controller → add security headers
 *
 * Both checks happen here, before any controller runs, so neither can be
 * forgotten on a new page.
 */
final class Application
{
    public readonly Router $router;

    /**
     * Services are rebuilt for every request (see handle()), because several
     * of them memoise per-request state: the signed-in user, the view's
     * shared data. This holds the most recent set.
     */
    private Container $container;

    public function __construct(
        private readonly string $rootDir,
        private readonly Config $config,
        private readonly Database $db,
        private readonly Session $session,
        private readonly Clock $clock,
    ) {
        $this->router = new Router();
        $this->routes();
        $this->container = $this->services(null);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(Request $request): Response
    {
        $this->container = $this->services($request);

        try {
            [$route, $params] = $this->router->match($request->method, $request->path);

            if (!$request->isGet() && !$this->container->get(Csrf::class)->isValid($request->input(Csrf::FIELD))) {
                throw new HttpException(419);
            }

            $response = $this->guard($route, $request) ?? $this->dispatch($route, $request, $params);
        } catch (HttpException $e) {
            $response = $this->errorPage($e->status, $e->getMessage());
            foreach ($e->headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        } catch (Throwable $e) {
            error_log(sprintf('[technest] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
            $message = $this->config->debug() ? $e::class . ': ' . $e->getMessage() : '';
            $response = $this->errorPage(500, $message);
        }

        $response = $response->withHeader('Cache-Control', 'no-store, private');

        return SecurityHeaders::apply($response, $request->isSecure());
    }

    /** Returns a response that short-circuits the request, or null to continue. */
    private function guard(Route $route, Request $request): ?Response
    {
        $auth = $this->container->get(Auth::class);

        switch ($route->guard) {
            case Route::GUARD_GUEST:
                return $auth->check() ? Response::redirect('/account') : null;

            case Route::GUARD_AUTH:
            case Route::GUARD_ADMIN:
                if (!$auth->check()) {
                    if ($request->isGet()) {
                        $this->container->get(Session::class)->set('intended', $request->fullPath());
                    }
                    $this->container->get(Flash::class)->info('Please sign in to continue.');

                    return Response::redirect('/login');
                }
                if ($route->guard === Route::GUARD_ADMIN && !$auth->isAdmin()) {
                    throw HttpException::forbidden('That page is for store administrators.');
                }

                return null;

            default:
                return null;
        }
    }

    /** @param array<string, string> $params */
    private function dispatch(Route $route, Request $request, array $params): Response
    {
        [$class, $method] = $route->handler;
        $controller = $this->container->get($class);

        $response = $controller->{$method}($request, ...$params);
        if (!$response instanceof Response) {
            throw new \LogicException("{$class}::{$method} did not return a Response");
        }

        return $response;
    }

    private function errorPage(int $status, string $message): Response
    {
        $message = $message !== '' ? $message : HttpException::defaultMessage($status);

        try {
            $html = $this->container->get(View::class)->render('errors/error', [
                'status' => $status,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            // The layout needs the database (theme, cart, user). If that is
            // what failed, fall back to a page that needs nothing.
            error_log('[technest] error page failed: ' . $e->getMessage());
            $html = '<!doctype html><meta charset="utf-8"><title>TechNest</title>'
                . '<h1>' . $status . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        return Response::html($html, $status);
    }

    private function services(?Request $request): Container
    {
        $c = new Container();
        $rootDir = $this->rootDir;
        $db = $this->db;
        $session = $this->session;
        $clock = $this->clock;

        $c->set(Config::class, fn() => $this->config);
        $c->set(Database::class, static fn() => $db);
        $c->set(Session::class, static fn() => $session);
        $c->set(Clock::class, static fn() => $clock);
        if ($request !== null) {
            $c->set(Request::class, static fn() => $request);
        }

        $c->set(Csrf::class, static fn(Container $c) => new Csrf($c->get(Session::class)));
        $c->set(Flash::class, static fn(Container $c) => new Flash($c->get(Session::class)));
        $c->set(Cart::class, static fn(Container $c) => new Cart($c->get(Session::class)));
        $c->set(PriceCalculator::class, static fn() => new PriceCalculator());

        $c->set(UserRepository::class, static fn(Container $c) => new UserRepository($c->get(Database::class), $c->get(Clock::class)));
        $c->set(ProductRepository::class, static fn(Container $c) => new ProductRepository($c->get(Database::class), $c->get(Clock::class)));
        $c->set(CategoryRepository::class, static fn(Container $c) => new CategoryRepository($c->get(Database::class)));
        $c->set(OrderRepository::class, static fn(Container $c) => new OrderRepository($c->get(Database::class)));
        $c->set(ThemeRepository::class, static fn(Container $c) => new ThemeRepository($c->get(Database::class)));
        $c->set(Reports::class, static fn(Container $c) => new Reports($c->get(Database::class), $c->get(Clock::class)));
        $c->set(LoginThrottle::class, static fn(Container $c) => new LoginThrottle($c->get(Database::class), $c->get(Clock::class)));

        $c->set(Auth::class, static fn(Container $c) => new Auth(
            $c->get(Session::class),
            $c->get(UserRepository::class),
            $c->get(LoginThrottle::class),
            $c->get(Csrf::class),
        ));
        $c->set(CartPricer::class, static fn(Container $c) => new CartPricer($c->get(ProductRepository::class), $c->get(PriceCalculator::class)));
        $c->set(CheckoutService::class, static fn(Container $c) => new CheckoutService(
            $c->get(Database::class),
            $c->get(CartPricer::class),
            $c->get(OrderRepository::class),
            $c->get(Clock::class),
        ));
        $c->set(OrderService::class, static fn(Container $c) => new OrderService(
            $c->get(Database::class),
            $c->get(OrderRepository::class),
            $c->get(Clock::class),
        ));

        $c->set(View::class, fn(Container $c) => new View(
            $rootDir . '/templates',
            $rootDir . '/public',
            static function () use ($c): array {
                $auth = $c->get(Auth::class);

                return [
                    'currentUser' => $auth->user(),
                    'isAdmin' => $auth->isAdmin(),
                    'cartCount' => $c->get(Cart::class)->count(),
                    'theme' => $c->get(ThemeRepository::class)->active(),
                    'csrfToken' => $c->get(Csrf::class)->token(),
                    'flashes' => $c->get(Flash::class)->consume(),
                    'currentPath' => $c->has(Request::class) ? $c->get(Request::class)->path : '/',
                    'searchQuery' => $c->has(Request::class) ? $c->get(Request::class)->query('q') : '',
                    'isDemo' => !$c->get(Config::class)->isProduction(),
                    'pricing' => $c->get(PriceCalculator::class),
                ];
            },
            new DateTimeZone($this->config->get('APP_TIMEZONE', 'America/Toronto')),
        ));

        // Controllers.
        $c->set(Controller\ShopController::class, static fn(Container $c) => new Controller\ShopController(
            $c->get(View::class),
            $c->get(ProductRepository::class),
            $c->get(CategoryRepository::class),
        ));
        $c->set(Controller\CartController::class, static fn(Container $c) => new Controller\CartController(
            $c->get(View::class),
            $c->get(Cart::class),
            $c->get(CartPricer::class),
            $c->get(ProductRepository::class),
            $c->get(PriceCalculator::class),
            $c->get(Flash::class),
        ));
        $c->set(Controller\CheckoutController::class, static fn(Container $c) => new Controller\CheckoutController(
            $c->get(View::class),
            $c->get(Auth::class),
            $c->get(Cart::class),
            $c->get(CartPricer::class),
            $c->get(CheckoutService::class),
            $c->get(Flash::class),
        ));
        $c->set(Controller\AuthController::class, static fn(Container $c) => new Controller\AuthController(
            $c->get(View::class),
            $c->get(Auth::class),
            $c->get(UserRepository::class),
            $c->get(Session::class),
            $c->get(Flash::class),
            $c->get(LoginThrottle::class),
        ));
        $c->set(Controller\AccountController::class, static fn(Container $c) => new Controller\AccountController(
            $c->get(View::class),
            $c->get(Auth::class),
            $c->get(UserRepository::class),
            $c->get(OrderRepository::class),
            $c->get(OrderService::class),
            $c->get(Flash::class),
        ));
        $c->set(Controller\PageController::class, static fn(Container $c) => new Controller\PageController(
            $c->get(View::class),
            $c->get(Database::class),
            $c->get(PriceCalculator::class),
        ));
        $c->set(Controller\Admin\DashboardController::class, static fn(Container $c) => new Controller\Admin\DashboardController(
            $c->get(View::class),
            $c->get(Reports::class),
            $c->get(OrderRepository::class),
            $c->get(ProductRepository::class),
        ));
        $c->set(Controller\Admin\ProductController::class, static fn(Container $c) => new Controller\Admin\ProductController(
            $c->get(View::class),
            $c->get(ProductRepository::class),
            $c->get(CategoryRepository::class),
            $c->get(Flash::class),
            $rootDir . '/public/assets/img/products',
        ));
        $c->set(Controller\Admin\OrderController::class, static fn(Container $c) => new Controller\Admin\OrderController(
            $c->get(View::class),
            $c->get(OrderRepository::class),
            $c->get(OrderService::class),
            $c->get(Auth::class),
            $c->get(Flash::class),
        ));
        $c->set(Controller\Admin\UserController::class, static fn(Container $c) => new Controller\Admin\UserController(
            $c->get(View::class),
            $c->get(UserRepository::class),
            $c->get(Auth::class),
            $c->get(Flash::class),
        ));
        $c->set(Controller\Admin\ThemeController::class, static fn(Container $c) => new Controller\Admin\ThemeController(
            $c->get(View::class),
            $c->get(ThemeRepository::class),
            $c->get(Flash::class),
        ));

        return $c;
    }

    private function routes(): void
    {
        $r = $this->router;

        $r->get('/', [Controller\ShopController::class, 'home']);
        $r->get('/catalog', [Controller\ShopController::class, 'catalog']);
        $r->get('/products/{slug:[a-z0-9-]+}', [Controller\ShopController::class, 'product']);

        $r->get('/cart', [Controller\CartController::class, 'show']);
        $r->post('/cart/add', [Controller\CartController::class, 'add']);
        $r->post('/cart/update', [Controller\CartController::class, 'update']);
        $r->post('/cart/remove', [Controller\CartController::class, 'remove']);

        $r->get('/help', [Controller\PageController::class, 'help']);
        $r->get('/health', [Controller\PageController::class, 'health']);

        $r->group(Route::GUARD_GUEST, static function (Router $r): void {
            $r->get('/login', [Controller\AuthController::class, 'showLogin']);
            $r->post('/login', [Controller\AuthController::class, 'login']);
            $r->get('/register', [Controller\AuthController::class, 'showRegister']);
            $r->post('/register', [Controller\AuthController::class, 'register']);
        });

        $r->group(Route::GUARD_AUTH, static function (Router $r): void {
            $r->post('/logout', [Controller\AuthController::class, 'logout']);
            $r->get('/checkout', [Controller\CheckoutController::class, 'show']);
            $r->post('/checkout', [Controller\CheckoutController::class, 'place']);
            $r->get('/account', [Controller\AccountController::class, 'index']);
            $r->post('/account/password', [Controller\AccountController::class, 'changePassword']);
            $r->get('/account/orders/{id:\d+}', [Controller\AccountController::class, 'order']);
            $r->post('/account/orders/{id:\d+}/cancel', [Controller\AccountController::class, 'cancel']);
        });

        $r->group(Route::GUARD_ADMIN, static function (Router $r): void {
            $r->get('/admin', [Controller\Admin\DashboardController::class, 'index']);

            $r->get('/admin/products', [Controller\Admin\ProductController::class, 'index']);
            $r->get('/admin/products/new', [Controller\Admin\ProductController::class, 'create']);
            $r->post('/admin/products', [Controller\Admin\ProductController::class, 'store']);
            $r->get('/admin/products/{id:\d+}/edit', [Controller\Admin\ProductController::class, 'edit']);
            $r->post('/admin/products/{id:\d+}', [Controller\Admin\ProductController::class, 'update']);
            $r->post('/admin/products/{id:\d+}/archive', [Controller\Admin\ProductController::class, 'toggleArchived']);
            $r->post('/admin/products/{id:\d+}/options', [Controller\Admin\ProductController::class, 'addOption']);
            $r->post('/admin/products/{id:\d+}/options/{optionId:\d+}/delete', [Controller\Admin\ProductController::class, 'deleteOption']);

            $r->get('/admin/orders', [Controller\Admin\OrderController::class, 'index']);
            $r->get('/admin/orders/{id:\d+}', [Controller\Admin\OrderController::class, 'show']);
            $r->post('/admin/orders/{id:\d+}/status', [Controller\Admin\OrderController::class, 'updateStatus']);

            $r->get('/admin/users', [Controller\Admin\UserController::class, 'index']);
            $r->post('/admin/users/{id:\d+}/active', [Controller\Admin\UserController::class, 'toggleActive']);
            $r->post('/admin/users/{id:\d+}/admin', [Controller\Admin\UserController::class, 'toggleAdmin']);

            $r->get('/admin/themes', [Controller\Admin\ThemeController::class, 'index']);
            $r->post('/admin/themes', [Controller\Admin\ThemeController::class, 'activate']);
        });
    }
}
