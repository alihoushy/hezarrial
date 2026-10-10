<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Backup\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportAndBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_prefixes_formula_values(): void
    {
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $user->id, 'name' => '=SUM(A1:A2)', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $category = Category::create(['user_id' => $user->id, 'name' => 'حقوق', 'type' => 'income']);
        Transaction::create(['user_id' => $user->id, 'account_id' => $account->id, 'category_id' => $category->id, 'type' => 'income', 'amount' => 1000, 'transaction_date' => '2026-05-26']);

        $response = $this->actingAs($user)->get(route('exports.transactions.csv'));

        $response->assertOk();
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString("'=SUM(A1:A2)", $content);
    }

    public function test_backup_is_stored_on_private_disk(): void
    {
        Storage::fake('local');
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);

        $backup = app(BackupService::class)->create($user);

        Storage::disk('local')->assertExists($backup->file_path);
        $this->assertStringStartsWith('backups/'.$user->id, $backup->file_path);
    }

    public function test_backup_restore_replaces_current_financial_data(): void
    {
        Storage::fake('local');
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $user->id, 'name' => 'بانک اصلی', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $category = Category::create(['user_id' => $user->id, 'name' => 'حقوق', 'type' => 'income']);
        Transaction::create(['user_id' => $user->id, 'account_id' => $account->id, 'category_id' => $category->id, 'type' => 'income', 'amount' => 1000, 'transaction_date' => '2026-05-26']);
        $backup = app(BackupService::class)->create($user);
        $content = Storage::disk('local')->get($backup->file_path);

        $account->update(['name' => 'حساب اشتباه']);

        $this->actingAs($user)->post(route('backups.restore'), [
            'password' => 'password-password',
            'backup' => UploadedFile::fake()->createWithContent('backup.json', $content),
        ])->assertRedirect();

        $this->assertDatabaseHas('accounts', ['user_id' => $user->id, 'name' => 'بانک اصلی']);
        $this->assertDatabaseMissing('accounts', ['user_id' => $user->id, 'name' => 'حساب اشتباه']);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'amount' => 1000]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'backup.restored']);
    }

    public function test_backup_is_encrypted_on_disk_but_downloaded_as_plain_json(): void
    {
        Storage::fake('local');
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        Account::create(['user_id' => $user->id, 'name' => 'بانک محرمانه', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $backup = app(BackupService::class)->create($user);

        $stored = Storage::disk('local')->get($backup->file_path);
        $this->assertTrue($backup->is_encrypted);
        $this->assertStringNotContainsString('بانک محرمانه', $stored);
        $this->assertNull(json_decode($stored, true));

        $download = $this->actingAs($user)->post(route('backups.download', $backup), ['password' => 'password-password']);
        $download->assertOk();
        $plain = $download->streamedContent();
        $this->assertSame(1, json_decode($plain, true)['schema_version']);
        $this->assertStringContainsString('بانک محرمانه', $plain);
    }

    public function test_plain_json_backup_from_download_can_be_restored(): void
    {
        Storage::fake('local');
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);
        $account = Account::create(['user_id' => $user->id, 'name' => 'بانک اصلی', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $backups = app(BackupService::class);
        $plain = $backups->contents($backups->create($user));
        $account->update(['name' => 'حساب اشتباه']);

        $this->actingAs($user)->post(route('backups.restore'), [
            'password' => 'password-password',
            'backup' => UploadedFile::fake()->createWithContent('backup.json', $plain),
        ])->assertRedirect();

        $this->assertDatabaseHas('accounts', ['user_id' => $user->id, 'name' => 'بانک اصلی']);
    }

    public function test_invalid_backup_file_is_rejected(): void
    {
        $user = User::forceCreate(['email_verified_at' => now(), 'name' => 'مالک', 'email' => 'owner@example.com', 'password' => Hash::make('password-password')]);

        $this->actingAs($user)->post(route('backups.restore'), [
            'password' => 'password-password',
            'backup' => UploadedFile::fake()->createWithContent('backup.json', 'not a backup'),
        ])->assertSessionHasErrors('backup');
    }
}
