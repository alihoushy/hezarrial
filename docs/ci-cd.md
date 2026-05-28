# CI/CD و استقرار Docker برای هزار ریال

این پروژه برای GitHub + یک VPS استاندارد طراحی شده است:

- CI روی GitHub Actions اجرا می‌شود.
- image production با Docker ساخته و در GitHub Container Registry منتشر می‌شود.
- CD فقط از branch `main` روی VPS انجام می‌شود.
- `develop` فقط CI و build image دارد و production را تغییر نمی‌دهد.
- secrets و فایل‌های محیطی production داخل Git commit نمی‌شوند.

برای repository عمومی، اجرای self-hosted runner روی VPS برای pull requestهای عمومی توصیه نمی‌شود، چون کد خارجی می‌تواند روی runner اجرا شود. الگوی امن‌تر این پروژه این است: GitHub-hosted runner برای CI، و deploy محدود با SSH به VPS فقط از `main`.

## Branch Strategy

- `main`: production و deploy خودکار
- `develop`: توسعه یکپارچه و image قابل تست
- `feature/*`: کار روزانه از روی `develop`
- merge به `main` فقط بعد از سبز بودن CI و بررسی دستی

## Local Docker Test روی Linux Mint

اگر Docker نصب نیست:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"
```

بعد از logout/login:

```bash
docker version
docker compose version
```

تست build image:

```bash
docker build -t hezarrial:local .
```

اجرای production compose به صورت local:

```bash
cp deploy/production.env.example .env.production
php artisan key:generate --show
```

مقدار `APP_KEY` خروجی را داخل `.env.production` قرار دهید و رمزهای دیتابیس را تغییر دهید، سپس:

```bash
APP_IMAGE=hezarrial:local docker compose --env-file .env.production -f compose.prod.yaml up -d
docker compose --env-file .env.production -f compose.prod.yaml exec -T web php artisan migrate --force
```

## GitHub Actions

فایل workflow:

```text
.github/workflows/ci-cd.yml
```

Jobها:

- `backend`: نصب Composer، validate، اجرای `php artisan test`
- `frontend`: نصب npm و اجرای `npm run build`
- `container`: ساخت و push image به GHCR برای pushهای `main` و `develop`
- `deploy-production`: deploy روی VPS فقط برای `main`

Imageها در GHCR با tagهای زیر منتشر می‌شوند:

- `ghcr.io/<owner>/<repo>:develop`
- `ghcr.io/<owner>/<repo>:main`
- `ghcr.io/<owner>/<repo>:latest`
- `ghcr.io/<owner>/<repo>:sha-<commit>`

## GitHub Secrets

در GitHub repository به مسیر زیر بروید:

```text
Settings > Secrets and variables > Actions
```

secrets لازم:

```text
PRODUCTION_HOST=your.server.ip.or.domain
PRODUCTION_USER=deploy
PRODUCTION_PATH=/opt/hezarrial
PRODUCTION_SSH_KEY=<private ssh key for deploy user>
```

اگر GHCR package خصوصی باشد، روی VPS یک token فقط با دسترسی `read:packages` بسازید و با همان user لاگین کنید. اگر package عمومی باشد، login لازم نیست.

## آماده‌سازی VPS

نمونه برای Ubuntu 24.04:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git gnupg ufw
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

ساخت deploy user:

```bash
sudo adduser deploy
sudo usermod -aG docker deploy
sudo mkdir -p /opt/hezarrial
sudo chown -R deploy:deploy /opt/hezarrial
```

Firewall:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

روی VPS با user `deploy`:

```bash
cd /opt/hezarrial
git clone https://github.com/YOUR_GITHUB_USERNAME/hezarrial.git .
git checkout main
cp deploy/production.env.example .env.production
```

فایل `.env.production` را با مقدارهای واقعی کامل کنید:

```dotenv
APP_IMAGE=ghcr.io/YOUR_GITHUB_USERNAME/hezarrial:latest
APP_KEY=base64:...
DB_PASSWORD=...
MYSQL_ROOT_PASSWORD=...
APP_DOMAIN=www.hezarrial.ir
APP_URL=https://www.hezarrial.ir
```

کلید production را بیرون از repo بسازید:

```bash
docker run --rm -e RUN_LARAVEL_OPTIMIZE=false ghcr.io/YOUR_GITHUB_USERNAME/hezarrial:latest php artisan key:generate --show
```

اولین deploy دستی:

```bash
./deploy/deploy.sh
```

بعد از آن، هر push یا merge به `main` مسیر deploy را خودکار اجرا می‌کند.

## DNS و HTTPS

رکوردهای DNS دامنه باید به IP سرور اشاره کنند:

```text
A     hezarrial.ir      <server-ip>
A     www               <server-ip>
```

Caddy داخل `compose.prod.yaml` گواهی TLS را خودکار می‌گیرد. پورت‌های 80 و 443 باید باز باشند.

## عملیات روزمره روی VPS

وضعیت سرویس‌ها:

```bash
cd /opt/hezarrial
docker compose --env-file .env.production -f compose.prod.yaml ps
```

Logها:

```bash
docker compose --env-file .env.production -f compose.prod.yaml logs -f web
docker compose --env-file .env.production -f compose.prod.yaml logs -f queue
```

اجرای Artisan:

```bash
docker compose --env-file .env.production -f compose.prod.yaml exec -T web php artisan route:list
docker compose --env-file .env.production -f compose.prod.yaml exec -T web php artisan app:recalculate-balances
```

Backup دیتابیس:

```bash
docker compose --env-file .env.production -f compose.prod.yaml exec -T mysql sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > hezarrial-$(date +%F).sql
```

## Rollback

اگر deploy جدید مشکل داشت، tag قبلی image را در `.env.production` قرار دهید:

```dotenv
APP_IMAGE=ghcr.io/YOUR_GITHUB_USERNAME/hezarrial:sha-OLD_COMMIT
```

سپس:

```bash
./deploy/deploy.sh
```

اگر migration برگشت‌پذیر نیست، قبل از rollback حتما backup دیتابیس را بررسی کنید.

## نکات امنیتی

- `.env.production` و کلیدهای SSH را commit نکنید.
- برای deploy user فقط SSH key-based login فعال باشد.
- روی GitHub، محیط `production` را با required reviewers محافظت کنید اگر می‌خواهید deploy نهایی دستی تایید شود.
- اگر repository عمومی است، self-hosted runner را برای pull requestها فعال نکنید.
- برای GHCR خصوصی، token فقط با `read:packages` روی VPS کافی است.
- `APP_DEBUG=false` و `SESSION_SECURE_COOKIE=true` در production الزامی است.
- backupهای اپلیکیشن در volume خصوصی `app-storage` نگهداری می‌شوند و از public web root سرو نمی‌شوند.
