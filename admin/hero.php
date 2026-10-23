<?php
$ACTIVE = 'hero.php'; $TITLE = 'Hero Slider';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $fileLama = $_POST['file_lama'];
        $file = upload_file('file', 'hero') ?? $fileLama;
        if (!$file) $file = $_POST['file_url'] ?: $fileLama;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE heroes SET tipe=?,file=?,judul=?,subtitle=?,badge=?,urutan=?,aktif=? WHERE id=?");
            $stmt->bind_param('sssssiii', $_POST['tipe'], $file, $_POST['judul'], $_POST['subtitle'], $_POST['badge'], $_POST['urutan'], $_POST['aktif'], $id);
            $msg = 'Slide berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO heroes (tipe,file,judul,subtitle,badge,urutan,aktif) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssii', $_POST['tipe'], $file, $_POST['judul'], $_POST['subtitle'], $_POST['badge'], $_POST['urutan'], $_POST['aktif']);
            $msg = 'Slide baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM heroes WHERE id=$id");
        $msg = 'Slide dihapus.';
    }
    if (isset($_POST['toggle'])) {
        $conn->query("UPDATE heroes SET aktif = 1 - aktif WHERE id=" . (int)$_POST['id']);
        $msg = 'Status slide diubah.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM heroes WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'tipe'=>'image','file'=>'','judul'=>'','subtitle'=>'','badge'=>'','urutan'=>1,'aktif'=>1];
}

