<?php
require_once __DIR__ . '/includes/header.php';

$sektor   = trim($_GET['sektor'] ?? '');
$angkatan = trim($_GET['angkatan'] ?? '');

$where = " WHERE ditampilkan=1";
$params = []; $types = '';
if ($sektor !== '')   { $where .= " AND sektor=?"; $params[] = $sektor; $types .= 's'; }
if ($angkatan !== '') { $where .= " AND angkatan=?"; $params[] = (int)$angkatan; $types .= 'i'; }

$list = qp($conn, "SELECT * FROM alumni$where ORDER BY urutan ASC, angkatan DESC", $types, $params);
$sektorList  = q($conn, "SELECT sektor, COUNT(*) AS c FROM alumni WHERE ditampilkan=1 GROUP BY sektor ORDER BY c DESC");
$angkatanList = q($conn, "SELECT angkatan, COUNT(*) AS c FROM alumni WHERE ditampilkan=1 GROUP BY angkatan ORDER BY angkatan DESC");
?>
<style>
/* ========= ALUMNI PUBLIC ========= */
.u-al-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-al-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-al-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-al-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-al-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-al-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}

.u-al-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-al-select{padding:11px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;cursor:pointer;outline:0;min-width:180px}

.u-al-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:22px;margin-top:20px}
.u-al-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px;transition:.3s;position:relative;overflow:hidden;text-align:center}
.u-al-card:hover{transform:translateY(-6px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.u-al-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-al-img{width:90px;height:90px;border-radius:50%;object-fit:cover;margin:0 auto 14px;border:3px solid var(--line);transition:.3s}
.u-al-card:hover .u-al-img{border-color:var(--green);transform:scale(1.05)}
.u-al-card h3{font-family:var(--display);color:var(--blue-dark);font-size:17px;margin-bottom:4px}
.u-al-card .angkatan{color:#7b8aa3;font-size:12px;font-weight:700;margin-bottom:10px}
.u-al-card .pekerjaan{font-size:13px;color:var(--blue-dark);font-weight:700;margin-bottom:4px}
.u-al-card .perusahaan{font-size:12px;color:#68758a;margin-bottom:12px}
.u-al-card .sektor{display:inline-block;padding:4px 12px;border-radius:99px;font-size:10.5px;font-weight:800;background:linear-gradient(135deg,rgba(10,102,194,.1),rgba(0,184,116,.1));color:var(--blue-dark);margin-bottom:14px}
.u-al-card .testi{font-size:12.5px;color:#68758a;line-height:1.7;font-style:italic;padding-top:14px;border-top:1px dashed var(--line);position:relative}
.u-al-card .testi::before{content:'"';position:absolute;top:-8px;left:10px;font-size:32px;color:var(--green);font-family:var(--display)}
.u-al-card .linkedin{display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:6px 14px;border-radius:9px;background:#0a66c2;color:#fff;font-size:11px;font-weight:700;text-decoration:none;transition:.2s}
.u-al-card .linkedin:hover{background:#071a39;transform:translateY(-2px)}
.u-al-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line);grid-column:1/-1}
.u-al-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-al-tools{flex-direction:column;align-items:stretch}.u-al-grid{grid-template-columns:1fr}}
</style>

<section class="u-al-hero">
  <div class="container u-al-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-user-graduate"></i> TRACER STUDY</span>
    <h1>Jejak <span>Alumni</span></h1>
    <p>Lulusan Program Studi Fisika yang berkarya di berbagai sektor, dari akademisi hingga industri.</p>
  </div>
</section>

<div class="u-al-tools container reveal">
  <select class="u-al-select" onchange="location.href='alumni.php?sektor='+this.value+'<?= $angkatan ? '&angkatan='.urlencode($angkatan) : '' ?>'">
    <option value="">Semua Sektor</option>
    <?php foreach ($sektorList as $s): ?><option value="<?= e($s['sektor']) ?>" <?= $sektor === $s['sektor'] ? 'selected' : '' ?>><?= e($s['sektor']) ?> (<?= (int)$s['c'] ?>)</option><?php endforeach; ?>
  </select>
  <select class="u-al-select" onchange="location.href='alumni.php?angkatan='+this.value+'<?= $sektor ? '&sektor='.urlencode($sektor) : '' ?>'">
    <option value="">Semua Angkatan</option>
    <?php foreach ($angkatanList as $a): ?><option value="<?= (int)$a['angkatan'] ?>" <?= $angkatan === (string)$a['angkatan'] ? 'selected' : '' ?>>Angkatan <?= (int)$a['angkatan'] ?> (<?= (int)$a['c'] ?>)</option><?php endforeach; ?>
  </select>
  <?php if ($sektor !== '' || $angkatan !== ''): ?>
  <a href="alumni.php" style="margin-left:auto;font-size:12.5px;font-weight:700;color:var(--blue)"><i class="fa-solid fa-xmark"></i> Reset Filter</a>
  <?php endif; ?>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <?php if (!$list): ?>
    <div class="u-al-empty reveal">
      <i class="fa-solid fa-user-slash"></i>
      <h3>Tidak ada alumni ditemukan</h3>
      <p>Coba hapus filter yang aktif.</p>
      <a href="alumni.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom:20px;font-size:13.5px;color:#68758a;font-weight:600">
      <i class="fa-solid fa-circle-info" style="color:#00b874;margin-right:6px"></i> Menampilkan <b style="color:var(--blue-dark)"><?= count($list) ?></b> alumni
    </div>

    <div class="u-al-grid">
      <?php foreach ($list as $a): ?>
      <div class="u-al-card reveal">
        <img class="u-al-img" src="<?= $a['foto'] ? e(media_url($a['foto'])) : 'https://ui-avatars.com/api/?name=' . urlencode($a['nama']) . '&background=0a66c2&color=fff&size=200' ?>" alt="<?= e($a['nama']) ?>" loading="lazy">
        <h3><?= e($a['nama']) ?></h3>
        <div class="angkatan">Angkatan <?= (int)$a['angkatan'] ?></div>
        <?php if ($a['pekerjaan']): ?><div class="pekerjaan"><?= e($a['pekerjaan']) ?></div><?php endif; ?>
        <?php if ($a['perusahaan']): ?><div class="perusahaan">di <?= e($a['perusahaan']) ?></div><?php endif; ?>
        <span class="sektor"><?= e($a['sektor']) ?></span>
        <?php if ($a['testimoni']): ?><div class="testi"><?= e($a['testimoni']) ?></div><?php endif; ?>
        <?php if ($a['linkedin']): ?><a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener" class="linkedin"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>