<?php
require_once __DIR__ . '/includes/header.php';

$semester = trim($_GET['semester'] ?? '');
$tahun    = trim($_GET['tahun'] ?? '');
$q        = trim($_GET['q'] ?? '');

$where = " WHERE 1=1";
$params = []; $types = '';
if ($semester !== '') { $where .= " AND semester=?"; $params[] = $semester; $types .= 's'; }
if ($tahun !== '')    { $where .= " AND tahun_akademik=?"; $params[] = $tahun; $types .= 's'; }
if ($q !== '')        { $where .= " AND (kode_mk LIKE ? OR mata_kuliah LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like]); $types .= 'ss'; }

$list = qp($conn, "SELECT * FROM rps_kurikulum$where ORDER BY tahun_akademik DESC, semester ASC, urutan ASC", $types, $params);
$semList  = q($conn, "SELECT semester, COUNT(*) AS c FROM rps_kurikulum GROUP BY semester ORDER BY semester ASC");
$tahunList = q($conn, "SELECT tahun_akademik, COUNT(*) AS c FROM rps_kurikulum GROUP BY tahun_akademik ORDER BY tahun_akademik DESC");

// Increment counter saat download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $did = (int)$_GET['download'];
    $file = q1($conn, "SELECT file_path FROM rps_kurikulum WHERE id=$did");
    if ($file && file_exists(UPLOAD_PATH . $file['file_path'])) {
        $conn->query("UPDATE rps_kurikulum SET jumlah_unduh = jumlah_unduh + 1 WHERE id=$did");
        header('Location: ' . url('assets/uploads/' . $file['file_path']));
        exit;
    }
}
?>
<style>
/* ========= RPS PUBLIC ========= */
.u-r-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-r-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-r-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-r-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-r-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-r-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}

.u-r-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-r-search{flex:1;min-width:260px;position:relative}
.u-r-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-r-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-r-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}
.u-r-select{padding:11px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;cursor:pointer;outline:0;min-width:160px}

.u-r-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:18px;margin-top:20px}
.u-r-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;transition:.3s;position:relative;overflow:hidden;display:flex;gap:16px;align-items:flex-start}
.u-r-card:hover{transform:translateY(-4px);border-color:rgba(0,184,116,.3);box-shadow:0 15px 35px rgba(10,42,94,.1)}
.u-r-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-r-ic{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#e5484d,#b3261e);display:grid;place-items:center;font-size:24px;color:#fff;flex:none}
.u-r-info{flex:1;min-width:0}
.u-r-info h3{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:6px;line-height:1.4}
.u-r-info .meta{display:flex;gap:10px;font-size:11.5px;color:#7b8aa3;font-weight:600;margin-bottom:10px;flex-wrap:wrap}
.u-r-info .meta i{color:var(--green);margin-right:4px}
.u-r-info .kode{display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:800;background:linear-gradient(135deg,rgba(10,102,194,.1),rgba(0,184,116,.1));color:var(--blue-dark);margin-bottom:10px}
.u-r-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;background:var(--grad);color:#fff;font-size:12px;font-weight:700;text-decoration:none;transition:.2s}
.u-r-btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,184,116,.3)}
.u-r-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line);grid-column:1/-1}
.u-r-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-r-tools{flex-direction:column;align-items:stretch}.u-r-grid{grid-template-columns:1fr}}
</style>

<section class="u-r-hero">
  <div class="container u-r-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-file-pdf"></i> DOKUMEN AKADEMIK</span>
    <h1>Kurikulum & <span>RPS</span></h1>
    <p>Unduh Rencana Pembelajaran Semester (RPS) dan dokumen kurikulum terbaru untuk seluruh mata kuliah Program Studi Fisika.</p>
  </div>
</section>

<div class="u-r-tools container reveal">
  <form method="get" class="u-r-search" action="rps.php">
    <?php if ($semester !== ''): ?><input type="hidden" name="semester" value="<?= e($semester) ?>"><?php endif; ?>
    <?php if ($tahun !== ''): ?><input type="hidden" name="tahun" value="<?= e($tahun) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari kode atau nama mata kuliah...">
  </form>
  <select class="u-r-select" onchange="location.href='rps.php?semester='+this.value+'<?= $tahun ? '&tahun='.urlencode($tahun) : '' ?>'">
    <option value="">Semua Semester</option>
    <?php foreach ($semList as $s): ?><option value="<?= e($s['semester']) ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>><?= e($s['semester']) ?> (<?= (int)$s['c'] ?>)</option><?php endforeach; ?>
  </select>
  <select class="u-r-select" onchange="location.href='rps.php?tahun='+this.value+'<?= $semester ? '&semester='.urlencode($semester) : '' ?>'">
    <option value="">Semua Tahun</option>
    <?php foreach ($tahunList as $t): ?><option value="<?= e($t['tahun_akademik']) ?>" <?= $tahun === $t['tahun_akademik'] ? 'selected' : '' ?>>TA <?= e($t['tahun_akademik']) ?> (<?= (int)$t['c'] ?>)</option><?php endforeach; ?>
  </select>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <?php if (!$list): ?>
    <div class="u-r-empty reveal">
      <i class="fa-solid fa-folder-open"></i>
      <h3>Tidak ada RPS ditemukan</h3>
      <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
      <a href="rps.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom:20px;font-size:13.5px;color:#68758a;font-weight:600">
      <i class="fa-solid fa-circle-info" style="color:#00b874;margin-right:6px"></i> Menampilkan <b style="color:var(--blue-dark)"><?= count($list) ?></b> dokumen RPS
      <?php if ($q !== ''): ?> untuk "<b><?= e($q) ?></b>"<?php endif; ?>
      <a href="rps.php" style="color:var(--blue);margin-left:10px"><i class="fa-solid fa-xmark"></i> Reset</a>
    </div>

    <div class="u-r-grid">
      <?php foreach ($list as $r): ?>
      <div class="u-r-card reveal">
        <span class="u-r-ic"><i class="fa-solid fa-file-pdf"></i></span>
        <div class="u-r-info">
          <h3><?= e($r['mata_kuliah']) ?></h3>
          <span class="kode"><?= e($r['kode_mk']) ?></span>
          <div class="meta">
            <span><i class="fa-solid fa-calendar"></i> <?= e($r['semester']) ?> <?= e($r['tahun_akademik']) ?></span>
            <span><i class="fa-solid fa-weight-hanging"></i> <?= e($r['ukuran_file']) ?></span>
            <span><i class="fa-solid fa-download"></i> <?= (int)$r['jumlah_unduh'] ?>x</span>
          </div>
          <a href="?download=<?= (int)$r['id'] ?>" class="u-r-btn"><i class="fa-solid fa-download"></i> Unduh RPS</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>