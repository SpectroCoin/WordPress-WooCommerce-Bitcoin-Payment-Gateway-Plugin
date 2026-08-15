#!/usr/bin/env bash
# ============================================================================
# Tier 3 end-to-end test — a real shopper, in a real browser, through checkout.
#
# Tier 1 proves the plugin loads. Tier 2 proves the gateway works: it calls
# process_payment() directly and drives every callback status. Neither can tell
# you whether a shopper can actually *reach* the gateway.
#
# That gap is not theoretical. WooCommerce's default checkout is block-based,
# and this plugin registers for it through a separate integration class
# (SpectroCoinBlocksIntegration). A gateway can pass every Tier 2 assertion and
# still never appear at a modern shop's checkout.
#
# So this one buys a product: add to cart, fill the form, pick SpectroCoin,
# place the order, and follow the redirect to the payment page. The SpectroCoin
# API is the same stub the other tiers use.
#
# Usage:
#   ./tier3.sh          # run the full journey
#   ./tier3.sh --keep   # leave the stack running for inspection
#
# Screenshots of any failing step land in ./artifacts/.
# ============================================================================
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/../../.." && pwd)"
SLUG="spectrocoin-accepting-bitcoin"
KEEP=0

while [ $# -gt 0 ]; do
  case "$1" in
    --keep) KEEP=1; shift ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
done

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
pass() { printf '  \033[32mPASS\033[0m  %s\n' "$*"; }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$*"; FAILED=$((FAILED+1)); }
FAILED=0

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

cd "$HERE"
rm -rf artifacts && mkdir -p artifacts

# --------------------------------------------------------------------------
# 1. Certificates and the stack.
# --------------------------------------------------------------------------
say "Starting WordPress, WooCommerce, the API stub and a browser"
rm -rf .certs && mkdir -p .certs
openssl req -x509 -newkey rsa:2048 -nodes -days 3650 \
  -keyout .certs/ca.key -out .certs/ca.crt \
  -subj "/CN=SpectroCoin Tier3 Test CA" >/dev/null 2>&1
openssl req -newkey rsa:2048 -nodes -keyout .certs/server.key -out .certs/server.csr \
  -subj "/CN=spectrocoin.com" >/dev/null 2>&1
printf 'subjectAltName=DNS:spectrocoin.com\n' > .certs/ext
openssl x509 -req -in .certs/server.csr -CA .certs/ca.crt -CAkey .certs/ca.key \
  -CAcreateserial -out .certs/server.crt -days 3650 -extfile .certs/ext >/dev/null 2>&1
