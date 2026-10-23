<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';

/* Tangkap info sesi SEBELUM dihancurkan */
$sessName  = $_SESSION['admin_name'] ?? 'Pengguna';
$sessRole  = $_SESSION['admin_role'] ?? 'guest';
$sessStart = $_SESSION['login_time'] ?? null;
$duration  = $sessStart ? max(0, time() - (int)$sessStart) : 0;
$durationTxt = '';
if ($duration > 0) {
    $h = floor($duration / 3600);
    $m = floor(($duration % 3600) / 60);
    $s = $duration % 60;
    if ($h > 0) $durationTxt = $h . ' jam ' . $m . ' menit';
    elseif ($m > 0) $durationTxt = $m . ' menit ' . $s . ' detik';
    else $durationTxt = $s . ' detik';
}

$hour  = (int)date('G');
$greet = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamatkan Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));
$quotes = [
    'Perjalanan seribu mil dimulai dengan satu langkah.',
    'Ilmu tanpa amal bagaikan pohon tanpa buah.',
    'Sampai jumpa di pertemuan berikutnya.',
    'Perpisahan adalah awal dari pertemuan baru.',
    'Semoga perjalanan Anda diberkahi keselamatan.',
];
$todayQuote = $quotes[date('d') % count($quotes)];

/* Catat log keluar, lalu hancurkan sesi */
sec_log($conn, 'logout', 'Durasi sesi: ' . ($durationTxt ?: '-'));
session_unset();
session_destroy();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_start();
    session_unset();
    session_destroy();
}
/* Bersihkan cookie sesi bila ada */
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mengeluarkan... — Admin Fisika</title>
<meta http-equiv="refresh" content="4;url=login.php">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
/* ========= LOGOUT ULTIMATE v4 ========= */
@keyframes l2spin{to{transform:rotate(360deg)}}
@keyframes l2pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.08);opacity:.8}}
@keyframes l2fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
@keyframes l2fadeIn{from{opacity:0}to{opacity:1}}
@keyframes l2grad{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@keyframes l2floatUp{0%{transform:translateY(100vh) rotate(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-20vh) rotate(720deg);opacity:0}}
@keyframes l2check{0%{transform:scale(0) rotate(-45deg)}50%{transform:scale(1.2) rotate(-45deg)}100%{transform:scale(1) rotate(-45deg)}}
@keyframes l2ring{0%{stroke-dashoffset:553}100%{stroke-dashoffset:0}}
@keyframes l2blink{50%{opacity:.4}}

*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:linear-gradient(135deg,#04101f 0%,#071a39 40%,#0a2a5e 100%);color:#101828;position:relative;overflow:hidden}

/* Background layers */
.lt-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,184,116,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,184,116,.04) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0}
.lt-glow{position:fixed;inset:0;pointer-events:none;z-index:0}
.lt-glow::before{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,116,.2),transparent 60%);top:-200px;left:-100px}
.lt-glow::after{content:'';position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(10,102,194,.25),transparent 60%);bottom:-300px;right:-200px}
.lt-particles{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.lt-particle{position:absolute;width:4px;height:4px;background:rgba(126,240,196,.6);border-radius:50%;animation:l2floatUp linear infinite}
.lt-particle::after{content:'';position:absolute;inset:-4px;background:radial-gradient(circle,rgba(126,240,196,.3),transparent 70%);border-radius:50%}

/* Main card */
.lt-card{position:relative;z-index:2;width:min(560px,100%);background:rgba(255,255,255,.03);backdrop-filter:blur(20px);border-radius:26px;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 40px 90px rgba(0,0,0,.5);animation:l2fadeUp .7s cubic-bezier(.22,.61,.36,1)}
.lt-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#0a66c2,#00b874,#0a66c2);background-size:200% 100%;animation:l2grad 4s ease infinite}

/* Top section */
.lt-top{text-align:center;padding:40px 36px 26px;position:relative}
.lt-top::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 50% 0%,rgba(0,184,116,.18),transparent 60%);pointer-events:none}

