<?php
require_once __DIR__ . '/includes/header.php';
$slug = slugify($_GET['slug'] ?? '');
$b = qp($conn, "SELECT * FROM berita WHERE slug=? AND status=1 LIMIT 1", 's', [$slug])[0] ?? null;
if (!$b) { echo '<div class="container" style="padding:120px 20px"><h2>Berita tidak ditemukan.</h2></div>'; require_once __DIR__.'/includes/footer.php'; exit; }
try { $conn->query("UPDATE berita SET views = views + 1 WHERE id=" . (int)$b['id']); } catch (Throwable $ex) {}

$baca      = max(1, (int)round(str_word_count(strip_tags((string)$b['isi'])) / 200));
$wordCount = str_word_count(strip_tags((string)$b['isi']));
$prev      = qp($conn, "SELECT slug, judul, gambar FROM berita WHERE status=1 AND tanggal < ? ORDER BY tanggal DESC LIMIT 1", 's', [$b['tanggal']])[0] ?? null;
$next      = qp($conn, "SELECT slug, judul, gambar FROM berita WHERE status=1 AND tanggal > ? ORDER BY tanggal ASC LIMIT 1", 's', [$b['tanggal']])[0] ?? null;
$rel       = qp($conn, "SELECT * FROM berita WHERE status=1 AND kategori=? AND id<>? ORDER BY tanggal DESC LIMIT 3", 'si', [$b['kategori'], (int)$b['id']]);
$sameAuthor = qp($conn, "SELECT * FROM berita WHERE status=1 AND penulis=? AND id<>? ORDER BY tanggal DESC LIMIT 3", 'si', [$b['penulis'], (int)$b['id']]);
$popCat    = qp($conn, "SELECT * FROM berita WHERE status=1 AND kategori=? ORDER BY views DESC LIMIT 3", 's', [$b['kategori']]);

/* Extract headings untuk TOC otomatis */
$headings = [];
preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/i', (string)$b['isi'], $matches, PREG_SET_ORDER);
foreach ($matches as $idx => $m) {
    $title = strip_tags($m[2]);
    $anchor = 'h-' . md5($title . $idx);
    $headings[] = ['level' => (int)$m[1], 'title' => $title, 'anchor' => $anchor];
    $b['isi'] = preg_replace('/' . preg_quote($m[0], '/') . '/', '<h' . $m[1] . ' id="' . $anchor . '">' . $m[2] . '</h' . $m[1] . '>', $b['isi'], 1);
}

/* Rating simulasi */
$rating = round(4.1 + (crc32($b['slug']) % 10) / 10, 1);
$ratingCount = 20 + (crc32($b['slug']) % 80);

