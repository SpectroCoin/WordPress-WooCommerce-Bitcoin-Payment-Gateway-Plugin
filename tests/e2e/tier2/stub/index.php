<?php
/**
 * A stand-in for the SpectroCoin merchant API, serving the three endpoints the
 * plugin calls. It exists so the payment flow can be driven end to end without
 * touching the real API: no credentials, no live orders, and every status in
 * the contract reachable on demand.
 *
 * It answers on https://spectrocoin.com/ inside the compose network, so the
 * plugin's shipped Config constants are used unmodified.
 *
 * Endpoints mirrored from the real API:
 *   POST /api/public/oauth/token
 *   POST /api/public/merchants/orders/create
 *   GET  /api/public/merchants/orders/{uuid}
 *
 * Plus a control surface the test drives it with, namespaced so it cannot be
 * confused for part of the contract:
 *   POST /__test/status     patch an order (status, orderId, amount, currency)
 *   GET  /__test/requests   everything the plugin has sent us
 *   POST /__test/reset      forget all state
 */

declare(strict_types=1);

const STATE = '/tmp/stub-state.json';

function state(): array
{
    return file_exists(STATE)
        ? (json_decode((string) file_get_contents(STATE), true) ?: [])
        : ['orders' => [], 'requests' => []];
}

function save(array $s): void
{
    file_put_contents(STATE, json_encode($s, JSON_PRETTY_PRINT));
}

function body(): array
{
    return json_decode((string) file_get_contents('php://input'), true) ?: [];
}

function send(int $code, array $payload): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$s      = state();

// Requests from the plugin are recorded so the test can assert on what it
// actually sent — the payload shape and the headers, including the plugin
// User-Agent. The control surface and the healthcheck are not the plugin, and
// recording them would bury the traffic under test.
if (!str_starts_with($path, '/__test/')) {
    $s['requests'][] = [
        'method'     => $method,
        'path'       => $path,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'auth'       => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        'body'       => (string) file_get_contents('php://input'),
        'post'       => $_POST,
    ];
    save($s);
}

// ---------------------------------------------------------------- control ---
if ($path === '/__test/reset') {
    save(['orders' => [], 'requests' => []]);
    send(200, ['ok' => true]);
}

if ($path === '/__test/requests') {
    send(200, $s['requests']);
}

if ($path === '/__test/status') {
    $in = body();
    $id = $in['uuid'] ?? '';
    if (!isset($s['orders'][$id])) {
        send(404, ['error' => 'no such order']);
    }
    if (isset($in['status'])) {
        $s['orders'][$id]['status'] = $in['status'];
    }
    // Lets the test drive a settlement that disagrees with the shop's order,
    // which is how the currency, amount and payment-method guards are
    // exercised. Patching state here keeps the test free of quoting gymnastics
    // against the state file.
    foreach (['orderId', 'receiveCurrencyCode', 'receiveAmount'] as $override) {
        if (array_key_exists($override, $in)) {
            $s['orders'][$id][$override] = $in[$override];
        }
    }
    save($s);
    send(200, ['ok' => true, 'order' => $s['orders'][$id]]);
}

// ------------------------------------------------------------------- auth ---
if ($path === '/api/public/oauth/token') {
    if ($method !== 'POST') {
        send(405, ['error' => 'method not allowed']);
    }
    // The real API issues tokens against client credentials; the plugin only
    // needs them to be present and the response to carry these two fields.
    if (empty($_POST['client_id']) || empty($_POST['client_secret'])) {
        send(400, ['error' => 'invalid_client']);
    }
    send(200, [
        'access_token' => 'stub-access-token',
        'expires_in'   => 3600,
        'token_type'   => 'bearer',
    ]);
}

// ---------------------------------------------------------------- orders ---
if ($path === '/api/public/merchants/orders/create') {
    if ($method !== 'POST') {
        send(405, ['error' => 'method not allowed']);
    }
    if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer stub-access-token') {
        send(401, ['error' => 'unauthorized']);
    }

    $in = body();
    // The uuid is what the callback carries and what GET /orders/{uuid} is keyed
    // by; it is deliberately unrelated to the shop's own order id.
    $uuid = bin2hex(random_bytes(16));

    $s['orders'][$uuid] = [
        'uuid'                => $uuid,
        'orderId'             => $in['orderId'] ?? '',
        'status'              => 'NEW',
        'receiveAmount'       => (string) ($in['receiveAmount'] ?? '0'),
        'receiveCurrencyCode' => $in['receiveCurrencyCode'] ?? '',
        'callbackUrl'         => $in['callbackUrl'] ?? '',
        'projectId'           => $in['projectId'] ?? '',
    ];
    save($s);

    send(200, [
        'preOrderId'          => 'pre-' . $uuid,
        'orderId'             => $uuid,
        'validUntil'          => '2099-01-01T00:00:00Z',
        'payCurrencyCode'     => 'BTC',
        'payNetworkCode'      => 'BTC',
        'receiveCurrencyCode' => $s['orders'][$uuid]['receiveCurrencyCode'],
        'payAmount'           => '0.00100000',
        'receiveAmount'       => $s['orders'][$uuid]['receiveAmount'],
        'depositAddress'      => 'bc1qstubstubstubstubstubstubstubstubstub',
        'memo'                => '',
        'redirectUrl'         => 'https://spectrocoin.com/pay/' . $uuid,
    ]);
}

if (preg_match('#^/api/public/merchants/orders/([A-Za-z0-9\-]+)$#', $path, $m)) {
    if ($method !== 'GET') {
        send(405, ['error' => 'method not allowed']);
    }
    if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer stub-access-token') {
        send(401, ['error' => 'unauthorized']);
    }
    $order = $s['orders'][$m[1]] ?? null;
    if (!$order) {
        send(404, ['error' => 'no such order']);
    }
    send(200, $order);
}

send(404, ['error' => 'not found', 'path' => $path]);
