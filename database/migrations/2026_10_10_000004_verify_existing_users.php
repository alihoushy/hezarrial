<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification is now required to use the app. Accounts that exist today were made by
 * the owner before verification existed, so they are marked verified rather than locked out.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo: whether an account was verified by a mail or by this migration is not kept.
    }
};
