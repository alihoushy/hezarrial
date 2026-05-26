<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 24)->index();
            $table->string('bank_name')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('masked_card_number')->nullable();
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'type']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 24)->index();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'type']);
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('mobile')->nullable();
            $table->text('description')->nullable();
            $table->string('avatar_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('type', 48)->index();
            $table->decimal('amount', 18, 2);
            $table->date('transaction_date')->index();
            $table->time('transaction_time')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('source', 24)->default('manual');
            $table->foreignId('related_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->uuid('transfer_group_uuid')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'account_id']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'person_id']);
            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('type', 24);
            $table->decimal('original_amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('open');
            $table->text('description')->nullable();
            $table->foreignId('created_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'person_id']);
            $table->index(['user_id', 'status', 'due_date']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('lender_name')->nullable();
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('total_payable_amount', 18, 2);
            $table->decimal('installment_amount', 18, 2);
            $table->unsignedInteger('installment_count');
            $table->unsignedInteger('paid_installment_count')->default(0);
            $table->date('start_date');
            $table->string('period', 24)->default('monthly');
            $table->string('status', 24)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 18, 2);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'loan_id']);
            $table->index(['user_id', 'due_date', 'status']);
        });

        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);
            $table->string('check_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('amount', 18, 2);
            $table->date('due_date');
            $table->date('issued_date')->nullable();
            $table->string('status', 24)->default('pending');
            $table->text('description')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'due_date', 'status']);
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('period', 24)->default('monthly');
            $table->decimal('amount', 18, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('alert_threshold_percent')->default(80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
        });

        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 18, 2);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('frequency', 24);
            $table->date('next_run_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('remindable_type')->nullable();
            $table->unsignedBigInteger('remindable_id')->nullable();
            $table->string('title');
            $table->date('due_date');
            $table->time('due_time')->nullable();
            $table->string('status', 24)->default('pending');
            $table->boolean('notify_in_app')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'due_date', 'status']);
            $table->index(['remindable_type', 'remindable_id']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_encrypted')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('file_path')->nullable();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('error_log')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        foreach ([
            'app_settings', 'audit_logs', 'imports', 'backups', 'reminders',
            'recurring_transactions', 'budgets', 'checks', 'loan_installments',
            'loans', 'debts', 'transactions', 'people', 'categories', 'accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
