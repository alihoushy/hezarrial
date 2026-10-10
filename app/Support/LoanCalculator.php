<?php

namespace App\Support;

/** The monthly instalment of a loan with equal payments (the "annuity" method). */
class LoanCalculator
{
    /**
     * @return array{instalment: float, total: float, interest: float, schedule: list<array{n: int, payment: float, interest: float, principal: float, balance: float}>}
     */
    public static function annuity(float $principal, float $annualRatePercent, int $months): array
    {
        $monthlyRate = $annualRatePercent / 100 / 12;

        $instalment = $monthlyRate > 0
            ? $principal * $monthlyRate / (1 - (1 + $monthlyRate) ** -$months)
            : $principal / $months;

        $balance = $principal;
        $schedule = [];

        for ($n = 1; $n <= $months; $n++) {
            $interest = $balance * $monthlyRate;
            $principalPart = $instalment - $interest;
            $balance = max(0.0, $balance - $principalPart);
            $schedule[] = ['n' => $n, 'payment' => $instalment, 'interest' => $interest, 'principal' => $principalPart, 'balance' => $balance];
        }

        return ['instalment' => $instalment, 'total' => $instalment * $months, 'interest' => $instalment * $months - $principal, 'schedule' => $schedule];
    }

    /** 1234567 => "۱٬۲۳۴٬۵۶۷". */
    public static function persianNumber(float|int $value, int $decimals = 0): string
    {
        return Digits::persian(number_format($value, $decimals, '٫', '٬'));
    }
}
