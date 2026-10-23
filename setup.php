<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/security_v2.php';

// Check if admin exists
$res = $conn->query("SELECT COUNT(*) AS c FROM users");
$count = (int)$res->fetch_assoc()['c'];

if ($count > 0) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    // Validate
    if (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter';
    } elseif (strlen($password) < 12) {
        $error = 'Password minimal 12 karakter (keamanan premium!)';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password harus mengandung huruf besar, kecil, dan angka';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid';
    } else {
        try {
            $hash = sec_hash_password($password);
            $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->bind_param('ssss', $name, $username, $email, $hash);
            $stmt->execute();
            
            $success = 'Admin berhasil dibuat! Anda akan diarahkan ke login...';
            header('refresh:3;url=admin/login.php');
        } catch (Throwable $e) {
            $error = 'Error: ' . $e->getMessage();
            sec_log_error('Setup failed', ['error' => $e->getMessage()]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Administrator - Prodi Fisika</title>
    <link rel="stylesheet" href="assets/public/css/design-tokens.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--gradient-dark);
            font-family: var(--font-body);
            padding: var(--space-6);
        }
        .setup-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: var(--space-10);
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-xl);
        }
        .setup-card h1 {
            font-family: var(--font-display);
            margin: 0 0 var(--space-4) 0;
            color: var(--text-primary);
        }
        .setup-card p {
            color: var(--text-secondary);
            margin-bottom: var(--space-6);
        }
        .form-group {
            margin-bottom: var(--space-4);
        }
        .form-group label {
            display: block;
            margin-bottom: var(--space-2);
            font-weight: 600;
            color: var(--text-primary);
        }
        .form-group input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 15px;
            transition: border-color var(--transition-base);
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px var(--brand-primary-light);
        }
        .password-strength {
            height: 4px;
            background: var(--border);
            border-radius: var(--radius-full);
            margin-top: var(--space-2);
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all var(--transition-base);
        }
        .alert {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }
        .btn-setup {
            width: 100%;
            padding: var(--space-4);
            background: var(--gradient-brand);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1>🔐 Setup Administrator</h1>
        <p>Buat akun administrator pertama untuk mengelola website Prodi Fisika.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required minlength="3" value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required minlength="12" id="password" oninput="checkStrength(this.value)">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText" style="color: var(--text-tertiary); font-size: 12px;">Minimal 12 karakter dengan huruf besar, kecil, dan angka</small>
            </div>
            
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirm" required minlength="12">
            </div>
            
            <button type="submit" class="btn-setup">Buat Administrator</button>
        </form>
    </div>
    
    <script>
    function checkStrength(password) {
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        let strength = 0;
        
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        const colors = ['#EF4444', '#F59E0B', '#F59E0B', '#10B981', '#10B981'];
        const texts = ['Sangat Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
        
        bar.style.width = (strength * 20) + '%';
        bar.style.background = colors[strength - 1] || '#E5E7EB';
        text.textContent = strength > 0 ? texts[strength - 1] : 'Minimal 12 karakter dengan huruf besar, kecil, dan angka';
    }
    </script>
</body>
</html>