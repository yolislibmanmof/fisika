<?php
$ACTIVE = 'publikasi.php'; $TITLE = 'Publikasi Ilmiah';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';
$doiError = '';

// Auto-fetch DOI via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_doi'])) {
    $doi = trim($_POST['doi'] ?? '');
    if (preg_match('/^10\.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', $doi)) {
        $url = 'https://api.crossref.org/works/' . urlencode($doi);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ProdiFisika/1.0 (mailto:admin@fisika.ac.id)');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['message'])) {
                $m = $data['message'];
                $judul = $m['title'][0] ?? '';
                $penulis = '';
                if (isset($m['author'])) {
                    $authors = array_map(function($a) {
                        return ($a['given'] ?? '') . ' ' . ($a['family'] ?? '');
                    }, $m['author']);
                    $penulis = implode(', ', $authors);
                }
                $jurnal = $m['container-title'][0] ?? ($m['short-container-title'][0] ?? '');
                $tahun = isset($m['published-print']['date-parts'][0][0]) ? $m['published-print']['date-parts'][0][0] : (isset($m['created']['date-parts'][0][0]) ? $m['created']['date-parts'][0][0] : date('Y'));
                
                echo json_encode(['success' => true, 'data' => [
                    'judul' => $judul,
                    'penulis' => $penulis,
                    'nama_jurnal' => $jurnal,
                    'tahun' => $tahun
                ]]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'DOI tidak ditemukan atau API error']);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Format DOI tidak valid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['fetch_doi'])) {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE publikasi SET judul=?, penulis=?, nama_jurnal=?, tahun=?, doi=?, link_url=?, indeks=?, dosen_pengusul=?, urutan=? WHERE id=?");
            $stmt->bind_param('ssssssssii', $_POST['judul'], $_POST['penulis'], $_POST['nama_jurnal'], (int)$_POST['tahun'], $_POST['doi'], $_POST['link_url'], $_POST['indeks'], $_POST['dosen_pengusul'], (int)$_POST['urutan'], $id);
            $msg = 'Publikasi berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO publikasi (judul, penulis, nama_jurnal, tahun, doi, link_url, indeks, dosen_pengusul, urutan) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssssi', $_POST['judul'], $_POST['penulis'], $_POST['nama_jurnal'], (int)$_POST['tahun'], $_POST['doi'], $_POST['link_url'], $_POST['indeks'], $_POST['dosen_pengusul'], (int)$_POST['urutan']);
            $msg = 'Publikasi baru berhasil ditambahkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM publikasi WHERE id=" . (int)$_POST['id']);
        $msg = 'Publikasi berhasil dihapus.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM publikasi WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'judul'=>'','penulis'=>'','nama_jurnal'=>'','tahun'=>date('Y'),'doi'=>'','link_url'=>'','indeks'=>'Lainnya','dosen_pengusul'=>'','urutan'=>1];
}

