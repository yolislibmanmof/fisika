<?php
$ACTIVE = 'berita.php'; $TITLE = 'Manajemen Berita';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';
$bulkAction = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $gambar = upload_file('gambar', 'berita') ?? $_POST['gambar_lama'];
        $slug = slugify($_POST['slug'] ?: $_POST['judul']);
        $penulis = $_SESSION['admin_name'];
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE berita SET judul=?,slug=?,kategori=?,isi=?,gambar=?,penulis=?,tanggal=?,status=? WHERE id=?");
            $stmt->bind_param('sssssssii', $_POST['judul'], $slug, $_POST['kategori'], $_POST['isi'], $gambar, $penulis, $_POST['tanggal'], $_POST['status'], $id);
            $msg = 'Berita berhasil diperbarui.';
        } else {
            $stmt = $conn->prepare("INSERT INTO berita (judul,slug,kategori,isi,gambar,penulis,tanggal,status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssi', $_POST['judul'], $slug, $_POST['kategori'], $_POST['isi'], $gambar, $penulis, $_POST['tanggal'], $_POST['status']);
            $msg = 'Berita baru berhasil diterbitkan.';
        }
        $stmt->execute();
    }
    if (isset($_POST['hapus'])) {
        $conn->query("DELETE FROM berita WHERE id=" . (int)$_POST['id']);
        $msg = 'Berita berhasil dihapus.';
    }
    if (isset($_POST['bulk']) && is_array($_POST['ids'] ?? null)) {
        $ids = array_map('intval', $_POST['ids']);
        if ($ids) {
            $in = implode(',', $ids);
            if ($_POST['bulk'] === 'delete')   { $conn->query("DELETE FROM berita WHERE id IN ($in)"); $msg = count($ids) . ' berita dihapus.'; }
            if ($_POST['bulk'] === 'publish')  { $conn->query("UPDATE berita SET status=1 WHERE id IN ($in)"); $msg = count($ids) . ' berita diterbitkan.'; }
            if ($_POST['bulk'] === 'draft')    { $conn->query("UPDATE berita SET status=0 WHERE id IN ($in)"); $msg = count($ids) . ' berita dijadikan draft.'; }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) { $st = $conn->prepare("SELECT * FROM berita WHERE id=?"); $st->bind_param('i', $id); $st->execute(); $edit = $st->get_result()->fetch_assoc(); }
    $edit = $edit ?: ['id'=>0,'judul'=>'','slug'=>'','kategori'=>'Kegiatan','isi'=>'','gambar'=>'','penulis'=>$_SESSION['admin_name'],'tanggal'=>date('Y-m-d'),'status'=>1,'views'=>0];
}

/* Data dashboard */
$statTotal  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM berita")['c'] ?? 0);
$statTayang = (int)(q1($conn, "SELECT COUNT(*) AS c FROM berita WHERE status=1")['c'] ?? 0);
$statDraft  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM berita WHERE status=0")['c'] ?? 0);
$statViews  = (int)(q1($conn, "SELECT SUM(views) AS s FROM berita")['s'] ?? 0);

/* Filter */
$fQ     = trim($_GET['q'] ?? '');
$fKat   = trim($_GET['kat'] ?? '');
$fStat  = trim($_GET['st'] ?? '');
$where  = " WHERE 1=1"; $params = []; $types = '';
if ($fQ !== '')    { $where .= " AND (judul LIKE ? OR kategori LIKE ? OR isi LIKE ?)"; $like = "%$fQ%"; $params = array_merge($params, [$like,$like,$like]); $types .= 'sss'; }
if ($fKat !== '')  { $where .= " AND kategori=?"; $params[] = $fKat; $types .= 's'; }
if ($fStat !== '') { $where .= " AND status=?"; $params[] = (int)$fStat; $types .= 'i'; }

$kats = q($conn, "SELECT kategori, COUNT(*) AS c FROM berita GROUP BY kategori ORDER BY kategori ASC");

