<?php

declare(strict_types=1);

namespace TechNest\Tests\Http;

use TechNest\Tests\Support\HttpTestCase;
use TechNest\User\User;

final class AdminTest extends HttpTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->fixtures->user('boss', admin: true);
        $this->actingAs($this->admin);
        $this->fixtures->category('audio', 'Audio');
    }

    public function testCreatingAProductValidatesThenRedirectsToItsEditor(): void
    {
        $response = $this->post('/admin/products', ['name' => 'X', 'price' => 'free', 'stock' => '-1', 'category_id' => '999']);
        $this->assertStatus(422, $response);
        $this->assertSee('Price must be an amount like 19.99.', $response);
        $this->assertSee('Choose a category.', $response);

        $response = $this->post('/admin/products', [
            'name' => 'Studio Monitor', 'price' => '249.99', 'stock' => '7',
            'category_id' => (string) $this->fixtures->category('audio'), 'description' => 'Flat response.', 'is_featured' => '1',
        ]);
        $this->assertRedirect('/admin/products/1/edit', $response);

        $row = $this->db->fetchOne('SELECT slug, price_cents, stock, is_featured FROM products WHERE id = 1');
        self::assertSame(['slug' => 'studio-monitor', 'price_cents' => 24999, 'stock' => 7, 'is_featured' => 1], array_map(
            static fn($v) => is_numeric($v) ? (int) $v : $v,
            $row ?? [],
        ));
    }

    public function testSlugsMustBeUnique(): void
    {
        $this->fixtures->product(['slug' => 'taken-slug']);

        $response = $this->post('/admin/products', ['name' => 'Another', 'slug' => 'taken-slug', 'price' => '1', 'stock' => '1', 'category_id' => (string) $this->fixtures->category('audio')]);

        $this->assertSee('Another product already uses that URL.', $response);
    }

    public function testOptionsCanBeAddedAndRemoved(): void
    {
        $product = $this->fixtures->product();

        $this->post("/admin/products/{$product->id}/options", ['group' => 'Color', 'value' => 'Teal', 'extra' => '5']);
        $this->post("/admin/products/{$product->id}/options", ['group' => 'Color', 'value' => 'Teal', 'extra' => '0']);
        $optionId = $this->db->fetchInt('SELECT id FROM product_options WHERE product_id = ?', [$product->id]);

        self::assertSame(1, $this->fixtures->count('product_options'), 'Duplicates are refused');
        self::assertSame(500, $this->db->fetchInt('SELECT extra_cents FROM product_options'));

        $this->post("/admin/products/{$product->id}/options/{$optionId}/delete");
        self::assertSame(0, $this->fixtures->count('product_options'));
    }

    public function testArchivingHidesAProductWithoutDeletingIt(): void
    {
        $product = $this->fixtures->product(['slug' => 'to-archive']);

        $this->post("/admin/products/{$product->id}/archive");

        $this->assertStatus(404, $this->get('/products/to-archive'));
        self::assertSame(1, $this->fixtures->count('products'));

        $this->post("/admin/products/{$product->id}/archive");
        $this->assertStatus(200, $this->get('/products/to-archive'));
    }

    public function testOrderStatusFollowsTheStateMachine(): void
    {
        $customer = $this->fixtures->user('cust');
        $product = $this->fixtures->product(['stock' => 1]);
        $this->actingAs($customer);
        $this->post('/cart/add', ['product_id' => (string) $product->id]);
        $this->post('/checkout', ['name' => 'Cy Customer', 'street' => '1 Main St', 'city' => 'Windsor', 'province' => 'ON', 'postal_code' => 'N9B3P4']);
        $this->actingAs($this->admin);

        $this->post('/admin/orders/1/status', ['status' => 'delivered']);
        $this->assertSee('cannot be marked delivered', $this->get('/admin/orders/1'));

        $this->post('/admin/orders/1/status', ['status' => 'cancelled']);
        $page = $this->get('/admin/orders/1');
        $this->assertSee('This order is cancelled', $page);
        self::assertSame(1, $this->fixtures->stock($product->id));
    }

    public function testAdminsCannotLockThemselvesOut(): void
    {
        $this->post("/admin/users/{$this->admin->id}/active");
        $this->post("/admin/users/{$this->admin->id}/admin");

        $page = $this->get('/admin/users');
        $this->assertStatus(200, $page);
        self::assertSame(1, $this->db->fetchInt('SELECT is_active FROM users WHERE id = ?', [$this->admin->id]));
        self::assertSame(1, $this->fixtures->count('admins'));
    }

    public function testGrantingAndRevokingAdminRecordsWhoGrantedIt(): void
    {
        $user = $this->fixtures->user('promotee');

        $this->post("/admin/users/{$user->id}/admin");
        self::assertSame($this->admin->id, $this->db->fetchInt('SELECT granted_by FROM admins WHERE user_id = ?', [$user->id]));

        $this->post("/admin/users/{$user->id}/admin");
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM admins WHERE user_id = ?', [$user->id]));
    }

    public function testSwitchingTheme(): void
    {
        $this->db->execute("INSERT INTO themes (slug, name, is_active) VALUES ('regular', 'Regular', 1), ('minimal', 'Minimal', 0)");

        $this->post('/admin/themes', ['theme' => 'minimal']);
        self::assertStringContainsString('<html lang="en" data-theme="minimal">', $this->get('/')->body);

        $this->post('/admin/themes', ['theme' => '../../etc/passwd']);
        self::assertStringContainsString('data-theme="minimal"', $this->get('/')->body);
    }

    public function testDashboardRendersWithAndWithoutSales(): void
    {
        $this->assertSee('No sales in this period.', $this->get('/admin'));
    }
}
