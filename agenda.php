<?php
require_once __DIR__ . '/includes/header.php';

$tipe   = trim($_GET['tipe'] ?? '');
$status = trim($_GET['status'] ?? '');
$q      = trim($_GET['q'] ?? '');

$where = " WHERE 1=1";
$params = []; $types = '';
if ($tipe !== '')    { $where .= " AND tipe=?"; $params[] = $tipe; $types .= 's'; }
if ($status !== '')  { $where .= " AND status=?"; $params[] = $status; $types .= 's'; }
if ($q !== '')       { $where .= " AND (judul LIKE ? OR deskripsi LIKE ? OR lokasi LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like, $like]); $types .= 'sss'; }

$list = qp($conn, "SELECT * FROM agenda$where ORDER BY tanggal_mulai ASC, urutan ASC", $types, $params);
$tipeList = q($conn, "SELECT tipe, COUNT(*) AS c FROM agenda GROUP BY tipe ORDER BY c DESC");
$now      = date('Y-m-d H:i:s');

// Auto-update status
$conn->query("UPDATE agenda SET status='selesai' WHERE tanggal_selesai < '$now' AND status != 'selesai'");
$conn->query("UPDATE agenda SET status='berlangsung' WHERE tanggal_mulai <= '$now' AND (tanggal_selesai >= '$now' OR tanggal_selesai IS NULL) AND status != 'berlangsung'");
?>
<style>
/* ========= AGENDA PUBLIC ========= */
.u-a-hero{position:relative;background:linear-gradient(120deg,var(--blue-deep) 0%,var(--blue-dark) 45%,var(--blue) 100%);padding:110px 0 90px;color:#fff;overflow:hidden}
.u-a-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%, rgba(0,184,116,.18), transparent 40%),radial-gradient(circle at 80% 70%, rgba(10,102,194,.3), transparent 50%);pointer-events:none}
.u-a-inner{position:relative;z-index:2;text-align:center;max-width:700px;margin:0 auto}
.u-a-inner h1{font-family:var(--display);font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:16px}
.u-a-inner h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-a-inner p{font-size:17px;color:#c8d6ea;line-height:1.8;margin-bottom:28px}

.u-a-tools{background:#fff;border-radius:18px;padding:18px 22px;box-shadow:var(--shadow);border:1px solid var(--line);display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%)}
.u-a-search{flex:1;min-width:260px;position:relative}
.u-a-search input{width:100%;padding:13px 15px 13px 44px;border:1.6px solid var(--line);border-radius:12px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-a-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1);background:#fff}
.u-a-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--blue)}
.u-a-select{padding:11px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;cursor:pointer;outline:0;min-width:160px}

