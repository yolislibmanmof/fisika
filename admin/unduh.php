<?php
$ACTIVE = 'unduh.php'; $TITLE = 'Pusat Unduhan';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $file_path = upload_file('file', 'unduh') ?? $_POST['file_lama'];
        $ukuran = '';
        if ($file_path && file_exists(UPLOAD_PATH . $file_path)) {
            $bytes = filesize(UPLOAD_PATH . $file_path);
            $ukuran = $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 2) . ' KB';
        }
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE unduh SET judul=?, kategori=?, file_path=?, ukuran_file=?, urutan=? WHERE id=?");
            $stmt->bind_param('ssssii', $_POST['judul'], $_POST['kategori'], $file_path, $ukuran, (int)$_POST['urutan'], $id);
            $msg = 'File berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO unduh (judul, kategori, file_path, ukuran_file, urutan) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssssi', $_POST['judul'], $_POST['kategori'], $file_path, $ukuran, (int)$_POST['urutan']);
            $msg = 'File baru berhasil diunggah.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        $old = q1($conn, "SELECT file_path FROM unduh WHERE id=$id");
        if ($old && file_exists(UPLOAD_PATH . $old['file_path'])) @unlink(UPLOAD_PATH . $old['file_path']);
        $conn->query("DELETE FROM unduh WHERE id=$id");
        $msg = 'File berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM unduh WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'judul'=>'','kategori'=>'Akademik','file_path'=>'','ukuran_file'=>'','urutan'=>1];
}

