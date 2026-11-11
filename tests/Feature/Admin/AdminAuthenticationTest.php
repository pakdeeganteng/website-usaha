<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders_a_csrf_protected_form(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('SELAMAT DATANG')
            ->assertSee('Senang melihat Anda kembali.')
            ->assertSee('Silakan masuk untuk mengakses dashboard administrator.')
            ->assertSee('&copy; '.now()->year.' '.config('app.name').'. All Rights Reserved.', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('MASUK KE DASHBOARD')
            ->assertSee('bi-house-heart-fill', false)
            ->assertSee('data-password-toggle', false);
    }

    public function test_configured_administrator_can_log_in_and_reaches_dashboard(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->get(route('admin.login'));
        $previousSessionId = $this->app['session']->driver()->getId();

        $this->post(route('admin.login.store'), [
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotSame($previousSessionId, $this->app['session']->driver()->getId());
        $this->assertAuthenticatedAs($administrator);
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel administrasi');
    }

    public function test_login_rejects_an_incorrect_password_with_a_generic_error(): void
    {
        User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->followingRedirects()
            ->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => 'admin@usahaku.com',
                'password' => 'wrong-password',
            ])
            ->assertOk()
            ->assertSee('SELAMAT DATANG')
            ->assertSee('Email atau kata sandi tidak sesuai.')
            ->assertSee('data-admin-login-form', false);

        $this->assertGuest();
    }

    public function test_login_rejects_an_unregistered_email_with_the_same_error(): void
    {
        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => 'unknown@example.com',
                'password' => 'Admin@2026#Secure',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);

        $this->assertGuest();
    }

    public function test_another_valid_user_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_when_opening_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_administrator_can_open_the_password_change_page(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.password.edit'))
            ->assertOk()
            ->assertSee('Ubah kata sandi')
            ->assertSee('Minimal 12 karakter');
    }

    public function test_logout_invalidates_access_to_the_admin_dashboard(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
        ]);

        $this->actingAs($administrator)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_administrator_can_change_password_and_is_logged_out(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->actingAs($administrator)
            ->put(route('admin.password.update'), [
                'current_password' => 'Admin@2026#Secure',
                'password' => 'NewAdmin@2026#Secure',
                'password_confirmation' => 'NewAdmin@2026#Secure',
            ])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('NewAdmin@2026#Secure', $administrator->refresh()->password));
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->actingAs($administrator)
            ->put(route('admin.password.update'), [
                'current_password' => 'Admin@2026#Secure',
                'password' => 'NewAdmin@2026#Secure',
                'password_confirmation' => 'DifferentAdmin@2026#Secure',
            ])
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($administrator);
        $this->assertTrue(Hash::check('Admin@2026#Secure', $administrator->refresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->actingAs($administrator)
            ->put(route('admin.password.update'), [
                'current_password' => 'incorrect-current-password',
                'password' => 'NewAdmin@2026#Secure',
                'password_confirmation' => 'NewAdmin@2026#Secure',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertAuthenticatedAs($administrator);
        $this->assertTrue(Hash::check('Admin@2026#Secure', $administrator->refresh()->password));
    }

    public function test_password_change_rejects_a_weak_password(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'Admin@2026#Secure',
        ]);

        $this->actingAs($administrator)
            ->put(route('admin.password.update'), [
                'current_password' => 'Admin@2026#Secure',
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ])
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($administrator);
        $this->assertTrue(Hash::check('Admin@2026#Secure', $administrator->refresh()->password));
    }
}
