#!/usr/bin/env bash
# ============================================================================
# Tier 2 end-to-end test — configure the gateway, place a real WooCommerce
# order through it, deliver callbacks, and assert what the shop actually does.
#
# Tier 1 proves the plugin loads. This proves it *works*: that the order we
# send SpectroCoin describes the shop's order, and that every status on the
# wire moves the shop's order where it should — or deliberately leaves it
# alone.
#
# The SpectroCoin API is stood in for by a stub answering as spectrocoin.com
# inside the compose network, over TLS signed by a CA generated here. No
# credentials, no live orders, no calls to the real API — and because the alias
# does the redirection, the plugin's own Config URLs are exercised as they ship.
#
# Usage:
#   ./tier2.sh          # run the full flow
#   ./tier2.sh --keep   # leave the stack running for inspection
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

# --------------------------------------------------------------------------
# 1. A CA and a certificate for spectrocoin.com, so the plugin's own HTTPS
#    URLs resolve to the stub and verify.
# --------------------------------------------------------------------------
say "Generating certificates for the stub"
rm -rf .certs && mkdir -p .certs
openssl req -x509 -newkey rsa:2048 -nodes -days 3650 \
  -keyout .certs/ca.key -out .certs/ca.crt \
  -subj "/CN=SpectroCoin Tier2 Test CA" >/dev/null 2>&1
openssl req -newkey rsa:2048 -nodes \
  -keyout .certs/server.key -out .certs/server.csr \
  -subj "/CN=spectrocoin.com" >/dev/null 2>&1
printf 'subjectAltName=DNS:spectrocoin.com\n' > .certs/ext
openssl x509 -req -in .certs/server.csr -CA .certs/ca.crt -CAkey .certs/ca.key \
  -CAcreateserial -out .certs/server.crt -days 3650 -extfile .certs/ext >/dev/null 2>&1
