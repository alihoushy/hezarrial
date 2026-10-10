<?php

namespace Database\Seeders;

use App\Actions\CreateDefaultCategories;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user && app()->environment(['local', 'testing'])) {
            $user = User::create([
                'name' => 'کاربر هزار ریال',
                'email' => 'owner@hezarrial.local',
                'password' => Hash::make('password-password'),
                'settings' => ['currency_display' => 'both', 'persian_digits' => true, 'theme' => 'system'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (! $user) {
            return;
        }

        app(CreateDefaultCategories::class)->handle($user);
    }
}
