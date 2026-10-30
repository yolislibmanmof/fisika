<?php
/**
 * Konfigurasi & Fungsi Global — Program Studi Fisika
 * Versi aman untuk production
 */

$isLocal = ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1'
    || ($_SERVER['REMOTE_ADDR'] ?? '') === '::1'
    || (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);

define('ENVIRONMENT', $isLocal ? 'development' : 'production');

// session hardening
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.gc_maxlifetime', '3600');
ini_set('session.sid_length', '48');

session_start();

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'fisika_user';
$dbPass = getenv('DB_PASS') ?: 'ubah_password_anda';
$dbName = getenv('DB_NAME') ?: 'fisika';

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php-error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php-error.log');
}

if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (ENVIRONMENT === 'development') {
        die('Koneksi database gagal: ' . $e->getMessage());
    }
    http_response_code(500);
    die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
}

define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}
