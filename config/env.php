<?php
/**
 * Supported mode list:
 * - development : all errors will appear
 * - production : silent error
 */
$env = 'production';

/**
 * Environment mode if
 * incoming ip has registered at
 * $ip_range and $based_on_ip = true
 */
$conditional_environment = 'production';

/**
 * Show error only for some ip
 * based on ip range
 */
$based_on_ip = false;

/**
 * Range IP will be impacted with
 * environment mode
 */
$range_ip = [''];

if ($based_on_ip) {
    // For load balancing or Reverse Proxy
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $range_ip)) {
        $env = $conditional_environment;
    } else if (in_array($_SERVER['REMOTE_ADDR']??'', $range_ip)) {
        $env = $conditional_environment;
    }
}

/**
 * Cli environment
 */
if (php_sapi_name() === 'cli') {
    $env = $conditional_environment !== $env ? $conditional_environment : $env;
}