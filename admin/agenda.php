<?php
$ACTIVE = 'agenda.php'; $TITLE = 'Agenda & Kalender';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE agenda SET judul=?, tanggal_mulai=?, tanggal_selesai=?, lokasi=?, deskripsi=?, tipe=?, status=?, urutan=? WHERE id=?");
            $stmt->bind_param('sssssssii', $_POST['judul'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai'], $_POST['lokasi'], $_POST['deskripsi'], $_POST['tipe'], $_POST['status'], (int)$_POST['urutan'], $id);
            $msg = 'Agenda berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO agenda (judul, tanggal_mulai, tanggal_selesai, lokasi, deskripsi, tipe, status, urutan) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssi', $_POST['judul'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai'], $_POST['lokasi'], $_POST['deskripsi'], $_POST['tipe'], $_POST['status'], (int)$_POST['urutan']);
            $msg = 'Agenda baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM agenda WHERE id=" . (int)$_POST['id']);
        $msg = 'Agenda berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM agenda WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'judul'=>'','tanggal_mulai'=>date('Y-m-d H:i'),'tanggal_selesai'=>'','lokasi'=>'Gedung FMIPA','deskripsi'=>'','tipe'=>'Lainnya','status'=>'akan_datang','urutan'=>1];
}

$statTotal   = (int)(q1($conn, "SELECT COUNT(*) AS c FROM agenda")['c'] ?? 0);
$statAkan    = (int)(q1($conn, "SELECT COUNT(*) AS c FROM agenda WHERE status='akan_datang'")['c'] ?? 0);
$statBerlang = (int)(q1($conn, "SELECT COUNT(*) AS c FROM agenda WHERE status='berlangsung'")['c'] ?? 0);
$allAgenda   = q($conn, "SELECT * FROM agenda ORDER BY tanggal_mulai ASC, urutan ASC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= AGENDA ULTIMATE ADMIN ========= */
.a-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.a-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.a-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.a-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.a-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none}
.a-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.a-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.a-st.c-b{--c:#0a66c2}.a-st.c-g{--c:#00b874}.a-st.c-a{--c:#f59e0b}

.a-list{display:flex;flex-direction:column;gap:12px}
.a-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 22px;display:grid;grid-template-columns:auto 1fr auto auto;gap:16px;align-items:center;transition:.25s}
.a-item:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.a-item.berlangsung{border-left:4px solid #00b874;background:#f0fdf7}
.a-item.selesai{opacity:.7}
.a-date{min-width:80px;text-align:center;padding:12px;background:linear-gradient(135deg,rgba(10,102,194,.1),rgba(0,184,116,.1));border-radius:12px}
.a-date .d{display:block;font-family:var(--display);font-size:24px;color:var(--blue-dark);font-weight:800;line-height:1}
.a-date .m{display:block;font-size:11px;color:#7b8aa3;font-weight:700;text-transform:uppercase;letter-spacing:1px}
.a-info h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:6px}
.a-meta{display:flex;gap:12px;font-size:11.5px;color:#7b8aa3;font-weight:600;flex-wrap:wrap}
.a-meta i{color:var(--green);margin-right:4px}
.a-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;white-space:nowrap}
.a-badge.akan{background:#eef3fa;color:#0a66c2}
.a-badge.berlangsung{background:#e7f8f1;color:#0a7a52}
.a-badge.selesai{background:var(--gray);color:#475569}
.a-type{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:700;background:var(--gray);color:#475569}
.a-acts{display:flex;gap:5px}
.a-acts button,.a-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.a-acts button:hover,.a-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.a-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.a-edit{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
.a-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.a-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.a-fld{margin-bottom:16px}
.a-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.a-fld label .req{color:#e5484d}
.a-fld label i{color:var(--green)}
.a-fld input,.a-fld select,.a-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.a-fld input:focus,.a-fld select:focus,.a-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.a-fld textarea{resize:vertical;min-height:120px;line-height:1.7}
.a-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.a-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.a-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.a-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.a-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.a-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.a-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

.a-prev{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow);position:sticky;top:90px}
.a-prev h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);display:flex;align-items:center;gap:8px}
.a-prev h5 i{color:var(--green)}
.a-prev .card{background:linear-gradient(135deg,#f8fafd,#eef4fb);border-radius:12px;padding:16px}
.a-prev .card h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:8px}
.a-prev .card .meta{display:flex;gap:8px;font-size:11.5px;color:#68758a;margin-bottom:10px;flex-wrap:wrap}
.a-prev .card .meta i{color:var(--green)}
.a-prev .card p{font-size:12.5px;color:#475569;line-height:1.6}

@media(max-width:1100px){.a-edit{grid-template-columns:1fr}.a-item{grid-template-columns:1fr;gap:10px}.a-date{display:none}}
@media(max-width:700px){.a-stats{grid-template-columns:1fr}.a-row{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<div class="a-edit">
  <form method="post" class="a-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-calendar-days"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit Agenda' : 'Tambah Agenda Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui agenda kegiatan' : 'Jadwalkan kegiatan akademik' ?></small></div>
    </div>

    <div class="a-fld"><label><i class="fa-solid fa-heading"></i> Judul Kegiatan <span class="req">*</span></label><input name="judul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Ujian Komprehensif Gelombang 1" required></div>
    
    <div class="a-row">
      <div class="a-fld"><label><i class="fa-regular fa-calendar-check"></i> Tanggal Mulai <span class="req">*</span></label><input type="datetime-local" name="tanggal_mulai" value="<?= e($edit['tanggal_mulai']) ?>" required></div>
      <div class="a-fld"><label><i class="fa-regular fa-calendar"></i> Tanggal Selesai</label><input type="datetime-local" name="tanggal_selesai" value="<?= e($edit['tanggal_selesai']) ?>"></div>
    </div>

    <div class="a-row">
      <div class="a-fld"><label><i class="fa-solid fa-location-dot"></i> Lokasi</label><input name="lokasi" value="<?= e($edit['lokasi']) ?>" placeholder="Contoh: Gedung FMIPA Lt. 2"></div>
      <div class="a-fld"><label><i class="fa-solid fa-tag"></i> Tipe Kegiatan</label>
        <select name="tipe">
          <?php $types = ['Seminar','Ujian','Workshop','Wisuda','Pendaftaran','Lainnya']; foreach($types as $t): ?>
          <option value="<?= $t ?>" <?= $edit['tipe'] === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="a-fld"><label><i class="fa-solid fa-align-left"></i> Deskripsi</label><textarea name="deskripsi" placeholder="Deskripsi detail kegiatan..."><?= e($edit['deskripsi']) ?></textarea></div>

    <div class="a-row">
      <div class="a-fld"><label><i class="fa-solid fa-signal"></i> Status</label>
        <select name="status">
          <option value="akan_datang" <?= $edit['status'] === 'akan_datang' ? 'selected' : '' ?>>Akan Datang</option>
          <option value="berlangsung" <?= $edit['status'] === 'berlangsung' ? 'selected' : '' ?>>Sedang Berlangsung</option>
          <option value="selesai" <?= $edit['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select>
      </div>
      <div class="a-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>
    </div>

    <div class="a-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui' : 'Simpan' ?></button>
      <a class="sec" href="<?= url('admin/agenda.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="if(confirm('Hapus agenda ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$edit['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>

  <aside class="a-prev">
    <h5><i class="fa-solid fa-eye"></i> Pratinjau</h5>
    <div class="card">
      <h4 id="pvJudul"><?= e($edit['judul'] ?: 'Judul Kegiatan') ?></h4>
      <div class="meta">
        <span><i class="fa-regular fa-calendar"></i> <span id="pvTanggal"><?= date('d M Y, H:i', strtotime($edit['tanggal_mulai'])) ?></span></span>
        <span><i class="fa-solid fa-location-dot"></i> <span id="pvLokasi"><?= e($edit['lokasi']) ?></span></span>
      </div>
      <p id="pvDeskripsi"><?= e($edit['deskripsi'] ?: 'Tidak ada deskripsi') ?></p>
    </div>
  </aside>
</div>

<script>
(function(){
  var j = document.querySelector('[name="judul"]'), t = document.querySelector('[name="tanggal_mulai"]'), l = document.querySelector('[name="lokasi"]'), d = document.querySelector('[name="deskripsi"]');
  var pvJ = document.getElementById('pvJudul'), pvT = document.getElementById('pvTanggal'), pvL = document.getElementById('pvLokasi'), pvD = document.getElementById('pvDeskripsi');
  
  if(j) j.addEventListener('input', function(){ pvJ.textContent = this.value || 'Judul Kegiatan'; });
  if(t) t.addEventListener('input', function(){ pvT.textContent = new Date(this.value).toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); });
  if(l) l.addEventListener('input', function(){ pvL.textContent = this.value || 'Gedung FMIPA'; });
  if(d) d.addEventListener('input', function(){ pvD.textContent = this.value || 'Tidak ada deskripsi'; });
})();
</script>

<?php else: ?>
<div class="a-stats">
  <div class="a-st c-b"><span class="a-st-ic"><i class="fa-solid fa-calendar-days"></i></span><div><b><?= $statTotal ?></b><small>Total Agenda</small></div></div>
  <div class="a-st c-g"><span class="a-st-ic"><i class="fa-solid fa-clock"></i></span><div><b><?= $statAkan ?></b><small>Akan Datang</small></div></div>
  <div class="a-st c-a"><span class="a-st-ic"><i class="fa-solid fa-play"></i></span><div><b><?= $statBerlang ?></b><small>Berlangsung</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-calendar-days" style="color:var(--green);margin-right:8px"></i>Daftar Agenda</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Tambah Agenda</a>
  </div>

  <?php if (!$allAgenda): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-calendar-days"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada agenda</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Jadwalkan kegiatan akademik pertama Anda.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Agenda</a>
  </div>
  <?php else: ?>
  <div class="a-list">
    <?php foreach ($allAgenda as $a): 
      $badgeClass = $a['status'];
      $isToday = date('Y-m-d') == date('Y-m-d', strtotime($a['tanggal_mulai']));
    ?>
    <div class="a-item <?= $a['status'] ?> <?= $isToday ? 'berlangsung' : '' ?>">
      <div class="a-date">
        <span class="d"><?= date('d', strtotime($a['tanggal_mulai'])) ?></span>
        <span class="m"><?= date('M', strtotime($a['tanggal_mulai'])) ?></span>
      </div>
      <div class="a-info">
        <h4><?= e($a['judul']) ?> <?php if ($isToday): ?><span style="color:#00b874;font-size:11px;font-weight:800">• HARI INI</span><?php endif; ?></h4>
        <div class="a-meta">
          <span><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($a['tanggal_mulai'])) ?> WIB</span>
          <?php if ($a['tanggal_selesai']): ?><span><i class="fa-solid fa-arrow-right"></i> <?= date('H:i', strtotime($a['tanggal_selesai'])) ?> WIB</span><?php endif; ?>
          <span><i class="fa-solid fa-location-dot"></i> <?= e($a['lokasi']) ?></span>
        </div>
      </div>
      <span class="a-type"><?= e($a['tipe']) ?></span>
      <span class="a-badge <?= $badgeClass ?>"><?= strtoupper(str_replace('_', ' ', $a['status'])) ?></span>
      <div class="a-acts">
        <a href="?edit=<?= (int)$a['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="if(confirm('Hapus agenda ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$a['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>