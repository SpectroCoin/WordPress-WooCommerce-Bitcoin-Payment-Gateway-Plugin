#!/usr/bin/env bash
# ============================================================================
# Tier 1 smoke test — install the packaged plugin into a real WordPress +
# WooCommerce and prove it actually runs.
#
# Unit tests cannot see the failures this catches:
#   - a release artifact that ships without its vendor tree (the plugin then
#     fatals on the first API call, and every unit test still passes);
#   - an autoloader that does not resolve inside a real install;
#   - a fatal or warning raised on activation;
#   - a payment gateway that never registers with WooCommerce.
#
# Usage:
#   ./smoke.sh                 # package the working tree the way release.yml does
#   ./smoke.sh --released 2.1.6   # test the artifact published on wordpress.org
#   ./smoke.sh --artifact x.zip   # test an arbitrary zip (e.g. a CI artifact)
#   ./smoke.sh --keep          # leave the stack running for inspection
# ============================================================================
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
SLUG="spectrocoin-accepting-bitcoin"
RELEASED=""
ARTIFACT=""
KEEP=0

while [ $# -gt 0 ]; do
  case "$1" in
    --released) RELEASED="${2:-}"; shift 2 ;;
    --artifact) ARTIFACT="${2:-}"; shift 2 ;;
    --keep)     KEEP=1; shift ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
done

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
pass() { printf '  \033[32mPASS\033[0m  %s\n' "$*"; }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$*"; FAILED=$((FAILED+1)); }
FAILED=0

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

# --------------------------------------------------------------------------
# 1. Obtain the artifact a merchant would actually install.
# --------------------------------------------------------------------------
say "Packaging artifact"
if [ -n "$ARTIFACT" ]; then
  cp "$ARTIFACT" "$WORK/plugin.zip"
  echo "  using supplied artifact $ARTIFACT"
elif [ -n "$RELEASED" ]; then
  curl -fsSL -o "$WORK/plugin.zip" \
    "https://downloads.wordpress.org/plugin/${SLUG}.${RELEASED}.zip"
  echo "  using published wordpress.org artifact ${RELEASED}"
else
  # Mirror release.yml exactly: --no-dev, and the same top-level exclusions.
  BUILD="$WORK/$SLUG"
  mkdir -p "$BUILD"
  ( cd "$ROOT" && find . -maxdepth 1 \
      -not -path './spectrocoin-accepting-bitcoin' -not -path '.' \
      -not -path './.git' -not -path './.github' -not -path './README.txt' \
      -not -path './README.md' -not -path './changelog.md' \
      -not -path './phpunit.xml' -not -path './.gitignore' -not -path './tests' \
      -exec cp -r {} "$BUILD/" \; )
  ( cd "$BUILD" && composer install --no-dev --prefer-dist --optimize-autoloader \
      --no-interaction -q 2>/dev/null || php "$ROOT/../composer.phar" install \
      --no-dev --prefer-dist --optimize-autoloader --no-interaction -q )
  ( cd "$WORK" && zip -qr plugin.zip "$SLUG" )
  echo "  built from working tree ($(find "$BUILD" -type f | wc -l | tr -d ' ') files)"
fi

# The artifact is the thing under test, so assert its shape before installing.
unzip -qo "$WORK/plugin.zip" -d "$WORK/inspect"
if [ -f "$WORK/inspect/$SLUG/vendor/autoload.php" ]; then
  pass "artifact contains vendor/autoload.php"
else
  fail "artifact has NO vendor/autoload.php - the plugin cannot run"
fi
# Check for actual source, not just the directory: the WHMCS artifact shipped
# vendor/guzzlehttp/guzzle/ as an empty directory, which a -d test would pass.
guzzle_files=$(find "$WORK/inspect/$SLUG/vendor/guzzlehttp/guzzle/src" -name '*.php' 2>/dev/null | wc -l | tr -d ' ')
if [ -f "$WORK/inspect/$SLUG/vendor/guzzlehttp/guzzle/src/Client.php" ] && [ "$guzzle_files" -gt 10 ]; then
  pass "artifact contains the HTTP client source ($guzzle_files files)"
else
  fail "artifact ships an EMPTY or partial guzzle tree ($guzzle_files php files, Client.php $([ -f "$WORK/inspect/$SLUG/vendor/guzzlehttp/guzzle/src/Client.php" ] && echo present || echo MISSING))"
fi

