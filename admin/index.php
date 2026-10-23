<?php
$ACTIVE = 'index.php'; $TITLE = 'Dashboard';
require_once __DIR__ . '/../config/config.php';
require_login();

/* ========= KUMPULKAN DATA DASHBOARD ========= */
$safeCount = fn(string $table, string $where = '1=1'): int => (int)(q1($conn, "SELECT COUNT(*) AS c FROM `$table` WHERE $where")['c'] ?? 0);
$safeSum   = fn(string $table, string $col, string $where = '1=1'): int => (int)(q1($conn, "SELECT COALESCE(SUM($col),0) AS c FROM `$table` WHERE $where")['c'] ?? 0);

$cUsers    = $safeCount('users');
$cBerita   = $safeCount('berita');
$cPage     = $safeCount('pages');
$cGaleri   = $safeCount('galeri');
$cFitur    = $safeCount('fitur');
$cHero     = $safeCount('heroes');
$cMenu     = $safeCount('menus');
$cStats    = $safeCount('stats');
$views     = $safeSum('berita', 'views');
$cTayang   = $safeCount('berita', 'status=1');
$cDraft    = $safeCount('berita', 'status=0');
$cFoto     = $safeCount('galeri', "tipe='image'");
$cVideo    = $safeCount('galeri', "tipe='video'");

/* Aktivitas 7 hari terakhir */
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $cnt = (int)(q1($conn, "SELECT COUNT(*) AS c FROM berita WHERE DATE(tanggal)='$d'")['c'] ?? 0);
    $chartData[] = ['date' => $d, 'label' => date('D', strtotime($d)), 'count' => $cnt];
}
$maxChart = max(1, max(array_column($chartData, 'count')));

/* Distribusi kategori */
$kats = q($conn, "SELECT kategori, COUNT(*) AS c FROM berita WHERE status=1 GROUP BY kategori ORDER BY c DESC LIMIT 5");
$totalKat = array_sum(array_column($kats, 'c'));
$katColors = ['#0a66c2','#00b874','#8b5cf6','#f59e0b','#ec4899'];

/* Top 5 berita */
$topNews = q($conn, "SELECT * FROM berita WHERE status=1 ORDER BY views DESC LIMIT 5");
$maxViews = max(1, (int)($topNews[0]['views'] ?? 1));

/* Berita terbaru */
$recentNews = q($conn, "SELECT * FROM berita ORDER BY dibuat DESC LIMIT 6");

/* Info sistem */
$sysInfo = [
    'php' => PHP_VERSION,
    'memory' => round(memory_get_usage(true) / 1048576, 1),
    'server' => php_uname('s'),
    'max_upload' => ini_get('upload_max_filesize'),
];

