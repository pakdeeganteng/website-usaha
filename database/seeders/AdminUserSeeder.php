<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('admin.email');
        $password = config('admin.initial_password');

        if ($email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException(
                'Set ADMIN_EMAIL and ADMIN_INITIAL_PASSWORD before running AdminUserSeeder.',
            );
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator DapurHarian',
                'password' => Hash::make($password),
            ],
        );
    }
}
