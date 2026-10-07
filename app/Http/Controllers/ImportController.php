<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Models\Import;
use App\Models\SmsPattern;
use App\Services\Import\SmsParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('imports/index', [
            'imports' => Import::forUser(auth()->user())->latest()->limit(20)->get()->map(Present::import(...)),
            'smsPatterns' => SmsPattern::forUser(auth()->user())->latest()->get()->map(Present::smsPattern(...)),
        ]);
    }

    public function csv(Request $request): RedirectResponse
    {
        $request->validate(['statement' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);
        $path = $request->file('statement')->store('imports/'.auth()->id());
        Import::create(['user_id' => auth()->id(), 'type' => 'csv', 'file_path' => $path, 'status' => 'pending']);

        return back()->with('status', __('فایل دریافت شد. مرحله نگاشت ستون‌ها در ادامه تکمیل می‌شود.'));
    }

    public function smsPreview(Request $request, SmsParserService $parser): Response
    {
        $request->validate([
            'sms_text' => ['required', 'string', 'max:2000'],
            'sms_pattern_id' => ['nullable', 'exists:sms_patterns,id'],
        ]);

        $pattern = $request->filled('sms_pattern_id')
            ? SmsPattern::forUser($request->user())->findOrFail($request->sms_pattern_id)
            : null;

        return Inertia::render('imports/sms-preview', ['parsed' => $parser->parse($request->sms_text, $pattern)]);
    }

    public function smsConfirm(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'type' => ['required', 'in:income,expense']]);
        Import::create(['user_id' => auth()->id(), 'type' => 'sms_text', 'status' => 'completed', 'total_rows' => 1, 'imported_rows' => 0]);

        return redirect()->route('imports.index')->with('status', __('پیش‌نمایش پیامک تایید شد. ساخت تراکنش نهایی از فرم تراکنش انجام می‌شود.'));
    }

    public function storeSmsPattern(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'pattern' => ['required', 'string', 'max:2000'],
            'debit_keywords' => ['nullable', 'string', 'max:500'],
            'credit_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        SmsPattern::create([
            ...$data,
            'user_id' => auth()->id(),
            'debit_keywords' => $this->keywords($data['debit_keywords'] ?? ''),
            'credit_keywords' => $this->keywords($data['credit_keywords'] ?? ''),
            'is_active' => true,
        ]);

        return back()->with('status', __('الگوی پیامک ذخیره شد.'));
    }

    private function keywords(string $value): array
    {
        return collect(explode(',', $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
