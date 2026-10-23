<?php
/**
 * Konfigurasi & Fungsi Global — Program Studi Fisika (v2.2 FINAL)
 * Kompatibel PHP 8+ | Laragon 8.2
 * + Security hardening v1.0
 * + Premium features: Caching, Environment Detection, Enhanced Error Handling
 * 
 * v2.2 FINAL: Conflict-free dengan includes/Cache.php
 */

/* ===== PREMIUM: Environment Detection ===== */
$isLocal = ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1' || 
           ($_SERVER['REMOTE_ADDR'] ?? '') === '::1' ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

define('ENVIRONMENT', $isLocal ? 'development' : 'production');

/* ---- Session cookie hardening (wajib sebelum session_start) ---- */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.gc_maxlifetime', '3600');
ini_set('session.sid_length', '48');

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fisika');

/* ===== PREMIUM: Environment-based error reporting ===== */
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

/* ===== PREMIUM: Create logs directory if not exists ===== */
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    if (ENVIRONMENT === 'development') {
        die('Koneksi database gagal: ' . $e->getMessage() . '<br>Silakan impor <b>database.sql</b> melalui phpMyAdmin terlebih dahulu.');
    } else {
        error_log('Database connection failed: ' . $e->getMessage());
        die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
    }
}

/* Deteksi URL dasar proyek secara otomatis (aman untuk Laragon) */
$docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$basePath = str_replace($docRoot, '', $projRoot);
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace('\\', '/', $basePath));
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }

/* ===== PREMIUM: Cache Configuration ===== */
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file');
define('CACHE_PATH', __DIR__ . '/../cache/');
define('CACHE_TTL_DEFAULT', 3600);

if (!is_dir(CACHE_PATH)) {
    @mkdir(CACHE_PATH, 0755, true);
}

/* ---------- QUERY AMAN ---------- */
function q(mysqli $conn, string $sql): array {
    try {
        $r = $conn->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Throwable $e) {
        if (ENVIRONMENT === 'development') {
            error_log('Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
        }
        return [];
    }
}

function q1(mysqli $conn, string $sql): ?array {
    return q($conn, $sql)[0] ?? null;
}

function qp(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    try {
        $stmt = $conn->prepare($sql);
        if ($params !== []) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Throwable $e) {
        if (ENVIRONMENT === 'development') {
            error_log('Prepared query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
        }
        return [];
    }
}

/* Seed admin default bila tabel users masih kosong */
try {
    $res = $conn->query("SELECT COUNT(*) AS c FROM users");
    if ($res && (int)$res->fetch_assoc()['c'] === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)");
        $nama = 'Administrator'; $user = 'admin'; $role = 'admin';
        $stmt->bind_param('ssss', $nama, $user, $hash, $role);
        $stmt->execute();
    }
} catch (Throwable $e) { /* menunggu impor sql */ }

/* ---------- Fungsi bantuan ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string { return SITE_URL . '/' . ltrim($path, '/'); }

function media_url($file): string {
    if (!$file) return '';
    if (preg_match('#^https?://#i', $file)) return $file;
    return url('assets/uploads/' . $file);
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function render_content($t): string {
    return strip_tags((string)$t, '<p><br><strong><em><b><i><ul><ol><li><h2><h3><h4><blockquote><a><span>');
}

function get_setting(mysqli $conn): array {
    static $s = null;
    if ($s === null) $s = q1($conn, "SELECT * FROM settings LIMIT 1") ?? [];
    return $s;
}

function upload_file(string $field, string $prefix = 'file', int $maxBytes = 10485760): ?string {
    if (!isset($_FILES[$field])) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($f['tmp_name'])) return null;
    if ($f['size'] > $maxBytes) return null;

    $orig = basename($f['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if ($ext === '' || mb_strlen($ext) > 6) return null;

    $allow = ['jpg','jpeg','png','webp','gif','svg','mp4','webm','ico','pdf'];
    if (!in_array($ext, $allow, true)) return null;

    $mimeMap = [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
        'gif'=>'image/gif','ico'=>'image/x-icon','mp4'=>'video/mp4','webm'=>'video/webm',
        'pdf'=>'application/pdf','svg'=>'image/svg+xml',
    ];
    $realMime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (isset($mimeMap[$ext]) && $realMime !== $mimeMap[$ext]) return null;

    $check = strtolower($orig);
    if (preg_match('/\.(php|phtml|php\d|pl|py|cgi|sh|exe|bat|com|asp|aspx|jsp)(\.|$)/', $check)) return null;
    if (strpos($orig, "\0") !== false) return null;

    if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
        if (@getimagesize($f['tmp_name']) === false) return null;
    }
    if ($ext === 'svg') {
        $raw = file_get_contents($f['tmp_name']);
        if (preg_match('/<\s*script/i', $raw)) return null;
        if (preg_match('/\bon\w+\s*=/i', $raw)) return null;
        if (stripos($raw, 'javascript:') !== false) return null;
    }

    $name = $prefix . '_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = rtrim(UPLOAD_PATH, '/') . '/' . $name;
    return move_uploaded_file($f['tmp_name'], $dest) ? $name : null;
}

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        if (!isset($_SESSION['_ul_warn']) || (time() - (int)$_SESSION['_ul_warn']) > 60) {
            $_SESSION['_ul_warn'] = time();
            try {
                global $conn;
                $ip = client_ip();
                $ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 250);
                $st = $conn->prepare("INSERT INTO admin_logs (user_id,user_name,action,detail,ip,ua) VALUES (0,'tamu','unauth_access',?,?,?)");
                $script = $_SERVER['REQUEST_URI'] ?? '-';
                $st->bind_param('sss', $script, $ip, $ua);
                $st->execute();
            } catch (Throwable $e) {}
        }
        redirect('admin/login.php');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
            http_response_code(403);
            die('Token CSRF tidak valid. Silakan ulangi permintaan Anda.');
        }
    }
}

function csrf_regenerate(): string {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
        $v = $_SERVER[$h] ?? null;
        if ($v) {
            $ip = trim(explode(',', $v)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function audit_log(mysqli $conn, string $action, string $detail = ''): void {
    try {
        $uid  = (int)($_SESSION['admin_id'] ?? 0);
        $name = mb_substr((string)($_SESSION['admin_name'] ?? 'tamu'), 0, 100);
        $ip   = client_ip();
        $ua   = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 250);
        $detail = mb_substr($detail, 0, 500);
        $st = $conn->prepare("INSERT INTO admin_logs (user_id,user_name,action,detail,ip,ua) VALUES (?,?,?,?,?,?)");
        $st->bind_param('isssss', $uid, $name, $action, $detail, $ip, $ua);
        $st->execute();
    } catch (Throwable $e) { /* tabel belum ada */ }
}

