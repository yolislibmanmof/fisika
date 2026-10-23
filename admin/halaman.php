<?php
$ACTIVE = 'halaman.php'; $TITLE = 'Halaman Statis';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $gambar = upload_file('gambar', 'page') ?? $_POST['gambar_lama'];
        $slug = slugify($_POST['slug'] ?: $_POST['judul']);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE pages SET slug=?,judul=?,konten=?,gambar=?,urutan=?,status=? WHERE id=?");
            $stmt->bind_param('ssssiii', $slug, $_POST['judul'], $_POST['konten'], $gambar, $_POST['urutan'], $_POST['status'], $id);
            $msg = 'Halaman berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO pages (slug,judul,konten,gambar,urutan,status) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssssii', $slug, $_POST['judul'], $_POST['konten'], $gambar, $_POST['urutan'], $_POST['status']);
            $msg = 'Halaman baru berhasil dibuat.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) { $conn->query("DELETE FROM pages WHERE id=" . (int)$_POST['id']); $msg = 'Halaman dihapus.'; }
    if (isset($_POST['toggle'])) { $conn->query("UPDATE pages SET status = 1 - status WHERE id=" . (int)$_POST['id']); $msg = 'Status halaman diubah.'; }
    if (isset($_POST['arah'])) {
        $id = (int)$_POST['id'];
        $cur = q1($conn, "SELECT urutan FROM pages WHERE id=$id");
        if ($cur) {
            $c = (int)$cur['urutan'];
            $nb = $_POST['arah'] === 'naik'
                ? q1($conn, "SELECT id, urutan FROM pages WHERE urutan < $c ORDER BY urutan DESC LIMIT 1")
                : q1($conn, "SELECT id, urutan FROM pages WHERE urutan > $c ORDER BY urutan ASC LIMIT 1");
            if ($nb) {
                $n = (int)$nb['urutan'];
                $conn->query("UPDATE pages SET urutan=$n WHERE id=$id");
                $conn->query("UPDATE pages SET urutan=$c WHERE id=" . (int)$nb['id']);
                $msg = 'Urutan halaman diperbarui.';
            }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM pages WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'slug'=>'','judul'=>'','konten'=>'','gambar'=>'','urutan'=>1,'status'=>1];
}

/* Statistik */
$allPages = q($conn, "SELECT * FROM pages");
$hTotal = count($allPages);
$hTayang = count(array_filter($allPages, fn($p) => (int)$p['status'] === 1));
$hDraft  = $hTotal - $hTayang;
$hWords  = 0;
foreach ($allPages as $p) $hWords += str_word_count(strip_tags((string)$p['konten']));

