<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_all_customer_pages(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('menu.index'))->assertRedirect(route('login'));
        $this->get(route('about'))->assertRedirect(route('login'));
        $this->get(route('contact'))->assertRedirect(route('login'));
    }

    public function test_customer_can_view_login_and_registration_forms(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('name="_token"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Buat akun pelanggan')
            ->assertSee('name="_token"', false);
    }

    public function test_customer_registration_creates_hashed_user_and_authenticates(): void
    {
        $this->get(route('register'));
        $previousSessionId = $this->app['session']->driver()->getId();

        $this->post(route('register.store'), [
            'name' => 'Pelanggan Baru',
            'email' => '  CUSTOMER@EXAMPLE.COM ',
            'password' => 'Customer@2026Secure',
            'password_confirmation' => 'Customer@2026Secure',
        ])
            ->assertRedirect(route('home'));

        $customer = User::query()->where('email', 'customer@example.com')->sole();

        $this->assertNotSame('Customer@2026Secure', $customer->password);
        $this->assertTrue(Hash::check('Customer@2026Secure', $customer->password));
        $this->assertAuthenticatedAs($customer);
        $this->assertNotSame($previousSessionId, $this->app['session']->driver()->getId());
    }

    public function test_customer_registration_rejects_an_existing_email(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Pelanggan Baru',
                'email' => 'CUSTOMER@EXAMPLE.COM',
                'password' => 'Customer@2026Secure',
                'password_confirmation' => 'Customer@2026Secure',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_public_registration_cannot_claim_the_administrator_email(): void
    {
        config(['admin.email' => 'admin@usahaku.com']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Pendaftar',
                'email' => 'admin@usahaku.com',
                'password' => 'Customer@2026Secure',
                'password_confirmation' => 'Customer@2026Secure',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_customer_registration_requires_a_strong_confirmed_password(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Pelanggan Baru',
                'email' => 'customer@example.com',
                'password' => 'weak',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_customer_can_log_in_with_case_insensitive_email(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'Customer@2026Secure',
        ]);

        $this->get(route('login'));
        $previousSessionId = $this->app['session']->driver()->getId();

        $this->post(route('login.store'), [
            'email' => 'CUSTOMER@EXAMPLE.COM',
            'password' => 'Customer@2026Secure',
        ])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($customer);
        $this->assertNotSame($previousSessionId, $this->app['session']->driver()->getId());
    }

    public function test_login_returns_customer_to_the_requested_page(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'Customer@2026Secure',
        ]);

        $this->get(route('menu.index'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'Customer@2026Secure',
        ])
            ->assertRedirect(route('menu.index'));
    }

    public function test_login_rejects_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'Customer@2026Secure',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'customer@example.com',
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);

        $this->assertGuest();
    }

    public function test_customer_logout_requires_authentication_and_clears_session(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('home'))->assertRedirect(route('login'));
    }
}
