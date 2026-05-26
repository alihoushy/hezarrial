<?php

namespace App\Console\Commands;

use App\Services\Accounting\AccountBalanceService;
use Illuminate\Console\Command;

class RecalculateBalances extends Command
{
    protected $signature = 'app:recalculate-balances {--fix : Update account balances to the recalculated values}';
    protected $description = 'Recalculate account balances from opening balance and valid transactions.';

    public function handle(AccountBalanceService $balances): int
    {
        foreach ($balances->recalculateAll((bool) $this->option('fix')) as $row) {
            $this->line(sprintf(
                '#%d %s current=%s expected=%s difference=%s',
                $row['account']->id,
                $row['account']->name,
                number_format($row['current'], 2),
                number_format($row['expected'], 2),
                number_format($row['difference'], 2),
            ));
        }

        return self::SUCCESS;
    }
}