$iconMap = ['sejarah'=>'fa-landmark','visi-misi'=>'fa-bullseye','struktur-organisasi'=>'fa-sitemap','kurikulum'=>'fa-book-open','laboratorium'=>'fa-flask','penelitian'=>'fa-microscope','kontak'=>'fa-headset'];

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= HALAMAN ULTIMATE ADMIN ========= */
.h2-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.h2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.h2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.h2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.h2-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.h2-st:hover .h2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.h2-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.h2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.h2-st.c-b{--c:#0a66c2}.h2-st.c-g{--c:#00b874}.h2-st.c-a{--c:#f59e0b}.h2-st.c-p{--c:#8b5cf6}

/* Kartu daftar halaman */
.h2-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.h2-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:.3s;box-shadow:var(--shadow)}
.h2-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.h2-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.h2-card.draft{opacity:.8}
.h2-card .h2-top{display:flex;align-items:flex-start;gap:14px;margin-bottom:14px}
.h2-card .h2-ic{width:50px;height:50px;border-radius:14px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:var(--blue);display:grid;place-items:center;font-size:20px;flex:none;transition:.3s}
.h2-card:hover .h2-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.h2-card .h2-top h4{font-family:var(--display);color:var(--blue-dark);font-size:15.5px;line-height:1.4;flex:1}
.h2-card .h2-top code{display:block;font-size:11px;color:var(--blue);background:#eef3fa;padding:2px 8px;border-radius:6px;margin-top:5px}
.h2-card .h2-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;flex:none}
.h2-badge.on{background:#e7f8f1;color:#0a7a52}
.h2-badge.off{background:#fdecec;color:#b3261e}
.h2-card p{color:#68758a;font-size:12.5px;line-height:1.65;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:42px}
.h2-card .h2-meta{display:flex;gap:12px;font-size:11px;color:#9aa8bf;font-weight:700;margin-bottom:14px;flex-wrap:wrap}
.h2-card .h2-meta i{color:var(--green);margin-right:4px}
.h2-card .h2-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line)}
.h2-card .h2-ord{font-size:11.5px;color:#7b8aa3;font-weight:700}
.h2-card .h2-ord i{color:var(--green);margin-right:5px}
.h2-card .h2-acts{display:flex;gap:5px}
.h2-card .h2-acts button,.h2-card .h2-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.h2-card .h2-acts button:hover,.h2-card .h2-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.h2-card .h2-acts .dng:hover{background:#e5484d;border-color:#e5484d}
.h2-card .h2-acts .tg{background:#e7f8f1;border-color:#b5e8d4;color:#0a7a52}
.h2-card .h2-acts .tg.off{background:#fdecec;border-color:#f5c2c0;color:#b3261e}

/* Editor */
.h2-edit{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start}
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
.h2-fld label .tip{margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;letter-spacing:0;text-transform:none}
.h2-fld input,.h2-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.h2-fld input:focus,.h2-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.h2-slugrow{display:flex;gap:8px}
.h2-slugrow input{flex:1;font-family:monospace;font-size:13px;color:var(--blue);background:#eef3fa}
.h2-slugrow button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:0 13px;border-radius:10px;cursor:pointer;font-size:11.5px;font-weight:700;transition:.2s;white-space:nowrap}
.h2-slugrow button:hover{background:var(--grad);color:#fff;border-color:transparent}

/* Toolbar editor konten */
.h2-edbar{display:flex;gap:5px;flex-wrap:wrap;padding:8px;background:var(--gray);border:1.6px solid var(--line);border-bottom:0;border-radius:11px 11px 0 0}
.h2-edbar button{border:1.2px solid transparent;background:transparent;color:#475569;min-width:32px;height:30px;padding:0 9px;border-radius:7px;cursor:pointer;font-size:12px;font-weight:700;transition:.2s;display:inline-flex;align-items:center;justify-content:center;gap:5px}
.h2-edbar button:hover{background:#fff;border-color:var(--line);color:var(--blue);transform:translateY(-1px)}
.h2-edbar .sep{width:1px;background:var(--line);margin:3px 4px}
.h2-fld textarea{width:100%;padding:14px;border:1.6px solid var(--line);border-radius:0 0 11px 11px;font-family:'SFMono-Regular',Consolas,monospace;font-size:13px;line-height:1.7;outline:0;min-height:380px;resize:vertical;background:#fbfcfe}
.h2-fld textarea:focus{border-color:var(--blue);background:#fff}

.h2-up{border:2px dashed var(--line);border-radius:14px;padding:14px;background:#fbfcfe;transition:.3s}
.h2-up:hover,.h2-up.drag{border-color:var(--green);background:#fff}
.h2-up img{width:100%;max-height:180px;object-fit:cover;border-radius:10px;display:block;margin-bottom:8px}
.h2-up .ph{text-align:center;padding:18px 8px;color:#9aa8bf;font-size:12px}
.h2-up .ph i{font-size:26px;color:var(--blue);margin-bottom:8px;display:block}

/* Sidebar */
.h2-side{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.h2-scard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.h2-scard h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:8px}
.h2-scard h5::after{content:'';position:absolute;left:0;bottom:-2px;width:32px;height:2px;background:var(--grad)}
.h2-scard h5 i{color:var(--green)}
.h2-sline{display:flex;justify-content:space-between;align-items:center;padding:7px 0;font-size:12.5px;color:#475569;font-weight:600}
.h2-sline b{color:var(--blue-dark);font-family:var(--display)}
.h2-sline i{color:var(--green);margin-right:7px;width:14px}
.h2-actions{display:flex;flex-direction:column;gap:8px}
.h2-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.h2-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.h2-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.h2-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.h2-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

/* Live preview */
.h2-lp{background:linear-gradient(160deg,#f8fafd,#eef4fb);border:1.6px solid var(--line);border-radius:14px;padding:16px;max-height:340px;overflow-y:auto;scrollbar-width:thin}
.h2-lp h1{font-family:var(--display);color:var(--blue-dark);font-size:20px;line-height:1.3;margin-bottom:12px}
.h2-lp .body{font-size:13px;color:#2b3a55;line-height:1.75}
.h2-lp .body h2,.h2-lp .body h3{color:var(--blue-dark);margin:14px 0 8px;font-size:15px}
.h2-lp .body ul,.h2-lp .body ol{margin:8px 0 8px 20px}
.h2-lp .body p{margin-bottom:10px}

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
.h2-pvmodal .h2-modal-box{width:min(860px,100%);max-height:88vh;display:flex;flex-direction:column}
.h2-pvhead{padding:16px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px}
.h2-pvhead .url{flex:1;font-family:monospace;font-size:12px;color:#7b8aa3;background:var(--gray);padding:8px 12px;border-radius:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.h2-pvbody{padding:30px;overflow-y:auto}
.h2-pvbody h1{font-family:var(--display);color:var(--blue-dark);font-size:28px;margin-bottom:18px}
.h2-pvbody img.pvimg{width:100%;border-radius:14px;margin-bottom:20px}
.h2-pvbody .isi{line-height:1.9;color:#2b3a55;font-size:14.5px}
.h2-pvbody .isi h2,.h2-pvbody .isi h3{color:var(--blue-dark);margin:20px 0 10px}
.h2-pvbody .isi ul,.h2-pvbody .isi ol{margin:12px 0 12px 24px}
.h2-pvbody .isi p{margin-bottom:12px}

.h2-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.h2-empty .eic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}

@media(max-width:1100px){.h2-edit{grid-template-columns:1fr}.h2-side{position:static}.h2-grid{grid-template-columns:1fr}}
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
    <input type="hidden" name="gambar_lama" value="<?= e($edit['gambar']) ?>">
    <div class="h2-h">
      <span class="ic"><i class="fa-solid fa-file-pen"></i></span>
      <div>
        <b><?= $edit['id'] ? 'Edit Halaman Statis' : 'Buat Halaman Baru' ?></b>
        <small><?= $edit['id'] ? 'Memperbarui halaman #' . $edit['id'] : 'Halaman profil, layanan, atau informasi lainnya' ?></small>
      </div>
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-heading"></i> Judul Halaman <span class="req">*</span></label>
      <input name="judul" id="hJudul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Visi & Misi" required>
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-link"></i> URL Slug <span class="tip">Otomatis dari judul</span></label>
      <div class="h2-slugrow">
        <input name="slug" id="hSlug" value="<?= e($edit['slug']) ?>" placeholder="otomatis-dari-judul">
        <button type="button" onclick="hGenSlug()"><i class="fa-solid fa-arrows-rotate"></i> Generate</button>
        <button type="button" onclick="hCopySlug()"><i class="fa-regular fa-copy"></i> Salin</button>
      </div>
    </div>

    <div class="h2-fld">
      <label><i class="fa-solid fa-align-left"></i> Konten Halaman <span class="req">*</span> <span class="tip" id="hWc">0 kata • 0 mnt baca</span></label>
      <div class="h2-edbar">
        <button type="button" onclick="hWrap('<h2>','</h2>')" title="Judul Bagian"><i class="fa-solid fa-heading"></i> H2</button>
        <button type="button" onclick="hWrap('<h3>','</h3>')" title="Sub Judul">H3</button>
        <button type="button" onclick="hWrap('<p>','</p>')" title="Paragraf"><i class="fa-solid fa-paragraph"></i></button>
        <span class="sep"></span>
        <button type="button" onclick="hWrap('<strong>','</strong>')" title="Tebal"><i class="fa-solid fa-bold"></i></button>
        <button type="button" onclick="hWrap('<em>','</em>')" title="Miring"><i class="fa-solid fa-italic"></i></button>
        <button type="button" onclick="hWrap('<ul>\n<li>','</li>\n</ul>')" title="Daftar"><i class="fa-solid fa-list-ul"></i></button>
        <span class="sep"></span>
        <button type="button" onclick="hWrap('<blockquote>','</blockquote>')" title="Kutipan"><i class="fa-solid fa-quote-left"></i></button>
        <button type="button" onclick="hLink()" title="Tautan"><i class="fa-solid fa-link"></i></button>
        <button type="button" onclick="hWrap('<br>','')" title="Baris Baru"><i class="fa-solid fa-arrow-turn-down"></i></button>
      </div>
      <textarea name="konten" id="hKonten" placeholder="Tulis konten halaman. Gunakan toolbar di atas atau tag HTML dasar."><?= e($edit['konten']) ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:14px">
      <div class="h2-fld">
        <label><i class="fa-solid fa-image"></i> Gambar Pendamping</label>
        <div class="h2-up" id="hUp">
          <img id="pvPage" class="<?= $edit['gambar'] ? '' : 'hidden' ?>" src="<?= $edit['gambar'] ? e(media_url($edit['gambar'])) : '' ?>" alt="">
          <div class="ph <?= $edit['gambar'] ? 'hidden' : '' ?>" id="hPh"><i class="fa-solid fa-image"></i>Klik untuk pilih gambar</div>
          <input type="file" name="gambar" id="hFile" accept="image/*" style="display:none">
        </div>
      </div>
      <div class="h2-fld">
        <label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label>
        <input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1">
      </div>
      <div class="h2-fld">
        <label><i class="fa-solid fa-signal"></i> Status</label>
        <select name="status" id="hStatus">
          <option value="1" <?= $edit['status'] ? 'selected' : '' ?>>✓ Tayang</option>
          <option value="0" <?= !$edit['status'] ? 'selected' : '' ?>>⏳ Draft</option>
        </select>
      </div>
    </div>

    <div class="h2-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Halaman' : 'Simpan Halaman' ?></button>
      <button type="button" class="sec" onclick="hPreview()"><i class="fa-solid fa-eye"></i> Pratinjau</button>
      <a class="sec" href="<?= url('admin/halaman.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
    </div>
  </form>

  <!-- SIDEBAR -->
  <aside class="h2-side">
    <div class="h2-scard">
      <h5><i class="fa-solid fa-gauge-high"></i> Statistik Konten</h5>
      <div class="h2-sline"><span><i class="fa-solid fa-font"></i> Kata</span><b id="hStW">0</b></div>
      <div class="h2-sline"><span><i class="fa-regular fa-clock"></i> Waktu Baca</span><b id="hStR">0 mnt</b></div>
      <div class="h2-sline"><span><i class="fa-solid fa-heading"></i> Sub Judul</span><b id="hStH">0</b></div>
      <div class="h2-sline"><span><i class="fa-solid fa-list"></i> Daftar</span><b id="hStL">0</b></div>
      <div class="h2-sline"><span><i class="fa-solid fa-signal"></i> Status</span><b id="hStS"><?= $edit['status'] ? 'Tayang' : 'Draft' ?></b></div>
    </div>
    <div class="h2-scard">
      <h5><i class="fa-solid fa-eye"></i> Pratinjau Langsung</h5>
      <div class="h2-lp">
        <h1 id="hLpJudul"><?= e($edit['judul'] ?: 'Judul Halaman') ?></h1>
        <div class="body" id="hLpBody"><?= $edit['konten'] ? render_content($edit['konten']) : '<p style="color:#9aa8bf">Konten akan tampil di sini...</p>' ?></div>
      </div>
    </div>
  </aside>
</div>

<!-- Modal pratinjau penuh -->
<div class="h2-modal h2-pvmodal" id="hPvModal">
  <div class="h2-modal-box">
    <div class="h2-pvhead">
      <i class="fa-solid fa-globe" style="color:var(--green);font-size:16px"></i>
      <span class="url" id="hPvUrl">—</span>
      <button type="button" class="icon-btn" onclick="document.getElementById('hPvModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="h2-pvbody" id="hPvBody"></div>
  </div>
</div>

<script>
(function(){
  var j = document.getElementById('hJudul'), s = document.getElementById('hSlug'), k = document.getElementById('hKonten'), st = document.getElementById('hStatus');
  var lpJ = document.getElementById('hLpJudul'), lpB = document.getElementById('hLpBody');

  if (j && lpJ) j.addEventListener('input', function(){
    lpJ.textContent = this.value || 'Judul Halaman';
    if (s && !s.dataset.locked) hGenSlug();
  });
  if (s) s.addEventListener('input', function(){ s.dataset.locked = '1'; });
  window.hGenSlug = function(){
    if (!j || !s) return;
    s.value = j.value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    s.dataset.locked = '';
  };
  window.hCopySlug = function(){
    if (s) navigator.clipboard && navigator.clipboard.writeText(s.value).then(function(){ if (window.a2Toast) a2Toast('Slug disalin!'); });
  };

  if (k) {
    var upd = function(){
      var raw = k.value;
      var txt = raw.replace(/<[^>]+>/g,' ');
      var w = txt.trim().split(/\s+/).filter(Boolean).length;
      var m = Math.max(1, Math.round(w / 200));
      var wc = document.getElementById('hWc');
      if (wc) wc.textContent = w + ' kata • ' + m + ' mnt baca';
      var sw = document.getElementById('hStW'), sr = document.getElementById('hStR'), sh = document.getElementById('hStH'), sl = document.getElementById('hStL');
      if (sw) sw.textContent = w;
      if (sr) sr.textContent = m + ' mnt';
      if (sh) sh.textContent = (raw.match(/<h[23][^>]*>/gi) || []).length;
      if (sl) sl.textContent = (raw.match(/<ul|<ol/gi) || []).length;
      if (lpB) lpB.innerHTML = raw ? raw : '<p style="color:#9aa8bf">Konten akan tampil di sini...</p>';
    };
    k.addEventListener('input', upd); upd();

    /* Toolbar wrap selection */
    window.hWrap = function(before, after){
      var st0 = k.selectionStart, en = k.selectionEnd, val = k.value;
      var sel = val.substring(st0, en) || 'teks';
      k.value = val.substring(0, st0) + before + sel + after + val.substring(en);
      k.focus();
      k.selectionStart = st0 + before.length;
      k.selectionEnd = st0 + before.length + sel.length;
      k.dispatchEvent(new Event('input'));
    };
    window.hLink = function(){
      var u = prompt('URL tautan:', 'https://');
      if (u) hWrap('<a href="' + u + '">', '</a>');
    };
  }

  if (st) st.addEventListener('change', function(){
    var el = document.getElementById('hStS');
    if (el) el.textContent = this.value === '1' ? 'Tayang' : 'Draft';
  });

  /* Image upload */
  var up = document.getElementById('hUp'), f = document.getElementById('hFile'), pv = document.getElementById('pvPage'), ph = document.getElementById('hPh');
  if (up && f) {
    up.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG') f.click(); });
    ['dragenter','dragover'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ up.addEventListener(ev, function(e){ e.preventDefault(); up.classList.remove('drag'); }); });
    up.addEventListener('drop', function(e){ if (e.dataTransfer.files[0]) { f.files = e.dataTransfer.files; prev(); } });
    f.addEventListener('change', prev);
    function prev(){
      if (!f.files[0]) return;
      var rd = new FileReader();
      rd.onload = function(ev){ pv.src = ev.target.result; pv.classList.remove('hidden'); if (ph) ph.classList.add('hidden'); };
      rd.readAsDataURL(f.files[0]);
    }
  }

  /* Preview modal */
  window.hPreview = function(){
    var judul = j ? j.value : '';
    var isi = k ? k.value : '';
    var img = pv && !pv.classList.contains('hidden') ? pv.src : '';
    var html = '<h1>' + (judul || '(Tanpa Judul)') + '</h1>';
    if (img) html += '<img class="pvimg" src="' + img + '">';
    html += '<div class="isi">' + (isi || '<p>(Belum ada konten)</p>') + '</div>';
    document.getElementById('hPvBody').innerHTML = html;
    document.getElementById('hPvUrl').textContent = window.location.origin + '/page.php?slug=' + (s && s.value ? s.value : '...');
    document.getElementById('hPvModal').classList.add('open');
  };
  document.getElementById('hPvModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.h2-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php else: ?>

<!-- ===== DAFTAR ===== -->
<div class="h2-stats">
  <div class="h2-st c-b"><span class="h2-st-ic"><i class="fa-solid fa-file-lines"></i></span><div><b><?= $hTotal ?></b><small>Total Halaman</small></div></div>
  <div class="h2-st c-g"><span class="h2-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $hTayang ?></b><small>Tayang</small></div></div>
  <div class="h2-st c-a"><span class="h2-st-ic"><i class="fa-solid fa-file-circle-exclamation"></i></span><div><b><?= $hDraft ?></b><small>Draft</small></div></div>
  <div class="h2-st c-p"><span class="h2-st-ic"><i class="fa-solid fa-font"></i></span><div><b><?= number_format($hWords) ?></b><small>Total Kata</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px">
    <h3 style="margin:0"><i class="fa-solid fa-file-lines" style="color:var(--green);margin-right:8px"></i>Daftar Halaman Statis</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Buat Halaman</a>
  </div>

  <?php if (!$allPages): ?>
  <div class="h2-empty">
    <div class="eic"><i class="fa-solid fa-file-lines"></i></div>
    <h3>Belum ada halaman</h3>
    <p>Buat halaman statis pertama Anda (profil, visi misi, kurikulum, dll).</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Buat Halaman Pertama</a>
  </div>
  <?php else: ?>
  <div class="h2-grid">
    <?php foreach ($allPages as $p):
      $w = str_word_count(strip_tags((string)$p['konten']));
      $ic = $iconMap[$p['slug']] ?? 'fa-file-lines'; ?>
    <div class="h2-card <?= $p['status'] ? '' : 'draft' ?>">
      <div class="h2-top">
        <span class="h2-ic"><i class="fa-solid <?= e($ic) ?>"></i></span>
        <div style="flex:1;min-width:0">
          <h4><?= e($p['judul']) ?></h4>
          <code>/page.php?slug=<?= e($p['slug']) ?></code>
        </div>
        <span class="h2-badge <?= $p['status'] ? 'on' : 'off' ?>"><?= $p['status'] ? 'TAYANG' : 'DRAFT' ?></span>
      </div>
      <p><?= e(mb_strimwidth(trim(strip_tags($p['konten'])), 0, 130, '...')) ?></p>
      <div class="h2-meta">
        <span><i class="fa-solid fa-font"></i><?= number_format($w) ?> kata</span>
        <span><i class="fa-regular fa-clock"></i><?= max(1, (int)round($w / 200)) ?> mnt baca</span>
        <span><i class="fa-solid fa-heading"></i><?= count(preg_grep('/^h/', array_map(fn($m) => $m[0], [])) ) ?: substr_count(strtolower($p['konten']), '<h2') + substr_count(strtolower($p['konten']), '<h3') ?> bagian</span>
      </div>
      <div class="h2-foot">
        <span class="h2-ord"><i class="fa-solid fa-arrow-down-1-9"></i> Urutan #<?= (int)$p['urutan'] ?></span>
        <div class="h2-acts">
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button name="arah" value="naik" title="Naikkan"><i class="fa-solid fa-arrow-up"></i></button></form>
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button name="arah" value="turun" title="Turunkan"><i class="fa-solid fa-arrow-down"></i></button></form>
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button name="toggle" value="1" class="tg <?= $p['status'] ? '' : 'off' ?>" title="<?= $p['status'] ? 'Jadikan draft' : 'Terbitkan' ?>"><i class="fa-solid <?= $p['status'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button></form>
          <a target="_blank" href="<?= url('page.php?slug=' . e($p['slug'])) ?>" title="Lihat"><i class="fa-solid fa-globe"></i></a>
          <a href="?edit=<?= (int)$p['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <button type="button" class="dng" onclick="hDel(<?= (int)$p['id'] ?>,'<?= e(addslashes($p['judul'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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

<!-- Modal hapus -->
<div class="h2-modal" id="hDelModal">
  <div class="h2-modal-box">
    <div class="h2-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Halaman Ini?</h3>
      <p id="hDelText">Halaman akan dihapus permanen.</p>
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
    document.getElementById('hDelText').textContent = 'Halaman "' + judul + '" akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('hDelModal').classList.add('open');
  };
  document.getElementById('hDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.h2-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>