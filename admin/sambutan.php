<?php
$ACTIVE = 'sambutan.php'; $TITLE = 'Sambutan Kaprodi';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $foto = upload_file('foto', 'kaprodi') ?? $_POST['foto_lama'];
        $stmt = $conn->prepare("UPDATE sambutan SET nama=?,jabatan=?,foto=?,isi=?,tanda_tangan=? WHERE id=1");
        $stmt->bind_param('sssss', $_POST['nama'], $_POST['jabatan'], $foto, $_POST['isi'], $_POST['tanda_tangan']);
        $stmt->execute();
        $msg = 'Sambutan Kaprodi berhasil diperbarui.';
    }
    if (isset($_POST['preset'])) {
        $templates = [
            'default' => "Assalamu'alaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua.\n\nSelamat datang di website resmi Program Studi Fisika. Kami berkomitmen menghadirkan pendidikan sains yang unggul, riset yang berdampak, serta lingkungan akademik yang kolaboratif.\n\nSemoga website ini menjadi jendela informasi yang bermanfaat bagi mahasiswa, mitra, dan masyarakat luas.",
            'formal' => "Salam sejahtera,\n\nPuji syukur kami panjatkan ke hadirat Tuhan Yang Maha Esa atas hadirnya website resmi Program Studi Fisika.\n\nSebagai institusi pendidikan tinggi yang berfokus pada sains fisika, kami terus berupaya meningkatkan kualitas pendidikan, penelitian, dan pengabdian kepada masyarakat. Semoga platform digital ini dapat menjadi sarana komunikasi yang efektif antara civitas akademika dan para pemangku kepentingan.",
            'inspirasi' => "Selamat datang di rumah para ilmuwan fisika masa depan.\n\nFisika adalah bahasa alam semesta. Melalui website ini, kami mengajak Anda menjelajahi dunia sains yang tak terbatas — dari partikel terkecil hingga galaksi terjauh.\n\nMari bersama-sama kita cetak generasi penerus yang tidak hanya menguasai teori, tetapi juga mampu menghadirkan inovasi untuk kehidupan manusia."
        ];
        $key = $_POST['preset'];
        if (isset($templates[$key])) {
            $stmt = $conn->prepare("UPDATE sambutan SET isi=? WHERE id=1");
            $stmt->bind_param('s', $templates[$key]);
            $stmt->execute();
            $msg = 'Template sambutan diterapkan.';
        }
    }
}

$sb = q1($conn, "SELECT * FROM sambutan LIMIT 1") ?? ['nama'=>'','jabatan'=>'','foto'=>'','isi'=>'','tanda_tangan'=>''];
$wordCount = str_word_count(strip_tags((string)$sb['isi']));
$readTime = max(1, (int)round($wordCount / 150));

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= SAMBUTAN KAPRODI ULTIMATE ========= */
@keyframes s2spin{to{transform:rotate(360deg)}}
@keyframes s2float{50%{transform:translateY(-8px)}}
@keyframes s2fade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

