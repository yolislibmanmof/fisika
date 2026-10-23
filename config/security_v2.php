<?php
/**
 * Enhanced Security Module - v2.0
 * Addressing: Default credentials, XSS, CSRF
 */

// === 1. HAPUS DEFAULT CREDENTIALS ===
function sec_bootstrap_admin($conn) {
    try {
        $res = $conn->query("SELECT COUNT(*) AS c FROM users");
        if ($res && (int)$res->fetch_assoc()['c'] === 0) {
            // TIDAK auto-seed lagi! Redirect ke setup wizard
            header('Location: setup.php');
            exit;
        }
    } catch (Throwable $e) {
        error_log('Bootstrap error: ' . $e->getMessage());
    }
}

// === 2. ENHANCED XSS PROTECTION ===
function e($str) {
    if ($str === null) return '';
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Anti-XSS untuk rich content (berita)
function render_content_safe($html) {
    // Allow only safe tags
    $allowed = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img><table><tr><td><th><thead><tbody>';
    $clean = strip_tags($html, $allowed);
    
    // Remove dangerous attributes
    $clean = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);
    $clean = preg_replace('/\s*javascript\s*:/i', '', $clean);
    $clean = preg_replace('/<\s*script/i', '&lt;script', $clean);
    
    // Add rel="noopener" to all links
    $clean = preg_replace('/<a([^>]*)>/i', '<a$1 rel="noopener noreferrer">', $clean);
    
    return $clean;
}

// === 3. SECURE SESSION CONFIG ===
function sec_configure_session() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    
    ini_set('session.name', '__FISIKA_SID'); // Custom session name
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict'); // Lebih strict
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.gc_maxlifetime', '3600');
    ini_set('session.sid_length', '64'); // Lebih panjang
    ini_set('session.sid_bits_per_character', '6');
    
    session_start();
}

// === 4. INPUT SANITIZATION ===
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// === 5. RATE LIMITING (Enhanced) ===
function sec_check_rate($conn, $ip, $action = 'login') {
    $window = 900; // 15 menit
    $maxAttempts = 5;
    
    try {
        // Check if IP is blocked
        $stmt = $conn->prepare("SELECT blocked_until FROM blocked_ips WHERE ip = ? AND blocked_until > NOW() LIMIT 1");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $minutes = ceil((strtotime($result['blocked_until']) - time()) / 60);
            return ['blocked' => true, 'retry_minutes' => $minutes];
        }
        
        // Count recent attempts
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM login_attempts WHERE ip = ? AND action = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->bind_param('ssi', $ip, $action, $window);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['c'];
        
        if ($count >= $maxAttempts) {
            // Block IP for 1 hour
            $stmt = $conn->prepare("INSERT INTO blocked_ips (ip, reason, blocked_until) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL 1 HOUR)");
            $reason = "Too many $action attempts";
            $stmt->bind_param('ss', $ip, $reason);
            $stmt->execute();
            
            return ['blocked' => true, 'retry_minutes' => 60];
        }
        
        return ['blocked' => false, 'attempts_left' => $maxAttempts - $count];
        
    } catch (Throwable $e) {
        error_log('Rate limit error: ' . $e->getMessage());
        return ['blocked' => false];
    }
}

// === 6. SECURE PASSWORD HASHING ===
function sec_hash_password($password) {
    // Use Argon2id if available, else Bcrypt
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function sec_verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// === 7. CSRF TOKEN (Enhanced) ===
function csrf_token() {
    if (empty($_SESSION['csrf']) || !isset($_SESSION['csrf_time']) || (time() - $_SESSION['csrf_time']) > 3600) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_time'] = time();
    }
    return $_SESSION['csrf'];
}

function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        sec_log_error('CSRF validation failed', [
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        die('Security token validation failed. Please refresh the page and try again.');
    }
}

// === 8. ERROR LOGGING ===
function sec_log_error($message, $context = []) {
    $log = [
        'timestamp' => date('c'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context,
        'ip' => client_ip(),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    error_log(json_encode($log) . "\n", 3, __DIR__ . '/../logs/security.log');
    
    // Optional: Send alert to admin email for critical errors
    if (defined('ADMIN_EMAIL') && strpos($message, 'critical') !== false) {
        mail(ADMIN_EMAIL, 'Security Alert: ' . $message, json_encode($log, JSON_PRETTY_PRINT));
    }
}