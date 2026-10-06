<?php

/*
 * Integrity check: recomputes every account balance from its opening balance
 * and transactions, and fails (so the cron e-mail fires) on any mismatch.
 *
 * Suggested cron: weekly, "30 4 * * 5". Read-only by default.
 *
 *     --fix   write the recomputed balances (app:recalculate-balances --fix).
 *             Add it as a one-off cron, then remove it.
 */

use App\Services\Accounting\AccountBalanceService;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/_runner.php';

$fix = in_array('--fix', $_SERVER['argv'] ?? [], true);

exit(cron_run('check-balances', function (Kernel $artisan, BufferedOutput $output) use ($fix) {
    $mismatched = app(AccountBalanceService::class)
        ->recalculateAll($fix)
        ->filter(function (array $row) {
            return abs($row['difference']) > 0.009;
        });

    foreach ($mismatched as $row) {
        $output->writeln(sprintf(
            '#%d %s current=%s expected=%s difference=%s%s',
            $row['account']->id,
            $row['account']->name,
            number_format($row['current'], 2),
            number_format($row['expected'], 2),
            number_format($row['difference'], 2),
            $fix ? ' (fixed)' : ''
        ));
    }

    if ($mismatched->isEmpty()) {
        $output->writeln('All account balances match their transactions.');
    }

    return $mismatched->isEmpty() || $fix ? 0 : 1;
}));
