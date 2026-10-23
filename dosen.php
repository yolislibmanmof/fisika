<?php
require_once __DIR__ . '/includes/header.php';

$bidang = trim($_GET['bidang'] ?? '');
$q      = trim($_GET['q'] ?? '');

$where = " WHERE aktif=1";
$params = []; $types = '';
if ($bidang !== '') { $where .= " AND bidang_keahlian=?"; $params[] = $bidang; $types .= 's'; }
if ($q !== '')      { $where .= " AND (nama LIKE ? OR bidang_keahlian LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like]); $types .= 'ss'; }

$list   = qp($conn, "SELECT * FROM dosen$where ORDER BY urutan ASC, nama ASC", $types, $params);
$bidangs = q($conn, "SELECT bidang_keahlian, COUNT(*) AS c FROM dosen WHERE aktif=1 GROUP BY bidang_keahlian ORDER BY c DESC");
$total  = count($list);
?>
<style>
/* ========= DOSEN PUBLIC ========= */
.u-d-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-d-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-d-hero::after{content:'\f5d2';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:8%;top:50%;transform:translateY(-50%);font-size:340px;color:rgba(255,255,255,.03);animation:uSpin 40s linear infinite}
.u-d-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-d-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-d-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-d-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}
.u-d-stats{display:flex;justify-content:center;gap:34px;flex-wrap:wrap}
.u-d-stats .hs b{display:block;font-family:var(--display);font-size:34px;color:#fff}
.u-d-stats .hs b em{color:#7ef0c4;font-style:normal}
.u-d-stats .hs small{font-size:11.5px;letter-spacing:2px;color:#9fb3cd;font-weight:700;text-transform:uppercase}

.u-d-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-d-search{flex:1;min-width:260px;position:relative}
.u-d-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-d-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-d-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}

.u-d-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:24px;margin-top:20px}
.u-d-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px;text-align:center;transition:.3s;position:relative;overflow:hidden}
.u-d-card:hover{transform:translateY(-6px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.u-d-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-d-img{width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto 16px;border:3px solid var(--line);transition:.3s}
.u-d-card:hover .u-d-img{border-color:var(--green);transform:scale(1.05)}
.u-d-card h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:4px}
.u-d-card .gelar{color:#7b8aa3;font-size:13px;font-weight:600;margin-bottom:12px;display:block}
.u-d-card .bidang{display:inline-block;background:linear-gradient(135deg,rgba(10,102,194,.1),rgba(0,184,116,.1));color:var(--blue-dark);padding:6px 14px;border-radius:99px;font-size:11.5px;font-weight:700;margin-bottom:18px}
.u-d-links{display:flex;justify-content:center;gap:10px;margin-top:16px;padding-top:16px;border-top:1px dashed var(--line)}
.u-d-links a{width:36px;height:36px;border-radius:10px;background:var(--gray);color:var(--blue);display:grid;place-items:center;font-size:15px;transition:.2s;text-decoration:none}
.u-d-links a:hover{background:var(--grad);color:#fff;transform:translateY(-3px)}
.u-d-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line);grid-column:1/-1}
.u-d-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-d-tools{flex-direction:column;align-items:stretch}.u-d-grid{grid-template-columns:1fr}}
</style>

<section class="u-d-hero">
  <div class="container u-d-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-chalkboard-user"></i> SUMBER DAYA MANUSIA</span>
    <h1>Dosen & <span>Peneliti</span></h1>
    <p>Didukung oleh tenaga pengajar berkualitas dengan kompetensi di bidang Fisika Teori, Material, Komputasi, dan Medis.</p>
    <div class="u-d-stats">
      <div class="hs"><b><?= count($bidangs) ?><em>+</em></b><small>Bidang Keahlian</small></div>
      <div class="hs"><b><?= $total ?><em>+</em></b><small>Dosen Aktif</small></div>
    </div>
  </div>
</section>

<div class="u-d-tools container reveal">
  <form method="get" class="u-d-search" action="dosen.php">
    <?php if ($bidang !== ''): ?><input type="hidden" name="bidang" value="<?= e($bidang) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama dosen atau bidang keahlian...">
  </form>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <div class="u-chips-wrap reveal">
      <div class="u-chips">
        <a class="<?= $bidang === '' ? 'on' : '' ?>" href="dosen.php"><span><i class="fa-solid fa-layer-group" style="margin-right:6px"></i>Semua Bidang</span></a>
        <?php foreach ($bidangs as $b): ?>
        <a class="<?= $bidang === $b['bidang_keahlian'] ? 'on' : '' ?>" href="?bidang=<?= urlencode($b['bidang_keahlian']) ?>"><span><?= e($b['bidang_keahlian']) ?> (<?= (int)$b['c'] ?>)</span></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$list): ?>
    <div class="u-d-empty reveal">
      <i class="fa-solid fa-user-slash"></i>
      <h3>Tidak ada dosen ditemukan</h3>
      <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
      <a href="dosen.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div class="u-d-grid">
      <?php foreach ($list as $d): ?>
      <div class="u-d-card reveal">
        <img class="u-d-img" src="<?= $d['foto'] ? e(media_url($d['foto'])) : 'https://ui-avatars.com/api/?name=' . urlencode($d['nama']) . '&background=0a66c2&color=fff&size=200' ?>" alt="<?= e($d['nama']) ?>" loading="lazy">
        <h3><?= e($d['nama']) ?></h3>
        <?php if ($d['gelar']): ?><span class="gelar"><?= e($d['gelar']) ?></span><?php endif; ?>
        <span class="bidang"><?= e($d['bidang_keahlian']) ?></span>
        
        <div class="u-d-links">
          <?php if ($d['email']): ?><a href="mailto:<?= e($d['email']) ?>" title="Email"><i class="fa-solid fa-envelope"></i></a><?php endif; ?>
          <?php if ($d['scholar']): ?><a href="<?= e($d['scholar']) ?>" target="_blank" rel="noopener" title="Google Scholar"><i class="fa-solid fa-graduation-cap"></i></a><?php endif; ?>
          <?php if ($d['scopus']): ?><a href="<?= e($d['scopus']) ?>" target="_blank" rel="noopener" title="Scopus"><i class="fa-solid fa-database"></i></a><?php endif; ?>
          <?php if ($d['orcid']): ?><a href="<?= e($d['orcid']) ?>" target="_blank" rel="noopener" title="ORCID"><i class="fa-solid fa-fingerprint"></i></a><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>