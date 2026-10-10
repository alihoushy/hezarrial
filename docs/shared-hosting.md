# استقرار هزار ریال روی هاست اشتراکی لینوکسی (بدون SSH)

این راهنما جایگزین مسیر Docker/VPS ([ci-cd.md](ci-cd.md)) برای هاست اشتراکی‌ای است که دسترسی SSH ندارد و فقط این امکانات را می‌دهد: File Manager یا FTP، phpMyAdmin، Cron Jobs و تنظیمات PHP در پنل (cPanel، DirectAdmin یا Plesk).

ایده اصلی ساده است:

- هر کاری که به `composer`، `npm` یا ترمینال نیاز دارد، روی سیستم خودت انجام می‌شود و نتیجه به‌صورت یک فایل zip آماده آپلود می‌شود.
- هر کاری که باید روی سرور با `php artisan` اجرا شود، به یک فایل PHP در پوشه `cron/` تبدیل شده که از بخش Cron Jobs پنل صدا زده می‌شود.

---

## ۱. تحلیل پروژه از دید هاست اشتراکی

| مورد | روی VPS/Docker | روی هاست اشتراکی |
| --- | --- | --- |
| وب‌سرور | nginx + php-fpm داخل container | Apache/LiteSpeed خود هاست؛ فایل `public/.htaccess` آماده است |
| HTTPS | Caddy | SSL رایگان پنل (AutoSSL / Let's Encrypt) |
| دیتابیس | container ماریا‌دی‌بی | MySQL/MariaDB خود هاست |
| نصب وابستگی‌ها و build | داخل build image | روی سیستم خودت با `deploy/shared-hosting/build.sh` |
| `migrate` و `optimize` | `deploy/deploy.sh` | `cron/deploy.php` (خودکار بعد از هر آپلود) |
| زمان‌بندی (`schedule:run`) | cron سرور | یک cron جدا برای هر کار |
| صف (queue) | worker | **لازم نیست**؛ هیچ jobی در کد dispatch نمی‌شود، پس `QUEUE_CONNECTION=sync` |
| `storage:link` | entrypoint | **لازم نیست**؛ دیسک `public` استفاده نمی‌شود و پشتیبان‌ها در `storage/app/private` هستند |
| پاک‌سازی sessionها | — | لازم نیست؛ درایور database خودش با lottery پاک می‌کند |

### کارهای دوره‌ای پروژه

دستورهای زمان‌بندی‌شده در [routes/console.php](../routes/console.php) این است:

- `app:process-recurring-transactions`: برای تراکنش‌های تکرارشونده‌ای که موعدشان رسیده، یادآور (Reminder) می‌سازد.
- `app:prune-sms-text`: متن خام پیامک‌های بانکی که قبلاً بررسی شده‌اند را بعد از ۳۰ روز پاک می‌کند (مبلغ و تاریخ می‌ماند).

دستور `app:recalculate-balances` زمان‌بندی نداشت، اما چون حالا راهی برای اجرای دستی‌اش نداری، به‌عنوان یک بررسی سلامت هفتگی اختیاری اضافه شده است.

### فایل‌های اضافه‌شده

| فایل | کاربرد |
| --- | --- |
| `cron/deploy.php` | کارهای بعد از آپلود: بررسی پیش‌نیازها، `migrate --force`، `optimize:clear` و `optimize` |
| `cron/process-recurring-transactions.php` | اجرای `app:process-recurring-transactions` |
| `cron/check-balances.php` | بررسی هفتگی مانده حساب‌ها (فقط گزارش؛ با `--fix` اصلاح می‌کند) |
| `cron/prune-sms-text.php` | پاک‌کردن متن خام پیامک‌های بررسی‌شده (روزانه) |
| `cron/_runner.php`, `cron/_functions.php` | زیرساخت مشترک: بررسی نسخه PHP، قفل ضد همپوشانی، لاگ، ایمیل خطا |
| `deploy/shared-hosting/build.sh` | ساخت zip آماده آپلود روی سیستم خودت |
| `deploy/shared-hosting/env.example` | نمونه `.env` مخصوص هاست اشتراکی (داخل zip با نام `.env.example` قرار می‌گیرد) |
| `deploy/shared-hosting/public_html-index.php` | `index.php` مخصوص حالتی که `public/` به `public_html` منتقل می‌شود |

### رفتار مشترک فایل‌های cron

- **فقط در صورت خطا خروجی می‌دهند.** پس ایمیل cron پنل فقط وقتی می‌آید که مشکلی هست. به انتهای دستور cron عبارت `>/dev/null 2>&1` اضافه نکن، چون ایمیل خطا را از بین می‌برد.
- **همه‌چیز را در `storage/logs/cron.log` می‌نویسند**، با زمان به وقت تهران. حجم این فایل محدود است و حدود ۱ مگابایت می‌چرخد.
- **اجرای هم‌زمان ندارند.** اگر اجرای قبلی هنوز تمام نشده باشد، اجرای جدید بی‌صدا رد می‌شود.
- **نسخه PHP را چک می‌کنند.** اگر cron با PHP اشتباه اجرا شود، پیام واضح می‌دهند (نسخه و مسیر باینری را می‌گویند) و parse error نمی‌دهند.
- **از مرورگر قابل اجرا نیستند.** هم بیرون از document root هستند و هم درخواست وب را با 404 رد می‌کنند.

---

## ۲. پیش‌نیازهای هاست (قبل از خرید یا از پشتیبانی بپرس)

- **PHP 8.3 یا 8.4.** نسخه 8.5 کار نمی‌کند، چون `phpoffice/phpspreadsheet` (خروجی Excel) نسخه کمتر از 8.5 می‌خواهد.
- **نسخه PHP خط فرمان (CLI) که cron استفاده می‌کند هم باید 8.3 یا 8.4 باشد.** این مهم‌ترین تله هاست‌های اشتراکی است (بخش ۷).
- **پشتیبانی از Argon2id در PHP** (`PASSWORD_ARGON2ID`)، چون `HASH_DRIVER=argon2id` است. `cron/deploy.php` این را چک می‌کند.
- **اکستنشن‌ها:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `dom`, `gd`, `zip`, `iconv`, `simplexml`, `xmlreader`, `xmlwriter`, `zlib`.
- **دیتابیس:** MySQL 8 یا MariaDB 10.11 به بالا با `utf8mb4`.
- **Cron Jobs** در پنل، ترجیحاً با امکان اجرای هر ۵ دقیقه.
- **امکان تغییر Document Root دامنه به یک پوشه دلخواه.** این امکان اختیاری است ولی بهترین حالت است (بخش ۴).
- **تنظیمات PHP پیشنهادی:**
  - `memory_limit = 256M`
  - `upload_max_filesize = 25M`
  - `post_max_size = 25M`
  - `max_execution_time = 60`

  دلیل: بازیابی پشتیبان تا 20MB فایل می‌پذیرد، و Argon2id به‌تنهایی برای هر هش 64MB حافظه مصرف می‌کند.

---

## ۳. ساخت بسته روی سیستم خودت

پیش‌نیاز: Node 20.19+ (`nvm use`)، PHP 8.3، `composer` و `zip`.

تغییرات را commit کن، چون اسکریپت از `HEAD` بسته می‌سازد و تغییرات commit‌نشده را نادیده می‌گیرد. بعد یکی از این دو را اجرا کن (توضیح دو حالت در بخش ۴ است):

```bash
deploy/shared-hosting/build.sh               # حالت A: document root = hezarrial/public
deploy/shared-hosting/build.sh public_html   # حالت B: محتوای public داخل public_html
```

خروجی در `dist/` ساخته می‌شود؛ مثلاً `dist/hezarrial-20261005-105323-c4a74d3-docroot.zip` با حجم حدود 13MB. اسکریپت این کارها را انجام می‌دهد:

1. یک کپی تمیز از `HEAD` می‌گیرد، بنابراین `.env` و فایل‌های محلی تو هرگز داخل بسته نمی‌روند.
2. `composer install --no-dev --optimize-autoloader` را اجرا می‌کند.
3. `npm ci && npm run build` را اجرا می‌کند.
4. فایل‌های غیرضروری را حذف می‌کند: tests، Docker، node_modules، سورس CSS/JS و docs.
5. `.env.example` را با نمونه مخصوص هاست اشتراکی جایگزین می‌کند.
6. فایل `RELEASE` را می‌نویسد. `cron/deploy.php` از روی این فایل آپلود جدید را تشخیص می‌دهد.

> کش‌های config/route/view عمداً داخل بسته نیستند، چون مسیرهای مطلق سرور را در خود ذخیره می‌کنند. این کش‌ها روی خود سرور توسط `cron/deploy.php` ساخته می‌شوند.

---

## ۴. انتخاب ساختار پوشه‌ها

پوشه برنامه **هرگز** نباید کامل داخل `public_html` باشد؛ در آن صورت `.env` و کد از وب قابل دسترسی می‌شوند.

### حالت A (پیشنهادی): تغییر Document Root

```text
/home/USER/
├── hezarrial/          ← کل برنامه
│   ├── public/         ← document root دامنه
│   ├── cron/
│   ├── storage/
│   └── .env
└── public_html/        ← استفاده نمی‌شود
```

در پنل، Document Root دامنه را روی `/home/USER/hezarrial/public` بگذار:

- **cPanel:** بخش Domains، ویرایش دامنه.
- **DirectAdmin:** Domain Setup، گزینه Custom HTTPD یا Document Root.
- **Plesk:** Hosting Settings.

اکثر هاست‌های «بهینه‌شده برای لاراول» این امکان را دارند.

### حالت B: بدون تغییر Document Root

```text
/home/USER/
├── hezarrial/          ← برنامه، بدون پوشه public
└── public_html/        ← محتوای public + index.php اصلاح‌شده
```

در این حالت بسته را با `build.sh public_html` بساز. `index.php` داخل `public_html` به `../hezarrial` اشاره می‌کند و مسیر public را برای Vite تنظیم می‌کند. اگر نام پوشه برنامه را عوض کردی، متغیر `$appPath` بالای `public_html/index.php` را اصلاح کن.

> **DirectAdmin:** پوشه وب هر دامنه معمولاً `~/domains/DOMAIN/public_html` است. zip را در `~/domains/hezarrial.ir/` extract کن، یعنی کنار همان `public_html`.

---

## ۵. اولین استقرار، قدم به قدم

### ۵-۱. دیتابیس

در پنل (MySQL Databases):

1. یک دیتابیس بساز، مثلاً `cpuser_hezarrial`.
2. یک کاربر با رمز قوی بساز.
3. کاربر را با **ALL PRIVILEGES** به دیتابیس وصل کن.

اگر می‌خواهی **داده‌های فعلی‌ات** را منتقل کنی، روی سیستم خودت این را اجرا کن:

```bash
mysqldump -u hezarrial_app -p --single-transaction --default-character-set=utf8mb4 hezarrial > hezarrial.sql
```

بعد فایل را در phpMyAdmin هاست از تب Import وارد کن. در این حالت نیازی به ساخت کاربر جدید در `/setup` نیست. یادت باشد هش رمزها Argon2id است، پس هاست باید از Argon2id پشتیبانی کند.

### ۵-۲. آپلود

1. با File Manager، zip را در **پوشه home** آپلود کن (نه داخل `public_html`).
2. Extract کن.
3. zip را پاک کن.
4. مطمئن شو پوشه‌های `storage/` و `bootstrap/cache/` قابل نوشتن هستند. روی اکثر هاست‌ها PHP با کاربر خودت اجرا می‌شود، پس 755 کافی است. **هرگز 777 نگذار.**

### ۵-۳. فایل `.env`

1. در File Manager، فایل `hezarrial/.env.example` را به `hezarrial/.env` کپی کن.
2. فایل را ویرایش کن و این مقادیر را پر کن:

| کلید | مقدار |
| --- | --- |
| `APP_KEY` | روی سیستم خودت `php artisan key:generate --show` را اجرا کن و خروجی را بگذار |
| `APP_URL` | آدرس کامل سایت با https |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | اطلاعات دیتابیسی که ساختی. `DB_HOST` معمولاً `localhost` می‌ماند |
| `SESSION_DOMAIN` | باید با دامنه سایت یکی باشد، مثلاً `.hezarrial.ir`؛ وگرنه ورود با خطای 419 شکست می‌خورد |
| `SMS_INGEST_TOKEN` | خروجی `openssl rand -hex 32` (اگر از Shortcut آیفون استفاده می‌کنی) |

3. دسترسی فایل `.env` را روی 600 یا 640 بگذار.

> هر بار که `.env` را عوض کنی، `cron/deploy.php` حداکثر در حدود ۱۰ دقیقه کش config را دوباره می‌سازد. لازم نیست کار دیگری بکنی.

### ۵-۴. Cron Jobها

بخش ۶ را ببین و cronها را اضافه کن. بعد حدود ۱۰ دقیقه صبر کن: یک اجرا تغییر را تشخیص می‌دهد و اجرای بعدی deploy را انجام می‌دهد. سپس `hezarrial/storage/logs/cron.log` را در File Manager باز کن. باید چیزی شبیه این ببینی:

```text
deploy: > php artisan migrate
...
deploy: exit=0 in 1.4s
```

اگر `PREFLIGHT:` دیدی، همان خط دقیقاً می‌گوید چه چیزی کم است (بخش ۹).

### ۵-۵. SSL و ورود

1. از پنل، SSL را برای دامنه فعال کن و گزینه Force HTTPS را روشن کن. اگر پنل این گزینه را ندارد، بالای قوانین `.htaccess` (داخل `public/` یا `public_html/`) این را اضافه کن:

   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteCond %{HTTP:X-Forwarded-Proto} !https
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

2. آدرس `https://DOMAIN/up` باید صفحه سبز سلامت را نشان دهد.
3. `https://DOMAIN/` را باز کن. اگر دیتابیس خالی باشد، به `/setup` می‌رود؛ کاربر اصلی را بساز. بعد از ساخت اولین کاربر، ثبت‌نام بسته می‌شود.
4. **دسته‌بندی‌های پیش‌فرض (اختیاری):** یک cron موقت با دستور زیر بساز:

   ```text
   /PATH/TO/php /home/USER/hezarrial/cron/deploy.php --force --seed
   ```

   بعد از یک بار اجرا، آن را حذف کن. Seeder دسته‌بندی‌های پیش‌فرض درآمد و هزینه را برای کاربر اول می‌سازد.

---

## ۶. Cron Jobها

| فایل | زمان‌بندی | الزامی؟ | کار |
| --- | --- | --- | --- |
| `cron/deploy.php` | `*/5 * * * *` (هر ۵ دقیقه) | بله | migrate و ساخت کش‌ها؛ فقط بعد از آپلود جدید یا تغییر `.env` کاری انجام می‌دهد |
| `cron/process-recurring-transactions.php` | `0 * * * *` (هر ساعت) | بله | ساخت یادآور برای تراکنش‌های تکرارشونده سررسیدشده |
| `cron/check-balances.php` | `30 4 * * 5` (جمعه ۴:۳۰) | اختیاری | اگر مانده حسابی با تراکنش‌هایش نخواند، ایمیل خطا می‌فرستد |
| `cron/prune-sms-text.php` | `15 3 * * *` (روزی یک بار) | اختیاری | پاک‌کردن متن خام پیامک‌های بانکی بررسی‌شده؛ فقط اگر پیامک را به برنامه می‌فرستی لازم است |

شکل دستور هر cron:

```text
/PATH/TO/php /home/USER/hezarrial/cron/process-recurring-transactions.php
```

### چرا recurring هر ساعت و نه روزی یک بار؟

این دستور idempotent است: اجرای تکراری یادآور تکراری نمی‌سازد. در هر اجرا، هر آیتم سررسیدشده فقط یک دوره جلو می‌رود. اگر cron هاست چند روز از کار بیفتد، اجرای ساعتی خیلی سریع‌تر عقب‌ماندگی را جبران می‌کند. اگر هاست محدودیت تعداد اجرا دارد، `0 3 * * *` (روزی یک بار) هم کافی است.

### چرا deploy هر ۵ دقیقه؟

وقتی تغییری نباشد، فقط یک hash مقایسه می‌کند و فوراً خارج می‌شود؛ هزینه‌اش تقریباً صفر است. وقتی `RELEASE` (آپلود جدید) یا `.env` عوض شود، این اتفاق‌ها می‌افتد:

1. اجرای اول تغییر را ثبت می‌کند و منتظر می‌ماند. اکسترکت با File Manager اتمی نیست و نباید وسط آن deploy شود.
2. اجرای بعدی (حداقل ۲ دقیقه بعد) migrate و کش‌ها را انجام می‌دهد.
3. اگر deploy شکست بخورد، یک ایمیل خطا می‌آید و تا تغییر بعدی دوباره امتحان نمی‌شود. اگر رفع مشکل با تغییر محتوای `.env` بوده، خودکار دوباره امتحان می‌شود؛ در غیر این صورت (مثلاً فعال کردن یک اکستنشن) یک بار با `--force` اجرا کن.

### اصلاح مانده حساب‌ها

اگر `check-balances` اختلاف گزارش کرد و می‌خواهی اصلاح شود، یک cron موقت بساز:

```text
/PATH/TO/php /home/USER/hezarrial/cron/check-balances.php --fix
```

بعد از یک بار اجرا، آن را حذف کن.

---

## ۷. پیدا کردن مسیر درست PHP برای cron

در هاست اشتراکی، `php` پیش‌فرض cron اغلب با نسخه PHP سایت فرق دارد (مثلاً 7.4 یا 8.1). اسکریپت‌ها در این حالت پیامی مثل این می‌دهند:

```text
PHP 8.1.34 (/usr/bin/php) is not supported; point the cron command at a PHP 8.3 or 8.4 binary.
```

مسیرهای رایج PHP 8.3:

| پنل / سیستم | مسیر |
| --- | --- |
| CloudLinux (PHP Selector) | `/opt/alt/php83/usr/bin/php` |
| cPanel EasyApache | `/opt/cpanel/ea-php83/root/usr/bin/php` یا `/usr/local/bin/ea-php83` |
| DirectAdmin CustomBuild | `/usr/local/php83/bin/php` |
| Plesk | `/opt/plesk/php/8.3/bin/php` |

اگر مطمئن نیستی، یک cron موقت با دستور `ls -d /opt/alt/php8* /opt/cpanel/ea-php8* /usr/local/php8* 2>&1; php -v` بساز و خروجی را در ایمیل cron ببین. یا از پشتیبانی هاست بپرس: «مسیر باینری PHP 8.3 CLI برای cron چیست؟»

> اگر هاست cron را با `php-cgi` اجرا کند، اسکریپت‌ها باز هم کار می‌کنند. در این حالت گزینه `-q` را بعد از مسیر PHP بگذار تا هدرهای HTTP وارد ایمیل نشوند.

---

## ۸. به‌روزرسانی

1. **قبل از هر به‌روزرسانی** از دیتابیس پشتیبان بگیر: در phpMyAdmin، تب Export، یا از بکاپ پنل. migrationها بعد از اجرا برگشت خودکار ندارند.
2. روی سیستم خودت commit کن و `deploy/shared-hosting/build.sh` (یا `build.sh public_html`) را اجرا کن.
3. zip را در همان جای قبلی (home) آپلود کن و با Overwrite اکسترکت کن.
   - اگر وابستگی‌های composer عوض شده‌اند، قبل از اکسترکت پوشه `hezarrial/vendor` را پاک کن تا فایل قدیمی جا نماند.
   - در حالت B، اگر خواستی پوشه `public_html/build` را هم قبلش پاک کن تا assetهای قدیمی جمع نشوند.
   - `.env` و محتوای `storage/` با اکسترکت دست نمی‌خورند، چون داخل zip نیستند.
4. ظرف حدود ۱۰ دقیقه `cron/deploy.php` نسخه جدید را تشخیص می‌دهد و migrate و کش‌ها را اجرا می‌کند. نتیجه را در `cron.log` ببین. فایل `storage/framework/deploy-state.json` هم نسخه و وضعیت آخرین deploy را نشان می‌دهد.

> بین اکسترکت و اجرای deploy (حداکثر حدود ۱۰ دقیقه) ممکن است صفحه‌ای که route جدید دارد کار نکند، چون کش route قدیمی است. برای یک برنامه شخصی این قابل قبول است. اگر عجله داری، یک cron موقت `deploy.php --force` بساز و بعد حذفش کن.

### برگشت به نسخه قبل

zip نسخه قبلی را که در `dist/` نگه داشته‌ای دوباره آپلود و اکسترکت کن. اگر نسخه جدید migration داشته، دیتابیس را هم از پشتیبان برگردان.

---

## ۹. عیب‌یابی

| علامت | علت محتمل و راه‌حل |
| --- | --- |
| `cron.log` اصلاً ساخته نمی‌شود | cron اجرا نمی‌شود یا مسیر فایل اشتباه است؛ ایمیل cron را در پنل فعال کن و خطا را ببین |
| `is not supported; point the cron command at a PHP 8.3 or 8.4 binary` | مسیر PHP در دستور cron را عوض کن (بخش ۷) |
| `PREFLIGHT: Missing PHP extensions` | اکستنشن را در بخش Select PHP Version یا PHP Extensions پنل فعال کن. نسخه CLI و وب را جدا چک کن |
| `PREFLIGHT: ... Argon2id` | از پشتیبانی بخواه Argon2 را فعال کند. اگر هنوز کاربری نساخته‌ای، می‌توانی `HASH_DRIVER=bcrypt` بگذاری. بعد از ساخت کاربر عوضش نکن، چون ورود از کار می‌افتد (`verify => true`) |
| `PREFLIGHT: Cannot connect to the database` | نام دیتابیس و کاربر معمولاً پیشوند نام کاربری هاست را دارند (`cpuser_...`)؛ `DB_HOST=localhost` را امتحان کن |
| `PREFLIGHT: ... not writable` | دسترسی پوشه را 755 کن (نه 777) |
| خطای 500 در سایت | `hezarrial/storage/logs/laravel-YYYY-MM-DD.log` را بخوان |
| `Vite manifest not found` | پوشه `build/` آپلود نشده، یا در حالت B از `index.php` معمولی به‌جای نسخه اصلاح‌شده استفاده شده |
| بعد از ورود، خطای 419 Page Expired | `SESSION_DOMAIN` با دامنه نمی‌خواند، یا سایت روی http باز شده در حالی که `SESSION_SECURE_COOKIE=true` است |
| صفحه سفید یا 404 برای همه آدرس‌ها جز صفحه اول | `mod_rewrite` یا `.htaccess` اعمال نمی‌شود؛ از پشتیبانی بپرس (`AllowOverride`) |
| API پیامک خطای 401 می‌دهد با اینکه توکن درست است | بعضی هاست‌ها هدر `Authorization` را حذف می‌کنند؛ در Shortcut به‌جای آن هدر `X-Ingest-Token: <token>` بفرست |
| `.env` را عوض کردم ولی اثر نکرد | حدود ۱۰ دقیقه صبر کن تا `deploy.php` کش config را بسازد، یا یک بار `--force` اجرا کن |
| deploy یک بار شکست خورد و دیگر تکرار نمی‌شود | عمدی است. اگر مشکل را با تغییر `.env` رفع کردی، خودکار تکرار می‌شود؛ وگرنه `deploy.php --force` را یک بار با cron موقت اجرا کن |

---

## ۱۰. پشتیبان‌گیری

- **بکاپ خودکار هاست:** ببین هاست چند روز نگه می‌دارد و آیا دیتابیس را هم شامل می‌شود.
- **بکاپ داخل برنامه:** صفحه پشتیبان‌ها فایل JSON را در `storage/app/private/backups` می‌سازد که از وب قابل دسترسی نیست. هر چند وقت یک بار آن را دانلود کن و جای دیگری نگه دار.
- **قبل از هر به‌روزرسانی:** یک Export کامل از phpMyAdmin بگیر.

---

## ۱۱. نکات امنیتی

- **ساختار پوشه:** برنامه بیرون از `public_html` باشد (بخش ۴). هرگز کل پروژه را داخل `public_html` با یک `.htaccess` ریدایرکت‌کننده نگذار.
- **`APP_DEBUG=false`:** `cron/deploy.php` اگر `true` باشد deploy نمی‌کند.
- **`.env`:** دسترسی 600/640. هرگز در Git یا داخل zip قرار نمی‌گیرد.
- **دسترسی پوشه‌ها:** هیچ پوشه‌ای 777 نباشد.
- **`SMS_INGEST_TOKEN`:** یک مقدار تصادفی طولانی باشد. اگر خالی بماند، endpoint پیامک با 503 بسته می‌ماند.
- **ایمیل cron:** برای cronها ایمیل بگذار تا خطاها را ببینی؛ اسکریپت‌ها در حالت موفق ایمیلی نمی‌فرستند.
