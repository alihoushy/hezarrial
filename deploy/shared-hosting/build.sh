#!/usr/bin/env bash
# Builds an upload-ready zip for a shared host without SSH (docs/shared-hosting.md).
#
#   deploy/shared-hosting/build.sh               # document root set to hezarrial/public
#   deploy/shared-hosting/build.sh public_html   # app beside public_html, public/ moved into it
#
# Packages the committed HEAD (not the working tree) with production vendor/ and
# compiled assets into dist/. Extract the zip in the host's home directory.
set -euo pipefail

LAYOUT="${1:-docroot}"
case "$LAYOUT" in
    docroot|public_html) ;;
    *) echo "Usage: $0 [docroot|public_html]" >&2; exit 64 ;;
esac

for tool in git php composer npm zip; do
    command -v "$tool" >/dev/null || { echo "Missing required tool: $tool" >&2; exit 1; }
done

ROOT="$(git -C "$(dirname "$0")" rev-parse --show-toplevel)"

# The package is built from HEAD, so the shared-hosting files must be committed.
REQUIRED=(cron/_runner.php cron/_functions.php cron/deploy.php cron/process-recurring-transactions.php deploy/shared-hosting/env.example deploy/shared-hosting/public_html-index.php)
MISSING=()
for file in "${REQUIRED[@]}"; do
    git -C "$ROOT" cat-file -e "HEAD:$file" 2>/dev/null || MISSING+=("$file")
done
if (( ${#MISSING[@]} )); then
    echo "Error: these files are not in HEAD (the package is built from the last commit):" >&2
    printf '  %s\n' "${MISSING[@]}" >&2
    echo "Commit them first, e.g.: git add cron deploy/shared-hosting && git commit" >&2
    exit 1
fi

if [[ -n "$(git -C "$ROOT" status --porcelain)" ]]; then
    echo "Warning: uncommitted changes are NOT included; the package is built from HEAD:" >&2
    git -C "$ROOT" status --short >&2
fi

RELEASE="$(date +%Y%m%d-%H%M%S)-$(git -C "$ROOT" rev-parse --short HEAD)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
APP="$WORK/hezarrial"

echo "==> Exporting HEAD"
mkdir -p "$APP"
git -C "$ROOT" archive HEAD | tar -x -C "$APP"

echo "==> Installing production Composer dependencies"
(cd "$APP" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress)

echo "==> Building assets"
(cd "$APP" && npm ci --no-audit --no-fund && npm run build)

echo "==> Trimming files the host does not need"
cp "$APP/deploy/shared-hosting/env.example" "$WORK/env.example"
cp "$APP/deploy/shared-hosting/public_html-index.php" "$WORK/public_html-index.php"
rm -rf \
    "$APP"/{node_modules,tests,docs,docker,deploy,.github,.claude,scripts} \
    "$APP"/resources/{css,js} \
    "$APP"/{Dockerfile,compose.prod.yaml,.dockerignore,phpunit.xml,package.json,package-lock.json,vite.config.ts,tsconfig.json,components.json} \
    "$APP"/{.editorconfig,.gitattributes,.gitignore,.npmrc,.nvmrc,sms-samples.txt.example,README.md}
mv "$WORK/env.example" "$APP/.env.example"
echo "$RELEASE" > "$APP/RELEASE"

PACKAGE_DIRS=(hezarrial)
if [[ "$LAYOUT" == public_html ]]; then
    mv "$APP/public" "$WORK/public_html"
    mv "$WORK/public_html-index.php" "$WORK/public_html/index.php"
    PACKAGE_DIRS+=(public_html)
fi

mkdir -p "$ROOT/dist"
OUT="$ROOT/dist/hezarrial-$RELEASE-$LAYOUT.zip"
(cd "$WORK" && zip -qr -X "$OUT" "${PACKAGE_DIRS[@]}")

echo "==> Built $OUT ($(du -h "$OUT" | cut -f1))"
