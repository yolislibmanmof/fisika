<?php
$ACTIVE = 'galeri.php'; $TITLE = 'Manajemen Galeri';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $file = upload_file('file', 'galeri') ?? $_POST['file_lama'];
        if (!$file) $file = $_POST['file_url'] ?: $file;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE galeri SET judul=?,kategori=?,file=?,tipe=?,tanggal=?,urutan=? WHERE id=?");
            $stmt->bind_param('sssssii', $_POST['judul'], $_POST['kategori'], $file, $_POST['tipe'], $_POST['tanggal'], $_POST['urutan'], $id);
            $msg = 'Item galeri berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO galeri (judul,kategori,file,tipe,tanggal,urutan) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('sssssi', $_POST['judul'], $_POST['kategori'], $file, $_POST['tipe'], $_POST['tanggal'], $_POST['urutan']);
            $msg = 'Item galeri baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) { $conn->query("DELETE FROM galeri WHERE id=" . (int)$_POST['id']); $msg = 'Item galeri dihapus.'; }
    if (isset($_POST['bulk']) && $_POST['bulk'] === 'delete' && is_array($_POST['ids'] ?? null)) {
        $ids = array_map('intval', $_POST['ids']);
        if ($ids) { $conn->query("DELETE FROM galeri WHERE id IN (" . implode(',', $ids) . ")"); $msg = count($ids) . ' item galeri dihapus.'; }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM galeri WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'judul'=>'','kategori'=>'Dokumentasi','file'=>'','tipe'=>'image','tanggal'=>date('Y-m-d'),'urutan'=>1];
}

/* Statistik */
$stTotal = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri")['c'] ?? 0);
$stFoto  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri WHERE tipe='image'")['c'] ?? 0);
$stVideo = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri WHERE tipe='video'")['c'] ?? 0);
$kats    = q($conn, "SELECT kategori, COUNT(*) AS c FROM galeri GROUP BY kategori ORDER BY c DESC");