$statTotal = (int)(q1($conn, "SELECT COUNT(*) AS c FROM publikasi")['c'] ?? 0);
$statScopus = (int)(q1($conn, "SELECT COUNT(*) AS c FROM publikasi WHERE indeks LIKE 'Scopus%'")['c'] ?? 0);
$statSinta = (int)(q1($conn, "SELECT COUNT(*) AS c FROM publikasi WHERE indeks LIKE 'Sinta%'")['c'] ?? 0);
$allPub = q($conn, "SELECT * FROM publikasi ORDER BY tahun DESC, urutan ASC");

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= PUBLIKASI ULTIMATE ADMIN + DOI ========= */
.p-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.p-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;gap:14px;align-items:center;position:relative;overflow:hidden;transition:.3s}
.p-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.p-st:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.p-st-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:17px;flex:none}
.p-st b{font-family:var(--display);font-size:22px;color:var(--blue-dark);display:block;line-height:1}
.p-st small{color:#7b8aa3;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.p-st.c-b{--c:#0a66c2}.p-st.c-g{--c:#00b874}.p-st.c-a{--c:#f59e0b}

.p-list{display:flex;flex-direction:column;gap:12px}
.p-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 22px;display:grid;grid-template-columns:1fr auto auto;gap:16px;align-items:center;transition:.25s}
.p-item:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.p-info h4{font-family:var(--display);color:var(--blue-dark);font-size:14.5px;margin-bottom:6px;line-height:1.4}
.p-meta{display:flex;gap:12px;font-size:11.5px;color:#7b8aa3;font-weight:600;flex-wrap:wrap}
.p-meta i{color:var(--green);margin-right:4px}
.p-badge{padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.6px;white-space:nowrap}
.p-badge.scopus{background:#eef3fa;color:#0a66c2}
.p-badge.sinta{background:#e7f8f1;color:#0a7a52}
.p-badge.lain{background:var(--gray);color:#475569}
.p-acts{display:flex;gap:5px}
.p-acts button,.p-acts a{width:31px;height:31px;border-radius:9px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.p-acts button:hover,.p-acts a:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.p-acts .dng:hover{background:#e5484d;border-color:#e5484d}

.p-edit{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
.p-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.p-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.p-fld{margin-bottom:16px}
.p-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.p-fld label .req{color:#e5484d}
.p-fld label i{color:var(--green)}
.p-fld input,.p-fld select,.p-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.p-fld input:focus,.p-fld select:focus,.p-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.p-fld textarea{resize:vertical;min-height:100px;line-height:1.7}
.p-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.p-doi-row{display:flex;gap:8px}
.p-doi-row input{flex:1}
.p-doi-btn{padding:12px 18px;border:0;background:var(--grad);color:#fff;border-radius:11px;font-weight:700;font-size:12px;cursor:pointer;transition:.2s;white-space:nowrap;display:flex;align-items:center;gap:6px}
.p-doi-btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,184,116,.3)}
.p-doi-btn:disabled{opacity:.6;cursor:not-allowed}
.p-doi-btn.loading{background:#7b8aa3}
.p-doi-status{font-size:11px;margin-top:6px;font-weight:600}
.p-doi-status.success{color:#00b874}
.p-doi-status.error{color:#e5484d}
.p-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.p-actions .btn-primary{justify-content:center;padding:13px;font-size:13px}
.p-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.p-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.p-actions .sec.dng{color:#b3261e;border-color:#f5c2c0}
.p-actions .sec.dng:hover{background:#fdecec;border-color:#b3261e}

@media(max-width:1100px){.p-edit{grid-template-columns:1fr}.p-item{grid-template-columns:1fr;gap:10px}}
@media(max-width:700px){.p-stats{grid-template-columns:1fr}.p-row{grid-template-columns:1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<div class="p-edit">
  <form method="post" class="p-form" id="pubForm">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)">
      <span style="width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none"><i class="fa-solid fa-book-open"></i></span>
      <div><b style="font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block"><?= $edit['id'] ? 'Edit Publikasi' : 'Tambah Publikasi Baru' ?></b><small style="color:#7b8aa3;font-size:12px"><?= $edit['id'] ? 'Memperbarui data publikasi' : 'Catat hasil riset terbaru' ?></small></div>
    </div>

    <!-- DOI Auto-Fetch Section -->
    <div class="p-fld" style="background:linear-gradient(135deg,#f8fafd,#eef4fb);padding:16px;border-radius:12px;border:1.6px solid var(--line)">
      <label><i class="fa-solid fa-barcode"></i> Auto-Fetch via DOI <span style="margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;text-transform:none">Powered by CrossRef API</span></label>
      <div class="p-doi-row">
        <input type="text" id="doiInput" value="<?= e($edit['doi']) ?>" placeholder="Contoh: 10.1016/j.physletb.2023.xxxxx">
        <button type="button" class="p-doi-btn" id="doiBtn" onclick="fetchDOI()"><i class="fa-solid fa-wand-magic-sparkles"></i> Ambil Data</button>
      </div>
      <div id="doiStatus" class="p-doi-status"></div>
    </div>

    <div class="p-fld"><label><i class="fa-solid fa-heading"></i> Judul Artikel <span class="req">*</span></label><textarea name="judul" id="pJudul" required placeholder="Judul lengkap publikasi..."><?= e($edit['judul']) ?></textarea></div>
    
    <div class="p-row">
      <div class="p-fld"><label><i class="fa-solid fa-users"></i> Penulis <span class="req">*</span></label><input name="penulis" id="pPenulis" value="<?= e($edit['penulis']) ?>" placeholder="Nama, A., et al." required></div>
      <div class="p-fld"><label><i class="fa-solid fa-newspaper"></i> Nama Jurnal <span class="req">*</span></label><input name="nama_jurnal" id="pJurnal" value="<?= e($edit['nama_jurnal']) ?>" placeholder="Contoh: Journal of Physics" required></div>
      <div class="p-fld"><label><i class="fa-regular fa-calendar"></i> Tahun <span class="req">*</span></label><input type="number" name="tahun" id="pTahun" value="<?= (int)$edit['tahun'] ?>" min="1900" max="2100" required></div>
    </div>

    <div class="p-row">
      <div class="p-fld"><label><i class="fa-solid fa-barcode"></i> DOI</label><input name="doi" id="pDoi" value="<?= e($edit['doi']) ?>" placeholder="10.xxxx/xxxxx"></div>
      <div class="p-fld"><label><i class="fa-solid fa-link"></i> URL Link</label><input name="link_url" value="<?= e($edit['link_url']) ?>" placeholder="https://doi.org/..."></div>
      <div class="p-fld"><label><i class="fa-solid fa-award"></i> Indeks <span class="req">*</span></label>
        <select name="indeks">
          <?php $opts = ['Scopus Q1','Scopus Q2','Scopus Q3','Scopus Q4','Sinta 1','Sinta 2','Sinta 3','Sinta 4','Sinta 5','Sinta 6','Lainnya']; foreach($opts as $o): ?>
          <option value="<?= $o ?>" <?= $edit['indeks'] === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="p-row">
      <div class="p-fld"><label><i class="fa-solid fa-chalkboard-user"></i> Dosen Pengusul</label><input name="dosen_pengusul" value="<?= e($edit['dosen_pengusul']) ?>" placeholder="Nama dosen utama"></div>
      <div class="p-fld"><label><i class="fa-solid fa-arrow-down-1-9"></i> Urutan</label><input type="number" name="urutan" value="<?= (int)$edit['urutan'] ?>" min="1"></div>
    </div>

    <div class="p-actions" style="flex-direction:row;flex-wrap:wrap">
      <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui' : 'Simpan' ?></button>
      <a class="sec" href="<?= url('admin/publikasi.php') ?>"><i class="fa-solid fa-xmark"></i> Batal</a>
      <?php if ($edit['id']): ?><button type="button" class="sec dng" onclick="if(confirm('Hapus publikasi ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$edit['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}"><i class="fa-solid fa-trash"></i> Hapus</button><?php endif; ?>
    </div>
  </form>
  <?php if ($edit['id']): ?><form id="fDel" method="post" style="display:none"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="hapus" value="1"></form><?php endif; ?>
</div>

<script>
function fetchDOI() {
    var doi = document.getElementById('doiInput').value.trim();
    var btn = document.getElementById('doiBtn');
    var status = document.getElementById('doiStatus');
    
    if (!doi) {
        status.textContent = '⚠️ Masukkan DOI terlebih dahulu';
        status.className = 'p-doi-status error';
        return;
    }
    
    btn.disabled = true;
    btn.classList.add('loading');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengambil...';
    status.textContent = '';
    
    var formData = new FormData();
    formData.append('csrf', document.querySelector('[name="csrf"]').value);
    formData.append('fetch_doi', '1');
    formData.append('doi', doi);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('pJudul').value = data.data.judul;
            document.getElementById('pPenulis').value = data.data.penulis;
            document.getElementById('pJurnal').value = data.data.nama_jurnal;
            document.getElementById('pTahun').value = data.data.tahun;
            document.getElementById('pDoi').value = doi;
            status.textContent = '✅ Data berhasil diambil dari CrossRef!';
            status.className = 'p-doi-status success';
            if(window.a2Toast) a2Toast('Data DOI berhasil diambil!');
        } else {
            status.textContent = '❌ ' + (data.error || 'Gagal mengambil data');
            status.className = 'p-doi-status error';
        }
    })
    .catch(err => {
        status.textContent = '❌ Error: ' + err.message;
        status.className = 'p-doi-status error';
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Ambil Data';
    });
}
</script>

<?php else: ?>
<div class="p-stats">
  <div class="p-st c-b"><span class="p-st-ic"><i class="fa-solid fa-book-open"></i></span><div><b><?= $statTotal ?></b><small>Total Publikasi</small></div></div>
  <div class="p-st c-g"><span class="p-st-ic"><i class="fa-solid fa-globe"></i></span><div><b><?= $statScopus ?></b><small>Terindeks Scopus</small></div></div>
  <div class="p-st c-a"><span class="p-st-ic"><i class="fa-solid fa-flag"></i></span><div><b><?= $statSinta ?></b><small>Terindeks Sinta</small></div></div>
</div>

<div class="panel">
  <div class="panel-head" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0"><i class="fa-solid fa-book-open" style="color:var(--green);margin-right:8px"></i>Daftar Publikasi</h3>
    <a class="btn-small" href="?edit=0"><i class="fa-solid fa-plus"></i> Tambah Publikasi</a>
  </div>

  <?php if (!$allPub): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px">
    <div style="width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)"><i class="fa-solid fa-book-open"></i></div>
    <h3 style="font-family:var(--display);color:var(--blue-dark);margin-bottom:8px">Belum ada publikasi</h3>
    <p style="color:#7b8aa3;font-size:13.5px;margin-bottom:18px">Catat publikasi ilmiah pertama dari dosen Anda.</p>
    <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tambah Publikasi</a>
  </div>
  <?php else: ?>
  <div class="p-list">
    <?php foreach ($allPub as $p): 
      $badgeClass = strpos($p['indeks'], 'Scopus') !== false ? 'scopus' : (strpos($p['indeks'], 'Sinta') !== false ? 'sinta' : 'lain');
    ?>
    <div class="p-item">
      <div class="p-info">
        <h4><?= e($p['judul']) ?></h4>
        <div class="p-meta">
          <span><i class="fa-solid fa-users"></i> <?= e($p['penulis']) ?></span>
          <span><i class="fa-solid fa-newspaper"></i> <?= e($p['nama_jurnal']) ?> (<?= (int)$p['tahun'] ?>)</span>
          <?php if ($p['dosen_pengusul']): ?><span><i class="fa-solid fa-chalkboard-user"></i> <?= e($p['dosen_pengusul']) ?></span><?php endif; ?>
        </div>
      </div>
      <span class="p-badge <?= $badgeClass ?>"><?= e($p['indeks']) ?></span>
      <div class="p-acts">
        <?php if ($p['link_url']): ?><a href="<?= e($p['link_url']) ?>" target="_blank" title="Buka Link"><i class="fa-solid fa-external-link-alt"></i></a><?php endif; ?>
        <a href="?edit=<?= (int)$p['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" class="dng" onclick="if(confirm('Hapus publikasi ini?')){let f=document.createElement('form');f.method='post';f.innerHTML='<input name=csrf value=<?= csrf_token() ?>><input name=id value=<?= (int)$p['id'] ?>><input name=hapus value=1>';document.body.appendChild(f);f.submit();}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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