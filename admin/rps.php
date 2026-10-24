<?php
$ACTIVE = 'rps.php'; $TITLE = 'Kurikulum & RPS';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $file_path = upload_file('file', 'rps') ?? $_POST['file_lama'];
        $ukuran = '';
        if ($file_path && file_exists(UPLOAD_PATH . $file_path)) {
            $bytes = filesize(UPLOAD_PATH . $file_path);
            $ukuran = $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 2) . ' KB';
        }
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE rps_kurikulum SET kode_mk=?, mata_kuliah=?, semester=?, tahun_akademik=?, file_path=?, ukuran_file=?, urutan=? WHERE id=?");
            $stmt->bind_param('ssssssii', $_POST['kode_mk'], $_POST['mata_kuliah'], $_POST['semester'], $_POST['tahun_akademik'], $file_path, $ukuran, (int)$_POST['urutan'], $id);
            $msg = 'Data RPS berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO rps_kurikulum (kode_mk, mata_kuliah, semester, tahun_akademik, file_path, ukuran_file, urutan) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssi', $_POST['kode_mk'], $_POST['mata_kuliah'], $_POST['semester'], $_POST['tahun_akademik'], $file_path, $ukuran, (int)$_POST['urutan']);
            $msg = 'RPS baru berhasil diunggah.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        $old = q1($conn, "SELECT file_path FROM rps_kurikulum WHERE id=$id");
        if ($old && file_exists(UPLOAD_PATH . $old['file_path'])) @unlink(UPLOAD_PATH . $old['file_path']);
        $conn->query("DELETE FROM rps_kurikulum WHERE id=$id");
        $msg = 'File RPS berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM rps_kurikulum WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'kode_mk'=>'','mata_kuliah'=>'','semester'=>'Ganjil','tahun_akademik'=>'2026/2027','file_path'=>'','ukuran_file'=>'','urutan'=>1];
}

