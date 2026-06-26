<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->text('raw_message');
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency_detected', 3)->nullable();
            $table->string('type', 48)->nullable();
            $table->decimal('balance_after', 18, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sms_pattern_id')->nullable()->constrained()->nullOnDelete();
            $table->string('confidence', 16)->default('low');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_sms');
    }
};
