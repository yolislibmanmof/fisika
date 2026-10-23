<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
sec_send_headers();
if (!empty($_SESSION['admin_id'])) redirect('admin/index.php');
$err = '';
$rateBlock = sec_check_rate($conn, sec_ip());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');

    /* Honeypot: bot yang mengisi kolom tersembunyi langsung ditolak */
    if (trim($_POST['website'] ?? '') !== '') {
        sec_record_fail($conn, sec_ip(), $u);
        $err = 'Permintaan ditolak oleh sistem keamanan.';
    } elseif ($rateBlock['blocked']) {
        $err = 'Terlalu banyak percobaan gagal dari perangkat ini. Silakan coba lagi dalam ' . $rateBlock['retry_minutes'] . ' menit.';
        sec_log($conn, 'login_throttled', 'username=' . $u);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param('s', $u); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($p, $user['password'])) {
            session_regenerate_id(true);              /* anti session-fixation */
            $_SESSION['admin_id']   = (int)$user['id'];
            $_SESSION['admin_name'] = $user['nama'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['sec_regen']  = time();
            sec_clear_attempts($conn, sec_ip());
            sec_log($conn, 'login_success', 'Masuk sebagai ' . $user['username']);
            redirect('admin/index.php');
        }
        sec_record_fail($conn, sec_ip(), $u);
        $left = sec_remaining_attempts($conn, sec_ip());
        $err  = 'Username atau password tidak sesuai.'
              . ($left !== null ? ' (Sisa percobaan: ' . $left . ')' : '');
    }
}
$quotes = [
    ['Fisika adalah cara memahami alam semesta melalui bahasa matematika.', 'Richard Feynman'],
    ['Imajinasi lebih penting daripada pengetahuan.', 'Albert Einstein'],
    ['Ukur apa yang dapat diukur, dan ukur apa yang belum dapat diukur.', 'Galileo Galilei'],
    ['Saya tidak memiliki bakat khusus, saya hanya penuh rasa ingin tahu.', 'Albert Einstein'],
    ['Kebenaran ilmiah lebih berharga daripada kenyamanan.', 'Max Planck'],
];
$todayQuote = $quotes[date('d') % count($quotes)];
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$phpv = PHP_VERSION;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — Prodi Fisika</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/admin/css/admin.css') ?>">
<style>
/* ========= LOGIN ULTIMATE v4 ========= */
@keyframes l2spin{to{transform:rotate(360deg)}}
@keyframes l2float{50%{transform:translateY(-10px)}}
@keyframes l2slide{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
@keyframes l2shake{0%,100%{transform:translateX(0)}10%,30%,50%,70%,90%{transform:translateX(-8px)}20%,40%,60%,80%{transform:translateX(8px)}}
@keyframes l2pulse{0%,100%{box-shadow:0 0 0 0 rgba(0,184,116,.4)}50%{box-shadow:0 0 0 12px rgba(0,184,116,0)}}
@keyframes l2grad{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@keyframes l2star{0%{opacity:0;transform:translateY(0) scale(.5)}20%{opacity:1}100%{opacity:0;transform:translateY(-100px) scale(1.2)}}
@keyframes l2blink{50%{opacity:.4}}
@keyframes l2load{from{width:0}to{width:100%}}
@keyframes l2type{from{width:0}to{width:100%}}
@keyframes l2caret{50%{border-color:transparent}}
@keyframes l2floatUp{0%{transform:translateY(100vh) rotate(0deg);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-20vh) rotate(720deg);opacity:0}}

*{box-sizing:border-box}
html,body{margin:0;padding:0;font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased;background:#071a39;color:#101828}

body.login-body{min-height:100vh;display:flex;padding:30px 20px;position:relative;overflow-x:hidden;overflow-y:auto;background:linear-gradient(135deg,#04101f 0%,#071a39 40%,#0a2a5e 100%)}

/* Partikel background */
.l2-particles{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.l2-particle{position:absolute;width:4px;height:4px;background:rgba(126,240,196,.6);border-radius:50%;animation:l2floatUp linear infinite}
.l2-particle::after{content:'';position:absolute;inset:-4px;background:radial-gradient(circle,rgba(126,240,196,.3),transparent 70%);border-radius:50%}
.l2-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,184,116,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,184,116,.04) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0}

/* Radial glow */
.l2-glow{position:fixed;inset:0;pointer-events:none;z-index:0}
.l2-glow::before{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,116,.18),transparent 60%);top:-200px;left:-100px}
.l2-glow::after{content:'';position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(10,102,194,.2),transparent 60%);bottom:-300px;right:-200px}

