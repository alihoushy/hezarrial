<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Export\ExportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly User $user) {}

    public function collection()
    {
        return Transaction::forUser($this->user)->with(['account', 'category', 'person'])->orderByDesc('transaction_date')->get();
    }

    public function headings(): array
    {
        return ['تاریخ', 'نوع', 'مبلغ', 'حساب', 'دسته', 'شخص', 'شرح', 'مرجع'];
    }

    public function map($transaction): array
    {
        $export = app(ExportService::class);

        return [
            $transaction->transaction_date?->toDateString(),
            $transaction->type->value,
            $transaction->amount,
            $export->safeCsvValue($transaction->account?->name),
            $export->safeCsvValue($transaction->category?->name),
            $export->safeCsvValue($transaction->person?->full_name),
            $export->safeCsvValue($transaction->description),
            $export->safeCsvValue($transaction->reference_number),
        ];
    }
}
