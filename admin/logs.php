<?php
$ACTIVE = 'logs.php'; $TITLE = 'Audit Log';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();

/* Filter */
$fQ    = trim($_GET['q'] ?? '');
$fAct  = trim($_GET['act'] ?? '');
$fUser = trim($_GET['user'] ?? '');
$fDays = (int)($_GET['days'] ?? 7);
$per   = 25;
$page  = max(1, (int)($_GET['hal'] ?? 1));
$off   = ($page - 1) * $per;

$where = " WHERE created_at >= (NOW() - INTERVAL ? DAY)"; $params = [$fDays]; $types = 'i';
if ($fQ !== '') {
    $where .= " AND (user_name LIKE ? OR detail LIKE ? OR ip LIKE ?)";
    $like = '%' . sec_escape_like($fQ) . '%';
    $params = array_merge($params, [$like,$like,$like]); $types .= 'sss';
}
if ($fAct !== '')  { $where .= " AND action=?"; $params[] = $fAct; $types .= 's'; }
if ($fUser !== '') { $where .= " AND user_name=?"; $params[] = $fUser; $types .= 's'; }

$total = (int)(qp($conn, "SELECT COUNT(*) AS c FROM admin_logs$where", $types, $params)[0]['c'] ?? 0);
$pages = max(1, (int)ceil($total / $per));
$list  = qp($conn, "SELECT * FROM admin_logs$where ORDER BY created_at DESC LIMIT ? OFFSET ?", $types.'ii', array_merge($params, [$per,$off]));

$users  = q($conn, "SELECT DISTINCT user_name FROM admin_logs ORDER BY user_name ASC");
$actions = q($conn, "SELECT DISTINCT action FROM admin_logs ORDER BY action ASC");

/* Ekspor CSV */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit-log-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Waktu','User','Aksi','Detail','IP','User-Agent']);
    $all = qp($conn, "SELECT * FROM admin_logs$where ORDER BY created_at DESC LIMIT 5000", $types, $params);
    foreach ($all as $r) fputcsv($out, [$r['created_at'],$r['user_name'],$r['action'],$r['detail'],$r['ip'],$r['ua']]);
    fclose($out);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
