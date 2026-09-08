<?php
/**
 * Security Configuration for SLiMS AKTI
 * This file should be included at the beginning of sysconfig.inc.php
 */

// Security settings for production
if (!defined('DEVELOPMENT_MODE')) {
    // Disable error display in production
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    
    // Enable error logging
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    
    // Hide PHP version
    ini_set('expose_php', '0');
    
    // Disable dangerous functions
    ini_set('allow_url_fopen', '0');
    ini_set('allow_url_include', '0');
    
    // Session security
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Additional security settings
    ini_set('magic_quotes_gpc', '0');
    ini_set('register_globals', '0');
}

// Security headers
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
           "style-src 'self' 'unsafe-inline'; " .
           "img-src 'self' data:; " .
           "font-src 'self'; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none';";
    header("Content-Security-Policy: $csp");
}

// Input sanitization function
if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        if (is_array($input)) {
            return array_map('sanitize_input', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// SQL injection protection function
if (!function_exists('escape_sql')) {
    function escape_sql($input, $connection) {
        if (is_numeric($input)) {
            return intval($input);
        }
        return $connection->real_escape_string($input);
    }
}

// CSRF protection function
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Rate limiting function
if (!function_exists('check_rate_limit')) {
    function check_rate_limit($action, $limit = 10, $window = 60) {
        $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
        $current = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => $current];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if window expired
        if ($current - $data['start'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'start' => $current];
            return true;
        }
        
        // Check limit
        if ($data['count'] >= $limit) {
            return false;
        }
        
        // Increment count
        $_SESSION[$key]['count']++;
        return true;
    }
}

// File upload security
if (!function_exists('secure_file_upload')) {
    function secure_file_upload($file, $allowed_types = [], $max_size = 1048576) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File too large';
        }
        
        // Check file type
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon'
        ];
        
        if (isset($allowed_mimes[$file_type]) && $mime_type !== $allowed_mimes[$file_type]) {
            $errors[] = 'File MIME type mismatch';
        }
        
        // Check for executable content
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<\?=|<script|javascript:|vbscript:/i', $content)) {
            $errors[] = 'File contains executable content';
        }
        
        return empty($errors) ? true : $errors;
    }
}

// Log security events
if (!function_exists('log_security_event')) {
    function log_security_event($event, $details = '') {
        $log_file = __DIR__ . '/logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $log_entry = "[$timestamp] IP: $ip | Event: $event | Details: $details | User-Agent: $user_agent\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Set proper permissions for logs directory
chmod($logs_dir, 0755);
