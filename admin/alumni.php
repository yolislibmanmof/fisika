<?php
$ACTIVE = 'alumni.php'; $TITLE = 'Tracer Study & Alumni';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $foto = upload_file('foto', 'alumni') ?? $_POST['foto_lama'];
        $ditampilkan = isset($_POST['ditampilkan']) ? 1 : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE alumni SET nama=?, angkatan=?, pekerjaan=?, perusahaan=?, sektor=?, testimoni=?, foto=?, linkedin=?, ditampilkan=?, urutan=? WHERE id=?");
            $stmt->bind_param('sisssssssii', $_POST['nama'], (int)$_POST['angkatan'], $_POST['pekerjaan'], $_POST['perusahaan'], $_POST['sektor'], $_POST['testimoni'], $foto, $_POST['linkedin'], $ditampilkan, (int)$_POST['urutan'], $id);
            $msg = 'Data alumni berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO alumni (nama, angkatan, pekerjaan, perusahaan, sektor, testimoni, foto, linkedin, ditampilkan, urutan) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sisssssssi', $_POST['nama'], (int)$_POST['angkatan'], $_POST['pekerjaan'], $_POST['perusahaan'], $_POST['sektor'], $_POST['testimoni'], $foto, $_POST['linkedin'], $ditampilkan, (int)$_POST['urutan']);
            $msg = 'Alumni baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM alumni WHERE id=" . (int)$_POST['id']);
        $msg = 'Data alumni berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM alumni WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'nama'=>'','angkatan'=>date('Y'),'pekerjaan'=>'','perusahaan'=>'','sektor'=>'Lainnya','testimoni'=>'','foto'=>'','linkedin'=>'','ditampilkan'=>1,'urutan'=>1];
}