.l2-wrap{position:relative;z-index:2;display:grid;grid-template-columns:1fr 480px;gap:0;width:min(1080px,100%);margin:auto;background:rgba(255,255,255,.02);backdrop-filter:blur(20px);border-radius:24px;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 40px 90px rgba(0,0,0,.4)}

/* ===== LEFT PANEL (BRANDING) ===== */
.l2-left{padding:50px 40px;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;min-height:640px}
.l2-left::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,#071a39 0%,#0a2a5e 50%,#0a66c2 100%);background-size:200% 200%;animation:l2grad 15s ease infinite;z-index:0}
.l2-left::after{content:'⚛';position:absolute;right:-40px;bottom:-40px;font-size:280px;color:rgba(255,255,255,.03);animation:l2spin 60s linear infinite;pointer-events:none}
.l2-left > *{position:relative;z-index:1}

/* Top brand */
.l2-brand{display:flex;align-items:center;gap:14px}
.l2-brand .logo-ph{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.08);backdrop-filter:blur(10px);display:grid;place-items:center;color:#fff;border:1px solid rgba(255,255,255,.15);position:relative}
.l2-brand .logo-ph::before{content:'';position:absolute;inset:-3px;border-radius:15px;border:1.5px solid rgba(0,184,116,.6);animation:l2pulse 2.5s infinite}
.l2-brand .logo-ph i{font-size:20px;color:#7ef0c4;animation:l2spin 7s linear infinite}
.l2-brand b{font-family:'Sora',var(--display);font-size:17px;color:#fff;letter-spacing:1px}
.l2-brand b span{color:#7ef0c4}
.l2-brand small{display:block;color:#9fb3cd;font-size:10px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;margin-top:2px}

/* Hero atom */
.l2-hero{text-align:center;padding:30px 0}
.l2-atom{position:relative;width:220px;height:220px;margin:0 auto 30px}
.l2-atom .nucleus{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7ef0c4,#4da3ff);box-shadow:0 0 40px rgba(126,240,196,.6),0 0 80px rgba(0,184,116,.3);animation:l2pulse 2s infinite}
.l2-atom .orbit{position:absolute;top:50%;left:50%;width:180px;height:60px;border:1.5px solid rgba(126,240,196,.35);border-radius:50%;transform:translate(-50%,-50%)}
.l2-atom .orbit i{position:absolute;top:-5px;left:50%;width:10px;height:10px;margin-left:-5px;border-radius:50%;background:#7ef0c4;box-shadow:0 0 12px #7ef0c4}
.l2-atom .o1{transform:translate(-50%,-50%) rotate(0deg);animation:l2spin 3s linear infinite}
.l2-atom .o2{transform:translate(-50%,-50%) rotate(60deg);animation:l2spin 4s linear infinite reverse}
.l2-atom .o3{transform:translate(-50%,-50%) rotate(120deg);animation:l2spin 5s linear infinite}
.l2-hero h1{font-family:'Sora',var(--display);color:#fff;font-size:36px;letter-spacing:3px;margin:0 0 8px;line-height:1.1}
.l2-hero h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.l2-hero p{color:#c8d6ea;font-size:14px;line-height:1.7;max-width:360px;margin:0 auto}

/* Features */
.l2-features{display:flex;flex-direction:column;gap:12px;margin:10px 0}
.l2-feat{display:flex;align-items:center;gap:12px;padding:12px 14px;background:rgba(255,255,255,.04);backdrop-filter:blur(10px);border-radius:12px;border:1px solid rgba(255,255,255,.08);transition:.3s}
.l2-feat:hover{background:rgba(255,255,255,.08);transform:translateX(4px)}
.l2-feat .ic{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(0,184,116,.3),rgba(77,163,255,.3));display:grid;place-items:center;color:#7ef0c4;font-size:14px;flex:none}
.l2-feat b{display:block;color:#fff;font-size:12.5px;font-family:'Sora',var(--display)}
.l2-feat small{color:#9fb3cd;font-size:11px;font-weight:600}

/* Quote */
.l2-quote{background:rgba(255,255,255,.04);backdrop-filter:blur(10px);border-radius:14px;padding:18px 20px;border:1px solid rgba(255,255,255,.08);position:relative;overflow:hidden}
.l2-quote::before{content:'"';position:absolute;top:-20px;left:10px;font-family:'Sora',var(--display);font-size:80px;color:rgba(126,240,196,.2);line-height:1}
.l2-quote p{color:#e6eefc;font-style:italic;font-size:13px;line-height:1.7;margin:0 0 8px;position:relative;z-index:1}
.l2-quote small{color:#7ef0c4;font-weight:800;letter-spacing:1.2px;font-size:10.5px;position:relative;z-index:1}

/* ===== RIGHT PANEL (FORM) ===== */
.l2-right{padding:50px 46px;background:#fff;display:flex;flex-direction:column;justify-content:center;position:relative}
.l2-right::before{content:'';position:absolute;top:0;right:0;width:200px;height:200px;background:radial-gradient(circle,rgba(0,184,116,.08),transparent 70%)}

.l2-form{position:relative;z-index:1;animation:l2slide .6s cubic-bezier(.22,.61,.36,1)}
.l2-form .head{margin-bottom:28px}
.l2-form .head .tag{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:#0a66c2;padding:6px 14px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;margin-bottom:14px;border:1px solid rgba(0,184,116,.25)}
.l2-form .head .tag i{color:#00b874}
.l2-form h2{font-family:'Sora',var(--display);font-size:28px;color:#0a2a5e;line-height:1.2;margin:0 0 8px}
.l2-form .muted{color:#68758a;font-size:14px;margin:0}

/* Honeypot (jebakan bot) — wajib tak terlihat oleh manusia */
.l2-hp{position:absolute!important;left:-99999px!important;width:1px;height:1px;opacity:0;pointer-events:none}

/* Alert */
.l2-alert{padding:13px 16px;border-radius:11px;background:#fdecec;color:#b3261e;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;margin-bottom:20px;border:1px solid #f5c2c0;animation:l2shake .5s}
.l2-alert i{font-size:16px;flex:none}
.l2-alert b{font-family:'Sora',var(--display);font-weight:700}

/* Fields */
.l2-field{margin-bottom:18px}
.l2-field label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.l2-field label i{color:#00b874;font-size:11px}
.l2-input{position:relative;display:flex;align-items:center;background:#f8fafd;border:1.6px solid var(--line);border-radius:12px;transition:.25s;overflow:hidden}
.l2-input:focus-within{border-color:#0a66c2;background:#fff;box-shadow:0 0 0 4px rgba(10,102,194,.08)}
.l2-input i{color:#9aa8bf;margin:0 14px;font-size:14px;transition:.25s}
.l2-input:focus-within i{color:#0a66c2}
.l2-input input{flex:1;padding:14px 14px 14px 0;border:0;background:transparent;font-family:inherit;font-size:14px;outline:0;color:#101828}
.l2-input input::placeholder{color:#b8c2d1}
.l2-input .toggle{border:0;background:transparent;color:#9aa8bf;cursor:pointer;padding:0 14px;font-size:14px;transition:.2s}
.l2-input .toggle:hover{color:#0a66c2}

/* Checkbox */
.l2-remember{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.l2-cb{display:flex;align-items:center;gap:9px;cursor:pointer;user-select:none;font-size:13px;color:#475569;font-weight:600}
.l2-cb input{position:absolute;opacity:0;pointer-events:none}
.l2-cb .check{width:20px;height:20px;border-radius:6px;border:1.6px solid var(--line);background:#f8fafd;display:grid;place-items:center;color:transparent;font-size:11px;transition:.25s;flex:none}
.l2-cb input:checked + .check{background:linear-gradient(135deg,#0a66c2,#00b874);border-color:transparent;color:#fff}
.l2-forget{font-size:12.5px;color:#0a66c2;font-weight:700;text-decoration:none;transition:.2s}
.l2-forget:hover{color:#00b874}

/* Submit button */
.l2-submit{position:relative;width:100%;padding:15px;border:0;background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;font-family:inherit;font-size:14px;font-weight:800;letter-spacing:1px;border-radius:12px;cursor:pointer;transition:.3s;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 14px 30px rgba(0,184,116,.35);overflow:hidden}
.l2-submit::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,#00b874,#0a66c2);opacity:0;transition:.3s}
.l2-submit:hover::before{opacity:1}
.l2-submit span{position:relative;z-index:1;display:flex;align-items:center;gap:10px}
.l2-submit:hover{transform:translateY(-3px);box-shadow:0 20px 40px rgba(0,184,116,.45)}
.l2-submit:active{transform:translateY(-1px)}
.l2-submit.loading span{opacity:0}
.l2-submit.loading::after{content:'';position:absolute;width:22px;height:22px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:l2spin .8s linear infinite;z-index:1}
.l2-submit .progress{position:absolute;bottom:0;left:0;height:3px;background:rgba(255,255,255,.3);width:0;transition:width .3s}
.l2-submit.loading .progress{animation:l2load 1.5s ease-out forwards}

/* Security badges */
.l2-sec{display:flex;gap:8px;justify-content:center;margin-top:22px;flex-wrap:wrap}
.l2-sec .badge{display:inline-flex;align-items:center;gap:5px;background:#f0fdf7;color:#0a7a52;padding:5px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;border:1px solid #b5e8d4}
.l2-sec .badge i{font-size:9px}

/* Footer */
.l2-foot{margin-top:24px;padding-top:22px;border-top:1px dashed var(--line);text-align:center}
.l2-foot .back{display:inline-flex;align-items:center;gap:8px;color:#475569;font-size:13px;font-weight:700;text-decoration:none;transition:.25s}
.l2-foot .back:hover{color:#0a66c2;gap:12px}
.l2-foot .copy{display:block;color:#9aa8bf;font-size:11px;margin-top:12px;font-weight:600}

/* Global bottom info */
.l2-bottom{position:fixed;bottom:20px;left:0;right:0;z-index:1;display:flex;justify-content:center;gap:20px;flex-wrap:wrap;pointer-events:none}
.l2-bottom .info{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.05);backdrop-filter:blur(10px);padding:6px 14px;border-radius:99px;border:1px solid rgba(255,255,255,.1);color:#9fb3cd;font-size:11px;font-weight:700}
.l2-bottom .info i{color:#7ef0c4;font-size:11px}
.l2-bottom .info b{color:#fff;font-family:'Sora',var(--display)}

@media(max-width:1024px){
  .l2-wrap{grid-template-columns:1fr;max-width:520px}
  .l2-left{min-height:auto;padding:36px 30px}
  .l2-hero{padding:20px 0}
  .l2-atom{width:160px;height:160px}
  .l2-atom .orbit{width:130px;height:45px}
  .l2-atom .nucleus{width:24px;height:24px}
  .l2-hero h1{font-size:28px}
  .l2-features{display:none}
  .l2-right{padding:36px 30px}
}
@media(max-width:500px){
  .l2-wrap{border-radius:18px}
  .l2-left{padding:28px 22px}
  .l2-right{padding:28px 22px}
  .l2-form h2{font-size:24px}
  .l2-sec{gap:5px}
  .l2-sec .badge{font-size:9px;padding:4px 8px}
}
</style>
</head>
<body class="login-body">

<!-- Partikel background -->
<div class="l2-grid"></div>
<div class="l2-glow"></div>
<div class="l2-particles" id="particles"></div>

<div class="l2-wrap">
  <!-- LEFT: BRANDING -->
  <div class="l2-left">
    <div class="l2-brand">
      <span class="logo-ph"><i class="fa-solid fa-atom"></i></span>
      <span>
        <b>ADMIN<span>FISIKA</span></b>
        <small>Control Panel</small>
      </span>
    </div>

    <div class="l2-hero">
      <div class="l2-atom">
        <span class="nucleus"></span>
        <span class="orbit o1"><i></i></span>
        <span class="orbit o2"><i></i></span>
        <span class="orbit o3"><i></i></span>
      </div>
      <h1>ADMIN <span>FISIKA</span></h1>
      <p>Sistem Manajemen Konten untuk Program Studi Fisika — kelola berita, galeri, slider, dan seluruh informasi public dalam satu panel.</p>
    </div>

    <div class="l2-features">
      <div class="l2-feat">
        <span class="ic"><i class="fa-solid fa-gauge-high"></i></span>
        <span><b>Dashboard Real-time</b><small>Statistik aktivitas langsung</small></span>
      </div>
      <div class="l2-feat">
        <span class="ic"><i class="fa-solid fa-shield-halved"></i></span>
        <span><b>Keamanan Berlapis</b><small>CSRF, enkripsi password, sesi aman</small></span>
      </div>
      <div class="l2-feat">
        <span class="ic"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
        <span><b>UI Premium</b><small>Antarmuka modern & responsif</small></span>
      </div>
    </div>

    <div class="l2-quote">
      <p><?= e($todayQuote[0]) ?></p>
      <small>— <?= e($todayQuote[1]) ?></small>
    </div>
  </div>

  <!-- RIGHT: FORM -->
  <div class="l2-right">
    <form method="post" class="l2-form" id="loginForm">
      <div class="head">
        <span class="tag"><i class="fa-solid fa-lock"></i> AKSES TERBATAS</span>
        <h2>Masuk ke Panel Admin</h2>
        <p class="muted">Gunakan akun resmi Anda untuk melanjutkan.</p>
      </div>

      <?php if ($err): ?>
      <div class="l2-alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><b>Login Gagal</b> — <?= e($err) ?></span>
      </div>
      <?php endif; ?>

      <input type="text" name="website" class="l2-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="l2-field">
        <label><i class="fa-solid fa-user"></i> Username</label>
        <div class="l2-input">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username" placeholder="nama.admin" required autofocus autocomplete="username" value="<?= $err ? e($_POST['username'] ?? '') : '' ?>">
        </div>
      </div>

      <div class="l2-field">
        <label><i class="fa-solid fa-lock"></i> Password</label>
        <div class="l2-input">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="passInput" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="toggle" id="togglePass" title="Lihat password"><i class="fa-regular fa-eye"></i></button>
        </div>
      </div>

      <div class="l2-remember">
        <label class="l2-cb">
          <input type="checkbox" name="remember">
          <span class="check"><i class="fa-solid fa-check"></i></span>
          <span>Ingat saya di perangkat ini</span>
        </label>
        <a class="l2-forget" href="#" onclick="alert('Silakan hubungi administrator untuk mereset password.');return false;">Lupa password?</a>
      </div>

      <button class="l2-submit" type="submit" id="submitBtn">
        <span><i class="fa-solid fa-right-to-bracket"></i> MASUK SEKARANG</span>
        <div class="progress"></div>
      </button>

      <div class="l2-sec">
        <span class="badge"><i class="fa-solid fa-lock"></i> <?= $secure ? 'HTTPS' : 'LOKAL' ?></span>
        <span class="badge"><i class="fa-solid fa-shield"></i> CSRF PROTECTED</span>
        <span class="badge"><i class="fa-solid fa-key"></i> BCRYPT</span>
      </div>

      <div class="l2-foot">
        <a href="<?= url('index.php') ?>" class="back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Website Public</a>
        <span class="copy">© <?= date('Y') ?> Program Studi Fisika • PHP v<?= e($phpv) ?></span>
      </div>
    </form>
  </div>
</div>

<!-- Bottom global info -->
<div class="l2-bottom">
  <span class="info"><i class="fa-regular fa-clock"></i> <b id="l2Clock">--:--:--</b> WIB</span>
  <span class="info"><i class="fa-solid fa-server"></i> Sistem <b>ONLINE</b></span>
  <span class="info"><i class="fa-solid fa-code-branch"></i> v4.0 ULTIMATE</span>
</div>

<script>
(function(){
  /* ===== PARTIKEL BACKGROUND ===== */
  var pc = document.getElementById('particles');
  if (pc) {
    for (var i = 0; i < 25; i++) {
      var p = document.createElement('div');
      p.className = 'l2-particle';
      p.style.left = Math.random() * 100 + '%';
      p.style.animationDuration = (15 + Math.random() * 25) + 's';
      p.style.animationDelay = (Math.random() * 20) + 's';
      var s = 2 + Math.random() * 4;
      p.style.width = p.style.height = s + 'px';
      pc.appendChild(p);
    }
  }

  /* ===== JAM REAL-TIME ===== */
  var ck = document.getElementById('l2Clock');
  var fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  if (ck) {
    function tk(){ ck.textContent = fmt.format(new Date()).replace(/\./g, ':'); }
    tk(); setInterval(tk, 1000);
  }

  /* ===== TOGGLE PASSWORD ===== */
  var pass = document.getElementById('passInput');
  var tg = document.getElementById('togglePass');
  if (pass && tg) {
    tg.addEventListener('click', function(){
      var isPass = pass.type === 'password';
      pass.type = isPass ? 'text' : 'password';
      tg.innerHTML = '<i class="fa-' + (isPass ? 'solid' : 'regular') + ' fa-eye' + (isPass ? '-slash' : '') + '"></i>';
    });
  }

  /* ===== SUBMIT LOADING STATE (input TIDAK di-disable agar data terkirim) ===== */
  var form = document.getElementById('loginForm');
  var btn = document.getElementById('submitBtn');
  var sending = false;
  if (form && btn) {
    form.addEventListener('submit', function(ev){
      if (sending) { ev.preventDefault(); return; }   /* cegah double-submit */
      sending = true;
      btn.classList.add('loading');
      btn.querySelector('span').style.opacity = '.75';
      /* SELURUH input dibiarkan enabled agar username & password ikut dalam POST */
    });
  }

  /* ===== FOCUS INPUT PERTAMA ===== */
  var first = document.querySelector('input[name=username]');
  if (first && !first.value) first.focus();

  /* ===== ENTER KEY di password → submit ===== */
  if (pass) {
    pass.addEventListener('keydown', function(e){
      if (e.key === 'Enter') form.submit();
    });
  }

  /* ===== Shake animation saat error (sudah auto dari CSS) ===== */
  /* ===== Keyboard shortcut: Alt+L untuk focus ===== */
  document.addEventListener('keydown', function(e){
    if (e.altKey && e.key.toLowerCase() === 'l') { e.preventDefault(); if(first) first.focus(); }
  });
})();
</script>
</body>
</html>