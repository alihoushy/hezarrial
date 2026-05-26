<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Services\Import\SmsParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('imports.index', ['imports' => Import::forUser(auth()->user())->latest()->get()]);
    }

    public function csv(Request $request): RedirectResponse
    {
        $request->validate(['statement' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);
        $path = $request->file('statement')->store('imports/'.auth()->id());
        Import::create(['user_id' => auth()->id(), 'type' => 'csv', 'file_path' => $path, 'status' => 'pending']);

        return back()->with('status', 'فایل دریافت شد. مرحله نگاشت ستون‌ها در ادامه تکمیل می‌شود.');
    }

    public function smsPreview(Request $request, SmsParserService $parser): View
    {
        $request->validate(['sms_text' => ['required', 'string', 'max:2000']]);

        return view('imports.sms-preview', ['parsed' => $parser->parse($request->sms_text)]);
    }

    public function smsConfirm(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'type' => ['required', 'in:income,expense']]);
        Import::create(['user_id' => auth()->id(), 'type' => 'sms_text', 'status' => 'completed', 'total_rows' => 1, 'imported_rows' => 0]);

        return redirect()->route('imports.index')->with('status', 'پیش‌نمایش پیامک تایید شد. ساخت تراکنش نهایی از فرم تراکنش انجام می‌شود.');
    }
}
