<?php

namespace Database\Seeders;

use App\Models\Category;
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
        }

        if (! $user) {
            return;
        }

        foreach (['حقوق', 'پروژه', 'هدیه', 'فروش', 'سود بانکی', 'سایر درآمدها'] as $index => $name) {
            Category::firstOrCreate(['user_id' => $user->id, 'name' => $name, 'type' => 'income'], ['sort_order' => $index, 'is_active' => true]);
        }

        foreach (['خوراک', 'حمل‌ونقل', 'خرید', 'اجاره', 'قبض‌ها', 'درمان', 'آموزش', 'تفریح', 'سفر', 'اشتراک‌ها', 'سایر هزینه‌ها'] as $index => $name) {
            Category::firstOrCreate(['user_id' => $user->id, 'name' => $name, 'type' => 'expense'], ['sort_order' => $index, 'is_active' => true]);
        }
    }
}