/* Hero sambutan */
.s2-hero{background:linear-gradient(135deg,#071a39 0%,#0a2a5e 50%,#0a66c2 100%);border-radius:22px;padding:32px;color:#fff;position:relative;overflow:hidden;margin-bottom:24px;box-shadow:0 30px 70px rgba(10,42,94,.3)}
.s2-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%,rgba(0,184,116,.2),transparent 45%)}
.s2-hero::after{content:'\f5d2';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:-30px;bottom:-50px;font-size:240px;color:rgba(255,255,255,.04);animation:s2spin 50s linear infinite}
.s2-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr 300px;gap:30px;align-items:center}
.s2-hero h2{font-family:var(--display);font-size:26px;margin-bottom:10px}
.s2-hero h2 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.s2-hero p{color:#c8d6ea;font-size:14px;line-height:1.75;margin-bottom:18px;max-width:520px}
.s2-hero-stats{display:flex;gap:12px;flex-wrap:wrap}
.s2-hero-stats .hs{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);padding:10px 14px;border-radius:11px;border:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:10px}
.s2-hero-stats .hs i{color:#7ef0c4;font-size:13px}
.s2-hero-stats .hs b{font-family:var(--display);font-size:15px;color:#fff;display:block}
.s2-hero-stats .hs small{color:#9fb3cd;font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase}

/* Avatar preview */
.s2-avatar{width:240px;height:240px;margin:0 auto;position:relative}
.s2-avatar-ring{position:absolute;inset:-10px;border:2px dashed rgba(0,184,116,.5);border-radius:50%;animation:s2spin 20s linear infinite}
.s2-avatar-ring.r2{inset:-22px;border-color:rgba(77,163,255,.35);animation-direction:reverse;animation-duration:30s}
.s2-avatar .av-img{width:100%;height:100%;border-radius:50%;overflow:hidden;background:linear-gradient(135deg,#00b874,#4da3ff);display:grid;place-items:center;color:#fff;font-size:70px;border:4px solid rgba(255,255,255,.2);box-shadow:0 20px 50px rgba(0,0,0,.4);position:relative;z-index:2}
.s2-avatar .av-img img{width:100%;height:100%;object-fit:cover}
.s2-avatar .av-name{position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);background:rgba(0,184,116,.95);color:#04231a;padding:6px 16px;border-radius:99px;font-size:11px;font-weight:800;letter-spacing:1px;white-space:nowrap;max-width:260px;overflow:hidden;text-overflow:ellipsis;border:2px solid #071a39;z-index:3}

/* Layout */
.s2-grid{display:grid;grid-template-columns:1fr 440px;gap:22px;align-items:start}
.s2-form{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.s2-form::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.s2-form .s2-h{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px dashed var(--line)}
.s2-form .s2-h .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:17px;flex:none}
.s2-form .s2-h b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.s2-form .s2-h small{color:#7b8aa3;font-size:12px;display:block;margin-top:3px}
.s2-fld{margin-bottom:16px}
.s2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.s2-fld label .req{color:#e5484d;margin-left:2px}
.s2-fld label i{color:var(--green)}
.s2-fld label .tip{margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;letter-spacing:0;text-transform:none}
.s2-fld input,.s2-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.s2-fld input:focus,.s2-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}

/* Editor toolbar */
.s2-edbar{display:flex;gap:4px;flex-wrap:wrap;padding:8px;background:var(--gray);border:1.6px solid var(--line);border-bottom:0;border-radius:11px 11px 0 0}
.s2-edbar button{border:1.2px solid transparent;background:transparent;color:#475569;min-width:32px;height:30px;padding:0 9px;border-radius:7px;cursor:pointer;font-size:12px;font-weight:700;transition:.2s;display:inline-flex;align-items:center;justify-content:center;gap:5px;font-family:var(--body)}
.s2-edbar button:hover{background:#fff;border-color:var(--line);color:var(--blue);transform:translateY(-1px)}
.s2-edbar .sep{width:1px;background:var(--line);margin:3px 4px}
.s2-fld textarea{border-radius:0 0 11px 11px;min-height:340px;line-height:1.8;resize:vertical;font-family:'SFMono-Regular',Consolas,monospace;font-size:13px}

/* Word counter */
.s2-wc{display:flex;gap:12px;margin-top:8px;flex-wrap:wrap}
.s2-wc span{display:inline-flex;align-items:center;gap:6px;background:var(--gray);border:1px solid var(--line);padding:5px 12px;border-radius:99px;font-size:11px;font-weight:700;color:#475569}
.s2-wc span i{color:var(--green);font-size:11px}
.s2-wc span b{color:var(--blue-dark);font-family:var(--display)}
.s2-wc-meter{flex:1;min-width:140px;display:flex;align-items:center;gap:8px;background:var(--gray);border:1px solid var(--line);padding:5px 12px;border-radius:99px}
.s2-wc-meter .bar{flex:1;height:5px;background:var(--line);border-radius:99px;overflow:hidden}
.s2-wc-meter .bar span{display:block;height:100%;background:var(--grad);transition:width .4s}
.s2-wc-meter small{font-size:10px;color:#7b8aa3;font-weight:700;white-space:nowrap}

/* Photo upload */
.s2-up{border:2px dashed var(--line);border-radius:14px;padding:16px;background:#fbfcfe;transition:.3s;text-align:center;position:relative;overflow:hidden}
.s2-up:hover,.s2-up.drag{border-color:var(--green);background:#fff}
.s2-up img{max-width:100%;max-height:200px;border-radius:10px;display:block;margin:0 auto}
.s2-up .ph{color:#9aa8bf;padding:22px 10px}
.s2-up .ph i{font-size:30px;color:var(--blue);margin-bottom:10px;display:block}
.s2-up .ph b{display:block;color:var(--blue-dark);margin-bottom:4px}
.s2-up .ph small{font-size:11.5px}
.s2-up input{display:none}
.s2-up-actions{display:flex;gap:6px;justify-content:center;margin-top:12px;flex-wrap:wrap}
.s2-up-actions button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:8px 14px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.s2-up-actions button:hover{background:var(--grad);color:#fff;border-color:transparent}
.s2-up-actions .dng:hover{background:#e5484d;border-color:#e5484d}

/* Templates */
.s2-tpl{margin-top:18px;background:linear-gradient(135deg,#f8fafd,#eef4fb);border:1.4px solid var(--line);border-radius:14px;padding:16px}
.s2-tpl h5{font-family:var(--display);font-size:13px;color:var(--blue-dark);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.s2-tpl h5 i{color:var(--green)}
.s2-tpl h5 small{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:600}
.s2-tpl-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.s2-tpl-item{background:#fff;border:1.4px solid var(--line);border-radius:10px;padding:12px;cursor:pointer;transition:.25s;text-align:left}
.s2-tpl-item:hover{border-color:var(--green);transform:translateY(-3px);box-shadow:0 10px 22px rgba(0,184,116,.15)}
.s2-tpl-item b{display:block;font-family:var(--display);color:var(--blue-dark);font-size:12.5px;margin-bottom:4px}
.s2-tpl-item p{color:#7b8aa3;font-size:11px;line-height:1.5;margin:0}

/* Preview card */
.s2-preview{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.s2-pv-title{font-family:var(--display);font-size:12px;color:var(--blue-dark);padding:10px 14px;background:var(--gray);border-radius:10px;display:flex;align-items:center;gap:8px;font-weight:800;letter-spacing:1px}
.s2-pv-title i{color:var(--green)}
.s2-pv-title .tools{margin-left:auto;display:flex;gap:5px}
.s2-pv-title .tools button{border:1.2px solid var(--line);background:#fff;color:#475569;width:26px;height:26px;border-radius:7px;cursor:pointer;font-size:10.5px;transition:.2s;display:grid;place-items:center}
.s2-pv-title .tools button:hover{background:var(--grad);color:#fff;border-color:transparent}
.s2-pv-title .tools button.on{background:var(--grad);color:#fff;border-color:transparent}

/* Live preview card */
.s2-pvcard{background:#fff;border:1.4px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(10,42,94,.14);transition:.35s}
.s2-pvcard:hover{transform:translateY(-5px)}
.s2-pvcard .photo-frame{position:relative;height:320px;overflow:hidden;background:linear-gradient(135deg,#0a2a5e,#071a39);display:grid;place-items:center}
.s2-pvcard .photo-frame img{width:100%;height:100%;object-fit:cover}
.s2-pvcard .photo-frame .ph{color:#7ef0c4;font-size:60px}
.s2-pvcard .orbit-ring{position:absolute;border:2px dashed rgba(0,184,116,.5);border-radius:50%;animation:s2spin 14s linear infinite}
.s2-pvcard .orbit-ring.r1{width:120px;height:120px;top:-38px;right:-38px}
.s2-pvcard .orbit-ring.r2{width:90px;height:90px;bottom:-28px;left:-28px;animation-direction:reverse}
.s2-pvcard .photo-badge{position:absolute;bottom:16px;left:16px;background:rgba(0,184,116,.95);color:#04231a;padding:7px 14px;border-radius:99px;font-size:11px;font-weight:800;letter-spacing:1px;display:flex;align-items:center;gap:7px}
.s2-pvcard .pv-body{padding:24px}
.s2-pvcard .sec-tag{display:inline-flex;gap:8px;color:var(--blue);font-weight:800;font-size:10.5px;letter-spacing:2px;margin-bottom:12px}
.s2-pvcard .sec-tag i{color:var(--green)}
.s2-pvcard h3{font-family:var(--display);color:var(--blue-dark);font-size:20px;margin-bottom:5px;min-height:26px}
.s2-pvcard .jab{color:var(--green);font-size:12.5px;font-weight:800;letter-spacing:.5px;margin-bottom:16px;display:block;min-height:18px}
.s2-pvcard .isi{color:#475569;font-size:13px;line-height:1.85;min-height:60px;max-height:180px;overflow-y:auto;white-space:pre-line;scrollbar-width:thin;padding-right:6px}
.s2-pvcard .isi::-webkit-scrollbar{width:5px}
.s2-pvcard .isi::-webkit-scrollbar-thumb{background:var(--grad);border-radius:99px}
.s2-pvcard .ttd{margin-top:18px;padding-top:14px;border-top:1px dashed var(--line);display:flex;align-items:center;gap:8px;color:var(--blue-dark);font-weight:800;font-size:12.5px}
.s2-pvcard .ttd i{color:var(--green)}

/* Actions */
.s2-actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.s2-actions .btn-primary{flex:1;min-width:160px;justify-content:center;padding:13px}
.s2-actions .sec{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;border:1.6px solid var(--line);background:#fff;color:#33415e;font-weight:700;font-size:12.5px;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none}
.s2-actions .sec:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}

/* Fullscreen preview modal */
.s2-modal{position:fixed;inset:0;background:rgba(7,26,57,.75);backdrop-filter:blur(8px);z-index:1500;display:none;align-items:center;justify-content:center;padding:4vh}
.s2-modal.open{display:flex}
.s2-modal-box{background:#fff;border-radius:22px;width:min(760px,95%);max-height:92vh;overflow:hidden;animation:a2zin .4s;box-shadow:0 40px 90px rgba(0,0,0,.5);display:flex;flex-direction:column}
.s2-modal-head{padding:18px 24px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#0a2a5e,#0a66c2);color:#fff}
.s2-modal-head i{color:#7ef0c4;font-size:16px}
.s2-modal-head b{font-family:var(--display);font-size:14px;letter-spacing:.5px}
.s2-modal-head .url{margin-left:auto;background:rgba(255,255,255,.1);padding:6px 12px;border-radius:8px;font-family:monospace;font-size:11px;color:#c8d6ea}
.s2-modal-head button{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;width:34px;height:34px;border-radius:9px;cursor:pointer;font-size:13px;transition:.2s;display:grid;place-items:center}
.s2-modal-head button:hover{background:rgba(255,255,255,.2)}
.s2-modal-body{overflow-y:auto;padding:36px;background:linear-gradient(180deg,#f8fafd,#fff);flex:1}
.s2-modal-body .full-photo{position:relative;height:360px;border-radius:16px;overflow:hidden;margin-bottom:26px;background:linear-gradient(135deg,#0a2a5e,#071a39);display:grid;place-items:center}
.s2-modal-body .full-photo img{width:100%;height:100%;object-fit:cover}
.s2-modal-body .full-photo .ph{color:#7ef0c4;font-size:80px}
.s2-modal-body .orbit-ring{position:absolute;border:2px dashed rgba(0,184,116,.5);border-radius:50%;animation:s2spin 14s linear infinite}
.s2-modal-body .orbit-ring.r1{width:160px;height:160px;top:-50px;right:-50px}
.s2-modal-body .orbit-ring.r2{width:130px;height:130px;bottom:-40px;left:-40px;animation-direction:reverse}
.s2-modal-body .tag{display:inline-flex;gap:8px;color:var(--blue);font-weight:800;font-size:11px;letter-spacing:2px;margin-bottom:14px}
.s2-modal-body .tag i{color:var(--green)}
.s2-modal-body h2{font-family:var(--display);color:var(--blue-dark);font-size:26px;margin-bottom:6px}
.s2-modal-body .jab{color:var(--green);font-weight:800;font-size:14px;letter-spacing:.5px;margin-bottom:22px;display:block}
.s2-modal-body .isi{color:#2b3a55;font-size:15px;line-height:2;white-space:pre-line}
.s2-modal-body .ttd{margin-top:30px;padding-top:18px;border-top:1px dashed var(--line);color:var(--blue-dark);font-weight:800;display:flex;align-items:center;gap:8px}
.s2-modal-body .ttd i{color:var(--green);font-size:16px}

@media(max-width:1100px){.s2-grid{grid-template-columns:1fr}.s2-preview{position:static}.s2-hero-inner{grid-template-columns:1fr}.s2-avatar{width:200px;height:200px}.s2-tpl-grid{grid-template-columns:1fr}}
@media(max-width:700px){.s2-hero-stats{gap:8px}.s2-hero-stats .hs{padding:8px 12px}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<!-- HERO -->
<div class="s2-hero">
  <div class="s2-hero-inner">
    <div>
      <span style="color:#7ef0c4;font-size:11px;font-weight:800;letter-spacing:2px"><i class="fa-solid fa-comment-dots"></i> EDITOR RESMI</span>
      <h2>Sambutan <span>Ketua Program Studi</span></h2>
      <p>Kelola sambutan Kaprodi yang tampil di beranda public. Perubahan tersimpan dan otomatis tampil pada seluruh pengunjung situs.</p>
      <div class="s2-hero-stats">
        <div class="hs"><i class="fa-solid fa-font"></i><div><b><?= number_format($wordCount) ?></b><small>Kata</small></div></div>
        <div class="hs"><i class="fa-regular fa-clock"></i><div><b><?= $readTime ?> mnt</b><small>Baca</small></div></div>
        <div class="hs"><i class="fa-solid fa-image"></i><div><b><?= !empty($sb['foto']) ? 'Ada' : 'Belum' ?></b><small>Foto</small></div></div>
        <div class="hs"><i class="fa-solid fa-signature"></i><div><b><?= !empty($sb['tanda_tangan']) ? 'Ada' : 'Belum' ?></b><small>TTD</small></div></div>
      </div>
    </div>
    <div class="s2-avatar">
      <span class="s2-avatar-ring"></span>
      <span class="s2-avatar-ring r2"></span>
      <span class="av-img" id="avImg">
        <?php if ($sb['foto']): ?>
        <img src="<?= e(media_url($sb['foto'])) ?>" alt="">
        <?php else: ?>
        <i class="fa-solid fa-user-tie"></i>
        <?php endif; ?>
      </span>
      <span class="av-name" id="avName"><?= e($sb['nama'] ?: 'Kaprodi Fisika') ?></span>
    </div>
  </div>
</div>

<!-- EDITOR + PREVIEW -->
<form method="post" enctype="multipart/form-data" id="fSambutan">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="foto_lama" value="<?= e($sb['foto']) ?>">

  <div class="s2-grid">
    <div class="s2-form">
      <div class="s2-h">
        <span class="ic"><i class="fa-solid fa-pen-nib"></i></span>
        <div>
          <b>Editor Sambutan Kaprodi</b>
          <small>Tulis pesan resmi dari Ketua Program Studi</small>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="s2-fld">
          <label><i class="fa-solid fa-user"></i> Nama Kaprodi <span class="req">*</span></label>
          <input name="nama" id="sNama" value="<?= e($sb['nama']) ?>" placeholder="Dr. Nama Lengkap, M.Si." required>
        </div>
        <div class="s2-fld">
          <label><i class="fa-solid fa-briefcase"></i> Jabatan</label>
          <input name="jabatan" id="sJab" value="<?= e($sb['jabatan']) ?>" placeholder="Ketua Program Studi Fisika">
        </div>
      </div>

      <div class="s2-fld">
        <label><i class="fa-solid fa-camera"></i> Foto Kaprodi</label>
        <div class="s2-up" id="sUp">
          <img id="pvFoto" class="<?= $sb['foto'] ? '' : 'hidden' ?>" src="<?= $sb['foto'] ? e(media_url($sb['foto'])) : '' ?>" alt="">
          <div class="ph <?= $sb['foto'] ? 'hidden' : '' ?>" id="sPh">
            <i class="fa-solid fa-user-tie"></i>
            <b>Unggah Foto Resmi</b>
            <small>Klik atau seret foto ke sini • JPG/PNG • Disarankan rasio 1:1</small>
          </div>
          <input type="file" name="foto" id="sFile" accept="image/*">
        </div>
        <div class="s2-up-actions">
          <button type="button" onclick="document.getElementById('sFile').click()"><i class="fa-solid fa-upload"></i> Pilih Foto</button>
          <button type="button" class="dng" onclick="sRmFoto()"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
      </div>

      <div class="s2-fld">
        <label><i class="fa-solid fa-align-left"></i> Isi Sambutan <span class="req">*</span> <span class="tip" id="sWc">0 kata • 0 mnt baca</span></label>
        <div class="s2-edbar">
          <button type="button" onclick="sWrap('<p>','</p>')" title="Paragraf"><i class="fa-solid fa-paragraph"></i></button>
          <button type="button" onclick="sWrap('<strong>','</strong>')" title="Tebal"><i class="fa-solid fa-bold"></i></button>
          <button type="button" onclick="sWrap('<em>','</em>')" title="Miring"><i class="fa-solid fa-italic"></i></button>
          <span class="sep"></span>
          <button type="button" onclick="sWrap('<ul>\n<li>','</li>\n</ul>')" title="Daftar"><i class="fa-solid fa-list-ul"></i></button>
          <button type="button" onclick="sWrap('<blockquote>','</blockquote>')" title="Kutipan"><i class="fa-solid fa-quote-left"></i></button>
          <button type="button" onclick="sWrap('\n\n','')" title="Baris Baru"><i class="fa-solid fa-arrow-turn-down"></i></button>
        </div>
        <textarea name="isi" id="sIsi" placeholder="Assalamu'alaikum warahmatullahi wabarakatuh..."><?= e($sb['isi']) ?></textarea>
        <div class="s2-wc">
          <span><i class="fa-solid fa-font"></i><b id="sWords"><?= $wordCount ?></b> kata</span>
          <span><i class="fa-regular fa-clock"></i><b id="sRead"><?= $readTime ?></b> mnt</span>
          <span><i class="fa-solid fa-paragraph"></i><b id="sPar"><?= substr_count((string)$sb['isi'], "\n\n") + 1 ?></b> paragraf</span>
          <div class="s2-wc-meter">
            <small>Singkat</small>
            <div class="bar"><span id="sBar" style="width:0%"></span></div>
            <small>Ideal</small>
          </div>
        </div>
      </div>

      <div class="s2-fld">
        <label><i class="fa-solid fa-signature"></i> Nama Tanda Tangan</label>
        <input name="tanda_tangan" id="sTtd" value="<?= e($sb['tanda_tangan']) ?>" placeholder="Dr. Nama Lengkap, M.Si.">
      </div>

      <!-- Templates -->
      <div class="s2-tpl">
        <h5><i class="fa-solid fa-wand-magic-sparkles"></i> Template Preset <small>Klik untuk menerapkan</small></h5>
        <div class="s2-tpl-grid">
          <button type="submit" name="preset" value="default" class="s2-tpl-item">
            <b>🌟 Default Resmi</b>
            <p>Sambutan standar dengan salam, komitmen, dan harapan.</p>
          </button>
          <button type="submit" name="preset" value="formal" class="s2-tpl-item">
            <b>📜 Formal Akademik</b>
            <p>Bahasa baku formal cocok untuk sambutan resmi institusi.</p>
          </button>
          <button type="submit" name="preset" value="inspirasi" class="s2-tpl-item">
            <b>💡 Inspiratif</b>
            <p>Nada memotivasi mengajak eksplorasi dunia sains.</p>
          </button>
        </div>
      </div>

      <div class="s2-actions">
        <button type="submit" name="simpan" value="1" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> SIMPAN SAMBUTAN</button>
        <button type="button" class="sec" onclick="sFullPv()"><i class="fa-solid fa-eye"></i> Pratinjau Penuh</button>
        <a class="sec" href="<?= url('index.php') ?>" target="_blank"><i class="fa-solid fa-globe"></i> Lihat Situs</a>
      </div>
    </div>

    <!-- PREVIEW -->
    <aside class="s2-preview">
      <div class="s2-pv-title">
        <i class="fa-solid fa-eye"></i> PRATINJAU LANGSUNG
        <div class="tools">
          <button type="button" onclick="sFullPv()" title="Layar penuh"><i class="fa-solid fa-expand"></i></button>
          <button type="button" id="sMobBtn" onclick="sToggleMob()" title="Mode mobile"><i class="fa-solid fa-mobile-screen"></i></button>
        </div>
      </div>
      <div class="s2-pvcard" id="sPvCard">
        <div class="photo-frame">
          <span class="orbit-ring r1"></span>
          <span class="orbit-ring r2"></span>
          <img id="sPvImg" class="<?= $sb['foto'] ? '' : 'hidden' ?>" src="<?= $sb['foto'] ? e(media_url($sb['foto'])) : '' ?>" alt="">
          <i class="fa-solid fa-user-tie ph <?= $sb['foto'] ? 'hidden' : '' ?>" id="sPvPh"></i>
          <div class="photo-badge"><i class="fa-solid fa-signature"></i> <span id="sPvBadge"><?= e($sb['tanda_tangan'] ?: 'KAPRODI') ?></span></div>
        </div>
        <div class="pv-body">
          <span class="sec-tag"><i class="fa-solid fa-atom"></i> SAMBUTAN KAPRODI</span>
          <h3 id="sPvNama"><?= e($sb['nama'] ?: 'Dr. Nama Kaprodi') ?></h3>
          <span class="jab" id="sPvJab"><?= e($sb['jabatan'] ?: 'Ketua Program Studi Fisika') ?></span>
          <div class="isi" id="sPvIsi"><?= e($sb['isi'] ?: 'Isi sambutan akan tampil di sini...') ?></div>
          <div class="ttd"><i class="fa-solid fa-signature"></i> <span id="sPvTtd"><?= e($sb['tanda_tangan'] ?: 'Nama Kaprodi') ?></span></div>
        </div>
      </div>
    </aside>
  </div>
</form>

<!-- Fullscreen modal -->
<div class="s2-modal" id="sFullModal">
  <div class="s2-modal-box">
    <div class="s2-modal-head">
      <i class="fa-solid fa-globe"></i>
      <b>Pratinjau Sambutan — Tampilan Public</b>
      <span class="url" id="sPvUrl">—</span>
      <button type="button" onclick="document.getElementById('sFullModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="s2-modal-body">
      <div class="full-photo">
        <span class="orbit-ring r1"></span>
        <span class="orbit-ring r2"></span>
        <img id="sFullImg" class="<?= $sb['foto'] ? '' : 'hidden' ?>" src="<?= $sb['foto'] ? e(media_url($sb['foto'])) : '' ?>" alt="">
        <i class="fa-solid fa-user-tie ph <?= $sb['foto'] ? 'hidden' : '' ?>" id="sFullPh"></i>
      </div>
      <span class="tag"><i class="fa-solid fa-atom"></i> SAMBUTAN KAPRODI</span>
      <h2 id="sFullNama"><?= e($sb['nama'] ?: 'Dr. Nama Kaprodi') ?></h2>
      <span class="jab" id="sFullJab"><?= e($sb['jabatan'] ?: 'Ketua Program Studi Fisika') ?></span>
      <div class="isi" id="sFullIsi"><?= e($sb['isi'] ?: 'Isi sambutan akan tampil di sini...') ?></div>
      <div class="ttd"><i class="fa-solid fa-signature"></i> <span id="sFullTtd"><?= e($sb['tanda_tangan'] ?: 'Nama Kaprodi') ?></span></div>
    </div>
  </div>
</div>

<script>
(function(){
  var nama = document.getElementById('sNama'), jab = document.getElementById('sJab'), isi = document.getElementById('sIsi'), ttd = document.getElementById('sTtd');
  var pNama = document.getElementById('sPvNama'), pJab = document.getElementById('sPvJab'), pIsi = document.getElementById('sPvIsi'), pTtd = document.getElementById('sPvTtd'), pBadge = document.getElementById('sPvBadge');
  var fNama = document.getElementById('sFullNama'), fJab = document.getElementById('sFullJab'), fIsi = document.getElementById('sFullIsi'), fTtd = document.getElementById('sFullTtd');
  var avName = document.getElementById('avName');
  var urlPv = document.getElementById('sPvUrl');
  if (urlPv) urlPv.textContent = window.location.origin + '/index.php#sambutan';

  function syncAll(){
    var n = nama ? nama.value : '';
    var j = jab ? jab.value : '';
    var i = isi ? isi.value : '';
    var t = ttd ? ttd.value : '';
    if (pNama) pNama.textContent = n || 'Dr. Nama Kaprodi';
    if (fNama) fNama.textContent = n || 'Dr. Nama Kaprodi';
    if (avName) avName.textContent = n || 'Kaprodi Fisika';
    if (pJab) pJab.textContent = j || 'Ketua Program Studi Fisika';
    if (fJab) fJab.textContent = j || 'Ketua Program Studi Fisika';
    if (pIsi) pIsi.textContent = i || 'Isi sambutan akan tampil di sini...';
    if (fIsi) fIsi.textContent = i || 'Isi sambutan akan tampil di sini...';
    if (pTtd) pTtd.textContent = t || 'Nama Kaprodi';
    if (fTtd) fTtd.textContent = t || 'Nama Kaprodi';
    if (pBadge) pBadge.textContent = t || 'KAPRODI';
  }
  if (nama) nama.addEventListener('input', syncAll);
  if (jab) jab.addEventListener('input', syncAll);
  if (ttd) ttd.addEventListener('input', syncAll);

  /* Word counter */
  if (isi) {
    var sW = document.getElementById('sWords'), sR = document.getElementById('sRead'), sP = document.getElementById('sPar'), sB = document.getElementById('sBar'), sWc = document.getElementById('sWc');
    var upd = function(){
      var w = isi.value.trim().split(/\s+/).filter(Boolean).length;
      var m = Math.max(1, Math.round(w / 150));
      var p = isi.value.split(/\n\n+/).filter(function(x){return x.trim();}).length || 1;
      if (sW) sW.textContent = w;
      if (sR) sR.textContent = m;
      if (sP) sP.textContent = p;
      if (sWc) sWc.textContent = w + ' kata • ' + m + ' mnt baca';
      if (sB) {
        var pct = Math.min(100, (w / 250) * 100);
        sB.style.width = pct + '%';
        sB.style.background = w < 80 ? '#e5484d' : (w > 300 ? '#f59e0b' : 'var(--grad)');
      }
      syncAll();
    };
    isi.addEventListener('input', upd); upd();
  }

  /* Toolbar wrap */
  window.sWrap = function(before, after){
    if (!isi) return;
    var st = isi.selectionStart, en = isi.selectionEnd, val = isi.value;
    var sel = val.substring(st, en) || 'teks';
    isi.value = val.substring(0, st) + before + sel + after + val.substring(en);
    isi.focus();
    isi.selectionStart = st + before.length;
    isi.selectionEnd = st + before.length + sel.length;
    isi.dispatchEvent(new Event('input'));
  };

  /* Photo upload */
  var up = document.getElementById('sUp'), f = document.getElementById('sFile'), pv = document.getElementById('pvFoto'), ph = document.getElementById('sPh');
  var pvI = document.getElementById('sPvImg'), pvPh = document.getElementById('sPvPh');
  var fI = document.getElementById('sFullImg'), fPh = document.getElementById('sFullPh');
  var avI = document.getElementById('avImg');

  function show(file){
    var rd = new FileReader();
    rd.onload = function(ev){
      if (pv) { pv.src = ev.target.result; pv.classList.remove('hidden'); }
      if (ph) ph.classList.add('hidden');
      if (pvI) { pvI.src = ev.target.result; pvI.classList.remove('hidden'); }
      if (pvPh) pvPh.classList.add('hidden');
      if (fI) { fI.src = ev.target.result; fI.classList.remove('hidden'); }
      if (fPh) fPh.classList.add('hidden');
      if (avI) avI.innerHTML = '<img src="' + ev.target.result + '" alt="">';
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
  window.sRmFoto = function(){
    if (!confirm('Hapus foto?')) return;
    if (pv) { pv.src=''; pv.classList.add('hidden'); }
    if (ph) ph.classList.remove('hidden');
    if (pvI) { pvI.src=''; pvI.classList.add('hidden'); }
    if (pvPh) pvPh.classList.remove('hidden');
    if (fI) { fI.src=''; fI.classList.add('hidden'); }
    if (fPh) fPh.classList.remove('hidden');
    if (avI) avI.innerHTML = '<i class="fa-solid fa-user-tie"></i>';
    if (f) f.value = '';
  };

  /* Fullscreen preview */
  window.sFullPv = function(){ document.getElementById('sFullModal').classList.add('open'); };
  document.getElementById('sFullModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });

  /* Mobile mode toggle */
  var mobBtn = document.getElementById('sMobBtn'), pvCard = document.getElementById('sPvCard');
  window.sToggleMob = function(){
    if (!pvCard || !mobBtn) return;
    var on = pvCard.style.maxWidth === '375px';
    if (on) {
      pvCard.style.maxWidth = '';
      pvCard.style.margin = '';
      mobBtn.classList.remove('on');
    } else {
      pvCard.style.maxWidth = '375px';
      pvCard.style.margin = '0 auto';
      mobBtn.classList.add('on');
    }
  };

  /* ESC to close */
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') document.querySelectorAll('.s2-modal.open').forEach(function(m){ m.classList.remove('open'); });
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>