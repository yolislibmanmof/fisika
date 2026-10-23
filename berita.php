<?php
require_once __DIR__ . '/includes/header.php';
$q      = trim($_GET['q'] ?? '');
$kat    = trim($_GET['kat'] ?? '');
$bulan  = trim($_GET['bulan'] ?? '');
$view   = trim($_GET['view'] ?? 'grid');
$per    = 6;
$pageNo = max(1, (int)($_GET['hal'] ?? 1));
$offset = ($pageNo - 1) * $per;

$where = " WHERE status=1"; $params = []; $types = '';
if ($kat !== '')   { $where .= " AND kategori=?"; $params[] = $kat; $types .= 's'; }
if ($bulan !== '') { $where .= " AND DATE_FORMAT(tanggal,'%Y-%m')=?"; $params[] = $bulan; $types .= 's'; }
if ($q !== '')     { $where .= " AND (judul LIKE ? OR isi LIKE ? OR kategori LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like, $like]); $types .= 'sss'; }

$total     = (int)(qp($conn, "SELECT COUNT(*) AS c FROM berita$where", $types, $params)[0]['c'] ?? 0);
$pages     = max(1, (int)ceil($total / $per));
$list      = qp($conn, "SELECT * FROM berita$where ORDER BY tanggal DESC LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$per, $offset]));
$kats      = q($conn, "SELECT kategori, COUNT(*) AS c FROM berita WHERE status=1 GROUP BY kategori ORDER BY c DESC");
$populer   = q($conn, "SELECT judul, slug, gambar, tanggal, views, kategori, isi FROM berita WHERE status=1 ORDER BY views DESC LIMIT 5");
$arsip     = q($conn, "SELECT DATE_FORMAT(tanggal,'%Y-%m') AS ym, DATE_FORMAT(tanggal,'%M %Y') AS label, COUNT(*) AS c FROM berita WHERE status=1 GROUP BY ym ORDER BY ym DESC LIMIT 12");
$tags      = q($conn, "SELECT kategori, COUNT(*) AS c FROM berita WHERE status=1 GROUP BY kategori ORDER BY c DESC");
$totalView = (int)(q1($conn, "SELECT SUM(views) AS s FROM berita WHERE status=1")['s'] ?? 0);
$featured  = q1($conn, "SELECT * FROM berita WHERE status=1 ORDER BY views DESC, tanggal DESC LIMIT 1");

/* Build query string helper */
$qs = function ($overrides = []) use ($q, $kat, $bulan, $view, $pageNo) {
    $base = ['q' => $q, 'kat' => $kat, 'bulan' => $bulan, 'view' => $view, 'hal' => $pageNo];
    $merged = array_filter(array_merge($base, $overrides), fn($v) => $v !== '' && $v !== null);
    return http_build_query($merged);
};

$quoteList = [
    ['Fisika adalah cara memahami alam semesta melalui bahasa matematika.', '— Richard Feynman'],
    ['Imajinasi lebih penting daripada pengetahuan.', '— Albert Einstein'],
    ['Tuhan tidak bermain dadu dengan alam semesta.', '— Albert Einstein'],
    ['Ukur apa yang dapat diukur, dan ukur apa yang belum dapat diukur.', '— Galileo Galilei'],
    ['Saya tidak memiliki bakat khusus, saya hanya penuh rasa ingin tahu.', '— Albert Einstein'],
];
$todayQuote = $quoteList[date('d') % count($quoteList)];
?>
<style>
/* ========= ULTIMATE BERITA — INLINE EXTENSION ========= */
.u-hero-band{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-hero-band::before{content:'';position:absolute;inset:0;background:
  radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),
  radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-hero-band::after{content:'⚛';position:absolute;right:8%;top:50%;transform:translateY(-50%);font-size:340px;color:rgba(255,255,255,.03);animation:uSpin 40s linear infinite}
@keyframes uSpin{to{transform:translateY(-50%) rotate(360deg)}}
.u-hero-inner{position:relative;z-index:2;display:grid;grid-template-columns:1.4fr 1fr;gap:40px;align-items:center}
.u-hero-left h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-hero-left h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-hero-left p{font-size:17px;color:#c8d6ea;line-height:1.8;max-width:560px;margin-bottom:28px}
.u-hero-stats{display:flex;gap:34px;flex-wrap:wrap}
.u-hero-stats .hs{text-align:left}
.u-hero-stats .hs b{display:block;font-family:var(--display);font-size:34px;color:#fff}
.u-hero-stats .hs b em{color:#7ef0c4;font-style:normal}
.u-hero-stats .hs small{font-size:11.5px;letter-spacing:2px;color:#9fb3cd;font-weight:700;text-transform:uppercase}
.u-featured{position:relative;border-radius:22px;overflow:hidden;background:#04101f;box-shadow:0 30px 80px rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.08);transition:.4s}
.u-featured:hover{transform:translateY(-6px) scale(1.01)}
.u-featured img{width:100%;height:380px;object-fit:cover;display:block}
.u-featured .u-fb{position:absolute;inset:0;background:linear-gradient(180deg,transparent 20%,rgba(4,16,31,.92));display:flex;flex-direction:column;justify-content:flex-end;padding:28px}
.u-featured .u-fb .u-ribbon{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#00b874,#00915c);color:#04231a;padding:7px 16px;border-radius:99px;font-size:11px;font-weight:800;letter-spacing:2px;align-self:flex-start;margin-bottom:14px}
.u-featured .u-fb .u-ribbon i{animation:uPulse 1.4s ease-in-out infinite}
@keyframes uPulse{50%{opacity:.4}}
.u-featured h3{font-family:var(--display);color:#fff;font-size:22px;line-height:1.4;margin-bottom:10px}
.u-featured h3:hover{color:#7ef0c4}
.u-featured .u-fb-meta{color:#c8d6ea;font-size:12.5px;font-weight:600}
.u-featured .u-fb-meta i{color:#7ef0c4;margin-right:5px}
.u-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-search{flex:1;min-width:260px;position:relative}
.u-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}
.u-search button{position:absolute;right:6px;top:50%;transform:translateY(-50%);background:var(--grad);color:#fff;border:0;padding:8px 16px;border-radius:9px;font-weight:700;font-size:12px;cursor:pointer;letter-spacing:.5px}
.u-view-toggle{display:flex;background:var(--gray);border-radius:10px;padding:4px;border:1px solid var(--line)}
.u-view-toggle button{border:0;background:transparent;padding:8px 14px;border-radius:7px;cursor:pointer;font-weight:700;font-size:12px;color:#68758a;display:flex;align-items:center;gap:6px;transition:.25s}
.u-view-toggle button.on{background:var(--grad);color:#fff;box-shadow:0 4px 12px rgba(10,102,194,.25)}
.u-filter-select{padding:11px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;cursor:pointer;outline:0}
.u-filter-select:focus{border-color:var(--blue)}
.u-chips-wrap{padding:0 0 18px;position:relative}
.u-chips{display:flex;gap:10px;flex-wrap:wrap;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin}
.u-chips::-webkit-scrollbar{height:6px}.u-chips::-webkit-scrollbar-thumb{background:var(--grad);border-radius:99px}
.u-chips a{padding:10px 20px;border-radius:99px;border:1.6px solid var(--line);font-size:12.5px;font-weight:700;color:#33415e;transition:.3s;background:#fff;white-space:nowrap;position:relative;overflow:hidden}
.u-chips a::before{content:'';position:absolute;inset:0;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:.35s;z-index:0}
.u-chips a span{position:relative;z-index:1}
.u-chips a:hover::before{transform:scaleX(1)}
.u-chips a:hover span{color:#fff}
.u-chips a.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 8px 20px rgba(0,184,116,.3)}
.u-chips a.on::before{transform:scaleX(1)}
.u-blog-layout{display:grid;grid-template-columns:1fr 340px;gap:40px;align-items:start}
.u-main{min-width:0}
.u-results-info{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px}
.u-results-info .u-rinfo{font-size:13.5px;color:#68758a;font-weight:600}
.u-results-info .u-rinfo b{color:var(--blue-dark)}
.u-results-info .u-rinfo i{color:#00b874}
.berita-grid.u-grid-2{grid-template-columns:repeat(2,1fr)}
.berita-grid.u-list{display:flex;flex-direction:column;gap:20px}
.u-list .berita-card{display:grid;grid-template-columns:280px 1fr;gap:0}
.u-list .bc-img{height:100%}
.u-list .bc-img img{height:100%}
.u-empty{text-align:center;padding:70px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line)}
.u-empty i{font-size:64px;color:#cfd8e3;margin-bottom:18px}
.u-empty h3{font-family:var(--display);color:var(--blue-dark);margin-bottom:10px}
.u-empty p{color:#68758a;margin-bottom:20px}
.u-pagination{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:44px;flex-wrap:wrap}
.u-pagination a,.u-pagination span{min-width:44px;height:44px;display:inline-grid;place-items:center;border-radius:11px;border:1.6px solid var(--line);background:#fff;font-weight:700;color:#33415e;transition:.3s;padding:0 12px;font-size:13px;text-decoration:none}
.u-pagination a:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-3px)}
.u-pagination a.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 8px 18px rgba(0,184,116,.3)}
.u-pagination .u-arr{background:var(--grad);color:#fff;border-color:transparent;padding:0 18px}
.u-pagination .u-arr:hover{transform:translateY(-3px) scale(1.04)}
.u-pagination .u-dis{opacity:.4;cursor:not-allowed;pointer-events:none}
.u-pagination .u-dots{border:0;color:#8a97ab;letter-spacing:3px}

/* SIDEBAR */
.u-side{display:flex;flex-direction:column;gap:24px;position:sticky;top:96px;max-height:calc(100vh - 120px);overflow-y:auto;padding-right:6px;scrollbar-width:thin}
.u-side > *{flex:0 0 auto;min-width:0}
.u-side::-webkit-scrollbar{width:6px}.u-side::-webkit-scrollbar-thumb{background:var(--grad);border-radius:99px}
.u-side-card{background:#fff;border-radius:18px;padding:24px;box-shadow:var(--shadow);border:1px solid var(--line);position:relative;overflow:hidden}
.u-side-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-side-card h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:10px}
.u-side-card h4::after{content:'';position:absolute;left:0;bottom:-2px;width:44px;height:2px;background:linear-gradient(90deg,#0a66c2,#00b874)}
.u-side-card h4 .u-badge{margin-left:auto;font-size:10px;font-weight:800;letter-spacing:1px;color:#fff;background:var(--grad);padding:3px 10px;border-radius:99px}
.u-pop-item{display:flex;gap:13px;padding:13px 0;border-bottom:1px dashed var(--line);position:relative;transition:.3s;text-decoration:none}
.u-pop-item:last-child{border:0}
.u-pop-item:hover{transform:translateX(5px)}
.u-pop-item .u-rank{position:absolute;left:-4px;top:13px;width:24px;height:24px;border-radius:50%;background:var(--gray);color:var(--blue-dark);font-size:11px;font-weight:800;display:grid;place-items:center;z-index:2;border:2px solid #fff}
.u-pop-item:nth-child(-n+3) .u-rank{background:var(--grad);color:#fff}
.u-pop-item img{width:72px;height:60px;object-fit:cover;border-radius:10px;flex:none;margin-left:24px}
.u-pop-item b{font-size:13px;line-height:1.5;color:#22304a;display:block;transition:.25s;margin-top:2px}
.u-pop-item:hover b{color:var(--blue)}
.u-pop-item small{color:#8a97ab;font-size:11.5px;font-weight:600;margin-top:4px;display:block}
.u-pop-item small i{color:#00b874;margin-right:4px}

.u-kat-item{display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px dashed var(--line);font-size:13px;font-weight:600;color:#33415e;transition:.25s;text-decoration:none;position:relative;overflow:hidden}
.u-kat-item:last-child{border:0}
.u-kat-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:0;background:var(--grad);transition:.3s;opacity:.08}
.u-kat-item:hover::before{width:100%}
.u-kat-item:hover{color:var(--blue);padding-left:6px}
.u-kat-item .cnt{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:800}

.u-archive-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px dashed var(--line);font-size:12.5px;color:#475569;font-weight:600;text-decoration:none;transition:.25s}
.u-archive-item:last-child{border:0}
.u-archive-item:hover{color:var(--blue);transform:translateX(4px)}
.u-archive-item i{color:#00b874;margin-right:8px}
.u-archive-item span{color:#8a97ab;font-size:11px}

.u-quote-card{background:linear-gradient(135deg,#0a2a5e 0%,#071a39 100%);color:#fff;border-radius:18px;padding:28px;position:relative;overflow:hidden}
.u-quote-card::before{content:'"';position:absolute;top:-30px;left:10px;font-family:var(--display);font-size:150px;color:rgba(0,184,116,.2);line-height:1;pointer-events:none}
.u-quote-card::after{content:'';position:absolute;right:-40px;bottom:-40px;width:140px;height:140px;border:2px dashed rgba(0,184,116,.3);border-radius:50%;animation:uSpin 20s linear infinite}
.u-quote-card p{font-family:var(--display);font-size:16px;line-height:1.7;color:#e6eefc;position:relative;z-index:1;margin-bottom:16px;font-style:italic}
.u-quote-card small{color:#7ef0c4;font-weight:800;letter-spacing:1px;font-size:11.5px;position:relative;z-index:1}

.u-newsletter{background:linear-gradient(135deg,#00b874 0%,#00915c 100%);color:#fff;border-radius:18px;padding:26px;position:relative;overflow:hidden}
.u-newsletter::before{content:'';position:absolute;right:-30px;top:-30px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.12);animation:uSpin 15s linear infinite reverse}
.u-newsletter h4{color:#fff;font-family:var(--display);font-size:15px;margin-bottom:8px;position:relative}
.u-newsletter h4::after{display:none}
.u-newsletter p{font-size:12.5px;color:rgba(255,255,255,.9);margin-bottom:14px;line-height:1.6;position:relative}
.u-newsletter .u-nl-form{position:relative;display:flex;gap:6px;flex-direction:column}
.u-newsletter input{width:100%;padding:11px 14px;border:0;border-radius:10px;font-family:var(--body);font-size:13px;outline:0;background:rgba(255,255,255,.96)}
.u-newsletter button{padding:11px;border:0;background:#04231a;color:#7ef0c4;border-radius:10px;font-weight:800;letter-spacing:1px;cursor:pointer;font-size:12px;transition:.25s;position:relative;overflow:hidden}
.u-newsletter button:hover{background:#000;color:#fff}
.u-newsletter small{display:block;margin-top:8px;font-size:10.5px;color:rgba(255,255,255,.85);position:relative}
.u-newsletter small i{margin-right:4px}

.u-tag-cloud{display:flex;flex-wrap:wrap;gap:8px}
.u-tag-cloud a{padding:6px 14px;border-radius:99px;background:var(--gray);font-size:11.5px;font-weight:700;color:#475569;transition:.25s;border:1px solid transparent;text-decoration:none}
.u-tag-cloud a:hover{background:var(--grad);color:#fff;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,184,116,.3)}

@media(max-width:1100px){.u-hero-inner{grid-template-columns:1fr}.u-blog-layout{grid-template-columns:1fr}.u-side{position:static;max-height:none}}
@media(max-width:700px){.u-list .berita-card{grid-template-columns:1fr}.u-hero-stats{gap:20px}.u-tools{margin-top:-24px;padding:14px}}
</style>

<!-- ===== HERO BAND ===== -->
<section class="u-hero-band">
  <div class="container u-hero-inner">
    <div class="u-hero-left reveal">
      <span class="sec-tag light"><i class="fa-solid fa-newspaper"></i> PUSAT INFORMASI</span>
      <h1>Berita, Riset, dan <span>Kegiatan Prodi Fisika</span></h1>
      <p>Temukan informasi terbaru seputar prestasi akademik, kegiatan kemahasiswaan, penelitian ilmiah, serta agenda ilmiah Program Studi Fisika.</p>
      <div class="u-hero-stats">
        <div class="hs"><b><?= $total ?><em>+</em></b><small>Total Artikel</small></div>
        <div class="hs"><b><?= count($kats) ?></b><small>Kategori</small></div>
        <div class="hs"><b><?= number_format($totalView) ?><em>+</em></b><small>Total Dibaca</small></div>
      </div>
    </div>
    <?php if ($featured): ?>
    <a class="u-featured reveal" href="<?= url('berita-detail.php?slug=' . e($featured['slug'])) ?>">
      <img src="<?= e(media_url($featured['gambar']) ?: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1200&q=60') ?>" alt="" loading="lazy">
      <div class="u-fb">
        <span class="u-ribbon"><i class="fa-solid fa-bolt"></i> ARTIKEL UNGGULAN</span>
        <h3><?= e($featured['judul']) ?></h3>
        <span class="u-fb-meta">
          <i class="fa-regular fa-calendar-days"></i> <?= date('d F Y', strtotime($featured['tanggal'])) ?>
          &nbsp;•&nbsp;<i class="fa-regular fa-eye"></i> <?= (int)$featured['views'] ?> dibaca
          &nbsp;•&nbsp;<i class="fa-regular fa-folder"></i> <?= e($featured['kategori']) ?>
        </span>
      </div>
    </a>
    <?php else: ?>
    <div class="u-featured reveal" style="padding:80px 30px;text-align:center">
      <i class="fa-solid fa-newspaper" style="font-size:48px;color:#7ef0c4"></i>
      <h3 style="margin-top:18px">Belum ada artikel unggulan</h3>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ===== TOOLS BAR ===== -->
<div class="u-tools container reveal">
  <form method="get" class="u-search" action="berita.php">
    <?php if ($kat !== ''): ?><input type="hidden" name="kat" value="<?= e($kat) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul, kategori, atau kata kunci...">
    <button type="submit">CARI</button>
  </form>
  <select class="u-filter-select" onchange="location.href='berita.php?bulan='+this.value+'<?= $kat ? '&kat='.urlencode($kat) : '' ?>'">
    <option value="">Semua Periode</option>
    <?php foreach ($arsip as $a): ?>
    <option value="<?= e($a['ym']) ?>" <?= $bulan === $a['ym'] ? 'selected' : '' ?>><?= e($a['label']) ?> (<?= (int)$a['c'] ?>)</option>
    <?php endforeach; ?>
  </select>
  <div class="u-view-toggle">
    <button class="<?= $view !== 'list' ? 'on' : '' ?>" onclick="location.href='berita.php?<?= $qs(['view'=>'grid','hal'=>1]) ?>'"><i class="fa-solid fa-grip"></i> Grid</button>
    <button class="<?= $view === 'list' ? 'on' : '' ?>" onclick="location.href='berita.php?<?= $qs(['view'=>'list','hal'=>1]) ?>'"><i class="fa-solid fa-list"></i> List</button>
  </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<section class="page-sec" style="padding-top:0">
  <div class="container">
    <div class="u-chips-wrap reveal">
      <div class="u-chips">
        <a class="<?= $kat === '' ? 'on' : '' ?>" href="berita.php"><span><i class="fa-solid fa-layer-group" style="margin-right:6px"></i>Semua Artikel</span></a>
        <?php foreach ($kats as $k): ?>
        <a class="<?= $kat === $k['kategori'] ? 'on' : '' ?>" href="?kat=<?= urlencode($k['kategori']) ?>"><span><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</span></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($q !== '' || $kat !== '' || $bulan !== ''): ?>
    <div class="u-results-info reveal">
      <span class="u-rinfo">
        <i class="fa-solid fa-circle-info"></i>
        Menampilkan <b><?= $total ?></b> artikel
        <?php if ($kat !== ''): ?> dalam kategori <b><?= e($kat) ?></b><?php endif; ?>
        <?php if ($bulan !== ''): ?> periode <b><?= e(date('F Y', strtotime($bulan.'-01'))) ?></b><?php endif; ?>
        <?php if ($q !== ''): ?> untuk kata kunci "<b><?= e($q) ?></b>"<?php endif; ?>
      </span>
      <a href="berita.php" style="font-size:12.5px;font-weight:700;color:var(--blue)"><i class="fa-solid fa-xmark"></i> Reset Filter</a>
    </div>
    <?php endif; ?>

    <div class="u-blog-layout">
      <div class="u-main">
        <?php if (!$list): ?>
        <div class="u-empty reveal">
          <i class="fa-solid fa-inbox"></i>
          <h3>Artikel tidak ditemukan</h3>
          <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
          <a href="berita.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
        </div>
        <?php else: ?>
        <div class="berita-grid <?= $view === 'list' ? 'u-list' : 'u-grid-2' ?>">
          <?php foreach ($list as $idx => $b): $readTime = max(1, (int)round(str_word_count(strip_tags((string)$b['isi'])) / 200)); ?>
          <a class="berita-card reveal" href="<?= url('berita-detail.php?slug=' . e($b['slug'])) ?>">
            <div class="bc-img">
              <img src="<?= e(media_url($b['gambar'])) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
              <span class="bc-cat"><?= e($b['kategori']) ?></span>
              <?php if ((int)$b['views'] > 100): ?><span style="position:absolute;top:14px;right:14px;background:#e5484d;color:#fff;font-size:10px;font-weight:800;letter-spacing:1px;padding:4px 10px;border-radius:99px"><i class="fa-solid fa-fire"></i> TRENDING</span><?php endif; ?>
            </div>
            <div class="bc-body">
              <span class="bc-meta">
                <i class="fa-regular fa-calendar-days"></i> <?= date('d M Y', strtotime($b['tanggal'])) ?>
                &nbsp;•&nbsp;<i class="fa-regular fa-eye"></i> <?= (int)$b['views'] ?>
                &nbsp;•&nbsp;<i class="fa-regular fa-clock"></i> <?= $readTime ?> mnt
              </span>
              <h3><?= e($b['judul']) ?></h3>
              <?php if ($view === 'list'): ?>
              <p style="font-size:13.5px;color:#68758a;line-height:1.7;margin-bottom:14px"><?= e(mb_strimwidth(trim(strip_tags($b['isi'])), 0, 180, '...')) ?></p>
              <?php endif; ?>
              <span class="bc-more">Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <div class="u-pagination reveal">
          <?php
          $qsNav = fn($p) => '?' . $qs(['hal' => $p]);
          ?>
          <a class="<?= $pageNo <= 1 ? 'u-dis' : '' ?> u-arr" href="<?= $pageNo > 1 ? $qsNav($pageNo - 1) : '#' ?>"><i class="fa-solid fa-chevron-left"></i></a>
          <?php
          $start = max(1, $pageNo - 2);
          $end   = min($pages, $pageNo + 2);
          if ($start > 1) { echo '<a href="' . $qsNav(1) . '">1</a>'; if ($start > 2) echo '<span class="u-dots">...</span>'; }
          for ($i = $start; $i <= $end; $i++) {
              echo '<a class="' . ($i === $pageNo ? 'on' : '') . '" href="' . $qsNav($i) . '">' . $i . '</a>';
          }
          if ($end < $pages) { if ($end < $pages - 1) echo '<span class="u-dots">...</span>'; echo '<a href="' . $qsNav($pages) . '">' . $pages . '</a>'; }
          ?>
          <a class="<?= $pageNo >= $pages ? 'u-dis' : '' ?> u-arr" href="<?= $pageNo < $pages ? $qsNav($pageNo + 1) : '#' ?>"><i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- ===== SIDEBAR ===== -->
      <aside class="u-side">
        <div class="u-side-card">
          <h4><i class="fa-solid fa-fire" style="color:#00b874"></i>Artikel Terpopuler<span class="u-badge">HOT</span></h4>
          <?php $rank = 0; foreach ($populer as $p): $rank++; $rt = max(1, (int)round(str_word_count(strip_tags((string)($p['isi'] ?? ''))) / 200)); ?>
          <a class="u-pop-item" href="<?= url('berita-detail.php?slug=' . e($p['slug'])) ?>">
            <span class="u-rank">#<?= $rank ?></span>
            <img src="<?= e(media_url($p['gambar']) ?: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=400&q=60') ?>" alt="" loading="lazy">
            <span>
              <b><?= e($p['judul']) ?></b>
              <small><i class="fa-regular fa-eye"></i><?= (int)$p['views'] ?> • <i class="fa-regular fa-clock"></i><?= $rt ?> mnt • <?= date('d M Y', strtotime($p['tanggal'])) ?></small>
            </span>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="u-side-card">
          <h4><i class="fa-solid fa-tags" style="color:#00b874"></i>Kategori</h4>
          <?php foreach ($kats as $k): ?>
          <a class="u-kat-item" href="?kat=<?= urlencode($k['kategori']) ?>">
            <span><i class="fa-solid fa-angle-right" style="color:#00b874;margin-right:6px"></i><?= e($k['kategori']) ?></span>
            <span class="cnt"><?= (int)$k['c'] ?></span>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="u-side-card">
          <h4><i class="fa-solid fa-calendar-week" style="color:#00b874"></i>Arsip Bulanan</h4>
          <?php foreach ($arsip as $a): ?>
          <a class="u-archive-item" href="?bulan=<?= urlencode($a['ym']) ?>">
            <span><i class="fa-regular fa-folder"></i><?= e($a['label']) ?></span>
            <span><?= (int)$a['c'] ?> artikel</span>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="u-quote-card reveal">
          <p><?= e($todayQuote[0]) ?></p>
          <small><?= e($todayQuote[1]) ?></small>
        </div>

        <div class="u-newsletter reveal">
          <h4><i class="fa-solid fa-paper-plane" style="margin-right:8px"></i>Berlangganan</h4>
          <p>Dapatkan update artikel terbaru langsung ke email Anda.</p>
          <form class="u-nl-form" onsubmit="alert('Terima kasih telah berlangganan!');return false;">
            <input type="email" placeholder="alamat@email.com" required>
            <button type="submit"><i class="fa-solid fa-bell"></i> BERLANGGANAN</button>
          </form>
          <small><i class="fa-solid fa-lock"></i> Kami menghormati privasi Anda.</small>
        </div>

        <div class="u-side-card">
          <h4><i class="fa-solid fa-hashtag" style="color:#00b874"></i>Tag Populer</h4>
          <div class="u-tag-cloud">
            <a href="?kat=Prestasi">#Prestasi</a>
            <a href="?kat=Akademik">#Akademik</a>
            <a href="?kat=Kegiatan">#Kegiatan</a>
            <a href="?kat=Riset">#Riset</a>
            <a href="?kat=Mahasiswa">#Mahasiswa</a>
            <a href="?kat=Dosen">#Dosen</a>
            <a href="?kat=Laboratorium">#Lab</a>
            <a href="?kat=Beasiswa">#Beasiswa</a>
            <a href="?kat=Seminar">#Seminar</a>
            <a href="?kat=Publikasi">#Publikasi</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<script>
/* ========= BERITA ULTIMATE — INLINE INTERACTION ========= */
(function(){
  /* Tilt halus untuk featured card */
  var featured = document.querySelector('.u-featured');
  if (featured && window.matchMedia('(pointer: fine)').matches) {
    featured.addEventListener('mousemove', function(ev){
      var r = featured.getBoundingClientRect();
      var x = (ev.clientX - r.left) / r.width - .5;
      var y = (ev.clientY - r.top) / r.height - .5;
      featured.style.transform = 'perspective(1000px) rotateY(' + (x * 6).toFixed(2) + 'deg) rotateX(' + (-y * 5).toFixed(2) + 'deg) translateY(-6px)';
    });
    featured.addEventListener('mouseleave', function(){ featured.style.transform = ''; });
  }

  /* Animasi counter hero stats */
  var counters = document.querySelectorAll('.u-hero-stats .hs b');
  if (counters.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if (!en.isIntersecting) return;
        var el = en.target;
        var text = el.textContent.replace(/[^\d]/g,'');
        var target = parseInt(text) || 0;
        var suffix = el.textContent.replace(/[\d]/g,'');
        var n = 0, step = Math.max(1, Math.ceil(target / 50));
        var t = setInterval(function(){
          n += step;
          if (n >= target) { n = target; clearInterval(t); }
          el.innerHTML = n.toLocaleString('id-ID') + '<em>' + (suffix || '') + '</em>';
        }, 24);
        io.unobserve(el);
      });
    }, { threshold: .5 });
    counters.forEach(function(c){ io.observe(c); });
  }

  /* Auto-submit filter bulan */
  document.querySelectorAll('.u-filter-select').forEach(function(s){
    s.addEventListener('change', function(){ this.form && this.form.submit(); });
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>