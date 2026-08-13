<?php

/**
 * Invariant tests for the plugin identification header.
 *
 * Every API call carries a User-Agent naming the plugin and its version, so the
 * version actually deployed across merchant installations is visible to us
 * without having to ask anyone. Three things must hold:
 *
 *   - the header is wired into the HTTP client, not merely defined;
 *   - the advertised version matches the version this plugin really is, because
 *     a version that silently drifts is worse than no version at all;
 *   - the header carries nothing identifying about the merchant or their site.
 *
 * Read from source on purpose: this must run in a bare checkout, with no
 * platform bootstrap and no vendor directory.
 *
 * Run:  php tests/check-plugin-user-agent.php
 */

class TestRunner
{
    private $failures = array();
    private $passed = 0;
    private $failed = 0;

    public function assertTrue($cond, $message)
    {
        if (!$cond) { $this->failures[] = $message; }
    }

    public function assertSame($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            $this->failures[] = $message . ' (expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true) . ')';
        }
    }

    public function run($name, $test)
    {
        $this->failures = array();
        try { call_user_func($test, $this); }
        catch (\Throwable $e) { $this->failures[] = 'threw ' . get_class($e) . ': ' . $e->getMessage(); }
        if (empty($this->failures)) { $this->passed++; echo "  PASS  {$name}\n"; }
        else {
            $this->failed++;
            echo "  FAIL  {$name}\n";
            foreach ($this->failures as $f) { echo "          {$f}\n"; }
        }
    }

    public function summary()
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$root   = __DIR__ . '/../';
$source = file_get_contents($root . 'SCMerchantClient/SCMerchantClient.php');

function constant_in($source, $name)
{
    preg_match('/const\s+' . $name . '\s*=\s*\'([^\']*)\'/', $source, $m);
    return isset($m[1]) ? $m[1] : null;
}

$t = new TestRunner();
echo "SpectroCoin WooCommerce — plugin identification header\n\n";

$t->run('the client declares its platform and version', function ($t) use ($source) {
    $t->assertSame('WooCommerce', constant_in($source, 'PLUGIN_PLATFORM'), 'PLUGIN_PLATFORM');
    $t->assertSame('2.1.5', constant_in($source, 'PLUGIN_VERSION'), 'PLUGIN_VERSION');
});

$t->run('the header is wired into the HTTP client', function ($t) use ($source) {
    $t->assertTrue(strpos($source, 'pluginUserAgent()') !== false,
        'the client must call pluginUserAgent()');
    $t->assertTrue(strpos($source, 'User-Agent') !== false,
        'the User-Agent header must be set');
    $t->assertTrue(substr_count($source, 'pluginUserAgent') >= 2,
        'pluginUserAgent() must be defined and used, not just defined');
});

$t->run('the advertised string is well formed', function ($t) use ($source) {
    $t->assertTrue(strpos($source, "'SpectroCoin-%s/%s (PHP/%s)'") !== false,
        'the user agent format must be SpectroCoin-<platform>/<version> (PHP/<php>)');
});

$t->run('the header carries no merchant or site identity', function ($t) use ($source) {
    preg_match('/function pluginUserAgent.*?\n\t*\s*\}/s', $source, $m);
    $body = isset($m[0]) ? strtolower($m[0]) : '';
    $t->assertTrue($body !== '', 'pluginUserAgent() body must be found');
    foreach (array('http', '$this', 'client_id', 'client_secret', 'project_id', 'token', 'email') as $leak) {
        $t->assertTrue(strpos($body, $leak) === false,
            "the user agent must not reference '{$leak}'");
    }
});

$t->run('the advertised version matches the plugin version', function ($t) use ($root) {
    preg_match('/^Version:\s*(\S+)/m', file_get_contents($root . 'spectrocoin.php'), $m);
    $t->assertSame(trim($m[1]), '2.1.5',
        'spectrocoin.php and the advertised version must not drift');
});

exit($t->summary());
