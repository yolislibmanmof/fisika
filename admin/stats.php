<?php
$ACTIVE = 'stats.php'; $TITLE = 'Statistik Beranda';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE stats SET label=?,nilai=?,suffix=?,icon=?,urutan=? WHERE id=?");
            $stmt->bind_param('sisiii', $_POST['label'], $_POST['nilai'], $_POST['suffix'], $_POST['icon'], $_POST['urutan'], $id);
            $msg = 'Statistik berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO stats (label,nilai,suffix,icon,urutan) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sisii', $_POST['label'], $_POST['nilai'], $_POST['suffix'], $_POST['icon'], $_POST['urutan']);
            $msg = 'Statistik baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM stats WHERE id=" . (int)$_POST['id']);
        $msg = 'Statistik berhasil dihapus.';
    }
}

$stats = q($conn, "SELECT * FROM stats ORDER BY urutan ASC, id ASC");
$stTotal = count($stats);
$stSum = array_sum(array_column($stats, 'nilai'));

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= STATISTIK ULTIMATE ADMIN ========= */
.st-hero{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.st-hcard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.st-hcard::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.st-hcard:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.st-hcard .ic{width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:18px;flex:none}
.st-hcard b{font-family:var(--display);font-size:24px;color:var(--blue-dark);display:block;line-height:1}
.st-hcard small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.st-hcard.c1{--c:#0a66c2}.st-hcard.c2{--c:#00b874}.st-hcard.c3{--c:#8b5cf6}

.st-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
.st-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;transition:.3s;box-shadow:var(--shadow);position:relative}
.st-card:hover{transform:translateY(-4px);border-color:rgba(0,184,116,.3)}
.st-card.new-card{border:2px dashed var(--line);background:linear-gradient(135deg,#f8fafd,#fff);box-shadow:none}
.st-card.new-card:hover{border-color:var(--green);transform:translateY(-4px)}

/* Live Preview Area */
.st-pv{padding:28px 24px;background:linear-gradient(135deg,#071a39,#0a2a5e);text-align:center;position:relative;overflow:hidden}
.st-pv::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 50% 120%,rgba(0,184,116,.2),transparent 60%)}
.st-pv .st-ic{width:64px;height:64px;border-radius:18px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:26px;margin:0 auto 16px;box-shadow:0 12px 26px rgba(10,102,194,.4);transition:.4s}
.st-card:hover .st-pv .st-ic{transform:rotateY(180deg)}
.st-pv .st-num{font-family:var(--display);font-size:42px;color:#fff;line-height:1;margin-bottom:6px;position:relative;z-index:1}
.st-pv .st-num em{color:#7ef0c4;font-style:normal;font-size:28px}
.st-pv .st-lbl{color:#c8d6ea;font-size:13px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;position:relative;z-index:1}

/* Edit Area */
.st-edit{padding:20px;border-top:1px solid var(--line)}
.st-row{display:flex;gap:10px;margin-bottom:12px;align-items:center}
.st-row:last-child{margin-bottom:0}
.st-in{flex:1;padding:10px 12px;border:1.4px solid var(--line);border-radius:9px;font-family:var(--body);font-size:13px;outline:0;transition:.2s;background:#fbfcfe}
.st-in:focus{border-color:var(--blue);background:#fff}
.st-in.sm{max-width:80px;text-align:center}
.st-in.suf{max-width:60px;text-align:center}
.st-icon-btn{width:42px;height:42px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:var(--blue);cursor:pointer;font-size:15px;display:grid;place-items:center;transition:.2s;flex:none}
.st-icon-btn:hover{background:var(--grad);color:#fff;border-color:transparent}
.st-acts{display:flex;gap:8px;margin-top:16px;padding-top:14px;border-top:1px dashed var(--line)}
.st-acts button,.st-acts .btn-small{flex:1;padding:10px;border-radius:9px;font-weight:700;font-size:12px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#475569;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
.st-acts button:hover,.st-acts .btn-small:hover{transform:translateY(-2px)}
.st-acts .save{background:var(--grad);color:#fff;border-color:transparent}
.st-acts .save:hover{box-shadow:0 8px 18px rgba(0,184,116,.3)}
.st-acts .del{color:#b3261e;border-color:#f5c2c0}
.st-acts .del:hover{background:#fdecec;border-color:#b3261e}

.st-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.st-empty .eic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}

/* Modal */
.st-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.st-modal.open{display:flex}
.st-modal-box{background:#fff;border-radius:18px;width:min(480px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.st-modal-body{padding:28px;text-align:center}
.st-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.st-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.st-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.st-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.st-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.st-modal-foot .del{background:#b3261e;color:#fff;border-color:#b3261e}
.st-modal-foot button:hover{transform:translateY(-2px)}

@media(max-width:1100px){.st-grid{grid-template-columns:1fr}.st-hero{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
‍<?php endif; ?>

<div class="st-hero">
  <div class="st-hcard c1"><span class="ic"><i class="fa-solid fa-chart-simple"></i></span><div><b><?= $stTotal ?></b><small>Total Item</small></div></div>
  <div class="st-hcard c2"><span class="ic"><i class="fa-solid fa-calculator"></i></span><div><b><?= number_format($stSum) ?></b><small>Total Nilai</small></div></div>
  <div class="st-hcard c3"><span class="ic"><i class="fa-solid fa-globe"></i></span><div><b>Public</b><small>Tampil di Beranda</small></div></div>
</div>

<?php if (!$stats): ?>
<div class="st-empty">
  <div class="eic"><i class="fa-solid fa-chart-simple"></i></div>
  <h3>Belum ada statistik</h3>
  <p>Tambahkan angka statistik pertama Anda untuk ditampilkan pada beranda public.</p>
  <a href="#newStat" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Statistik Pertama</a>
</div>
<?php else: ?>
<div class="st-grid">
  <?php foreach ($stats as $s): ?>
  <div class="st-card">
    <div class="st-pv">
      <span class="st-ic"><i id="pvIc<?= (int)$s['id'] ?>" class="<?= e($s['icon']) ?>"></i></span>
      <div class="st-num"><span id="pvNum<?= (int)$s['id'] ?>"><?= (int)$s['nilai'] ?></span><em id="pvSuf<?= (int)$s['id'] ?>"><?= e($s['suffix']) ?></em></div>
      <div class="st-lbl" id="pvLbl<?= (int)$s['id'] ?>"><?= e($s['label']) ?></div>
    </div>
    <form method="post" class="st-edit">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
      
      <div class="st-row">
        <input type="hidden" name="icon" id="ic<?= (int)$s['id'] ?>" value="<?= e($s['icon']) ?>">
        <button type="button" class="st-icon-btn" data-iconpicker="#ic<?= (int)$s['id'] ?>" title="Ganti Ikon"><i class="fa-solid fa-icons"></i></button>
        <input class="st-in" name="label" value="<?= e($s['label']) ?>" placeholder="Label (cth: Mahasiswa)" oninput="stPv(<?= (int)$s['id'] ?>)">
     6</div>
      <div class="st-row">
        <input type="number" class="st-in sm" name="nilai" value="<?= (int)$s['nilai'] ?>" placeholder="0" oninput="stPv(<?= (int)$s['id'] ?>)">
        <input class="st-in suf" name="suffix" value="<?= e($s['suffix']) ?>" placeholder="+" oninput="stPv(<?= (int)$s['id'] ?>)">
        <input type="number" class="st-in sm" name="urutan" value="<?= (int)$s['urutan'] ?>" placeholder="#" title="Urutan">
      </div>
      <div class="st-acts">
        <button type="submit" name="simpan" value="1" class="save"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        <button type="button" class="del" onclick="stDel(<?= (int)$s['id'] ?>,'<?= e(addslashes($s['label'])) ?>')"><i class="fa-solid fa-trash"></i> Hapus</button>
      </div>
    </form>
  </div>
  <?php endforeach; ?>

  <!-- ADD NEW CARD -->
  <div class="st-card new-card" id="newStat">
    <div class="st-pv" style="background:linear-gradient(135deg,#f0fdf7,#ecfeff)">
      <span class="st-ic" style="background:#fff;color:#00b874;box-shadow:0 8px 20px rgba(0,184,116,.2)"><i class="fa-solid fa-plus"></i></span>
      <div class="st-num" style="color:#00b874;font-size:24px">Tambah Baru</div>
      <div class="st-lbl" style="color:#0a7a52">Statistik Beranda</div>
    </div>
    <form method="post" class="st-edit">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="0">
      <div class="st-row">
        <input type="hidden" name="icon" id="icNew" value="fa-solid fa-star">
        <button type="button" class="st-icon-btn" data-iconpicker="#icNew" title="Pilih Ikon"><i class="fa-solid fa-icons"></i></button>
        <input class="st-in" name="label" placeholder="Label Baru" required>
      </div>
      <div class="st-row">
        <input type="number" class="st-in sm" name="nilai" value="0" required>
        <input class="st-in suf" name="suffix" value="+" required>
        <input type="" class="st-in sm" name="urutan" value="<?= $stTotal + 1 ?>" required>
      </div>
      <div class="st-acts">
        <button type="submit" name="simpan" value="1" class="save"><i class="fa-solid fa-plus"></i> Tambah</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Modal Hapus -->
<div class="st-modal" id="stDelModal">
  <div class="st-modal-box">
    <div class="st-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Statistik?</h3>
      <p id="stDelText">Item ini akan dihapus permanen dari beranda.</p>
    </div>
    <form method="post" class="st-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="stDelId" value="0">
      <button type="button" onclick="document.getElementById('stDelModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<script>
(function(){
  /* Live Preview Update */
  window.stPv = function(id){
    var lbl = document.querySelector('input[name="label"][form*="id"][form*="'+id+'"]') || document.querySelector('.st-card:nth-child('+(id)+') input[name="label"]');
    // Fallback selector yang lebih aman berdasarkan struktur DOM form
    var forms = document.querySelectorAll('.st-edit');
    forms.forEach(function(f){
      var idInp = f.querySelector('input[name="id"]');
      if (idInp && idInp.value == id) {
        var l = f.querySelector('input[name="label"]').value;
        var n = f.querySelector('input[name="nilai"]').value;
        var s = f.querySelector('input[name="suffix"]').value;
        var ic = f.querySelector('input[name="icon"]').value;
        
        var pvL = document.getElementById('pvLbl'+id);
        var pvN = document.getElementById('pvNum'+id);
        var pvS = document.getElementById('pvSuf'+id);
        var pvI = document.getElementById('pvIc'+id);
        
        if (pvL) pvL.textContent = l || 'Label';
        if (pvN) pvN.textContent = n || '0';
        if (pvS) pvS.textContent = s || '+';
        if (pvI) pvI.className = ic || 'fa-solid fa-star';
      }
    });
  };

  /* Delete Modal */
  window.stDel = function(id, label){
    document.getElementById('stDelId').value = id;
    document.getElementById('stDelText').textContent = 'Statistik "' + label + '" akan dihapus permanen dari beranda public.';
    document.getElementById('stDelModal').classList.add('open');
  };
  document.getElementById('stDelModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.st-modal.open').forEach(function(m){ m.classList.remove('open'); }); });

  /* Icon picker sync listener */
  var observer = new MutationObserver(function(){
    document.querySelectorAll('.st-edit').forEach(function(f){
      var id = f.querySelector('input[name="id"]').value;
      stPv(id);
    });
  });
  document.querySelectorAll('input[name="icon"]').forEach(function(inp){
    observer.observe(inp, { attributes: true, attributeFilter: ['value'] });
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>