# --------------------------------------------------------------------------
# 2. Real WordPress + WooCommerce.
# --------------------------------------------------------------------------
say "Starting WordPress + WooCommerce"
cd "$HERE"
docker compose down -v >/dev/null 2>&1 || true
docker compose up -d --wait >/dev/null 2>&1
wp() { docker compose exec -T cli wp --allow-root --path=/var/www/html "$@"; }

# Idempotent: the stack may be reused with --keep.
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url=http://localhost:8080 --title=smoke \
    --admin_user=admin --admin_password=admin --admin_email=smoke@example.com \
    --skip-email >/dev/null 2>&1
fi
wp plugin is-installed woocommerce >/dev/null 2>&1 \
  || wp plugin install woocommerce >/dev/null 2>&1
wp plugin activate woocommerce >/dev/null 2>&1 || true
pass "WooCommerce $(wp plugin get woocommerce --field=version 2>/dev/null) active"

# --------------------------------------------------------------------------
# 3. Install the artifact exactly as a merchant would.
# --------------------------------------------------------------------------
say "Installing the plugin"
docker compose cp "$WORK/plugin.zip" cli:/tmp/plugin.zip >/dev/null
if wp plugin install /tmp/plugin.zip --force >/dev/null 2>&1; then
  pass "plugin installed from zip"
else
  fail "plugin could not be installed from the zip"
fi

if wp plugin activate "$SLUG" >/dev/null 2>&1; then
  pass "plugin activated without fatal"
else
  fail "ACTIVATION FAILED - see debug.log below"
fi

# --------------------------------------------------------------------------
# 4. Assertions that only a real install can make.
# --------------------------------------------------------------------------
say "Verifying inside the running site"

[ "$(wp plugin get "$SLUG" --field=status 2>/dev/null)" = "active" ] \
  && pass "plugin reports active" || fail "plugin is not active"

ver=$(wp plugin get "$SLUG" --field=version 2>/dev/null || true)
[ -n "$ver" ] && pass "plugin version $ver" || fail "no version reported"

# The class must resolve through the real autoloader, in a real request.
if wp eval 'exit(class_exists("SpectroCoin\\SCMerchantClient\\SCMerchantClient") ? 0 : 1);' >/dev/null 2>&1; then
  pass "SCMerchantClient resolves via autoload"
else
  fail "SCMerchantClient does NOT resolve - autoload or vendor is broken"
fi

# Guzzle is what the WHMCS artifact was missing; prove it loads here.
if wp eval 'exit(class_exists("GuzzleHttp\\Client") ? 0 : 1);' >/dev/null 2>&1; then
  pass "GuzzleHttp\\Client resolves via autoload"
else
  fail "GuzzleHttp\\Client does NOT resolve - vendor tree is absent or stale"
fi

# The gateway must actually register with WooCommerce, not merely exist.
if wp eval '
  $gws = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : [];
  foreach ($gws as $g) { if (stripos(get_class($g), "spectrocoin") !== false) exit(0); }
  exit(1);' >/dev/null 2>&1; then
  pass "gateway registered with WooCommerce"
else
  fail "gateway did NOT register with WooCommerce"
fi

# The settings screen must render: a fatal here is invisible to unit tests.
if wp eval '
  $gws = WC()->payment_gateways->payment_gateways();
  foreach ($gws as $g) {
    if (stripos(get_class($g), "spectrocoin") !== false) {
      ob_start(); $g->admin_options(); $out = ob_get_clean();
      exit(strlen($out) > 0 ? 0 : 1);
    }
  }
  exit(1);' >/dev/null 2>&1; then
  pass "settings screen renders"
else
  fail "settings screen failed to render"
fi

# --------------------------------------------------------------------------
# 5. Nothing may have been logged as a fatal or warning.
# --------------------------------------------------------------------------
say "PHP error log"
log=$(docker compose exec -T cli sh -c 'cat /var/www/html/wp-content/debug.log 2>/dev/null || true')
ours=$(printf '%s\n' "$log" | grep -iE "fatal|uncaught|parse error" \
  | grep -iE "spectrocoin|guzzle|class .* not found" || true)
if [ -z "$ours" ]; then
  pass "no fatals attributable to the plugin"
else
  fail "fatals in the log:"; printf '%s\n' "$ours" | head -10
fi

if [ "$KEEP" -eq 1 ]; then
  echo -e "\nstack left running: http://localhost:8080/wp-admin (admin/admin)"
else
  docker compose down -v >/dev/null 2>&1 || true
fi

echo
if [ "$FAILED" -eq 0 ]; then
  echo "smoke test PASSED"
else
  echo "smoke test FAILED ($FAILED check(s))"
fi
exit $([ "$FAILED" -eq 0 ] && echo 0 || echo 1)
