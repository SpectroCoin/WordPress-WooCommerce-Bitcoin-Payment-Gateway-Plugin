<?php
// bootstrap.php
require_once __DIR__ . '/../vendor/autoload.php';

// Check if the required environment variables are not already set
if (!isset($_SERVER['PROJECT_ID']) || !isset($_SERVER['CLIENT_ID']) || !isset($_SERVER['CLIENT_SECRET'])) {
    // Load the local .env file using DotenvVault
    \DotenvVault\DotenvVault::createImmutable(__DIR__, '.env')->safeLoad();
}
