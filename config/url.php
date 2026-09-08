<?php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envContent);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
} else {
    $env = [];
}

return [
    /**
     * Configure base path of SLiMS.
     * 
     * If you setup your SLiMS behind reverse proxy
     * like nginx and take it with subfolder strategy
     * (e.g foo.com/slims/) this value must be fill up
     * with your path config in reverse proxy server
     */
    'base' => isset($env['BASE_URL']) ? rtrim($env['BASE_URL'], '/') . '/' : 'https://e-library.akti.ac.id/',

    /**
     * Force Http schema
     * 
     * By default SLiMS url generator by default 
     * generate Http schema with only http:// if SLiMS
     * running on standart http port or SLiMS behind
     * proxy server. Set it to 'enable'.
     */
    'force_https' => isset($env['HTTPS_ENABLE']) ? ($env['HTTPS_ENABLE'] === 'true') : true
];
