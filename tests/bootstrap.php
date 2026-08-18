<?php

/*
 * A stale `bootstrap/cache/config.php` (from running `config:cache` in a
 * non-testing environment) bypasses phpunit.xml's <env> overrides entirely,
 * including DB_DATABASE=testing - Laravel stops evaluating env() once
 * config is cached, so tests would silently run against whatever database
 * was active when the cache was built. Clear it before anything boots, so
 * running tests is safe regardless of how they're invoked.
 */
$configCache = __DIR__.'/../bootstrap/cache/config.php';

if (file_exists($configCache)) {
    unlink($configCache);
}

require __DIR__.'/../vendor/autoload.php';
