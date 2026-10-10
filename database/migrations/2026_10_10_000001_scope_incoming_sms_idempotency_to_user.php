<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The key was unique across all users, so with more than one user an identical
 * bank message from a second person would be treated as a duplicate and dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->unique('idempotency_key');
        });
    }
};
