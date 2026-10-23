<?php
require_once __DIR__ . '/includes/header.php';
$slug = slugify($_GET['slug'] ?? '');
$page = qp($conn, "SELECT * FROM pages WHERE slug=? AND status=1 LIMIT 1", 's', [$slug])[0] ?? null;
if (!$page) { echo '<div class="container" style="padding:120px 20px"><h2>Halaman tidak ditemukan.</h2></div>'; require_once __DIR__.'/includes/footer.php'; exit; }
$others = qp($conn, "SELECT slug, judul, konten FROM pages WHERE status=1 AND slug<>? ORDER BY urutan ASC LIMIT 6", 's', [$slug]);
$all    = q($conn, "SELECT slug, judul FROM pages WHERE status=1 ORDER BY urutan ASC");
$relatedBerita = qp($conn, "SELECT * FROM berita WHERE status=1 AND (judul LIKE ? OR isi LIKE ?) ORDER BY tanggal DESC LIMIT 3", 'ss', ['%'.$page['judul'].'%', '%'.$page['judul'].'%']);

/* Extract headings untuk TOC */
$headings = [];
preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/i', (string)$page['konten'], $matches, PREG_SET_ORDER);
foreach ($matches as $idx => $m) {
    $title  = strip_tags($m[2]);
    $anchor = 'sec-' . md5($title . $idx);
    $headings[] = ['level' => (int)$m[1], 'title' => $title, 'anchor' => $anchor];
    $page['konten'] = preg_replace('/' . preg_quote($m[0], '/') . '/', '<h' . $m[1] . ' id="' . $anchor . '">' . $m[2] . '</h' . $m[1] . '>', $page['konten'], 1);
}

$wordCount = str_word_count(strip_tags((string)$page['konten']));
$readTime  = max(1, (int)round($wordCount / 200));

/* Icon per slug */
$iconMap = ['sejarah'=>'fa-landmark','visi-misi'=>'fa-bullseye','struktur-organisasi'=>'fa-sitemap','kurikulum'=>'fa-book-open','laboratorium'=>'fa-flask','penelitian'=>'fa-microscope','kontak'=>'fa-headset'];
$curIcon = $iconMap[$slug] ?? 'fa-file-lines';
?>
<style>
/* ========= ULTIMATE PAGE ========= */
.u-page-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 50%,var(--blue) 100%);padding:110px 0 80px;color:#fff;overflow:hidden}
.u-page-hero::before{content:'';position:absolute;inset:0;background:
  radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),
  radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-page-hero::after{content:'\f5d2';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:8%;top:50%;transform:translateY(-50%);font-size:340px;color:rgba(255,255,255,.04);animation:uSpin 50s linear infinite}