/* ================================================================
   PREMIUM: FILE-BASED CACHING SYSTEM
   Dibungkus dengan class_exists() agar tidak konflik dengan
   includes/Cache.php yang lebih advanced (punya Redis support).
   
   Logic:
   - Jika Cache.php belum di-load → pakai versi file-based ini
   - Jika Cache.php sudah di-load → skip, pakai yang lebih advanced
   ================================================================ */
if (!class_exists('Cache', false)) {
    class Cache {
        private static $instance = null;
        private $prefix = 'fisika_';
        
        private function __construct() {}
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function get($key, $default = null) {
            if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) return $default;
            
            $file = $this->getCachePath($key);
            if (!file_exists($file)) return $default;
            
            $data = @unserialize(file_get_contents($file));
            if ($data === false) {
                @unlink($file);
                return $default;
            }
            if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < time()) {
                @unlink($file);
                return $default;
            }
            return $data['value'] ?? $default;
        }
        
        public function set($key, $value, $ttl = 3600) {
            if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) return false;
            
            $file = $this->getCachePath($key);
            $data = [
                'value' => $value,
                'expires' => $ttl > 0 ? time() + $ttl : 0,
                'created' => time()
            ];
            return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
        }
        
        public function delete($key) {
            $file = $this->getCachePath($key);
            return file_exists($file) ? @unlink($file) : true;
        }
        
        public function flush() {
            if (!defined('CACHE_PATH')) return;
            $files = glob(CACHE_PATH . '*.cache');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
        
        public function remember($key, $ttl, $callback) {
            $value = $this->get($key);
            if ($value !== null) return $value;
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        }
        
        private function getCachePath($key) {
            $path = defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache/';
            return $path . $this->prefix . md5($key) . '.cache';
        }
    }
}

/* ===== Cache Helper Functions (Safe Declaration) ===== */
if (!function_exists('cache_get')) {
    function cache_get($key, $default = null) {
        if (!class_exists('Cache', false)) return $default;
        return Cache::getInstance()->get($key, $default);
    }
}

if (!function_exists('cache_set')) {
    function cache_set($key, $value, $ttl = 3600) {
        if (!class_exists('Cache', false)) return false;
        return Cache::getInstance()->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember($key, $ttl, $callback) {
        if (!class_exists('Cache', false)) return $callback();
        return Cache::getInstance()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_flush')) {
    function cache_flush() {
        if (!class_exists('Cache', false)) return;
        Cache::getInstance()->flush();
    }
}

/* ===== PREMIUM: Enhanced Helper Functions ===== */
function get_environment(): string { return ENVIRONMENT; }
function is_development(): bool { return ENVIRONMENT === 'development'; }
function is_production(): bool { return ENVIRONMENT === 'production'; }

function log_error($message, $context = []) {
    $log = [
        'timestamp' => date('c'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context,
        'ip' => client_ip(),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    @error_log(json_encode($log) . "\n", 3, __DIR__ . '/../logs/error.log');
}

function log_info($message, $context = []) {
    $log = [
        'timestamp' => date('c'),
        'level' => 'INFO',
        'message' => $message,
        'context' => $context
    ];
    @error_log(json_encode($log) . "\n", 3, __DIR__ . '/../logs/info.log');
}

function get_memory_usage(): float {
    return round(memory_get_usage(true) / 1048576, 2);
}

function get_execution_time(): float {
    return round(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 4);
}

if (ENVIRONMENT === 'production') {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');