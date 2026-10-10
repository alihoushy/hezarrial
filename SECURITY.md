# Security policy / سیاست امنیتی

## Reporting a vulnerability / گزارش آسیب‌پذیری

Please **do not open a public issue** for a security problem.
Report it privately through GitHub:
<https://github.com/alihoushy/hezarrial/security/advisories/new>

لطفاً مشکل امنیتی را به‌صورت issue عمومی گزارش نکنید. از لینک بالا (گزارش خصوصی GitHub) استفاده کنید.

Include what you found, how to reproduce it, and the impact you expect.
We aim to reply within 5 days and to fix confirmed issues quickly, crediting
you in the release notes if you wish.

## Scope / دامنه

In scope: this repository and the hosted service at `hezarrial.ir`, for example
authentication, access to another user's data, injection, and leaks of
financial data or backups.

Out of scope: denial of service by sheer traffic, social engineering, and
issues that need a rooted device or an already-compromised account.

## Supported versions / نسخه‌های پشتیبانی‌شده

Only the latest release on `main` receives security fixes.

## What the app does to protect data / اقدامات فعلی

- Passwords are hashed with Argon2id; sessions are encrypted and idle sessions expire.
- Strict CSP with per-request nonces, HSTS (production), and other security headers.
- Every record is scoped to its owner and checked by policies.
- Only the last four digits of a card are stored; one-time bank codes are discarded.
- Backups and bank SMS text are encrypted at rest.
- Dependencies are audited in CI and updated through Dependabot.