$statTotal = (int)(q1($conn, "SELECT COUNT(*) AS c FROM rps_kurikulum")['c'] ?? 0);
$allRPS    = q($conn, "SELECT * FROM rps_kurikulum ORDER BY tahun_akademik DESC, semester ASC, urutan ASC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* Menggunakan style yang sama dengan admin/unduh.php untuk konsistensi */
.r-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:22px}
.r-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.r-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.r-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.r-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none}
.r-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.r-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.r-st.c-b{--c:#0a66c2}.r-st.c-g{--c:#00b874}

.r-list{display:flex;flex-direction:column;gap:12px}
.r-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 22px;display:grid;grid-template-columns:auto 1fr auto auto auto;gap:16px;align-items:center;transition:.25s}
.r-item:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.r-ic{width:50px;height:50px;border-radius:12px;background:linear-gradient(135deg,#e5484d,#b3261e);display:grid;place-items:center;font-size:22px;color:#fff;flex:none}
.r-info h4{font-family:var(--display);color:var(--blue-dark);font-size:14.5px;margin-bottom:4px}
.r-info small{color:#7b8aa3;font-size:11.5px;font-weight:600}
.r-info small i{color:var(--green);margin-right:4px}
.r-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;background:var(--gray);color:#475569}
.r-size{font-size:12px;color:#68758a;font-weight:700;font-family:var(--display)}
.r-acts{display:flex;gap:5px}
.r-acts button,.r-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.r-acts button:hover,.r-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.r-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.r-edit{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
.r-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.r-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.r-fld{margin-bottom:16px}
.r-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.r-fld label .req{color:#e5484d}
.r-fld label i{color:var(--green)}
.r-fld input,.r-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.r-fld input:focus,.r-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.r-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.r-up{border:2px dashed var(--line);border-radius:14px;padding:20px;background:#fbfcfe;transition:.3s;text-align:center}
.r-up:hover,.r-up.drag{border-color:var(--green);background:#fff}
.r-up .file-info{display:flex;align-items:center;gap:12px;padding:12px;background:var(--gray);border-radius:10px;margin-top:10px}
.r-up .file-info i{font-size:24px;color:var(--blue)}
.r-up .file-info b{display:block;color:var(--blue-dark);font-size:13px}
.r-up .file-info small{color:#7b8aa3;font-size:11px}
.r-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.r-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.r-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.r-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.r-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.r-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

@media(max-width:1100px){.r-edit{grid-template-columns:1fr}.r-item{grid-template-columns:auto 1fr auto;gap:10px}.r-size,.r-badge{display:none}}
@media(max-width:700px){.r-stats{grid-template-columns:1fr}.r-row{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<div class="r-edit">
  <form method="post" enctype="multipart/form-data" class="r-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="file_lama" value="<?= e($edit['file_path']) ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-file-pdf"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit RPS' : 'Unggah RPS Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui file RPS' : 'Unggah silabus mata kuliah' ?></small></div>
    </div>

    <div class="r-row">
      <div class="r-fld"><label><i class="fa-solid fa-hashtag"></i> Kode MK <span class="req">*</span></label><input name="kode_mk" value="<?= e($edit['kode_mk']) ?>" placeholder="Contoh: FIS101" required></div>
      <div class="r-fld"><label><i class="fa-solid fa-book"></i> Mata Kuliah <span class="req">*</span></label><input name="mata_kuliah" value="<?= e($edit['mata_kuliah']) ?>" placeholder="Contoh: Fisika Dasar I" required></div>
    </div>
    
    <div class="r-row">
      <div class="r-fld"><label><i class="fa-solid fa-calendar"></i> Semester</label>
        <select name="semester">
          <option value="Ganjil" <?= $edit['semester'] === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
          <option value="Genap" <?= $edit['semester'] === 'Genap' ? 'selected' : '' ?>>Genap</option>
        </select>
      </div>
      <div class="r-fld"><label><i class="fa-solid fa-clock"></i> Tahun Akademik</label><input name="tahun_akademik" value="<?= e($edit['tahun_akademik']) ?>" placeholder="2026/2027"></div>
    </div>

    <div class="r-fld">
      <label><i class="fa-solid fa-file-arrow-up"></i> File RPS (PDF) <span class="req">*</span></label>
      <div class="r-up" id="rUp">
        <?php if ($edit['file_path']): ?>
        <div class="file-info">
          <i class="fa-solid fa-file-pdf"></i>
          <div style="flex:1;text-align:left"><b><?= e(basename($edit['file_path'])) ?></b><small><?= e($edit['ukuran_file']) ?></small></div>
        </div>
        <small style="color:#7b8aa3;font-size:11px;display:block;margin-top:10px">Klik untuk ganti file</small>
        <?php else: ?>
        <i class="fa-solid fa-cloud-arrow-up" style="font-size:32px;color:var(--blue);margin-bottom:10px;display:block"></i>
        <b style="color:var(--blue-dark);display:block;margin-bottom:4px">Klik atau seret file PDF ke sini</b>
        <small style="color:#7b8aa3">Format: PDF • Maks 10 MB</small>
        <?php endif; ?>
        <input type="file" name="file" id="rFile" style="display:none" accept=".pdf">
      </div>
    </div>

    <div class="r-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan Tampil</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>

    <div class="r-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui' : 'Unggah' ?></button>
      <a class="sec" href="<?= url('admin/rps.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="if(confirm('Hapus file RPS ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$edit['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>
</div>

<script>
(function(){
  var up = document.getElementById('rUp'), f = document.getElementById('rFile');
  if(up && f) {
    up.addEventListener('click', function(e){ if(e.target.tagName !== 'INPUT') f.click(); });
    ['dragenter','dragover'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.remove('drag'); }); });
    up.addEventListener('drop', function(e){ if(e.dataTransfer.files[0]) { f.files = e.dataTransfer.files; showFile(); } });
    f.addEventListener('change', showFile);
    function showFile(){
      if(!f.files[0]) return;
      var name = f.files[0].name;
      var size = f.files[0].size >= 1048576 ? (f.files[0].size / 1048576).toFixed(2) + ' MB' : (f.files[0].size / 1024).toFixed(2) + ' KB';
      up.innerHTML = '<div class="file-info"><i class="fa-solid fa-file-pdf"></i><div style="flex:1;text-align:left"><b>' + name + '</b><small>' + size + '</small></div></div><small style="color:#7b8aa3;font-size:11px;display:block;margin-top:10px">Klik untuk ganti file</small>';
    }
  }
})();
</script>

<?php else: ?>
<div class="r-stats">
  <div class="r-st c-b"><span class="r-st-ic"><i class="fa-solid fa-file-pdf"></i></span><div><b><?= $statTotal ?></b><small>Total RPS Tersedia</small></div></div>
  <div class="r-st c-g"><span class="r-st-ic"><i class="fa-solid fa-graduation-cap"></i></span><div><b><?= count(array_unique(array_column($allRPS, 'mata_kuliah'))) ?></b><small>Mata Kuliah Unik</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-file-pdf" style="color:var(--green);margin-right:8px"></i>Daftar RPS & Kurikulum</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Unggah RPS</a>
  </div>

  <?php if (!$allRPS): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-file-pdf"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada file RPS</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Unggah RPS pertama untuk mahasiswa.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Unggah RPS Pertama</a>
  </div>
  <?php else: ?>
  <div class="r-list">
    <?php foreach ($allRPS as $r): ?>
    <div class="r-item">
      <span class="r-ic"><i class="fa-solid fa-file-pdf"></i></span>
      <div class="r-info">
        <h4><?= e($r['kode_mk']) ?> - <?= e($r['mata_kuliah']) ?></h4>
        <small><i class="fa-regular fa-calendar"></i> <?= e($r['semester']) ?> <?= e($r['tahun_akademik']) ?> • <i class="fa-solid fa-download"></i> <?= (int)$r['jumlah_unduh'] ?>x diunduh</small>
      </div>
      <span class="r-badge"><?= e($r['semester']) ?></span>
      <span class="r-size"><?= e($r['ukuran_file']) ?></span>
      <div class="r-acts">
        <a href="<?= url('assets/uploads/' . $r['file_path']) ?>" target="_blank" title="Lihat"><i class="fa-solid fa-eye"></i></a>
        <a href="?edit=<?= (int)$r['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="if(confirm('Hapus file RPS ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$r['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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