$statTotal  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM unduh")['c'] ?? 0);
$statAkademik = (int)(q1($conn, "SELECT COUNT(*) AS c FROM unduh WHERE kategori='Akademik'")['c'] ?? 0);
$statAdmin  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM unduh WHERE kategori='Administrasi'")['c'] ?? 0);
$allUnduh   = q($conn, "SELECT * FROM unduh ORDER BY urutan ASC, tanggal_upload DESC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= UNDUH ULTIMATE ADMIN ========= */
.u-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.u-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.u-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.u-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.u-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none}
.u-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.u-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.u-st.c-b{--c:#0a66c2}.u-st.c-g{--c:#00b874}.u-st.c-a{--c:#f59e0b}

.u-list{display:flex;flex-direction:column;gap:12px}
.u-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 22px;display:grid;grid-template-columns:auto 1fr auto auto auto;gap:16px;align-items:center;transition:.25s}
.u-item:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.u-ic{width:50px;height:50px;border-radius:12px;display:grid;place-items:center;font-size:22px;color:#fff;flex:none}
.u-ic.pdf{background:linear-gradient(135deg,#e5484d,#b3261e)}
.u-ic.doc{background:linear-gradient(135deg,#0a66c2,#071a39)}
.u-ic.xls{background:linear-gradient(135deg,#00b874,#00915c)}
.u-ic.lain{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}
.u-info h4{font-family:var(--display);color:var(--blue-dark);font-size:14.5px;margin-bottom:4px}
.u-info small{color:#7b8aa3;font-size:11.5px;font-weight:600}
.u-info small i{color:var(--green);margin-right:4px}
.u-kat{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;background:var(--gray);color:#475569}
.u-size{font-size:12px;color:#68758a;font-weight:700;font-family:var(--display)}
.u-acts{display:flex;gap:5px}
.u-acts button,.u-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.u-acts button:hover,.u-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.u-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.u-edit{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
.u-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.u-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-fld{margin-bottom:16px}
.u-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.u-fld label .req{color:#e5484d}
.u-fld label i{color:var(--green)}
.u-fld input,.u-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-fld input:focus,.u-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.u-up{border:2px dashed var(--line);border-radius:14px;padding:20px;background:#fbfcfe;transition:.3s;text-align:center}
.u-up:hover,.u-up.drag{border-color:var(--green);background:#fff}
.u-up .file-info{display:flex;align-items:center;gap:12px;padding:12px;background:var(--gray);border-radius:10px;margin-top:10px}
.u-up .file-info i{font-size:24px;color:var(--blue)}
.u-up .file-info b{display:block;color:var(--blue-dark);font-size:13px}
.u-up .file-info small{color:#7b8aa3;font-size:11px}
.u-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.u-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.u-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.u-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.u-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.u-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

@media(max-width:1100px){.u-edit{grid-template-columns:1fr}.u-item{grid-template-columns:auto 1fr auto;gap:10px}.u-size,.u-kat{display:none}}
@media(max-width:700px){.u-stats{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<div class="u-edit">
  <form method="post" enctype="multipart/form-data" class="u-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="file_lama" value="<?= e($edit['file_path']) ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-download"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit File Unduhan' : 'Unggah File Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui file' : 'Unggah file untuk diunduh publik' ?></small></div>
    </div>

    <div class="u-fld"><label><i class="fa-solid fa-heading"></i> Judul File <span class="req">*</span></label><input name="judul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Pedoman Skripsi 2026" required></div>
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="u-fld"><label><i class="fa-solid fa-tag"></i> Kategori</label>
        <select name="kategori">
          <?php $kats = ['Akademik','Administrasi','Pedoman','Formulir','Lainnya']; foreach($kats as $k): ?>
          <option value="<?= $k ?>" <?= $edit['kategori'] === $k ? 'selected' : '' ?>><?= $k ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>
    </div>

    <div class="u-fld">
      <label><i class="fa-solid fa-file-arrow-up"></i> File <span class="req">*</span> <span style="margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;text-transform:none">PDF, DOC, DOCX, XLS, XLSX • Maks 10 MB</span></label>
      <div class="u-up" id="uUp">
        <?php if ($edit['file_path']): ?>
        <div class="file-info">
          <i class="fa-solid fa-file-pdf"></i>
          <div style="flex:1;text-align:left"><b><?= e(basename($edit['file_path'])) ?></b><small><?= e($edit['ukuran_file']) ?></small></div>
        </div>
        <small style="color:#7b8aa3;font-size:11px;display:block;margin-top:10px">Klik untuk ganti file</small>
        <?php else: ?>
        <i class="fa-solid fa-cloud-arrow-up" style="font-size:32px;color:var(--blue);margin-bottom:10px;display:block"></i>
        <b style="color:var(--blue-dark);display:block;margin-bottom:4px">Klik atau seret file ke sini</b>
        <small style="color:#7b8aa3">PDF, DOC, DOCX, XLS, XLSX</small>
        <?php endif; ?>
        <input type="file" name="file" id="uFile" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx">
      </div>
    </div>

    <div class="u-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui' : 'Unggah' ?></button>
      <a class="sec" href="<?= url('admin/unduh.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="if(confirm('Hapus file ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$edit['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>

  <aside class="u-actions" style="position:sticky;top:90px">
    <div class="u-form" style="padding:18px">
      <h5 style="font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;display:flex;align-items:center;gap:8px"><i class="fa-solid fa-circle-info" style="color:var(--green)"></i> Informasi</h5>
      <div style="font-size:12.5px;color:#68758a;line-height:1.7">
        <p style="margin-bottom:10px"><b style="color:var(--blue-dark)">Format yang didukung:</b> PDF, DOC, DOCX, XLS, XLSX</p>
        <p style="margin-bottom:10px"><b style="color:var(--blue-dark)">Ukuran maksimal:</b> 10 MB per file</p>
        <p><b style="color:var(--blue-dark)">Total file:</b> <?= $statTotal ?> file</p>
      </div>
    </div>
  </aside>
</div>

<script>
(function(){
  var up = document.getElementById('uUp'), f = document.getElementById('uFile');
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
      var ext = name.split('.').pop().toLowerCase();
      var icon = ext === 'pdf' ? 'fa-file-pdf' : (['doc','docx'].includes(ext) ? 'fa-file-word' : (['xls','xlsx'].includes(ext) ? 'fa-file-excel' : 'fa-file'));
      up.innerHTML = '<div class="file-info"><i class="fa-solid ' + icon + '"></i><div style="flex:1;text-align:left"><b>' + name + '</b><small>' + size + '</small></div></div><small style="color:#7b8aa3;font-size:11px;display:block;margin-top:10px">Klik untuk ganti file</small>';
    }
  }
})();
</script>

<?php else: ?>
<div class="u-stats">
  <div class="u-st c-b"><span class="u-st-ic"><i class="fa-solid fa-download"></i></span><div><b><?= $statTotal ?></b><small>Total File</small></div></div>
  <div class="u-st c-g"><span class="u-st-ic"><i class="fa-solid fa-graduation-cap"></i></span><div><b><?= $statAkademik ?></b><small>Akademik</small></div></div>
  <div class="u-st c-a"><span class="u-st-ic"><i class="fa-solid fa-file-lines"></i></span><div><b><?= $statAdmin ?></b><small>Administrasi</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-download" style="color:var(--green);margin-right:8px"></i>Daftar File Unduhan</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Unggah File</a>
  </div>

  <?php if (!$allUnduh): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-download"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada file</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Unggah file pertama untuk diunduh publik.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Unggah File Pertama</a>
  </div>
  <?php else: ?>
  <div class="u-list">
    <?php foreach ($allUnduh as $u): 
      $ext = strtolower(pathinfo($u['file_path'], PATHINFO_EXTENSION));
      $icClass = $ext === 'pdf' ? 'pdf' : (in_array($ext, ['doc','docx']) ? 'doc' : (in_array($ext, ['xls','xlsx']) ? 'xls' : 'lain'));
      $icName = $ext === 'pdf' ? 'fa-file-pdf' : (in_array($ext, ['doc','docx']) ? 'fa-file-word' : (in_array($ext, ['xls','xlsx']) ? 'fa-file-excel' : 'fa-file'));
    ?>
    <div class="u-item">
      <span class="u-ic <?= $icClass ?>"><i class="fa-solid <?= $icName ?>"></i></span>
      <div class="u-info">
        <h4><?= e($u['judul']) ?></h4>
        <small><i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($u['tanggal_upload'])) ?> • <i class="fa-solid fa-download"></i> <?= (int)$u['jumlah_unduh'] ?>x diunduh</small>
      </div>
      <span class="u-kat"><?= e($u['kategori']) ?></span>
      <span class="u-size"><?= e($u['ukuran_file']) ?></span>
      <div class="u-acts">
        <a href="<?= url('assets/uploads/' . $u['file_path']) ?>" target="_blank" title="Buka"><i class="fa-solid fa-eye"></i></a>
        <a href="?edit=<?= (int)$u['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="if(confirm('Hapus file ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$u['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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