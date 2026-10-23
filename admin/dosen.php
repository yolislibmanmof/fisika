<?php
$ACTIVE = 'dosen.php'; $TITLE = 'Direktori Dosen';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $foto = upload_file('foto', 'dosen') ?? $_POST['foto_lama'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE dosen SET nama=?, nip=?, gelar=?, bidang_keahlian=?, foto=?, email=?, scholar=?, scopus=?, orcid=?, pendidikan=?, urutan=?, aktif=? WHERE id=?");
            $stmt->bind_param('ssssssssssiii', $_POST['nama'], $_POST['nip'], $_POST['gelar'], $_POST['bidang_keahlian'], $foto, $_POST['email'], $_POST['scholar'], $_POST['scopus'], $_POST['orcid'], $_POST['pendidikan'], (int)$_POST['urutan'], $aktif, $id);
            $msg = 'Data dosen berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO dosen (nama, nip, gelar, bidang_keahlian, foto, email, scholar, scopus, orcid, pendidikan, urutan, aktif) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssssssii', $_POST['nama'], $_POST['nip'], $_POST['gelar'], $_POST['bidang_keahlian'], $foto, $_POST['email'], $_POST['scholar'], $_POST['scopus'], $_POST['orcid'], $_POST['pendidikan'], (int)$_POST['urutan'], $aktif);
            $msg = 'Dosen baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM dosen WHERE id=" . (int)$_POST['id']);
        $msg = 'Data dosen berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM dosen WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'nama'=>'','nip'=>'','gelar'=>'','bidang_keahlian'=>'','foto'=>'','email'=>'','scholar'=>'','scopus'=>'','orcid'=>'','pendidikan'=>'','urutan'=>1,'aktif'=>1];
}

