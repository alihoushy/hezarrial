<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Transaction;
use App\Services\Export\ExportService;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function transactionsCsv(ExportService $export): StreamedResponse
    {
        $file = 'hezarrial-transactions.csv';

        return response()->streamDownload(function () use ($export): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['تاریخ', 'نوع', 'مبلغ', 'حساب', 'دسته', 'شخص', 'شرح']);
            Transaction::forUser(auth()->user())->with(['account', 'category', 'person'])->orderByDesc('transaction_date')->chunk(200, function ($rows) use ($handle, $export): void {
                foreach ($rows as $transaction) {
                    fputcsv($handle, [
                        $transaction->transaction_date?->toDateString(),
                        $transaction->type->value,
                        $transaction->amount,
                        $export->safeCsvValue($transaction->account?->name),
                        $export->safeCsvValue($transaction->category?->name),
                        $export->safeCsvValue($transaction->person?->full_name),
                        $export->safeCsvValue($transaction->description),
                    ]);
                }
            });
            fclose($handle);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function transactionsXlsx(): BinaryFileResponse
    {
        return Excel::download(new TransactionsExport(auth()->user()), 'hezarrial-transactions.xlsx');
    }

    public function reportPdf(): Response
    {
        abort(501, 'خروجی PDF در فاز بعدی پیاده‌سازی می‌شود.');
    }
}
