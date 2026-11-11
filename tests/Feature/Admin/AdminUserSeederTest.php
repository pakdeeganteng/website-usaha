<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_a_unique_user_with_a_hashed_password(): void
    {
        config([
            'admin.email' => 'admin@usahaku.com',
            'admin.initial_password' => 'Admin@2026#Secure',
        ]);

        $this->seed(AdminUserSeeder::class);

        $administrator = User::query()->where('email', 'admin@usahaku.com')->sole();

        $this->assertSame('Administrator DapurHarian', $administrator->name);
        $this->assertNotSame('Admin@2026#Secure', $administrator->password);
        $this->assertTrue(Hash::check('Admin@2026#Secure', $administrator->password));
    }

    public function test_admin_seeder_does_not_duplicate_or_reset_an_existing_account(): void
    {
        config([
            'admin.email' => 'admin@usahaku.com',
            'admin.initial_password' => 'Admin@2026#Secure',
        ]);
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
            'password' => 'ExistingAdmin@2026#Secure',
        ]);

        $this->seed(AdminUserSeeder::class);

        $administrator->refresh();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('ExistingAdmin@2026#Secure', $administrator->password));
    }
}
