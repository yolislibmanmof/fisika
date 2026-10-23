<?php
require_once __DIR__ . '/includes/header.php';

$indeks = trim($_GET['indeks'] ?? '');
$tahun  = trim($_GET['tahun'] ?? '');
$q      = trim($_GET['q'] ?? '');

$where = " WHERE 1=1";
$params = []; $types = '';
if ($indeks !== '') { $where .= " AND indeks=?"; $params[] = $indeks; $types .= 's'; }
if ($tahun !== '')  { $where .= " AND tahun=?"; $params[] = (int)$tahun; $types .= 'i'; }
if ($q !== '')      { $where .= " AND (judul LIKE ? OR penulis LIKE ? OR nama_jurnal LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like, $like]); $types .= 'sss'; }

$list = qp($conn, "SELECT * FROM publikasi$where ORDER BY tahun DESC, urutan ASC", $types, $params);
$indeksList = q($conn, "SELECT indeks, COUNT(*) AS c FROM publikasi GROUP BY indeks ORDER BY c DESC");
$tahunList  = q($conn, "SELECT tahun, COUNT(*) AS c FROM publikasi GROUP BY tahun ORDER BY tahun DESC");
$total = count($list);
?>
<style>
/* ========= PUBLIKASI PUBLIC ========= */
.u-p-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-p-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-p-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-p-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-p-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-p-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}

.u-p-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-p-search{flex:1;min-width:260px;position:relative}
.u-p-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-p-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-p-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}
.u-p-select{padding:11px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;cursor:pointer;outline:0;min-width:160px}

.u-p-list{display:flex;flex-direction:column;gap:16px}
.u-p-item{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px;transition:.3s;position:relative;overflow:hidden}
.u-p-item:hover{transform:translateX(6px);border-color:rgba(0,184,116,.3);box-shadow:0 10px 30px rgba(10,42,94,.08)}
.u-p-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--grad)}
.u-p-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px;flex-wrap:wrap}
.u-p-head h3{font-family:var(--display);color:var(--blue-dark);font-size:16px;line-height:1.5;flex:1}
.u-p-badge{padding:5px 12px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.6px;white-space:nowrap;flex:none}
.u-p-badge.scopus{background:#eef3fa;color:#0a66c2}
.u-p-badge.sinta{background:#e7f8f1;color:#0a7a52}
.u-p-badge.lain{background:var(--gray);color:#475569}
.u-p-meta{display:flex;gap:16px;font-size:12.5px;color:#68758a;font-weight:600;flex-wrap:wrap;margin-bottom:14px}
.u-p-meta i{color:#00b874;margin-right:5px}
.u-p-actions{display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px dashed var(--line)}
.u-p-actions a{padding:8px 16px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;font-size:12px;font-weight:700;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.u-p-actions a:hover{background:var(--grad);color:#fff;border-color:transparent}
.u-p-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line)}
.u-p-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-p-tools{flex-direction:column;align-items:stretch}.u-p-head{flex-direction:column}.u-p-meta{gap:10px}}
</style>

<section class="u-p-hero">
  <div class="container u-p-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-book-open"></i> LUARAN RISET</span>
    <h1>Publikasi <span>Ilmiah</span></h1>
    <p>Dokumentasi hasil penelitian dosen dan mahasiswa Program Studi Fisika di jurnal nasional dan internasional bereputasi.</p>
  </div>
</section>

<div class="u-p-tools container reveal">
  <form method="get" class="u-p-search" action="publikasi.php">
    <?php if ($indeks !== ''): ?><input type="hidden" name="indeks" value="<?= e($indeks) ?>"><?php endif; ?>
    <?php if ($tahun !== ''): ?><input type="hidden" name="tahun" value="<?= e($tahun) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul, penulis, atau jurnal...">
  </form>
  <select class="u-p-select" onchange="location.href='publikasi.php?indeks='+this.value+'<?= $tahun ? '&tahun='.urlencode($tahun) : '' ?>'">
    <option value="">Semua Indeks</option>
    <?php foreach ($indeksList as $i): ?><option value="<?= e($i['indeks']) ?>" <?= $indeks === $i['indeks'] ? 'selected' : '' ?>><?= e($i['indeks']) ?> (<?= (int)$i['c'] ?>)</option><?php endforeach; ?>
  </select>
  <select class="u-p-select" onchange="location.href='publikasi.php?tahun='+this.value+'<?= $indeks ? '&indeks='.urlencode($indeks) : '' ?>'">
    <option value="">Semua Tahun</option>
    <?php foreach ($tahunList as $t): ?><option value="<?= (int)$t['tahun'] ?>" <?= $tahun === (string)$t['tahun'] ? 'selected' : '' ?>><?= (int)$t['tahun'] ?> (<?= (int)$t['c'] ?>)</option><?php endforeach; ?>
  </select>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <?php if (!$list): ?>
    <div class="u-p-empty reveal">
      <i class="fa-solid fa-inbox"></i>
      <h3>Tidak ada publikasi ditemukan</h3>
      <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
      <a href="publikasi.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom:20px;font-size:13.5px;color:#68758a;font-weight:600">
      <i class="fa-solid fa-circle-info" style="color:#00b874;margin-right:6px"></i> Menampilkan <b style="color:var(--blue-dark)"><?= $total ?></b> publikasi
      <?php if ($q !== ''): ?> untuk kata kunci "<b><?= e($q) ?></b>"<?php endif; ?>
      <a href="publikasi.php" style="color:var(--blue);margin-left:10px"><i class="fa-solid fa-xmark"></i> Reset</a>
    </div>

    <div class="u-p-list">
      <?php foreach ($list as $p): 
        $badgeClass = strpos($p['indeks'], 'Scopus') !== false ? 'scopus' : (strpos($p['indeks'], 'Sinta') !== false ? 'sinta' : 'lain');
      ?>
      <div class="u-p-item reveal">
        <div class="u-p-head">
          <h3><?= e($p['judul']) ?></h3>
          <span class="u-p-badge <?= $badgeClass ?>"><?= e($p['indeks']) ?></span>
        </div>
        <div class="u-p-meta">
          <span><i class="fa-solid fa-users"></i> <?= e($p['penulis']) ?></span>
          <span><i class="fa-solid fa-newspaper"></i> <?= e($p['nama_jurnal']) ?></span>
          <span><i class="fa-regular fa-calendar"></i> Tahun <?= (int)$p['tahun'] ?></span>
          <?php if ($p['dosen_pengusul']): ?><span><i class="fa-solid fa-chalkboard-user"></i> <?= e($p['dosen_pengusul']) ?></span><?php endif; ?>
        </div>
        <div class="u-p-actions">
          <?php if ($p['doi']): ?><a href="https://doi.org/<?= e($p['doi']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-barcode"></i> DOI: <?= e($p['doi']) ?></a><?php endif; ?>
          <?php if ($p['link_url']): ?><a href="<?= e($p['link_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-external-link-alt"></i> Buka Jurnal</a><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>