$view = ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid';

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= BERITA ULTIMATE ADMIN (inline) ========= */
.a2-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.a2-st{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;transition:.3s;cursor:pointer}
.a2-st:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:rgba(0,184,116,.3)}
.a2-st::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c,var(--grad))}
.a2-st-ic{width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;color:var(--blue);font-size:18px;flex:none;transition:.3s}
.a2-st:hover .a2-st-ic{background:var(--grad);color:#fff;transform:rotate(-6deg)}
.a2-st b{font-family:var(--display);font-size:24px;color:var(--blue-dark);display:block;line-height:1}
.a2-st small{color:#7b8aa3;font-size:11.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:4px;display:block}
.a2-st.c-blue{--c:#0a66c2}.a2-st.c-green{--c:#00b874}.a2-st.c-amber{--c:#f59e0b}.a2-st.c-purple{--c:#8b5cf6}

/* Toolbar */
.a2-toolbar{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.a2-search{position:relative;flex:1;min-width:220px}
.a2-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9aa8bf;font-size:13px}
.a2-search input{width:100%;padding:10px 13px 10px 38px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;outline:0;transition:.25s}
.a2-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1)}
.a2-select{padding:10px 12px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;font-weight:600;color:#33415e;background:#fbfcfe;outline:0;cursor:pointer}
.a2-select:focus{border-color:var(--blue)}
.a2-view-btns{display:flex;background:var(--gray);border-radius:9px;padding:3px;border:1px solid var(--line)}
.a2-view-btns button{border:0;background:transparent;padding:7px 12px;border-radius:7px;cursor:pointer;color:#7b8aa3;font-size:11.5px;font-weight:700;display:flex;align-items:center;gap:6px;transition:.25s}
.a2-view-btns button.on{background:#fff;color:var(--blue);box-shadow:0 2px 6px rgba(0,0,0,.06)}
.a2-info{margin-left:auto;font-size:12.5px;color:#7b8aa3;font-weight:600}
.a2-info b{color:var(--blue-dark);font-family:var(--display)}

/* Bulk bar */
.a2-bulk{position:sticky;top:67px;z-index:40;background:linear-gradient(135deg,#0a2a5e,#071a39);color:#fff;border-radius:14px;padding:12px 18px;margin-bottom:16px;display:none;align-items:center;gap:12px;box-shadow:0 10px 30px rgba(10,42,94,.25)}
.a2-bulk.show{display:flex;animation:a2zin .3s}
.a2-bulk b{font-family:var(--display)}
.a2-bulk .a2-bb{border:0;background:rgba(255,255,255,.12);color:#fff;padding:8px 14px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.25s}
.a2-bulk .a2-bb:hover{background:#fff;color:var(--blue-dark)}
.a2-bulk .a2-bb.danger{background:#e5484d}
.a2-bulk .a2-bb.danger:hover{background:#fff;color:#b3261e}
.a2-bulk .x{margin-left:auto;background:transparent;border:0;color:#fff;font-size:18px;cursor:pointer}

/* Grid kartu berita */
.a2-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.a2-news{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;position:relative;transition:.3s;display:flex;flex-direction:column}
.a2-news:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(10,42,94,.14);border-color:rgba(0,184,116,.3)}
.a2-news .a2-img{position:relative;height:170px;background:var(--gray);overflow:hidden}
.a2-news .a2-img img{width:100%;height:100%;object-fit:cover;transition:.6s}
.a2-news:hover .a2-img img{transform:scale(1.08)}
.a2-news .a2-img .a2-empty-img{position:absolute;inset:0;display:grid;place-items:center;color:#cfd8e3;font-size:40px}
.a2-news .a2-ck{position:absolute;top:10px;left:10px;width:24px;height:24px;border-radius:7px;background:rgba(255,255,255,.92);display:grid;place-items:center;cursor:pointer;border:2px solid transparent;transition:.2s;z-index:2}
.a2-news .a2-ck input{margin:0;cursor:pointer}
.a2-news.selected .a2-ck{background:var(--grad);border-color:#fff}
.a2-news.selected{outline:2px solid var(--green)}
.a2-news .a2-cat{position:absolute;top:10px;right:10px;background:rgba(7,26,57,.85);backdrop-filter:blur(10px);color:#fff;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.6px}
.a2-news .a2-trending{position:absolute;bottom:10px;right:10px;background:#e5484d;color:#fff;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.8px;display:flex;align-items:center;gap:5px}
.a2-news .a2-trending i{animation:a2beat 1.5s infinite}
.a2-news-body{padding:18px;flex:1;display:flex;flex-direction:column}
.a2-news-body h4{font-family:var(--display);font-size:15px;line-height:1.45;color:var(--blue-dark);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:44px}
.a2-news-meta{display:flex;gap:10px;font-size:11.5px;color:#7b8aa3;font-weight:600;margin-bottom:12px;flex-wrap:wrap}
.a2-news-meta i{color:var(--green);margin-right:4px}
.a2-news-excerpt{font-size:12.5px;color:#68758a;line-height:1.65;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:14px;min-height:34px}
.a2-news-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px dashed var(--line);margin-top:auto}
.a2-status-chip{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;letter-spacing:.6px;padding:4px 10px;border-radius:99px}
.a2-status-chip.ok{background:#e7f8f1;color:#0a7a52}
.a2-status-chip.ok::before{content:'';width:6px;height:6px;border-radius:50%;background:#00b874}
.a2-status-chip.no{background:#fdecec;color:#b3261e}
.a2-status-chip.no::before{content:'';width:6px;height:6px;border-radius:50%;background:#e5484d}
.a2-acts{display:flex;gap:5px}
.a2-acts button,.a2-acts a{width:30px;height:30px;border-radius:8px;border:1.4px solid var(--line);background:#fff;color:#475569;display:grid;place-items:center;font-size:11.5px;cursor:pointer;transition:.2s;text-decoration:none}
.a2-acts a:hover,.a2-acts button:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}
.a2-acts .dng:hover{background:#e5484d;border-color:#e5484d}

/* List view */
.a2-list{display:flex;flex-direction:column;gap:10px}
.a2-list-item{background:#fff;border:1px solid var(--line);border-radius:13px;padding:14px 18px;display:grid;grid-template-columns:auto 64px 1fr auto auto auto auto;gap:16px;align-items:center;transition:.25s}
.a2-list-item:hover{border-color:rgba(0,184,116,.3);transform:translateX(4px)}
.a2-list-item .li-img{width:64px;height:48px;border-radius:9px;background:var(--gray);overflow:hidden;object-fit:cover;display:grid;place-items:center;color:#cfd8e3}
.a2-list-item .li-img img{width:100%;height:100%;object-fit:cover}
.a2-list-item .li-body h4{font-family:var(--display);font-size:13.5px;color:var(--blue-dark);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
.a2-list-item .li-body small{color:#7b8aa3;font-size:11px;font-weight:600}
.a2-list-item .li-body small i{color:var(--green);margin-right:3px}

/* Editor premium */
.a2-edit-wrap{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
.a2-editor{background:#fff;border:1px solid var(--line);border-radius:16px;padding:28px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.a2-editor::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.a2-editor .a2-head-edit{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.a2-editor .a2-head-edit .a2-ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.a2-editor .a2-head-edit b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.a2-editor .a2-head-edit small{color:#7b8aa3;font-size:12px}
.a2-field{margin-bottom:18px}
.a2-field label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.a2-field label .req{color:#e5484d;margin-left:2px}
.a2-field label .tip{margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;letter-spacing:0;text-transform:none}
.a2-field label i{color:var(--green)}
.a2-field input,.a2-field select,.a2-field textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.a2-field input:focus,.a2-field select:focus,.a2-field textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.a2-field textarea{resize:vertical;line-height:1.75;min-height:360px}
.a2-slug-row{display:flex;gap:8px}
.a2-slug-row input{flex:1;font-family:'SFMono-Regular',Consolas,monospace;font-size:13px;color:var(--blue);background:#eef3fa}
.a2-slug-row button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:0 14px;border-radius:10px;cursor:pointer;font-size:11.5px;font-weight:700;transition:.2s;white-space:nowrap}
.a2-slug-row button:hover{background:var(--grad);color:#fff;border-color:transparent}

/* Image uploader kaya */
.a2-up-box{border:2px dashed var(--line);border-radius:14px;padding:16px;background:#fbfcfe;transition:.3s;position:relative;overflow:hidden}
.a2-up-box:hover{border-color:var(--green);background:#fff}
.a2-up-box.drag{border-color:var(--green);background:#e7f8f1}
.a2-up-box img{width:100%;max-height:260px;object-fit:cover;border-radius:10px;display:block;margin-bottom:10px}
.a2-up-ph{text-align:center;padding:30px 10px;color:#9aa8bf}
.a2-up-ph i{font-size:32px;color:var(--blue);margin-bottom:10px;display:block}
.a2-up-ph b{display:block;color:var(--blue-dark);margin-bottom:4px}
.a2-up-ph small{font-size:11.5px}
.a2-up-actions{display:flex;gap:8px;flex-wrap:wrap}
.a2-up-actions button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:8px 12px;border-radius:8px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.a2-up-actions button:hover{background:var(--grad);color:#fff;border-color:transparent}
.a2-up-actions button.dng:hover{background:#e5484d;border-color:#e5484d}

/* Sidebar editor */
.a2-side-ed{display:flex;flex-direction:column;gap:18px;position:sticky;top:90px}
.a2-card-ed{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.a2-card-ed h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--line);position:relative;display:flex;align-items:center;gap:8px}
.a2-card-ed h5::after{content:'';position:absolute;left:0;bottom:-2px;width:32px;height:2px;background:var(--grad)}
.a2-card-ed h5 i{color:var(--green)}
.a2-stat-line{display:flex;justify-content:space-between;align-items:center;padding:7px 0;font-size:12.5px;color:#475569;font-weight:600}
.a2-stat-line b{color:var(--blue-dark);font-family:var(--display)}
.a2-stat-line i{color:var(--green);margin-right:7px;width:14px}
.a2-wpmeter{margin-top:8px;height:6px;background:var(--gray);border-radius:99px;overflow:hidden;position:relative}
.a2-wpmeter span{display:block;height:100%;background:var(--grad);border-radius:99px;transition:width .4s}
.a2-wp-legend{display:flex;justify-content:space-between;font-size:10.5px;color:#9aa8bf;margin-top:6px;font-weight:600}

.a2-actions-big{display:flex;flex-direction:column;gap:8px}
.a2-actions-big .btn-primary{padding:13px;justify-content:center;font-size:13.5px}
.a2-actions-big .a2-sec-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.a2-actions-big .a2-sec-btn:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.a2-actions-big .a2-sec-btn.dng{color:#b3261e;border-color:#f5c2c0}
.a2-actions-big .a2-sec-btn.dng:hover{background:#fdecec;border-color:#b3261e}

/* Empty state */
.a2-empty{text-align:center;padding:60px 20px;background:#fff;border:2px dashed var(--line);border-radius:18px}
.a2-empty .a2-emp-ic{width:76px;height:76px;border-radius:20px;background:linear-gradient(135deg,rgba(10,102,194,.12),rgba(0,184,116,.12));display:grid;place-items:center;margin:0 auto 18px;font-size:30px;color:var(--blue)}
.a2-empty h3{font-family:var(--display);color:var(--blue-dark);margin-bottom:8px}
.a2-empty p{color:#7b8aa3;font-size:13.5px;margin-bottom:18px;max-width:380px;margin-left:auto;margin-right:auto}

/* Pagination elegan */
.a2-pag{display:flex;justify-content:center;gap:6px;margin-top:24px}
.a2-pag a,.a2-pag span{min-width:38px;height:38px;display:inline-grid;place-items:center;border-radius:10px;border:1.4px solid var(--line);background:#fff;color:#475569;font-weight:700;font-size:12.5px;transition:.2s;text-decoration:none;padding:0 10px}
.a2-pag a:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.a2-pag a.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 6px 14px rgba(0,184,116,.25)}

/* Modal */
.a2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.a2-modal.open{display:flex}
.a2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.a2-modal-body{padding:28px}
.a2-modal-body .a2-m-ic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.a2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;text-align:center;margin-bottom:10px}
.a2-modal-body p{color:#68758a;font-size:13.5px;text-align:center;line-height:1.65}
.a2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.a2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.a2-modal-foot button:hover{transform:translateY(-2px)}
.a2-modal-foot .a2-m-del{background:#b3261e;color:#fff;border-color:#b3261e}
.a2-modal-foot .a2-m-del:hover{background:#8b1d16;border-color:#8b1d16}

/* Preview modal */
.a2-pv-modal .a2-modal-box{width:min(820px,100%);max-height:88vh;display:flex;flex-direction:column}
.a2-pv-head{padding:18px 24px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:14px}
.a2-pv-head .pv-url{flex:1;font-family:monospace;font-size:12px;color:#7b8aa3;background:var(--gray);padding:8px 12px;border-radius:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.a2-pv-body{padding:30px;overflow-y:auto}
.a2-pv-body h1{font-family:var(--display);color:var(--blue-dark);font-size:28px;line-height:1.25;margin-bottom:16px}
.a2-pv-body .pv-meta{display:flex;gap:14px;color:#7b8aa3;font-size:12.5px;margin-bottom:24px;flex-wrap:wrap}
.a2-pv-body .pv-meta i{color:var(--green);margin-right:5px}
.a2-pv-body img{width:100%;border-radius:14px;margin-bottom:22px}
.a2-pv-body .pv-isi{line-height:1.9;color:#2b3a55;font-size:14.5px}
.a2-pv-body .pv-isi p{margin-bottom:14px}

@media(max-width:1100px){.a2-edit-wrap{grid-template-columns:1fr}.a2-side-ed{position:static}.a2-stats-row{grid-template-columns:1fr 1fr}}
@media(max-width:860px){.a2-grid{grid-template-columns:1fr 1fr}.a2-list-item{grid-template-columns:auto 1fr auto;gap:10px}.a2-list-item .li-img{display:none}}
@media(max-width:600px){.a2-grid{grid-template-columns:1fr}.a2-stats-row{grid-template-columns:1fr 1fr}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<?php if ($edit): ?>
<!-- ===== EDITOR ===== -->
<form method="post" enctype="multipart/form-data" class="a2-edit-wrap" id="fBerita">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
  <input type="hidden" name="gambar_lama" value="<?= e($edit['gambar']) ?>">

  <div class="a2-editor">
    <div class="a2-head-edit">
      <span class="a2-ic"><i class="fa-solid fa-newspaper"></i></span>
      <div>
        <b><?= $edit['id'] ? 'Edit Artikel Berita' : 'Tulis Artikel Baru' ?></b>
        <small><?= $edit['id'] ? 'Memperbarui artikel #' . $edit['id'] : 'Buat artikel baru untuk dipublikasikan' ?></small>
      </div>
    </div>

    <div class="a2-field">
      <label><i class="fa-solid fa-heading"></i> Judul Artikel <span class="req">*</span> <span class="tip" id="titleCount">0 / 120 karakter</span></label>
      <input name="judul" id="fJudul" value="<?= e($edit['judul']) ?>" placeholder="Contoh: Tim Fisika Raih Juara Kompetisi Nasional 2026" required maxlength="120">
    </div>

    <div class="a2-field">
      <label><i class="fa-solid fa-link"></i> URL Slug <span class="tip">Otomatis dari judul</span></label>
      <div class="a2-slug-row">
        <input name="slug" id="fSlug" value="<?= e($edit['slug']) ?>" placeholder="otomatis-dari-judul">
        <button type="button" onclick="generateSlug()" title="Generate ulang"><i class="fa-solid fa-arrows-rotate"></i> Generate</button>
        <button type="button" onclick="copySlug()" title="Salin"><i class="fa-regular fa-copy"></i> Salin</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
      <div class="a2-field">
        <label><i class="fa-solid fa-tag"></i> Kategori</label>
        <input name="kategori" id="fKategori" value="<?= e($edit['kategori']) ?>" placeholder="Prestasi, Kegiatan, Akademik..." list="katList">
        <datalist id="katList">
          <?php foreach ($kats as $k): ?><option value="<?= e($k['kategori']) ?>"><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</option><?php endforeach; ?>
        </datalist>
      </div>
      <div class="a2-field">
        <label><i class="fa-regular fa-calendar-days"></i> Tanggal</label>
        <input type="date" name="tanggal" value="<?= e($edit['tanggal']) ?>">
      </div>
      <div class="a2-field">
        <label><i class="fa-solid fa-signal"></i> Status</label>
        <select name="status">
          <option value="1" <?= $edit['status'] ? 'selected' : '' ?>>✓ Tayang (Publik)</option>
          <option value="0" <?= !$edit['status'] ? 'selected' : '' ?>>⏳ Draft (Belum tayang)</option>
        </select>
      </div>
    </div>

    <div class="a2-field">
      <label><i class="fa-solid fa-image"></i> Gambar Utama <span class="tip">Disarankan 1200×630 px</span></label>
      <div class="a2-up-box" id="upBox">
        <img id="pvBerita" class="<?= $edit['gambar'] ? '' : 'hidden' ?>" src="<?= $edit['gambar'] ? e(media_url($edit['gambar'])) : '' ?>" alt="">
        <div class="a2-up-ph <?= $edit['gambar'] ? 'hidden' : '' ?>" id="upPh">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <b>Klik atau seret gambar ke sini</b>
          <small>Format: JPG, PNG, WebP • Maks 5 MB</small>
        </div>
        <input type="file" name="gambar" id="fGambar" accept="image/*" style="display:none">
      </div>
      <div class="a2-up-actions" style="margin-top:10px">
        <button type="button" onclick="document.getElementById('fGambar').click()"><i class="fa-solid fa-upload"></i> Pilih Gambar</button>
        <button type="button" class="dng" onclick="removeImg()" <?= $edit['gambar'] ? '' : 'disabled' ?>><i class="fa-solid fa-trash"></i> Hapus</button>
      </div>
    </div>

    <div class="a2-field">
      <label><i class="fa-solid fa-align-left"></i> Isi Berita <span class="req">*</span> <span class="tip" id="wordCount">0 kata • 0 menit baca</span></label>
      <textarea name="isi" id="fIsi" placeholder="Tulis isi berita di sini. Anda dapat menggunakan tag HTML dasar: <p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>, <a>"><?= e($edit['isi']) ?></textarea>
    </div>
  </div>

  <!-- SIDEBAR EDITOR -->
  <aside class="a2-side-ed">
    <div class="a2-card-ed">
      <h5><i class="fa-solid fa-gauge-high"></i> Ringkasan Artikel</h5>
      <div class="a2-stat-line"><span><i class="fa-solid fa-font"></i> Judul</span><b id="stTitle">—</b></div>
      <div class="a2-stat-line"><span><i class="fa-solid fa-pen-nib"></i> Penulis</span><b><?= e($_SESSION['admin_name']) ?></b></div>
      <div class="a2-stat-line"><span><i class="fa-solid fa-font"></i> Kata</span><b id="stWords">0</b></div>
      <div class="a2-stat-line"><span><i class="fa-regular fa-clock"></i> Waktu Baca</span><b id="stRead">0 mnt</b></div>
      <div class="a2-wpmeter"><span id="wpBar" style="width:0%"></span></div>
      <div class="wp-legend"><span>Pendek</span><span>Ideal: 600–1200</span><span>Panjang</span></div>
    </div>

    <div class="a2-card-ed">
      <h5><i class="fa-solid fa-bullhorn"></i> Optimasi SEO</h5>
      <div class="a2-stat-line"><span><i class="fa-solid fa-heading"></i> Judul SEO</span><b id="seoTitle" style="color:var(--green)">—</b></div>
      <div class="a2-stat-line"><span><i class="fa-solid fa-link"></i> Slug</span><b id="seoSlug" style="font-family:monospace;font-size:11px">—</b></div>
      <div class="a2-stat-line"><span><i class="fa-solid fa-font"></i> Panjang Judul</span><b id="seoLen">0</b></div>
      <div class="a2-stat-line"><span><i class="fa-solid fa-image"></i> Gambar</span><b id="seoImg" style="color:#b3261e">Belum ada</b></div>
    </div>

    <div class="a2-card-ed">
      <h5><i class="fa-solid fa-bolt"></i> Aksi</h5>
      <div class="a2-actions-big">
        <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $edit['id'] ? 'Perbarui Artikel' : 'Simpan Artikel' ?></button>
        <button type="button" class="a2-sec-btn" onclick="openPreview()"><i class="fa-solid fa-eye"></i> Pratinjau Artikel</button>
        <a class="a2-sec-btn" href="<?= url('admin/berita.php') ?>"><i class="fa-solid fa-xmark"></i> Batal / Kembali</a>
        <?php if ($edit['id']): ?>
        <button type="button" class="a2-sec-btn dng" onclick="confirmDel(<?= (int)$edit['id'] ?>,'<?= e(addslashes($edit['judul'])) ?>')"><i class="fa-solid fa-trash"></i> Hapus Artikel</button>
        <?php endif; ?>
      </div>
    </div>
  </aside>
</form>

<?php else: ?>
<!-- ===== DAFTAR ===== -->
<div class="a2-stats-row">
  <div class="a2-st c-blue"><span class="a2-st-ic"><i class="fa-solid fa-newspaper"></i></span><div><b><?= $statTotal ?></b><small>Total Artikel</small></div></div>
  <div class="a2-st c-green"><span class="a2-st-ic"><i class="fa-solid fa-circle-check"></i></span><div><b><?= $statTayang ?></b><small>Sudah Tayang</small></div></div>
  <div class="a2-st c-amber"><span class="a2-st-ic"><i class="fa-solid fa-file-circle-exclamation"></i></span><div><b><?= $statDraft ?></b><small>Masih Draft</small></div></div>
  <div class="a2-st c-purple"><span class="a2-st-ic"><i class="fa-solid fa-eye"></i></span><div><b><?= number_format($statViews) ?></b><small>Total Dibaca</small></div></div>
</div>

<div class="a2-toolbar">
  <form method="get" class="a2-search" style="display:flex;gap:8px">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="q" value="<?= e($fQ) ?>" placeholder="Cari judul, kategori, atau isi...">
  </form>
  <select class="a2-select" onchange="location.href='berita.php?kat='+this.value+'<?= $fQ ? '&q='.urlencode($fQ) : '' ?><?= $fStat !== '' ? '&st='.urlencode($fStat) : '' ?>'">
    <option value="">Semua Kategori</option>
    <?php foreach ($kats as $k): ?>
    <option value="<?= e($k['kategori']) ?>" <?= $fKat === $k['kategori'] ? 'selected' : '' ?>><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</option>
    <?php endforeach; ?>
  </select>
  <select class="a2-select" onchange="location.href='berita.php?st='+this.value+'<?= $fQ ? '&q='.urlencode($fQ) : '' ?><?= $fKat ? '&kat='.urlencode($fKat) : '' ?>'">
    <option value="">Semua Status</option>
    <option value="1" <?= $fStat === '1' ? 'selected' : '' ?>>Sudah Tayang</option>
    <option value="0" <?= $fStat === '0' ? 'selected' : '' ?>>Masih Draft</option>
  </select>
  <div class="a2-view-btns">
    <button class="<?= $view === 'grid' ? 'on' : '' ?>" onclick="location.href='berita.php?view=grid'"><i class="fa-solid fa-grip"></i> Grid</button>
    <button class="<?= $view === 'list' ? 'on' : '' ?>" onclick="location.href='berita.php?view=list'"><i class="fa-solid fa-list"></i> List</button>
  </div>
  <span class="a2-info">Menampilkan <b><?= count(qp($conn, "SELECT id FROM berita$where", $types, $params)) ?></b> artikel</span>
</div>

<form method="post" id="bulkForm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="a2-bulk" id="bulkBar">
    <b><span id="bulkCount">0</span> artikel dipilih</b>
    <button type="submit" name="bulk" value="publish" class="a2-bb"><i class="fa-solid fa-circle-check"></i> Terbitkan</button>
    <button type="submit" name="bulk" value="draft" class="a2-bb"><i class="fa-solid fa-file-circle-exclamation"></i> Jadikan Draft</button>
    <button type="submit" name="bulk" value="delete" class="a2-bb danger" onclick="return confirm('Hapus semua artikel yang dipilih?')"><i class="fa-solid fa-trash"></i> Hapus</button>
    <button type="button" class="x" onclick="clearBulk()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <?php
  $list = qp($conn, "SELECT * FROM berita$where ORDER BY tanggal DESC, id DESC", $types, $params);
  if (!$list): ?>
    <div class="a2-empty">
      <div class="a2-emp-ic"><i class="fa-solid fa-newspaper"></i></div>
      <h3>Belum ada artikel</h3>
      <p><?= $fQ || $fKat || $fStat !== '' ? 'Tidak ada artikel yang cocok dengan filter Anda. Coba ubah filter atau reset pencarian.' : 'Mulai dengan menulis artikel pertama Anda. Setiap artikel akan tampil di halaman berita public.' ?></p>
      <?php if ($fQ || $fKat || $fStat !== ''): ?>
      <a href="berita.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Reset Filter</a>
      <?php else: ?>
      <a href="?edit=0" class="btn-primary"><i class="fa-solid fa-plus"></i> Tulis Artikel Pertama</a>
      <?php endif; ?>
    </div>
  <?php else: ?>

  <?php if ($view === 'grid'): ?>
  <div class="a2-grid">
    <?php foreach ($list as $b): $rt = max(1, (int)round(str_word_count(strip_tags((string)$b['isi'])) / 200)); ?>
    <div class="a2-news" data-id="<?= (int)$b['id'] ?>">
      <label class="a2-ck"><input type="checkbox" name="ids[]" value="<?= (int)$b['id'] ?>" onchange="bulkUpdate()"></label>
      <div class="a2-img">
        <?php if ($b['gambar']): ?><img src="<?= e(media_url($b['gambar'])) ?>" alt="" loading="lazy"><?php else: ?><div class="a2-empty-img"><i class="fa-solid fa-image"></i></div><?php endif; ?>
        <span class="a2-cat"><?= e($b['kategori']) ?></span>
        <?php if ((int)$b['views'] > 100): ?><span class="a2-trending"><i class="fa-solid fa-fire"></i> TRENDING</span><?php endif; ?>
      </div>
      <div class="a2-news-body">
        <h4><?= e($b['judul']) ?></h4>
        <div class="a2-news-meta">
          <span><i class="fa-regular fa-calendar-days"></i><?= date('d M Y', strtotime($b['tanggal'])) ?></span>
          <span><i class="fa-regular fa-eye"></i><?= (int)$b['views'] ?></span>
          <span><i class="fa-regular fa-clock"></i><?= $rt ?> mnt</span>
        </div>
        <div class="a2-news-excerpt"><?= e(mb_strimwidth(trim(strip_tags($b['isi'])), 0, 120, '...')) ?></div>
        <div class="a2-news-foot">
          <span class="a2-status-chip <?= $b['status'] ? 'ok' : 'no' ?>"><?= $b['status'] ? 'Tayang' : 'Draft' ?></span>
          <div class="a2-acts">
            <a target="_blank" href="<?= url('berita-detail.php?slug=' . e($b['slug'])) ?>" title="Lihat di situs"><i class="fa-solid fa-globe"></i></a>
            <a href="?edit=<?= (int)$b['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
            <button type="button" onclick="confirmDel(<?= (int)$b['id'] ?>,'<?= e(addslashes($b['judul'])) ?>')" title="Hapus" class="dng"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="a2-list">
    <?php foreach ($list as $b): $rt = max(1, (int)round(str_word_count(strip_tags((string)$b['isi'])) / 200)); ?>
    <div class="a2-list-item" data-id="<?= (int)$b['id'] ?>">
      <label style="display:grid;place-items:center;width:24px;height:24px;cursor:pointer"><input type="checkbox" name="ids[]" value="<?= (int)$b['id'] ?>" onchange="bulkUpdate()" style="margin:0;cursor:pointer"></label>
      <div class="li-img">
        <?php if ($b['gambar']): ?><img src="<?= e(media_url($b['gambar'])) ?>" alt="" loading="lazy"><?php else: ?><i class="fa-solid fa-image"></i><?php endif; ?>
      </div>
      <div class="li-body">
        <h4><?= e($b['judul']) ?></h4>
        <small><i class="fa-solid fa-tag"></i><?= e($b['kategori']) ?> • <i class="fa-regular fa-user"></i><?= e($b['penulis']) ?> • <i class="fa-regular fa-clock"></i><?= $rt ?> mnt</small>
      </div>
      <span class="a2-status-chip <?= $b['status'] ? 'ok' : 'no' ?>"><?= $b['status'] ? 'Tayang' : 'Draft' ?></span>
      <span style="font-size:11.5px;color:#7b8aa3;font-weight:600;white-space:nowrap"><i class="fa-regular fa-calendar-days" style="color:var(--green);margin-right:5px"></i><?= date('d M Y', strtotime($b['tanggal'])) ?></span>
      <span style="font-size:11.5px;color:#7b8aa3;font-weight:600;white-space:nowrap"><i class="fa-regular fa-eye" style="color:var(--green);margin-right:5px"></i><?= (int)$b['views'] ?></span>
      <div class="a2-acts">
        <a target="_blank" href="<?= url('berita-detail.php?slug=' . e($b['slug'])) ?>" title="Lihat"><i class="fa-solid fa-globe"></i></a>
        <a href="?edit=<?= (int)$b['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
        <button type="button" onclick="confirmDel(<?= (int)$b['id'] ?>,'<?= e(addslashes($b['judul'])) ?>')" title="Hapus" class="dng"><i class="fa-solid fa-trash"></i></button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</form>

<!-- Tambah artikel floating -->
<a href="?edit=0" style="position:fixed;right:30px;bottom:30px;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 14px 30px rgba(0,184,116,.4);transition:.3s;z-index:100;text-decoration:none" onmouseover="this.style.transform='translateY(-4px) scale(1.08)'" onmouseout="this.style.transform=''">
  <i class="fa-solid fa-plus"></i>
</a>

<?php endif; ?>

<!-- Modal konfirmasi hapus -->
<div class="a2-modal" id="delModal">
  <div class="a2-modal-box">
    <div class="a2-modal-body">
      <div class="a2-m-ic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Hapus Artikel Ini?</h3>
      <p id="delText">Artikel akan dihapus permanen dan tidak dapat dikembalikan.</p>
    </div>
    <form method="post" class="a2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="delId" value="0">
      <button type="button" onclick="document.getElementById('delModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="hapus" value="1" class="a2-m-del"><i class="fa-solid fa-trash"></i> Ya, Hapus</button>
    </form>
  </div>
</div>

<!-- Modal pratinjau -->
<div class="a2-modal a2-pv-modal" id="pvModal">
  <div class="a2-modal-box">
    <div class="a2-pv-head">
      <i class="fa-solid fa-globe" style="color:var(--green);font-size:17px"></i>
      <span class="pv-url" id="pvUrl">—</span>
      <button type="button" class="icon-btn" onclick="document.getElementById('pvModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="a2-pv-body" id="pvBody"></div>
  </div>
</div>

<script>
/* ========= BERITA ULTIMATE ========= */
(function(){
  /* Title counter */
  var t = document.getElementById('fJudul'), tc = document.getElementById('titleCount'), stT = document.getElementById('stTitle');
  if (t && tc) {
    var upd = function(){
      var n = t.value.length;
      tc.textContent = n + ' / 120 karakter';
      tc.style.color = n > 100 ? '#e5484d' : '#9aa8bf';
      if (stT) stT.textContent = t.value ? (t.value.length > 18 ? t.value.slice(0,18)+'…' : t.value) : '—';
      var seoT = document.getElementById('seoTitle');
      var seoL = document.getElementById('seoLen');
      if (seoT) seoT.textContent = t.value ? (n >= 30 && n <= 70 ? '✓ Ideal' : n < 30 ? 'Terlalu pendek' : 'Terlalu panjang') : '—';
      if (seoT) seoT.style.color = (n >= 30 && n <= 70) ? 'var(--green)' : '#f59e0b';
      if (seoL) seoL.textContent = n;
    };
    t.addEventListener('input', upd); upd();
  }

  /* Slug auto */
  var s = document.getElementById('fSlug'), seoSl = document.getElementById('seoSlug');
  if (t && s) {
    t.addEventListener('input', function(){
      if (!s.dataset.locked) generateSlug();
    });
    s.addEventListener('input', function(){ s.dataset.locked = '1'; });
  }
  window.generateSlug = function(){
    if (!t || !s) return;
    s.value = t.value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    s.dataset.locked = '';
    if (seoSl) seoSl.textContent = s.value || '—';
  };
  window.copySlug = function(){
    if (!s) return;
    navigator.clipboard && navigator.clipboard.writeText(s.value).then(function(){
      if (window.a2Toast) a2Toast('Slug disalin!');
    });
  };
  if (seoSl && s) seoSl.textContent = s.value || '—';

  /* Word counter */
  var is = document.getElementById('fIsi'), wc = document.getElementById('wordCount'), stW = document.getElementById('stWords'), stR = document.getElementById('stRead'), wpB = document.getElementById('wpBar');
  if (is && wc) {
    var upd = function(){
      var txt = is.value.replace(/<[^>]+>/g,' ');
      var w = txt.trim().split(/\s+/).filter(Boolean).length;
      var m = Math.max(1, Math.round(w / 200));
      wc.textContent = w + ' kata • ' + m + ' menit baca';
      if (stW) stW.textContent = w;
      if (stR) stR.textContent = m + ' mnt';
      if (wpB) {
        var pct = Math.min(100, (w / 1200) * 100);
        wpB.style.width = pct + '%';
        wpB.style.background = pct < 30 ? '#e5484d' : (pct > 100 ? '#f59e0b' : 'var(--grad)');
      }
    };
    is.addEventListener('input', upd); upd();
  }

  /* Image uploader */
  var upBox = document.getElementById('upBox'), fGambar = document.getElementById('fGambar'), pv = document.getElementById('pvBerita'), upPh = document.getElementById('upPh'), seoImg = document.getElementById('seoImg');
  if (upBox && fGambar) {
    upBox.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG') fGambar.click(); });
    ['dragenter','dragover'].forEach(function(ev){
      upBox.addEventListener(ev, function(e){ e.preventDefault(); upBox.classList.add('drag'); });
    });
    ['dragleave','drop'].forEach(function(ev){
      upBox.addEventListener(ev, function(e){ e.preventDefault(); upBox.classList.remove('drag'); });
    });
    upBox.addEventListener('drop', function(e){
      if (e.dataTransfer.files[0]) { fGambar.files = e.dataTransfer.files; previewFile(); }
    });
    fGambar.addEventListener('change', previewFile);
    function previewFile(){
      if (!fGambar.files[0]) return;
      var rd = new FileReader();
      rd.onload = function(ev){
        pv.src = ev.target.result; pv.classList.remove('hidden');
        if (upPh) upPh.classList.add('hidden');
        if (seoImg) { seoImg.textContent = '✓ Ada'; seoImg.style.color = 'var(--green)'; }
      };
      rd.readAsDataURL(fGambar.files[0]);
    }
    if (pv && pv.src && seoImg) { seoImg.textContent = '✓ Ada'; seoImg.style.color = 'var(--green)'; }
  }
  window.removeImg = function(){
    if (!pv) return;
    if (!confirm('Hapus gambar?')) return;
    pv.src = ''; pv.classList.add('hidden');
    if (upPh) upPh.classList.remove('hidden');
    if (fGambar) fGambar.value = '';
    if (seoImg) { seoImg.textContent = 'Belum ada'; seoImg.style.color = '#b3261e'; }
  };

  /* Bulk */
  window.bulkUpdate = function(){
    var cks = document.querySelectorAll('#bulkForm input[type=checkbox]:checked');
    var bar = document.getElementById('bulkBar'), cnt = document.getElementById('bulkCount');
    if (bar && cnt) {
      cnt.textContent = cks.length;
      bar.classList.toggle('show', cks.length > 0);
    }
  };
  window.clearBulk = function(){
    document.querySelectorAll('#bulkForm input[type=checkbox]:checked').forEach(function(c){ c.checked = false; });
    bulkUpdate();
  };

  /* Delete modal */
  window.confirmDel = function(id, judul){
    document.getElementById('delId').value = id;
    document.getElementById('delText').textContent = 'Artikel "' + judul + '" akan dihapus permanen dan tidak dapat dikembalikan.';
    document.getElementById('delModal').classList.add('open');
  };
  document.getElementById('delModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });

  /* Preview */
  window.openPreview = function(){
    var judul = document.getElementById('fJudul').value || '(Belum ada judul)';
    var kategori = document.getElementById('fKategori').value || 'Umum';
    var isi = document.getElementById('fIsi').value || '(Belum ada isi)';
    var gambar = document.getElementById('pvBerita').src;
    var hasImg = gambar && !document.getElementById('pvBerita').classList.contains('hidden');
    var html = '<h1>' + judul + '</h1>';
    html += '<div class="pv-meta"><span><i class="fa-solid fa-tag"></i>' + kategori + '</span><span><i class="fa-regular fa-user"></i><?= e($_SESSION['admin_name']) ?></span><span><i class="fa-regular fa-calendar-days"></i><?= date('d F Y') ?></span></div>';
    if (hasImg) html += '<img src="' + gambar + '">';
    html += '<div class="pv-isi">' + isi.replace(/\n/g,'<br>') + '</div>';
    document.getElementById('pvBody').innerHTML = html;
    document.getElementById('pvUrl').textContent = window.location.origin + '/berita-detail.php?slug=' + (document.getElementById('fSlug').value || '...');
    document.getElementById('pvModal').classList.add('open');
  };
  document.getElementById('pvModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });

  /* ESC menutup modal */
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      document.querySelectorAll('.a2-modal.open').forEach(function(m){ m.classList.remove('open'); });
    }
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>