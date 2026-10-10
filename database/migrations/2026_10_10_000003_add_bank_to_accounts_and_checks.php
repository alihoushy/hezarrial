<?php

use App\Enums\Bank;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `bank` holds a known bank (its logo slug); the free-text `bank_name` stays for banks
 * that are not in the list. Existing names are matched to a bank but never removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['accounts', 'checks'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('bank', 32)->nullable()->after('bank_name')->index();
            });

            DB::table($table)->whereNotNull('bank_name')->orderBy('id')->each(function ($row) use ($table): void {
                $bank = Bank::tryFromText($row->bank_name);

                if ($bank) {
                    DB::table($table)->where('id', $row->id)->update(['bank' => $bank->value]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['accounts', 'checks'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['bank']);
                $table->dropColumn('bank');
            });
        }
    }
};
