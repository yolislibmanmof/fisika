<?php
$ACTIVE = 'users.php'; $TITLE = 'Manajemen Pengguna';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $pass = trim($_POST['password']);
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $role = $_POST['role'];

        if ($id > 0) {
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama=?,username=?,password=?,role=? WHERE id=?");
                $stmt->bind_param('ssssi', $nama, $username, $hash, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama=?,username=?,role=? WHERE id=?");
                $stmt->bind_param('sssi', $nama, $username, $role, $id);
            }
            $msg = 'Data pengguna berhasil diperbarui.';
        } else {
            if ($pass === '') $pass = 'admin123'; // Default password
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama,username,password,role) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $nama, $username, $hash, $role);
            $msg = 'Pengguna baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['admin_id']) { 
            $conn->query("DELETE FROM users WHERE id=$id"); 
            $msg = 'Pengguna berhasil dihapus.'; 
        } else {
            $msg = 'Anda tidak dapat menghapus akun Anda sendiri.';
        }
    }
}

$users = q($conn, "SELECT * FROM users ORDER BY id ASC");
$stTotal = count($users);
$stAdmin = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$stEditor = $stTotal - $stAdmin;

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { 
        $st = $conn->prepare("SELECT id,nama,username,role,dibuat FROM users WHERE id=?"); 
        $st->bind_param('i', $id); 
        $st->execute(); 
        $edit = $st->get_result()->fetch_assoc(); 
    }
    $edit = $edit ?: ['id'=>0,'nama'=>'','username'=>'','role'=>'admin','dibuat'=>date('Y-m-d H:i:s')];
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= PENGGUNA ULTIMATE ADMIN ========= */
.u-hero{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.u-hcard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.u-hcard::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.u-hcard:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.u-hcard .ic{width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:18px;flex:none}
.u-hcard b{font-family:var(--display);font-size:24px;color:var(--blue-dark);display:block;line-height:1}
.u-hcard small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.u-hcard.c1{--c:#0a66c2}.u-hcard.c2{--c:#8b5cf6}.u-hcard.c3{--c:#00b874}

.u-layout{display:grid;grid-template-columns:1fr 420px;gap:22px;align-items:start}

/* User List Cards */
.u-list{display:flex;flex-direction:column;gap:12px}
.u-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;display:flex;align-items:center;gap:16px;transition:.3s;position:relative}
.u-card:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px);box-shadow:var(--shadow)}
.u-card.me{border-left:4px solid var(--green);background:linear-gradient(135deg,#f0fdf7,#fff)}
.u-avatar{width:50px;height:50px;border-radius:13px;overflow:hidden;background:var(--gray);flex:none;display:grid;place-items:center}
.u-avatar img{width:100%;height:100%;object-fit:cover}
.u-avatar .init{font-family:var(--display);font-size:18px;font-weight:800;color:var(--blue)}
.u-info{flex:1;min-width:0}
.u-info h4{font-family:var(--display);color:var(--blue-dark);font-size:15px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.u-info .uname{font-size:12px;color:#7b8aa3;font-weight:600;display:flex;align-items:center;gap:6px}
.u-info .uname i{color:var(--green);font-size:10px}
.u-info .date{font-size:11px;color:#9aa8bf;margin-top:4px;display:flex;align-items:center;gap:5px}
.u-role{padding:5px 12px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase}
.u-role.admin{background:rgba(139,92,246,.12);color:#7c3aed;border:1px solid rgba(139,92,246,.25)}
.u-role.editor{background:rgba(0,184,116,.12);color:#0a7a52;border:1px solid rgba(0,184,116,.25)}
.u-acts{display:flex;gap:6px}
.u-acts button,.u-acts a{width:34px;height:34px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:12px;cursor:pointer;transition:.2s;text-decoration:none}
.u-acts button:hover,.u-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.u-acts .dng:hover{background:#e5484d;border-color:#e5484d}
.u-acts .dng:disabled{opacity:.4;cursor:not-allowed;transform:none!important;background:#fff!important;color:#475569!important;border-color:var(--line)!important}

/* Editor Form */
.u-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:sticky;top:90px}
.u-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.u-form .u-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.u-form .u-h .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.u-form .u-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.u-form .u-h small{color:#7b8aa3;font-size:12px;display:block;margin-top:3px}
.u-fld{margin-bottom:18px}
.u-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.u-fld label .req{color:#e5484d}
.u-fld label i{color:var(--green)}
.u-fld input,.u-fld select{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.u-fld input:focus,.u-fld select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.u-fld .hint{font-size:11.5px;color:#7b8aa3;margin-top:6px;display:flex;align-items:center;gap:6px}
.u-fld .hint i{color:#f59e0b}
.u-actions{display:flex;gap:10px;margin-top:24px}
.u-actions .btn-primary{flex:1;justify-content:center;padding:13px}
.u-actions .btn-ghost-d{flex:1;justify-content:center;padding:13px}

.u-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.u-empty .eic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}

/* Modal */
.u-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.u-modal.open{display:flex}
.u-modal-box{background:#fff;border-radius:18px;width:min(480px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.u-modal-body{padding:28px;text-align:center}
.u-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.u-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.u-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.u-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.u-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.u-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.u-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.u-layout{grid-template-columns:1fr}.u-form{position:static}.u-hero{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert <?= strpos($msg, 'tidak dapat') !== false ? 'err' : 'ok' ?>">
  <i class="fa-solid fa-<?= strpos($msg, 'tidak dapat') !== false ? 'circle-exclamation' : 'circle-check' ?>"></i> <?= e($msg) ?>
</div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>', '<?= strpos($msg, 'tidak dapat') !== false ? 'err' : 'ok' ?>');</script>
<?php endif; ?>

<div class="u-hero">
  <div class="u-hcard c1"><span class="ic"><i class="fa-solid fa-users"></i></span><div><b><?= $stTotal ?></b><small>Total Pengguna</small></div></div>
  <div class="u-hcard c2"><span class="ic"><i class="fa-solid fa-user-shield"></i></span><div><b><?= $stAdmin ?></b><small>Administrator</small></div></div>
  <div class="u-hcard c3"><span class="ic"><i class="fa-solid fa-user-pen"></i></span><div><b><?= $stEditor ?></b><small>Editor</small></div></div>
</div>

<div class="u-layout">
  <!-- DAFTAR PENGGUNA -->
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 style="margin:0;font-family:var(--display);color:var(--blue-dark);font-size:18px"><i class="fa-solid fa-users-gear" style="color:var(--green);margin-right:8px"></i>Daftar Pengguna</h3>
      <?php if (!$edit): ?><a href="?edit=0" class="btn-small"><i class="fa-solid fa-plus"></i> Tambah Pengguna</a><?php endif; ?>
    </div>

    <?php if (!$users): ?>
    <div class="u-empty">
      <div class="eic"><i class="fa-solid fa-user-plus"></i></div>
      <h3>Belum ada pengguna</h3>
      <p>Buat akun administrator atau editor pertama Anda untuk mengelola situs.</p>
      <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Buat Pengguna Pertama</a>
    </div>
    <?php else: ?>
    <div class="u-list">
      <?php foreach ($users as $u): 
        $isMe = ((int)$u['id'] === (int)$_SESSION['admin_id']);
        $initials = strtoupper(substr($u['nama'], 0, 1) . (strpos($u['nama'], ' ') !== false ? substr(explode(' ', $u['nama'])[1], 0, 1) : ''));
        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($u['nama']) . "&background=0a66c2&color=fff&bold=true&size=100";
      ?>
      <div class="u-card <?= $isMe ? 'me' : '' ?>">
        <div class="u-avatar">
          <img src="<?= $avatarUrl ?>" alt="<?= e($u['nama']) ?>">
        </div>
        <div class="u-info">
          <h4><?= e($u['nama']) ?> <?= $isMe ? '<span style="font-size:10px;background:var(--green);color:#04231a;padding:2px 8px;border-radius:99px;font-weight:800">ANDAA</span>' : '' ?></h4>
          <div class="uname"><i class="fa-solid fa-at"></i> <?= e($u['username']) ?></div>
          <div class="date"><i class="fa-regular fa-clock"></i> Bergabung <?= date('d M Y', strtotime($u['dibuat'])) ?></div>
        </div>
        <span class="u-role <?= $u['role'] ?>"><?= e($u['role']) ?></span>
        <div class="u-acts">
          <a href="?edit=<?= (int)$u['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button type="button" class="dng" <?= $isMe ? 'disabled title="Tidak dapat menghapus diri sendiri"' : 'onclick="uDel(' . (int)$u['id'] . ',\'' . e(addslashes($u['nama'])) . '\')"' ?> title="<?= $isMe ? 'Akun Anda' : 'Hapus' ?>"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- FORM EDITOR -->
  <form method="post" class="u-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="u-h">
      <span class="ic"><i class="fa-solid fa-<?= ($edit['id'] ?? 0) ? 'user-pen' : 'user-plus' ?>"></i></span>
      <div>
        <b><?= ($edit['id'] ?? 0) ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></b>
        <small><?= ($edit['id'] ?? 0) ? 'Memperbarui data akun #' . $edit['id'] : 'Buat akun baru untuk mengakses panel admin' ?></small>
      </div>
    </div>

    <div class="u-fld">
      <label><i class="fa-solid fa-user"></i> Nama Lengkap <span class="req">*</span></label>
      <input name="nama" id="uNama" value="<?= e($edit['nama'] ?? '') ?>" placeholder="Contoh: Budi Santoso" required>
    </div>

    <div class="u-fld">
      <label><i class="fa-solid fa-at"></i> Username <span class="req">*</span></label>
      <input name="username" value="<?= e($edit['username'] ?? '') ?>" placeholder="Contoh: budi.admin" required style="font-family:monospace">
    </div>

    <div class="u-fld">
      <label><i class="fa-solid fa-lock"></i> Password</label>
      <input type="password" name="password" id="uPass" placeholder="<?= ($edit['id'] ?? 0) ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 6 karakter' ?>" <?= ($edit['id'] ?? 0) ? '' : 'required' ?>>
      <div class="hint">
        <i class="fa-solid fa-circle-info"></i> 
        <span><?= ($edit['id'] ?? 0) ? 'Biarkan kosong untuk mempertahankan password lama.' : 'Password default: <b>admin123</b> jika dikosongkan.' ?></span>
      </div>
    </div>

    <div class="u-fld">
      <label><i class="fa-solid fa-user-tag"></i> Peran (Role)</label>
      <select name="role">
        <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>🛡️ Administrator (Akses Penuh)</option>
        <option value="editor" <?= ($edit['role'] ?? '') === 'editor' ? 'selected' : '' ?>>✏️ Editor (Kelola Konten Saja)</option>
      </select>
    </div>

    <div class="u-actions">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= ($edit['id'] ?? 0) ? 'Perbarui Data' : 'Simpan Pengguna' ?></button>
      <a class="btn-ghost-d" href="<?= url('admin/users.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
    </div>
    <?php if (($edit['id'] ?? 0) && (int)$edit['id'] !== (int)$_SESSION['admin_id']): ?>
    <div style="margin-top:16px;text-align:center">
      <button type="button" class="btn-ghost-d" style="width:100%;color:#b3261e;border-color:#f5c2c0" onclick="uDel(<?= (int)$edit['id'] ?>,'<?= e(addslashes($edit['nama'])) ?>')">
        <i class="fa-solid fa-trash"></i> Hapus Pengguna Ini
      </button>
    </div>
    <?php endif; ?>
  </form>
</div>

<!-- Modal Hapus -->
<div class="u-modal" id="uDelModal">
  <div class="u-modal-box">
    <div class="u-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Pengguna?</h3>
      <p id="uDelText">Akun ini akan dihapus permanen dan tidak dapat masuk ke sistem lagi.</p>
    </div>
    <form method="post" class="u-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="uDelId" value="0">
      <button type="button" onclick="document.getElementById('uDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  /* Live avatar update */
  var namaInp = document.getElementById('uNama');
  var avatarImgs = document.querySelectorAll('.u-avatar img'); // Update if we had a preview, but here we just keep it simple
  
  /* Delete Modal */
  window.uDel = function(id, nama){
    document.getElementById('uDelId').value = id;
    document.getElementById('uDelText').textContent = 'Pengguna "' + nama + '" akan dihapus permanen dari sistem.';
    document.getElementById('uDelModal').classList.add('open');
  };
  document.getElementById('uDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.u-modal.open').forEach(function(m){ m.classList.remove('open'); }); });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>