.u-page-hero .u-inner{position:relative;z-index:2;max-width:820px}
.u-page-hero .u-breadcrumb{display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);backdrop-filter:blur(10px);padding:8px 16px;border-radius:99px;font-size:12px;font-weight:600;color:#c8d6ea;margin-bottom:18px;border:1px solid rgba(255,255,255,.12)}
.u-page-hero .u-breadcrumb a{color:#c8d6ea;transition:.25s}
.u-page-hero .u-breadcrumb a:hover{color:#7ef0c4}
.u-page-hero .u-breadcrumb i{color:#7ef0c4}
.u-page-hero .u-cat{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(90deg,#00b874,#00915c);color:#04231a;padding:7px 16px;border-radius:99px;font-size:11.5px;font-weight:800;letter-spacing:2px;margin-bottom:16px}
.u-page-hero h1{font-family:var(--display);font-size:clamp(34px,5vw,54px);line-height:1.15;margin-bottom:16px}
.u-page-hero h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-page-hero .u-meta{display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:#c8d6ea;font-weight:600;margin-top:16px}
.u-page-hero .u-meta span{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.06);padding:7px 14px;border-radius:99px;border:1px solid rgba(255,255,255,.1)}
.u-page-hero .u-meta i{color:#7ef0c4}

.u-page-actions{display:flex;gap:10px;margin-top:24px;flex-wrap:wrap}
.u-page-actions button{padding:10px 20px;border:1.4px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);backdrop-filter:blur(10px);color:#fff;border-radius:11px;font-weight:700;font-size:12.5px;letter-spacing:.5px;cursor:pointer;transition:.3s;display:inline-flex;align-items:center;gap:8px}
.u-page-actions button:hover{background:var(--grad);border-color:transparent;transform:translateY(-3px)}

.u-page-tools{position:fixed;right:26px;top:50%;transform:translateY(-50%);z-index:500;display:flex;flex-direction:column;gap:10px}
.u-page-tools button{width:46px;height:46px;border-radius:12px;border:0;background:#fff;color:var(--blue-dark);font-size:15px;cursor:pointer;box-shadow:var(--shadow);transition:.3s;display:grid;place-items:center;border:1.6px solid var(--line)}
.u-page-tools button:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-3px) scale(1.08)}

.u-page-wrap{display:grid;grid-template-columns:260px 1fr;gap:40px;align-items:start}
.u-toc-side{position:sticky;top:96px;background:#fff;border-radius:18px;padding:24px;box-shadow:var(--shadow);border:1px solid var(--line);max-height:calc(100vh - 120px);overflow-y:auto;scrollbar-width:thin}
.u-toc-side::-webkit-scrollbar{width:6px}.u-toc-side::-webkit-scrollbar-thumb{background:var(--grad);border-radius:99px}
.u-toc-side h4{font-family:var(--display);color:var(--blue-dark);font-size:14px;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:8px}
.u-toc-side h4::after{content:'';position:absolute;left:0;bottom:-2px;width:36px;height:2px;background:var(--grad)}
.u-toc-side h4 i{color:#00b874}
.u-toc-side .u-toc-list{display:flex;flex-direction:column;gap:6px}
.u-toc-side .u-toc-list a{padding:9px 14px;border-radius:10px;font-size:12.5px;font-weight:600;color:#475569;transition:.25s;text-decoration:none;display:flex;align-items:center;gap:10px;position:relative;border-left:3px solid transparent}
.u-toc-side .u-toc-list a.lvl3{padding-left:28px;font-size:12px}
.u-toc-side .u-toc-list a::before{content:'▸';color:#00b874;font-weight:800}
.u-toc-side .u-toc-list a:hover{background:var(--gray);color:var(--blue);border-left-color:#00b874}
.u-toc-side .u-toc-list a.active{background:linear-gradient(135deg,rgba(10,102,194,.08),rgba(0,184,116,.08));color:var(--blue-dark);border-left-color:#00b874;font-weight:700}

.u-toc-side .u-side-meta{margin-top:24px;padding-top:18px;border-top:2px solid var(--line);display:flex;flex-direction:column;gap:10px}
.u-toc-side .u-side-meta .u-sd{display:flex;align-items:center;gap:10px;font-size:12.5px;color:#68758a}
.u-toc-side .u-side-meta .u-sd i{color:#00b874;width:18px}
.u-toc-side .u-side-meta .u-sd b{color:var(--blue-dark);font-family:var(--display);font-size:14px}

.u-main-side{display:flex;flex-direction:column;gap:26px}
.u-right-side{display:flex;flex-direction:column;gap:22px;margin-top:26px}
.u-right-card{background:#fff;border-radius:18px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--line);position:relative;overflow:hidden}
.u-right-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-right-card h4{font-family:var(--display);color:var(--blue-dark);font-size:14px;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:10px}
.u-right-card h4::after{content:'';position:absolute;left:0;bottom:-2px;width:40px;height:2px;background:var(--grad)}
.u-right-card h4 i{color:#00b874}

.u-content{background:#fff;border-radius:18px;padding:44px 50px;box-shadow:var(--shadow);border:1px solid var(--line);line-height:1.95;color:#2b3a55;font-size:15.5px}
.u-content p{margin-bottom:18px}
.u-content h2,.u-content h3{font-family:var(--display);color:var(--blue-dark);margin:34px 0 14px;position:relative;padding-left:16px;border-left:4px solid #00b874}
.u-content h2{font-size:24px}.u-content h3{font-size:19px}
.u-content blockquote{margin:24px 0;padding:22px 28px;background:linear-gradient(135deg,#f0fdf7,#ecfeff);border-left:4px solid #00b874;border-radius:12px;font-style:italic;font-size:16px;color:#0a2a5e;position:relative}
.u-content blockquote::before{content:'"';position:absolute;top:-12px;left:14px;font-family:var(--display);font-size:60px;color:rgba(0,184,116,.4);line-height:1}
.u-content ul,.u-content ol{margin:16px 0 16px 28px}
.u-content li{margin-bottom:10px}
.u-content strong{color:var(--blue-dark)}
.u-content a{color:var(--blue);font-weight:700}
.u-content img{border-radius:14px;margin:20px 0}

.u-other-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px dashed var(--line);transition:.25s;text-decoration:none;align-items:center}
.u-other-item:last-child{border:0}
.u-other-item:hover{transform:translateX(5px)}
.u-other-item .u-oi-ic{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:var(--blue);display:grid;place-items:center;font-size:15px;flex:none;transition:.3s}
.u-other-item:hover .u-oi-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.u-other-item b{color:var(--blue-dark);font-size:13.5px;display:block;font-family:var(--display);line-height:1.5}
.u-other-item small{color:#8a97ab;font-size:11.5px;display:block;margin-top:2px}
.u-other-item i.arr{margin-left:auto;color:#00b874;opacity:0;transform:translateX(-6px);transition:.25s}
.u-other-item:hover i.arr{opacity:1;transform:none}

.u-contact-card{background:linear-gradient(135deg,#0a2a5e 0%,#071a39 100%);color:#fff;border-radius:18px;padding:26px;position:relative;overflow:hidden}
.u-contact-card::before{content:'';position:absolute;right:-30px;top:-30px;width:100px;height:100px;border:2px dashed rgba(0,184,116,.35);border-radius:50%;animation:uSpin 18s linear infinite}
.u-contact-card h4{color:#fff;border-bottom-color:rgba(255,255,255,.12)}
.u-contact-card h4::after{background:linear-gradient(90deg,#7ef0c4,#4da3ff)}
.u-contact-card .u-ci{display:flex;gap:12px;align-items:flex-start;padding:10px 0;font-size:13px;color:#c8d6ea;line-height:1.7;position:relative;z-index:1}
.u-contact-card .u-ci i{color:#7ef0c4;width:22px;margin-top:4px}
.u-contact-card .u-ci b{color:#fff;display:block;font-size:13.5px;margin-bottom:2px}

.u-rel-news{display:flex;flex-direction:column;gap:14px}
.u-rel-news a{display:flex;gap:12px;text-decoration:none;transition:.25s;padding:8px;border-radius:10px}
.u-rel-news a:hover{background:var(--gray);transform:translateX(4px)}
.u-rel-news a img{width:70px;height:54px;border-radius:9px;object-fit:cover;flex:none}
.u-rel-news a b{font-size:13px;color:var(--blue-dark);display:block;line-height:1.5;font-family:var(--display)}
.u-rel-news a small{color:#8a97ab;font-size:11.5px;display:block;margin-top:3px}

.u-related-pages{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:34px}
.u-rp-card{background:#fff;border:1.6px solid var(--line);border-radius:16px;padding:24px;text-decoration:none;transition:.3s;position:relative;overflow:hidden;display:block}
.u-rp-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:.4s}
.u-rp-card:hover::before{transform:scaleX(1)}
.u-rp-card:hover{transform:translateY(-6px);border-color:rgba(0,184,116,.4);box-shadow:var(--shadow)}
.u-rp-card .u-rp-ic{width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:var(--blue);display:grid;place-items:center;font-size:20px;margin-bottom:14px;transition:.3s}
.u-rp-card:hover .u-rp-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.u-rp-card h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:8px}
.u-rp-card p{font-size:12.5px;color:#68758a;line-height:1.65}
.u-rp-card .u-rp-go{display:inline-flex;gap:6px;color:#00b874;font-weight:700;font-size:11.5px;letter-spacing:.5px;margin-top:10px}
.u-rp-card:hover .u-rp-go i{transform:translateX(4px)}
.u-rp-card .u-rp-go i{transition:.3s}

.u-page-share{margin-top:28px;padding-top:22px;border-top:2px solid var(--line);display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.u-page-share span{font-weight:700;color:var(--blue-dark);font-size:13px}
.u-page-share a{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;color:#fff;font-size:14px;transition:.3s;text-decoration:none}
.u-page-share a:hover{transform:translateY(-4px) rotate(-6deg)}

@media(max-width:1100px){.u-page-wrap{grid-template-columns:1fr}.u-toc-side{position:static;max-height:none}.u-related-pages{grid-template-columns:1fr 1fr}.u-content{padding:30px 24px}.u-page-tools{display:none}}
@media(max-width:600px){.u-related-pages{grid-template-columns:1fr}}
</style>

<!-- ===== HERO ===== -->
<section class="u-page-hero">
  <div class="container u-inner reveal">
    <span class="u-breadcrumb">
      <a href="<?= url('index.php') ?>"><i class="fa-solid fa-house"></i> Beranda</a>
      <i class="fa-solid fa-angle-right"></i>
      <a href="<?= url('index.php') ?>">Halaman</a>
      <i class="fa-solid fa-angle-right"></i>
      <span style="color:#fff;font-weight:700"><?= e($page['judul']) ?></span>
    </span>
    <span class="u-cat"><i class="fa-solid <?= e($curIcon) ?>"></i> HALAMAN RESMI</span>
    <h1><?= e($page['judul']) ?></h1>
    <div class="u-meta">
      <span><i class="fa-solid fa-font"></i> <?= number_format($wordCount) ?> kata</span>
      <span><i class="fa-regular fa-clock"></i> <?= $readTime ?> menit baca</span>
      <span><i class="fa-regular fa-calendar"></i> Diperbarui <?= date('d F Y') ?></span>
      <span><i class="fa-solid fa-check-double"></i> Terverifikasi</span>
    </div>
    <div class="u-page-actions">
      <button onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
      <button onclick="navigator.clipboard && navigator.clipboard.writeText(location.href).then(()=>alert('Tautan disalin!'))"><i class="fa-solid fa-link"></i> Salin Tautan</button>
      <button onclick="window.open('https://wa.me/?text=<?= urlencode($page['judul'].' - '.url('page.php?slug='.$page['slug'])) ?>','_blank')"><i class="fa-brands fa-whatsapp"></i> Bagikan</button>
      <button onclick="document.body.classList.toggle('u-font-lg')"><i class="fa-solid fa-text-height"></i> Perbesar</button>
    </div>
  </div>
</section>

<!-- Floating tools -->
<div class="u-page-tools">
  <button onclick="document.documentElement.scrollTo({top:0,behavior:'smooth'})" title="Ke Atas"><i class="fa-solid fa-arrow-up"></i></button>
  <button onclick="window.print()" title="Cetak"><i class="fa-solid fa-print"></i></button>
  <button onclick="navigator.clipboard && navigator.clipboard.writeText(location.href).then(()=>alert('Tautan disalin!'))" title="Salin Tautan"><i class="fa-solid fa-link"></i></button>
  <button onclick="window.history.back()" title="Kembali"><i class="fa-solid fa-arrow-left"></i></button>
</div>

<!-- ===== CONTENT ===== -->
<section class="page-sec">
  <div class="container">
    <div class="u-page-wrap">
      <!-- LEFT: TOC Sticky -->
      <?php if ($headings): ?>
      <aside class="u-toc-side">
        <h4><i class="fa-solid fa-list-ol"></i> Daftar Isi</h4>
        <div class="u-toc-list">
          <?php foreach ($headings as $h): ?>
          <a class="<?= $h['level'] === 3 ? 'lvl3' : '' ?>" href="#<?= e($h['anchor']) ?>"><?= e($h['title']) ?></a>
          <?php endforeach; ?>
        </div>
        <div class="u-side-meta">
          <div class="u-sd"><i class="fa-solid fa-font"></i><div><small style="display:block;color:#8a97ab;font-size:10.5px">Total Kata</small><b><?= number_format($wordCount) ?></b></div></div>
          <div class="u-sd"><i class="fa-regular fa-clock"></i><div><small style="display:block;color:#8a97ab;font-size:10.5px">Waktu Baca</small><b><?= $readTime ?> menit</b></div></div>
          <div class="u-sd"><i class="fa-solid fa-list"></i><div><small style="display:block;color:#8a97ab;font-size:10.5px">Bagian</small><b><?= count($headings) ?></b></div></div>
        </div>
      </aside>
      <?php else: ?>
      <aside class="u-toc-side">
        <h4><i class="fa-solid fa-compass"></i> Navigasi Cepat</h4>
        <div class="u-toc-list">
          <?php foreach ($all as $a): ?>
          <a href="<?= url('page.php?slug=' . e($a['slug'])) ?>" class="<?= $a['slug'] === $slug ? 'active' : '' ?>"><?= e($a['judul']) ?></a>
          <?php endforeach; ?>
        </div>
      </aside>
      <?php endif; ?>

      <div class="u-main-side">
        <article class="u-content reveal"><?= render_content($page['konten']) ?></article>

        <?php if (!empty($page['gambar'])): ?>
        <div class="u-content reveal" style="padding:24px;text-align:center">
          <img src="<?= e(media_url($page['gambar'])) ?>" alt="<?= e($page['judul']) ?>" style="max-width:100%;margin:0">
        </div>
        <?php endif; ?>

        <?php if ($relatedBerita): ?>
        <div class="u-content reveal">
          <h3 style="border-left:0;padding-left:0;font-size:18px;margin-top:0"><i class="fa-solid fa-newspaper" style="color:#00b874;margin-right:10px"></i>Berita Terkait</h3>
          <div class="u-rel-news">
            <?php foreach ($relatedBerita as $rb): ?>
            <a href="<?= url('berita-detail.php?slug=' . e($rb['slug'])) ?>">
              <img src="<?= e(media_url($rb['gambar'])) ?>" alt="" loading="lazy">
              <span><b><?= e($rb['judul']) ?></b><small><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y', strtotime($rb['tanggal'])) ?> • <i class="fa-regular fa-eye"></i> <?= (int)$rb['views'] ?> dilihat</small></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="u-page-share">
          <span><i class="fa-solid fa-share-nodes" style="color:#00b874;margin-right:6px"></i>Bagikan Halaman:</span>
          <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode(url('page.php?slug=' . $page['slug'])) ?>" target="_blank" style="background:#1877f2"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="https://wa.me/?text=<?= urlencode($page['judul'].' '.url('page.php?slug='.$page['slug'])) ?>" target="_blank" style="background:#25d366"><i class="fa-brands fa-whatsapp"></i></a>
          <a href="https://twitter.com/intent/tweet?text=<?= urlencode($page['judul']) ?>&url=<?= urlencode(url('page.php?slug='.$page['slug'])) ?>" target="_blank" style="background:#1da1f2"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://linkedin.com/shareArticle?url=<?= urlencode(url('page.php?slug='.$page['slug'])) ?>" target="_blank" style="background:#0a66c2"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>
      </div>
    </div>

    <!-- RIGHT SIDE CARDS -->
    <div class="u-right-side" style="max-width:880px;margin:40px auto 0">
      <div class="u-right-card reveal">
        <h4><i class="fa-solid fa-book-open"></i> Halaman Lainnya</h4>
        <?php foreach ($others as $o):
          parse_str((string)parse_url('page.php?slug='.$o['slug'], PHP_URL_QUERY), $qs);
          $ic = $iconMap[$qs['slug']] ?? 'fa-file-lines'; ?>
        <a class="u-other-item" href="<?= url('page.php?slug=' . e($o['slug'])) ?>">
          <span class="u-oi-ic"><i class="fa-solid <?= e($ic) ?>"></i></span>
          <span><b><?= e($o['judul']) ?></b><small><?= e(mb_strimwidth(trim(strip_tags($o['konten'])), 0, 70, '...')) ?></small></span>
          <i class="fa-solid fa-arrow-right arr"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="u-contact-card reveal">
        <h4><i class="fa-solid fa-headset" style="color:#7ef0c4"></i> Butuh Bantuan?</h4>
        <div class="u-ci"><i class="fa-solid fa-location-dot"></i><span><b>Alamat</b><?= e($S['alamat'] ?? '') ?></span></div>
        <div class="u-ci"><i class="fa-solid fa-phone"></i><span><b>Telepon</b><?= e($S['telepon'] ?? '') ?></span></div>
        <div class="u-ci"><i class="fa-solid fa-envelope"></i><span><b>Email</b><?= e($S['email'] ?? '') ?></span></div>
      </div>
    </div>

    <!-- RELATED PAGES GRID -->
    <?php if ($others): ?>
    <div class="sec-head reveal" style="margin-top:60px">
      <span class="sec-tag"><i class="fa-solid fa-layer-group"></i> JELAJAHI LEBIH LANJUT</span>
      <h2>Halaman <span class="grad-txt">Terkait</span></h2>
    </div>
    <div class="u-related-pages">
      <?php foreach ($others as $o):
        parse_str((string)parse_url('page.php?slug='.$o['slug'], PHP_URL_QUERY), $qs);
        $ic = $iconMap[$qs['slug']] ?? 'fa-file-lines';
        $exc = mb_strimwidth(trim(strip_tags($o['konten'])), 0, 110, '...'); ?>
      <a class="u-rp-card reveal" href="<?= url('page.php?slug=' . e($o['slug'])) ?>">
        <span class="u-rp-ic"><i class="fa-solid <?= e($ic) ?>"></i></span>
        <h4><?= e($o['judul']) ?></h4>
        <p><?= e($exc) ?></p>
        <span class="u-rp-go">BACA SELENGKAPNYA <i class="fa-solid fa-arrow-right"></i></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
/* ========= PAGE ULTIMATE ========= */
(function(){
  /* TOC smooth scroll + active state */
  var tocLinks = document.querySelectorAll('.u-toc-list a[href^="#"]');
  if (tocLinks.length) {
    tocLinks.forEach(function(a){
      a.addEventListener('click', function(ev){
        var id = this.getAttribute('href').slice(1);
        var el = document.getElementById(id);
        if (el) { ev.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      });
    });
    /* Active heading detection on scroll */
    if ('IntersectionObserver' in window) {
      var headings = Array.from(document.querySelectorAll('.u-content h2[id], .u-content h3[id]'));
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){
          if (en.isIntersecting) {
            tocLinks.forEach(function(l){ l.classList.remove('active'); });
            var active = document.querySelector('.u-toc-list a[href="#' + en.target.id + '"]');
            if (active) active.classList.add('active');
          }
        });
      }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
      headings.forEach(function(h){ io.observe(h); });
    }
  }
})();
</script>
<style>body.u-font-lg .u-content{font-size:18px!important}body.u-font-lg .u-content h2{font-size:28px!important}body.u-font-lg .u-content h3{font-size:22px!important}</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>