chmod 644 .certs/*
[ -s .certs/server.crt ] && pass "issued a certificate for spectrocoin.com" \
  || fail "certificate generation failed"

# --------------------------------------------------------------------------
# 2. The stack.
# --------------------------------------------------------------------------
say "Starting WordPress, WooCommerce and the API stub"
docker compose down -v >/dev/null 2>&1 || true
docker compose up -d --build --wait >/dev/null 2>&1

wp()   { docker compose exec -T cli wp --allow-root --path=/var/www/html "$@"; }
stub() { docker compose exec -T spectrocoin "$@"; }

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url=http://shop.test --title=tier2 \
    --admin_user=admin --admin_password=admin --admin_email=tier2@example.com \
    --skip-email >/dev/null 2>&1
fi

# Trust the test CA. Appended rather than replacing the bundle, so plugin
# downloads from wordpress.org keep working.
for svc in wordpress cli; do
  docker compose exec -T -u 0 "$svc" sh -c \
    'cat /certs/ca.crt >> /etc/ssl/certs/ca-certificates.crt' >/dev/null 2>&1 || true
done

wp plugin is-installed woocommerce >/dev/null 2>&1 || wp plugin install woocommerce >/dev/null 2>&1
wp plugin activate woocommerce >/dev/null 2>&1 || true
pass "WooCommerce $(wp plugin get woocommerce --field=version 2>/dev/null) active"

# The gateway refuses currencies SpectroCoin does not settle in.
wp option update woocommerce_currency EUR >/dev/null 2>&1

# --------------------------------------------------------------------------
# 3. The plugin, built the way release.yml builds it.
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

# Configure the gateway as a merchant would through the settings screen.
wp option update woocommerce_spectrocoin_settings --format=json '{
  "enabled":"yes",
  "title":"Pay with SpectroCoin",
  "description":"",
  "project_id":"tier2-project",
  "client_id":"tier2-client",
  "client_secret":"tier2-secret",
  "order_status":"wc-completed",
  "display_logo":"yes",
  "hide_from_checkout":"no"
}' >/dev/null 2>&1

if wp eval '
  $gws = WC()->payment_gateways->payment_gateways();
  exit(isset($gws["spectrocoin"]) && $gws["spectrocoin"]->is_available() ? 0 : 1);' >/dev/null 2>&1; then
  pass "gateway is configured and available at checkout"
else
  fail "gateway is NOT available - settings were rejected"
fi

# --------------------------------------------------------------------------
# 4. Place a real order through the gateway.
# --------------------------------------------------------------------------
say "Placing an order through the gateway"
stub curl -fsS -X POST http://localhost/__test/reset >/dev/null 2>&1

# process_payment() is what WooCommerce calls when the shopper submits
# checkout: it mints a token, creates the SpectroCoin order, and hands back
# the redirect.
result=$(wp eval '
  $order = wc_create_order();
  $order->set_currency("EUR");
  $item = new WC_Order_Item_Fee();
  $item->set_name("Tier 2 test item");
  $item->set_total("12.34");
  $order->add_item($item);
  $order->set_payment_method("spectrocoin");
  $order->calculate_totals();
  $order->save();

  $gw  = WC()->payment_gateways->payment_gateways()["spectrocoin"];
  $res = $gw->process_payment($order->get_id());
  echo json_encode([
    "wc_order_id" => $order->get_id(),
    "result"      => $res["result"]   ?? "",
    "redirect"    => $res["redirect"] ?? "",
    "status"      => wc_get_order($order->get_id())->get_status(),
    "total"       => wc_get_order($order->get_id())->get_total(),
  ]);' 2>/dev/null || echo '{}')

# Read it as JSON: PHP escapes the slashes in URLs, so a sed capture yields
# "https:\/\/..." and every comparison against it silently fails.
rfield() { printf '%s' "$result" | python3 -c "import json,sys;print(json.load(sys.stdin).get('$1',''))" 2>/dev/null; }
WC_ORDER_ID=$(rfield wc_order_id)
PAY_RESULT=$(rfield result)
REDIRECT=$(rfield redirect)
AFTER_STATUS=$(rfield status)

[ "$PAY_RESULT" = "success" ] \
  && pass "process_payment succeeded (WC order #$WC_ORDER_ID)" \
  || fail "process_payment did not succeed: $result"

case "$REDIRECT" in
  https://spectrocoin.com/pay/*) pass "shopper is redirected to the payment page" ;;
  *) fail "unexpected redirect: '$REDIRECT'" ;;
esac

[ "$AFTER_STATUS" = "pending" ] \
  && pass "order is left pending payment" \
  || fail "order status after checkout is '$AFTER_STATUS', expected pending"

# --------------------------------------------------------------------------
# 5. What the plugin actually sent us.
# --------------------------------------------------------------------------
say "Inspecting the request the plugin sent"
stub curl -fsS http://localhost/__test/requests > "$WORK/requests.json" 2>/dev/null

created=$(python3 - "$WORK/requests.json" <<'PY'
import json,sys
reqs = json.load(open(sys.argv[1]))
for r in reqs:
    if r["path"].endswith("/orders/create"):
        print(json.dumps({**json.loads(r["body"] or "{}"), "_ua": r["user_agent"]}))
        break
PY
)
[ -n "$created" ] || created='{}'

field() { printf '%s' "$created" | python3 -c "import json,sys;print(json.load(sys.stdin).get('$1',''))"; }

[ "$(field receiveCurrencyCode)" = "EUR" ] \
  && pass "order was sent in the shop's currency" \
  || fail "receiveCurrencyCode was '$(field receiveCurrencyCode)', expected EUR"

[ "$(field receiveAmount)" = "12.34" ] \
  && pass "order was sent for the shop's total (12.34)" \
  || fail "receiveAmount was '$(field receiveAmount)', expected 12.34"

case "$(field orderId)" in
  "$WC_ORDER_ID"-*) pass "orderId carries the shop's order id" ;;
  *) fail "orderId '$(field orderId)' does not start with $WC_ORDER_ID-" ;;
esac

case "$(field callbackUrl)" in
  *wc-api=spectrocoin_callback*) pass "callbackUrl points at the plugin's endpoint" ;;
  *) fail "unexpected callbackUrl: '$(field callbackUrl)'" ;;
esac

[ "$(field projectId)" = "tier2-project" ] \
  && pass "projectId is the configured one" \
  || fail "projectId was '$(field projectId)'"

# The plugin User-Agent is how a platform's install base is measured; nothing
# else verifies that it survives to the wire.
case "$(field _ua)" in
  SpectroCoin-WooCommerce/*) pass "identifies itself as $(field _ua)" ;;
  *) fail "User-Agent was '$(field _ua)', expected SpectroCoin-WooCommerce/<version>" ;;
esac

# The uuid the stub minted is the key every callback refers to.
UUID=$(docker compose exec -T spectrocoin sh -c \
       "php -r '\$s=json_decode(file_get_contents(\"/tmp/stub-state.json\"),true); echo array_key_first(\$s[\"orders\"]);'" 2>/dev/null)
[ -n "$UUID" ] && pass "SpectroCoin order created (uuid ${UUID:0:8}…)" \
               || fail "no SpectroCoin order was created"

# --------------------------------------------------------------------------
# 6. Deliver callbacks and assert what the shop does with each status.
# --------------------------------------------------------------------------
say "Delivering callbacks for every status on the wire"

# Delivered from the stub container: in production the callback comes from
# SpectroCoin's server, not from the shopper's browser, and only that container
# can resolve the shop's dotted hostname.
CB="http://shop.test/?wc-api=spectrocoin_callback"
shopcurl() { docker compose exec -T spectrocoin curl "$@"; }

# Sets the status the stub reports, delivers the callback, and echoes
# "<http code> <resulting wc status>".
deliver() {
  local status="$1" uuid="${2:-$UUID}"
  stub curl -fsS -X POST -H 'Content-Type: application/json' \
    -d "{\"uuid\":\"$uuid\",\"status\":\"$status\"}" \
    http://localhost/__test/status >/dev/null 2>&1
  local code
  code=$(shopcurl -s -o /dev/null -w '%{http_code}' -X POST \
    -H 'Content-Type: application/json' \
    -d "{\"id\":\"$uuid\",\"merchantApiId\":\"tier2-api\"}" "$CB")
  printf '%s %s' "$code" "$(wp eval "echo wc_get_order($WC_ORDER_ID)->get_status();" 2>/dev/null)"
}

# Resets the order so each status is judged from the same starting point.
reset_order() {
  wp eval "\$o = wc_get_order($WC_ORDER_ID); \$o->set_status('pending'); \$o->save();" >/dev/null 2>&1
}

check_status() {
  local status="$1" want="$2" note="${3:-}"
  reset_order
  local got; got=$(deliver "$status")
  if [ "$got" = "200 $want" ]; then
    pass "$status -> $want${note:+ ($note)}"
  else
    fail "$status gave '$got', expected '200 $want'${note:+ ($note)}"
  fi
}

check_status NEW     pending
check_status PENDING pending
check_status PAID    completed "the configured order status"
check_status FAILED          failed
check_status CANCELLED       failed
check_status REJECTED        failed
check_status INVALID_PAYMENT failed
check_status EXPIRED         failed

# Informational statuses report on a payment already under way. The order must
# be left exactly as it was: transitioning here would either fulfil an order
# that was not paid in full, or reverse one the merchant already settled.
for s in PARTIAL_PAYMENT UNDERPAID LATE_CRYPTO_PAYMENT PENDING_LATE_CRYPTO_PAYMENT \
         PROCESSING_REFUND REFUNDED REJECTED_REFUND TEST TEST_PAID TEST_EXPIRED; do
  check_status "$s" pending "informational, no change"
done

# --------------------------------------------------------------------------
# 7. The callback endpoint is a public URL. It must refuse the obvious abuse.
# --------------------------------------------------------------------------
say "Callback endpoint guards"

code=$(shopcurl -s -o /dev/null -w '%{http_code}' "$CB")
[ "$code" = "405" ] && pass "GET is refused (405)" \
                    || fail "GET returned $code, expected 405 - the callback must be POST-only"

code=$(shopcurl -s -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' \
  -d '{"id":"does-not-exist","merchantApiId":"tier2-api"}' "$CB")
if [ "$code" = "400" ] || [ "$code" = "404" ]; then
  pass "a callback for an unknown order is refused ($code)"
else
  fail "unknown order returned $code, expected 400 or 404"
fi

# An order placed through a different gateway must not be settleable by a
# SpectroCoin callback.
OTHER=$(wp eval '
  $o = wc_create_order();
  $o->set_currency("EUR");
  $i = new WC_Order_Item_Fee(); $i->set_name("other"); $i->set_total("12.34");
  $o->add_item($i);
  $o->set_payment_method("cheque");
  $o->calculate_totals(); $o->save();
  echo $o->get_id();' 2>/dev/null)
patch_order() {
  stub curl -fsS -X POST -H 'Content-Type: application/json' -d "$1" \
    http://localhost/__test/status >/dev/null 2>&1
}
# Point the SpectroCoin order at the cheque order and report it paid.
patch_order "{\"uuid\":\"$UUID\",\"status\":\"PAID\",\"orderId\":\"$OTHER-aaaaaa\"}"
code=$(shopcurl -s -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' \
  -d "{\"id\":\"$UUID\",\"merchantApiId\":\"tier2-api\"}" "$CB")
other_status=$(wp eval "echo wc_get_order($OTHER)->get_status();" 2>/dev/null)
if [ "$code" = "400" ] && [ "$other_status" != "completed" ]; then
  pass "a callback cannot settle an order paid by another gateway ($code)"
else
  fail "callback returned $code and left order #$OTHER as '$other_status' - it settled a foreign order"
fi

# Restore the mapping, then disagree about the currency.
patch_order "{\"uuid\":\"$UUID\",\"orderId\":\"$WC_ORDER_ID-aaaaaa\",\"receiveCurrencyCode\":\"USD\"}"
reset_order
code=$(shopcurl -s -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' \
  -d "{\"id\":\"$UUID\",\"merchantApiId\":\"tier2-api\"}" "$CB")
now=$(wp eval "echo wc_get_order($WC_ORDER_ID)->get_status();" 2>/dev/null)
if [ "$code" = "400" ] && [ "$now" = "pending" ]; then
  pass "a settlement in the wrong currency is refused (400)"
else
  fail "currency mismatch returned $code and left the order '$now'"
fi

# --------------------------------------------------------------------------
# 8. Nothing may have been logged as a fatal.
# --------------------------------------------------------------------------
say "PHP error log"
log=$(docker compose exec -T cli sh -c 'cat /var/www/html/wp-content/debug.log 2>/dev/null || true')
ours=$(printf '%s\n' "$log" | grep -iE "fatal|uncaught|parse error" \
  | grep -iE "spectrocoin|guzzle|class .* not found" || true)
[ -z "$ours" ] && pass "no fatals attributable to the plugin" \
  || { fail "fatals in the log:"; printf '%s\n' "$ours" | head -10; }

if [ "$KEEP" -eq 1 ]; then
  echo -e "\nstack left running: add '127.0.0.1 shop.test' to /etc/hosts, then"
  echo    "http://shop.test:8086/wp-admin (admin/admin)"
else
  docker compose down -v >/dev/null 2>&1 || true
  rm -rf .certs
fi

echo
[ "$FAILED" -eq 0 ] && echo "tier 2 PASSED" || echo "tier 2 FAILED ($FAILED check(s))"
exit $([ "$FAILED" -eq 0 ] && echo 0 || echo 1)
