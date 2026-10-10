<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\IncomingSms;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmsIngestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $bank;
    private string $token = 'test-ingest-token-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms_ingest.token' => $this->token,
            'services.sms_ingest.user_id' => null,
        ]);

        $this->user = User::forceCreate(['email_verified_at' => now(), 
            'name' => 'مالک',
            'email' => 'owner@example.com',
            'password' => Hash::make('password-password'),
        ]);

        config(['services.sms_ingest.user_id' => $this->user->id]);

        $this->bank = Account::create([
            'user_id' => $this->user->id,
            'name' => 'بانک ملت',
            'type' => 'bank',
            'bank_name' => 'mellat',
            'card_last_four' => '1234',
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
        ]);
    }

    public function test_rejects_request_without_token(): void
    {
        $this->postJson('/api/sms/ingest', ['message' => 'واریز مبلغ 500,000 ریال'])
            ->assertStatus(401);
    }

    public function test_rejects_request_with_wrong_token(): void
    {
        $this->postJson('/api/sms/ingest', ['message' => 'واریز مبلغ 500,000 ریال'], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertStatus(401);
    }

    public function test_rejects_missing_message(): void
    {
        $this->postJson('/api/sms/ingest', [], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_rejects_short_message(): void
    {
        $this->postJson('/api/sms/ingest', ['message' => 'سلام'], $this->headers())
            ->assertStatus(422);
    }

    public function test_successful_ingest_with_bearer_token(): void
    {
        $response = $this->postJson('/api/sms/ingest', [
            'message' => 'واریز مبلغ 500,000 ریال به حساب *1234 مانده: 1,500,000',
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertStatus(201)
            ->assertJsonStructure(['status', 'id', 'parsed']);

        $response->assertJson(['status' => 'pending']);

        $this->assertDatabaseHas('incoming_sms', [
            'user_id' => $this->user->id,
            'status' => 'pending',
            'amount' => 500000,
            'type' => 'income',
        ]);
    }

    public function test_successful_ingest_with_header_token(): void
    {
        $this->postJson('/api/sms/ingest', [
            'message' => 'برداشت مبلغ 250,000 ریال از کارت *1234',
        ], ['X-Ingest-Token' => $this->token])
            ->assertStatus(201)
            ->assertJson(['status' => 'pending']);
    }

    public function test_auto_matches_account_by_card_last_four(): void
    {
        $this->postJson('/api/sms/ingest', [
            'message' => 'واریز مبلغ 500,000 ریال به کارت *1234',
        ], $this->headers())->assertStatus(201);

        $sms = IncomingSms::latest()->first();
        $this->assertSame($this->bank->id, $sms->account_id);
    }

    public function test_duplicate_message_returns_200_with_duplicate_status(): void
    {
        $message = 'واریز مبلغ 500,000 ریال به حساب *1234 پیگیری: 123456';

        $first = $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers());
        $first->assertStatus(201);

        $second = $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers());
        $second->assertStatus(200);
        $second->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, IncomingSms::count());
    }

    public function test_same_message_from_two_users_is_not_a_duplicate(): void
    {
        $message = 'واریز مبلغ 500,000 ریال به حساب *1234 پیگیری: 123456';
        $other = User::forceCreate(['email_verified_at' => now(), 'name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);

        $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers())->assertStatus(201);
        config(['services.sms_ingest.user_id' => $other->id]);
        $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers())->assertStatus(201);

        $this->assertSame(1, IncomingSms::where('user_id', $this->user->id)->count());
        $this->assertSame(1, IncomingSms::where('user_id', $other->id)->count());
    }

    public function test_unparseable_message_stored_as_unparsed(): void
    {
        $this->postJson('/api/sms/ingest', [
            'message' => 'سرویس اینترنت بانک شما فعال شد. برای اطلاعات بیشتر تماس بگیرید.',
        ], $this->headers())->assertStatus(201);

        $sms = IncomingSms::latest()->first();
        $this->assertSame('unparsed', $sms->status);
        $this->assertNull($sms->amount);
    }

    public function test_stores_raw_message(): void
    {
        $message = 'واریز مبلغ ۵۰۰,۰۰۰ ریال به حساب';

        $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers())
            ->assertStatus(201);

        $sms = IncomingSms::latest()->first();
        $this->assertSame($message, $sms->raw_message);
    }

    public function test_the_old_shared_token_stops_working_once_it_is_removed_from_the_env(): void
    {
        config(['services.sms_ingest.token' => null]);

        $this->postJson('/api/sms/ingest', [
            'message' => 'واریز مبلغ 500,000 ریال',
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertStatus(401);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_raw_message_is_encrypted_in_the_database(): void
    {
        $message = 'واریز مبلغ 500,000 ریال به حساب *1234 مانده: 1,500,000';

        $this->postJson('/api/sms/ingest', ['message' => $message], $this->headers())->assertStatus(201);

        $stored = \DB::table('incoming_sms')->value('raw_message');
        $this->assertStringNotContainsString('500,000', $stored);
        $this->assertSame($message, IncomingSms::first()->raw_message);
    }

    public function test_prune_clears_only_old_handled_messages(): void
    {
        $make = fn (string $status, int $daysOld) => tap(IncomingSms::create([
            'user_id' => $this->user->id,
            'status' => $status,
            'raw_message' => 'text '.$status.$daysOld,
            'idempotency_key' => hash('sha256', $status.$daysOld),
        ]), fn ($sms) => $sms->forceFill(['created_at' => now()->subDays($daysOld)])->save());

        $oldConfirmed = $make('confirmed', 40);
        $newConfirmed = $make('confirmed', 5);
        $oldPending = $make('pending', 40);

        $this->artisan('app:prune-sms-text')->assertSuccessful();

        $this->assertNull($oldConfirmed->fresh()->raw_message);
        $this->assertNotNull($newConfirmed->fresh()->raw_message);
        $this->assertNotNull($oldPending->fresh()->raw_message);
    }

    public function test_a_personal_token_ingests_for_its_own_user(): void
    {
        config(['services.sms_ingest.token' => null, 'services.sms_ingest.user_id' => null]);
        $other = User::forceCreate(['email_verified_at' => now(), 'name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);
        $mine = $this->user->createToken('آیفون', ['sms:ingest'])->plainTextToken;
        $theirs = $other->createToken('x', ['sms:ingest'])->plainTextToken;
        $message = 'واریز مبلغ 500,000 ریال به حساب *1234 پیگیری: 123456';

        $this->postJson('/api/sms/ingest', ['message' => $message], ['Authorization' => "Bearer {$mine}"])->assertStatus(201);
        $this->postJson('/api/sms/ingest', ['message' => $message], ['X-Ingest-Token' => $theirs])->assertStatus(201);

        $this->assertSame(1, IncomingSms::where('user_id', $this->user->id)->count());
        $this->assertSame(1, IncomingSms::where('user_id', $other->id)->count());
        $this->assertNotNull($this->user->tokens()->first()->last_used_at);
    }

    public function test_a_token_without_the_sms_ability_or_of_an_unverified_user_is_refused(): void
    {
        config(['services.sms_ingest.token' => null]);
        $wrongAbility = $this->user->createToken('x', ['something-else'])->plainTextToken;
        $this->postJson('/api/sms/ingest', ['message' => 'واریز مبلغ 500,000 ریال'], ['Authorization' => "Bearer {$wrongAbility}"])->assertStatus(401);

        $this->user->forceFill(['email_verified_at' => null])->save();
        $good = $this->user->createToken('y', ['sms:ingest'])->plainTextToken;
        $this->postJson('/api/sms/ingest', ['message' => 'واریز مبلغ 500,000 ریال'], ['Authorization' => "Bearer {$good}"])->assertStatus(401);
    }

    public function test_tokens_are_managed_from_settings_and_shown_only_once(): void
    {
        $this->actingAs($this->user)->post(route('sms-tokens.store'), ['name' => 'آیفون من'])->assertRedirect()->assertSessionHas('status');

        $row = $this->user->tokens()->first();
        $this->assertSame('آیفون من', $row->name);
        $this->assertSame(['sms:ingest'], $row->abilities);

        $this->actingAs($this->user)->get(route('sms-tokens.index'))->assertInertia(fn ($page) => $page
            ->component('settings/sms')
            ->has('tokens', 1)
            ->missing('tokens.0.token'));

        $this->actingAs($this->user)->delete(route('sms-tokens.destroy', $row->id))->assertRedirect();
        $this->assertSame(0, $this->user->tokens()->count());
    }

    public function test_one_user_cannot_delete_anothers_token_and_the_number_of_tokens_is_capped(): void
    {
        $other = User::forceCreate(['email_verified_at' => now(), 'name' => 'دیگری', 'email' => 'other@example.com', 'password' => Hash::make('password-password')]);
        $theirs = $other->createToken('x', ['sms:ingest']);

        $this->actingAs($this->user)->delete(route('sms-tokens.destroy', $theirs->accessToken->id));
        $this->assertSame(1, $other->tokens()->count());

        foreach (range(1, 5) as $i) {
            $this->user->createToken("t{$i}", ['sms:ingest']);
        }
        $this->actingAs($this->user)->post(route('sms-tokens.store'), ['name' => 'یکی بیشتر'])->assertSessionHasErrors('name');
        $this->assertSame(5, $this->user->tokens()->count());
    }
}
