<?php
/**
 * Konfigurasi & Fungsi Global — Program Studi Fisika (v2)
 * Kompatibel PHP 8+ | Laragon 8.2
 * + Security hardening v1.0
 */

/* ---- Session cookie hardening (wajib sebelum session_start) ---- */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');            // tolak session id tak dikenal
ini_set('session.use_only_cookies', '1');            // jangan pernah kirim di URL
ini_set('session.cookie_httponly', '1');             // JS tidak bisa baca cookie sesi
ini_set('session.cookie_samesite', 'Lax');           // anti-CSRF lintas situs
ini_set('session.cookie_secure', $isHttps ? '1' : '0'); // HTTPS-only bila tersedia
ini_set('session.gc_maxlifetime', '3600');           // sesi idle maksimum 60 menit
ini_set('session.sid_length', '48');                 // panjang session id 48 bytes

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fisika');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    die('Koneksi database gagal. Silakan impor <b>database.sql</b> melalui phpMyAdmin terlebih dahulu.');
}

/* Deteksi URL dasar proyek secara otomatis (aman untuk Laragon) */
$docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$basePath = str_replace($docRoot, '', $projRoot);
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace('\\', '/', $basePath));
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }

/* ---------- QUERY AMAN: tidak pernah memicu fatal error ---------- */
function q(mysqli $conn, string $sql): array {
    try { $r = $conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; }
    catch (Throwable $e) { return []; }
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
    } catch (Throwable $e) { return []; }
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
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
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
    if (!is_uploaded_file($f['tmp_name'])) return null;           // anti LFI
    if ($f['size'] > $maxBytes) return null;                        // batas 10 MB default

    /* Sanitasi nama asli: hilangkan path traversal & karakter aneh */
    $orig = basename($f['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if ($ext === '' || mb_strlen($ext) > 6) return null;

    /* Whitelist ekstensi */
    $allow = ['jpg','jpeg','png','webp','gif','svg','mp4','webm','ico','pdf'];
    if (!in_array($ext, $allow, true)) return null;

    /* Validasi MIME asli via magic bytes (bukan tebak-tebakan ekstensi) */
    $mimeMap = [
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
        'gif'=>'image/gif','ico'=>'image/x-icon','mp4'=>'video/mp4','webm'=>'video/webm',
        'pdf'=>'application/pdf','svg'=>'image/svg+xml',
    ];
    $realMime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (isset($mimeMap[$ext]) && $realMime !== $mimeMap[$ext]) return null;

    /* Cegah file PHP menyamar (double extension, null byte, dsb) */
    $check = strtolower($orig);
    if (preg_match('/\.(php|phtml|php\d|pl|py|cgi|sh|exe|bat|com|asp|aspx|jsp)(\.|$)/', $check)) return null;
    if (strpos($orig, "\0") !== false) return null;

    /* Gambar wajib valid (anti RCE via file gambar rusak) */
    if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
        if (@getimagesize($f['tmp_name']) === false) return null;
    }
    /* SVG wajib tanpa script/event handler */
    if ($ext === 'svg') {
        $raw = file_get_contents($f['tmp_name']);
        if (preg_match('/<\s*script/i', $raw)) return null;
        if (preg_match('/\bon\w+\s*=/i', $raw)) return null;
        if (stripos($raw, 'javascript:') !== false) return null;
    }

    /* Nama file acak kriptografis (bukan time() yang bisa diprediksi) */
    $name = $prefix . '_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = rtrim(UPLOAD_PATH, '/') . '/' . $name;
    return move_uploaded_file($f['tmp_name'], $dest) ? $name : null;
}
function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        /* Catat akses tidak sah sekali saja per IP per 60 detik */
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
/** Regenerasi token CSRF — panggil setelah operasi POST sensitif */
function csrf_regenerate(): string {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
/** IP client (aman untuk Cloudflare / proxy) */
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
/** Audit log ringkas (fallback bila security.php belum dimuat) */
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
/** Cegah error disclosure ke user */
ini_set('display_errors', '0');
ini_set('log_errors', '1');