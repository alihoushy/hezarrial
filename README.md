# هزار ریال

یک اپلیکیشن خصوصی حسابداری شخصی فارسی، RTL و موبایل‌اول برای استفاده روی iPhone، ساخته‌شده با Laravel 13، Blade، Livewire، Tailwind CSS و MySQL/MariaDB.

## معماری

- Laravel 13 / PHP 8.3+
- Blade + Livewire 4 installed, with server-rendered mobile-first UI
- Tailwind CSS 4 + Vite
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

Node 20.19+ is required by the Laravel 13 Vite toolchain. The current workspace had Node 18.19.1, so asset build cannot complete until Node is upgraded.

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

Add the Laravel scheduler on production so reminders for recurring transactions are created daily:

```cron
* * * * * cd /path/to/hezarrial && php artisan schedule:run >> /dev/null 2>&1
```

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
