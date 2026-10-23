<?php
$ACTIVE = 'menu.php'; $TITLE = 'Menu Navigasi';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE menus SET label=?,url=?,parent_id=?,urutan=?,aktif=? WHERE id=?");
            $stmt->bind_param('ssiiii', $_POST['label'], $_POST['url'], $_POST['parent_id'], $_POST['urutan'], $_POST['aktif'], $id);
            $msg = 'Menu berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO menus (label,url,parent_id,urutan,aktif) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiii', $_POST['label'], $_POST['url'], $_POST['parent_id'], $_POST['urutan'], $_POST['aktif']);
            $msg = 'Menu baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM menus WHERE id=$id OR parent_id=$id");
        $msg = 'Menu beserta sub-menu dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM menus WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'label'=>'','url'=>'','parent_id'=>0,'urutan'=>1,'aktif'=>1];
}

$allParents = $conn->query("SELECT * FROM menus WHERE parent_id=0 ORDER BY urutan ASC");
$allMenus = q($conn, "SELECT * FROM menus ORDER BY parent_id ASC, urutan ASC");
$parents = array_filter($allMenus, fn($m) => (int)$m['parent_id'] === 0);
$children = array_filter($allMenus, fn($m) => (int)$m['parent_id'] !== 0);

$stTotal = count($allMenus);
$stParent = count($parents);
$stChild = count($children);
$stAktif = count(array_filter($allMenus, fn($m) => (int)$m['aktif'] === 1));

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= MENU NAVIGASI ULTIMATE ========= */
.m2-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.m2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.m2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.m2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.m2-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none;transition:.3s}
.m2-st:hover .m2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.m2-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.m2-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.m2-st.c-b{--c:#0a66c2}.m2-st.c-g{--c:#00b874}.m2-st.c-a{--c:#f59e0b}.m2-st.c-p{--c:#8b5cf6}

/* Tree view */
.m2-tree{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;box-shadow:var(--shadow)}
.m2-tree-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.m2-tree-head h3{margin:0;display:flex;align-items:center;gap:10px}
.m2-tree-head h3 i{color:var(--green)}
.m2-tree-head .info{font-size:12.5px;color:#7b8aa3;font-weight:600}
.m2-tree-head .info b{color:var(--blue-dark);font-family:var(--display)}

.m2-item{border-left:2px solid var(--line);margin-left:24px;padding:12px 16px;position:relative;transition:.25s;background:#fbfcfe;border-radius:0 10px 10px 0;margin-bottom:8px}
.m2-item:hover{background:#fff;border-left-color:var(--green);transform:translateX(4px)}
.m2-item.parent{border-left-color:var(--blue);background:linear-gradient(135deg,rgba(10,102,194,.05),rgba(0,184,116,.05))}
.m2-item .m2-top{display:flex;align-items:center;gap:12px;margin-bottom:8px}
.m2-item .m2-ic{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));color:var(--blue);display:grid;place-items:center;font-size:14px;flex:none}
.m2-item.parent .m2-ic{background:var(--grad);color:#fff}
.m2-item h4{font-family:var(--display);color:var(--blue-dark);font-size:14.5px;margin:0;flex:1}
.m2-item .m2-badge{padding:3px 10px;border-radius:99px;font-size:9.5px;font-weight:800;letter-spacing:.6px}
.m2-badge.on{background:#e7f8f1;color:#0a7a52}
.m2-badge.off{background:#fdecec;color:#b3261e}
.m2-item code{display:block;font-size:11px;color:var(--blue);background:#eef3fa;padding:2px 8px;border-radius:6px;margin-bottom:8px}
.m2-item .m2-meta{display:flex;gap:12px;font-size:11px;color:#9aa8bf;font-weight:700;margin-bottom:10px;flex-wrap:wrap}
.m2-item .m2-meta i{color:var(--green);margin-right:4px}
.m2-item .m2-acts{display:flex;gap:5px}
.m2-item .m2-acts button,.m2-item .m2-acts a{width:30px;height:30px;border-radius:8px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11px;cursor:pointer;transition:.2s;text-decoration:none}
.m2-item .m2-acts button:hover,.m2-item .m2-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.m2-item .m2-acts .dng:hover{background:#e5484d;border-color:#e5484d}
.m2-item .m2-acts .tg{background:#e7f8f1;border-color:#b5e8d4;color:#0a7a52}
.m2-item .m2-acts .tg.off{background:#fdecec;border-color:#f5c2c0;color:#b3261e}

/* Editor */
.m2-edit{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.m2-edit::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.m2-edit .m2-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.m2-edit .m2-h .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.m2-edit .m2-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.m2-edit .m2-h small{color:#7b8aa3;font-size:12px}
.m2-fld{margin-bottom:16px}
.m2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.m2-fld label .req{color:#e5484d}
.m2-fld label i{color:var(--green)}
.m2-fld input,.m2-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.m2-fld input:focus,.m2-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.m2-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.m2-actions .btn-primary{flex:1;min-width:160px;justify-content:center;padding:13px}
.m2-actions .btn-ghost-d{flex:1;min-width:110px;justify-content:center;padding:13px}

/* Modal */
.m2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.m2-modal.open{display:flex}
.m2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.m2-modal-body{padding:28px;text-align:center}
.m2-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.m2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.m2-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.m2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.m2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.m2-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.m2-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.m2-stats{grid-template-columns:1fr 1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<div class="m2-stats">
  <div class="m2-st c-b"><span class="m2-st-ic"><i class="fa-solid fa-bars-progress"></i></span><div><b><?= $stTotal ?></b><small>Total Menu</small></div></div>
  <div class="m2-st c-g"><span class="m2-st-ic"><i class="fa-solid fa-layer-group"></i></span><div><b><?= $stParent ?></b><small>Menu Utama</small></div></div>
  <div class="m2-st c-a"><span class="m2-st-ic"><i class="fa-solid fa-sitemap"></i></span><div><b><?= $stChild ?></b><small>Sub Menu</small></div></div>
  <div class="m2-st c-p"><span class="m2-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $stAktif ?></b><small>Aktif</small></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 420px;gap:22px;align-items:start">
  <!-- TREE VIEW -->
  <div class="m2-tree">
    <div class="m2-tree-head">
      <h3><i class="fa-solid fa-bars-progress"></i>Struktur Menu Public</h3>
      <span class="info">Menampilkan <b><?= $stTotal ?></b> menu</span>
    </div>

    <?php if (!$allMenus): ?>
    <div style="text-align:center;padding:50px 20px;background:var(--gray);border-radius:12px">
      <i class="fa-solid fa-bars-progress" style="font-size:40px;color:#cfd8e3;margin-bottom:14px"></i>
      <h4 style="color:var(--blue-dark);margin-bottom:8px">Belum ada menu</h4>
      <p style="color:#7b8aa3;font-size:13px;margin-bottom:16px">Tambahkan menu pertama Anda untuk navigasi public.</p>
      <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Buat Menu Pertama</a>
    </div>
    <?php else: ?>
    <!-- PARENTS -->
    <?php foreach ($parents as $p):
      $pChildren = array_filter($children, fn($c) => (int)$c['parent_id'] === (int)$p['id']); ?>
    <div class="m2-item parent">
      <div class="m2-top">
        <span class="m2-ic"><i class="fa-solid fa-bars"></i></span>
        <h4><?= e($p['label']) ?></h4>
        <span class="m2-badge <?= $p['aktif'] ? 'on' : 'off' ?>"><?= $p['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      </div>
      <code><?= e($p['url'] ?: '— dropdown —') ?></code>
      <div class="m2-meta">
        <span><i class="fa-solid fa-arrow-down-1-9"></i>Urutan #<?= (int)$p['urutan'] ?></span>
        <span><i class="fa-solid fa-sitemap"></i><?= count($pChildren) ?> sub-menu</span>
      </div>
      <div class="m2-acts">
        <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button name="toggle" value="1" class="tg <?= $p['aktif'] ? '' : 'off' ?>" title="<?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $p['aktif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button></form>
        <a href="?edit=<?= (int)$p['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="mDel(<?= (int)$p['id'] ?>,'<?= e(addslashes($p['label'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
      </div>
    </div>
    <!-- CHILDREN -->
    <?php foreach ($pChildren as $c): ?>
    <div class="m2-item">
      <div class="m2-top">
        <span class="m2-ic"><i class="fa-solid fa-angle-right"></i></span>
        <h4><?= e($c['label']) ?></h4>
        <span class="m2-badge <?= $c['aktif'] ? 'on' : 'off' ?>"><?= $c['aktif'] ? 'AKTIF' : 'NONAKTIF' ?></span>
      </div>
      <code><?= e($c['url']) ?></code>
      <div class="m2-meta">
        <span><i class="fa-solid fa-arrow-down-1-9"></i>Urutan #<?= (int)$c['urutan'] ?></span>
      </div>
      <div class="m2-acts">
        <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button name="toggle" value="1" class="tg <?= $c['aktif'] ? '' : 'off' ?>" title="<?= $c['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="fa-solid <?= $c['aktif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button></form>
        <a href="?edit=<?= (int)$c['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="mDel(<?= (int)$c['id'] ?>,'<?= e(addslashes($c['label'])) ?>')" title="Hapus"><i class="fa-solid fa-trash"></i></button>
      </div>
    </div>
    <?php endforeach; endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- EDITOR -->
  <form method="post" class="m2-edit">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="m2-h">
      <span class="ic"><i class="fa-solid fa-pen-ruler"></i></span>
      <div>
        <b><?= ($edit['id'] ?? 0) ? 'Edit Menu' : 'Tambah Menu Baru' ?></b>
        <small><?= ($edit['id'] ?? 0) ? 'Memperbarui menu #' . $edit['id'] : 'Buat menu untuk navigasi public' ?></small>
      </div>
    </div>

    <div class="m2-fld">
      <label><i class="fa-solid fa-heading"></i> Label Menu <span class="req">*</span></label>
      <input name="label" value="<?= e($edit['label'] ?? '') ?>" placeholder="Contoh: Beranda, Profil, Akademik" required>
    </div>

    <div class="m2-fld">
      <label><i class="fa-solid fa-link"></i> URL <span style="margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600;letter-spacing:0;text-transform:none">Kosongkan untuk dropdown induk</span></label>
      <input name="url" value="<?= e($edit['url'] ?? '') ?>" placeholder="index.php, page.php?slug=sejarah">
    </div>

    <div class="m2-fld">
      <label><i class="fa-solid fa-sitemap"></i> Induk (Parent)</label>
      <select name="parent_id">
        <option value="0">— Menu Utama (Level 1) —</option>
        <?php foreach ($parents as $p): if ((int)$p['id'] === (int)($edit['id'] ?? 0)) continue; ?>
        <option value="<?= (int)$p['id'] ?>" <?= (int)($edit['parent_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="m2-fld">
        <label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label>
        <input type="number" name="urutan" value="<?= (int)($edit['urutan'] ?? 1) ?>" min="1">
      </div>
      <div class="m2-fld">
        <label><i class="fa-solid fa-signal"></i> Status</label>
        <select name="aktif">
          <option value="1" <?= ($edit['aktif'] ?? 1) ? 'selected' : '' ?>>✓ Aktif (Tampil di navigasi)</option>
          <option value="0" <?= !($edit['aktif'] ?? 1) ? 'selected' : '' ?>>⏸ Nonaktif</option>
        </select>
      </div>
    </div>

    <div class="m2-actions">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= ($edit['id'] ?? 0) ? 'Perbarui Menu' : 'Simpan Menu' ?></button>
      <a class="btn-ghost-d" href="<?= url('admin/menu.php') ?>"><i class="fa-solid fa-xmark"></i> Reset</a>
    </div>
  </form>
</div>

<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>

<!-- Modal hapus -->
<div class="m2-modal" id="mDelModal">
  <div class="m2-modal-box">
    <div class="m2-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Menu Ini?</h3>
      <p id="mDelText">Menu dan sub-menu akan dihapus permanen.</p>
    </div>
    <form method="post" class="m2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="mDelId" value="0">
      <button type="button" onclick="document.getElementById('mDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  window.mDel = function(id, label){
    document.getElementById('mDelId').value = id;
    document.getElementById('mDelText').textContent = 'Menu "' + label + '" beserta sub-menu akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('mDelModal').classList.add('open');
  };
  document.getElementById('mDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.m2-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>