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

    public function testSavingTheProductFormDoesNotUndoSalesMadeWhileItWasOpen(): void
    {
        $product = $this->fixtures->product(['name' => 'Kindle Test', 'stock' => 40]);
        $form = $this->productForm($product->id);
        self::assertSame('40', $form['stock_original']);

        // A customer buys 3 while the admin is fixing a typo.
        $this->db->execute('UPDATE products SET stock = 37 WHERE id = ?', [$product->id]);
        $this->assertRedirect("/admin/products/{$product->id}/edit", $this->post("/admin/products/{$product->id}", ['description' => 'Fixed typo'] + $form));

        self::assertSame(37, $this->fixtures->stock($product->id));
        self::assertSame('Fixed typo', $this->db->fetchValue('SELECT description FROM products WHERE id = ?', [$product->id]));
    }

    public function testAStockEditThatRacedASaleIsShownBackInsteadOfSaved(): void
    {
        $product = $this->fixtures->product(['name' => 'Kindle Test', 'stock' => 40]);
        $form = $this->productForm($product->id);

        $this->db->execute('UPDATE products SET stock = 37 WHERE id = ?', [$product->id]);
        $response = $this->post("/admin/products/{$product->id}", ['name' => 'Kindle Renamed', 'stock' => '50'] + $form);

        $this->assertStatus(409, $response);
        $this->assertSee('Stock changed to 37 while you were editing', $response);
        self::assertSame(37, $this->fixtures->stock($product->id));
        self::assertSame('Kindle Test', $this->db->fetchValue('SELECT name FROM products WHERE id = ?', [$product->id]));

        // Saving the re-shown form applies the admin's figure.
        self::assertSame(1, preg_match('/name="stock_original" value="(\d+)"/', $response->body, $m));
        $this->post("/admin/products/{$product->id}", ['name' => 'Kindle Renamed', 'stock' => '50', 'stock_original' => $m[1]] + $form);
        self::assertSame(50, $this->fixtures->stock($product->id));
    }

    public function testOptionGroupNamesCannotBreakTheProductForm(): void
    {
        // "options[Size [in]]" would reach PHP as the key "Size [in".
        $product = $this->fixtures->product();

        $this->post("/admin/products/{$product->id}/options", ['group' => 'Size [in]', 'value' => 'Big', 'extra' => '0']);

        $this->assertSee('Option names cannot contain square brackets.', $this->get("/admin/products/{$product->id}/edit"));
        self::assertSame(0, $this->fixtures->count('product_options'));
    }

    public function testOrderPagesPastTheEndRedirectInsteadOfFailing(): void
    {
        $this->assertRedirect('/admin/orders', $this->get('/admin/orders?page=99999999999999999999'));
        $this->assertRedirect('/admin/orders?status=shipped', $this->get('/admin/orders?status=shipped&page=4'));
    }

    public function testArchivingHidesAProductWithoutDeletingIt(): void
    {
        $product = $this->fixtures->product(['slug' => 'to-archive']);

        $this->post("/admin/products/{$product->id}/archive", ['archived' => '1']);

        $this->assertStatus(404, $this->get('/products/to-archive'));
        self::assertSame(1, $this->fixtures->count('products'));

        // A second submission, e.g. from a stale tab, does not undo it.
        $this->post("/admin/products/{$product->id}/archive", ['archived' => '1']);
        $this->assertSee('is already archived', $this->get('/admin/products'));
        $this->assertStatus(404, $this->get('/products/to-archive'));

        $this->post("/admin/products/{$product->id}/archive", ['archived' => '0']);
        $this->assertStatus(200, $this->get('/products/to-archive'));
        $this->assertStatus(400, $this->post("/admin/products/{$product->id}/archive"));
    }

    public function testOrderStatusFollowsTheStateMachine(): void
    {
        $customer = $this->fixtures->user('cust');
        $product = $this->fixtures->product(['stock' => 1]);
        $this->actingAs($customer);
        $this->post('/cart/add', ['product_id' => (string) $product->id]);
        $this->checkout(['name' => 'Cy Customer', 'street' => '1 Main St', 'city' => 'Windsor', 'province' => 'ON', 'postal_code' => 'N9B3P4']);
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
        $this->post("/admin/users/{$this->admin->id}/active", ['active' => '0']);
        $this->post("/admin/users/{$this->admin->id}/admin", ['admin' => '0']);

        $page = $this->get('/admin/users');
        $this->assertStatus(200, $page);
        self::assertSame(1, $this->db->fetchInt('SELECT is_active FROM users WHERE id = ?', [$this->admin->id]));
        self::assertSame(1, $this->fixtures->count('admins'));
    }

    public function testGrantingAndRevokingAdminRecordsWhoGrantedIt(): void
    {
        $user = $this->fixtures->user('promotee');

        $this->post("/admin/users/{$user->id}/admin", ['admin' => '1']);
        self::assertSame($this->admin->id, $this->db->fetchInt('SELECT granted_by FROM admins WHERE user_id = ?', [$user->id]));

        $this->post("/admin/users/{$user->id}/admin", ['admin' => '0']);
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM admins WHERE user_id = ?', [$user->id]));

        // Repeating a revoke from a stale page does not re-grant.
        $this->post("/admin/users/{$user->id}/admin", ['admin' => '0']);
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM admins WHERE user_id = ?', [$user->id]));
    }

    public function testDisablingTwiceLeavesTheAccountDisabled(): void
    {
        $user = $this->fixtures->user('troll');

        $this->post("/admin/users/{$user->id}/active", ['active' => '0']);
        $this->post("/admin/users/{$user->id}/active", ['active' => '0']);

        self::assertSame(0, $this->db->fetchInt('SELECT is_active FROM users WHERE id = ?', [$user->id]));
        $this->assertSee('troll is already disabled.', $this->get('/admin/users'));
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

    /**
     * The edit form's fields as the browser would submit them.
     *
     * @return array<string, string>
     */
    private function productForm(int $id): array
    {
        $html = $this->get("/admin/products/{$id}/edit")->body;
        $fields = [];
        preg_match_all('/<input[^>]*\bname="([a-z_]+)"[^>]*\bvalue="([^"]*)"[^>]*>/', $html, $inputs, PREG_SET_ORDER);
        foreach ($inputs as [$tag, $name, $value]) {
            if (!str_contains($tag, 'type="checkbox"') || str_contains($tag, ' checked')) {
                $fields[$name] = html_entity_decode($value);
            }
        }
        preg_match_all('/<select[^>]*\bname="([a-z_]+)".*?<option value="([^"]*)" selected/s', $html, $selects, PREG_SET_ORDER);
        foreach ($selects as [, $name, $value]) {
            $fields[$name] = html_entity_decode($value);
        }
        preg_match('/<textarea[^>]*name="description"[^>]*>(.*?)<\/textarea>/s', $html, $description);
        $fields['description'] = html_entity_decode($description[1] ?? '');
        unset($fields['_token'], $fields['value'], $fields['group'], $fields['extra'], $fields['q']);

        return $fields;
    }
}
