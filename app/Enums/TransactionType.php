<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case DebtGiven = 'debt_given';
    case DebtReceived = 'debt_received';
    case DebtPayment = 'debt_payment';
    case ReceivableCollection = 'receivable_collection';
    case LoanReceive = 'loan_receive';
    case LoanInstallmentPayment = 'loan_installment_payment';
    case CheckPayment = 'check_payment';
    case CheckReceive = 'check_receive';

    public function affectsBalance(): int
    {
        return match ($this) {
            self::Income, self::TransferIn, self::DebtReceived, self::ReceivableCollection, self::LoanReceive, self::CheckReceive => 1,
            self::Expense, self::TransferOut, self::DebtGiven, self::DebtPayment, self::LoanInstallmentPayment, self::CheckPayment => -1,
            self::Adjustment => 1,
        };
    }
}