$hour  = (int)date('G');
$greet = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));
$greetIcon = $hour < 11 ? 'fa-sun' : ($hour < 15 ? 'fa-cloud-sun' : ($hour < 19 ? 'fa-cloud-moon' : 'fa-moon'));
$firstName = explode(' ', (string)$_SESSION['admin_name'])[0];

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= DASHBOARD ULTIMATE v4 ========= */
@keyframes d2float{50%{transform:translateY(-6px)}}
@keyframes d2pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
@keyframes d2slide{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes d2grow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
@keyframes d2fill{from{width:0}to{width:var(--w,100%)}}
@keyframes d2spin{to{transform:rotate(360deg)}}
@keyframes d2blink{50%{opacity:.4}}

/* ===== HERO GREETING ===== */
.d2-hero{background:linear-gradient(135deg,#071a39 0%,#0a2a5e 50%,#0a66c2 100%);border-radius:22px;padding:32px;color:#fff;position:relative;overflow:hidden;margin-bottom:26px;box-shadow:0 30px 70px rgba(10,42,94,.3)}
.d2-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%,rgba(0,184,116,.2),transparent 45%),radial-gradient(circle at 85% 70%,rgba(10,102,194,.3),transparent 50%)}
.d2-hero::after{content:'\f080';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:-30px;bottom:-50px;font-size:220px;color:rgba(255,255,255,.04);animation:d2spin 60s linear infinite}
.d2-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:center}
.d2-greet h2{font-family:var(--display);font-size:28px;line-height:1.25;margin-bottom:8px}
.d2-greet h2 i{color:#7ef0c4;margin-right:10px}
.d2-greet h2 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.d2-greet p{color:#c8d6ea;font-size:14.5px;line-height:1.75;max-width:560px;margin-bottom:20px}
.d2-meta{display:flex;gap:12px;flex-wrap:wrap}
.d2-meta .chip{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);padding:8px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:#e6eefc}
.d2-meta .chip i{color:#7ef0c4;font-size:12px}
.d2-meta .chip b{font-family:var(--display);color:#fff}

/* Jam & cuaca */
.d2-clock{background:rgba(255,255,255,.06);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:24px;text-align:center}
.d2-clock .time{font-family:var(--display);font-size:44px;color:#fff;line-height:1;letter-spacing:2px;margin-bottom:8px;font-variant-numeric:tabular-nums}
.d2-clock .time em{color:#7ef0c4;font-style:normal}
.d2-clock .date{color:#c8d6ea;font-size:13px;font-weight:600;margin-bottom:14px}
.d2-clock .sep{height:1px;background:rgba(255,255,255,.1);margin:14px 0}
.d2-clock .loc{display:flex;align-items:center;justify-content:center;gap:8px;color:#9fb3cd;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase}
.d2-clock .loc i{color:#7ef0c4}
.d2-clock .loc .dot{width:7px;height:7px;border-radius:50%;background:#00b874;animation:d2blink 1.5s infinite}

/* ===== STAT CARDS ===== */
.d2-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:26px}
.d2-st{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;position:relative;overflow:hidden;transition:.35s;cursor:pointer;animation:d2slide .5s backwards}
.d2-st:hover{transform:translateY(-5px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.d2-st::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.d2-st::after{content:'';position:absolute;right:-12px;bottom:-12px;width:80px;height:80px;border-radius:50%;background:var(--c,var(--grad));opacity:.06;transition:.4s}
.d2-st:hover::after{transform:scale(1.8);opacity:.08}
.d2-st-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px}
.d2-st-ic{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:19px;transition:.35s;flex:none}
.d2-st:hover .d2-st-ic{background:var(--c,var(--grad));color:#fff;transform:rotate(-6deg) scale(1.05)}
.d2-st-trend{display:flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.5px}
.d2-st-trend.up{background:#e7f8f1;color:#0a7a52}
.d2-st-trend.down{background:#fdecec;color:#b3261e}
.d2-st-trend i{font-size:9px}
.d2-st b{font-family:var(--display);font-size:30px;color:var(--blue-dark);display:block;line-height:1;margin-bottom:6px}
.d2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;display:block}
.d2-st:nth-child(1){animation-delay:.05s;--c:#0a66c2}
.d2-st:nth-child(2){animation-delay:.10s;--c:#00b874}
.d2-st:nth-child(3){animation-delay:.15s;--c:#8b5cf6}
.d2-st:nth-child(4){animation-delay:.20s;--c:#f59e0b}
.d2-st:nth-child(5){animation-delay:.25s;--c:#ec4899}
.d2-st:nth-child(6){animation-delay:.30s;--c:#06b6d4}
.d2-st:nth-child(7){animation-delay:.35s;--c:#10b981}
.d2-st:nth-child(8){animation-delay:.40s;--c:#f97316}

/* ===== MAIN LAYOUT ===== */
.d2-main{display:grid;grid-template-columns:1.4fr 1fr;gap:22px;margin-bottom:22px}

/* ===== PANEL UMUM ===== */
.d2-panel{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column}
.d2-panel-head{padding:18px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px}
.d2-panel-head h3{margin:0;font-family:var(--display);color:var(--blue-dark);font-size:15px;flex:1;display:flex;align-items:center;gap:10px}
.d2-panel-head h3 i{color:var(--green);font-size:14px}
.d2-panel-head .more{font-size:11.5px;font-weight:700;color:var(--blue);text-decoration:none;display:flex;align-items:center;gap:5px;transition:.2s}
.d2-panel-head .more:hover{color:var(--green);gap:8px}
.d2-panel-body{padding:22px;flex:1}

/* ===== CHART AKTIVITAS ===== */
.d2-chart{height:220px;position:relative;padding-top:10px}
.d2-chart-bars{display:flex;align-items:flex-end;gap:10px;height:180px;padding:0 4px}
.d2-chart-bar{flex:1;display:flex;flex-direction:column;align-items:center;gap:6px}
.d2-chart-bar .bar-wrap{flex:1;width:100%;background:var(--gray);border-radius:8px 8px 4px 4px;position:relative;overflow:hidden;display:flex;align-items:flex-end}
.d2-chart-bar .bar-fill{width:100%;background:var(--grad);border-radius:8px 8px 4px 4px;position:relative;animation:d2grow 1s cubic-bezier(.22,.61,.36,1) backwards;transform-origin:bottom;min-height:4px}
.d2-chart-bar:nth-child(1) .bar-fill{animation-delay:.1s}
.d2-chart-bar:nth-child(2) .bar-fill{animation-delay:.15s}
.d2-chart-bar:nth-child(3) .bar-fill{animation-delay:.2s}
.d2-chart-bar:nth-child(4) .bar-fill{animation-delay:.25s}
.d2-chart-bar:nth-child(5) .bar-fill{animation-delay:.3s}
.d2-chart-bar:nth-child(6) .bar-fill{animation-delay:.35s}
.d2-chart-bar:nth-child(7) .bar-fill{animation-delay:.4s}
.d2-chart-bar .val{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:11px;font-weight:800;color:var(--blue-dark);font-family:var(--display);background:#fff;padding:2px 7px;border-radius:99px;border:1px solid var(--line);white-space:nowrap}
.d2-chart-bar .lbl{font-size:10.5px;color:#7b8aa3;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.d2-chart-bar.today .lbl{color:var(--green);font-weight:800}
.d2-chart-bar.today .bar-fill{background:linear-gradient(180deg,#00b874,#00915c)}
.d2-chart-legend{display:flex;gap:14px;margin-top:14px;padding-top:14px;border-top:1px dashed var(--line);font-size:11.5px;color:#7b8aa3;font-weight:600;flex-wrap:wrap}
.d2-chart-legend span{display:flex;align-items:center;gap:6px}
.d2-chart-legend i{font-size:10px;color:var(--green)}

/* ===== PIE DISTRIBUSI ===== */
.d2-pie{display:grid;grid-template-columns:160px 1fr;gap:22px;align-items:center}
.d2-pie-svg{width:160px;height:160px;position:relative}
.d2-pie-svg svg{width:100%;height:100%;transform:rotate(-90deg)}
.d2-pie-svg .center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.d2-pie-svg .center b{font-family:var(--display);font-size:26px;color:var(--blue-dark);line-height:1}
.d2-pie-svg .center small{color:#7b8aa3;font-size:10.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-top:4px}
.d2-pie-list{display:flex;flex-direction:column;gap:10px}
.d2-pie-item{display:flex;align-items:center;gap:10px;font-size:13px;color:#475569;font-weight:600}
.d2-pie-item .dot{width:12px;height:12px;border-radius:4px;flex:none}
.d2-pie-item b{margin-left:auto;font-family:var(--display);color:var(--blue-dark);font-size:14px}
.d2-pie-item small{color:#9aa8bf;font-size:11px;margin-left:4px}

/* ===== TOP NEWS LIST ===== */
.d2-toplist{display:flex;flex-direction:column;gap:10px}
.d2-top-item{display:flex;align-items:center;gap:12px;padding:10px;border-radius:11px;transition:.25s;cursor:pointer;text-decoration:none}
.d2-top-item:hover{background:var(--gray);transform:translateX(3px)}
.d2-top-item .rank{width:28px;height:28px;border-radius:8px;background:var(--gray);color:var(--blue-dark);font-size:12px;font-weight:800;display:grid;place-items:center;flex:none;font-family:var(--display)}
.d2-top-item:nth-child(1) .rank{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff}
.d2-top-item:nth-child(2) .rank{background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff}
.d2-top-item:nth-child(3) .rank{background:linear-gradient(135deg,#cd7f32,#a0522d);color:#fff}
.d2-top-item .info{flex:1;min-width:0}
.d2-top-item .info h5{font-family:var(--display);color:var(--blue-dark);font-size:13px;margin:0 0 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.d2-top-item .info small{color:#9aa8bf;font-size:11px;font-weight:600;display:flex;align-items:center;gap:6px}
.d2-top-item .info small i{color:var(--green);font-size:10px}
.d2-top-item .views{font-family:var(--display);font-size:15px;color:var(--blue-dark);font-weight:800}
.d2-top-item .views small{color:#9aa8bf;font-size:10px;font-weight:600;display:block;text-align:right;letter-spacing:.5px}

/* ===== RECENT NEWS (BOTTOM) ===== */
.d2-recent{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.d2-recent-item{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:.3s;text-decoration:none;display:flex;flex-direction:column;box-shadow:var(--shadow)}
.d2-recent-item:hover{transform:translateY(-4px);border-color:rgba(0,184,116,.3);box-shadow:0 16px 40px rgba(10,42,94,.14)}
.d2-recent-img{height:130px;background:var(--gray);position:relative;overflow:hidden}
.d2-recent-img img{width:100%;height:100%;object-fit:cover;transition:.5s}
.d2-recent-item:hover .d2-recent-img img{transform:scale(1.08)}
.d2-recent-img .ph{position:absolute;inset:0;display:grid;place-items:center;color:#cfd8e3;font-size:28px}
.d2-recent-img .cat{position:absolute;top:8px;left:8px;background:rgba(7,26,57,.85);backdrop-filter:blur(8px);color:#fff;padding:3px 9px;border-radius:99px;font-size:9.5px;font-weight:800;letter-spacing:.6px}
.d2-recent-img .stat{position:absolute;top:8px;right:8px;padding:3px 9px;border-radius:99px;font-size:9.5px;font-weight:800;letter-spacing:.6px}
.d2-recent-img .stat.on{background:#e7f8f1;color:#0a7a52}
.d2-recent-img .stat.off{background:#fdecec;color:#b3261e}
.d2-recent-body{padding:14px;flex:1;display:flex;flex-direction:column}
.d2-recent-body h5{font-family:var(--display);color:var(--blue-dark);font-size:13.5px;line-height:1.4;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:38px}
.d2-recent-body .meta{margin-top:auto;display:flex;gap:10px;font-size:10.5px;color:#9aa8bf;font-weight:600}
.d2-recent-body .meta i{color:var(--green);margin-right:3px}

/* ===== QUICK ACTIONS ===== */
.d2-qa{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.d2-qa-item{display:flex;align-items:center;gap:12px;padding:14px;border:1.6px solid var(--line);border-radius:12px;background:#fbfcfe;transition:.3s;text-decoration:none;position:relative;overflow:hidden}
.d2-qa-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:0;background:var(--grad);transition:.3s;opacity:.08}
.d2-qa-item:hover{border-color:var(--green);background:#fff;transform:translateY(-3px)}
.d2-qa-item:hover::before{width:100%}
.d2-qa-item .ic{width:38px;height:38px;border-radius:10px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:14px;flex:none;transition:.3s;position:relative;z-index:1}
.d2-qa-item:hover .ic{transform:rotate(-6deg) scale(1.08)}
.d2-qa-item b{display:block;color:var(--blue-dark);font-family:var(--display);font-size:12.5px;position:relative;z-index:1}
.d2-qa-item small{color:#9aa8bf;font-size:10.5px;font-weight:600;display:block;margin-top:2px;position:relative;z-index:1}

/* ===== SYSTEM STATUS ===== */
.d2-sys{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.d2-sys-item{padding:14px;border:1.4px solid var(--line);border-radius:11px;background:#fbfcfe;display:flex;align-items:center;gap:12px;transition:.25s}
.d2-sys-item:hover{border-color:rgba(0,184,116,.3);background:#fff}
.d2-sys-item .ic{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:var(--blue);display:grid;place-items:center;font-size:14px;flex:none}
.d2-sys-item small{color:#9aa8bf;font-size:10.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;display:block}
.d2-sys-item b{color:var(--blue-dark);font-family:var(--display);font-size:13px;display:block;margin-top:2px;word-break:break-all}

/* ===== TIMELINE AKTIVITAS ===== */
.d2-timeline{display:flex;flex-direction:column;gap:14px;padding-left:16px;border-left:2px dashed var(--line)}
.d2-tl-item{position:relative;padding-left:20px}
.d2-tl-item::before{content:'';position:absolute;left:-23px;top:4px;width:12px;height:12px;border-radius:50%;background:#fff;border:3px solid var(--green);box-shadow:0 0 0 3px rgba(0,184,116,.12)}
.d2-tl-item.old::before{border-color:#94a3b8;box-shadow:0 0 0 3px rgba(148,163,184,.15)}
.d2-tl-item .when{font-size:10.5px;color:#9aa8bf;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:3px}
.d2-tl-item h5{font-family:var(--display);color:var(--blue-dark);font-size:13px;margin:0 0 3px;line-height:1.4}
.d2-tl-item p{color:#7b8aa3;font-size:12px;line-height:1.55;margin:0}

/* ===== EMPTY STATE ===== */
.d2-empty{padding:34px 20px;text-align:center;color:#7b8aa3}
.d2-empty i{font-size:32px;color:#cfd8e3;margin-bottom:10px;display:block}
.d2-empty p{font-size:13px}

/* ===== SECTION HEAD ===== */
.d2-sec-head{display:flex;align-items:center;justify-content:space-between;margin:32px 0 18px;flex-wrap:wrap;gap:10px}
.d2-sec-head h3{margin:0;font-family:var(--display);color:var(--blue-dark);font-size:18px;display:flex;align-items:center;gap:10px}
.d2-sec-head h3 i{color:var(--green);font-size:16px}
.d2-sec-head .actions{display:flex;gap:8px}
.d2-sec-head .actions a{padding:8px 14px;border:1.4px solid var(--line);background:#fff;color:#475569;border-radius:9px;font-size:11.5px;font-weight:700;transition:.2s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.d2-sec-head .actions a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}

@media(max-width:1100px){.d2-main{grid-template-columns:1fr}.d2-stats{grid-template-columns:1fr 1fr}.d2-recent{grid-template-columns:1fr 1fr}.d2-hero-inner{grid-template-columns:1fr}.d2-clock{max-width:320px;margin:0 auto}}
@media(max-width:700px){.d2-stats{grid-template-columns:1fr}.d2-recent{grid-template-columns:1fr}.d2-qa{grid-template-columns:1fr 1fr}.d2-pie{grid-template-columns:1fr}}
</style>

<!-- ===== HERO GREETING ===== -->
<div class="d2-hero">
  <div class="d2-hero-inner">
    <div class="d2-greet">
      <h2><i class="fa-regular <?= $greetIcon ?>"></i> <?= e($greet) ?>, <span><?= e($firstName) ?>!</span></h2>
      <p>Selamat datang kembali di panel admin Program Studi Fisika. Pantau statistik, kelola konten, dan pastikan seluruh informasi public tetap mutakhir.</p>
      <div class="d2-meta">
        <span class="chip"><i class="fa-solid fa-calendar-day"></i> <b id="d2Date"><?= date('d F Y') ?></b></span>
        <span class="chip"><i class="fa-solid fa-clock"></i> <b id="d2Time">--:--:--</b> WIB</span>
        <span class="chip"><i class="fa-solid fa-shield-halved"></i> <b>Sesi Aktif</b></span>
        <span class="chip"><i class="fa-solid fa-user-shield"></i> <b><?= ucfirst(e($_SESSION['admin_role'])) ?></b></span>
      </div>
    </div>
    <div class="d2-clock">
      <div class="time" id="d2BigTime">--<em>:</em>--<em>:</em>--</div>
      <div class="date" id="d2BigDate"><?= date('l, d F Y') ?></div>
      <div class="sep"></div>
      <div class="loc">
        <span class="dot"></span>
        <i class="fa-solid fa-location-dot"></i>
        <span>JAKARTA • WIB (UTC+7)</span>
      </div>
    </div>
  </div>
</div>

<!-- ===== 8 STAT CARDS ===== -->
<div class="d2-stats">
  <a class="d2-st" href="<?= url('admin/berita.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-newspaper"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-arrow-up"></i> AKTIF</span>
    </div>
    <b><?= $cBerita ?></b>
    <small>Total Berita</small>
  </a>
  <a class="d2-st" href="<?= url('admin/berita.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-circle-check"></i></span>
      <span class="d2-st-trend <?= $cTayang > $cDraft ? 'up' : 'down' ?>"><i class="fa-solid fa-<?= $cTayang > $cDraft ? 'arrow-up' : 'arrow-down' ?>"></i> <?= round($cBerita ? ($cTayang/$cBerita)*100 : 0) ?>%</span>
    </div>
    <b><?= $cTayang ?></b>
    <small>Berita Tayang</small>
  </a>
  <a class="d2-st" href="<?= url('admin/berita.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-eye"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-chart-line"></i> TOTAL</span>
    </div>
    <b><?= number_format($views) ?></b>
    <small>Total Kunjungan</small>
  </a>
  <a class="d2-st" href="<?= url('admin/galeri.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-images"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-photo-film"></i> <?= $cFoto ?>+<?= $cVideo ?></span>
    </div>
    <b><?= $cGaleri ?></b>
    <small>Item Galeri</small>
  </a>
  <a class="d2-st" href="<?= url('admin/halaman.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-file-lines"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-globe"></i> PUBLIC</span>
    </div>
    <b><?= $cPage ?></b>
    <small>Halaman Statis</small>
  </a>
  <a class="d2-st" href="<?= url('admin/hero.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-photo-film"></i></span>
      <span class="d2-st-trend <?= $cHero > 0 ? 'up' : 'down' ?>"><i class="fa-solid fa-<?= $cHero > 0 ? 'check' : 'minus' ?>"></i> <?= $cHero ?> SLIDE</span>
    </div>
    <b><?= $cHero ?></b>
    <small>Hero Slider</small>
  </a>
  <a class="d2-st" href="<?= url('admin/menu.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-bars-progress"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-sitemap"></i> NAVIGASI</span>
    </div>
    <b><?= $cMenu ?></b>
    <small>Menu Navigasi</small>
  </a>
  <a class="d2-st" href="<?= url('admin/users.php') ?>">
    <div class="d2-st-top">
      <span class="d2-st-ic"><i class="fa-solid fa-users-gear"></i></span>
      <span class="d2-st-trend up"><i class="fa-solid fa-user-shield"></i> AKTIF</span>
    </div>
    <b><?= $cUsers ?></b>
    <small>Pengguna Admin</small>
  </a>
</div>

<!-- ===== MAIN ROW: CHART + PIE ===== -->
<div class="d2-main">
  <!-- AKTIVITAS 7 HARI -->
  <div class="d2-panel">
    <div class="d2-panel-head">
      <h3><i class="fa-solid fa-chart-line"></i> Aktivitas 7 Hari Terakhir</h3>
      <a class="more" href="<?= url('admin/berita.php') ?>">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="d2-panel-body">
      <div class="d2-chart">
        <div class="d2-chart-bars">
          <?php foreach ($chartData as $idx => $d): 
            $pct = ($d['count'] / $maxChart) * 100;
            $isToday = $d['date'] === date('Y-m-d'); ?>
          <div class="d2-chart-bar <?= $isToday ? 'today' : '' ?>">
            <div class="bar-wrap">
              <span class="val"><?= $d['count'] ?></span>
              <div class="bar-fill" style="height:<?= max(3, $pct) ?>%"></div>
            </div>
            <span class="lbl"><?= $isToday ? 'HRN' : $d['label'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="d2-chart-legend">
        <span><i class="fa-solid fa-circle"></i> Total artikel dibuat minggu ini: <b style="color:var(--blue-dark);font-family:var(--display)"><?= array_sum(array_column($chartData, 'count')) ?></b></span>
        <span><i class="fa-solid fa-star"></i> Rata-rata: <b style="color:var(--blue-dark);font-family:var(--display)"><?= round(array_sum(array_column($chartData, 'count')) / 7, 1) ?></b> artikel/hari</span>
      </div>
    </div>
  </div>

  <!-- DISTRIBUSI KATEGORI -->
  <div class="d2-panel">
    <div class="d2-panel-head">
      <h3><i class="fa-solid fa-chart-pie"></i> Distribusi Kategori</h3>
    </div>
    <div class="d2-panel-body">
      <?php if ($kats): 
        $circumference = 2 * M_PI * 60;
        $offset = 0; ?>
      <div class="d2-pie">
        <div class="d2-pie-svg">
          <svg viewBox="0 0 140 140">
            <?php foreach ($kats as $i => $k): 
              $pct = $totalKat > 0 ? ($k['c'] / $totalKat) : 0;
              $dash = $pct * $circumference; ?>
            <circle cx="70" cy="70" r="60" stroke="<?= $katColors[$i] ?>" stroke-width="20" fill="none"
              stroke-dasharray="<?= $dash ?> <?= $circumference - $dash ?>"
              stroke-dashoffset="-<?= $offset ?>" stroke-linecap="butt"/>
            <?php $offset += $dash; endforeach; ?>
          </svg>
          <div class="center">
            <b><?= $totalKat ?></b>
            <small>Total</small>
          </div>
        </div>
        <div class="d2-pie-list">
          <?php foreach ($kats as $i => $k): 
            $pct = $totalKat > 0 ? round(($k['c'] / $totalKat) * 100) : 0; ?>
          <div class="d2-pie-item">
            <span class="dot" style="background:<?= $katColors[$i] ?>"></span>
            <span><?= e($k['kategori']) ?></span>
            <b><?= (int)$k['c'] ?><small>(<?= $pct ?>%)</small></b>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="d2-empty">
        <i class="fa-solid fa-chart-pie"></i>
        <p>Belum ada data kategori untuk ditampilkan.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== SECOND ROW: TOP NEWS + QUICK ACTIONS ===== -->
<div class="d2-main">
  <!-- TOP ARTIKEL -->
  <div class="d2-panel">
    <div class="d2-panel-head">
      <h3><i class="fa-solid fa-trophy"></i> Top 5 Artikel Terpopuler</h3>
      <a class="more" href="<?= url('admin/berita.php') ?>">Semua <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="d2-panel-body">
      <?php if ($topNews): ?>
      <div class="d2-toplist">
        <?php foreach ($topNews as $idx => $n): ?>
        <a class="d2-top-item" href="<?= url('admin/berita.php?edit=' . (int)$n['id']) ?>">
          <span class="rank">#<?= $idx + 1 ?></span>
          <div class="info">
            <h5><?= e($n['judul']) ?></h5>
            <small><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y', strtotime($n['tanggal'])) ?> <i class="fa-solid fa-tag" style="margin-left:6px"></i> <?= e($n['kategori']) ?></small>
          </div>
          <div class="views">
            <?= number_format((int)$n['views']) ?>
            <small>VIEWS</small>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="d2-empty">
        <i class="fa-solid fa-trophy"></i>
        <p>Belum ada artikel yang dipublikasikan.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- AKSI CEPAT + SISTEM -->
  <div class="d2-panel">
    <div class="d2-panel-head">
      <h3><i class="fa-solid fa-bolt"></i> Aksi Cepat</h3>
    </div>
    <div class="d2-panel-body">
      <div class="d2-qa">
        <a class="d2-qa-item" href="<?= url('admin/berita.php?edit=0') ?>">
          <span class="ic"><i class="fa-solid fa-newspaper"></i></span>
          <span><b>Berita Baru</b><small>Tulis artikel</small></span>
        </a>
        <a class="d2-qa-item" href="<?= url('admin/galeri.php?edit=0') ?>">
          <span class="ic"><i class="fa-solid fa-images"></i></span>
          <span><b>Tambah Galeri</b><small>Unggah foto</small></span>
        </a>
        <a class="d2-qa-item" href="<?= url('admin/halaman.php?edit=0') ?>">
          <span class="ic"><i class="fa-solid fa-file-pen"></i></span>
          <span><b>Halaman Baru</b><small>Buat halaman</small></span>
        </a>
        <a class="d2-qa-item" href="<?= url('admin/hero.php?edit=0') ?>">
          <span class="ic"><i class="fa-solid fa-photo-film"></i></span>
          <span><b>Slide Hero</b><small>Tambah slide</small></span>
        </a>
        <a class="d2-qa-item" href="<?= url('admin/sambutan.php') ?>">
          <span class="ic"><i class="fa-solid fa-comment-dots"></i></span>
          <span><b>Edit Sambutan</b><small>Pesan Kaprodi</small></span>
        </a>
        <a class="d2-qa-item" href="<?= url('admin/pengaturan.php') ?>">
          <span class="ic"><i class="fa-solid fa-sliders"></i></span>
          <span><b>Pengaturan</b><small>Kelola situs</small></span>
        </a>
      </div>

      <div style="margin-top:20px;padding-top:18px;border-top:1px dashed var(--line)">
        <h4 style="margin:0 0 12px;font-family:var(--display);color:var(--blue-dark);font-size:13px;display:flex;align-items:center;gap:8px"><i class="fa-solid fa-microchip" style="color:var(--green)"></i>Status Sistem</h4>
        <div class="d2-sys">
          <div class="d2-sys-item">
            <span class="ic"><i class="fa-brands fa-php"></i></span>
            <div><small>PHP</small><b>v<?= e($sysInfo['php']) ?></b></div>
          </div>
          <div class="d2-sys-item">
            <span class="ic"><i class="fa-solid fa-memory"></i></span>
            <div><small>Memory</small><b><?= e($sysInfo['memory']) ?> MB</b></div>
          </div>
          <div class="d2-sys-item">
            <span class="ic"><i class="fa-solid fa-server"></i></span>
            <div><small>Server</small><b><?= e($sysInfo['server']) ?></b></div>
          </div>
          <div class="d2-sys-item">
            <span class="ic"><i class="fa-solid fa-upload"></i></span>
            <div><small>Max Upload</small><b><?= e($sysInfo['max_upload']) ?></b></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== SECTION: BERITA TERBARU ===== -->
<div class="d2-sec-head">
  <h3><i class="fa-solid fa-clock-rotate-left"></i> Berita Terbaru</h3>
  <div class="actions">
    <a href="<?= url('admin/berita.php') ?>"><i class="fa-solid fa-list"></i> Lihat Semua</a>
    <a href="<?= url('admin/berita.php?edit=0') ?>"><i class="fa-solid fa-plus"></i> Tambah</a>
  </div>
</div>

<?php if ($recentNews): ?>
<div class="d2-recent">
  <?php foreach ($recentNews as $b): ?>
  <a class="d2-recent-item" href="<?= url('admin/berita.php?edit=' . (int)$b['id']) ?>">
    <div class="d2-recent-img">
      <?php if ($b['gambar']): ?>
      <img src="<?= e(media_url($b['gambar'])) ?>" alt="" loading="lazy">
      <?php else: ?>
      <div class="ph"><i class="fa-solid fa-newspaper"></i></div>
      <?php endif; ?>
      <span class="cat"><?= e($b['kategori']) ?></span>
      <span class="stat <?= $b['status'] ? 'on' : 'off' ?>"><?= $b['status'] ? 'TAYANG' : 'DRAFT' ?></span>
    </div>
    <div class="d2-recent-body">
      <h5><?= e($b['judul']) ?></h5>
      <div class="meta">
        <span><i class="fa-regular fa-calendar-days"></i><?= date('d M Y', strtotime($b['tanggal'])) ?></span>
        <span><i class="fa-regular fa-eye"></i><?= number_format((int)$b['views']) ?></span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="d2-panel" style="margin-top:0">
  <div class="d2-panel-body">
    <div class="d2-empty">
      <i class="fa-solid fa-newspaper"></i>
      <p>Belum ada berita. <a href="<?= url('admin/berita.php?edit=0') ?>" style="color:var(--green);font-weight:700">Buat berita pertama</a>.</p>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ===== TIMELINE AKTIVITAS ===== -->
<div class="d2-sec-head">
  <h3><i class="fa-solid fa-timeline"></i> Jejak Aktivitas</h3>
</div>

<div class="d2-panel">
  <div class="d2-panel-body">
    <?php 
    $timeline = q($conn, "SELECT * FROM berita ORDER BY dibuat DESC LIMIT 6");
    if ($timeline): ?>
    <div class="d2-timeline">
      <?php foreach ($timeline as $idx => $t): 
        $madeAt = strtotime($t['dibuat']);
        $diff = time() - $madeAt;
        if ($diff < 60) $when = 'Baru saja';
        elseif ($diff < 3600) $when = round($diff/60) . ' menit lalu';
        elseif ($diff < 86400) $when = round($diff/3600) . ' jam lalu';
        elseif ($diff < 172800) $when = 'Kemarin';
        else $when = date('d M Y', $madeAt);
        $isOld = $idx >= 3; ?>
      <div class="d2-tl-item <?= $isOld ? 'old' : '' ?>">
        <div class="when"><?= $when ?></div>
        <h5><?= e($t['judul']) ?></h5>
        <p><?= e($t['penulis']) ?> • Kategori <?= e($t['kategori']) ?> • <?= $t['status'] ? 'Dipublikasikan' : 'Masih draft' ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="d2-empty">
      <i class="fa-solid fa-timeline"></i>
      <p>Belum ada aktivitas untuk ditampilkan.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  /* ===== REAL-TIME CLOCK ===== */
  var timeEl = document.getElementById('d2Time');
  var bigTimeEl = document.getElementById('d2BigTime');
  var dateEl = document.getElementById('d2Date');
  var bigDateEl = document.getElementById('d2BigDate');
  var fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  var dFmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'long', year: 'numeric' });
  var dFmtFull = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
  function tick() {
    var now = new Date();
    var t = fmt.format(now).replace(/\./g, ':');
    if (timeEl) timeEl.textContent = t;
    if (bigTimeEl) {
      var parts = t.split(':');
      bigTimeEl.innerHTML = parts[0] + '<em>:</em>' + parts[1] + '<em>:</em>' + parts[2];
    }
    if (dateEl) dateEl.textContent = dFmt.format(now);
    if (bigDateEl) bigDateEl.textContent = dFmtFull.format(now);
  }
  tick();
  setInterval(tick, 1000);

  /* ===== Auto-refresh setiap 5 menit ===== */
  setTimeout(function(){ location.reload(); }, 5 * 60 * 1000);

  /* ===== Welcome toast (sekali saja per sesi) ===== */
  if (!sessionStorage.getItem('d2Welcomed')) {
    sessionStorage.setItem('d2Welcomed', '1');
    if (window.a2Toast) {
      setTimeout(function(){ a2Toast('Selamat datang kembali, <?= e(addslashes($firstName)) ?>! 👋'); }, 600);
    }
  }

  /* ===== Counter animation untuk stat cards ===== */
  var counters = document.querySelectorAll('.d2-st b');
  if (counters.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if (!en.isIntersecting) return;
        var el = en.target;
        var text = el.textContent.replace(/[^\d]/g,'');
        var target = parseInt(text) || 0;
        if (target <= 0) { io.unobserve(el); return; }
        var n = 0, step = Math.max(1, Math.ceil(target / 40));
        var t = setInterval(function(){
          n += step;
          if (n >= target) { n = target; clearInterval(t); el.textContent = n.toLocaleString('id-ID'); }
          else el.textContent = n.toLocaleString('id-ID');
        }, 25);
        io.unobserve(el);
      });
    }, { threshold: .5 });
    counters.forEach(function(c){ io.observe(c); });
  }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>