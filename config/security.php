<?php
/**
 * =====================================================================
 *  SECURITY LIBRARY v1.0 — CMS Prodi Fisika
 *  Berkas baru: config/security.php
 *  Dipanggil sekali per request melalui require_once.
 *  Tidak menyentuh admin.css / admin.js / public.css / public.js.
 * =====================================================================
 */
if (!defined('SEC_LOADED')) {
    define('SEC_LOADED', true);

    /* ---------- Konfigurasi keamanan ---------- */
    define('SEC_MAX_ATTEMPTS', 5);       // percobaan login gagal maksimum
    define('SEC_WINDOW', 900);           // jendela waktu (detik) = 15 menit
    define('SEC_BLOCK_TIME', 1800);      // durasi blokir IP (detik) = 30 menit
    define('SEC_IDLE_TIMEOUT', 3600);    // sesi menganggur maksimum = 60 menit
    define('SEC_REGEN_INTERVAL', 900);   // regenerasi session id = 15 menit

    /* ---------- 1. SECURITY HEADERS (CSP dkk) ---------- */
    function sec_send_headers(): void
    {
        static $sent = false;
        if ($sent || headers_sent()) { $sent = true; return; }
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
             . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
             . "img-src 'self' data: blob: https:; "
             . "media-src 'self' blob: https:; "
             . "frame-src https://www.youtube.com https://youtube.com; "
             . "connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'";
        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        $sent = true;
    }

    /* ---------- 2. IP CLIENT (aman proxy/Cloudflare) ---------- */
    function sec_ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        foreach ($candidates as $c) {
            if ($c && filter_var($c, FILTER_VALIDATE_IP)) return $c;
        }
        return '0.0.0.0';
    }

    /* ---------- 3. RATE LIMITING LOGIN ---------- */
    function sec_check_rate($conn, string $ip): array
    {
        try {
            $st = $conn->prepare("SELECT blocked_until FROM blocked_ips WHERE ip=? AND blocked_until > NOW() LIMIT 1");
            $st->bind_param('s', $ip); $st->execute();
            if ($row = $st->get_result()->fetch_assoc()) {
                $mins = max(1, (int)ceil((strtotime($row['blocked_until']) - time()) / 60));
                return ['blocked' => true, 'retry_minutes' => $mins];
            }
            $w = SEC_WINDOW;
            $st = $conn->prepare("SELECT COUNT(*) AS c, MIN(attempted_at) AS first_t FROM login_attempts WHERE ip=? AND attempted_at > (NOW() - INTERVAL ? SECOND)");
            $st->bind_param('si', $ip, $w); $st->execute();
            $r = $st->get_result()->fetch_assoc();
            if ($r && (int)$r['c'] >= SEC_MAX_ATTEMPTS) {
                $elapsed = time() - strtotime($r['first_t']);
                return ['blocked' => true, 'retry_minutes' => max(1, (int)ceil((SEC_WINDOW - $elapsed) / 60))];
            }
        } catch (Throwable $e) { /* tabel belum ada: gagal terbuka */ }
        return ['blocked' => false, 'retry_minutes' => 0];
    }

    function sec_remaining_attempts($conn, string $ip): ?int
    {
        try {
            $w = SEC_WINDOW;
            $st = $conn->prepare("SELECT COUNT(*) AS c FROM login_attempts WHERE ip=? AND attempted_at > (NOW() - INTERVAL ? SECOND)");
            $st->bind_param('si', $ip, $w); $st->execute();
            $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
            return max(0, SEC_MAX_ATTEMPTS - $c);
        } catch (Throwable $e) { return null; }
    }

    function sec_record_fail($conn, string $ip, string $username): void
    {
        try {
            $username = mb_substr($username, 0, 100);
            $st = $conn->prepare("INSERT INTO login_attempts (ip, username) VALUES (?,?)");
            $st->bind_param('ss', $ip, $username); $st->execute();

            $w = SEC_WINDOW;
            $st = $conn->prepare("SELECT COUNT(*) AS c FROM login_attempts WHERE ip=? AND attempted_at > (NOW() - INTERVAL ? SECOND)");
            $st->bind_param('si', $ip, $w); $st->execute();
            $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);

            if ($c >= SEC_MAX_ATTEMPTS) {
                $reason = 'Brute-force: ' . $c . ' percobaan gagal';
                $st = $conn->prepare("INSERT INTO blocked_ips (ip, reason, blocked_until)
                        VALUES (?,?, NOW() + INTERVAL " . SEC_BLOCK_TIME . " SECOND)
                        ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_until=VALUES(blocked_until)");
                $st->bind_param('ss', $ip, $reason); $st->execute();
            }
        } catch (Throwable $e) {}
    }

    function sec_clear_attempts($conn, string $ip): void
    {
        try {
            $st = $conn->prepare("DELETE FROM login_attempts WHERE ip=?");
            $st->bind_param('s', $ip); $st->execute();
        } catch (Throwable $e) {}
    }

    /* ---------- 4. AUDIT LOG ---------- */
    function sec_log($conn, string $action, string $detail = ''): void
    {
        try {
            $uid    = (int)($_SESSION['admin_id'] ?? 0);
            $name   = (string)($_SESSION['admin_name'] ?? 'tamu');
            $ip     = sec_ip();
            $ua     = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 250);
            $detail = mb_substr($detail, 0, 500);
            $st = $conn->prepare("INSERT INTO admin_logs (user_id, user_name, action, detail, ip, ua) VALUES (?,?,?,?,?,?)");
            $st->bind_param('isssss', $uid, $name, $action, $detail, $ip, $ua);
            $st->execute();
        } catch (Throwable $e) {}
    }

    /* ---------- 5. SESSION HARDENING RUNTIME ---------- */
    function sec_touch_session(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        $now     = time();
        $script  = $_SERVER['SCRIPT_NAME'] ?? '';
        $inAdmin = (strpos($script, '/admin/') !== false) && (basename($script) !== 'login.php');

        /* Paksa keluar bila sesi menganggur terlalu lama */
        if ($inAdmin && isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > SEC_IDLE_TIMEOUT) {
            session_unset(); session_destroy();
            if (function_exists('redirect')) redirect('admin/login.php');
            header('Location: login.php'); exit;
        }
        /* Regenerasi session id berkala (anti fixation/hijack) */
        if (!isset($_SESSION['sec_regen']) || ($now - (int)$_SESSION['sec_regen']) > SEC_REGEN_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['sec_regen'] = $now;
        }
        $_SESSION['last_activity'] = $now;
    }

    /* ---------- 6. UPLOAD VALIDATOR (siap dipakai Fase 1B) ---------- */
    function sec_validate_upload(array $file, array $allowExt, int $maxBytes, string $targetDir): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
            return ['ok' => false, 'err' => 'File tidak valid.', 'name' => ''];
        if ($file['size'] > $maxBytes)
            return ['ok' => false, 'err' => 'Ukuran file melebihi batas.', 'name' => ''];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowExt, true))
            return ['ok' => false, 'err' => 'Ekstensi tidak diizinkan: .' . $ext, 'name' => ''];

        /* Verifikasi MIME sesungguhnya (bukan sekadar ekstensi) */
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $mimeMap = [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
            'gif'=>'image/gif','svg'=>'image/svg+xml','ico'=>'image/x-icon',
            'mp4'=>'video/mp4','webm'=>'video/webm','pdf'=>'application/pdf',
        ];
        if (isset($mimeMap[$ext]) && $mime !== $mimeMap[$ext])
            return ['ok' => false, 'err' => 'Isi file tidak sesuai ekstensi (MIME: ' . $mime . ').', 'name' => ''];
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true) && @getimagesize($file['tmp_name']) === false)
            return ['ok' => false, 'err' => 'File gambar rusak / bukan gambar.', 'name' => ''];

        if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], rtrim($targetDir, '/') . '/' . $name))
            return ['ok' => false, 'err' => 'Gagal menyimpan file.', 'name' => ''];
        return ['ok' => true, 'err' => '', 'name' => $name];
    }

    /* ---------- 7. KEBIJAKAN PASSWORD ---------- */
    function sec_password_policy(string $p): array
    {
        if (mb_strlen($p) < 8) return ['ok' => false, 'msg' => 'Minimal 8 karakter.'];
        if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p))
            return ['ok' => false, 'msg' => 'Wajib mengandung huruf dan angka.'];
        return ['ok' => true, 'msg' => 'Kuat'];
    }

    /* ---------- 8. ESCAPER UNTUK QUERY LIKE ---------- */
    function sec_escape_like(string $s): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }
}