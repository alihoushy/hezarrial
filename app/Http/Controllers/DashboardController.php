<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        return view('dashboard', ['dashboard' => $reports->dashboard(auth()->user())]);
    }
}