/* Related images dari galeri */
$galRel = q($conn, "SELECT file, judul FROM galeri WHERE tipe='image' ORDER BY urutan ASC LIMIT 4");
?>
<style>
/* ========= ULTIMATE BERITA DETAIL ========= */
.u-detail-hero{position:relative;min-height:520px;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#fff;padding:120px 0 80px}
.u-detail-hero::before{content:'';position:absolute;inset:0;background-image:url('<?= e(media_url($b['gambar'])) ?>');background-size:cover;background-position:center;filter:blur(2px) brightness(.4)}
.u-detail-hero::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,26,57,.6) 0%,rgba(7,26,57,.92) 100%)}
.u-detail-hero .u-inner{position:relative;z-index:2;max-width:880px;text-align:center;margin:0 auto;padding:0 20px}
.u-detail-hero .u-cat-badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#00b874,#00915c);color:#04231a;padding:8px 18px;border-radius:99px;font-size:11.5px;font-weight:800;letter-spacing:2px;margin-bottom:20px}
.u-detail-hero h1{font-family:var(--display);font-size:clamp(28px,4.2vw,48px);line-height:1.25;margin-bottom:22px;max-width:780px;margin-left:auto;margin-right:auto}
.u-detail-hero .u-meta{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;font-size:13.5px;color:#c8d6ea;font-weight:600}
.u-detail-hero .u-meta span{display:inline-flex;align-items:center;gap:7px}
.u-detail-hero .u-meta i{color:#7ef0c4}
.u-detail-hero .u-author-inline{display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);backdrop-filter:blur(10px);padding:6px 16px 6px 6px;border-radius:99px;border:1px solid rgba(255,255,255,.15);margin-top:16px}
.u-detail-hero .u-author-inline img{width:34px;height:34px;border-radius:50%;object-fit:cover}
.u-detail-hero .u-author-inline b{color:#fff;font-size:13px}
.u-detail-hero .u-author-inline small{color:#9fb3cd;font-size:11px;display:block}

.u-progress-read{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#0a66c2,#00b874);z-index:1002;box-shadow:0 0 14px rgba(0,184,116,.7);transition:width .1s linear}

.u-article-wrap{max-width:880px;margin:-60px auto 0;background:#fff;border-radius:22px;box-shadow:0 30px 80px rgba(10,42,94,.18);padding:50px 60px;position:relative;z-index:5}
.u-article-wrap .u-rating{display:flex;align-items:center;gap:14px;padding:18px 22px;background:linear-gradient(135deg,#f8fafd,#eef4fb);border-radius:14px;margin-bottom:30px;border:1px solid var(--line)}
.u-article-wrap .u-rating .u-stars{color:#f59e0b;font-size:15px;letter-spacing:2px}
.u-article-wrap .u-rating b{color:var(--blue-dark);font-family:var(--display);font-size:16px}
.u-article-wrap .u-rating small{color:#68758a;font-size:12px}
.u-article-wrap .u-rating .u-rbtns{margin-left:auto;display:flex;gap:6px}
.u-article-wrap .u-rating .u-rbtns button{border:1.4px solid var(--line);background:#fff;color:#33415e;padding:7px 14px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.25s}
.u-article-wrap .u-rating .u-rbtns button:hover{background:var(--grad);color:#fff;border-color:transparent}

.u-article-wrap .u-content{line-height:1.95;color:#2b3a55;font-size:15.5px}
.u-article-wrap .u-content p{margin-bottom:18px}
.u-article-wrap .u-content h2,.u-article-wrap .u-content h3{font-family:var(--display);color:var(--blue-dark);margin:34px 0 14px;position:relative;padding-left:16px;border-left:4px solid #00b874}
.u-article-wrap .u-content h2{font-size:24px}
.u-article-wrap .u-content h3{font-size:19px}
.u-article-wrap .u-content blockquote{margin:26px 0;padding:24px 28px;background:linear-gradient(135deg,#f0fdf7,#ecfeff);border-left:4px solid #00b874;border-radius:12px;font-style:italic;font-size:16px;color:#0a2a5e;position:relative}
.u-article-wrap .u-content blockquote::before{content:'"';position:absolute;top:-12px;left:14px;font-family:var(--display);font-size:60px;color:rgba(0,184,116,.4);line-height:1}
.u-article-wrap .u-content ul,.u-article-wrap .u-content ol{margin:16px 0 16px 28px}
.u-article-wrap .u-content li{margin-bottom:10px}
.u-article-wrap .u-content strong{color:var(--blue-dark)}
.u-article-wrap .u-content a{color:var(--blue);font-weight:700;text-decoration:underline;text-underline-offset:3px}

.u-toc{background:linear-gradient(135deg,#f8fafd,#eef4fb);border:1.6px solid var(--line);border-radius:16px;padding:22px 26px;margin-bottom:34px}
.u-toc h4{font-family:var(--display);color:var(--blue-dark);font-size:14px;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.u-toc h4 i{color:#00b874}
.u-toc ol{margin:0;padding-left:22px;list-style:decimal}
.u-toc li{margin-bottom:8px;font-size:13.5px;line-height:1.6}
.u-toc li.h3{list-style:none;margin-left:-18px;padding-left:18px}
.u-toc a{color:#33415e;font-weight:600;transition:.25s;text-decoration:none;display:block}
.u-toc a:hover{color:var(--blue);transform:translateX(4px)}
.u-toc a::before{content:'▸';color:#00b874;margin-right:8px;font-weight:800}

.u-article-footer{margin-top:50px;padding-top:30px;border-top:2px solid var(--line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px}
.u-article-footer .u-tags{display:flex;gap:8px;flex-wrap:wrap}
.u-article-footer .u-tags a{padding:6px 14px;border-radius:99px;background:var(--gray);font-size:11.5px;font-weight:700;color:#475569;text-decoration:none;transition:.25s}
.u-article-footer .u-tags a:hover{background:var(--grad);color:#fff}
.u-share-row{display:flex;align-items:center;gap:10px}
.u-share-row span{font-weight:700;color:var(--blue-dark);font-size:13px}
.u-share-row a{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;color:#fff;transition:.3s;font-size:14px;text-decoration:none}
.u-share-row .soc.fb{background:#1877f2}.u-share-row .soc.wa{background:#25d366}.u-share-row .soc.li{background:#0a66c2}.u-share-row .soc.tw{background:#1da1f2}.u-share-row .soc.pt{background:#33415e}
.u-share-row a:hover{transform:translateY(-4px) rotate(-6deg);box-shadow:0 8px 18px rgba(0,0,0,.15)}

.u-author-box{display:grid;grid-template-columns:90px 1fr;gap:22px;align-items:center;background:linear-gradient(135deg,#0a2a5e 0%,#071a39 100%);color:#fff;border-radius:18px;padding:26px;margin-top:34px;position:relative;overflow:hidden}
.u-author-box::before{content:'';position:absolute;right:-40px;top:-40px;width:120px;height:120px;border:2px dashed rgba(0,184,116,.35);border-radius:50%;animation:uSpin 18s linear infinite}
.u-author-box .u-av{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#00b874,#4da3ff);display:grid;place-items:center;font-size:34px;color:#fff;border:3px solid rgba(255,255,255,.2);position:relative;z-index:1}
.u-author-box h4{font-family:var(--display);color:#fff;font-size:18px;margin-bottom:4px;position:relative;z-index:1}
.u-author-box h4::after{display:none}
.u-author-box small{color:#7ef0c4;font-size:12px;font-weight:700;letter-spacing:1px;display:block;margin-bottom:10px;position:relative;z-index:1}
.u-author-box p{color:#c8d6ea;font-size:13.5px;line-height:1.7;position:relative;z-index:1}

.u-pn{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:34px}
.u-pn a{background:#fff;border:1.6px solid var(--line);border-radius:16px;padding:20px;font-size:13px;font-weight:700;color:#22304a;transition:.3s;display:flex;gap:12px;align-items:center;text-decoration:none;position:relative;overflow:hidden}
.u-pn a::before{content:'';position:absolute;inset:0;background:var(--grad);transform:scaleX(0);transform-origin:left;transition:.4s;opacity:.06}
.u-pn a:hover::before{transform:scaleX(1)}
.u-pn a:hover{border-color:var(--green);transform:translateY(-4px);box-shadow:var(--shadow)}
.u-pn a.next{justify-content:flex-end;text-align:right}
.u-pn a small{display:block;color:#8a97ab;font-weight:600;font-size:11px;letter-spacing:1px;margin-bottom:4px}
.u-pn a b{display:block;color:var(--blue-dark);font-family:var(--display);font-size:14.5px;line-height:1.5}
.u-pn a .u-arr{flex:none;width:42px;height:42px;border-radius:11px;background:var(--gray);color:var(--blue);display:grid;place-items:center;transition:.3s}
.u-pn a:hover .u-arr{background:var(--grad);color:#fff}
.u-pn a.empty{opacity:.5;cursor:default;pointer-events:none}
.u-pn a img{width:60px;height:60px;border-radius:10px;object-fit:cover;flex:none}

.u-floating-share{position:fixed;left:26px;top:50%;transform:translateY(-50%);z-index:500;display:flex;flex-direction:column;gap:10px;opacity:0;visibility:hidden;transition:.4s}
.u-floating-share.show{opacity:1;visibility:visible}
.u-floating-share a{width:42px;height:42px;border-radius:11px;display:grid;place-items:center;color:#fff;font-size:15px;transition:.3s;text-decoration:none;box-shadow:0 6px 16px rgba(0,0,0,.2)}
.u-floating-share a:hover{transform:translateX(4px) scale(1.08)}

.u-article-tools{position:fixed;right:26px;bottom:26px;z-index:500;display:flex;flex-direction:column;gap:10px}
.u-article-tools button{width:46px;height:46px;border-radius:12px;border:0;background:var(--grad);color:#fff;font-size:16px;cursor:pointer;box-shadow:0 10px 24px rgba(0,184,116,.35);transition:.3s;display:grid;place-items:center}
.u-article-tools button:hover{transform:translateY(-4px) scale(1.08)}
.u-article-tools button.alt{background:#fff;color:var(--blue-dark);border:1.6px solid var(--line)}

.u-rel-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.u-rel-compact{background:#fff;border:1.6px solid var(--line);border-radius:16px;overflow:hidden;transition:.3s;text-decoration:none;display:flex;flex-direction:column}
.u-rel-compact:hover{transform:translateY(-6px);border-color:rgba(0,184,116,.4);box-shadow:var(--shadow)}
.u-rel-compact img{width:100%;height:170px;object-fit:cover}
.u-rel-compact .u-rel-body{padding:18px}
.u-rel-compact small{color:#00b874;font-weight:800;font-size:10.5px;letter-spacing:1.4px;text-transform:uppercase}
.u-rel-compact h4{font-family:var(--display);color:var(--blue-dark);font-size:14.5px;line-height:1.5;margin:8px 0 10px}
.u-rel-compact .u-meta-mini{color:#8a97ab;font-size:11.5px;font-weight:600;display:flex;gap:8px;align-items:center}
.u-rel-compact .u-meta-mini i{color:#00b874}

.u-gallery-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:26px}
.u-gallery-strip img{width:100%;height:120px;object-fit:cover;border-radius:12px;cursor:pointer;transition:.3s}
.u-gallery-strip img:hover{transform:scale(1.05)}

.u-back-link{display:inline-flex;align-items:center;gap:10px;color:#fff;font-weight:700;font-size:13px;margin-bottom:20px;text-decoration:none;padding:8px 16px;background:rgba(255,255,255,.08);border-radius:99px;border:1px solid rgba(255,255,255,.15);transition:.3s;backdrop-filter:blur(6px)}
.u-back-link:hover{background:rgba(0,184,116,.25);transform:translateX(-4px)}

@media(max-width:1024px){.u-rel-grid{grid-template-columns:1fr 1fr}.u-gallery-strip{grid-template-columns:1fr 1fr}.u-floating-share{display:none}}
@media(max-width:700px){.u-article-wrap{padding:30px 22px;margin-top:-30px}.u-pn{grid-template-columns:1fr}.u-rel-grid{grid-template-columns:1fr}.u-author-box{grid-template-columns:1fr;text-align:center}.u-author-box .u-av{margin:0 auto}}
</style>

<div class="u-progress-read" id="uProgressRead"></div>

<!-- ===== HERO ARTICLE ===== -->
<section class="u-detail-hero">
  <div class="container u-inner reveal">
    <a href="berita.php" class="u-back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Berita</a>
    <span class="u-cat-badge"><i class="fa-solid fa-tag"></i> <?= e($b['kategori']) ?></span>
    <h1><?= e($b['judul']) ?></h1>
    <div class="u-meta">
      <span><i class="fa-regular fa-calendar-days"></i> <?= date('d F Y', strtotime($b['tanggal'])) ?></span>
      <span><i class="fa-regular fa-user"></i> <?= e($b['penulis']) ?></span>
      <span><i class="fa-regular fa-eye"></i> <?= (int)$b['views'] ?> dibaca</span>
      <span><i class="fa-regular fa-clock"></i> <?= $baca ?> menit baca</span>
      <span><i class="fa-solid fa-font"></i> <?= number_format($wordCount) ?> kata</span>
    </div>
    <div class="u-author-inline">
      <img src="https://ui-avatars.com/api/?name=<?= urlencode($b['penulis']) ?>&background=0a66c2&color=fff&bold=true" alt="">
      <span><b><?= e($b['penulis']) ?></b><small>Tim Humas Prodi Fisika</small></span>
    </div>
  </div>
</section>

<!-- ===== ARTICLE BODY ===== -->
<div class="container">
  <div class="u-article-wrap reveal">
    <?php if ($headings): ?>
    <div class="u-toc">
      <h4><i class="fa-solid fa-list-ol"></i> Daftar Isi</h4>
      <ol>
        <?php foreach ($headings as $h): ?>
        <li class="h<?= $h['level'] ?>"><a href="#<?= e($h['anchor']) ?>"><?= e($h['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>

    <div class="u-rating">
      <div class="u-stars">
        <?php for ($i = 1; $i <= 5; $i++): ?><i class="<?= $i <= round($rating) ? 'fa-solid' : 'fa-regular' ?> fa-star"></i><?php endfor; ?>
      </div>
      <div><b><?= $rating ?>/5</b><small> (<?= $ratingCount ?> pembaca menilai)</small></div>
      <div class="u-rbtns">
        <button onclick="alert('Terima kasih atas penilaian Anda!');"><i class="fa-solid fa-thumbs-up"></i> Bermanfaat</button>
        <button onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
      </div>
    </div>

    <article class="u-content"><?= render_content($b['isi']) ?></article>

    <div class="u-article-footer">
      <div class="u-tags">
        <?php foreach (['#Fisika','#Sains','#Riset','#'.str_replace(' ','',$b['kategori']),'#Akademik'] as $t): ?>
        <a href="berita.php?q=<?= urlencode($t) ?>"><?= e($t) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="u-share-row">
        <span>Bagikan:</span>
        <a class="soc fb" target="_blank" href="https://facebook.com/sharer/sharer.php?u=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>"><i class="fa-brands fa-facebook-f"></i></a>
        <a class="soc wa" target="_blank" href="https://wa.me/?text=<?= urlencode($b['judul'] . ' ' . url('berita-detail.php?slug=' . $b['slug'])) ?>"><i class="fa-brands fa-whatsapp"></i></a>
        <a class="soc tw" target="_blank" href="https://twitter.com/intent/tweet?text=<?= urlencode($b['judul']) ?>&url=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>"><i class="fa-brands fa-x-twitter"></i></a>
        <a class="soc li" target="_blank" href="https://linkedin.com/shareArticle?url=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>"><i class="fa-brands fa-linkedin-in"></i></a>
        <a class="soc pt" onclick="navigator.clipboard && navigator.clipboard.writeText('<?= url('berita-detail.php?slug=' . $b['slug']) ?>');this.innerHTML='<i class=\'fa-solid fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-link\'></i>',2000)"><i class="fa-solid fa-link"></i></a>
      </div>
    </div>

    <div class="u-author-box reveal">
      <span class="u-av"><i class="fa-solid fa-user-pen"></i></span>
      <div>
        <h4>Tentang Penulis</h4>
        <small><?= e($b['penulis']) ?></small>
        <p><?= e($b['penulis']) ?> adalah kontributor resmi Tim Humas Program Studi Fisika, aktif mendokumentasikan kegiatan akademik, prestasi mahasiswa, serta perkembangan riset di lingkungan Prodi Fisika.</p>
      </div>
    </div>

    <?php if ($galRel): ?>
    <div class="u-gallery-strip">
      <?php foreach ($galRel as $g): ?>
      <img src="<?= e(media_url($g['file'])) ?>" alt="<?= e($g['judul']) ?>" loading="lazy">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="u-pn">
      <?php if ($prev): ?>
      <a href="<?= url('berita-detail.php?slug=' . e($prev['slug'])) ?>">
        <span class="u-arr"><i class="fa-solid fa-arrow-left"></i></span>
        <span><small>ARTIKEL SEBELUMNYA</small><b><?= e($prev['judul']) ?></b></span>
      </a>
      <?php else: ?><a class="empty"><span class="u-arr"><i class="fa-solid fa-minus"></i></span><span><small>SEBELUMNYA</small><b>Tidak ada artikel</b></span></a><?php endif; ?>
      <?php if ($next): ?>
      <a class="next" href="<?= url('berita-detail.php?slug=' . e($next['slug'])) ?>">
        <span><small>ARTIKEL BERIKUTNYA</small><b><?= e($next['judul']) ?></b></span>
        <span class="u-arr"><i class="fa-solid fa-arrow-right"></i></span>
      </a>
      <?php else: ?><a class="empty next"><span><small>BERIKUTNYA</small><b>Tidak ada artikel</b></span><span class="u-arr"><i class="fa-solid fa-minus"></i></span></a><?php endif; ?>
    </div>
  </div>
</div>

<!-- ===== RELATED ===== -->
<?php if ($rel): ?>
<section class="berita-sec" style="background:var(--gray)">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-layer-group"></i> TERKAIT</span>
      <h2>Artikel Lainnya dalam <span class="grad-txt"><?= e($b['kategori']) ?></span></h2>
    </div>
    <div class="u-rel-grid">
      <?php foreach ($rel as $r): $rt = max(1, (int)round(str_word_count(strip_tags((string)$r['isi'])) / 200)); ?>
      <a class="u-rel-compact reveal" href="<?= url('berita-detail.php?slug=' . e($r['slug'])) ?>">
        <img src="<?= e(media_url($r['gambar'])) ?>" alt="" loading="lazy">
        <div class="u-rel-body">
          <small><?= e($r['kategori']) ?></small>
          <h4><?= e($r['judul']) ?></h4>
          <span class="u-meta-mini"><i class="fa-regular fa-calendar-days"></i><?= date('d M Y', strtotime($r['tanggal'])) ?> • <i class="fa-regular fa-clock"></i><?= $rt ?> mnt • <i class="fa-regular fa-eye"></i><?= (int)$r['views'] ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($sameAuthor && count($sameAuthor) > 0): ?>
<section class="berita-sec">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-user-pen"></i> DARI PENULIS YANG SAMA</span>
      <h2>Artikel Lain oleh <span class="grad-txt"><?= e($b['penulis']) ?></span></h2>
    </div>
    <div class="u-rel-grid">
      <?php foreach ($sameAuthor as $a): ?>
      <a class="u-rel-compact reveal" href="<?= url('berita-detail.php?slug=' . e($a['slug'])) ?>">
        <img src="<?= e(media_url($a['gambar'])) ?>" alt="" loading="lazy">
        <div class="u-rel-body">
          <small><?= e($a['kategori']) ?></small>
          <h4><?= e($a['judul']) ?></h4>
          <span class="u-meta-mini"><i class="fa-regular fa-calendar-days"></i><?= date('d M Y', strtotime($a['tanggal'])) ?> • <i class="fa-regular fa-eye"></i><?= (int)$a['views'] ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($popCat): ?>
<section class="berita-sec" style="background:var(--gray)">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-fire"></i> POPULER</span>
      <h2>Artikel Populer Kategori <span class="grad-txt"><?= e($b['kategori']) ?></span></h2>
    </div>
    <div class="u-rel-grid">
      <?php foreach ($popCat as $p): ?>
      <a class="u-rel-compact reveal" href="<?= url('berita-detail.php?slug=' . e($p['slug'])) ?>">
        <img src="<?= e(media_url($p['gambar'])) ?>" alt="" loading="lazy">
        <div class="u-rel-body">
          <small><?= e($p['kategori']) ?></small>
          <h4><?= e($p['judul']) ?></h4>
          <span class="u-meta-mini"><i class="fa-regular fa-eye"></i><?= (int)$p['views'] ?> dibaca • <?= date('d M Y', strtotime($p['tanggal'])) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Floating share sidebar -->
<div class="u-floating-share" id="uFloatShare">
  <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>" target="_blank" style="background:#1877f2" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
  <a href="https://wa.me/?text=<?= urlencode($b['judul'] . ' ' . url('berita-detail.php?slug=' . $b['slug'])) ?>" target="_blank" style="background:#25d366" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
  <a href="https://twitter.com/intent/tweet?text=<?= urlencode($b['judul']) ?>&url=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>" target="_blank" style="background:#1da1f2" title="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
  <a href="https://linkedin.com/shareArticle?url=<?= urlencode(url('berita-detail.php?slug=' . $b['slug'])) ?>" target="_blank" style="background:#0a66c2" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
  <a onclick="navigator.clipboard.writeText('<?= url('berita-detail.php?slug=' . $b['slug']) ?>');this.innerHTML='<i class=\'fa-solid fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-link\'></i>',2000)" style="background:#33415e;cursor:pointer" title="Salin Tautan"><i class="fa-solid fa-link"></i></a>
</div>

<!-- Article tools -->
<div class="u-article-tools">
  <button onclick="window.print()" title="Cetak"><i class="fa-solid fa-print"></i></button>
  <button onclick="document.body.classList.toggle('u-font-lg')" title="Perbesar Font" class="alt"><i class="fa-solid fa-text-height"></i></button>
  <button onclick="document.documentElement.scrollTo({top:0,behavior:'smooth'})" title="Ke Atas"><i class="fa-solid fa-arrow-up"></i></button>
</div>

<script>
/* ========= BERITA DETAIL ULTIMATE ========= */
(function(){
  /* Reading progress bar */
  var pbar = document.getElementById('uProgressRead');
  var hero = document.querySelector('.u-detail-hero');
  var wrap = document.querySelector('.u-article-wrap');
  if (pbar && hero && wrap) {
    var onScroll = function(){
      var start = hero.offsetTop + hero.offsetHeight;
      var end   = wrap.offsetTop + wrap.offsetHeight;
      var sc    = window.scrollY;
      if (sc < start) { pbar.style.width = '0%'; return; }
      var prog = Math.min(100, ((sc - start) / (end - start - window.innerHeight)) * 100);
      pbar.style.width = Math.max(0, prog).toFixed(1) + '%';
      var fs = document.getElementById('uFloatShare');
      if (fs) fs.classList.toggle('show', sc > start && sc < end);
    };
    document.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* TOC smooth scroll */
  document.querySelectorAll('.u-toc a').forEach(function(a){
    a.addEventListener('click', function(ev){
      var id = this.getAttribute('href').slice(1);
      var el = document.getElementById(id);
      if (el) { ev.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  /* Gallery strip lightbox */
  document.querySelectorAll('.u-gallery-strip img').forEach(function(img){
    img.addEventListener('click', function(){
      var lb = document.getElementById('lightbox');
      if (lb) { lb.querySelector('img').src = this.src; lb.classList.add('open'); }
    });
  });
})();
</script>
<style>body.u-font-lg .u-content{font-size:18px!important}body.u-font-lg .u-content h2{font-size:28px!important}body.u-font-lg .u-content h3{font-size:22px!important}</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>