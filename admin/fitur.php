<?php
$ACTIVE = 'fitur.php'; $TITLE = 'Fitur & Ikon';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE fitur SET icon=?,judul=?,deskripsi=?,urutan=?,aktif=? WHERE id=?");
            $stmt->bind_param('sssiii', $_POST['icon'], $_POST['judul'], $_POST['deskripsi'], $_POST['urutan'], $_POST['aktif'], $id);
            $msg = 'Fitur berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO fitur (icon,judul,deskripsi,urutan,aktif) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssii', $_POST['icon'], $_POST['judul'], $_POST['deskripsi'], $_POST['urutan'], $_POST['aktif']);
            $msg = 'Fitur baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM fitur WHERE id=" . (int)$_POST['id']);
        $msg = 'Fitur berhasil dihapus.';
    }
    if (isset($_POST['toggle']) && isset($_POST['id'])) {
        $conn->query("UPDATE fitur SET aktif = 1 - aktif WHERE id=" . (int)$_POST['id']);
        $msg = 'Status fitur diubah.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM fitur WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'icon'=>'fa-solid fa-atom','judul'=>'','deskripsi'=>'','urutan'=>1,'aktif'=>1];
}

$statAktif  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM fitur WHERE aktif=1")['c'] ?? 0);
$statNon    = (int)(q1($conn, "SELECT COUNT(*) AS c FROM fitur WHERE aktif=0")['c'] ?? 0);
$statTotal  = $statAktif + $statNon;
$nextOrder  = ((int)(q1($conn, "SELECT MAX(urutan) AS m FROM fitur")['m'] ?? 0)) + 1;

