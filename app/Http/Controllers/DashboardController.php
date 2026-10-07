<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Reports\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): Response
    {
        $user = auth()->user();

        return Inertia::render('dashboard', [
            'summary' => $reports->summary($user),
            'accounts' => Account::forUser($user)->where('is_active', true)->orderBy('sort_order')->get()->map(Present::account(...)),
            'recent' => Transaction::forUser($user)->with(['account', 'category', 'person'])->latest('transaction_date')->latest('id')->limit(8)->get()->map(Present::transaction(...)),
            // The heavier sections load after the first paint, behind skeletons.
            'charts' => Inertia::defer(fn () => $reports->charts($user)),
            'upcoming' => Inertia::defer(fn () => $reports->upcoming($user)),
        ]);
    }
}
