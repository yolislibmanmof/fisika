<?php
$ACTIVE = 'pengaturan.php'; $TITLE = 'Pengaturan Situs';
require_once __DIR__ . '/../config/config.php';
require_login(); csrf_check();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan'])) {
        $logo = upload_file('logo', 'logo') ?? $_POST['logo_lama'];
        $fav  = upload_file('favicon', 'favicon') ?? $_POST['fav_lama'];
        $stmt = $conn->prepare("UPDATE settings SET site_name=?,site_tagline=?,prodi_name=?,fakultas=?,kampus=?,logo=?,favicon=?,email=?,telepon=?,alamat=?,facebook=?,instagram=?,youtube=?,linkedin=?,badge_hero=?,hero_title=?,hero_subtitle=?,akreditasi=?,meta_description=?,footer_text=? WHERE id=1");
        $v = [$_POST['site_name'],$_POST['site_tagline'],$_POST['prodi_name'],$_POST['fakultas'],$_POST['kampus'],$logo,$fav,$_POST['email'],$_POST['telepon'],$_POST['alamat'],$_POST['facebook'],$_POST['instagram'],$_POST['youtube'],$_POST['linkedin'],$_POST['badge_hero'],$_POST['hero_title'],$_POST['hero_subtitle'],$_POST['akreditasi'],$_POST['meta_description'],$_POST['footer_text']];
        $stmt->bind_param(str_repeat('s', 20), ...$v);
        $stmt->execute();
        $msg = 'Pengaturan situs berhasil disimpan.';
    }
    if (isset($_POST['reset_meta'])) {
        $conn->query("UPDATE settings SET meta_description='Website resmi Program Studi Fisika: pendidikan, riset, dan inovasi sains.', footer_text='© " . date('Y') . " Program Studi Fisika. Hak Cipta Dilindungi Undang-Undang.' WHERE id=1");
        $msg = 'Meta & footer direset ke bawaan.';
    }
}

$S = get_setting($conn);

/* Hitung kelengkapan profil */
$scoreFields = ['site_name','site_tagline','prodi_name','fakultas','kampus','logo','favicon','email','telepon','alamat','facebook','instagram','akreditasi','meta_description'];
$filled = 0;
foreach ($scoreFields as $f) if (!empty($S[$f])) $filled++;
$score = round(($filled / count($scoreFields)) * 100);
$scoreColor = $score >= 90 ? '#00b874' : ($score >= 60 ? '#f59e0b' : '#e5484d');
$scoreLabel = $score >= 90 ? 'Lengkap' : ($score >= 60 ? 'Cukup Baik' : 'Perlu Diperbaiki');

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ========= PENGATURAN SITUS ULTIMATE ========= */
@keyframes p2spin{to{transform:rotate(360deg)}}
@keyframes p2float{50%{transform:translateY(-6px)}}
@keyframes p2fade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

