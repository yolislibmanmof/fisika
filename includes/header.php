<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
sec_send_headers();

/* ===== PREMIUM MODULES (Safe Load - tidak error jika belum ada) ===== */
$useCache = file_exists(__DIR__ . '/Cache.php');
$useAsset = file_exists(__DIR__ . '/Asset.php');
$useSEO   = file_exists(__DIR__ . '/SEO.php');

if ($useCache) require_once __DIR__ . '/Cache.php';
if ($useAsset) require_once __DIR__ . '/Asset.php';
if ($useSEO)   require_once __DIR__ . '/SEO.php';

/* ===== ASSET HELPER WRAPPER ===== */
function asset_css($path) {
    global $useAsset;
    return $useAsset && class_exists('Asset')
        ? Asset::css($path)
        : '<link rel="stylesheet" href="' . url($path) . '">';
}

function asset_js($path, $defer = true) {
    global $useAsset;
    return $useAsset && class_exists('Asset')
        ? Asset::js($path)
        : '<script src="' . url($path) . '"' . ($defer ? ' defer' : '') . '></script>';
}

function asset_preload($path, $as = 'style') {
    global $useAsset;
    return $useAsset && class_exists('Asset')
        ? Asset::preload($path, $as)
        : '<link rel="preload" href="' . url($path) . '" as="' . $as . '">';
}

/* Kompabilitas bila config lama belum memiliki query aman */
if (!function_exists('q'))  { function q($c, $s) { try { $r = $c->query($s); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; } catch (Throwable $e) { return []; } } }
if (!function_exists('q1')) { function q1($c, $s) { return q($c, $s)[0] ?? null; } }
if (!function_exists('qp')) { function qp($c, $s, $t = '', $p = []) { try { $st = $c->prepare($s); if ($p !== []) $st->bind_param($t, ...$p); $st->execute(); $r = $st->get_result(); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; } catch (Throwable $e) { return []; } } }

$S = get_setting($conn);
$menusAll = q($conn, "SELECT * FROM menus WHERE aktif=1 ORDER BY urutan ASC");
$parents  = array_values(array_filter($menusAll, fn($m) => (int)$m['parent_id'] === 0));
$getKids  = fn($pid) => array_values(array_filter($menusAll, fn($m) => (int)$m['parent_id'] === (int)$pid));

/* Penanda menu aktif otomatis */
function nav_active(string $url): bool {
    $self   = basename($_SERVER['SCRIPT_NAME']);
    $target = basename((string)parse_url($url, PHP_URL_PATH));
    if ($target === '' || $target !== $self) return false;
    parse_str((string)parse_url($url, PHP_URL_QUERY), $qs);
    if (isset($qs['slug'])) return $qs['slug'] === ($_GET['slug'] ?? '');
    return true;
}
$parentActive = fn($kids) => (bool)array_filter($kids, fn($k) => nav_active($k['url']));

/* Ticker berita terbaru - DENGAN CACHING (premium) */
if ($useCache && function_exists('cache_remember')) {
    $tickerItems = cache_remember('ticker_news_header', 1800, function() use ($conn) {
        return q($conn, "SELECT judul, slug FROM berita WHERE status=1 ORDER BY tanggal DESC LIMIT 5");
    });
} else {
    $tickerItems = q($conn, "SELECT judul, slug FROM berita WHERE status=1 ORDER BY tanggal DESC LIMIT 5");
}
if (!$tickerItems) $tickerItems = [['judul' => 'Selamat datang di website resmi Program Studi Fisika', 'slug' => '']];

