<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A mobile number is only used for SMS and sign-in once its owner has proved it with a code.
 * Numbers saved before verification existed were entered by the owner, so they count as verified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');
        });

        DB::table('users')->whereNotNull('mobile')->update(['mobile_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobile_verified_at');
        });
    }
};
