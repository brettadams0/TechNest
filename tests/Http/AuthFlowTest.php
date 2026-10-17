<?php

declare(strict_types=1);

namespace TechNest\Tests\Http;

use TechNest\Security\LoginThrottle;
use TechNest\Tests\Support\Fixtures;
use TechNest\Tests\Support\HttpTestCase;

final class AuthFlowTest extends HttpTestCase
{
    public function testRegistrationValidatesAndSignsTheUserIn(): void
    {
        $this->fixtures->user('taken');

        $response = $this->post('/register', ['username' => 'TAKEN', 'email' => 'nope', 'password' => 'short', 'password_confirmation' => 'other']);
        $this->assertStatus(422, $response);
        $this->assertSee('Enter a valid email address.', $response);
        $this->assertSee('Password must be at least 10 characters.', $response);

        $response = $this->post('/register', ['username' => 'TAKEN', 'email' => 'new@example.com', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1']);
        $this->assertSee('That username is taken.', $response);

        $response = $this->post('/register', ['username' => 'newbie', 'email' => 'New@Example.com', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1']);
        $this->assertRedirect('/account', $response);
        $this->assertSee('new@example.com', $this->get('/account'));
    }

    public function testSignedInUsersAreSentAwayFromGuestPages(): void
    {
        $this->actingAs($this->fixtures->user());

        $this->assertRedirect('/account', $this->get('/login'));
        $this->assertRedirect('/account', $this->get('/register'));
    }

    public function testLoginNextParameterCannotRedirectOffSite(): void
    {
        $this->fixtures->user('una');
        $this->get('/login?next=https://evil.example/phish');

        $this->assertRedirect('/account', $this->post('/login', ['login' => 'una', 'password' => Fixtures::PASSWORD]));
    }

    public function testLoginNextParameterWorksForLocalPaths(): void
    {
        $this->fixtures->user('vic');
        $this->get('/login?next=/catalog?category=audio');

        $this->assertRedirect('/catalog?category=audio', $this->post('/login', ['login' => 'vic', 'password' => Fixtures::PASSWORD]));
    }

    public function testRepeatedFailuresAreThrottledWith429(): void
    {
        $this->fixtures->user('wes');
        for ($i = 0; $i < 5; ++$i) {
            $this->assertStatus(422, $this->post('/login', ['login' => 'wes', 'password' => 'not-the-password']));
        }

        $response = $this->post('/login', ['login' => 'wes', 'password' => Fixtures::PASSWORD]);

        $this->assertStatus(429, $response);
        $this->assertSee('Too many failed attempts', $response);
    }

    public function testLogoutEndsTheSession(): void
    {
        $this->actingAs($this->fixtures->user());

        $this->assertRedirect('/', $this->post('/logout'));
        $this->assertRedirect('/login', $this->get('/account'));
    }

    public function testChangingPasswordRequiresTheCurrentOne(): void
    {
        $this->actingAs($this->fixtures->user('xena'));

        $response = $this->post('/account/password', ['current_password' => 'wrong', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass']);
        $this->assertStatus(422, $response);
        $this->assertSee('Your current password is not correct.', $response);

        $this->assertRedirect('/account', $this->post('/account/password', ['current_password' => Fixtures::PASSWORD, 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass']));
        // The session that changed the password stays signed in.
        $this->assertStatus(200, $this->get('/account'));
        $this->post('/logout');
        $this->assertRedirect('/account', $this->post('/login', ['login' => 'xena', 'password' => 'brand-new-pass']));
    }

    public function testGuessingTheCurrentPasswordIsThrottled(): void
    {
        $this->actingAs($this->fixtures->user('zed'));
        $guess = ['current_password' => 'guess', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'];
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            $this->assertStatus(422, $this->post('/account/password', $guess));
        }

        $response = $this->post('/account/password', ['current_password' => Fixtures::PASSWORD] + $guess);

        $this->assertStatus(429, $response);
        $this->assertSee('Too many failed attempts.', $response);
    }

    public function testRegistrationOnlyRevealsATakenEmailWhenTheUsernameIsFree(): void
    {
        $this->fixtures->user('owner');
        $form = ['password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1'];

        $response = $this->post('/register', ['username' => 'owner', 'email' => 'owner@example.com'] + $form);
        $this->assertSee('That username is taken.', $response);
        $this->assertDontSee('An account already uses that email.', $response);

        $response = $this->post('/register', ['username' => 'fresh', 'email' => 'owner@example.com'] + $form);
        $this->assertSee('An account already uses that email.', $response);
    }

    public function testCheckingEmailsThroughRegistrationIsThrottled(): void
    {
        $this->fixtures->user('owner');
        $form = ['username' => 'fresh', 'email' => 'owner@example.com', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1'];
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; ++$i) {
            $this->assertStatus(422, $this->post('/register', $form));
        }

        $response = $this->post('/register', ['email' => 'someone-new@example.com'] + $form);

        $this->assertStatus(429, $response);
        $this->assertSee('Too many failed attempts.', $response);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM users'));
    }

    public function testADisabledUserIsSignedOutOnTheirNextRequest(): void
    {
        $user = $this->fixtures->user('yuri');
        $this->actingAs($user);
        $this->assertStatus(200, $this->get('/account'));

        $this->db->execute('UPDATE users SET is_active = 0 WHERE id = ?', [$user->id]);

        $this->assertRedirect('/login', $this->get('/account'));
    }
}
