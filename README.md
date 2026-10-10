# هزار ریال

یک اپلیکیشن خصوصی حسابداری شخصی فارسی، RTL و موبایل‌اول برای استفاده روی iPhone، ساخته‌شده با Laravel 13، Inertia، React، shadcn/ui، Tailwind CSS و MySQL/MariaDB.

## معماری

- Laravel 13 / PHP 8.3+
- Inertia 3 + React 19 + TypeScript: controllers return `Inertia::render()`, pages live in `resources/js/pages`
- [shadcn/ui](https://ui.shadcn.com) (Vega preset, Radix, RTL) in `resources/js/components/ui`, with the Vazirmatn font and a mobile-first light theme
- Tailwind CSS 4 + Vite; charts with Recharts (loaded on demand)
- Loading states: deferred props with skeletons, instant page visits, infinite-scroll lists
- MySQL 8 یا MariaDB 10.11+
- Eloquent models, migrations, policies, form requests, services
- Accounting logic lives in services, not controllers:
  - `AccountBalanceService`
  - `TransactionService`
  - `TransferService`
  - `DebtService`
  - `LoanService`
  - `CheckService`
  - `BudgetService`
  - `ReportService`
  - `BackupService`
  - `SmsParserService`
- Financial tables are scoped with `user_id` even though phase 1 is single-user.

## نصب

اگر `nvm` نصب نیست، ابتدا Node Version Manager را نصب کنید. نسخه فعلی nvm در زمان نگارش `v0.40.4` است:

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.4/install.sh | bash
source ~/.bashrc
nvm install 20.19.0
```

اگر shell شما `zsh` است، به جای `source ~/.bashrc` از `source ~/.zshrc` استفاده کنید.

```bash
composer install
nvm use
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

برای توسعه با hot reload به‌جای `npm run build` از `npm run dev` استفاده کنید. کامپوننت جدید shadcn را با `npx shadcn@latest add <name>` اضافه کنید؛ چون `rtl: true` در `components.json` فعال است، کلاس‌ها خودکار به حالت منطقی (`start`/`end`) تبدیل می‌شوند. بعد از ساخت یا تغییر route، نام‌ها از طریق Ziggy (`route('...')`) در React در دسترس‌اند.

Node 20.19+ is required by the Laravel 13 Vite toolchain. The current workspace had Node 18.19.1, so asset build cannot complete until Node is upgraded.

## زبان‌ها

رابط کاربری فارسی (راست‌چین، تقویم شمسی) و انگلیسی (چپ‌چین) را پشتیبانی می‌کند. زبان هر کاربر در بخش «بیشتر» انتخاب می‌شود و در تنظیمات او ذخیره می‌شود؛ صفحه‌ی ورود هم لینک تغییر زبان دارد.

ترجمه‌ها در `resources/lang/{code}.json` قرار دارند و کلید هر ترجمه خودِ متن فارسی است (قرارداد ترجمه‌ی JSON در Laravel). Laravel با `__()` و React با `t()` از همین فایل می‌خوانند و زبان فارسی فایل نمی‌خواهد. متن‌هایی که عدد یا نام دارند از `:name` استفاده می‌کنند تا ترتیب کلمات در هر زبان آزاد باشد.

برای افزودن زبان جدید (مثلاً عربی):

1. `resources/lang/en.json` را به `resources/lang/ar.json` کپی و مقدارها را ترجمه کنید.
2. زبان را با `name` و `dir` (`rtl` یا `ltr`) به `supported_locales` در `config/app.php` اضافه کنید. برای فایل‌های اعتبارسنجی هم `resources/lang/ar/validation.php` را از `en` بسازید.
3. `npm run i18n:check` را اجرا کنید؛ ترجمه‌های جاافتاده، اضافه یا با جانگهدار ناهماهنگ را گزارش می‌دهد (در CI هم اجرا می‌شود).

هنگام نوشتن متن جدید در React آن را در `t('...')` و در PHP در `__('...')` بگذارید، و ثابت‌های سطح ماژول را با `tr('...')` علامت بزنید و هنگام نمایش با `t()` ترجمه کنید.

## تنظیمات محیط

Important `.env` values:

```dotenv
APP_NAME="هزار ریال"
APP_URL=https://www.hezarrial.ir
APP_LOCALE=fa
APP_FALLBACK_LOCALE=fa
APP_TIMEZONE=Asia/Tehran
HASH_DRIVER=argon2id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hezarrial
DB_USERNAME=root
DB_PASSWORD=

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

For local non-HTTPS development, set:

```dotenv
APP_URL=http://localhost:8000
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
```

## امکانات فاز ۱

- ورود امن و راه‌اندازی اولین کاربر
- غیرفعال شدن ثبت‌نام عمومی بعد از اولین کاربر
- داشبورد فارسی موبایل‌اول
- مدیریت حساب‌ها
- مدیریت دسته‌بندی‌ها
- ثبت درآمد، هزینه، انتقال، اصلاح مانده و انواع تراکنش مالی
- سرویس‌های مرکزی برای مانده حساب و تراکنش
- طلب/بدهی، وام/اقساط، چک، بودجه، اشخاص و یادآوری‌ها با جریان پایه
- گزارش‌های کارتی و خروجی CSV/Excel
- پیش‌نمایش امن پیامک بانکی بدون ذخیره متن خام
- پشتیبان‌گیری JSON خارج از public storage
- PWA manifest و iOS safe-area support
- هدرهای امنیتی پایه و policies برای جلوگیری از دسترسی بین کاربران

## دستورات مهم

```bash
php artisan app:recalculate-balances
php artisan app:recalculate-balances --fix
php artisan app:process-recurring-transactions
php artisan route:list
php artisan test
```

## CI/CD و Docker

پروژه شامل Dockerfile production، compose فایل VPS، و GitHub Actions برای CI/CD است:

```text
Dockerfile
compose.prod.yaml
.github/workflows/ci-cd.yml
docs/ci-cd.md
```

راهنمای کامل آماده‌سازی VPS، secrets، deploy، rollback و تست local Docker در [docs/ci-cd.md](docs/ci-cd.md) آمده است.

Add the Laravel scheduler on production so reminders for recurring transactions are created daily:

```cron
* * * * * cd /path/to/hezarrial && php artisan schedule:run >> /dev/null 2>&1
```

## هاست اشتراکی (بدون SSH)

برای استقرار روی هاست اشتراکی، بسته آماده آپلود با `deploy/shared-hosting/build.sh` ساخته می‌شود و کارهای دوره‌ای و بعد از آپلود (migrate، کش‌ها، تراکنش‌های تکرارشونده) به فایل‌های PHP پوشه `cron/` تبدیل شده‌اند که از Cron Jobs پنل اجرا می‌شوند. راهنمای کامل: [docs/shared-hosting.md](docs/shared-hosting.md).

## تست‌ها

تست‌های واحد بدون دیتابیس:

```bash
php artisan test tests/Unit
```

تست‌های Feature نیاز به درایور دیتابیس تست دارند. در این محیط `pdo_sqlite` نصب نبود و MySQL محلی هم در دسترس نبود، بنابراین تست‌های دیتابیسی اجرا نشدند. روی محیطی با `pdo_sqlite` یا MySQL تست، اجرای کامل باید با دستور زیر انجام شود:

```bash
php artisan test
```

## نکات امنیتی

- CVV2، رمز کارت، رمز دوم، PIN بانکی و اطلاعات حساس بانکی ذخیره نمی‌شوند.
- کارت‌ها فقط با نام مستعار، بانک، چهار رقم آخر و شماره ماسک‌شده اختیاری ذخیره می‌شوند.
- فایل‌های پشتیبان در disk محلی و خارج از public ذخیره می‌شوند.
- دانلود/بازیابی پشتیبان نیازمند تایید رمز عبور است.
- CSV export در برابر formula injection محافظت می‌شود.
- همه رکوردهای مالی با policy و `user_id` محدود شده‌اند.

## سپاس و مجوزها

- لوگوی بانک‌ها از [@persianlabs/icons](https://github.com/persianlabs/icons) (مجوز MIT) گرفته شده که خودش بر پایه‌ی [zegond/logos-per-banks](https://github.com/zegond/logos-per-banks) (CC0) است. اسکریپت `scripts/bank-logos.mjs` فایل‌ها را بهینه می‌کند و در `public/images/banks` می‌گذارد. لوگوها علامت تجاری بانک‌های مربوطه‌اند.