.alg-hero{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.alg-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.alg-card::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.alg-card:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.alg-card .ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.alg-card:hover .ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.alg-card b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.alg-card small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.alg-card.c1{--c:#0a66c2}.alg-card.c2{--c:#00b874}.alg-card.c3{--c:#f59e0b}.alg-card.c4{--c:#8b5cf6}

.alg-filter{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.alg-search{position:relative;flex:1;min-width:220px}
.alg-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9aa8bf;font-size:13px}
.alg-search input{width:100%;padding:10px 13px 10px 38px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;outline:0;transition:.25s}
.alg-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1)}
.alg-select{padding:10px 12px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;background:#fbfcfe;outline:0;cursor:pointer}
.alg-info{font-size:12.5px;color:#7b8aa3;font-weight:600;margin-left:auto}
.alg-info b{color:var(--blue-dark);font-family:var(--display)}

.alg-list{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)}
.alg-row{display:grid;grid-template-columns:170px 140px 140px 1fr 130px;gap:16px;padding:14px 18px;border-bottom:1px solid var(--line);align-items:center;transition:.2s;font-size:13px}
.alg-row:last-child{border-bottom:0}
.alg-row:hover{background:#fbfcfe}
.alg-row .time{font-family:'SFMono-Regular',Consolas,monospace;font-size:11.5px;color:#68758a}
.alg-row .time b{display:block;color:var(--blue-dark);font-size:12.5px}
.alg-row .user{display:flex;align-items:center;gap:9px;font-weight:600;color:var(--blue-dark)}
.alg-row .user img{width:28px;height:28px;border-radius:8px}
.alg-row .user.guest{color:#b3261e;font-style:italic}
.alg-row .action{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.5px;white-space:nowrap;width:fit-content}
.alg-row .action.ok{background:#e7f8f1;color:#0a7a52}.alg-row .action.warn{background:#fef3c7;color:#92400e}.alg-row .action.danger{background:#fdecec;color:#b3261e}.alg-row .action.info{background:#e0f2fe;color:#0369a1}
.alg-row .detail{color:#475569;font-size:12.5px;line-height:1.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.alg-row .ip{font-family:'SFMono-Regular',Consolas,monospace;font-size:11.5px;color:#68758a;text-align:right}

.alg-empty{padding:60px 20px;text-align:center;color:#7b8aa3}
.alg-empty i{font-size:34px;color:#cfd8e3;margin-bottom:14px;display:block}

.alg-pag{display:flex;gap:6px;justify-content:center;margin-top:20px}
.alg-pag a,.alg-pag span{min-width:36px;height:36px;display:inline-grid;place-items:center;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;font-weight:700;font-size:12px;text-decoration:none;transition:.2s}
.alg-pag a:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.alg-pag a.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 6px 14px rgba(0,184,116,.25)}

@media(max-width:1100px){.alg-hero{grid-template-columns:1fr 1fr}.alg-row{grid-template-columns:1fr;gap:6px;padding:12px 14px}}
</style>

<?php
/* Hitung statistik */
$stat24h = (int)(q1($conn, "SELECT COUNT(*) AS c FROM admin_logs WHERE created_at >= (NOW() - INTERVAL 1 DAY)")['c'] ?? 0);
$stat7d  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM admin_logs WHERE created_at >= (NOW() - INTERVAL 7 DAY)")['c'] ?? 0);
$statFail= (int)(q1($conn, "SELECT COUNT(*) AS c FROM admin_logs WHERE action IN ('login_failed','login_throttled','unauth_access')")['c'] ?? 0);
$statTotal = (int)(q1($conn, "SELECT COUNT(*) AS c FROM admin_logs")['c'] ?? 0);
?>

<div class="alg-hero">
  <div class="alg-card c1"><span class="ic"><i class="fa-solid fa-clock"></i></span><div><b><?= $stat24h ?></b><small>24 Jam Terakhir</small></div></div>
  <div class="alg-card c2"><span class="ic"><i class="fa-solid fa-calendar-week"></i></span><div><b><?= $stat7d ?></b><small>7 Hari Terakhir</small></div></div>
  <div class="alg-card c3"><span class="ic"><i class="fa-solid fa-triangle-exclamation"></i></span><div><b><?= $statFail ?></b><small>Aktivitas Mencurigakan</small></div></div>
  <div class="alg-card c4"><span class="ic"><i class="fa-solid fa-database"></i></span><div><b><?= number_format($statTotal) ?></b><small>Total Log</small></div></div>
</div>

<form class="alg-filter" method="get">
  <div class="alg-search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($fQ) ?>" placeholder="Cari user, detail, atau IP...">
  </div>
  <select name="act" class="alg-select">
    <option value="">Semua Aksi</option>
    <?php foreach ($actions as $a): ?>
    <option value="<?= e($a['action']) ?>" <?= $fAct === $a['action'] ? 'selected' : '' ?>><?= e($a['action']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="user" class="alg-select">
    <option value="">Semua User</option>
    <?php foreach ($users as $u): ?>
    <option value="<?= e($u['user_name']) ?>" <?= $fUser === $u['user_name'] ? 'selected' : '' ?>><?= e($u['user_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="days" class="alg-select" onchange="this.form.submit()">
    <option value="1"  <?= $fDays===1?'selected':'' ?>>Hari ini</option>
    <option value="7"  <?= $fDays===7?'selected':'' ?>>7 hari</option>
    <option value="30" <?= $fDays===30?'selected':'' ?>>30 hari</option>
    <option value="90" <?= $fDays===90?'selected':'' ?>>90 hari</option>
    <option value="365" <?= $fDays===365?'selected':'' ?>>1 tahun</option>
  </select>
  <span class="alg-info">Menampilkan <b><?= $total ?></b> entri</span>
  <a href="?export=csv<?= $fQ?'&q='.urlencode($fQ):'' ?><?= $fAct?'&act='.urlencode($fAct):'' ?><?= $fUser?'&user='.urlencode($fUser):'' ?>&days=<?= $fDays ?>" class="btn-small"><i class="fa-solid fa-file-csv"></i> Ekspor CSV</a>
</form>

<div class="alg-list">
  <?php if (!$list): ?>
  <div class="alg-empty">
    <i class="fa-solid fa-clipboard-list"></i>
    <h4 style="color:var(--blue-dark);margin:0 0 6px">Belum ada log pada rentang ini</h4>
    <p style="margin:0;font-size:13px">Coba perluaskan rentang tanggal atau ubah filter.</p>
  </div>
  <?php else: foreach ($list as $r):
    $isGuest = $r['user_name'] === 'tamu';
    $ini = strtoupper(substr($r['user_name'],0,1));
    $avaUrl = "https://ui-avatars.com/api/?name=" . urlencode($r['user_name']) . "&background=0a66c2&color=fff&size=56&bold=true";
    $actClass = match(true){
      in_array($r['action'], ['login_failed','login_throttled','unauth_access','delete_user']) => 'danger',
      in_array($r['action'], ['delete','hapus','logout']) => 'warn',
      in_array($r['action'], ['login_success','simpan','tambah','update']) => 'ok',
      default => 'info',
    };
    $actionLabel = ucwords(str_replace('_',' ',$r['action'])); ?>
  <div class="alg-row">
    <div class="time"><b><?= date('d M Y', strtotime($r['created_at'])) ?></b><?= date('H:i:s', strtotime($r['created_at'])) ?> WIB</div>
    <div class="user <?= $isGuest ? 'guest' : '' ?>">
      <img src="<?= $avaUrl ?>" alt="">
      <span><?= e($r['user_name']) ?></span>
    </div>
    <span class="action <?= $actClass ?>"><?= e($actionLabel) ?></span>
    <div class="detail" title="<?= e($r['detail']) ?>"><?= e($r['detail'] ?: '-') ?></div>
    <div class="ip"><?= e($r['ip']) ?></div>
  </div>
  <?php endforeach; endif; ?>
</div>

<?php if ($pages > 1): ?>
<div class="alg-pag">
  <?php $qs = fn($p) => '?' . http_build_query(array_filter(array_merge(['q'=>$fQ,'act'=>$fAct,'user'=>$fUser,'days'=>$fDays,'hal'=>$p]))); ?>
  <?php if ($page > 1): ?><a href="<?= $qs($page-1) ?>"><i class="fa-solid fa-chevron-left"></i></a><?php endif; ?>
  <?php
  $start = max(1, $page - 2); $end = min($pages, $page + 2);
  if ($start > 1) { echo '<a href="' . $qs(1) . '">1</a>'; if ($start > 2) echo '<span>...</span>'; }
  for ($i = $start; $i <= $end; $i++) echo '<a class="' . ($i === $page ? 'on' : '') . '" href="' . $qs($i) . '">' . $i . '</a>';
  if ($end < $pages) { if ($end < $pages - 1) echo '<span>...</span>'; echo '<a href="' . $qs($pages) . '">' . $pages . '</a>'; }
  ?>
  <?php if ($page < $pages): ?><a href="<?= $qs($page+1) ?>"><i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>