$presets = [
  ['icon'=>'fa-solid fa-atom','judul'=>'Riset & Inovasi','deskripsi'=>'Penelitian fisika terapan berkaliber internasional dengan publikasi bereputasi.'],
  ['icon'=>'fa-solid fa-flask','judul'=>'Laboratorium Modern','deskripsi'=>'Enam laboratorium lengkap: dasar, elektronika, optika, material, dan komputasi.'],
  ['icon'=>'fa-solid fa-user-graduate','judul'=>'Dosen Berkualitas','deskripsi'=>'Tenaga pengajar berpendidikan S2 dan S3 dari perguruan tinggi dalam dan luar negeri.'],
  ['icon'=>'fa-solid fa-satellite','judul'=>'Fasilitas Teknologi','deskripsi'=>'Instrumentasi presisi tinggi dan komputasi awan untuk simulasi fenomena fisika.'],
  ['icon'=>'fa-solid fa-book-open','judul'=>'Kurikulum Adaptif','deskripsi'=>'Kurikulum OBE yang selaras dengan kebutuhan industri dan perkembangan sains.'],
  ['icon'=>'fa-solid fa-handshake','judul'=>'Kerja Sama Mitra','deskripsi'=>'Kolaborasi riset dan magang dengan industri, rumah sakit, dan institusi global.'],
  ['icon'=>'fa-solid fa-microscope','judul'=>'Riset Material','deskripsi'=>'Kajian material maju berbasis nanoteknologi untuk aplikasi industri.'],
  ['icon'=>'fa-solid fa-rocket','judul'=>'Inovasi Teknologi','deskripsi'=>'Pengembangan inovasi teknologi tepat guna berbasis fisika terapan.'],
  ['icon'=>'fa-solid fa-brain','judul'=>'Kecerdasan Buatan','deskripsi'=>'Integrasi AI dan komputasi dalam penelitian dan pembelajaran fisika.'],
  ['icon'=>'fa-solid fa-globe','judul'=>'Jaringan Global','deskripsi'=>'Kolaborasi dengan universitas dan lembaga riset di berbagai negara.'],
];

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= FITUR ULTIMATE ADMIN ========= */
.a2-feat-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.a2-fst{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.a2-fst::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.a2-fst:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.a2-fst-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.a2-fst:hover .a2-fst-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.a2-fst b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.a2-fst small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.a2-fst.c-g{--c:#00b874}.a2-fst.c-r{--c:#e5484d}.a2-fst.c-b{--c:#0a66c2}

/* Layout editor */
.a2-feat-edit{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start}
.a2-feat-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.a2-feat-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.a2-feat-form .a2-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.a2-feat-form .a2-h .a2-ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.a2-feat-form .a2-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.a2-feat-form .a2-h small{color:#7b8aa3;font-size:12px}
.a2-fld{margin-bottom:18px}
.a2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.a2-fld label .req{color:#e5484d;margin-left:2px}
.a2-fld label i{color:var(--green)}
.a2-fld input,.a2-fld select,.a2-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.a2-fld input:focus,.a2-fld select:focus,.a2-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.a2-fld textarea{resize:vertical;line-height:1.7;min-height:110px}

/* Icon picker inline kaya */
.a2-icon-row{display:flex;gap:12px;align-items:center;padding:14px;background:linear-gradient(135deg,#f8fafd,#eef4fb);border:1.6px solid var(--line);border-radius:13px;transition:.25s}
.a2-icon-row:hover{border-color:rgba(0,184,116,.3)}
.a2-icon-pv{width:60px;height:60px;border-radius:15px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:24px;flex:none;box-shadow:0 10px 22px rgba(10,102,194,.25);transition:.35s}
.a2-icon-pv i{transition:.35s}
.a2-icon-pv:hover i{transform:rotate(-8deg) scale(1.1)}
.a2-icon-meta{flex:1;min-width:0}
.a2-icon-meta b{display:block;font-family:monospace;font-size:12px;color:var(--blue);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.a2-icon-meta small{color:#7b8aa3;font-size:11px;display:block}
.a2-icon-btn{border:0;background:var(--grad);color:#fff;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;transition:.25s;display:flex;align-items:center;gap:7px;box-shadow:0 6px 14px rgba(0,184,116,.25)}
.a2-icon-btn:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(0,184,116,.35)}
.a2-icon-clear{border:1.4px solid var(--line);background:#fff;color:#475569;padding:10px 14px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;transition:.2s}
.a2-icon-clear:hover{background:#fdecec;color:#b3261e;border-color:#f5c2c0}

/* Preview langsung */
.a2-pv-pane{position:sticky;top:90px}
.a2-pv-title{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:12px;padding:10px 14px;background:var(--gray);border-radius:10px;display:flex;align-items:center;gap:8px;font-weight:800;letter-spacing:1px}
.a2-pv-title i{color:var(--green)}
.a2-pv-title small{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0}
.a2-pv-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:32px 26px;box-shadow:0 20px 50px rgba(10,42,94,.14);position:relative;overflow:hidden;transition:.4s}
.a2-pv-card::after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 85% 12%,rgba(0,184,116,.1),transparent 55%);opacity:0;transition:.4s;pointer-events:none}
.a2-pv-card:hover{transform:translateY(-8px);border-color:rgba(0,184,116,.4)}
.a2-pv-card:hover::after{opacity:1}
.a2-pv-ic{width:64px;height:64px;border-radius:18px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:26px;margin-bottom:20px;box-shadow:0 12px 26px rgba(10,102,194,.3);transition:.45s}
.a2-pv-card:hover .a2-pv-ic{transform:rotateY(180deg)}
.a2-pv-card h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px;min-height:26px}
.a2-pv-card p{color:#68758a;font-size:14px;line-height:1.75;min-height:66px}
.a2-pv-card .a2-pv-status{position:absolute;top:14px;right:14px;padding:5px 12px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:1px}
.a2-pv-status.on{background:#e7f8f1;color:#0a7a52}
.a2-pv-status.off{background:#fdecec;color:#b3261e}

/* Preset */
.a2-preset{margin-top:20px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.a2-preset h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:8px}
.a2-preset h5::after{content:'';position:absolute;left:0;bottom:-2px;width:32px;height:2px;background:var(--grad)}
.a2-preset h5 i{color:var(--green)}
.a2-preset h5 small{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600}
.a2-preset-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.a2-preset-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.4px solid var(--line);border-radius:10px;cursor:pointer;transition:.25s;background:#fbfcfe;font-size:12px;font-weight:600;color:#475569}
.a2-preset-item:hover{border-color:var(--green);background:#fff;color:var(--blue-dark);transform:translateY(-2px)}
.a2-preset-item i{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:13px;flex:none;transition:.25s}
.a2-preset-item:hover i{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.a2-preset-item span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* Actions */
.a2-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.a2-actions .btn-primary{flex:1;min-width:180px;justify-content:center;padding:13px}
.a2-actions .btn-ghost-d{flex:1;min-width:120px;justify-content:center;padding:13px}

/* Daftar */
.a2-feat-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.a2-feat-head .a2-info{font-size:12.5px;color:#7b8aa3;font-weight:600}
.a2-feat-head .a2-info b{color:var(--blue-dark);font-family:var(--display)}

.a2-feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.a2-feat-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px;position:relative;transition:.3s;overflow:hidden;box-shadow:var(--shadow)}
.a2-feat-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.a2-feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.a2-feat-card.inactive{opacity:.65}
.a2-feat-card .a2-fc-top{display:flex;align-items:flex-start;gap:14px;margin-bottom:14px}
.a2-feat-card .a2-fc-ic{width:52px;height:52px;border-radius:14px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:22px;flex:none;box-shadow:0 8px 18px rgba(10,102,194,.25);transition:.3s}
.a2-feat-card:hover .a2-fc-ic{transform:rotate(-6deg) scale(1.05)}
.a2-feat-card .a2-fc-top h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;line-height:1.4;flex:1}
.a2-feat-card .a2-fc-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px}
.a2-feat-card .a2-fc-badge.on{background:#e7f8f1;color:#0a7a52}
.a2-feat-card .a2-fc-badge.off{background:#fdecec;color:#b3261e}
.a2-feat-card p{color:#68758a;font-size:13px;line-height:1.65;margin-bottom:14px;min-height:46px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.a2-feat-card .a2-fc-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line);font-size:11.5px;color:#9aa8bf;font-weight:700}
.a2-feat-card .a2-fc-foot i{color:var(--green);margin-right:5px}
.a2-feat-card .a2-fc-acts{display:flex;gap:5px}
.a2-feat-card .a2-fc-acts button,.a2-feat-card .a2-fc-acts a{width:32px;height:32px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:12px;cursor:pointer;transition:.2s;text-decoration:none}
.a2-feat-card .a2-fc-acts button:hover,.a2-feat-card .a2-fc-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.a2-feat-card .a2-fc-acts .dng:hover{background:#e5484d;border-color:#e5484d}
.a2-feat-card .a2-fc-acts .tg{background:#e7f8f1;border-color:#b5e8d4;color:#0a7a52}
.a2-feat-card .a2-fc-acts .tg.off{background:#fdecec;border-color:#f5c2c0;color:#b3261e}

/* Empty */
.a2-empty-feat{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.a2-empty-feat .a2-emp-ic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}

/* Modal */
.a2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.a2-modal.open{display:flex}
.a2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.a2-modal-body{padding:28px;text-align:center}
.a2-modal-body .a2-m-ic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.a2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.a2-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.a2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.a2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.a2-modal-foot .a2-m-del{background:#b3261e;color:#fff;border-color:#b3261e}
.a2-modal-foot .a2-m-del:hover{background:#8b1d16}
.a2-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.a2-feat-edit{grid-template-columns:1fr}.a2-pv-pane{position:static}.a2-feat-grid{grid-template-columns:1fr 1fr}.a2-preset-grid{grid-template-columns:1fr}}
@media(max-width:700px){.a2-feat-grid{grid-template-columns:1fr}.a2-feat-stats{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<!-- EDITOR -->
<div class="a2-feat-edit">
  <form method="post" class="a2-feat-form" id="fFitur">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="a2-h">
      <span class="a2-ic"><i class="fa-solid fa-shapes"></i></span>
      <div>
        <b><?= $edit['id'] ? 'Edit Fitur' : 'Tambah Fitur Baru' ?></b>
        <small><?= $edit['id'] ? 'Memperbarui fitur #' . $edit['id'] : 'Buat kartu keunggulan baru untuk beranda' ?></small>
      </div>
    </div>

    <div class="a2-fld">
      <label><i class="fa-solid fa-icons"></i> Ikon Kartu <span class="req">*</span></label>
      <input type="hidden" name="icon" id="fIcon" value="<?= e($edit['icon']) ?>">
      <div class="a2-icon-row">
        <span class="a2-icon-pv"><i id="iconPv" class="<?= e($edit['icon']) ?>"></i></span>
        <div class="a2-icon-meta">
          <b id="iconLabel"><?= e($edit['icon']) ?></b>
          <small>Ikon Font Awesome 6 • <?= $edit['id'] ? 'Klik ganti untuk mengubah' : 'Klik pilih untuk memilih ikon' ?></small>
        </div>
        <button type="button" class="a2-icon-btn" data-iconpicker="#fIcon" data-previewicon="#iconPv"><i class="fa-solid fa-wand-magic-sparkles"></i> Pilih Ikon</button>
      </div>
    </div>

    <div class="a2-fld">
      <label><i class="fa-solid fa-heading"></i> Judul Fitur <span class="req">*</span> <span style="margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0;text-transform:none" id="titleCount">0 / 60 karakter</span></label>
      <input name="judul" id="fJudul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Riset & Inovasi" required maxlength="60">
    </div>

    <div class="a2-fld">
      <label><i class="fa-solid fa-align-left"></i> Deskripsi <span class="req">*</span> <span style="margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0;text-transform:none" id="descCount">0 / 200 karakter</span></label>
      <textarea name="deskripsi" id="fDeskripsi" maxlength="200" placeholder="Deskripsi singkat mengenai keunggulan fitur ini..."><?= e($edit['deskripsi']) ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="a2-fld">
        <label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan Tampil</label>
        <input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1">
      </div>
      <div class="a2-fld">
        <label><i class="fa-solid fa-toggle-on"></i> Status</label>
        <select name="aktif">
          <option value="1" <?= $edit['aktif'] ? 'selected' : '' ?>>✓ Aktif (Tampil di beranda)</option>
          <option value="0" <?= !$edit['aktif'] ? 'selected' : '' ?>>⏸ Nonaktif</option>
        </select>
      </div>
    </div>

    <div class="a2-actions">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Fitur' : 'Tambah Fitur' ?></button>
      <a class="btn-ghost-d" href="<?= url('admin/fitur.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
    </div>
  </form>

  <!-- PREVIEW & PRESET -->
  <div class="a2-pv-pane">
    <div class="a2-pv-title"><i class="fa-solid fa-eye"></i> PRATINJAU LANGSUNG <small>Tampilan di beranda</small></div>
    <div class="a2-pv-card">
      <span class="a2-pv-status <?= $edit['aktif'] ? 'on' : 'off' ?>" id="pvStatus"><?= $edit['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      <span class="a2-pv-ic"><i id="pvIcon" class="<?= e($edit['icon']) ?>"></i></span>
      <h3 id="pvJudul"><?= e($edit['judul'] ?: 'Judul Fitur') ?></h3>
      <p id="pvDesk"><?= e($edit['deskripsi'] ?: 'Deskripsi fitur akan tampil di sini...') ?></p>
    </div>

    <?php if (!$edit['id']): ?>
    <div class="a2-preset">
      <h5><i class="fa-solid fa-wand-magic-sparkles"></i> Template Preset <small>Klik untuk isi otomatis</small></h5>
      <div class="a2-preset-grid">
        <?php foreach ($presets as $p): ?>
        <button type="button" class="a2-preset-item" onclick="usePreset('<?= e($p['icon']) ?>','<?= e(addslashes($p['judul'])) ?>','<?= e(addslashes($p['deskripsi'])) ?>')">
          <i class="fa-solid <?= e($p['icon']) ?>"></i>
          <span><?= e($p['judul']) ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  /* Live preview */
  var judul = document.getElementById('fJudul');
  var desk  = document.getElementById('fDeskripsi');
  var pvJ   = document.getElementById('pvJudul');
  var pvD   = document.getElementById('pvDesk');
  var pvI   = document.getElementById('pvIcon');
  var pvSt  = document.getElementById('pvStatus');
  var iLabel = document.getElementById('iconLabel');
  var fIcon = document.getElementById('fIcon');

  if (judul && pvJ) judul.addEventListener('input', function(){
    pvJ.textContent = this.value || 'Judul Fitur';
    var tc = document.getElementById('titleCount');
    if (tc) { tc.textContent = this.value.length + ' / 60 karakter'; tc.style.color = this.value.length > 50 ? '#f59e0b' : '#9aa8bf'; }
  });
  if (desk && pvD) desk.addEventListener('input', function(){
    pvD.textContent = this.value || 'Deskripsi fitur akan tampil di sini...';
    var dc = document.getElementById('descCount');
    if (dc) { dc.textContent = this.value.length + ' / 200 karakter'; dc.style.color = this.value.length > 170 ? '#f59e0b' : '#9aa8bf'; }
  });

  /* Icon picker integration - sync dengan admin.js */
  var syncIcon = function(){
    if (!fIcon || !iLabel || !pvI) return;
    iLabel.textContent = fIcon.value || '(belum dipilih)';
    pvI.className = fIcon.value || '';
  };
  var obs = new MutationObserver(syncIcon);
  if (fIcon) obs.observe(fIcon, { attributes: true, attributeFilter: ['value'] });

  /* Status preview */
  var statSel = document.querySelector('select[name=aktif]');
  if (statSel && pvSt) statSel.addEventListener('change', function(){
    pvSt.textContent = this.value === '1' ? 'AKTIF' : 'NONAKTIF';
    pvSt.className = 'a2-pv-status ' + (this.value === '1' ? 'on' : 'off');
  });

  /* Trigger awal */
  if (judul && judul.value) judul.dispatchEvent(new Event('input'));
  if (desk && desk.value) desk.dispatchEvent(new Event('input'));
  syncIcon();

  /* Preset */
  window.usePreset = function(icon, judul, desk){
    if (fIcon) fIcon.value = icon;
    var j = document.getElementById('fJudul');
    var d = document.getElementById('fDeskripsi');
    if (j) { j.value = judul; j.dispatchEvent(new Event('input')); }
    if (d) { d.value = desk; d.dispatchEvent(new Event('input')); }
    syncIcon();
    if (window.a2Toast) a2Toast('Template "' + judul + '" diterapkan!');
  };
})();
</script>
<?php else: ?>

<!-- DAFTAR FITUR -->
<div class="a2-feat-stats">
  <div class="a2-fst c-b"><span class="a2-fst-ic"><i class="fa-solid fa-shapes"></i></span><div><b><?= $statTotal ?></b><small>Total Fitur</small></div></div>
  <div class="a2-fst c-g"><span class="a2-fst-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $statAktif ?></b><small>Aktif</small></div></div>
  <div class="a2-fst c-r"><span class="a2-fst-ic"><i class="fa-solid fa-pause"></i></span><div><b><?= $statNon ?></b><small>Nonaktif</small></div></div>
</div>

<div class="panel">
  <div class="a2-feat-head">
    <h3 style="margin:0"><i class="fa-solid fa-shapes" style="color:var(--green);margin-right:8px"></i>Kartu Keunggulan Beranda</h3>
    <div style="display:flex;align-items:center;gap:10px">
      <span class="a2-info">Menampilkan <b><?= $statTotal ?></b> fitur</span>
      <a href="?edit=0" class="btn-small"><i class="fa-solid fa-plus"></i> Tambah Fitur</a>
    </div>
  </div>

  <?php
  $list = q($conn, "SELECT * FROM fitur ORDER BY urutan ASC, id ASC");
  if (!$list): ?>
  <div class="a2-empty-feat">
    <div class="a2-emp-ic"><i class="fa-solid fa-shapes"></i></div>
    <h3>Belum ada fitur</h3>
    <p>Tambahkan kartu keunggulan pertama Anda untuk ditampilkan pada beranda public.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Fitur Pertama</a>
  </div>
  <?php else: ?>
  <div class="a2-feat-grid">
    <?php foreach ($list as $f): ?>
    <div class="a2-feat-card <?= $f['aktif'] ? '' : 'inactive' ?>">
      <div class="a2-fc-top">
        <span class="a2-fc-ic"><i class="<?= e($f['icon']) ?>"></i></span>
        <h4><?= e($f['judul']) ?></h4>
        <span class="a2-fc-badge <?= $f['aktif'] ? 'on' : 'off' ?>"><?= $f['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      </div>
      <p><?= e($f['deskripsi']) ?></p>
      <div class="a2-fc-foot">
        <span><i class="fa-solid fa-arrow-down-1-9"></i> Urutan: <?= (int)$f['urutan'] ?></span>
        <div class="a2-fc-acts">
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><button type="submit" name="toggle" value="1" class="tg <?= $f['aktif'] ? '' : 'off' ?>" title="<?= $f['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $f['aktif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button></form>
          <a href="?edit=<?= (int)$f['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <button type="button" onclick="confirmDelFeat(<?= (int)$f['id'] ?>,'<?= e(addslashes($f['judul'])) ?>')" title="Hapus" class="dng"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Floating add -->
<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>

<!-- Modal konfirmasi hapus -->
<div class="a2-modal" id="delFeatModal">
  <div class="a2-modal-box">
    <div class="a2-modal-body">
      <div class="a2-m-ic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Fitur Ini?</h3>
      <p id="delFeatText">Fitur akan dihapus permanen dan tidak dapat dikembalikan.</p>
    </div>
    <form method="post" class="a2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="delFeatId" value="0">
      <button type="button" onclick="document.getElementById('delFeatModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="a2-m-del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  window.confirmDelFeat = function(id, judul){
    document.getElementById('delFeatId').value = id;
    document.getElementById('delFeatText').textContent = 'Fitur "' + judul + '" akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('delFeatModal').classList.add('open');
  };
  document.getElementById('delFeatModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.a2-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>