.u-a-list{display:flex;flex-direction:column;gap:16px}
.u-a-item{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px;transition:.3s;position:relative;overflow:hidden;display:flex;gap:20px;align-items:flex-start}
.u-a-item:hover{transform:translateX(6px);border-color:rgba(0,184,116,.3);box-shadow:0 10px 30px rgba(10,42,94,.08)}
.u-a-item.berlangsung{border-left:4px solid #00b874;background:linear-gradient(135deg,#f0fdf7,#fff)}
.u-a-item.selesai{opacity:.6}
.u-a-date{min-width:90px;text-align:center;padding:16px;background:linear-gradient(135deg,rgba(10,102,194,.1),rgba(0,184,116,.1));border-radius:14px}
.u-a-date .d{display:block;font-family:var(--display);font-size:28px;color:var(--blue-dark);font-weight:800;line-height:1;margin-bottom:4px}
.u-a-date .m{display:block;font-size:12px;color:#7b8aa3;font-weight:700;text-transform:uppercase;letter-spacing:1px}
.u-a-date .t{display:block;font-size:11px;color:var(--blue);margin-top:6px;font-weight:700}
.u-a-info{flex:1}
.u-a-info h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:8px;line-height:1.4}
.u-a-info .meta{display:flex;gap:16px;font-size:12.5px;color:#68758a;margin-bottom:12px;flex-wrap:wrap}
.u-a-info .meta i{color:#00b874;margin-right:5px}
.u-a-info p{font-size:13.5px;color:#475569;line-height:1.7;margin-bottom:12px}
.u-a-badge{display:inline-block;padding:5px 12px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.6px}
.u-a-badge.akan{background:#eef3fa;color:#0a66c2}
.u-a-badge.berlangsung{background:#e7f8f1;color:#0a7a52}
.u-a-badge.selesai{background:var(--gray);color:#475569}
.u-a-type{display:inline-block;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:700;background:var(--gray);color:#475569;margin-left:8px}
.u-a-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line)}
.u-a-empty i{font-size:56px;color:#cfd8e3;margin-bottom:16px}

@media(max-width:700px){.u-a-tools{flex-direction:column;align-items:stretch}.u-a-item{flex-direction:column;gap:16px}.u-a-date{width:100%}}
</style>

<section class="u-a-hero">
  <div class="container u-a-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-calendar-days"></i> JADWAL KEGIATAN</span>
    <h1>Agenda & <span>Kalender Akademik</span></h1>
    <p>Pantau jadwal kegiatan akademik, seminar, ujian, dan acara penting lainnya di Program Studi Fisika.</p>
  </div>
</section>

<div class="u-a-tools container reveal">
  <form method="get" class="u-a-search" action="agenda.php">
    <?php if ($tipe !== ''): ?><input type="hidden" name="tipe" value="<?= e($tipe) ?>"><?php endif; ?>
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul, lokasi, atau deskripsi...">
  </form>
  <select class="u-a-select" onchange="location.href='agenda.php?tipe='+this.value+'<?= $status ? '&status='.urlencode($status) : '' ?>'">
    <option value="">Semua Tipe</option>
    <?php foreach ($tipeList as $t): ?><option value="<?= e($t['tipe']) ?>" <?= $tipe === $t['tipe'] ? 'selected' : '' ?>><?= e($t['tipe']) ?> (<?= (int)$t['c'] ?>)</option><?php endforeach; ?>
  </select>
  <select class="u-a-select" onchange="location.href='agenda.php?status='+this.value+'<?= $tipe ? '&tipe='.urlencode($tipe) : '' ?>'">
    <option value="">Semua Status</option>
    <option value="akan_datang" <?= $status === 'akan_datang' ? 'selected' : '' ?>>Akan Datang</option>
    <option value="berlangsung" <?= $status === 'berlangsung' ? 'selected' : '' ?>>Sedang Berlangsung</option>
    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
  </select>
</div>

<section class="page-sec" style="padding-top:0">
  <div class="container">
    <?php if (!$list): ?>
    <div class="u-a-empty reveal">
      <i class="fa-solid fa-calendar-xmark"></i>
      <h3>Tidak ada agenda ditemukan</h3>
      <p>Coba gunakan kata kunci lain atau hapus filter yang aktif.</p>
      <a href="agenda.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom:20px;font-size:13.5px;color:#68758a;font-weight:600">
      <i class="fa-solid fa-circle-info" style="color:#00b874;margin-right:6px"></i> Menampilkan <b style="color:var(--blue-dark)"><?= count($list) ?></b> agenda
      <?php if ($q !== ''): ?> untuk kata kunci "<b><?= e($q) ?></b>"<?php endif; ?>
      <a href="agenda.php" style="color:var(--blue);margin-left:10px"><i class="fa-solid fa-xmark"></i> Reset</a>
    </div>

    <div class="u-a-list">
      <?php foreach ($list as $a): 
        $badgeClass = $a['status'];
        $isToday = date('Y-m-d') == date('Y-m-d', strtotime($a['tanggal_mulai']));
      ?>
      <div class="u-a-item <?= $a['status'] ?> <?= $isToday ? 'berlangsung' : '' ?> reveal">
        <div class="u-a-date">
          <span class="d"><?= date('d', strtotime($a['tanggal_mulai'])) ?></span>
          <span class="m"><?= date('M', strtotime($a['tanggal_mulai'])) ?></span>
          <span class="t"><?= date('H:i', strtotime($a['tanggal_mulai'])) ?></span>
        </div>
        <div class="u-a-info">
          <h3><?= e($a['judul']) ?> <span class="u-a-type"><?= e($a['tipe']) ?></span> <?php if ($isToday): ?><span style="color:#00b874;font-size:11px;font-weight:800">• HARI INI</span><?php endif; ?></h3>
          <div class="meta">
            <span><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($a['tanggal_mulai'])) ?> - <?= $a['tanggal_selesai'] ? date('H:i', strtotime($a['tanggal_selesai'])) : 'Selesai' ?> WIB</span>
            <span><i class="fa-solid fa-location-dot"></i> <?= e($a['lokasi']) ?></span>
          </div>
          <?php if ($a['deskripsi']): ?><p><?= nl2br(e($a['deskripsi'])) ?></p><?php endif; ?>
          <span class="u-a-badge <?= $badgeClass ?>"><?= strtoupper(str_replace('_', ' ', $a['status'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>