/* Success icon */
.lt-ic{position:relative;width:100px;height:100px;margin:0 auto 24px}
.lt-ic-circle{width:100%;height:100%;border-radius:50%;background:linear-gradient(135deg,#00b874,#00915c);display:grid;place-items:center;box-shadow:0 16px 40px rgba(0,184,116,.4),0 0 60px rgba(0,184,116,.2);position:relative;animation:l2pulse 2.5s ease-in-out infinite}
.lt-ic-circle::before{content:'';position:absolute;inset:-8px;border-radius:50%;border:2px dashed rgba(0,184,116,.4);animation:l2spin 12s linear infinite}
.lt-ic-circle::after{content:'';position:absolute;inset:-16px;border-radius:50%;border:1.5px solid rgba(0,184,116,.2);animation:l2spin 18s linear infinite reverse}
.lt-ic-check{width:40px;height:22px;border-left:5px solid #fff;border-bottom:5px solid #fff;transform:rotate(-45deg);margin-top:-6px;animation:l2check .5s cubic-bezier(.22,.61,.36,1) .3s both}

/* Title & message */
.lt-tag{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,rgba(0,184,116,.25),rgba(77,163,255,.25));color:#7ef0c4;padding:6px 16px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;margin-bottom:14px;border:1px solid rgba(0,184,116,.4)}
.lt-tag i{font-size:10px}
.lt-title{font-family:'Sora',sans-serif;font-size:28px;color:#fff;line-height:1.2;margin-bottom:10px;letter-spacing:-.5px}
.lt-title span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.lt-msg{color:#c8d6ea;font-size:14px;line-height:1.7;max-width:380px;margin:0 auto}
.lt-msg b{color:#fff;font-family:'Sora',sans-serif}

/* Session stats */
.lt-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:22px 36px;background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06)}
.lt-stat{text-align:center;padding:12px 8px;background:rgba(255,255,255,.04);border-radius:12px;border:1px solid rgba(255,255,255,.06);transition:.3s}
.lt-stat:hover{background:rgba(255,255,255,.08);transform:translateY(-2px)}
.lt-stat i{color:#7ef0c4;font-size:14px;margin-bottom:6px;display:block}
.lt-stat b{display:block;font-family:'Sora',sans-serif;color:#fff;font-size:14px;margin-bottom:2px;word-break:break-word}
.lt-stat small{color:#9fb3cd;font-size:9.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase}

/* Countdown section */
.lt-countdown{padding:30px 36px;text-align:center}
.lt-countdown-head{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:16px;color:#c8d6ea;font-size:13px;font-weight:600}
.lt-countdown-head i{color:#7ef0c4}
.lt-countdown-head b{color:#fff;font-family:'Sora',sans-serif;font-size:15px}

/* Countdown ring */
.lt-ring-wrap{position:relative;width:140px;height:140px;margin:0 auto 20px}
.lt-ring{width:100%;height:100%;transform:rotate(-90deg)}
.lt-ring circle{fill:none;stroke-width:8;stroke-linecap:round}
.lt-ring .bg{stroke:rgba(255,255,255,.08)}
.lt-ring .fg{stroke:url(#ltgrad);stroke-dasharray:408;stroke-dashoffset:0;animation:l2ring 4s linear forwards}
.lt-ring-num{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.lt-ring-num b{font-family:'Sora',sans-serif;font-size:54px;color:#fff;line-height:1;font-variant-numeric:tabular-nums}
.lt-ring-num small{color:#7ef0c4;font-size:11px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;margin-top:4px}

/* Progress bar (backup visual) */
.lt-bar{height:6px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;margin-bottom:18px}
.lt-bar-fill{height:100%;background:linear-gradient(90deg,#0a66c2,#00b874);border-radius:99px;width:0%;animation:l2load 4s linear forwards}
@keyframes l2load{to{width:100%}}

/* Buttons */
.lt-btns{display:flex;gap:10px;flex-wrap:wrap}
.lt-btn{flex:1;min-width:140px;padding:13px 18px;border-radius:12px;font-family:inherit;font-size:13px;font-weight:800;letter-spacing:.5px;cursor:pointer;transition:.3s;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;border:1.4px solid transparent}
.lt-btn-primary{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;box-shadow:0 10px 24px rgba(0,184,116,.35)}
.lt-btn-primary:hover{transform:translateY(-3px);box-shadow:0 16px 34px rgba(0,184,116,.45)}
.lt-btn-ghost{background:rgba(255,255,255,.06);color:#e6eefc;border-color:rgba(255,255,255,.12)}
.lt-btn-ghost:hover{background:rgba(255,255,255,.12);transform:translateY(-2px)}

/* Quote */
.lt-quote{margin-top:24px;padding-top:20px;border-top:1px dashed rgba(255,255,255,.1);text-align:center}
.lt-quote p{color:#9fb3cd;font-style:italic;font-size:12.5px;line-height:1.6;margin-bottom:6px}
.lt-quote small{color:#7ef0c4;font-size:10.5px;font-weight:800;letter-spacing:1px}

/* Bottom info */
.lt-bottom{position:fixed;bottom:20px;left:0;right:0;z-index:1;display:flex;justify-content:center;gap:16px;flex-wrap:wrap;pointer-events:none}
.lt-bottom .info{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.05);backdrop-filter:blur(10px);padding:6px 14px;border-radius:99px;border:1px solid rgba(255,255,255,.1);color:#9fb3cd;font-size:11px;font-weight:700}
.lt-bottom .info i{color:#7ef0c4;font-size:11px}
.lt-bottom .info b{color:#fff;font-family:'Sora',sans-serif}

/* Responsive */
@media(max-width:560px){
  .lt-card{border-radius:20px}
  .lt-top{padding:32px 22px 22px}
  .lt-stats{padding:16px 22px;grid-template-columns:1fr 1fr}
  .lt-countdown{padding:24px 22px}
  .lt-title{font-size:22px}
  .lt-ring-wrap{width:120px;height:120px}
  .lt-ring-num b{font-size:44px}
  .lt-btns{flex-direction:column}
  .lt-btn{min-width:100%}
  .lt-bottom{gap:8px;padding:0 10px}
  .lt-bottom .info{font-size:10px;padding:5px 10px}
}
</style>
</head>
<body>
<div class="lt-grid"></div>
<div class="lt-glow"></div>
<div class="lt-particles" id="particles"></div>

<div class="lt-card">
  <!-- TOP: SUCCESS MESSAGE -->
  <div class="lt-top">
    <div class="lt-ic">
      <div class="lt-ic-circle">
        <div class="lt-ic-check"></div>
      </div>
    </div>
    <span class="lt-tag"><i class="fa-solid fa-shield-halved"></i> SESI DIAKHIRI AMAN</span>
    <h1 class="lt-title"><?= e($greet) ?>, <span><?= e(explode(' ', $sessName)[0]) ?></span></h1>
    <p class="lt-msg">Anda telah berhasil <b>keluar dari panel admin</b>. Sesi telah dihancurkan dengan aman. Sampai jumpa di sesi berikutnya!</p>
  </div>

  <!-- SESSION STATS -->
  <div class="lt-stats">
    <div class="lt-stat">
      <i class="fa-solid fa-user-shield"></i>
      <b><?= e($sessRole === 'admin' ? 'Administrator' : ucfirst($sessRole)) ?></b>
      <small>Peran</small>
    </div>
    <div class="lt-stat">
      <i class="fa-solid fa-clock"></i>
      <b><?= e($durationTxt ?: 'Singkat') ?></b>
      <small>Durasi Sesi</small>
    </div>
    <div class="lt-stat">
      <i class="fa-solid fa-calendar-check"></i>
      <b><?= date('H:i') ?></b>
      <small>Waktu Logout</small>
    </div>
  </div>

  <!-- COUNTDOWN -->
  <div class="lt-countdown">
    <div class="lt-countdown-head">
      <i class="fa-solid fa-hourglass-half"></i>
      <span>Mengalihkan ke halaman login dalam <b id="cdText">4 detik</b></span>
    </div>

    <div class="lt-ring-wrap">
      <svg class="lt-ring" viewBox="0 0 140 140">
        <defs>
          <linearGradient id="ltgrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#0a66c2"/>
            <stop offset="100%" style="stop-color:#00b874"/>
          </linearGradient>
        </defs>
        <circle class="bg" cx="70" cy="70" r="65"/>
        <circle class="fg" cx="70" cy="70" r="65"/>
      </svg>
      <div class="lt-ring-num">
        <b id="cdNum">4</b>
        <small>detik</small>
      </div>
    </div>

    <div class="lt-bar"><div class="lt-bar-fill"></div></div>

    <div class="lt-btns">
      <a class="lt-btn lt-btn-ghost" href="<?= url('index.php') ?>">
        <i class="fa-solid fa-globe"></i> Ke Website
      </a>
      <a class="lt-btn lt-btn-primary" href="login.php" id="cdBtn">
        <i class="fa-solid fa-right-to-bracket"></i> Login Lagi
      </a>
    </div>

    <div class="lt-quote">
      <p>"<?= e($todayQuote) ?>"</p>
      <small>— Pesan Penutup</small>
    </div>
  </div>
</div>

<!-- Bottom info -->
<div class="lt-bottom">
  <span class="info"><i class="fa-solid fa-shield-halved"></i> SESI <b>DIHANCURKAN</b></span>
  <span class="info"><i class="fa-solid fa-cookie-bite"></i> COOKIE <b>DIBERSIHKAN</b></span>
  <span class="info"><i class="fa-solid fa-lock"></i> AMAN <b>100%</b></span>
</div>

<script>
(function(){
  /* Particles */
  var pc = document.getElementById('particles');
  if (pc) {
    for (var i = 0; i < 20; i++) {
      var p = document.createElement('div');
      p.className = 'lt-particle';
      p.style.left = Math.random() * 100 + '%';
      p.style.animationDuration = (15 + Math.random() * 25) + 's';
      p.style.animationDelay = (Math.random() * 20) + 's';
      var s = 2 + Math.random() * 4;
      p.style.width = p.style.height = s + 'px';
      pc.appendChild(p);
    }
  }

  /* Countdown */
  var num = document.getElementById('cdNum');
  var text = document.getElementById('cdText');
  var btn = document.getElementById('cdBtn');
  var count = 4;
  var timer = setInterval(function(){
    count--;
    if (num) num.textContent = Math.max(0, count);
    if (text) text.textContent = count + ' detik';
    if (count <= 0) {
      clearInterval(timer);
      if (text) text.textContent = 'Mengalihkan...';
      if (btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengalihkan...'; btn.style.pointerEvents = 'none'; }
      window.location.href = 'login.php';
    }
  }, 1000);

  /* ESC untuk langsung ke login */
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' || e.key === 'Enter') { clearInterval(timer); window.location.href = 'login.php'; }
  });
})();
</script>
</body>
</html>