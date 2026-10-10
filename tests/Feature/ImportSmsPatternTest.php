<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ImportSmsPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_sms_pattern_and_preview_sms_with_it(): void
    {
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);

        $this->actingAs($user)->post(route('imports.sms-patterns.store'), [
            'name' => 'بانک نمونه',
            'bank_name' => 'نمونه',
            'pattern' => 'مبلغ (?<amount>[\d,]+) ریال (?<type>واریز|برداشت).*مانده (?<balance>[\d,]+).*کارت (?<card>\d{4})',
            'debit_keywords' => 'برداشت',
            'credit_keywords' => 'واریز',
        ])->assertRedirect();

        $this->assertDatabaseHas('sms_patterns', ['user_id' => $user->id, 'name' => 'بانک نمونه']);

        $patternId = $user->smsPatterns()->first()->id;

        $this->actingAs($user)->post(route('imports.sms-preview'), [
            'sms_pattern_id' => $patternId,
            'sms_text' => 'مبلغ 1,250,000 ریال واریز شد. مانده 5,000,000 کارت 9876',
        ])->assertInertia(fn (AssertableInertia $page) => $page
            ->component('imports/sms-preview')
            ->where('parsed.amount', 1250000)
            ->where('parsed.type', 'income')
            ->where('parsed.card_last_four', '9876'));
    }
}
