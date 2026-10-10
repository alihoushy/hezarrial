<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\App;

class CreateDefaultCategories
{
    /** Seeds the starter categories in the user's language. Safe to run twice. */
    public function handle(User $user, ?string $locale = null): void
    {
        $previous = App::getLocale();
        App::setLocale($locale ?? $previous);

        try {
            $names = [
                'income' => [__('حقوق'), __('پروژه'), __('هدیه'), __('فروش'), __('سود بانکی'), __('سایر درآمدها')],
                'expense' => [__('خوراک'), __('حمل‌ونقل'), __('خرید'), __('اجاره'), __('قبض‌ها'), __('درمان'), __('آموزش'), __('تفریح'), __('سفر'), __('اشتراک‌ها'), __('سایر هزینه‌ها')],
            ];
        } finally {
            App::setLocale($previous);
        }

        foreach ($names as $type => $list) {
            foreach ($list as $index => $name) {
                Category::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $name, 'type' => $type],
                    ['sort_order' => $index, 'is_active' => true],
                );
            }
        }
    }
}