/* ===== PREMIUM: Detect theme preference ===== */
$initialTheme = 'light'; // Default, JS akan override
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($initialTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ===== SEO META (existing + premium additions) ===== -->
<title><?= e($S['site_name'] ?? 'Program Studi Fisika') ?> — <?= e($S['kampus'] ?? '') ?></title>
<meta name="description" content="<?= e($S['meta_description'] ?? '') ?>">
<meta name="theme-color" content="#0A66C2" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0B1120" media="(prefers-color-scheme: dark)">

<!-- ===== PREMIUM: Canonical URL ===== -->
<link rel="canonical" href="<?= e(url($_SERVER['REQUEST_URI'] ?? '')) ?>">

<!-- ===== PREMIUM: Open Graph Defaults (di-override per halaman jika perlu) ===== -->
<meta property="og:site_name" content="<?= e($S['site_name'] ?? 'Program Studi Fisika') ?>">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="website">

<?php if (!empty($S['favicon'])): ?><link rel="icon" href="<?= e(media_url($S['favicon'])) ?>"><?php endif; ?>

<!-- ===== PREMIUM: Preload Critical Resources ===== -->
<?= asset_preload('assets/public/css/public.css', 'style') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- ===== Stylesheets dengan Versioning ===== -->
<?= asset_css('assets/public/css/public.css') ?>

<!-- ===== PREMIUM: Organization Schema (SEO) ===== -->
<?php if ($useSEO && class_exists('SEO') && function_exists('seo')): ?>
<?= seo()->organization() ?>
<?php endif; ?>

<!-- ===== PREMIUM: Anti-FOUC Dark Mode Script ===== -->
<script>
(function() {
    try {
        var theme = localStorage.getItem('fisika-theme') || 
                   (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    } catch(e) {}
})();
</script>

<style>
/* ========= EKSTENSI PREMIUM v3 (inline — public.css tetap final) ========= */
.x-progress{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#0a66c2,#00b874);z-index:1001;box-shadow:0 0 12px rgba(0,184,116,.6)}
.ticker{overflow:hidden;flex:1;max-width:430px;margin:0 10px}
.ticker-track{display:flex;width:max-content;animation:xTick 30s linear infinite}
.ticker:hover .ticker-track{animation-play-state:paused}
.tick-group{display:flex;align-items:center;gap:38px;padding-right:38px}
.ticker a{color:#cfd8e3;font-size:12.5px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:8px;transition:.25s}
.ticker a i{color:#00b874;font-size:8px}
.ticker a:hover{color:#7ef0c4}
@keyframes xTick{to{transform:translateX(-50%)}}
.x-akad-sec{background:#fff}
.x-akad-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.x-akad-card{position:relative;background:#fff;border:1px solid var(--line);border-radius:16px;padding:28px;overflow:hidden;transition:.4s;box-shadow:var(--shadow);display:block}
.x-akad-card:hover{transform:translateY(-8px);border-color:rgba(10,102,194,.4)}
.x-akad-card .num{position:absolute;right:16px;top:8px;font-family:var(--display);font-size:54px;font-weight:800;color:rgba(10,42,94,.06)}
.x-akad-ic{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;font-size:21px;color:#fff;margin-bottom:16px;background:linear-gradient(135deg,#0a66c2,#0a2a5e);transition:.4s}
.x-akad-card:nth-child(even) .x-akad-ic{background:linear-gradient(135deg,#00b874,#00915c)}
.x-akad-card:hover .x-akad-ic{transform:rotateY(180deg)}
.x-akad-card h3{font-family:var(--display);color:var(--blue-dark);font-size:16.5px;margin-bottom:8px}
.x-akad-card p{font-size:13.5px;color:#68758a;line-height:1.7}
.x-akad-card .go{margin-top:14px;display:inline-flex;gap:8px;color:#00b874;font-weight:700;font-size:12.5px;letter-spacing:.6px}
.x-akad-card:hover .go i{transform:translateX(5px)}
.x-akad-card .go i{transition:.3s}
.x-act-sec{background:var(--gray)}
.x-timeline{position:relative;padding-left:34px}
.x-timeline::before{content:'';position:absolute;left:10px;top:6px;bottom:6px;width:2px;background:linear-gradient(180deg,#0a66c2,#00b874)}
.x-tl-item{position:relative;padding-bottom:26px}
.x-tl-item::before{content:'';position:absolute;left:-30px;top:4px;width:14px;height:14px;border-radius:50%;background:#fff;border:3px solid #00b874;box-shadow:0 0 0 4px rgba(0,184,116,.15)}
.x-tl-date{font-size:11.5px;font-weight:800;letter-spacing:1.4px;color:#0a66c2}
.x-tl-item h4{font-family:var(--display);color:var(--blue-dark);font-size:15.5px;margin:6px 0}
.x-tl-item h4 a:hover{color:#0a66c2}
.x-tl-item p{font-size:13.5px;color:#68758a;line-height:1.7}
.x-marq{overflow:hidden;margin-top:14px}
.x-marq-track{display:flex;gap:22px;width:max-content;animation:xTick 38s linear infinite;padding:8px 0}
.x-marq:hover .x-marq-track{animation-play-state:paused}
.x-marq-card{width:340px;flex:none;background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;display:flex;gap:14px;align-items:center;transition:.3s}
.x-marq-card:hover{border-color:rgba(0,184,116,.5);transform:translateY(-4px)}
.x-marq-card img{width:62px;height:62px;border-radius:12px;object-fit:cover;flex:none}
.x-marq-card b{display:block;font-size:13.5px;color:var(--blue-dark);line-height:1.5}
.x-marq-card small{color:#00b874;font-weight:800;font-size:10.5px;letter-spacing:1.4px}
.x-quote{position:relative}
.x-quote::before{content:'\201C';position:absolute;top:-52px;left:-16px;font-family:var(--display);font-size:120px;color:rgba(0,184,116,.16);line-height:1;pointer-events:none}
.x-chips{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 30px}
.x-chips a{padding:9px 18px;border-radius:99px;border:1.6px solid var(--line);font-size:12.5px;font-weight:700;color:#33415e;transition:.25s;background:#fff}
.x-chips a:hover{border-color:#0a66c2;color:#0a66c2;transform:translateY(-2px)}
.x-chips a.on{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;border-color:transparent}
.x-blog-layout{display:grid;grid-template-columns:1.6fr .8fr;gap:34px;align-items:start}
.x-side{position:sticky;top:96px;display:flex;flex-direction:column;gap:22px}
.x-side-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;box-shadow:var(--shadow)}
.x-side-card h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--line);position:relative}
.x-side-card h4::after{content:'';position:absolute;left:0;bottom:-2px;width:36px;height:2px;background:linear-gradient(90deg,#0a66c2,#00b874)}
.x-pop-item{display:flex;gap:12px;padding:10px 0;border-bottom:1px dashed var(--line)}
.x-pop-item:last-child{border:0}
.x-pop-item img{width:64px;height:52px;object-fit:cover;border-radius:9px;flex:none}
.x-pop-item b{font-size:13px;line-height:1.5;color:#22304a;display:block;transition:.2s}
.x-pop-item b:hover{color:#0a66c2}
.x-pop-item small{color:#8a97ab;font-size:11.5px}
.x-kat-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px dashed var(--line);font-size:13px;font-weight:600;color:#33415e}
.x-kat-row:last-child{border:0}
.x-kat-row:hover{color:#0a66c2}
.x-kat-row .cnt{background:var(--gray);border-radius:99px;padding:2px 10px;font-size:11px;font-weight:800;color:#0a66c2}
.x-author{display:flex;gap:14px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-top:34px}
.x-author .ava{width:52px;height:52px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;flex:none}
.x-author b{color:var(--blue-dark);font-size:14.5px;display:block}
.x-author small{color:#8a97ab;font-size:12px}
.x-pn{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:26px}
.x-pn a{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;font-size:13px;font-weight:700;color:#22304a;transition:.25s;display:flex;gap:10px;align-items:center}
.x-pn a:hover{border-color:#00b874;color:#00915c;transform:translateY(-3px)}
.x-pn a.next{justify-content:flex-end;text-align:right}
.x-pn small{display:block;color:#8a97ab;font-weight:600;font-size:11px}
.x-wordmark{overflow:hidden;border-bottom:1px solid rgba(255,255,255,.06);padding:20px 0;margin-bottom:46px}
.x-wordmark .ticker-track{animation:xTick 44s linear infinite}
.x-wordmark span{font-family:var(--display);font-size:46px;font-weight:800;color:transparent;-webkit-text-stroke:1px rgba(255,255,255,.15);white-space:nowrap;padding-right:60px}
.x-wordmark span em{font-style:normal;color:rgba(0,184,116,.4);-webkit-text-stroke:0}
@media(max-width:1024px){.x-akad-grid{grid-template-columns:repeat(2,1fr)}.x-blog-layout{grid-template-columns:1fr}.x-side{position:static}}
@media(max-width:560px){.x-akad-grid{grid-template-columns:1fr}.x-pn{grid-template-columns:1fr}}

/* ========= PREMIUM: Dark Mode Theme System ========= */
:root {
    --bg-body: #ffffff;
    --bg-surface: #ffffff;
    --bg-elevated: #f8fafc;
    --text-main: #0F172A;
    --text-muted: #475569;
    --border-light: #e2e8f0;
    --shadow-base: 0 4px 12px rgba(15,23,42,0.08);
    --theme-transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

[data-theme="dark"] {
    --bg-body: #0B1120;
    --bg-surface: #1E293B;
    --bg-elevated: #334155;
    --text-main: #F1F5F9;
    --text-muted: #94A3B8;
    --border-light: #334155;
    --shadow-base: 0 4px 12px rgba(0,0,0,0.3);
}

body {
    background-color: var(--bg-body);
    color: var(--text-main);
    transition: var(--theme-transition);
}

[data-theme="dark"] body,
[data-theme="dark"] .x-akad-sec,
[data-theme="dark"] .x-side-card,
[data-theme="dark"] .x-akad-card,
[data-theme="dark"] .x-marq-card,
[data-theme="dark"] .x-chips a,
[data-theme="dark"] .x-pn a,
[data-theme="dark"] .x-author,
[data-theme="dark"] .navbar,
[data-theme="dark"] .topbar {
    background-color: var(--bg-surface);
    color: var(--text-main);
}

[data-theme="dark"] .x-akad-card p,
[data-theme="dark"] .x-tl-item p,
[data-theme="dark"] .x-marq-card b,
[data-theme="dark"] .x-author small,
[data-theme="dark"] .ticker a {
    color: var(--text-muted);
}

[data-theme="dark"] .x-akad-card,
[data-theme="dark"] .x-side-card,
[data-theme="dark"] .x-pn a,
[data-theme="dark"] .x-author,
[data-theme="dark"] .x-chips a,
[data-theme="dark"] .x-marq-card {
    border-color: var(--border-light);
    background-color: var(--bg-surface);
}

[data-theme="dark"] .navbar {
    background-color: rgba(11, 17, 32, 0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

/* Dark Mode Toggle Button */
.theme-toggle {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.theme-toggle:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.theme-toggle i {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-toggle .fa-sun {
    position: absolute;
    opacity: 0;
    transform: rotate(-90deg) scale(0);
}

[data-theme="dark"] .theme-toggle .fa-moon {
    opacity: 0;
    transform: rotate(90deg) scale(0);
}

[data-theme="dark"] .theme-toggle .fa-sun {
    opacity: 1;
    transform: rotate(0) scale(1);
}

/* Smooth theme transitions untuk semua elemen */
*, *::before, *::after {
    transition-property: background-color, border-color, color;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}
</style>
</head>
<body>
<div class="x-progress" id="xProgress"></div>

<!-- ===== TOP BAR ===== -->
<div class="topbar">
  <div class="container topbar-in">
    <div class="tb-left">
      <span><i class="fa-solid fa-phone"></i> <?= e($S['telepon'] ?? '') ?></span>
      <span><i class="fa-solid fa-envelope"></i> <?= e($S['email'] ?? '') ?></span>
      <span class="tb-clock"><i class="fa-solid fa-clock"></i> <b id="clockWIB">--:--:--</b> WIB</span>
    </div>
    <div class="tb-mid ticker" title="Info terbaru — berhenti saat kursor di atasnya">
      <div class="ticker-track">
        <?php for ($g = 0; $g < 2; $g++): ?>
        <div class="tick-group">
          <?php foreach ($tickerItems as $t): ?>
          <a href="<?= $t['slug'] ? url('berita-detail.php?slug=' . e($t['slug'])) : '#' ?>"><i class="fa-solid fa-circle"></i> <?= e($t['judul']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endfor; ?>
      </div>
    </div>
    <div class="tb-right">
      <!-- PREMIUM: Dark Mode Toggle -->
      <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Ganti mode tampilan">
        <i class="fa-solid fa-moon"></i>
        <i class="fa-solid fa-sun"></i>
      </button>
      <a href="#"><i class="fa-solid fa-user-graduate"></i> Alumni</a>
      <a href="#"><i class="fa-solid fa-download"></i> Unduhan</a>
      <span class="akre-pill"><i class="fa-solid fa-certificate"></i> Terakreditasi <?= e($S['akreditasi'] ?? '') ?></span>
    </div>
  </div>
</div>

<!-- ===== NAVBAR ===== -->
<header class="navbar" id="navbar">
  <div class="container nav-in">
    <a class="brand" href="<?= url('index.php') ?>">
      <?php if (!empty($S['logo'])): ?>
        <img src="<?= e(media_url($S['logo'])) ?>" alt="Logo" class="brand-logo">
      <?php else: ?>
        <span class="brand-atom"><i class="fa-solid fa-atom"></i></span>
      <?php endif; ?>
      <span class="brand-txt">
        <b>ADMIN<span class="green">FISIKA</span></b>
        <small><?= e($S['site_tagline'] ?? '') ?></small>
      </span>
    </a>

    <nav class="nav-menu" id="navMenu">
      <ul>
        <?php foreach ($parents as $p): $kids = $getKids($p['id']); $on = $parentActive($kids) || (!$kids && nav_active($p['url'])); ?>
        <li class="nav-item <?= $kids ? 'has-child' : '' ?>">
          <a href="<?= $kids ? '#' : url($p['url']) ?>" class="<?= $on ? 'active' : '' ?>">
            <?= e($p['label']) ?>
            <?php if ($kids): ?><i class="fa-solid fa-chevron-down caret"></i><?php endif; ?>
          </a>
          <?php if ($kids): ?>
          <div class="dropdown">
            <?php foreach ($kids as $c): ?>
            <a href="<?= url($c['url']) ?>" class="<?= nav_active($c['url']) ? 'active' : '' ?>" style="<?= nav_active($c['url']) ? 'color:#0a66c2;background:#f4f7fb' : '' ?>"><i class="fa-solid fa-angle-right"></i> <?= e($c['label']) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="nav-actions">
      <button class="icon-btn" id="btnSearch" title="Cari" aria-label="Buka pencarian"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn-cta" href="<?= url('page.php?slug=kontak') ?>"><i class="fa-solid fa-paper-plane"></i> DAFTAR SEKARANG</a>
      <button class="icon-btn burger" id="btnBurger" aria-label="Toggle menu"><i class="fa-solid fa-bars-staggered"></i></button>
    </div>
  </div>
</header>

<!-- ===== SEARCH OVERLAY ===== -->
<div class="search-overlay" id="searchOverlay">
  <form action="<?= url('berita.php') ?>" method="get" class="search-box">
    <input type="text" name="q" placeholder="Ketik kata kunci berita / kegiatan..." autocomplete="off" aria-label="Kata kunci pencarian">
    <button type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass"></i></button>
    <button type="button" class="search-close" id="searchClose" aria-label="Tutup pencarian"><i class="fa-solid fa-xmark"></i></button>
  </form>
</div>