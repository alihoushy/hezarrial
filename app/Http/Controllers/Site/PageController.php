<?php

namespace App\Http\Controllers\Site;

use App\Content\ContentRepository;
use App\Content\Features;
use App\Http\Controllers\Controller;
use App\Support\LoanCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;

/** The static pages of the public website. */
class PageController extends Controller
{
    public function home(ContentRepository $content): View
    {
        return view('site.home', ['features' => Features::all(), 'planned' => Features::planned(), 'posts' => $content->all('blog')->take(3)]);
    }

    public function features(): View
    {
        return view('site.features', ['features' => Features::all(), 'planned' => Features::planned()]);
    }

    public function feature(string $slug): View
    {
        $features = Features::all();
        abort_unless(isset($features[$slug]), 404);

        return view('site.feature', ['slug' => $slug, 'feature' => $features[$slug], 'others' => collect($features)->except($slug)]);
    }

    public function pricing(): View
    {
        return view('site.pricing', ['planned' => Features::planned()]);
    }

    public function download(): View
    {
        return view('site.download');
    }

    public function about(): View
    {
        return view('site.about');
    }

    public function security(): View
    {
        return view('site.security');
    }

    public function privacy(): View
    {
        return view('site.privacy');
    }

    public function terms(): View
    {
        return view('site.terms');
    }

    public function faq(): View
    {
        return view('site.faq', ['groups' => require base_path('content/faq.php')]);
    }

    public function changelog(ContentRepository $content): View
    {
        return view('site.changelog', ['page' => $content->renderFile(base_path('CHANGELOG.md'))]);
    }

    /** Computed on the server, from the query string, so the page works without JavaScript and can be linked. */
    public function loanCalculator(Request $request): View
    {
        // This page has no session, so a bad value is explained on the page itself, not by a redirect.
        $validator = Validator::make($request->query(), [
            'amount' => ['nullable', 'numeric', 'min:1', 'max:100000000000'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'months' => ['nullable', 'integer', 'min:1', 'max:360'],
        ], [
            'amount.numeric' => 'مبلغ وام را به‌صورت عدد بنویسید.',
            'amount.min' => 'مبلغ وام باید بیشتر از صفر باشد.',
            'amount.max' => 'مبلغ وام خیلی بزرگ است.',
            'rate.numeric' => 'نرخ سود را به‌صورت عدد بنویسید.',
            'rate.max' => 'نرخ سود نمی‌تواند بیشتر از ۱۰۰ درصد باشد.',
            'months.integer' => 'تعداد اقساط باید عدد صحیح باشد.',
            'months.min' => 'تعداد اقساط حداقل یک ماه است.',
            'months.max' => 'تعداد اقساط حداکثر ۳۶۰ ماه است.',
        ]);

        $input = $validator->fails() ? $request->only('amount', 'rate', 'months') : $validator->validated();
        $result = ! $validator->fails() && isset($input['amount'], $input['rate'], $input['months'])
            ? LoanCalculator::annuity((float) $input['amount'], (float) $input['rate'], (int) $input['months'])
            : null;

        return view('site.loan-calculator', ['input' => $input, 'result' => $result, 'errors' => (new ViewErrorBag)->put('default', $validator->errors())]);
    }
}
