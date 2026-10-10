<?php

namespace App\Services\Backup;

use App\Models\Account;
use App\Models\AppSetting;
use App\Models\Backup;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Person;
use App\Models\RecurringTransaction;
use App\Models\Reminder;
use App\Models\SmsPattern;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BackupService
{
    private const DATASETS = [
        'accounts' => Account::class,
        'categories' => Category::class,
        'people' => Person::class,
        'transactions' => Transaction::class,
        'debts' => Debt::class,
        'loans' => Loan::class,
        'loan_installments' => LoanInstallment::class,
        'checks' => Check::class,
        'budgets' => Budget::class,
        'recurring_transactions' => RecurringTransaction::class,
        'reminders' => Reminder::class,
        'sms_patterns' => SmsPattern::class,
        'app_settings' => AppSetting::class,
    ];

    private const DELETE_ORDER = [
        Reminder::class,
        SmsPattern::class,
        RecurringTransaction::class,
        Budget::class,
        Check::class,
        LoanInstallment::class,
        Loan::class,
        Debt::class,
        Transaction::class,
        Person::class,
        Category::class,
        Account::class,
        AppSetting::class,
    ];

    public function create(User $user): Backup
    {
        $payload = [
            'schema_version' => 1,
            'created_at' => now()->toISOString(),
            'user' => $user->only(['name', 'email', 'mobile', 'settings']),
        ];

        foreach (self::DATASETS as $key => $model) {
            $query = $model::query()->where('user_id', $user->id);
            if (method_exists($model, 'bootSoftDeletes')) {
                $query->withTrashed();
            }

            $payload[$key] = $query->orderBy('id')->get();
        }

        $fileName = 'hezarrial-backup-'.$user->id.'-'.now()->format('Ymd-His').'.json';
        $path = 'backups/'.$user->id.'/'.$fileName;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        // Encrypted at rest with the app key. Downloads hand the user plain JSON (see contents()),
        // so a backup stays restorable on any installation, whatever its key.
        Storage::disk('local')->put($path, Crypt::encryptString($json));

        return Backup::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => strlen($json),
            'is_encrypted' => true,
            'created_at' => now(),
        ]);
    }

    /** The portable (plain JSON) content of a stored backup. */
    public function contents(Backup $backup): string
    {
        $stored = Storage::disk('local')->get($backup->file_path);

        return $backup->is_encrypted ? Crypt::decryptString($stored) : $stored;
    }

    /**
     * Turns an uploaded backup into its payload. Accepts the plain JSON we hand out on
     * download, and the encrypted form kept on disk.
     */
    public function decode(string $raw): array
    {
        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            try {
                $payload = json_decode(Crypt::decryptString(trim($raw)), true);
            } catch (DecryptException) {
                $payload = null;
            }
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages(['backup' => __('فایل پشتیبان معتبر نیست.')]);
        }

        return $payload;
    }

    public function restore(User $user, array $payload): array
    {
        if (($payload['schema_version'] ?? null) !== 1) {
            throw ValidationException::withMessages(['backup' => __('نسخه فایل پشتیبان پشتیبانی نمی‌شود.')]);
        }

        return DB::transaction(function () use ($user, $payload): array {
            Schema::disableForeignKeyConstraints();

            try {
                foreach (self::DELETE_ORDER as $model) {
                    $model::query()->where('user_id', $user->id)->forceDelete();
                }

                $restored = [];

                foreach (self::DATASETS as $key => $model) {
                    $table = (new $model())->getTable();
                    $columns = Schema::getColumnListing($table);
                    $rows = collect($payload[$key] ?? [])
                        ->map(function (array $row) use ($columns, $user): array {
                            $row['user_id'] = $user->id;

                            return Arr::only($row, $columns);
                        })
                        ->values();

                    if ($rows->isNotEmpty()) {
                        DB::table($table)->insert($rows->all());
                    }

                    $restored[$key] = $rows->count();
                }

                $user->forceFill([
                    'name' => $payload['user']['name'] ?? $user->name,
                    'email' => $payload['user']['email'] ?? $user->email,
                    'mobile' => $payload['user']['mobile'] ?? $user->mobile,
                    'settings' => $payload['user']['settings'] ?? $user->settings,
                ])->save();

                return $restored;
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });
    }
}