/* Filter */
$fQ    = trim($_GET['q'] ?? '');
$fKat  = trim($_GET['kat'] ?? '');
$fTipe = trim($_GET['tipe'] ?? '');
$where = " WHERE 1=1"; $params = []; $types = '';
if ($fQ !== '')    { $where .= " AND (judul LIKE ? OR kategori LIKE ?)"; $like = "%$fQ%"; $params = array_merge($params, [$like,$like]); $types .= 'ss'; }
if ($fKat !== '')  { $where .= " AND kategori=?"; $params[] = $fKat; $types .= 's'; }
if ($fTipe !== '') { $where .= " AND tipe=?"; $params[] = $fTipe; $types .= 's'; }
$view = ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid';

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= GALERI ULTIMATE ADMIN ========= */
.g2-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.g2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.g2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.g2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.g2-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.g2-st:hover .g2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.g2-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.g2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.g2-st.c-b{--c:#0a66c2}.g2-st.c-g{--c:#00b874}.g2-st.c-p{--c:#8b5cf6}.g2-st.c-a{--c:#f59e0b}

.g2-toolbar{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.g2-search{position:relative;flex:1;min-width:200px}
.g2-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9aa8bf;font-size:13px}
.g2-search input{width:100%;padding:10px 13px 10px 38px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;outline:0;transition:.25s}
.g2-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1)}
.g2-select{padding:10px 12px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;background:#fbfcfe;outline:0;cursor:pointer}
.g2-vbtns{display:flex;background:var(--gray);border-radius:9px;padding:3px;border:1px solid var(--line)}
.g2-vbtns button{border:0;background:transparent;padding:7px 12px;border-radius:7px;cursor:pointer;color:#7b8aa3;font-size:11.5px;font-weight:700;display:flex;align-items:center;gap:6px;transition:.25s}
.g2-vbtns button.on{background:#fff;color:var(--blue);box-shadow:0 2px 6px rgba(0,0,0,.06)}
.g2-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.g2-chips a{padding:7px 15px;border-radius:99px;border:1.4px solid var(--line);background:#fff;font-size:12px;font-weight:700;color:#475569;transition:.25s;text-decoration:none}
.g2-chips a:hover{border-color:var(--green);color:var(--green);transform:translateY(-2px)}
.g2-chips a.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 6px 14px rgba(0,184,116,.25)}

.g2-bulk{position:sticky;top:67px;z-index:40;background:linear-gradient(135deg,#0a2a5e,#071a39);color:#fff;border-radius:14px;padding:12px 18px;margin-bottom:16px;display:none;align-items:center;gap:12px;box-shadow:0 10px 30px rgba(10,42,94,.25)}
.g2-bulk.show{display:flex;animation:a2zin .3s}
.g2-bulk b{font-family:var(--display)}
.g2-bulk .bb{border:0;background:rgba(255,255,255,.12);color:#fff;padding:8px 14px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.25s}
.g2-bulk .bb:hover{background:#fff;color:var(--blue-dark)}
.g2-bulk .bb.danger{background:#e5484d}
.g2-bulk .bb.danger:hover{background:#fff;color:#b3261e}
.g2-bulk .x{margin-left:auto;background:transparent;border:0;color:#fff;font-size:18px;cursor:pointer}

/* Masonry grid */
.g2-masonry{column-count:4;column-gap:14px}
.g2-item{break-inside:avoid;margin-bottom:14px;position:relative;border-radius:14px;overflow:hidden;background:#04101f;box-shadow:var(--shadow);transition:.3s;cursor:pointer}
.g2-item:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(10,42,94,.22)}
.g2-item.selected{outline:3px solid var(--green)}
.g2-item img{width:100%;display:block;transition:.5s}
.g2-item:hover img{transform:scale(1.05)}
.g2-item .g2-vph{aspect-ratio:16/10;display:grid;place-items:center;color:#7ef0c4;font-size:38px;background:linear-gradient(135deg,#0a2a5e,#071a39)}
.g2-item .g2-ck{position:absolute;top:10px;left:10px;width:24px;height:24px;border-radius:7px;background:rgba(255,255,255,.92);display:grid;place-items:center;z-index:3;border:2px solid transparent}
.g2-item .g2-ck input{margin:0;cursor:pointer}
.g2-item.selected .g2-ck{background:var(--grad);border-color:#fff}
.g2-item .g2-tbadge{position:absolute;top:10px;right:10px;background:rgba(7,26,57,.85);backdrop-filter:blur(8px);color:#fff;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1px;z-index:2}
.g2-item .g2-tbadge i{color:#7ef0c4;margin-right:4px}
.g2-item .g2-over{position:absolute;inset:0;background:linear-gradient(180deg,transparent 35%,rgba(7,26,57,.95));display:flex;flex-direction:column;justify-content:flex-end;padding:16px;opacity:0;transition:.35s}
.g2-item:hover .g2-over{opacity:1}
.g2-item .g2-over p{color:#fff;font-family:var(--display);font-size:13.5px;font-weight:700;line-height:1.4;margin-bottom:4px}
.g2-item .g2-over small{color:#7ef0c4;font-size:10.5px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
.g2-item .g2-acts{position:absolute;top:10px;left:50%;transform:translateX(-50%) translateY(-8px);display:flex;gap:6px;opacity:0;transition:.35s;z-index:4}
.g2-item:hover .g2-acts{opacity:1;transform:translateX(-50%)}
.g2-item .g2-acts button,.g2-item .g2-acts a{width:32px;height:32px;border-radius:9px;border:0;background:rgba(255,255,255,.16);backdrop-filter:blur(8px);color:#fff;display:grid;place-items:center;font-size:12px;cursor:pointer;transition:.2s;text-decoration:none}
.g2-item .g2-acts button:hover,.g2-item .g2-acts a:hover{background:var(--grad);transform:scale(1.1)}
.g2-item .g2-acts .dng:hover{background:#e5484d}

/* List view */
.g2-list{display:flex;flex-direction:column;gap:10px}
.g2-litem{background:#fff;border:1px solid var(--line);border-radius:13px;padding:12px 16px;display:grid;grid-template-columns:auto 70px 1fr auto auto auto auto;gap:14px;align-items:center;transition:.25s}
.g2-litem:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.g2-litem .li-img{width:70px;height:52px;border-radius:9px;overflow:hidden;background:var(--gray);display:grid;place-items:center;color:#cfd8e3}
.g2-litem .li-img img{width:100%;height:100%;object-fit:cover}
.g2-litem h4{font-family:var(--display);font-size:13.5px;color:var(--blue-dark);margin-bottom:3px}
.g2-litem small{color:#7b8aa3;font-size:11px;font-weight:600}
.g2-litem small i{color:var(--green);margin-right:4px}

/* Editor */
.g2-edit{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start}
.g2-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.g2-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.g2-form .g2-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.g2-form .g2-h .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.g2-form .g2-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.g2-form .g2-h small{color:#7b8aa3;font-size:12px}
.g2-fld{margin-bottom:16px}
.g2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.g2-fld label .req{color:#e5484d}
.g2-fld label i{color:var(--green)}
.g2-fld input,.g2-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.g2-fld input:focus,.g2-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.g2-up{border:2px dashed var(--line);border-radius:14px;padding:16px;background:#fbfcfe;transition:.3s;position:relative}
.g2-up:hover,.g2-up.drag{border-color:var(--green);background:#fff}
.g2-up img{width:100%;max-height:240px;object-fit:cover;border-radius:10px;display:block;margin-bottom:10px}
.g2-up .ph{text-align:center;padding:26px 10px;color:#9aa8bf}
.g2-up .ph i{font-size:30px;color:var(--blue);margin-bottom:10px;display:block}
.g2-up .ph b{display:block;color:var(--blue-dark);margin-bottom:4px}
.g2-up .ph small{font-size:11.5px}
.g2-upbtns{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.g2-upbtns button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:8px 12px;border-radius:8px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.g2-upbtns button:hover{background:var(--grad);color:#fff;border-color:transparent}
.g2-upbtns .dng:hover{background:#e5484d;border-color:#e5484d}
.g2-or{display:flex;align-items:center;gap:10px;margin:14px 0;color:#9aa8bf;font-size:11px;font-weight:800;letter-spacing:1.4px}
.g2-or::before,.g2-or::after{content:'';flex:1;height:1px;background:var(--line)}

/* Preview pane */
.g2-pvpane{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.g2-pvtitle{font-family:var(--display);font-size:12px;color:var(--blue-dark);padding:10px 14px;background:var(--gray);border-radius:10px;display:flex;align-items:center;gap:8px;font-weight:800;letter-spacing:1px}
.g2-pvtitle i{color:var(--green)}
.g2-pvtitle small{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0}
.g2-pvcard{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(10,42,94,.14);transition:.35s}
.g2-pvcard:hover{transform:translateY(-5px)}
.g2-pvcard .g2-pvmedia{position:relative;height:220px;background:linear-gradient(135deg,#0a2a5e,#071a39);display:grid;place-items:center;color:#7ef0c4;font-size:44px;overflow:hidden}
.g2-pvcard .g2-pvmedia img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.g2-pvcard .g2-pvbody{padding:18px}
.g2-pvcard .g2-pvbody h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:6px;min-height:20px}
.g2-pvcard .g2-pvbody .meta{display:flex;gap:10px;font-size:11px;color:#7b8aa3;font-weight:700;flex-wrap:wrap}
.g2-pvcard .g2-pvbody .meta i{color:var(--green);margin-right:4px}
.g2-actions{display:flex;gap:10px;flex-wrap:wrap}
.g2-actions .btn-primary{flex:1;min-width:160px;justify-content:center;padding:13px}
.g2-actions .btn-ghost-d{flex:1;min-width:110px;justify-content:center;padding:13px}

/* Empty & modal */
.g2-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.g2-empty .eic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}
.g2-empty h3{font-family:var(--display);color:var(--blue-dark);margin-bottom:8px}
.g2-empty p{color:#7b8aa3;font-size:13.5px;margin-bottom:18px}
.g2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.g2-modal.open{display:flex}
.g2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.g2-modal-body{padding:28px;text-align:center}
.g2-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.g2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.g2-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.g2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.g2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.g2-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.g2-modal-foot button:hover{transform:translateY(-2px)}
.g2-lightbox{position:fixed;inset:0;background:rgba(4,16,31,.94);z-index:1500;display:none;align-items:center;justify-content:center;padding:4vh}
.g2-lightbox.open{display:flex}
.g2-lightbox img{max-height:86vh;max-width:88vw;border-radius:14px;box-shadow:0 30px 80px rgba(0,0,0,.6);animation:a2zin .4s}
.g2-lightbox button{position:absolute;top:26px;right:30px;width:48px;height:48px;border-radius:50%;border:0;background:#fff;color:#0b0f14;font-size:18px;cursor:pointer}

@media(max-width:1100px){.g2-edit{grid-template-columns:1fr}.g2-pvpane{position:static}.g2-masonry{column-count:3}}
@media(max-width:860px){.g2-masonry{column-count:2}.g2-stats{grid-template-columns:1fr 1fr}.g2-litem{grid-template-columns:auto 1fr auto;gap:10px}.g2-litem .li-img{display:none}}
@media(max-width:560px){.g2-masonry{column-count:1}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<!-- ===== EDITOR ===== -->
<div class="g2-edit">
  <form method="post" enctype="multipart/form-data" class="g2-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <input type="hidden" name="file_lama" value="<?= e($edit['file']) ?>">
    <div class="g2-h">
      <span class="ic"><i class="fa-solid fa-images"></i></span>
      <div>
        <b><?= $edit['id'] ? 'Edit Item Galeri' : 'Tambah Item Galeri' ?></b>
        <small><?= $edit['id'] ? 'Memperbarui item #' . $edit['id'] : 'Unggah dokumentasi baru untuk galeri public' ?></small>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="g2-fld">
        <label><i class="fa-solid fa-heading"></i> Judul <span class="req">*</span></label>
        <input name="judul" id="gJudul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Praktikum Optika 2026" required>
      </div>
      <div class="g2-fld">
        <label><i class="fa-solid fa-tag"></i> Kategori</label>
        <input name="kategori" id="gKategori" value="<?= e($edit['kategori']) ?>" list="gKatList">
        <datalist id="gKatList">
          <?php foreach ($kats as $k): ?><option value="<?= e($k['kategori']) ?>"><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</option><?php endforeach; ?>
        </datalist>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
      <div class="g2-fld">
        <label><i class="fa-solid fa-photo-film"></i> Tipe</label>
        <select name="tipe" id="gTipe">
          <option value="image" <?= $edit['tipe'] === 'image' ? 'selected' : '' ?>>📷 Foto</option>
          <option value="video" <?= $edit['tipe'] === 'video' ? 'selected' : '' ?>>🎬 Video</option>
        </select>
      </div>
      <div class="g2-fld">
        <label><i class="fa-regular fa-calendar-days"></i> Tanggal</label>
        <input type="date" name="tanggal" value="<?= e($edit['tanggal']) ?>">
      </div>
      <div class="g2-fld">
        <label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label>
        <input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1">
      </div>
    </div>

    <div class="g2-fld">
      <label><i class="fa-solid fa-cloud-arrow-up"></i> Unggah File</label>
      <div class="g2-up" id="gUp">
        <img id="pvGal" class="<?= $edit['file'] && $edit['tipe'] === 'image' ? '' : 'hidden' ?>" src="<?= $edit['file'] ? e(media_url($edit['file'])) : '' ?>" alt="">
        <div class="ph <?= $edit['file'] ? 'hidden' : '' ?>" id="gPh">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <b>Klik atau seret file ke sini</b>
          <small>Foto: JPG/PNG/WebP • Video: MP4/WebM • Maks 20 MB</small>
        </div>
        <input type="file" name="file" id="gFile" accept="image/*,video/mp4,video/webm" style="display:none">
      </div>
      <div class="g2-upbtns">
        <button type="button" onclick="document.getElementById('gFile').click()"><i class="fa-solid fa-upload"></i> Pilih File</button>
        <button type="button" class="dng" onclick="gRemove()"><i class="fa-solid fa-trash"></i> Hapus File</button>
      </div>
      <div class="g2-or">ATAU GUNAKAN URL</div>
      <input name="file_url" id="gUrl" placeholder="https://contoh.com/gambar.jpg" oninput="gUrlPrev(this.value)">
    </div>

    <div class="g2-actions">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Item' : 'Simpan Item' ?></button>
      <a class="btn-ghost-d" href="<?= url('admin/galeri.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
    </div>
  </form>

  <!-- PREVIEW -->
  <div class="g2-pvpane">
    <div class="g2-pvtitle"><i class="fa-solid fa-eye"></i> PRATINJAU ITEM <small>Tampilan galeri public</small></div>
    <div class="g2-pvcard">
      <div class="g2-pvmedia" id="pvMedia">
        <i class="fa-solid fa-image" id="pvPhIcon"></i>
        <img id="pvImg" class="<?= $edit['file'] ? '' : 'hidden' ?>" src="<?= $edit['file'] ? e(media_url($edit['file'])) : '' ?>" alt="">
      </div>
      <div class="g2-pvbody">
        <h4 id="pvJudul"><?= e($edit['judul'] ?: 'Judul Dokumentasi') ?></h4>
        <div class="meta">
          <span><i class="fa-solid fa-tag"></i><span id="pvKat"><?= e($edit['kategori'] ?: 'Kategori') ?></span></span>
          <span><i class="fa-solid fa-photo-film"></i><span id="pvTipe"><?= strtoupper(e($edit['tipe'])) ?></span></span>
          <span><i class="fa-regular fa-calendar-days"></i><?= date('d M Y', strtotime($edit['tanggal'])) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var j = document.getElementById('gJudul'), k = document.getElementById('gKategori'), t = document.getElementById('gTipe');
  var pvJ = document.getElementById('pvJudul'), pvK = document.getElementById('pvKat'), pvT = document.getElementById('pvTipe');
  if (j) j.addEventListener('input', function(){ pvJ.textContent = this.value || 'Judul Dokumentasi'; });
  if (k) k.addEventListener('input', function(){ pvK.textContent = this.value || 'Kategori'; });
  if (t) t.addEventListener('change', function(){
    pvT.textContent = this.value.toUpperCase();
    var icon = document.getElementById('pvPhIcon');
    if (icon) icon.className = 'fa-solid ' + (this.value === 'video' ? 'fa-film' : 'fa-image');
  });

  /* Upload & drag-drop */
  var up = document.getElementById('gUp'), f = document.getElementById('gFile'), pv = document.getElementById('pvGal'), ph = document.getElementById('gPh'), pvImg = document.getElementById('pvImg'), pvIcon = document.getElementById('pvPhIcon');
  function show(file){
    var rd = new FileReader();
    rd.onload = function(ev){
      if (t && t.value === 'video') { pvImg.classList.add('hidden'); pvImg.src=''; if(pvIcon){pvIcon.className='fa-solid fa-film'; pvIcon.parentElement.classList.remove('hidden');} }
      else { pv.src = ev.target.result; pv.classList.remove('hidden'); if(ph) ph.classList.add('hidden'); if(pvImg){pvImg.src=ev.target.result; pvImg.classList.remove('hidden');} if(pvIcon) pvIcon.classList.add('hidden'); }
    };
    rd.readAsDataURL(file);
  }
  if (up && f) {
    up.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG') f.click(); });
    ['dragenter','dragover'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.remove('drag'); }); });
    up.addEventListener('drop', function(e){ if (e.dataTransfer.files[0]) { f.files = e.dataTransfer.files; show(e.dataTransfer.files[0]); } });
    f.addEventListener('change', function(){ if (f.files[0]) show(f.files[0]); });
  }
  window.gRemove = function(){
    if (pv) { pv.src=''; pv.classList.add('hidden'); }
    if (ph) ph.classList.remove('hidden');
    if (f) f.value = '';
    if (pvImg) { pvImg.src=''; pvImg.classList.add('hidden'); }
    if (pvIcon) pvIcon.classList.remove('hidden');
  };
  window.gUrlPrev = function(v){
    if (!v) return;
    if (t && t.value === 'video') { if(pvImg) pvImg.classList.add('hidden'); if(pvIcon) pvIcon.className='fa-solid fa-film'; }
    else { if (pvImg) { pvImg.src = v; pvImg.classList.remove('hidden'); } if (pvIcon) pvIcon.classList.add('hidden'); if (ph) ph.classList.add('hidden'); if (pv) { pv.src = v; pv.classList.remove('hidden'); } }
  };
})();
</script>
<?php else: ?>

<!-- ===== DAFTAR ===== -->
<div class="g2-stats">
  <div class="g2-st c-b"><span class="g2-st-ic"><i class="fa-solid fa-images"></i></span><div><b><?= $stTotal ?></b><small>Total Item</small></div></div>
  <div class="g2-st c-g"><span class="g2-st-ic"><i class="fa-solid fa-image"></i></span><div><b><?= $stFoto ?></b><small>Foto</small></div></div>
  <div class="g2-st c-p"><span class="g2-st-ic"><i class="fa-solid fa-film"></i></span><div><b><?= $stVideo ?></b><small>Video</small></div></div>
  <div class="g2-st c-a"><span class="g2-st-ic"><i class="fa-solid fa-layer-group"></i></span><div><b><?= count($kats) ?></b><small>Kategori</small></div></div>
</div>

<div class="g2-toolbar">
  <form method="get" class="g2-search" style="display:flex;gap:8px">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($fQ) ?>" placeholder="Cari judul atau kategori...">
  </form>
  <select class="g2-select" onchange="location.href='galeri.php?tipe='+this.value+'<?= $fQ ? '&q='.urlencode($fQ) : '' ?><?= $fKat ? '&kat='.urlencode($fKat) : '' ?>'">
    <option value="">Semua Tipe</option>
    <option value="image" <?= $fTipe === 'image' ? 'selected' : '' ?>>Foto</option>
    <option value="video" <?= $fTipe === 'video' ? 'selected' : '' ?>>Video</option>
  </select>
  <div class="g2-vbtns">
    <button class="<?= $view === 'grid' ? 'on' : '' ?>" onclick="location.href='galeri.php?view=grid'"><i class="fa-solid fa-table-cells-large"></i> Masonry</button>
    <button class="<?= $view === 'list' ? 'on' : '' ?>" onclick="location.href='galeri.php?view=list'"><i class="fa-solid fa-list"></i> List</button>
  </div>
  <span style="margin-left:auto;font-size:12.5px;color:#7b8aa3;font-weight:600">Menampilkan <b style="color:var(--blue-dark);font-family:var(--display)"><?= count(qp($conn, "SELECT id FROM galeri$where", $types, $params)) ?></b> item</span>
</div>

<div class="g2-chips">
  <a class="<?= $fKat === '' ? 'on' : '' ?>" href="galeri.php">Semua</a>
  <?php foreach ($kats as $k): ?>
  <a class="<?= $fKat === $k['kategori'] ? 'on' : '' ?>" href="?kat=<?= urlencode($k['kategori']) ?>"><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</a>
  <?php endforeach; ?>
</div>

<form method="post" id="gBulkForm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="g2-bulk" id="gBulkBar">
    <b><span id="gBulkCount">0</span> item dipilih</b>
    <button type="submit" name="bulk" value="delete" class="bb danger" onclick="return confirm('Hapus semua item yang dipilih?')"><i class="fa-solid fa-trash"></i> Hapus Terpilih</button>
    <button type="button" class="x" onclick="gClearBulk()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <?php
  $list = qp($conn, "SELECT * FROM galeri$where ORDER BY urutan ASC, tanggal DESC", $types, $params);
  if (!$list): ?>
  <div class="g2-empty">
    <div class="eic"><i class="fa-solid fa-images"></i></div>
    <h3>Galeri masih kosong</h3>
    <p><?= $fQ || $fKat || $fTipe ? 'Tidak ada item yang cocok dengan filter Anda.' : 'Unggah dokumentasi pertama Anda untuk ditampilkan pada galeri public.' ?></p>
    <?php if ($fQ || $fKat || $fTipe): ?><a href="galeri.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Reset Filter</a>
    <?php else: ?><a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Item Pertama</a><?php endif; ?>
  </div>
  <?php else: ?>

  <?php if ($view === 'grid'): ?>
  <div class="g2-masonry">
    <?php foreach ($list as $g): ?>
    <div class="g2-item" data-id="<?= (int)$g['id'] ?>">
      <label class="g2-ck"><input type="checkbox" name="ids[]" value="<?= (int)$g['id'] ?>" onchange="gBulk()"></label>
      <span class="g2-tbadge"><i class="fa-solid <?= $g['tipe'] === 'video' ? 'fa-film' : 'fa-image' ?>"></i><?= strtoupper(e($g['tipe'])) ?></span>
      <div class="g2-acts">
        <?php if ($g['tipe'] === 'image' && $g['file']): ?><button type="button" onclick="gLb('<?= e(media_url($g['file'])) ?>')" title="Lihat"><i class="fa-solid fa-magnifying-glass-plus"></i></button><?php endif; ?>
        <a href="?edit=<?= (int)$g['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="gDel(<?= (int)$g['id'] ?>,'<?= e(addslashes($g['judul'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
      </div>
      <?php if ($g['tipe'] === 'video'): ?>
        <div class="g2-vph"><i class="fa-solid fa-circle-play"></i></div>
      <?php elseif ($g['file']): ?>
        <img src="<?= e(media_url($g['file'])) ?>" alt="<?= e($g['judul']) ?>" loading="lazy">
      <?php else: ?>
        <div class="g2-vph"><i class="fa-solid fa-image"></i></div>
      <?php endif; ?>
      <div class="g2-over">
        <p><?= e($g['judul']) ?></p>
        <small><?= e($g['kategori']) ?> • Urutan <?= (int)$g['urutan'] ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="g2-list">
    <?php foreach ($list as $g): ?>
    <div class="g2-litem" data-id="<?= (int)$g['id'] ?>">
      <label style="display:grid;place-items:center;width:24px;height:24px;cursor:pointer"><input type="checkbox" name="ids[]" value="<?= (int)$g['id'] ?>" onchange="gBulk()" style="margin:0;cursor:pointer"></label>
      <div class="li-img">
        <?php if ($g['file'] && $g['tipe'] === 'image'): ?><img src="<?= e(media_url($g['file'])) ?>" alt="" loading="lazy"><?php else: ?><i class="fa-solid <?= $g['tipe'] === 'video' ? 'fa-film' : 'fa-image' ?>"></i><?php endif; ?>
      </div>
      <div>
        <h4><?= e($g['judul']) ?></h4>
        <small><i class="fa-solid fa-tag"></i><?= e($g['kategori']) ?> • <i class="fa-regular fa-calendar-days"></i><?= $g['tanggal'] ? date('d M Y', strtotime($g['tanggal'])) : '-' ?></small>
      </div>
      <span class="badge info"><?= strtoupper(e($g['tipe'])) ?></span>
      <span style="font-size:11.5px;color:#7b8aa3;font-weight:700">#<?= (int)$g['urutan'] ?></span>
      <div class="g2-acts" style="position:static;opacity:1;transform:none;display:flex;gap:5px">
        <?php if ($g['tipe'] === 'image' && $g['file']): ?><button type="button" style="width:32px;height:32px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;cursor:pointer" onclick="gLb('<?= e(media_url($g['file'])) ?>')" title="Lihat"><i class="fa-solid fa-magnifying-glass-plus"></i></button><?php endif; ?>
        <a style="width:32px;height:32px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center" href="?edit=<?= (int)$g['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" style="width:32px;height:32px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#b3261e;cursor:pointer" onclick="gDel(<?= (int)$g['id'] ?>,'<?= e(addslashes($g['judul'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</form>

<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>
<?php endif; ?>

<!-- Modal hapus -->
<div class="g2-modal" id="gDelModal">
  <div class="g2-modal-box">
    <div class="g2-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Item Galeri?</h3>
      <p id="gDelText">Item akan dihapus permanen.</p>
    </div>
    <form method="post" class="g2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="gDelId" value="0">
      <button type="button" onclick="document.getElementById('gDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<!-- Lightbox -->
<div class="g2-lightbox" id="gLb">
  <img src="" alt="">
  <button onclick="document.getElementById('gLb').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
</div>

<script>
(function(){
  window.gBulk = function(){
    var c = document.querySelectorAll('#gBulkForm input[type=checkbox]:checked');
    var bar = document.getElementById('gBulkBar'), n = document.getElementById('gBulkCount');
    if (bar && n) { n.textContent = c.length; bar.classList.toggle('show', c.length > 0); }
    document.querySelectorAll('.g2-item,.g2-litem').forEach(function(it){
      var ck = it.querySelector('input[type=checkbox]');
      if (ck) it.classList.toggle('selected', ck.checked);
    });
  };
  window.gClearBulk = function(){
    document.querySelectorAll('#gBulkForm input[type=checkbox]:checked').forEach(function(c){ c.checked = false; });
    gBulk();
  };
  window.gDel = function(id, judul){
    document.getElementById('gDelId').value = id;
    document.getElementById('gDelText').textContent = 'Item "' + judul + '" akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('gDelModal').classList.add('open');
  };
  window.gLb = function(src){
    var lb = document.getElementById('gLb');
    lb.querySelector('img').src = src;
    lb.classList.add('open');
  };
  document.getElementById('gDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.getElementById('gLb').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') document.querySelectorAll('.g2-modal.open,.g2-lightbox.open').forEach(function(m){ m.classList.remove('open'); });
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>