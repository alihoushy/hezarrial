@php
    $schema = [['@type' => 'WebApplication', 'name' => 'ماشین‌حساب اقساط وام', 'applicationCategory' => 'FinanceApplication', 'operatingSystem' => 'Web', 'inLanguage' => 'fa-IR', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IRR'], 'url' => config('app.url').'/tools/loan-calculator']];
    $fmt = fn ($n, $d = 0) => \App\Support\LoanCalculator::persianNumber($n, $d);
@endphp
<x-site.layout
    title="ماشین‌حساب اقساط وام"
    description="قسط ماهانه، مجموع پرداختی و سود کل وام را با مبلغ، نرخ سود سالانه و تعداد اقساط حساب کنید؛ جدول اقساط ماه‌به‌ماه، رایگان و بدون ثبت‌نام."
    path="/tools/loan-calculator"
    :schema="$schema"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'ماشین‌حساب اقساط وام', 'url' => '/tools/loan-calculator']]">
    <section class="mx-auto max-w-3xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">ماشین‌حساب اقساط وام</h1>
        <p class="mt-4 text-lg leading-9 text-muted-foreground">مبلغ وام، نرخ سود سالانه و تعداد اقساط را بنویسید تا قسط ماهانه، مجموع پرداختی و سود کل را ببینید.</p>

        <form method="get" action="/tools/loan-calculator" class="mt-8 grid gap-5 rounded-2xl border border-border bg-card p-6 sm:grid-cols-3">
            <label class="flex flex-col gap-2 text-sm font-semibold">مبلغ وام (تومان)
                <input name="amount" type="number" inputmode="numeric" min="1" step="any" required dir="ltr" value="{{ $input['amount'] ?? '' }}" placeholder="500000000" class="h-12 rounded-xl border border-input bg-background px-3 text-start text-base font-normal">
                @error('amount')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
            </label>
            <label class="flex flex-col gap-2 text-sm font-semibold">نرخ سود سالانه (٪)
                <input name="rate" type="number" inputmode="decimal" min="0" max="100" step="any" required dir="ltr" value="{{ $input['rate'] ?? '' }}" placeholder="23" class="h-12 rounded-xl border border-input bg-background px-3 text-start text-base font-normal">
                @error('rate')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
            </label>
            <label class="flex flex-col gap-2 text-sm font-semibold">تعداد اقساط (ماه)
                <input name="months" type="number" inputmode="numeric" min="1" max="360" required dir="ltr" value="{{ $input['months'] ?? '' }}" placeholder="36" class="h-12 rounded-xl border border-input bg-background px-3 text-start text-base font-normal">
                @error('months')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
            </label>
            <button class="h-12 rounded-xl bg-primary font-bold text-primary-foreground sm:col-span-3">محاسبه</button>
        </form>

        @if ($result)
            <section class="mt-8" aria-live="polite">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-brand/40 bg-brand/5 p-5"><p class="text-sm text-muted-foreground">قسط ماهانه</p><p class="mt-1 text-2xl font-extrabold">{{ $fmt($result['instalment']) }}</p><p class="text-xs text-muted-foreground">تومان</p></div>
                    <div class="rounded-2xl border border-border bg-card p-5"><p class="text-sm text-muted-foreground">مجموع پرداختی</p><p class="mt-1 text-2xl font-extrabold">{{ $fmt($result['total']) }}</p><p class="text-xs text-muted-foreground">تومان</p></div>
                    <div class="rounded-2xl border border-border bg-card p-5"><p class="text-sm text-muted-foreground">سود کل</p><p class="mt-1 text-2xl font-extrabold">{{ $fmt($result['interest']) }}</p><p class="text-xs text-muted-foreground">تومان</p></div>
                </div>

                <h2 class="mt-10 text-xl font-extrabold">جدول اقساط</h2>
                <div class="mt-4 overflow-x-auto rounded-2xl border border-border">
                    <table class="w-full min-w-[34rem] text-sm">
                        <thead class="bg-muted text-start"><tr><th class="p-3 text-start">قسط</th><th class="p-3 text-start">مبلغ قسط</th><th class="p-3 text-start">سود</th><th class="p-3 text-start">اصل</th><th class="p-3 text-start">مانده</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($result['schedule'] as $row)
                                <tr><td class="p-3">{{ $fmt($row['n']) }}</td><td class="p-3">{{ $fmt($row['payment']) }}</td><td class="p-3">{{ $fmt($row['interest']) }}</td><td class="p-3">{{ $fmt($row['principal']) }}</td><td class="p-3">{{ $fmt($row['balance']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="prose-site mt-12">
            <h2>این محاسبه چگونه انجام می‌شود؟</h2>
            <p>ماشین‌حساب از روش «اقساط مساوی با سود کاهنده» استفاده می‌کند: هر ماه سود فقط روی باقی‌مانده‌ی اصل وام حساب می‌شود و قسط ماهانه ثابت است. فرمول آن <code>قسط = مبلغ × r ÷ (1 − (1 + r)<sup>−n</sup>)</code> است که r نرخ ماهانه (نرخ سالانه ÷ ۱۲) و n تعداد اقساط است.</p>
            <p><strong>توجه:</strong> بانک‌ها و مؤسسات مالی ممکن است سود را به شیوه‌ی دیگری محاسبه کنند (مثلاً سود ساده روی کل مبلغ، کارمزد یا بیمه‌ی جداگانه). عدد این صفحه برای برآورد است؛ مبلغ نهایی همان است که در قرارداد بانک نوشته شده.</p>
            <p>وقتی وام گرفتید، می‌توانید اقساطش را در <a href="/features/loans">هزار ریال ثبت کنید</a> و پرداخت هر قسط را دنبال کنید.</p>
        </div>
    </section>
    <x-site.cta />
</x-site.layout>
