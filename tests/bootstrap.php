<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DotenvVault\DotenvVault;

DotenvVault::createImmutable(__DIR__, '.env')->safeLoad();