$statTotal  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM dosen")['c'] ?? 0);
$statAktif  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM dosen WHERE aktif=1")['c'] ?? 0);
$statBidang = (int)(q1($conn, "SELECT COUNT(DISTINCT bidang_keahlian) AS c FROM dosen WHERE aktif=1")['c'] ?? 0);
$allDosen   = q($conn, "SELECT * FROM dosen ORDER BY urutan ASC, nama ASC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= DOSEN ULTIMATE ADMIN ========= */
.d2-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.d2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.d2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.d2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.d2-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.d2-st:hover .d2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.d2-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.d2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.d2-st.c-b{--c:#0a66c2}.d2-st.c-g{--c:#00b874}.d2-st.c-p{--c:#8b5cf6}

.d-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.d-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:.3s;box-shadow:var(--shadow)}
.d-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.d-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.d-card.draft{opacity:.7}
.d-top{display:flex;align-items:flex-start;gap:14px;margin-bottom:14px}
.d-ic{width:56px;height:56px;border-radius:14px;background:var(--gray);overflow:hidden;flex:none;display:grid;place-items:center;color:#cfd8e3;font-size:24px}
.d-ic img{width:100%;height:100%;object-fit:cover}
.d-top h4{font-family:var(--display);color:var(--blue-dark);font-size:15.5px;line-height:1.4;flex:1}
.d-top code{display:block;font-size:11px;color:var(--blue);background:#eef3fa;padding:2px 8px;border-radius:6px;margin-top:5px;width:fit-content}
.d-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;flex:none}
.d-badge.on{background:#e7f8f1;color:#0a7a52}.d-badge.off{background:#fdecec;color:#b3261e}
.d-card p{color:#68758a;font-size:12.5px;line-height:1.65;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:42px}
.d-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line)}
.d-acts{display:flex;gap:5px}
.d-acts button,.d-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.d-acts button:hover,.d-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.d-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.d-edit{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start}
.d-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.d-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.d-fld{margin-bottom:16px}
.d-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.d-fld label .req{color:#e5484d}
.d-fld label i{color:var(--green)}
.d-fld input,.d-fld select,.d-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.d-fld input:focus,.d-fld select:focus,.d-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.d-fld textarea{resize:vertical;min-height:120px;line-height:1.7}
.d-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.d-up{border:2px dashed var(--line);border-radius:14px;padding:14px;background:#fbfcfe;transition:.3s;text-align:center}
.d-up:hover,.d-up.drag{border-color:var(--green);background:#fff}
.d-up img{width:100%;max-height:200px;object-fit:cover;border-radius:10px;display:block;margin:0 auto 8px}
.d-side{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.d-scard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.d-scard h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:8px}
.d-scard h5::after{content:'';position:absolute;left:0;bottom:-2px;width:32px;height:2px;background:var(--grad)}
.d-prev{text-align:center}
.d-prev img{width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto 12px;border:3px solid var(--line)}
.d-prev h4{font-family:var(--display);color:var(--blue-dark);font-size:16px;margin-bottom:4px}
.d-prev small{color:#7b8aa3;font-size:12px;display:block;margin-bottom:10px}
.d-prev .links{display:flex;justify-content:center;gap:8px;margin-top:10px}
.d-prev .links a{width:32px;height:32px;border-radius:8px;background:var(--gray);color:var(--blue);display:grid;place-items:center;font-size:14px;transition:.2s;text-decoration:none}
.d-prev .links a:hover{background:var(--grad);color:#fff}
.d-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.d-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.d-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.d-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.d-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.d-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

.d-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.d-modal.open{display:flex}
.d-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.d-modal-body{padding:28px;text-align:center}
.d-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.d-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.d-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.d-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.d-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.d-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.d-edit{grid-template-columns:1fr}.d-side{position:static}.d-grid{grid-template-columns:1fr}}
@media(max-width:700px){.d2-stats{grid-template-columns:1fr}.d-row{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<!-- ===== EDITOR ===== -->
<div class="d-edit">
  <form method="post" enctype="multipart/form-data" class="d-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="foto_lama" value="<?= e($edit['foto']) ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-chalkboard-user"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit Profil Dosen' : 'Tambah Dosen Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui data dosen' : 'Isi data lengkap dosen' ?></small></div>
    </div>

    <div class="d-row">
      <div class="d-fld"><label><i class="fa-solid fa-user"></i> Nama Lengkap <span class="req">*</span></label><input name="nama" id="dNama" value="<?= e($edit['nama']) ?>" placeholder="Contoh: Dr. Rizky Pratama" required></div>
      <div class="d-fld"><label><i class="fa-solid fa-id-card"></i> Gelar</label><input name="gelar" id="dGelar" value="<?= e($edit['gelar']) ?>" placeholder="Contoh: M.Si."></div>
    </div>
    <div class="d-row">
      <div class="d-fld"><label><i class="fa-solid fa-hashtag"></i> NIP</label><input name="nip" value="<?= e($edit['nip']) ?>" placeholder="Opsional"></div>
      <div class="d-fld"><label><i class="fa-solid fa-atom"></i> Bidang Keahlian <span class="req">*</span></label><input name="bidang_keahlian" value="<?= e($edit['bidang_keahlian']) ?>" placeholder="Contoh: Fisika Material" required></div>
    </div>
    <div class="d-fld"><label><i class="fa-solid fa-envelope"></i> Email Institusi</label><input type="email" name="email" value="<?= e($edit['email']) ?>" placeholder="nama@kampus.ac.id"></div>
    
    <div class="d-row">
      <div class="d-fld"><label><i class="fa-solid fa-graduation-cap"></i> Google Scholar</label><input name="scholar" value="<?= e($edit['scholar']) ?>" placeholder="https://scholar.google.com/..."></div>
      <div class="d-fld"><label><i class="fa-solid fa-database"></i> Scopus ID</label><input name="scopus" value="<?= e($edit['scopus']) ?>" placeholder="https://www.scopus.com/..."></div>
    </div>
    <div class="d-fld"><label><i class="fa-solid fa-fingerprint"></i> ORCID</label><input name="orcid" value="<?= e($edit['orcid']) ?>" placeholder="https://orcid.org/..."></div>

    <div class="d-fld"><label><i class="fa-solid fa-book"></i> Riwayat Pendidikan</label><textarea name="pendidikan" placeholder="S1: Universitas X (2010)&#10;S2: Universitas Y (2015)&#10;S3: Universitas Z (2020)"><?= e($edit['pendidikan']) ?></textarea></div>

    <div class="d-row">
      <div class="d-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>
      <div class="d-fld" style="display:flex;align-items:flex-end;padding-bottom:12px">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-size:14px;color:var(--blue-dark);text-transform:none;letter-spacing:0">
          <input type="checkbox" name="aktif" value="1" <?= $edit['aktif'] ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--green)"> Tampilkan di Website
        </label>
      </div>
    </div>

    <div class="d-fld">
      <label><i class="fa-solid fa-image"></i> Foto Profil</label>
      <div class="d-up" id="dUp">
        <img id="pvDosen" class="<?= $edit['foto'] ? '' : 'hidden' ?>" src="<?= $edit['foto'] ? e(media_url($edit['foto'])) : '' ?>" alt="">
        <div class="<?= $edit['foto'] ? 'hidden' : '' ?>" id="dPh" style="padding:20px;color:#9aa8bf"><i class="fa-solid fa-cloud-arrow-up" style="font-size:24px;color:var(--blue);margin-bottom:8px;display:block"></i>Klik untuk pilih foto</div>
        <input type="file" name="foto" id="dFile" accept="image/*" style="display:none">
      </div>
    </div>

    <div class="d-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Data' : 'Simpan Data' ?></button>
      <a class="sec" href="<?= url('admin/dosen.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="dDel(<?= (int)$edit['id'] ?>,'<?= e(addslashes($edit['nama'])) ?>')"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>

  <!-- LIVE PREVIEW -->
  <aside class="d-side">
    <div class="d-scard">
      <h5><i class="fa-solid fa-eye"></i> Pratinjau Profil</h5>
      <div class="d-prev">
        <img id="pvImg" src="<?= $edit['foto'] ? e(media_url($edit['foto'])) : 'https://ui-avatars.com/api/?name=' . urlencode($edit['nama'] ?: 'Dosen') . '&background=0a66c2&color=fff&size=200' ?>" alt="">
        <h4 id="pvNama"><?= e($edit['nama'] ?: 'Nama Dosen') ?></h4>
        <small id="pvGelar"><?= e($edit['gelar'] ?: 'Gelar') ?></small>
        <div style="font-size:12px;color:var(--green);font-weight:700" id="pvBidang"><?= e($edit['bidang_keahlian'] ?: 'Bidang Keahlian') ?></div>
        <div class="links">
          <a id="pvScholar" href="<?= e($edit['scholar']) ?: '#' ?>" target="_blank" style="<?= $edit['scholar'] ? '' : 'opacity:0.3;pointer-events:none' ?>"><i class="fa-solid fa-graduation-cap"></i></a>
          <a id="pvScopus" href="<?= e($edit['scopus']) ?: '#' ?>" target="_blank" style="<?= $edit['scopus'] ? '' : 'opacity:0.3;pointer-events:none' ?>"><i class="fa-solid fa-database"></i></a>
          <a id="pvOrcid" href="<?= e($edit['orcid']) ?: '#' ?>" target="_blank" style="<?= $edit['orcid'] ? '' : 'opacity:0.3;pointer-events:none' ?>"><i class="fa-solid fa-fingerprint"></i></a>
        </div>
      </div>
    </div>
  </aside>
</div>

<!-- Modal Hapus -->
<div class="d-modal" id="dDelModal">
  <div class="d-modal-box">
    <div class="d-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Data Dosen?</h3>
      <p id="dDelText">Data akan dihapus permanen.</p>
    </div>
    <form method="post" class="d-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="dDelId" value="0">
      <button type="button" onclick="document.getElementById('dDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  var n = document.getElementById('dNama'), g = document.getElementById('dGelar'), b = document.getElementById('dBidang') || {value:''};
  var pvN = document.getElementById('pvNama'), pvG = document.getElementById('pvGelar'), pvB = document.getElementById('pvBidang');
  if(n) n.addEventListener('input', function(){ pvN.textContent = this.value || 'Nama Dosen'; });
  if(g) g.addEventListener('input', function(){ pvG.textContent = this.value || 'Gelar'; });
  
  var up = document.getElementById('dUp'), f = document.getElementById('dFile'), pv = document.getElementById('pvDosen'), ph = document.getElementById('dPh'), pvImg = document.getElementById('pvImg');
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
  
  window.dDel = function(id, nama){
    document.getElementById('dDelId').value = id;
    document.getElementById('dDelText').textContent = 'Dosen "' + nama + '" akan dihapus permanen.';
    document.getElementById('dDelModal').classList.add('open');
  };
  document.getElementById('dDelModal').addEventListener('click', function(e){ if(e.target === this) this.classList.remove('open'); });
})();
</script>

<?php else: ?>
<!-- ===== DAFTAR ===== -->
<div class="d2-stats">
  <div class="d2-st c-b"><span class="d2-st-ic"><i class="fa-solid fa-chalkboard-user"></i></span><div><b><?= $statTotal ?></b><small>Total Dosen</small></div></div>
  <div class="d2-st c-g"><span class="d2-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $statAktif ?></b><small>Dosen Aktif</small></div></div>
  <div class="d2-st c-p"><span class="d2-st-ic"><i class="fa-solid fa-atom"></i></span><div><b><?= $statBidang ?></b><small>Bidang Keahlian</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-chalkboard-user" style="color:var(--green);margin-right:8px"></i>Daftar Dosen</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Tambah Dosen</a>
  </div>

  <?php if (!$allDosen): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-chalkboard-user"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada data dosen</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Tambahkan profil dosen pertama Anda.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Dosen Pertama</a>
  </div>
  <?php else: ?>
  <div class="d-grid">
    <?php foreach ($allDosen as $d): ?>
    <div class="d-card <?= $d['aktif'] ? '' : 'draft' ?>">
      <div class="d-top">
        <span class="d-ic"><?= $d['foto'] ? '<img src="' . e(media_url($d['foto'])) . '" alt="">' : '<i class="fa-solid fa-user"></i>' ?></span>
        <div style="flex:1;min-width:0">
          <h4><?= e($d['nama']) ?> <?= e($d['gelar']) ? ', ' . e($d['gelar']) : '' ?></h4>
          <code><?= e($d['bidang_keahlian']) ?></code>
        </div>
        <span class="d-badge <?= $d['aktif'] ? 'on' : 'off' ?>"><?= $d['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      </div>
      <p><?= e(mb_strimwidth(trim(strip_tags($d['pendidikan'] ?: 'Belum ada riwayat pendidikan')), 0, 100, '...')) ?></p>
      <div class="d-foot">
        <span style="font-size:11.5px;color:#7b8aa3;font-weight:700"><i class="fa-solid fa-arrow-down-1-9" style="color:var(--green);margin-right:5px"></i> Urutan #<?= (int)$d['urutan'] ?></span>
        <div class="d-acts">
          <a href="?edit=<?= (int)$d['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <button type="button" class="dng" onclick="dDel(<?= (int)$d['id'] ?>,'<?= e(addslashes($d['nama'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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