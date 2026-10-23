<?php
require_once __DIR__ . '/includes/header.php';

$kategori = trim($_GET['kategori'] ?? '');
$q        = trim($_GET['q'] ?? '');

$where = " WHERE 1=1";
$params = []; $types = '';
if ($kategori !== '') { $where .= " AND kategori=?"; $params[] = $kategori; $types .= 's'; }
if ($q !== '')        { $where .= " AND judul LIKE ?"; $params[] = "%$q%"; $types .= 's'; }

$list = qp($conn, "SELECT * FROM unduh$where ORDER BY urutan ASC, tanggal_upload DESC", $types, $params);
$katList = q($conn, "SELECT kategori, COUNT(*) AS c FROM unduh GROUP BY kategori ORDER BY c DESC");

// Increment counter saat download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $did = (int)$_GET['download'];
    $file = q1($conn, "SELECT file_path FROM unduh WHERE id=$did");
    if ($file && file_exists(UPLOAD_PATH . $file['file_path'])) {
        $conn->query("UPDATE unduh SET jumlah_unduh = jumlah_unduh + 1 WHERE id=$did");
        header('Location: ' . url('assets/uploads/' . $file['file_path']));
        exit;
    }
}
?>
<style>
/* ========= UNDUH PUBLIC ========= */
.u-d-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-d-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-d-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-d-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-d-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-d-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}

.u-d-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-d-search{flex:1;min-width:260px;position:relative}
.u-d-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-d-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-d-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}

.u-d-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:18px;margin-top:20px}
.u-d-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;transition:.3s;position:relative;overflow:hidden;display:flex;gap:16px;align-items:flex-start}
.u-d-card:hover{transform:translateY(-4px);border-color:rgba(0,184,116,.3);box-shadow:0 15px 35px rgba(10,42,94,.1)}
.u-d-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-d-ic{width:56px;height:56px;border-radius:14px;display:grid;place-items:center;font-size:24px;color:#fff;flex:none}
.u-d-ic.pdf{background:linear-gradient(135deg,#e5484d,#b3261e)}
.u-d-ic.doc{background:linear-gradient(135deg,#0a66c2,#071a39)}
.u-d-ic.xls{background:linear-gradient(135deg,#00b874,#00915c)}
.u-d-ic.lain{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}
.u-d-info{flex:1;min-width:0}
.u-d-info h3{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:6px;line-height:1.4}
.u-d-info .meta{display:flex;gap:10px;font-size:11.5px;color:#7b8aa3;font-weight:600;margin-bottom:10px;flex-wrap:wrap}
.u-d-info .meta i{color:var(--green);margin-right:4px}
.u-d-info .kat{display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:800;background:var(--gray);color:#475569;margin-bottom:10px}
.u-d-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;background:var(--grad);color:#fff;font-size:12px;font-weight:700;text-decoration:none;transition:.2s}
.u-d-btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,184,116,.3)}
.u-d-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line);grid-column:1/-1}
.u-d-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-d-tools{flex-direction:column;align-items:stretch}.u-d-grid{grid-template-columns:1fr}}
</style>

<section class="u-d-hero">
  <div class="container u-d-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-download"></i> PUSAT DOKUMEN</span>
    <h1>Pusat <span>Unduhan</span></h1>
    <p>Unduh dokumen akademik, formulir, pedoman, dan file penting lainnya dari Program Studi Fisika.</p>
  </div>
</section>

<div class="u-d-tools container reveal">
  <form method="get" class="u-d-search" action="unduh.php">
    <?php if ($kategori !== ''): ?><input type="hidden" name="kategori" value="<?= e($kategori) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul file...">
  </form>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <div class="u-chips-wrap reveal">
      <div class="u-chips">
        <a class="<?= $kategori === '' ? 'on' : '' ?>" href="unduh.php"><span><i class="fa-solid fa-layer-group" style="margin-right:6px"></i>Semua Kategori</span></a>
        <?php foreach ($katList as $k): ?>
        <a class="<?= $kategori === $k['kategori'] ? 'on' : '' ?>" href="?kategori=<?= urlencode($k['kategori']) ?>"><span><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</span></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$list): ?>
    <div class="u-d-empty reveal">
      <i class="fa-solid fa-folder-open"></i>
      <h3>Tidak ada file ditemukan</h3>
      <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
      <a href="unduh.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div class="u-d-grid">
      <?php foreach ($list as $u): 
        $ext = strtolower(pathinfo($u['file_path'], PATHINFO_EXTENSION));
        $icClass = $ext === 'pdf' ? 'pdf' : (in_array($ext, ['doc','docx']) ? 'doc' : (in_array($ext, ['xls','xlsx']) ? 'xls' : 'lain'));
        $icName = $ext === 'pdf' ? 'fa-file-pdf' : (in_array($ext, ['doc','docx']) ? 'fa-file-word' : (in_array($ext, ['xls','xlsx']) ? 'fa-file-excel' : 'fa-file'));
      ?>
      <div class="u-d-card reveal">
        <span class="u-d-ic <?= $icClass ?>"><i class="fa-solid <?= $icName ?>"></i></span>
        <div class="u-d-info">
          <h3><?= e($u['judul']) ?></h3>
          <span class="kat"><?= e($u['kategori']) ?></span>
          <div class="meta">
            <span><i class="fa-solid fa-weight-hanging"></i> <?= e($u['ukuran_file']) ?></span>
            <span><i class="fa-solid fa-download"></i> <?= (int)$u['jumlah_unduh'] ?>x</span>
          </div>
          <a href="?download=<?= (int)$u['id'] ?>" class="u-d-btn"><i class="fa-solid fa-download"></i> Unduh</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>