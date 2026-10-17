<?php

declare(strict_types=1);

namespace TechNest\Tests\Http;

use TechNest\Catalog\Product;
use TechNest\Tests\Support\Fixtures;
use TechNest\Tests\Support\HttpTestCase;

/** The whole journey a customer takes, through the real HTTP stack. */
final class PurchaseFlowTest extends HttpTestCase
{
    private Product $phone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = $this->fixtures->phone(stock: 4);
    }

    public function testBrowseAddSignInCheckOutTrackAndCancel(): void
    {
        $this->fixtures->user('pat');

        // Add a configured phone to the cart as a guest.
        $response = $this->post('/cart/add', [
            'product_id' => (string) $this->phone->id,
            'options' => ['Color' => (string) $this->phone->options[1]->id, 'Storage' => (string) $this->phone->options[3]->id],
            'quantity' => '2',
        ]);
        $this->assertRedirect('/cart', $response);
        $cart = $this->get('/cart');
        $this->assertSee('Color: White · Storage: 256GB', $cart);
        $this->assertSee('$2,198.00', $cart);

        // Checkout needs an account: sign-in sends us back to it.
        $this->assertRedirect('/login', $this->get('/checkout'));
        $this->assertRedirect('/checkout', $this->post('/login', ['login' => 'pat', 'password' => Fixtures::PASSWORD]));
        $this->assertStatus(200, $this->get('/checkout'));

        // A bad postal code is caught and the form keeps what was typed.
        $invalid = $this->post('/checkout', $this->address(['postal_code' => '12345']));
        $this->assertStatus(422, $invalid);
        $this->assertSee('Enter a Canadian postal code', $invalid);
        $this->assertSee('401 Sunset Ave', $invalid);

        $placed = $this->post('/checkout', $this->address());
        $location = (string) $placed->header('Location');
        self::assertMatchesRegularExpression('#^/account/orders/\d+$#', $location);
        self::assertSame(2, $this->fixtures->stock($this->phone->id));

        $order = $this->get($location);
        $this->assertSee('Thank you', $order);
        $this->assertSee('Pixel Test', $order);
        $this->assertSee('Cancel order', $order);
        $this->assertSee('TN-00001', $this->get('/account'));

        $this->assertRedirect($location, $this->post($location . '/cancel'));
        $this->assertSee('Cancelled', $this->get($location));
        self::assertSame(4, $this->fixtures->stock($this->phone->id), 'Cancelling returned the stock');
    }

    public function testInvalidOptionsAreRejectedAtAddToCart(): void
    {
        $response = $this->post('/cart/add', [
            'product_id' => (string) $this->phone->id,
            'options' => ['Color' => (string) $this->phone->options[0]->id],
            'quantity' => '1',
        ]);

        $this->assertRedirect('/products/pixel-test', $response);
        $this->assertSee('Choose a Storage for Pixel Test.', $this->get('/products/pixel-test'));
        self::assertNull($this->session->get('cart'));
    }

    public function testCustomersCannotSeeEachOthersOrders(): void
    {
        $owner = $this->fixtures->user('owner');
        $this->actingAs($owner);
        $this->post('/cart/add', ['product_id' => (string) $this->phone->id, 'options' => ['Color' => (string) $this->phone->options[0]->id, 'Storage' => (string) $this->phone->options[2]->id]]);
        $location = (string) $this->post('/checkout', $this->address())->header('Location');

        $this->actingAs($this->fixtures->user('snoop'));

        $this->assertStatus(404, $this->get($location));
        $this->post($location . '/cancel');
        self::assertSame('pending', $this->db->fetchValue('SELECT status FROM orders'));
    }

    public function testAnEmptyCartCannotReachCheckout(): void
    {
        $this->actingAs($this->fixtures->user());

        $this->assertRedirect('/cart', $this->get('/checkout'));
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function address(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pat Customer',
            'street' => '401 Sunset Ave',
            'city' => 'Windsor',
            'province' => 'ON',
            'postal_code' => 'n9b3p4',
        ], $overrides);
    }
}
