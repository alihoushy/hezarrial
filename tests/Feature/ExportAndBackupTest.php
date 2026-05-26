<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Backup\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportAndBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_prefixes_formula_values(): void
    {
        $user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $user->id, 'name' => '=SUM(A1:A2)', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $category = Category::create(['user_id' => $user->id, 'name' => 'حقوق', 'type' => 'income']);
        Transaction::create(['user_id' => $user->id, 'account_id' => $account->id, 'category_id' => $category->id, 'type' => 'income', 'amount' => 1000, 'transaction_date' => '2026-05-26']);

        $this->actingAs($user)->get(route('exports.transactions.csv'))
            ->assertOk()
            ->assertSee("'=SUM(A1:A2)", false);
    }

    public function test_backup_is_stored_on_private_disk(): void
    {
        Storage::fake('local');
        $user = User::create(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);

        $backup = app(BackupService::class)->create($user);

        Storage::disk('local')->assertExists($backup->file_path);
        $this->assertStringStartsWith('backups/'.$user->id, $backup->file_path);
    }
}
