<?php

namespace Database\Seeders;

use App\Enums\Users\UserRole;
use App\Models\User;
use App\Support\InitialUserPassword;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'jane@example.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email_verified_at' => now(),
                'password' => InitialUserPassword::resolve(
                    app()->environment(),
                    config('pixelforge.initial_user_password'),
                ),
            ],
        );

        $user->forceFill([
            'role' => UserRole::BasicUser,
        ])->save();
    }
}