/* Statistik */
$stTotal = (int)(q1($conn, "SELECT COUNT(*) AS c FROM heroes")['c'] ?? 0);
$stAktif = (int)(q1($conn, "SELECT COUNT(*) AS c FROM heroes WHERE aktif=1")['c'] ?? 0);
$stVideo = (int)(q1($conn, "SELECT COUNT(*) AS c FROM heroes WHERE tipe='video'")['c'] ?? 0);
$stImg   = $stTotal - $stVideo;

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= HERO SLIDER ULTIMATE ========= */
.h2-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.h2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.h2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.h2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.h2-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.h2-st:hover .h2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.h2-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.h2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.h2-st.c-b{--c:#0a66c2}.h2-st.c-g{--c:#00b874}.h2-st.c-p{--c:#8b5cf6}.h2-st.c-a{--c:#f59e0b}

/* Editor */
.h2-edit{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start}
.h2-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.h2-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.h2-form .h2-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.h2-form .h2-h .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.h2-form .h2-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.h2-form .h2-h small{color:#7b8aa3;font-size:12px}
.h2-fld{margin-bottom:16px}
.h2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.h2-fld label .req{color:#e5484d}
.h2-fld label i{color:var(--green)}
.h2-fld input,.h2-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.h2-fld input:focus,.h2-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}

/* Upload area */
.h2-up{border:2px dashed var(--line);border-radius:14px;padding:16px;background:#fbfcfe;transition:.3s;position:relative}
.h2-up:hover,.h2-up.drag{border-color:var(--green);background:#fff}
.h2-up img,.h2-up video{width:100%;max-height:280px;object-fit:cover;border-radius:10px;display:block;margin-bottom:10px}
.h2-up .ph{text-align:center;padding:28px 10px;color:#9aa8bf}
.h2-up .ph i{font-size:34px;color:var(--blue);margin-bottom:10px;display:block}
.h2-up .ph b{display:block;color:var(--blue-dark);margin-bottom:4px}
.h2-up .ph small{font-size:11.5px}
.h2-upbtns{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.h2-upbtns button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:8px 12px;border-radius:8px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.h2-upbtns button:hover{background:var(--grad);color:#fff;border-color:transparent}
.h2-upbtns .dng:hover{background:#e5484d;border-color:#e5484d}
.h2-or{display:flex;align-items:center;gap:10px;margin:14px 0;color:#9aa8bf;font-size:11px;font-weight:800;letter-spacing:1.4px}
.h2-or::before,.h2-or::after{content:'';flex:1;height:1px;background:var(--line)}

/* Preview pane */
.h2-pvpane{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.h2-pvtitle{font-family:var(--display);font-size:12px;color:var(--blue-dark);padding:10px 14px;background:var(--gray);border-radius:10px;display:flex;align-items:center;gap:8px;font-weight:800;letter-spacing:1px}
.h2-pvtitle i{color:var(--green)}
.h2-pvtitle small{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0}
.h2-pvcard{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(10,42,94,.14);transition:.35s}
.h2-pvcard:hover{transform:translateY(-5px)}
.h2-pvcard .h2-pvmedia{position:relative;height:240px;background:linear-gradient(135deg,#0a2a5e,#071a39);display:grid;place-items:center;color:#7ef0c4;font-size:44px;overflow:hidden}
.h2-pvcard .h2-pvmedia img,.h2-pvcard .h2-pvmedia video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.h2-pvcard .h2-pvbody{padding:18px}
.h2-pvcard .h2-pvbody .badge{display:inline-block;padding:4px 12px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1px;background:var(--grad);color:#fff;margin-bottom:10px}
.h2-pvcard .h2-pvbody h4{font-family:var(--display);color:var(--blue-dark);font-size:16px;line-height:1.4;margin-bottom:8px;min-height:24px}
.h2-pvcard .h2-pvbody p{color:#68758a;font-size:13px;line-height:1.6;min-height:20px}
.h2-pvcard .h2-pvstatus{position:absolute;top:14px;right:14px;padding:5px 12px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1px}
.h2-pvstatus.on{background:#e7f8f1;color:#0a7a52}
.h2-pvstatus.off{background:#fdecec;color:#b3261e}

.h2-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.h2-actions .btn-primary{flex:1;min-width:160px;justify-content:center;padding:13px}
.h2-actions .btn-ghost-d{flex:1;min-width:110px;justify-content:center;padding:13px}

/* Daftar slide */
.h2-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.h2-slide{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;position:relative;transition:.3s;box-shadow:var(--shadow)}
.h2-slide:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.h2-slide.inactive{opacity:.65}
.h2-slide .h2-media{position:relative;height:180px;background:linear-gradient(135deg,#0a2a5e,#071a39);display:grid;place-items:center;color:#7ef0c4;font-size:38px;overflow:hidden}
.h2-slide .h2-media img,.h2-slide .h2-media video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.h2-slide .h2-badge{position:absolute;top:12px;left:12px;padding:5px 12px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1px;z-index:2}
.h2-badge.img{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff}
.h2-badge.vid{background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff}
.h2-slide .h2-status{position:absolute;top:12px;right:12px;padding:5px 12px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1px;z-index:2}
.h2-status.on{background:#e7f8f1;color:#0a7a52}
.h2-status.off{background:#fdecec;color:#b3261e}
.h2-slide .h2-body{padding:18px}
.h2-slide .h2-body h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;line-height:1.4;margin-bottom:6px;min-height:22px}
.h2-slide .h2-body p{color:#7b8aa3;font-size:12.5px;line-height:1.5;margin-bottom:14px;min-height:20px}
.h2-slide .h2-meta{display:flex;gap:10px;font-size:11px;color:#9aa8bf;font-weight:700;margin-bottom:14px;flex-wrap:wrap}
.h2-slide .h2-meta i{color:var(--green);margin-right:4px}
.h2-slide .h2-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line)}
.h2-slide .h2-ord{font-size:11.5px;color:#7b8aa3;font-weight:700}
.h2-slide .h2-ord i{color:var(--green);margin-right:5px}
.h2-slide .h2-acts{display:flex;gap:5px}
.h2-slide .h2-acts button,.h2-slide .h2-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.h2-slide .h2-acts button:hover,.h2-slide .h2-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.h2-slide .h2-acts .dng:hover{background:#e5484d;border-color:#e5484d}
.h2-slide .h2-acts .tg{background:#e7f8f1;border-color:#b5e8d4;color:#0a7a52}
.h2-slide .h2-acts .tg.off{background:#fdecec;border-color:#f5c2c0;color:#b3261e}

.h2-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.h2-empty .eic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}
.h2-empty h3{font-family:var(--display);color:var(--blue-dark);margin-bottom:8px}
.h2-empty p{color:#7b8aa3;font-size:13.5px;margin-bottom:18px}

/* Modal */
.h2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.h2-modal.open{display:flex}
.h2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.h2-modal-body{padding:28px;text-align:center}
.h2-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.h2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.h2-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.h2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.h2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.h2-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.h2-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.h2-edit{grid-template-columns:1fr}.h2-pvpane{position:static}.h2-grid{grid-template-columns:1fr}}
@media(max-width:700px){.h2-stats{grid-template-columns:1fr 1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<!-- ===== EDITOR ===== -->
<div class="h2-edit">
  <form method="post" enctype="multipart/form-data" class="h2-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="file_lama" value="<?= e($edit['file']) ?>">
    <div class="h2-h">
      <span class="ic"><i class="fa-solid fa-photo-film"></i></span>
      <div>
        <b><?= $edit['id'] ? 'Edit Slide Hero' : 'Tambah Slide Hero Baru' ?></b>
        <small><?= $edit['id'] ? 'Memperbarui slide #' . $edit['id'] : 'Tambahkan slide untuk beranda public' ?></small>
      </div>
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-photo-film"></i> Tipe Media</label>
      <select name="tipe" id="hTipe">
        <option value="image" <?= $edit['tipe'] === 'image' ? 'selected' : '' ?>>📷 Foto / Gambar</option>
        <option value="video" <?= $edit['tipe'] === 'video' ? 'selected' : '' ?>>🎬 Video (MP4/WebM)</option>
      </select>
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-cloud-arrow-up"></i> Upload File</label>
      <div class="h2-up" id="hUp">
        <?php if ($edit['tipe'] === 'video' && $edit['file']): ?>
        <video id="pvHero" controls class="<?= $edit['file'] ? '' : 'hidden' ?>"><source src="<?= e(media_url($edit['file'])) ?>" type="video/mp4"></video>
        <?php else: ?>
        <img id="pvHero" class="<?= $edit['file'] && $edit['tipe'] === 'image' ? '' : 'hidden' ?>" src="<?= $edit['file'] ? e(media_url($edit['file'])) : '' ?>" alt="">
        <?php endif; ?>
        <div class="ph <?= $edit['file'] ? 'hidden' : '' ?>" id="hPh">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <b>Klik atau seret file ke sini</b>
          <small>Foto: JPG/PNG/WebP • Video: MP4/WebM • Maks 50 MB</small>
        </div>
        <input type="file" name="file" id="hFile" accept="image/*,video/mp4,video/webm" style="display:none">
      </div>
      <div class="h2-upbtns">
        <button type="button" onclick="document.getElementById('hFile').click()"><i class="fa-solid fa-upload"></i> Pilih File</button>
        <button type="button" class="dng" onclick="hRemove()"><i class="fa-solid fa-trash"></i> Hapus File</button>
      </div>
      <div class="h2-or">ATAU GUNAKAN URL</div>
      <input name="file_url" id="hUrl" placeholder="https://contoh.com/gambar.jpg" oninput="hUrlPrev(this.value)">
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-heading"></i> Judul Slide</label>
      <input name="judul" id="hJudul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Mencetak Ilmuwan Fisika Masa Depan">
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-align-left"></i> Sub-judul</label>
      <input name="subtitle" id="hSubtitle" value="<?= e($edit['subtitle']) ?>" placeholder="Contoh: Program Studi Fisika — Terakreditasi UNGGUL">
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-tag"></i> Badge</label>
      <input name="badge" id="hBadge" value="<?= e($edit['badge']) ?>" placeholder="Contoh: PRODI FISIKA - TERAKREDITASI UNGGUL">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="h2-fld">
        <label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label>
        <input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1">
      </div>
      <div class="h2-fld">
        <label><i class="fa-solid fa-signal"></i> Status</label>
        <select name="aktif" id="hStatus">
          <option value="1" <?= $edit['aktif'] ? 'selected' : '' ?>>✓ Aktif (Tampil di slider)</option>
          <option value="0" <?= !$edit['aktif'] ? 'selected' : '' ?>>⏸ Nonaktif</option>
        </select>
      </div>
    </div>

    <div class="h2-actions">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Slide' : 'Simpan Slide' ?></button>
      <a class="btn-ghost-d" href="<?= url('admin/hero.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
    </div>
  </form>

  <!-- PREVIEW -->
  <div class="h2-pvpane">
    <div class="h2-pvtitle"><i class="fa-solid fa-eye"></i> PRATINJAU SLIDE <small>Tampilan beranda public</small></div>
    <div class="h2-pvcard">
      <span class="h2-pvstatus <?= $edit['aktif'] ? 'on' : 'off' ?>" id="pvStatus"><?= $edit['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      <div class="h2-pvmedia" id="pvMedia">
        <i class="fa-solid fa-image" id="pvPhIcon"></i>
        <?php if ($edit['tipe'] === 'video' && $edit['file']): ?>
        <video id="pvVideo" controls class="<?= $edit['file'] ? '' : 'hidden' ?>"><source src="<?= e(media_url($edit['file'])) ?>" type="video/mp4"></video>
        <?php else: ?>
        <img id="pvImg" class="<?= $edit['file'] && $edit['tipe'] === 'image' ? '' : 'hidden' ?>" src="<?= $edit['file'] ? e(media_url($edit['file'])) : '' ?>" alt="">
        <?php endif; ?>
      </div>
      <div class="h2-pvbody">
        <span class="badge" id="pvBadge"><?= e($edit['badge'] ?: 'BADGE SLIDE') ?></span>
        <h4 id="pvJudul"><?= e($edit['judul'] ?: 'Judul Slide') ?></h4>
        <p id="pvSubtitle"><?= e($edit['subtitle'] ?: 'Sub-judul slide akan tampil di sini...') ?></p>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var tipe = document.getElementById('hTipe');
  var judul = document.getElementById('hJudul'), subtitle = document.getElementById('hSubtitle'), badge = document.getElementById('hBadge');
  var pvJ = document.getElementById('pvJudul'), pvS = document.getElementById('pvSubtitle'), pvB = document.getElementById('pvBadge');
  var pvSt = document.getElementById('pvStatus');
  var status = document.getElementById('hStatus');

  if (judul) judul.addEventListener('input', function(){ pvJ.textContent = this.value || 'Judul Slide'; });
  if (subtitle) subtitle.addEventListener('input', function(){ pvS.textContent = this.value || 'Sub-judul slide akan tampil di sini...'; });
  if (badge) badge.addEventListener('input', function(){ pvB.textContent = this.value || 'BADGE SLIDE'; });
  if (status) status.addEventListener('change', function(){
    pvSt.textContent = this.value === '1' ? 'AKTIF' : 'NONAKTIF';
    pvSt.className = 'h2-pvstatus ' + (this.value === '1' ? 'on' : 'off');
  });
  if (tipe) tipe.addEventListener('change', function(){
    var icon = document.getElementById('pvPhIcon');
    if (icon) icon.className = 'fa-solid ' + (this.value === 'video' ? 'fa-film' : 'fa-image');
  });

  /* Upload & drag-drop */
  var up = document.getElementById('hUp'), f = document.getElementById('hFile'), ph = document.getElementById('hPh');
  var pvImg = document.getElementById('pvImg'), pvVideo = document.getElementById('pvVideo'), pvIcon = document.getElementById('pvPhIcon');

  function show(file){
    var rd = new FileReader();
    rd.onload = function(ev){
      if (tipe && tipe.value === 'video') {
        if (pvVideo) {
          pvVideo.innerHTML = '<source src="' + ev.target.result + '" type="video/mp4">';
          pvVideo.classList.remove('hidden');
        }
        if (pvImg) pvImg.classList.add('hidden');
        if (pvIcon) pvIcon.classList.add('hidden');
      } else {
        if (pvImg) { pvImg.src = ev.target.result; pvImg.classList.remove('hidden'); }
        if (pvVideo) pvVideo.classList.add('hidden');
        if (pvIcon) pvIcon.classList.add('hidden');
      }
      if (ph) ph.classList.add('hidden');
    };
    rd.readAsDataURL(file);
  }

  if (up && f) {
    up.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG' && e.target.tagName !== 'VIDEO') f.click(); });
    ['dragenter','dragover'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.remove('drag'); }); });
    up.addEventListener('drop', function(e){ if (e.dataTransfer.files[0]) { f.files = e.dataTransfer.files; show(e.dataTransfer.files[0]); } });
    f.addEventListener('change', function(){ if (f.files[0]) show(f.files[0]); });
  }

  window.hRemove = function(){
    if (pvImg) { pvImg.src=''; pvImg.classList.add('hidden'); }
    if (pvVideo) { pvVideo.innerHTML=''; pvVideo.classList.add('hidden'); }
    if (ph) ph.classList.remove('hidden');
    if (f) f.value = '';
    if (pvIcon) pvIcon.classList.remove('hidden');
  };

  window.hUrlPrev = function(v){
    if (!v) return;
    if (tipe && tipe.value === 'video') {
      if (pvVideo) { pvVideo.innerHTML = '<source src="' + v + '" type="video/mp4">'; pvVideo.classList.remove('hidden'); }
      if (pvImg) pvImg.classList.add('hidden');
      if (pvIcon) pvIcon.classList.add('hidden');
    } else {
      if (pvImg) { pvImg.src = v; pvImg.classList.remove('hidden'); }
      if (pvVideo) pvVideo.classList.add('hidden');
      if (pvIcon) pvIcon.classList.add('hidden');
    }
    if (ph) ph.classList.add('hidden');
  };

  /* Trigger awal */
  if (judul && judul.value) judul.dispatchEvent(new Event('input'));
  if (subtitle && subtitle.value) subtitle.dispatchEvent(new Event('input'));
  if (badge && badge.value) badge.dispatchEvent(new Event('input'));
})();
</script>
<?php else: ?>

<!-- ===== DAFTAR SLIDE ===== -->
<div class="h2-stats">
  <div class="h2-st c-b"><span class="h2-st-ic"><i class="fa-solid fa-photo-film"></i></span><div><b><?= $stTotal ?></b><small>Total Slide</small></div></div>
  <div class="h2-st c-g"><span class="h2-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $stAktif ?></b><small>Aktif</small></div></div>
  <div class="h2-st c-p"><span class="h2-st-ic"><i class="fa-solid fa-film"></i></span><div><b><?= $stVideo ?></b><small>Video</small></div></div>
  <div class="h2-st c-a"><span class="h2-st-ic"><i class="fa-solid fa-image"></i></span><div><b><?= $stImg ?></b><small>Foto</small></div></div>
</div>

<?php
$slides = q($conn, "SELECT * FROM heroes ORDER BY urutan ASC, id ASC");
if (!$slides): ?>
<div class="h2-empty">
  <div class="eic"><i class="fa-solid fa-photo-film"></i></div>
  <h3>Belum ada slide hero</h3>
  <p>Tambahkan slide pertama Anda untuk ditampilkan pada beranda public.</p>
  <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Slide Pertama</a>
</div>
<?php else: ?>
<div class="h2-grid">
  <?php foreach ($slides as $s): ?>
  <div class="h2-slide <?= $s['aktif'] ? '' : 'inactive' ?>">
    <div class="h2-media">
      <span class="h2-badge <?= $s['tipe'] === 'video' ? 'vid' : 'img' ?>"><i class="fa-solid <?= $s['tipe'] === 'video' ? 'fa-film' : 'fa-image' ?>"></i> <?= strtoupper(e($s['tipe'])) ?></span>
      <span class="h2-status <?= $s['aktif'] ? 'on' : 'off' ?>"><?= $s['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      <?php if ($s['tipe'] === 'video' && $s['file']): ?>
      <video muted loop><source src="<?= e(media_url($s['file'])) ?>" type="video/mp4"></video>
      <?php elseif ($s['file']): ?>
      <img src="<?= e(media_url($s['file'])) ?>" alt="<?= e($s['judul']) ?>" loading="lazy">
      <?php else: ?>
      <i class="fa-solid fa-image"></i>
      <?php endif; ?>
    </div>
    <div class="h2-body">
      <h4><?= e($s['judul'] ?: '(Tanpa Judul)') ?></h4>
      <p><?= e($s['subtitle'] ?: '—') ?></p>
      <div class="h2-meta">
        <span><i class="fa-solid fa-tag"></i><?= e($s['badge'] ?: '—') ?></span>
        <span><i class="fa-solid fa-arrow-down-1-9"></i>Urutan #<?= (int)$s['urutan'] ?></span>
      </div>
      <div class="h2-foot">
        <span class="h2-ord"><i class="fa-solid fa-arrow-down-1-9"></i> Urutan #<?= (int)$s['urutan'] ?></span>
        <div class="h2-acts">
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button name="toggle" value="1" class="tg <?= $s['aktif'] ? '' : 'off' ?>" title="<?= $s['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $s['aktif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button></form>
          <a href="?edit=<?= (int)$s['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <button type="button" class="dng" onclick="hDel(<?= (int)$s['id'] ?>,'<?= e(addslashes($s['judul'] ?: 'Slide #' . $s['id'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>
<?php endif; ?>

<!-- Modal hapus -->
<div class="h2-modal" id="hDelModal">
  <div class="h2-modal-box">
    <div class="h2-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Slide Ini?</h3>
      <p id="hDelText">Slide akan dihapus permanen dan tidak dapat dikembalikan.</p>
    </div>
    <form method="post" class="h2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="hDelId" value="0">
      <button type="button" onclick="document.getElementById('hDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  window.hDel = function(id, judul){
    document.getElementById('hDelId').value = id;
    document.getElementById('hDelText').textContent = 'Slide "' + judul + '" akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('hDelModal').classList.add('open');
  };
  document.getElementById('hDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.h2-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>