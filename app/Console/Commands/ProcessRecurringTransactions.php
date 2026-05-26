<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'app:process-recurring-transactions';
    protected $description = 'Create reminder suggestions for due recurring transactions.';

    public function handle(): int
    {
        $count = 0;

        RecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', now()->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString()))
            ->chunkById(100, function ($items) use (&$count): void {
                foreach ($items as $item) {
                    Reminder::firstOrCreate([
                        'user_id' => $item->user_id,
                        'remindable_type' => $item::class,
                        'remindable_id' => $item->id,
                        'due_date' => $item->next_run_date,
                    ], [
                        'title' => 'پیشنهاد تراکنش تکرارشونده: '.$item->title,
                        'status' => 'pending',
                        'notify_in_app' => true,
                    ]);

                    $item->forceFill(['next_run_date' => $this->nextDate($item->next_run_date, $item->frequency)])->save();
                    $count++;
                }
            });

        $this->info("{$count} recurring transaction suggestions processed.");

        return self::SUCCESS;
    }

    private function nextDate(mixed $date, string $frequency): string
    {
        $date = Carbon::parse($date);

        return match ($frequency) {
            'daily' => $date->addDay()->toDateString(),
            'weekly' => $date->addWeek()->toDateString(),
            'yearly' => $date->addYearNoOverflow()->toDateString(),
            default => $date->addMonthNoOverflow()->toDateString(),
        };
    }
}
