<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SimpleModuleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->middleware('throttle:30,1')->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/setup', [AuthenticatedSessionController::class, 'setup'])->name('setup');
    Route::post('/setup', [AuthenticatedSessionController::class, 'storeSetup'])->middleware('throttle:5,1');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::post('/accounts/{account}/recalculate', [AccountController::class, 'recalculate'])->name('accounts.recalculate');
    Route::resource('categories', CategoryController::class)->except(['show']);

    Route::resource('transactions', TransactionController::class);

    Route::get('/people', [SimpleModuleController::class, 'people'])->name('people.index');
    Route::get('/people/create', [SimpleModuleController::class, 'createPerson'])->name('people.create');
    Route::post('/people', [SimpleModuleController::class, 'storePerson'])->name('people.store');
    Route::get('/people/{person}', [SimpleModuleController::class, 'showPerson'])->name('people.show');
    Route::get('/people/{person}/edit', [SimpleModuleController::class, 'editPerson'])->name('people.edit');
    Route::put('/people/{person}', [SimpleModuleController::class, 'updatePerson'])->name('people.update');
    Route::delete('/people/{person}', [SimpleModuleController::class, 'deletePerson'])->name('people.destroy');

    Route::get('/debts', [SimpleModuleController::class, 'debts'])->name('debts.index');
    Route::post('/debts', [SimpleModuleController::class, 'storeDebt'])->name('debts.store');
    Route::post('/debts/{debt}/settle', [SimpleModuleController::class, 'settleDebt'])->name('debts.settle');

    Route::get('/loans', [SimpleModuleController::class, 'loans'])->name('loans.index');
    Route::post('/loans', [SimpleModuleController::class, 'storeLoan'])->name('loans.store');
    Route::get('/loans/{loan}', [SimpleModuleController::class, 'showLoan'])->name('loans.show');
    Route::post('/loans/{loan}/installments/{installment}/pay', [SimpleModuleController::class, 'payInstallment'])->name('loans.installments.pay');

    Route::get('/checks', [SimpleModuleController::class, 'checks'])->name('checks.index');
    Route::post('/checks', [SimpleModuleController::class, 'storeCheck'])->name('checks.store');
    Route::post('/checks/{check}/pass', [SimpleModuleController::class, 'passCheck'])->name('checks.pass');
    Route::post('/checks/{check}/bounce', [SimpleModuleController::class, 'bounceCheck'])->name('checks.bounce');
    Route::post('/checks/{check}/cancel', [SimpleModuleController::class, 'cancelCheck'])->name('checks.cancel');

    Route::get('/budgets', [SimpleModuleController::class, 'budgets'])->name('budgets.index');
    Route::post('/budgets', [SimpleModuleController::class, 'storeBudget'])->name('budgets.store');
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::patch('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');

    Route::get('/recurring-transactions', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::post('/recurring-transactions', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::patch('/recurring-transactions/{recurring}', [RecurringTransactionController::class, 'update'])->name('recurring.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/accounts', [ReportController::class, 'accounts'])->name('reports.accounts');
    Route::get('/reports/categories', [ReportController::class, 'categories'])->name('reports.categories');
    Route::get('/reports/people', [ReportController::class, 'people'])->name('reports.people');
    Route::get('/reports/loans', [ReportController::class, 'loans'])->name('reports.loans');
    Route::get('/reports/checks', [ReportController::class, 'checks'])->name('reports.checks');

    Route::get('/exports/transactions.csv', [ExportController::class, 'transactionsCsv'])->name('exports.transactions.csv');
    Route::get('/exports/transactions.xlsx', [ExportController::class, 'transactionsXlsx'])->name('exports.transactions.xlsx');
    Route::get('/exports/report.pdf', [ExportController::class, 'reportPdf'])->name('exports.report.pdf');

    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::match(['GET', 'POST'], '/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/csv', [ImportController::class, 'csv'])->name('imports.csv');
    Route::post('/imports/sms-preview', [ImportController::class, 'smsPreview'])->name('imports.sms-preview');
    Route::post('/imports/sms-confirm', [ImportController::class, 'smsConfirm'])->name('imports.sms-confirm');
    Route::post('/imports/sms-patterns', [ImportController::class, 'storeSmsPattern'])->name('imports.sms-patterns.store');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Unknown addresses go through the web group too, so the error page knows the language and theme.
Route::fallback(fn () => abort(404));