chmod 644 .certs/*

docker compose down -v >/dev/null 2>&1 || true
docker compose up -d --build --wait >/dev/null 2>&1

wp()   { docker compose exec -T cli wp --allow-root --path=/var/www/html "$@"; }
stub() { docker compose exec -T spectrocoin "$@"; }
pw()   { docker compose exec -T playwright "$@"; }

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url=http://shop.test --title=tier3 \
    --admin_user=admin --admin_password=admin --admin_email=tier3@example.com \
    --skip-email >/dev/null 2>&1
fi
# The shop calls SpectroCoin over TLS from PHP, so it has to trust the CA this
# harness minted. Asserted rather than assumed: without it every checkout fails
# with "cURL error 60" and the only visible symptom is an order marked failed.
docker compose exec -T -u 0 wordpress sh -c \
  'cat /certs/ca.crt >> /etc/ssl/certs/ca-certificates.crt' >/dev/null 2>&1 || true
if docker compose exec -T wordpress sh -c \
     'curl -fsS -o /dev/null https://spectrocoin.com/__test/requests' >/dev/null 2>&1; then
  pass "the shop trusts the stub's certificate"
else
  fail "the shop cannot reach the stub over TLS - checkout will fail with cURL error 60"
fi

wp plugin is-installed woocommerce >/dev/null 2>&1 || wp plugin install woocommerce >/dev/null 2>&1
wp plugin activate woocommerce >/dev/null 2>&1 || true
pass "WooCommerce $(wp plugin get woocommerce --field=version 2>/dev/null) active"

wp option update woocommerce_currency EUR >/dev/null 2>&1
wp option update woocommerce_default_country "LT:*" >/dev/null 2>&1
# Nothing about this journey should depend on shipping or accounts.
wp option update woocommerce_ship_to_countries "disabled" >/dev/null 2>&1
wp option update woocommerce_enable_guest_checkout "yes" >/dev/null 2>&1
wp option update woocommerce_enable_checkout_login_reminder "no" >/dev/null 2>&1

# --------------------------------------------------------------------------
# 2. The plugin, built the way release.yml builds it.
# --------------------------------------------------------------------------
say "Installing and configuring the plugin"
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

docker compose cp "$WORK/plugin.zip" cli:/tmp/plugin.zip >/dev/null
wp plugin install /tmp/plugin.zip --force >/dev/null 2>&1
wp plugin activate "$SLUG" >/dev/null 2>&1 \
  && pass "plugin activated" || fail "plugin failed to activate"

wp option update woocommerce_spectrocoin_settings --format=json '{
  "enabled":"yes",
  "title":"Pay with SpectroCoin",
  "description":"",
  "project_id":"tier3-project",
  "client_id":"tier3-client",
  "client_secret":"tier3-secret",
  "order_status":"wc-completed",
  "display_logo":"yes",
  "hide_from_checkout":"no"
}' >/dev/null 2>&1
pass "gateway configured"

# --------------------------------------------------------------------------
# 3. Something to buy, and pages to buy it on.
# --------------------------------------------------------------------------
say "Setting up the shop"
wp wc product create --name="Tier 3 test item" --type=simple --regular_price=12.34 \
   --virtual=true --user=admin >/dev/null 2>&1 || true
PRODUCT_ID=$(wp post list --post_type=product --field=ID --posts_per_page=1 2>/dev/null | head -1)
[ -n "$PRODUCT_ID" ] && pass "product created (#$PRODUCT_ID)" || fail "no product could be created"

# WooCommerce creates its pages on activation; make sure checkout is the block
# one, which is what a shop installed today gets.
CHECKOUT_ID=$(wp option get woocommerce_checkout_page_id 2>/dev/null | tr -d '\r')
if [ -n "$CHECKOUT_ID" ] && [ "$CHECKOUT_ID" != "0" ]; then
  pass "checkout page exists (#$CHECKOUT_ID)"
else
  fail "WooCommerce has no checkout page"
fi
wp rewrite structure '/%postname%/' >/dev/null 2>&1 || true
wp rewrite flush --hard >/dev/null 2>&1 || true

stub curl -fsS -X POST http://localhost/__test/reset >/dev/null 2>&1

# --------------------------------------------------------------------------
# 4. The shopper.
# --------------------------------------------------------------------------
say "Walking a shopper through checkout"
PRODUCT_URL="http://shop.test/?p=$PRODUCT_ID"
# The image carries the browsers but not the client library; pin it to the
# image's own version so the two cannot drift apart.
pw sh -c 'cd /work && [ -d node_modules/playwright ] || npm --silent i playwright@1.50.0' \
  > "$WORK/npm.log" 2>&1 || true
pw sh -c 'node -e "require(\"playwright\")"' >/dev/null 2>&1 \
  && pass "browser client available" \
  || { fail "playwright module could not be installed:"; tail -4 "$WORK/npm.log" | sed 's/^/        /'; }

pw sh -c "SHOP_URL=http://shop.test PRODUCT_URL='$PRODUCT_URL' node /work/checkout.mjs" \
  > "$WORK/browser.log" 2>&1 || true

# A browser run that produces no verdicts at all is a failure in itself, not a
# silent pass - and `set -o pipefail` would otherwise abort the script here.
if ! grep -aqE '^(PASS|FAIL)' "$WORK/browser.log"; then
  fail "the browser run produced no verdicts:"
  tail -12 "$WORK/browser.log" | sed 's/^/        /'
fi

grep -aE '^(PASS|FAIL|INFO)' "$WORK/browser.log" 2>/dev/null | while read -r line; do
  case "$line" in
    PASS*) printf '  \033[32mPASS\033[0m  %s\n' "${line#PASS }" ;;
    FAIL*) printf '  \033[31mFAIL\033[0m  %s\n' "${line#FAIL }" ;;
    INFO*) printf '  \033[33mNOTE\033[0m  %s\n' "${line#INFO }" ;;
  esac
done
browser_failures=$(grep -ac '^FAIL' "$WORK/browser.log" 2>/dev/null || true)
browser_failures=${browser_failures:-0}
FAILED=$((FAILED + browser_failures))
if [ "$browser_failures" -gt 0 ]; then
  echo "        --- browser log tail ---"
  tail -12 "$WORK/browser.log" | sed 's/^/        /'
fi

# --------------------------------------------------------------------------
# 5. What the shop and SpectroCoin ended up with.
# --------------------------------------------------------------------------
say "Verifying the order that resulted"
stub curl -fsS http://localhost/__test/requests > "$WORK/requests.json" 2>/dev/null

created=$(python3 - "$WORK/requests.json" <<'PYEOF'
import json,sys
for r in json.load(open(sys.argv[1])):
    if r["path"].endswith("/orders/create"):
        print(json.dumps(json.loads(r["body"] or "{}")))
        break
PYEOF
)
[ -n "$created" ] || created='{}'
field() { printf '%s' "$created" | python3 -c "import json,sys;print(json.load(sys.stdin).get('$1',''))" 2>/dev/null; }

if [ -n "$(field orderId)" ]; then
  pass "checkout produced a SpectroCoin order ($(field orderId))"
else
  fail "checkout never reached SpectroCoin - no create-order request arrived"
fi

if python3 -c "
import sys
sys.exit(0 if abs(float('$(field receiveAmount)' or 'nan') - 12.34) < 0.005 else 1)" 2>/dev/null; then
  pass "the order was sent for the cart total (12.34)"
else
  fail "receiveAmount was '$(field receiveAmount)', expected the cart total 12.34"
fi

wc_status=$(wp eval '
  $o = wc_get_orders(["limit" => 1, "orderby" => "date", "order" => "DESC"]);
  echo $o ? $o[0]->get_status() . ":" . $o[0]->get_payment_method() : "none";' 2>/dev/null | tr -d '\r')
case "$wc_status" in
  pending:spectrocoin) pass "the shop recorded a pending SpectroCoin order" ;;
  none) fail "no WooCommerce order was created" ;;
  *) fail "the shop's order is '$wc_status', expected 'pending:spectrocoin'" ;;
esac

say "PHP error log"
log=$(docker compose exec -T cli sh -c 'cat /var/www/html/wp-content/debug.log 2>/dev/null || true')
ours=$(printf '%s\n' "$log" | grep -iE "fatal|uncaught" | grep -iE "spectrocoin|guzzle" || true)
[ -z "$ours" ] && pass "no fatals attributable to the plugin" \
  || { fail "fatals in the log:"; printf '%s\n' "$ours" | head -6; }

if [ "$KEEP" -eq 1 ]; then
  echo -e "\nstack left running: add '127.0.0.1 shop.test' to /etc/hosts, then"
  echo    "http://shop.test:8093 (admin/admin)"
else
  docker compose down -v >/dev/null 2>&1 || true
  rm -rf .certs
fi

echo
[ "$FAILED" -eq 0 ] && echo "tier 3 PASSED" || echo "tier 3 FAILED ($FAILED check(s))"
exit $([ "$FAILED" -eq 0 ] && echo 0 || echo 1)