/* Hero pengaturan */
.p2-hero{background:linear-gradient(120deg,#071a39 0%,#0a2a5e 50%,#0a66c2 100%);border-radius:22px;padding:32px;color:#fff;position:relative;overflow:hidden;margin-bottom:24px;box-shadow:0 30px 70px rgba(10,42,94,.3)}
.p2-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 30%,rgba(0,184,116,.2),transparent 45%),radial-gradient(circle at 85% 70%,rgba(10,102,194,.3),transparent 50%)}
.p2-hero::after{content:'\f013';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:-20px;bottom:-40px;font-size:220px;color:rgba(255,255,255,.04);animation:p2spin 50s linear infinite}
.p2-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:1fr 340px;gap:30px;align-items:center}
.p2-hero h2{font-family:var(--display);font-size:28px;margin-bottom:10px}
.p2-hero h2 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.p2-hero p{color:#c8d6ea;font-size:14.5px;line-height:1.75;margin-bottom:18px;max-width:560px}
.p2-hero-stats{display:flex;gap:16px;flex-wrap:wrap}
.p2-hero-stats .hs{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);padding:10px 16px;border-radius:11px;border:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:10px}
.p2-hero-stats .hs i{color:#7ef0c4;font-size:14px}
.p2-hero-stats .hs b{font-family:var(--display);font-size:16px;display:block;color:#fff}
.p2-hero-stats .hs small{color:#9fb3cd;font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase}

/* Score ring */
.p2-score{position:relative;width:280px;height:280px;margin:0 auto}
.p2-score-ring{width:100%;height:100%;transform:rotate(-90deg)}
.p2-score-ring circle{fill:none;stroke-width:12;stroke-linecap:round}
.p2-score-ring .bg{stroke:rgba(255,255,255,.1)}
.p2-score-ring .fg{stroke:url(#p2grad);transition:stroke-dashoffset 1.5s cubic-bezier(.22,.61,.36,1)}
.p2-score-body{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.p2-score-body b{font-family:var(--display);font-size:52px;line-height:1;color:#fff}
.p2-score-body b em{color:#7ef0c4;font-style:normal}
.p2-score-body small{color:#9fb3cd;font-size:10.5px;letter-spacing:1.8px;font-weight:800;text-transform:uppercase;margin-top:6px}
.p2-score-body .lbl{margin-top:10px;padding:6px 14px;background:rgba(0,184,116,.2);color:#7ef0c4;border:1px solid rgba(0,184,116,.4);border-radius:99px;font-size:11px;font-weight:800;letter-spacing:1px}

/* Tabs */
.p2-tabs{display:flex;gap:6px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:8px;margin-bottom:22px;overflow-x:auto;scrollbar-width:none}
.p2-tabs::-webkit-scrollbar{display:none}
.p2-tab{display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:10px;background:transparent;border:0;cursor:pointer;font-family:var(--body);font-size:13px;font-weight:700;color:#68758a;transition:.25s;white-space:nowrap;position:relative}
.p2-tab i{font-size:13px;transition:.3s}
.p2-tab:hover{background:var(--gray);color:var(--blue)}
.p2-tab.on{background:var(--grad);color:#fff;box-shadow:0 8px 18px rgba(0,184,116,.3)}
.p2-tab.on i{transform:rotate(-8deg) scale(1.15)}
.p2-tab .dot{width:7px;height:7px;border-radius:50%;background:#e5484d;margin-left:4px}
.p2-tab .dot.ok{background:#00b874}

/* Sections */
.p2-section{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;margin-bottom:18px;box-shadow:var(--shadow);position:relative;overflow:hidden;animation:p2fade .4s}
.p2-section::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad)}
.p2-section-head{display:flex;align-items:center;gap:14px;margin-bottom:22px;padding-bottom:16px;border-bottom:1px dashed var(--line)}
.p2-section-head .ic{width:46px;height:46px;border-radius:13px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:18px;flex:none}
.p2-section-head b{font-family:var(--display);font-size:17px;color:var(--blue-dark);display:block}
.p2-section-head small{color:#7b8aa3;font-size:12px;display:block;margin-top:3px}
.p2-section-head .tools{margin-left:auto;display:flex;gap:8px}
.p2-section-head .tools button{border:1.4px solid var(--line);background:#fff;color:#475569;padding:8px 14px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px}
.p2-section-head .tools button:hover{background:var(--grad);color:#fff;border-color:transparent;transform:translateY(-2px)}

.p2-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.p2-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.p2-fld{margin-bottom:16px}
.p2-fld label{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:8px}
.p2-fld label .req{color:#e5484d;margin-left:2px}
.p2-fld label i{color:var(--green)}
.p2-fld label .tip{margin-left:auto;font-size:10px;font-weight:600;color:#9aa8bf;letter-spacing:0;text-transform:none}
.p2-fld input,.p2-fld select,.p2-fld textarea{width:100%;padding:12px 14px;border:1.6px solid var(--line);border-radius:11px;font-family:var(--body);font-size:14px;outline:0;transition:.25s;background:#fbfcfe}
.p2-fld input:focus,.p2-fld select:focus,.p2-fld textarea:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.08);background:#fff}
.p2-fld textarea{resize:vertical;line-height:1.7;min-height:80px}

/* Upload box premium */
.p2-upbox{border:2px dashed var(--line);border-radius:14px;padding:18px;background:#fbfcfe;transition:.3s;position:relative;overflow:hidden;text-align:center}
.p2-upbox:hover,.p2-upbox.drag{border-color:var(--green);background:#fff;transform:translateY(-2px)}
.p2-upbox img{max-width:100%;max-height:150px;margin:0 auto;display:block;border-radius:10px;background:var(--gray);padding:10px}
.p2-upbox.fav img{max-height:80px;padding:12px;background:#fff;border:1.6px solid var(--line)}
.p2-upbox .ph{color:#9aa8bf;padding:20px 10px}
.p2-upbox .ph i{font-size:32px;color:var(--blue);margin-bottom:10px;display:block}
.p2-upbox .ph b{display:block;color:var(--blue-dark);margin-bottom:4px}
.p2-upbox .ph small{font-size:11.5px}
.p2-upbox input[type=file]{display:none}
.p2-up-actions{display:flex;gap:6px;justify-content:center;margin-top:12px}
.p2-up-actions button{border:1.2px solid var(--line);background:#fff;color:#475569;padding:7px 12px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:5px}
.p2-up-actions button:hover{background:var(--grad);color:#fff;border-color:transparent}
.p2-up-actions .dng:hover{background:#e5484d;border-color:#e5484d}

/* Social media preview */
.p2-soc-preview{background:linear-gradient(135deg,#0a2a5e,#071a39);border-radius:14px;padding:22px;color:#fff;position:relative;overflow:hidden}
.p2-soc-preview::after{content:'';position:absolute;right:-30px;top:-30px;width:100px;height:100px;border:2px dashed rgba(0,184,116,.3);border-radius:50%;animation:p2spin 20s linear infinite}
.p2-soc-preview h5{color:#fff;font-family:var(--display);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.p2-soc-preview h5::after{display:none}
.p2-soc-preview h5 i{color:#7ef0c4}
.p2-soc-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;position:relative;z-index:1}
.p2-soc-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);transition:.25s;text-decoration:none;color:#c8d6ea}
.p2-soc-item:hover{background:rgba(255,255,255,.12);transform:translateX(4px);color:#fff}
.p2-soc-item .soc-ic{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;color:#fff;font-size:15px;flex:none}
.p2-soc-item.fb .soc-ic{background:#1877f2}
.p2-soc-item.ig .soc-ic{background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#bc1888)}
.p2-soc-item.yt .soc-ic{background:#ff0000}
.p2-soc-item.li .soc-ic{background:#0a66c2}
.p2-soc-item b{display:block;font-size:12px;font-family:var(--display);color:#fff}
.p2-soc-item small{color:#9fb3cd;font-size:10px;font-weight:700;letter-spacing:.5px}
.p2-soc-item .arrow{margin-left:auto;color:#7ef0c4;font-size:11px;opacity:0;transition:.2s}
.p2-soc-item:hover .arrow{opacity:1}
.p2-soc-item .arrow i{transform:translateX(0);transition:.25s}
.p2-soc-item:hover .arrow i{transform:translateX(4px)}
.p2-soc-item.empty{opacity:.4;cursor:not-allowed}
.p2-soc-item.empty:hover{transform:none;background:rgba(255,255,255,.06)}

/* Hero preview */
.p2-hero-preview{background:linear-gradient(135deg,#071a39,#0a2a5e 50%,#0a66c2);border-radius:16px;padding:26px;color:#fff;position:relative;overflow:hidden;min-height:260px;display:flex;flex-direction:column;justify-content:center}
.p2-hero-preview::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 20%,rgba(0,184,116,.2),transparent 50%)}
.p2-hero-preview::after{content:'⚛';position:absolute;right:10%;top:50%;transform:translateY(-50%);font-size:150px;color:rgba(255,255,255,.04);animation:p2spin 40s linear infinite}
.p2-hero-preview .pv-inner{position:relative;z-index:1}
.p2-hero-preview .badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(90deg,#00b874,#00915c);color:#04231a;padding:6px 14px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:1.2px;margin-bottom:14px}
.p2-hero-preview .badge i{animation:p2spin 4s linear infinite}
.p2-hero-preview h3{font-family:var(--display);font-size:22px;line-height:1.25;margin-bottom:10px;max-width:90%}
.p2-hero-preview p{color:#c8d6ea;font-size:13.5px;line-height:1.7;max-width:90%}

/* Navbar preview */
.p2-nav-preview{background:#fff;border:1.4px solid var(--line);border-radius:14px;padding:12px 18px;display:flex;align-items:center;gap:14px;margin-bottom:16px;box-shadow:0 6px 18px rgba(0,0,0,.05)}
.p2-nav-preview .logo-ph{display:flex;align-items:center;gap:10px}
.p2-nav-preview .logo-ph img{width:38px;height:38px;border-radius:10px;object-fit:contain;background:var(--gray);padding:4px}
.p2-nav-preview .logo-ph .atom{width:38px;height:38px;border-radius:10px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:16px}
.p2-nav-preview .logo-ph .atom i{animation:p2spin 6s linear infinite}
.p2-nav-preview .logo-ph b{font-family:var(--display);font-size:14px;color:var(--blue-dark)}
.p2-nav-preview .logo-ph b span{color:var(--green)}
.p2-nav-preview .logo-ph small{display:block;color:#9aa8bf;font-size:10px;font-weight:600}
.p2-nav-preview .nav-items{display:flex;gap:8px;margin-left:auto}
.p2-nav-preview .nav-items span{padding:6px 10px;font-size:11px;font-weight:700;color:#475569}
.p2-nav-preview .nav-items .cta{background:var(--grad);color:#fff;border-radius:8px;padding:6px 12px}

/* SEO meter */
.p2-seo-meter{margin-top:8px}
.p2-seo-bar{height:8px;background:var(--gray);border-radius:99px;overflow:hidden;position:relative}
.p2-seo-bar span{display:block;height:100%;border-radius:99px;transition:width .4s,background .3s}
.p2-seo-legend{display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#9aa8bf;font-weight:600}
.p2-seo-legend .current{color:var(--blue-dark);font-family:var(--display);font-weight:800}
.p2-seo-hint{margin-top:8px;padding:10px 12px;border-radius:9px;font-size:11.5px;font-weight:700;display:flex;align-items:center;gap:8px}
.p2-seo-hint.good{background:#e7f8f1;color:#0a7a52;border:1px solid #b5e8d4}
.p2-seo-hint.warn{background:#fef3c7;color:#92400e;border:1px solid #fcd34d}
.p2-seo-hint.bad{background:#fdecec;color:#b3261e;border:1px solid #f5c2c0}

/* Footer preview */
.p2-footer-preview{background:#0b0f14;border-radius:14px;padding:22px;color:#c8d6ea;font-size:11px;position:relative;overflow:hidden}
.p2-footer-preview::before{content:'FISIKA • SAINS • INOVASI • RISET';position:absolute;top:8px;left:0;right:0;text-align:center;font-family:var(--display);font-size:14px;font-weight:800;letter-spacing:3px;color:rgba(255,255,255,.1)}
.p2-footer-preview .brand{display:flex;align-items:center;gap:10px;margin-bottom:12px;margin-top:16px}
.p2-footer-preview .brand .atom{width:34px;height:34px;border-radius:9px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:14px}
.p2-footer-preview .brand .atom i{animation:p2spin 6s linear infinite}
.p2-footer-preview .brand b{font-family:var(--display);color:#fff;font-size:13px}
.p2-footer-preview p{color:#9aa8bf;font-size:11px;line-height:1.6;margin-bottom:10px}
.p2-footer-preview .fbot{border-top:1px solid rgba(255,255,255,.08);padding-top:10px;font-size:10.5px;color:#7b8aa3;display:flex;justify-content:space-between}

/* Submit bar */
.p2-submit{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 22px;display:flex;align-items:center;gap:16px;position:sticky;bottom:20px;z-index:30;box-shadow:0 20px 50px rgba(10,42,94,.2);margin-top:20px}
.p2-submit .info{display:flex;align-items:center;gap:10px;flex:1;font-size:12.5px;color:#68758a;font-weight:600}
.p2-submit .info i{color:var(--green);font-size:14px}
.p2-submit .info b{color:var(--blue-dark);font-family:var(--display)}
.p2-submit .info .dot{width:8px;height:8px;border-radius:50%;background:#00b874;animation:a2beat 1.6s infinite}
.p2-submit button{padding:12px 28px;border:0;background:var(--grad);color:#fff;font-weight:800;font-size:13px;border-radius:11px;cursor:pointer;transition:.3s;display:inline-flex;align-items:center;gap:8px;letter-spacing:.5px;box-shadow:0 10px 24px rgba(0,184,116,.35)}
.p2-submit button:hover{transform:translateY(-3px);box-shadow:0 16px 32px rgba(0,184,116,.45)}
.p2-submit .reset{border:1.4px solid var(--line);background:#fff;color:#475569;padding:10px 18px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.p2-submit .reset:hover{border-color:#e5484d;color:#b3261e;background:#fdecec}

/* Modal */
.p2-modal{position:fixed;inset:0;background:rgba(7,26,57,.65);backdrop-filter:blur(6px);z-index:1450;display:none;align-items:center;justify-content:center;padding:20px}
.p2-modal.open{display:flex}
.p2-modal-box{background:#fff;border-radius:18px;width:min(520px,100%);overflow:hidden;animation:a2zin .35s;box-shadow:0 30px 80px rgba(0,0,0,.35)}
.p2-modal-body{padding:28px;text-align:center}
.p2-modal-body .mic{width:64px;height:64px;border-radius:18px;background:#fdecec;color:#b3261e;display:grid;place-items:center;font-size:26px;margin:0 auto 18px}
.p2-modal-body h3{font-family:var(--display);color:var(--blue-dark);font-size:18px;margin-bottom:10px}
.p2-modal-body p{color:#68758a;font-size:13.5px;line-height:1.65}
.p2-modal-foot{display:flex;gap:10px;padding:16px 28px;background:#fbfcfe;border-top:1px solid var(--line)}
.p2-modal-foot button{flex:1;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;border:1.4px solid var(--line);background:#fff;color:#33415e;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.p2-modal-foot .ok{background:var(--grad);color:#fff;border-color:transparent}
.p2-modal-foot button:hover{transform:translateY(-2px)}

/* Hidden state for tab switching */
.p2-section.hidden{display:none}

@media(max-width:1100px){.p2-hero-inner{grid-template-columns:1fr}.p2-score{width:220px;height:220px}.p2-grid-2,.p2-grid-3{grid-template-columns:1fr}.p2-soc-grid{grid-template-columns:1fr}}
@media(max-width:700px){.p2-submit{flex-direction:column;align-items:stretch}.p2-submit .info{justify-content:center}}
</style>

<?php if ($msg): ?>
<div class="alert ok"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
<script>if(window.a2Toast) a2Toast('<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<!-- HERO PENGATURAN -->
<div class="p2-hero">
  <div class="p2-hero-inner">
    <div>
      <span style="color:#7ef0c4;font-size:11px;font-weight:800;letter-spacing:2px"><i class="fa-solid fa-sliders"></i> PANEL KONTROL</span>
      <h2>Pengaturan <span>Situs Program Studi</span></h2>
      <p>Kelola identitas, media, hero slider, media sosial, dan SEO situs Program Studi Fisika. Seluruh perubahan akan langsung tersinkron ke tampilan public.</p>
      <div class="p2-hero-stats">
        <div class="hs"><i class="fa-solid fa-circle-check"></i><div><b><?= $score ?>%</b><small>Kelengkapan</small></div></div>
        <div class="hs"><i class="fa-solid fa-photo-film"></i><div><b><?= !empty($S['logo']) ? 'Ada' : 'Belum' ?></b><small>Logo</small></div></div>
        <div class="hs"><i class="fa-solid fa-star"></i><div><b><?= !empty($S['favicon']) ? 'Ada' : 'Belum' ?></b><small>Favicon</small></div></div>
        <div class="hs"><i class="fa-solid fa-share-nodes"></i><div><b><?= count(array_filter([$S['facebook'] ?? '',$S['instagram'] ?? '',$S['youtube'] ?? '',$S['linkedin'] ?? ''])) ?>/4</b><small>Sosmed Aktif</small></div></div>
      </div>
    </div>
    <div class="p2-score">
      <svg class="p2-score-ring" viewBox="0 0 200 200">
        <defs>
          <linearGradient id="p2grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#00b874"/>
            <stop offset="100%" style="stop-color:#4da3ff"/>
          </linearGradient>
        </defs>
        <circle class="bg" cx="100" cy="100" r="88"/>
        <circle class="fg" id="p2ScoreRing" cx="100" cy="100" r="88" stroke-dasharray="553" stroke-dashoffset="553"/>
      </svg>
      <div class="p2-score-body">
        <b><span id="p2ScoreNum">0</span><em>%</em></b>
        <small>Skor Kelengkapan</small>
        <span class="lbl"><?= e($scoreLabel) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="p2-tabs">
  <button class="p2-tab on" data-tab="identitas"><i class="fa-solid fa-building"></i> Identitas</button>
  <button class="p2-tab" data-tab="media"><i class="fa-solid fa-image"></i> Logo & Favicon</button>
  <button class="p2-tab" data-tab="hero"><i class="fa-solid fa-wand-magic-sparkles"></i> Hero Beranda</button>
  <button class="p2-tab" data-tab="sosmed"><i class="fa-solid fa-share-nodes"></i> Media Sosial</button>
  <button class="p2-tab" data-tab="seo"><i class="fa-solid fa-magnifying-glass-chart"></i> SEO & Footer</button>
</div>

<form method="post" enctype="multipart/form-data" id="fSettings">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="logo_lama" value="<?= e($S['logo']) ?>">
  <input type="hidden" name="fav_lama" value="<?= e($S['favicon']) ?>">

  <!-- IDENTITAS -->
  <div class="p2-section" data-section="identitas">
    <div class="p2-section-head">
      <span class="ic"><i class="fa-solid fa-building"></i></span>
      <div>
        <b>Identitas Program Studi</b>
        <small>Informasi utama yang tampil pada navbar, footer, dan mesin pencari</small>
      </div>
      <div class="tools">
        <a href="<?= url('index.php') ?>" target="_blank" class=""><i class="fa-solid fa-globe"></i> Lihat Situs</a>
      </div>
    </div>

    <div class="p2-grid-2">
      <div class="p2-fld">
        <label><i class="fa-solid fa-heading"></i> Nama Website <span class="req">*</span></label>
        <input name="site_name" id="sName" value="<?= e($S['site_name']) ?>" required>
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-quote-left"></i> Tagline</label>
        <input name="site_tagline" id="sTagline" value="<?= e($S['site_tagline']) ?>" placeholder="Departemen Fisika FMIPA">
      </div>
    </div>
    <div class="p2-grid-3">
      <div class="p2-fld">
        <label><i class="fa-solid fa-atom"></i> Nama Prodi</label>
        <input name="prodi_name" value="<?= e($S['prodi_name']) ?>">
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-graduation-cap"></i> Fakultas</label>
        <input name="fakultas" value="<?= e($S['fakultas']) ?>">
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-school"></i> Kampus / Universitas</label>
        <input name="kampus" value="<?= e($S['kampus']) ?>">
      </div>
    </div>
    <div class="p2-grid-3">
      <div class="p2-fld">
        <label><i class="fa-solid fa-certificate"></i> Akreditasi</label>
        <input name="akreditasi" value="<?= e($S['akreditasi']) ?>" placeholder="UNGGUL / BAIK SEKALI">
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-envelope"></i> Email</label>
        <input type="email" name="email" value="<?= e($S['email']) ?>">
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-phone"></i> Telepon</label>
        <input name="telepon" value="<?= e($S['telepon']) ?>">
      </div>
    </div>
    <div class="p2-fld">
      <label><i class="fa-solid fa-location-dot"></i> Alamat</label>
      <textarea name="alamat" rows="2" placeholder="Alamat lengkap gedung/kampus"><?= e($S['alamat']) ?></textarea>
    </div>
  </div>

  <!-- MEDIA -->
  <div class="p2-section hidden" data-section="media">
    <div class="p2-section-head">
      <span class="ic"><i class="fa-solid fa-image"></i></span>
      <div>
        <b>Logo & Favicon</b>
        <small>Aset visual yang tampil pada navbar, footer, dan tab browser</small>
      </div>
    </div>
    <div class="p2-grid-2">
      <div class="p2-fld">
        <label><i class="fa-solid fa-building-columns"></i> Logo Utama</label>
        <div class="p2-upbox" id="upLogo">
          <img id="pvLogo" class="<?= $S['logo'] ? '' : 'hidden' ?>" src="<?= $S['logo'] ? e(media_url($S['logo'])) : '' ?>" alt="">
          <span class="ph <?= $S['logo'] ? 'hidden' : '' ?>" id="phLogo">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <b>Unggah Logo</b>
            <small>Klik atau seret file ke sini • PNG/SVG • Disarankan latar transparan</small>
          </span>
          <input type="file" name="logo" id="fLogo" accept="image/*">
        </div>
        <div class="p2-up-actions">
          <button type="button" onclick="document.getElementById('fLogo').click()"><i class="fa-solid fa-upload"></i> Pilih</button>
          <button type="button" class="dng" onclick="p2RmLogo()"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
      </div>
      <div class="p2-fld">
        <label><i class="fa-solid fa-star"></i> Favicon</label>
        <div class="p2-upbox fav" id="upFav">
          <img id="pvFav" class="<?= $S['favicon'] ? '' : 'hidden' ?>" src="<?= $S['favicon'] ? e(media_url($S['favicon'])) : '' ?>" alt="">
          <span class="ph <?= $S['favicon'] ? 'hidden' : '' ?>" id="phFav">
            <i class="fa-solid fa-star"></i>
            <b>Unggah Favicon</b>
            <small>ICO/PNG • Ukuran persegi (32×32 atau 64×64 px)</small>
          </span>
          <input type="file" name="favicon" id="fFav" accept="image/*,.ico">
        </div>
        <div class="p2-up-actions">
          <button type="button" onclick="document.getElementById('fFav').click()"><i class="fa-solid fa-upload"></i> Pilih</button>
          <button type="button" class="dng" onclick="p2RmFav()"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
      </div>
    </div>

    <!-- Preview navbar -->
    <div style="margin-top:22px">
      <label style="font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:10px;display:block"><i class="fa-solid fa-eye" style="color:var(--green);margin-right:6px"></i>Pratinjau Navbar</label>
      <div class="p2-nav-preview">
        <div class="logo-ph">
          <img id="pvNavLogo" class="<?= $S['logo'] ? '' : 'hidden' ?>" src="<?= $S['logo'] ? e(media_url($S['logo'])) : '' ?>" alt="">
          <span class="atom <?= $S['logo'] ? 'hidden' : '' ?>" id="pvNavAtom"><i class="fa-solid fa-atom"></i></span>
          <div>
            <b>ADMIN<span>FISIKA</span></b>
            <small id="pvNavTag"><?= e($S['site_tagline'] ?: 'Departemen Fisika FMIPA') ?></small>
          </div>
        </div>
        <div class="nav-items">
          <span>Beranda</span><span>Profil</span><span>Akademik</span><span>Berita</span>
          <span class="cta">DAFTAR</span>
        </div>
      </div>
    </div>

    <!-- Preview browser tab -->
    <div style="margin-top:16px">
      <label style="font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:10px;display:block"><i class="fa-solid fa-window-maximize" style="color:var(--green);margin-right:6px"></i>Pratinjau Tab Browser</label>
      <div style="background:#e3e9f2;border-radius:12px 12px 0 0;padding:10px 14px;display:inline-flex;align-items:center;gap:10px;min-width:340px;box-shadow:0 4px 12px rgba(0,0,0,.08)">
        <img id="pvTabIcon" class="<?= $S['favicon'] ? '' : 'hidden' ?>" src="<?= $S['favicon'] ? e(media_url($S['favicon'])) : '' ?>" style="width:16px;height:16px;border-radius:3px">
        <span class="tab-atom <?= $S['favicon'] ? 'hidden' : '' ?>" id="pvTabAtom" style="width:16px;height:16px;border-radius:3px;background:var(--grad);display:inline-grid;place-items:center;color:#fff;font-size:8px"><i class="fa-solid fa-atom"></i></span>
        <span style="font-size:12px;color:#475569;font-weight:700" id="pvTabTitle"><?= e(($S['site_name'] ?: 'Program Studi Fisika') . ' — ' . ($S['kampus'] ?? '')) ?></span>
        <span style="margin-left:auto;color:#9aa8bf;font-size:14px"><i class="fa-solid fa-xmark"></i></span>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <div class="p2-section hidden" data-section="hero">
    <div class="p2-section-head">
      <span class="ic"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
      <div>
        <b>Hero Beranda (Fallback)</b>
        <small>Teks default jika belum ada slide di menu "Hero Slider"</small>
      </div>
      <div class="tools">
        <a href="<?= url('admin/hero.php') ?>"><i class="fa-solid fa-photo-film"></i> Kelola Slider</a>
      </div>
    </div>
    <div class="p2-grid-2">
      <div>
        <div class="p2-fld">
          <label><i class="fa-solid fa-tag"></i> Badge Hero</label>
          <input name="badge_hero" id="hBadge" value="<?= e($S['badge_hero']) ?>" placeholder="PRODI FISIKA - TERAKREDITASI UNGGUL">
        </div>
        <div class="p2-fld">
          <label><i class="fa-solid fa-heading"></i> Judul Utama Hero</label>
          <input name="hero_title" id="hTitle" value="<?= e($S['hero_title']) ?>" placeholder="Mencetak Ilmuwan & Inovator Fisika Masa Depan">
        </div>
        <div class="p2-fld">
          <label><i class="fa-solid fa-align-left"></i> Sub-judul Hero</label>
          <input name="hero_subtitle" id="hSubtitle" value="<?= e($S['hero_subtitle']) ?>" placeholder="Program Studi Fisika — Terakreditasi UNGGUL">
        </div>
      </div>
      <div>
        <label style="font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:10px;display:block"><i class="fa-solid fa-eye" style="color:var(--green);margin-right:6px"></i>Pratinjau Hero</label>
        <div class="p2-hero-preview">
          <div class="pv-inner">
            <span class="badge"><i class="fa-solid fa-wave-square"></i> <span id="pvHBadge"><?= e($S['badge_hero'] ?: 'PRODI FISIKA') ?></span></span>
            <h3 id="pvHTitle"><?= e($S['hero_title'] ?: 'Mencetak Ilmuwan & Inovator Fisika Masa Depan') ?></h3>
            <p id="pvHSub"><?= e($S['hero_subtitle'] ?: 'Program Studi Fisika — Terakreditasi UNGGUL') ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SOSMED -->
  <div class="p2-section hidden" data-section="sosmed">
    <div class="p2-section-head">
      <span class="ic"><i class="fa-solid fa-share-nodes"></i></span>
      <div>
        <b>Media Sosial</b>
        <small>Tautan akun resmi yang tampil pada navbar dan footer situs</small>
      </div>
    </div>
    <div class="p2-grid-2">
      <div>
        <div class="p2-fld">
          <label><i class="fa-brands fa-facebook"></i> Facebook</label>
          <input name="facebook" id="socFb" value="<?= e($S['facebook']) ?>" placeholder="https://facebook.com/prodi.fisika">
        </div>
        <div class="p2-fld">
          <label><i class="fa-brands fa-instagram"></i> Instagram</label>
          <input name="instagram" id="socIg" value="<?= e($S['instagram']) ?>" placeholder="https://instagram.com/prodi.fisika">
        </div>
        <div class="p2-fld">
          <label><i class="fa-brands fa-youtube"></i> YouTube</label>
          <input name="youtube" id="socYt" value="<?= e($S['youtube']) ?>" placeholder="https://youtube.com/@prodi.fisika">
        </div>
        <div class="p2-fld">
          <label><i class="fa-brands fa-linkedin"></i> LinkedIn</label>
          <input name="linkedin" id="socLi" value="<?= e($S['linkedin']) ?>" placeholder="https://linkedin.com/school/prodi-fisika">
        </div>
      </div>
      <div class="p2-soc-preview">
        <h5><i class="fa-solid fa-eye"></i> Pratinjau Tautan Sosial</h5>
        <div class="p2-soc-grid">
          <a class="p2-soc-item fb" id="sPrevFb" href="<?= e($S['facebook'] ?: '#') ?>" target="_blank">
            <span class="soc-ic"><i class="fa-brands fa-facebook-f"></i></span>
            <div><b>Facebook</b><small id="sFbUrl"><?= e($S['facebook'] ? 'Terhubung' : 'Belum diatur') ?></small></div>
            <span class="arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
          </a>
          <a class="p2-soc-item ig" id="sPrevIg" href="<?= e($S['instagram'] ?: '#') ?>" target="_blank">
            <span class="soc-ic"><i class="fa-brands fa-instagram"></i></span>
            <div><b>Instagram</b><small id="sIgUrl"><?= e($S['instagram'] ? 'Terhubung' : 'Belum diatur') ?></small></div>
            <span class="arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
          </a>
          <a class="p2-soc-item yt" id="sPrevYt" href="<?= e($S['youtube'] ?: '#') ?>" target="_blank">
            <span class="soc-ic"><i class="fa-brands fa-youtube"></i></span>
            <div><b>YouTube</b><small id="sYtUrl"><?= e($S['youtube'] ? 'Terhubung' : 'Belum diatur') ?></small></div>
            <span class="arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
          </a>
          <a class="p2-soc-item li" id="sPrevLi" href="<?= e($S['linkedin'] ?: '#') ?>" target="_blank">
            <span class="soc-ic"><i class="fa-brands fa-linkedin-in"></i></span>
            <div><b>LinkedIn</b><small id="sLiUrl"><?= e($S['linkedin'] ? 'Terhubung' : 'Belum diatur') ?></small></div>
            <span class="arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- SEO & FOOTER -->
  <div class="p2-section hidden" data-section="seo">
    <div class="p2-section-head">
      <span class="ic"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
      <div>
        <b>SEO & Footer</b>
        <small>Optimasi mesin pencari dan teks hak cipta di bagian bawah situs</small>
      </div>
      <div class="tools">
        <button type="button" onclick="p2ResetMeta()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
      </div>
    </div>
    <div class="p2-fld">
      <label><i class="fa-solid fa-bullhorn"></i> Meta Deskripsi (Google) <span class="tip" id="metaCount"><?= strlen($S['meta_description']) ?> / 160 karakter</span></label>
      <textarea name="meta_description" id="metaDesc" rows="3" placeholder="Deskripsi singkat situs untuk hasil pencarian Google (ideal 150-160 karakter)"><?= e($S['meta_description']) ?></textarea>
      <div class="p2-seo-meter">
        <div class="p2-seo-bar"><span id="metaBar" style="width:0%"></span></div>
        <div class="p2-seo-legend">
          <span>0</span>
          <span>50</span>
          <span class="current" id="metaCurrent"><?= strlen($S['meta_description']) ?></span>
          <span>160 (ideal)</span>
          <span>200+</span>
        </div>
      </div>
      <div class="p2-seo-hint" id="metaHint"><i class="fa-solid fa-circle-info"></i> <span>Menunggu analisis...</span></div>

      <div style="margin-top:18px">
        <label style="font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:10px;display:block"><i class="fa-solid fa-eye" style="color:var(--green);margin-right:6px"></i>Pratinjau Hasil Google</label>
        <div style="background:#fff;border:1.6px solid var(--line);border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(0,0,0,.05)">
          <div style="color:#202124;font-size:18px;line-height:1.3;margin-bottom:4px;font-family:arial,sans-serif" id="gTitle"><?= e(($S['site_name'] ?: 'Program Studi Fisika') . ' — ' . ($S['kampus'] ?? '')) ?></div>
          <div style="color:#4d5156;font-size:13px;line-height:1.4;margin-bottom:3px;font-family:arial,sans-serif"><?= e(url('index.php')) ?></div>
          <div style="color:#4d5156;font-size:13px;line-height:1.6;font-family:arial,sans-serif" id="gDesc"><?= e($S['meta_description']) ?></div>
        </div>
      </div>
    </div>
    <div class="p2-fld" style="margin-top:22px">
      <label><i class="fa-solid fa-copyright"></i> Teks Footer</label>
      <input name="footer_text" id="footTxt" value="<?= e($S['footer_text']) ?>" placeholder="© 2026 Program Studi Fisika. Hak Cipta Dilindungi.">
    </div>

    <!-- Preview footer -->
    <div style="margin-top:18px">
      <label style="font-size:11.5px;font-weight:800;color:#475569;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:10px;display:block"><i class="fa-solid fa-eye" style="color:var(--green);margin-right:6px"></i>Pratinjau Footer</label>
      <div class="p2-footer-preview">
        <div class="brand">
          <span class="atom"><i class="fa-solid fa-atom"></i></span>
          <b>PRODI <span style="color:#00b874">FISIKA</span></b>
        </div>
        <p id="pvFootDesc"><?= e($S['meta_description'] ?: 'Website resmi Program Studi Fisika.') ?></p>
        <div class="fbot">
          <span id="pvFootTxt"><?= e($S['footer_text'] ?: '© ' . date('Y') . ' Program Studi Fisika.') ?></span>
          <span>Dikembangkan oleh <b style="color:#fff">Tim IT Prodi Fisika</b></span>
        </div>
      </div>
    </div>
  </div>

  <!-- SUBMIT BAR -->
  <div class="p2-submit">
    <div class="info">
      <span class="dot"></span>
      <span>Perubahan belum disimpan • Klik <b>SIMPAN</b> untuk menerapkan</span>
    </div>
    <button type="button" class="reset" onclick="p2ResetMeta()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
    <button type="submit" name="simpan" value="1"><i class="fa-solid fa-floppy-disk"></i> SIMPAN PERUBAHAN</button>
  </div>
</form>

<!-- Modal reset -->
<div class="p2-modal" id="p2ResetModal">
  <div class="p2-modal-box">
    <div class="p2-modal-body">
      <div class="mic"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3>Reset Meta & Footer?</h3>
      <p>Deskripsi meta dan teks footer akan dikembalikan ke nilai bawaan sistem.</p>
    </div>
    <form method="post" class="p2-modal-foot">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button type="button" onclick="document.getElementById('p2ResetModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i> Batal</button>
      <button type="submit" name="reset_meta" value="1" class="ok"><i class="fa-solid fa-rotate-left"></i> Ya, Reset</button>
    </form>
  </div>
</div>

<script>
(function(){
  /* Score ring animation */
  var score = <?= (int)$score ?>;
  var ring = document.getElementById('p2ScoreRing');
  var num = document.getElementById('p2ScoreNum');
  if (ring && num) {
    var circ = 2 * Math.PI * 88;
    var target = circ - (score / 100) * circ;
    setTimeout(function(){ ring.style.strokeDashoffset = target; }, 200);
    var n = 0, t = setInterval(function(){
      n += 2; if (n >= score) { n = score; clearInterval(t); }
      num.textContent = n;
    }, 22);
  }

  /* Tab switching */
  var tabs = document.querySelectorAll('.p2-tab');
  var sections = document.querySelectorAll('.p2-section');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      var id = this.dataset.tab;
      tabs.forEach(function(x){ x.classList.remove('on'); });
      this.classList.add('on');
      sections.forEach(function(s){ s.classList.toggle('hidden', s.dataset.section !== id); });
      window.scrollTo({ top: document.querySelector('.p2-tabs').offsetTop - 80, behavior: 'smooth' });
    });
  });

  /* Mark unsaved */
  var saved = true;
  var info = document.querySelector('.p2-submit .info span:last-child');
  document.querySelectorAll('#fSettings input, #fSettings textarea, #fSettings select').forEach(function(el){
    el.addEventListener('input', function(){
      saved = false;
      if (info) info.innerHTML = 'Perubahan <b>belum disimpan</b> • Klik <b>SIMPAN</b> untuk menerapkan';
    });
  });
  document.getElementById('fSettings').addEventListener('submit', function(){ saved = true; });

  /* Live previews - Navbar tagline */
  var tag = document.getElementById('sTagline'), navTag = document.getElementById('pvNavTag'), tabTitle = document.getElementById('pvTabTitle'), gTitle = document.getElementById('gTitle'), nameIn = document.getElementById('sName');
  if (tag) tag.addEventListener('input', function(){ if (navTag) navTag.textContent = this.value || 'Departemen Fisika FMIPA'; });
  if (nameIn) nameIn.addEventListener('input', function(){
    if (tabTitle) tabTitle.textContent = (this.value || 'Program Studi Fisika') + ' — ' + (document.querySelector('input[name=kampus]').value || '');
    if (gTitle) gTitle.textContent = (this.value || 'Program Studi Fisika') + ' — ' + (document.querySelector('input[name=kampus]').value || '');
  });
  document.querySelectorAll('input[name=kampus]').forEach(function(k){
    k.addEventListener('input', function(){
      var n = nameIn ? nameIn.value : 'Program Studi Fisika';
      if (tabTitle) tabTitle.textContent = n + ' — ' + this.value;
      if (gTitle) gTitle.textContent = n + ' — ' + this.value;
    });
  });

  /* Logo upload */
  var upLogo = document.getElementById('upLogo'), fLogo = document.getElementById('fLogo'), pvLogo = document.getElementById('pvLogo'), phLogo = document.getElementById('phLogo'), pvNavLogo = document.getElementById('pvNavLogo'), pvNavAtom = document.getElementById('pvNavAtom'), pvTabIcon = document.getElementById('pvTabIcon'), pvTabAtom = document.getElementById('pvTabAtom');
  function showLogo(file){
    var rd = new FileReader();
    rd.onload = function(ev){
      if (pvLogo) { pvLogo.src = ev.target.result; pvLogo.classList.remove('hidden'); }
      if (phLogo) phLogo.classList.add('hidden');
      if (pvNavLogo) { pvNavLogo.src = ev.target.result; pvNavLogo.classList.remove('hidden'); }
      if (pvNavAtom) pvNavAtom.classList.add('hidden');
    };
    rd.readAsDataURL(file);
  }
  if (upLogo && fLogo) {
    upLogo.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG') fLogo.click(); });
    ['dragenter','dragover'].forEach(function(ev){ upLogo.addEventListener(ev, function(e){ e.preventDefault(); upLogo.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ upLogo.addEventListener(ev, function(e){ e.preventDefault(); upLogo.classList.remove('drag'); }); });
    upLogo.addEventListener('drop', function(e){ if (e.dataTransfer.files[0]) { fLogo.files = e.dataTransfer.files; showLogo(e.dataTransfer.files[0]); } });
    fLogo.addEventListener('change', function(){ if (fLogo.files[0]) showLogo(fLogo.files[0]); });
  }
  window.p2RmLogo = function(){
    if (confirm('Hapus logo?')) {
      if (pvLogo) { pvLogo.src=''; pvLogo.classList.add('hidden'); }
      if (phLogo) phLogo.classList.remove('hidden');
      if (pvNavLogo) { pvNavLogo.src=''; pvNavLogo.classList.add('hidden'); }
      if (pvNavAtom) pvNavAtom.classList.remove('hidden');
      if (fLogo) fLogo.value = '';
    }
  };

  /* Favicon upload */
  var upFav = document.getElementById('upFav'), fFav = document.getElementById('fFav'), pvFav = document.getElementById('pvFav'), phFav = document.getElementById('phFav');
  function showFav(file){
    var rd = new FileReader();
    rd.onload = function(ev){
      if (pvFav) { pvFav.src = ev.target.result; pvFav.classList.remove('hidden'); }
      if (phFav) phFav.classList.add('hidden');
      if (pvTabIcon) { pvTabIcon.src = ev.target.result; pvTabIcon.classList.remove('hidden'); }
      if (pvTabAtom) pvTabAtom.classList.add('hidden');
    };
    rd.readAsDataURL(file);
  }
  if (upFav && fFav) {
    upFav.addEventListener('click', function(e){ if (e.target.tagName !== 'IMG') fFav.click(); });
    ['dragenter','dragover'].forEach(function(ev){ upFav.addEventListener(ev, function(e){ e.preventDefault(); upFav.classList.add('drag'); }); });
    ['dragleave','drop'].forEach(function(ev){ upFav.addEventListener(ev, function(e){ e.preventDefault(); upFav.classList.remove('drag'); }); });
    upFav.addEventListener('drop', function(e){ if (e.dataTransfer.files[0]) { fFav.files = e.dataTransfer.files; showFav(e.dataTransfer.files[0]); } });
    fFav.addEventListener('change', function(){ if (fFav.files[0]) showFav(fFav.files[0]); });
  }
  window.p2RmFav = function(){
    if (confirm('Hapus favicon?')) {
      if (pvFav) { pvFav.src=''; pvFav.classList.add('hidden'); }
      if (phFav) phFav.classList.remove('hidden');
      if (pvTabIcon) { pvTabIcon.src=''; pvTabIcon.classList.add('hidden'); }
      if (pvTabAtom) pvTabAtom.classList.remove('hidden');
      if (fFav) fFav.value = '';
    }
  };

  /* Hero live preview */
  var hB = document.getElementById('hBadge'), hT = document.getElementById('hTitle'), hS = document.getElementById('hSubtitle');
  var pvB = document.getElementById('pvHBadge'), pvT = document.getElementById('pvHTitle'), pvS = document.getElementById('pvHSub');
  if (hB) hB.addEventListener('input', function(){ if (pvB) pvB.textContent = this.value || 'PRODI FISIKA'; });
  if (hT) hT.addEventListener('input', function(){ if (pvT) pvT.textContent = this.value || 'Mencetak Ilmuwan & Inovator Fisika Masa Depan'; });
  if (hS) hS.addEventListener('input', function(){ if (pvS) pvS.textContent = this.value || 'Program Studi Fisika — Terakreditasi UNGGUL'; });

  /* Social live preview */
  var socs = {fb:{i:document.getElementById('socFb'),p:document.getElementById('sPrevFb'),u:document.getElementById('sFbUrl')},
              ig:{i:document.getElementById('socIg'),p:document.getElementById('sPrevIg'),u:document.getElementById('sIgUrl')},
              yt:{i:document.getElementById('socYt'),p:document.getElementById('sPrevYt'),u:document.getElementById('sYtUrl')},
              li:{i:document.getElementById('socLi'),p:document.getElementById('sPrevLi'),u:document.getElementById('sLiUrl')}};
  Object.keys(socs).forEach(function(k){
    var s = socs[k];
    if (!s.i) return;
    s.i.addEventListener('input', function(){
      var v = this.value.trim();
      if (s.p) {
        s.p.href = v || '#';
        s.p.classList.toggle('empty', !v);
      }
      if (s.u) s.u.textContent = v ? 'Terhubung' : 'Belum diatur';
    });
  });

  /* Meta description SEO meter */
  var meta = document.getElementById('metaDesc'), bar = document.getElementById('metaBar'), cur = document.getElementById('metaCurrent'), cnt = document.getElementById('metaCount'), hint = document.getElementById('metaHint'), gDesc = document.getElementById('gDesc');
  function updMeta(){
    if (!meta) return;
    var n = meta.value.length;
    if (cnt) cnt.textContent = n + ' / 160 karakter';
    if (cur) cur.textContent = n;
    var pct = Math.min(100, (n / 160) * 100);
    if (bar) {
      bar.style.width = pct + '%';
      if (n < 50) bar.style.background = '#e5484d';
      else if (n >= 120 && n <= 160) bar.style.background = 'var(--grad)';
      else if (n > 160 && n <= 200) bar.style.background = '#f59e0b';
      else if (n > 200) bar.style.background = '#e5484d';
      else bar.style.background = '#0a66c2';
    }
    if (hint) {
      if (n < 50) { hint.className = 'p2-seo-hint bad'; hint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span>Terlalu pendek. Ideal 120–160 karakter untuk hasil Google optimal.</span>'; }
      else if (n <= 160) { hint.className = 'p2-seo-hint good'; hint.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>Panjang ideal. Deskripsi akan tampil utuh di hasil Google.</span>'; }
      else if (n <= 200) { hint.className = 'p2-seo-hint warn'; hint.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> <span>Agak panjang. Google mungkin memotong deskripsi di hasil pencarian.</span>'; }
      else { hint.className = 'p2-seo-hint bad'; hint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span>Terlalu panjang. Persingkat menjadi maksimal 160 karakter.</span>'; }
    }
    if (gDesc) gDesc.textContent = meta.value || '(Belum ada deskripsi)';
  }
  if (meta) { meta.addEventListener('input', updMeta); updMeta(); }

  /* Footer text live */
  var ft = document.getElementById('footTxt'), pvFt = document.getElementById('pvFootTxt'), mD = document.getElementById('metaDesc'), pvFd = document.getElementById('pvFootDesc');
  if (ft) ft.addEventListener('input', function(){ if (pvFt) pvFt.textContent = this.value || '© ' + new Date().getFullYear() + ' Program Studi Fisika.'; });
  if (mD && pvFd) mD.addEventListener('input', function(){ if (pvFd) pvFd.textContent = this.value || 'Website resmi Program Studi Fisika.'; });

  /* Reset meta modal */
  window.p2ResetMeta = function(){ document.getElementById('p2ResetModal').classList.add('open'); };
  document.getElementById('p2ResetModal').addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });

  /* Warn before leaving if unsaved */
  window.addEventListener('beforeunload', function(e){
    if (!saved) { e.preventDefault(); e.returnValue = ''; }
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>