$statTotal    = (int)(q1($conn, "SELECT COUNT(*) AS c FROM alumni")['c'] ?? 0);
$statTampil   = (int)(q1($conn, "SELECT COUNT(*) AS c FROM alumni WHERE ditampilkan=1")['c'] ?? 0);
$statAngkatan = (int)(q1($conn, "SELECT COUNT(DISTINCT angkatan) AS c FROM alumni")['c'] ?? 0);
$allAlumni    = q($conn, "SELECT * FROM alumni ORDER BY urutan ASC, angkatan DESC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= ALUMNI ULTIMATE ADMIN ========= */
.al-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.al-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.al-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.al-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.al-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none}
.al-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.al-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.al-st.c-b{--c:#0a66c2}.al-st.c-g{--c:#00b874}.al-st.c-p{--c:#8b5cf6}

.al-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.al-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:.3s;box-shadow:var(--shadow)}
.al-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.al-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.al-card.draft{opacity:.7}
.al-top{display:flex;align-items:flex-start;gap:14px;margin-bottom:14px}
.al-ic{width:56px;height:56px;border-radius:14px;background:var(--gray);overflow:hidden;flex:none;display:grid;place-items:center;color:#cfd8e3;font-size:24px}
.al-ic img{width:100%;height:100%;object-fit:cover}
.al-top h4{font-family:var(--display);color:var(--blue-dark);font-size:15.5px;line-height:1.4;flex:1}
.al-top code{display:block;font-size:11px;color:var(--blue);background:#eef3fa;padding:2px 8px;border-radius:6px;margin-top:5px;width:fit-content}
.al-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;flex:none}
.al-badge.on{background:#e7f8f1;color:#0a7a52}.al-badge.off{background:#fdecec;color:#b3261e}
.al-card .testi{color:#68758a;font-size:12.5px;line-height:1.65;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-height:50px;font-style:italic}
.al-card .testi::before{content:'"';font-size:24px;color:var(--green);font-family:var(--display)}
.al-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line)}
.al-acts{display:flex;gap:5px}
.al-acts button,.al-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.al-acts button:hover,.al-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.al-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.al-edit{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start}
.al-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.al-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.al-fld{margin-bottom:16px}
.al-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.al-fld label .req{color:#e5484d}
.al-fld label i{color:var(--green)}
.al-fld input,.al-fld select,.al-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.al-fld input:focus,.al-fld select:focus,.al-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.al-fld textarea{resize:vertical;min-height:100px;line-height:1.7}
.al-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.al-up{border:2px dashed var(--line);border-radius:14px;padding:14px;background:#fbfcfe;transition:.3s;text-align:center}
.al-up:hover,.al-up.drag{border-color:var(--green);background:#fff}
.al-up img{width:100%;max-height:200px;object-fit:cover;border-radius:10px;display:block;margin:0 auto 8px}
.al-side{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.al-scard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.al-scard h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);display:flex;align-items:center;gap:8px}
.al-scard h5 i{color:var(--green)}
.al-prev{text-align:center}
.al-prev img{width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto 12px;border:3px solid var(--line)}
.al-prev h4{font-family:var(--display);color:var(--blue-dark);font-size:16px;margin-bottom:4px}
.al-prev small{color:#7b8aa3;font-size:12px;display:block;margin-bottom:10px}
.al-prev .testi-prev{font-size:12px;color:#68758a;line-height:1.6;font-style:italic;margin-top:10px;padding-top:10px;border-top:1px dashed var(--line)}
.al-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.al-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.al-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.al-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.al-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.al-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

@media(max-width:1100px){.al-edit{grid-template-columns:1fr}.al-side{position:static}.al-grid{grid-template-columns:1fr}}
@media(max-width:700px){.al-stats{grid-template-columns:1fr}.al-row{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<div class="al-edit">
  <form method="post" enctype="multipart/form-data" class="al-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="foto_lama" value="<?= e($edit['foto']) ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-user-graduate"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit Data Alumni' : 'Tambah Alumni Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui data alumni' : 'Catat jejak lulusan' ?></small></div>
    </div>

    <div class="al-row">
      <div class="al-fld"><label><i class="fa-solid fa-user"></i> Nama Lengkap <span class="req">*</span></label><input name="nama" id="alNama" value="<?= e($edit['nama']) ?>" placeholder="Nama alumni" required></div>
      <div class="al-fld"><label><i class="fa-solid fa-graduation-cap"></i> Angkatan <span class="req">*</span></label><input type="number" name="angkatan" value="<?= (int)$edit['angkatan'] ?>" min="1970" max="2100" required></div>
    </div>

    <div class="al-row">
      <div class="al-fld"><label><i class="fa-solid fa-briefcase"></i> Pekerjaan</label><input name="pekerjaan" value="<?= e($edit['pekerjaan']) ?>" placeholder="Contoh: Data Scientist"></div>
      <div class="al-fld"><label><i class="fa-solid fa-building"></i> Perusahaan</label><input name="perusahaan" value="<?= e($edit['perusahaan']) ?>" placeholder="Contoh: PT Telkom"></div>
    </div>

    <div class="al-row">
      <div class="al-fld"><label><i class="fa-solid fa-industry"></i> Sektor</label>
        <select name="sektor">
          <?php $sektors = ['BUMN','Swasta','PNS/TNI/Polri','Akademisi','Wirausaha','Lainnya']; foreach($sektors as $s): ?>
          <option value="<?= $s ?>" <?= $edit['sektor'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="al-fld"><label><i class="fa-brands fa-linkedin"></i> LinkedIn</label><input name="linkedin" value="<?= e($edit['linkedin']) ?>" placeholder="https://linkedin.com/in/..."></div>
    </div>

    <div class="al-fld"><label><i class="fa-solid fa-quote-left"></i> Testimoni</label><textarea name="testimoni" placeholder="Pesan atau kesan alumni..."><?= e($edit['testimoni']) ?></textarea></div>

    <div class="al-fld">
      <label><i class="fa-solid fa-image"></i> Foto</label>
      <div class="al-up" id="alUp">
        <img id="pvAlumni" class="<?= $edit['foto'] ? '' : 'hidden' ?>" src="<?= $edit['foto'] ? e(media_url($edit['foto'])) : '' ?>" alt="">
        <div class="<?= $edit['foto'] ? 'hidden' : '' ?>" id="alPh" style="padding:20px;color:#9aa8bf"><i class="fa-solid fa-cloud-arrow-up" style="font-size:24px;color:var(--blue);margin-bottom:8px;display:block"></i>Klik untuk pilih foto</div>
        <input type="file" name="foto" id="alFile" accept="image/*" style="display:none">
      </div>
    </div>

    <div class="al-row">
      <div class="al-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>
      <div class="al-fld" style="display:flex;align-items:flex-end;padding-bottom:12px">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-size:14px;color:var(--blue-dark);text-transform:none;letter-spacing:0">
          <input type="checkbox" name="ditampilkan" value="1" <?= $edit['ditampilkan'] ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--green)"> Tampilkan di Website
        </label>
      </div>
    </div>

    <div class="al-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui' : 'Simpan' ?></button>
      <a class="sec" href="<?= url('admin/alumni.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="if(confirm('Hapus data alumni ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$edit['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>

  <aside class="al-side">
    <div class="al-scard">
      <h5><i class="fa-solid fa-eye"></i> Pratinjau</h5>
      <div class="al-prev">
        <img id="pvImg" src="<?= $edit['foto'] ? e(media_url($edit['foto'])) : 'https://ui-avatars.com/api/?name=' . urlencode($edit['nama'] ?: 'Alumni') . '&background=0a66c2&color=fff&size=200' ?>" alt="">
        <h4 id="pvNama"><?= e($edit['nama'] ?: 'Nama Alumni') ?></h4>
        <small id="pvPekerjaan"><?= e($edit['pekerjaan'] ?: 'Pekerjaan') ?> <?php if ($edit['perusahaan']): ?>• <?= e($edit['perusahaan']) ?><?php endif; ?></small>
        <div style="font-size:11px;color:var(--green);font-weight:700;margin-top:4px">Angkatan <?= (int)$edit['angkatan'] ?> • <?= e($edit['sektor']) ?></div>
        <div class="testi-prev" id="pvTesti"><?= e($edit['testimoni'] ?: 'Testimoni akan tampil di sini...') ?></div>
      </div>
    </div>
  </aside>
</div>

<script>
(function(){
  var n = document.getElementById('alNama'), p = document.querySelector('[name="pekerjaan"]'), t = document.querySelector('[name="testimoni"]');
  var pvN = document.getElementById('pvNama'), pvP = document.getElementById('pvPekerjaan'), pvT = document.getElementById('pvTesti');
  if(n) n.addEventListener('input', function(){ pvN.textContent = this.value || 'Nama Alumni'; });
  if(p) p.addEventListener('input', function(){ pvP.textContent = this.value || 'Pekerjaan'; });
  if(t) t.addEventListener('input', function(){ pvT.textContent = this.value || 'Testimoni akan tampil di sini...'; });
  
  var up = document.getElementById('alUp'), f = document.getElementById('alFile'), pv = document.getElementById('pvAlumni'), ph = document.getElementById('alPh'), pvImg = document.getElementById('pvImg');
  if(up && f) {
    up.addEventListener('click', function(e){ if(e.target.tagName !== 'IMG') f.click(); });
    f.addEventListener('change', function(){
      if(!f.files[0]) return;
      var rd = new FileReader();
      rd.onload = function(ev){ 
        pv.src = ev.target.result; pv.classList.remove('hidden'); 
        if(ph) ph.classList.add('hidden');
        if(pvImg) pvImg.src = ev.target.result;
      };
      rd.readAsDataURL(f.files[0]);
    });
  }
})();
</script>

<?php else: ?>
<div class="al-stats">
  <div class="al-st c-b"><span class="al-st-ic"><i class="fa-solid fa-user-graduate"></i></span><div><b><?= $statTotal ?></b><small>Total Alumni</small></div></div>
  <div class="al-st c-g"><span class="al-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $statTampil ?></b><small>Ditampilkan</small></div></div>
  <div class="al-st c-p"><span class="al-st-ic"><i class="fa-solid fa-graduation-cap"></i></span><div><b><?= $statAngkatan ?></b><small>Angkatan</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-user-graduate" style="color:var(--green);margin-right:8px"></i>Daftar Alumni</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Tambah Alumni</a>
  </div>

  <?php if (!$allAlumni): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-user-graduate"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada data alumni</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Catat jejak lulusan pertama Anda.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Alumni Pertama</a>
  </div>
  <?php else: ?>
  <div class="al-grid">
    <?php foreach ($allAlumni as $a): ?>
    <div class="al-card <?= $a['ditampilkan'] ? '' : 'draft' ?>">
      <div class="al-top">
        <span class="al-ic"><?= $a['foto'] ? '<img src="' . e(media_url($a['foto'])) . '" alt="">' : '<i class="fa-solid fa-user"></i>' ?></span>
        <div style="flex:1;min-width:0">
          <h4><?= e($a['nama']) ?></h4>
          <code>Angkatan <?= (int)$a['angkatan'] ?> • <?= e($a['sektor']) ?></code>
        </div>
        <span class="al-badge <?= $a['ditampilkan'] ? 'on' : 'off' ?>"><?= $a['ditampilkan'] ? 'TAMPIL' : 'SEMBUNYI' ?></span>
      </div>
      <div class="testi"><?= e($a['testimoni'] ?: 'Belum ada testimoni') ?></div>
      <div style="font-size:12px;color:#68758a;margin-bottom:12px"><b style="color:var(--blue-dark)"><?= e($a['pekerjaan']) ?></b> <?php if ($a['perusahaan']): ?>di <?= e($a['perusahaan']) ?><?php endif; ?></div>
      <div class="al-foot">
        <span style="font-size:11.5px;color:#7b8aa3;font-weight:700"><i class="fa-solid fa-arrow-down-1-9" style="color:var(--green);margin-right:5px"></i> #<?= (int)$a['urutan'] ?></span>
        <div class="al-acts">
          <a href="?edit=<?= (int)$a['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <button type="button" class="dng" onclick="if(confirm('Hapus data alumni ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$a['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
        </div>
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