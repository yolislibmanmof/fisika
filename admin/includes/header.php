<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/security.php';
sec_send_headers();
sec_touch_session();
require_login();
define('A2_START', microtime(true));
$S = get_setting($conn);
$ACTIVE = $ACTIVE ?? '';
$TITLE  = $TITLE ?? 'Dashboard';

$SUB = [
  'index.php'    => 'Ringkasan aktivitas, statistik, dan akses cepat',
  'pengaturan.php'=> 'Identitas situs, logo, favicon, hero, dan media sosial',
  'hero.php'     => 'Kelola slide foto & video pada beranda public',
  'sambutan.php' => 'Editor sambutan Kaprodi dengan pratinjau langsung',
  'halaman.php'  => 'Kelola halaman statis profil & layanan akademik',
  'fitur.php'    => 'Kartu keunggulan beranda beserta pemilihan ikon',
  'stats.php'    => 'Angka statistik beranda yang dapat disesuaikan',
  'berita.php'   => 'Manajemen artikel, kategori, dan publikasi',
  'galeri.php'   => 'Dokumentasi foto & video kegiatan prodi',
  'menu.php'     => 'Struktur navigasi public beserta dropdown',
  'users.php'    => 'Manajemen akun administrator & editor',
];
$hour  = (int)date('G');
$greet = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));
$firstName = explode(' ', (string)$_SESSION['admin_name'])[0];
$nDraftB = (int)(q1($conn, "SELECT COUNT(*) AS c FROM berita WHERE status=0")['c'] ?? 0);
$nDraftP = (int)(q1($conn, "SELECT COUNT(*) AS c FROM pages WHERE status=0")['c'] ?? 0);
$nVid    = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri WHERE tipe='video'")['c'] ?? 0);
$nNotif  = $nDraftB + $nDraftP;

$palNav = [
  ['index.php','fa-gauge-high','Dashboard'],['pengaturan.php','fa-sliders','Pengaturan Situs'],
  ['hero.php','fa-photo-film','Hero Slider'],['sambutan.php','fa-comment-dots','Sambutan Kaprodi'],
  ['halaman.php','fa-file-lines','Halaman Statis'],['fitur.php','fa-shapes','Fitur & Ikon'],
  ['stats.php','fa-chart-simple','Statistik'],['berita.php','fa-newspaper','Berita'],
  ['galeri.php','fa-images','Galeri'],['menu.php','fa-bars-progress','Menu Navigasi'],
  ['users.php','fa-users-gear','Pengguna'],['logout.php','fa-right-from-bracket','Keluar'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($TITLE) ?> — Admin Fisika</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/admin/css/admin.css') ?>">
<style>
/* ========= ADMIN ULTIMATE v4 (inline — admin.css tetap final) ========= */
@keyframes a2slide{to{background-position:200% 0}}
@keyframes a2beat{70%{box-shadow:0 0 0 6px rgba(0,224,138,0)}100%{box-shadow:0 0 0 0 rgba(0,224,138,0)}}
@keyframes a2zin{from{transform:scale(.94) translateY(-14px);opacity:0}}
@keyframes a2tin{from{opacity:0;transform:translateX(40px)}}
.a2-topline{position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#0a66c2,#00b874,#0a66c2);background-size:200% 100%;animation:a2slide 6s linear infinite;z-index:990}
.admin-top{top:3px!important;height:64px!important;background:rgba(255,255,255,.93)!important;backdrop-filter:blur(14px);border-bottom:1px solid var(--line)!important;box-shadow:0 4px 24px rgba(10,42,94,.08)!important}
.layout{padding-top:67px!important}
.sidebar{top:67px!important;background:linear-gradient(180deg,#071a39 0%,#08254f 55%,#0a2a5e 100%)!important}
.content{padding:26px 28px 0!important}

/* Salam & jam */
.a2-greet{display:flex;align-items:center;gap:9px;background:var(--gray);border:1px solid var(--line);border-radius:12px;padding:8px 14px;font-size:12.5px;color:#475569;font-weight:600;white-space:nowrap}
.a2-greet i{color:var(--green)}
.a2-greet b{color:var(--blue-dark)}
.a2-greet .clk{font-variant-numeric:tabular-nums;color:var(--blue);font-weight:800}

/* Tombol command palette */
.a2-pal-btn{display:flex;align-items:center;gap:10px;background:var(--gray);border:1.4px solid var(--line);border-radius:11px;padding:9px 14px;color:#7b8aa3;font-size:13px;cursor:pointer;transition:.25s;min-width:210px;font-family:var(--body)}
.a2-pal-btn:hover{border-color:var(--blue);color:var(--blue);background:#fff;transform:translateY(-1px)}
.a2-pal-btn kbd{margin-left:auto;background:#fff;border:1px solid var(--line);border-radius:6px;padding:2px 7px;font-size:10px;font-weight:800;color:#7b8aa3}

/* Dropdown elegan */
.a2-dd{position:relative}
.a2-dd-menu{position:absolute;right:0;top:calc(100% + 12px);background:#fff;border-radius:14px;border:1px solid var(--line);box-shadow:0 20px 55px rgba(10,42,94,.2);opacity:0;transform:translateY(12px) scale(.97);transform-origin:top right;pointer-events:none;transition:.32s cubic-bezier(.22,.61,.36,1);overflow:hidden;z-index:1250;padding:6px;min-width:230px}
.a2-dd.open .a2-dd-menu{opacity:1;transform:none;pointer-events:auto}
.a2-dd-head{display:flex;align-items:center;gap:9px;padding:11px 14px;font-size:10.5px;font-weight:800;letter-spacing:1.6px;color:#7b8aa3;border-bottom:1px solid var(--line);margin-bottom:6px}
.a2-dd-head i{color:var(--green)}
.a2-dd-head small{margin-left:auto;color:#9aa8bf;letter-spacing:.6px}
.a2-dd-item{display:flex;align-items:center;gap:11px;padding:10px 14px;border-radius:9px;font-size:13.5px;font-weight:600;color:#33415e;transition:.22s}
.a2-dd-item i{width:26px;height:26px;border-radius:8px;background:var(--gray);color:var(--blue);display:grid;place-items:center;font-size:11.5px;transition:.22s;flex:none}
.a2-dd-item:hover{background:var(--gray);color:var(--blue);transform:translateX(4px)}
.a2-dd-item:hover i{background:var(--grad);color:#fff}
.a2-dd-item.danger{color:#b3261e}
.a2-dd-item.danger i{background:#fdecec;color:#b3261e}
.a2-dd-item.danger:hover{background:#fdecec;color:#b3261e}
.a2-dd-div{height:1px;background:var(--line);margin:6px 8px}
.a2-dd-empty{padding:18px;text-align:center;color:#7b8aa3;font-size:12.5px;font-weight:600}
.a2-dd-empty i{color:var(--green);margin-right:7px}
.a2-bell{position:relative}
.a2-bell .bdg{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;border-radius:99px;background:#e5484d;color:#fff;font-size:9.5px;font-weight:800;display:grid;place-items:center;padding:0 4px;border:2px solid #fff}
button.user-chip{font-family:var(--body);cursor:pointer}
button.user-chip .fa-chevron-down{font-size:10px;color:#9aa8bf;transition:.3s}
.a2-dd.open button.user-chip .fa-chevron-down{transform:rotate(180deg)}

/* Page head kaya */
.page-head{flex-direction:row!important;align-items:center!important;justify-content:space-between!important;background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px 24px;box-shadow:var(--shadow);margin-bottom:24px;position:relative;overflow:hidden;flex-wrap:wrap;gap:12px}
.page-head::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--grad)}
.page-head h1{font-size:22px!important}
.ph-sub{display:block;color:#7b8aa3;font-size:13px;font-weight:500;margin-top:4px}
.ph-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ph-date{background:var(--gray);border:1px solid var(--line);border-radius:10px;padding:8px 14px;font-size:12px;font-weight:700;color:#475569;display:flex;gap:8px;align-items:center}
.ph-date i{color:var(--green)}

/* Command palette */
.a2-palette{position:fixed;inset:0;background:rgba(7,26,57,.6);backdrop-filter:blur(6px);z-index:1600;display:none;align-items:flex-start;justify-content:center;padding-top:12vh}
.a2-palette.open{display:flex}
.a2-pal-box{width:min(620px,92%);background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 40px 90px rgba(0,0,0,.4);animation:a2zin .35s}
.a2-pal-in{display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid var(--line)}
.a2-pal-in i{color:var(--blue);font-size:16px}
.a2-pal-in input{flex:1;border:0;outline:0;font-family:var(--body);font-size:15px;color:#101828}
.a2-pal-in kbd{background:var(--gray);border:1px solid var(--line);border-radius:6px;padding:3px 8px;font-size:10.5px;font-weight:800;color:#7b8aa3}
.a2-pal-list{max-height:320px;overflow-y:auto;padding:10px}
.a2-pal-list a{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:11px;color:#33415e;font-weight:600;font-size:14px;transition:.2s}
.a2-pal-list a i{width:32px;height:32px;border-radius:9px;background:var(--gray);color:var(--blue);display:grid;place-items:center;font-size:13px;transition:.2s;flex:none}
.a2-pal-list a:hover{background:var(--gray);color:var(--blue)}
.a2-pal-list a:hover i{background:var(--grad);color:#fff}
.a2-pal-list a span{margin-left:auto;font-size:10px;color:#9aa8bf;font-weight:800;letter-spacing:1.2px}
.a2-pal-foot{display:flex;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--line);background:#fbfcfe;font-size:11px;color:#9aa8bf;font-weight:600;flex-wrap:wrap;gap:8px}
.a2-pal-foot kbd{background:#fff;border:1px solid var(--line);border-radius:5px;padding:1px 6px;font-size:10px;font-weight:800}

/* Pencarian ikon dalam modal */
.a2-icon-search{padding:14px 18px;border-bottom:1px solid var(--line);position:relative}
.a2-icon-search i{position:absolute;left:32px;top:50%;transform:translateY(-50%);color:#9aa8bf;font-size:13px}
.a2-icon-search input{width:100%;padding:11px 14px 11px 38px;border:1.6px solid var(--line);border-radius:10px;font-family:var(--body);font-size:13px;outline:0}
.a2-icon-search input:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(10,102,194,.1)}

/* Toast */
.a2-toasts{position:fixed;right:24px;bottom:24px;z-index:2000;display:flex;flex-direction:column;gap:10px}
.a2-toast{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid var(--line);border-left:4px solid var(--green);border-radius:12px;padding:14px 18px;box-shadow:0 16px 40px rgba(10,42,94,.2);font-size:13px;font-weight:600;color:#22304a;animation:a2tin .4s cubic-bezier(.22,.61,.36,1);min-width:260px}
.a2-toast.err{border-left-color:#e5484d}
.a2-toast i{color:var(--green);font-size:16px}
.a2-toast.err i{color:#e5484d}

/* Footbar sistem */
.a2-footbar{margin:34px -28px 0 -28px;padding:16px 28px;border-top:1px solid var(--line);background:#fff;display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:12px;color:#7b8aa3;font-weight:600}
.a2-footbar .chip{display:inline-flex;align-items:center;gap:7px;background:var(--gray);border:1px solid var(--line);border-radius:99px;padding:6px 12px;font-weight:700;font-size:11px;color:#475569}
.a2-footbar .chip i{color:var(--green)}
.a2-footbar .sp{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.a2-footbar a.chip:hover{border-color:var(--green);color:var(--green);transform:translateY(-2px)}

@media(max-width:1200px){.a2-greet{display:none}.a2-pal-btn{min-width:0}.a2-pal-btn span{display:none}}
@media(max-width:700px){.a2-pal-btn kbd{display:none}.ph-right{display:none}}
</style>
</head>
<body>
<div class="a2-topline"></div>
<div class="admin-top">
  <button class="icon-btn" id="btnSidebar"><i class="fa-solid fa-bars"></i></button>
  <div class="at-brand"><span class="brand-atom"><i class="fa-solid fa-atom"></i></span> ADMIN <b>FISIKA</b></div>
  <span class="a2-greet"><i class="fa-regular fa-sun"></i> <?= e($greet) ?>, <b><?= e($firstName) ?></b> <span class="clk">• <span id="a2Clock">--:--:--</span> WIB</span></span>
  <div class="at-right">
    <button class="a2-pal-btn" id="a2PalBtn" title="Cari menu (Ctrl+K)"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari menu...</span> <kbd>Ctrl K</kbd></button>

    <div class="a2-dd">
      <button class="icon-btn" data-dd title="Buat cepat"><i class="fa-solid fa-plus"></i></button>
      <div class="a2-dd-menu">
        <div class="a2-dd-head"><i class="fa-solid fa-bolt"></i> AKSI CEPAT</div>
        <a class="a2-dd-item" href="<?= url('admin/berita.php?edit=0') ?>"><i class="fa-solid fa-newspaper"></i> Tulis Berita</a>
        <a class="a2-dd-item" href="<?= url('admin/galeri.php?edit=0') ?>"><i class="fa-solid fa-images"></i> Tambah Galeri</a>
        <a class="a2-dd-item" href="<?= url('admin/halaman.php?edit=0') ?>"><i class="fa-solid fa-file-lines"></i> Halaman Baru</a>
        <a class="a2-dd-item" href="<?= url('admin/hero.php?edit=0') ?>"><i class="fa-solid fa-photo-film"></i> Slide Hero Baru</a>
        <div class="a2-dd-div"></div>
        <a class="a2-dd-item" href="<?= url('admin/users.php?edit=0') ?>"><i class="fa-solid fa-user-plus"></i> Pengguna Baru</a>
      </div>
    </div>

    <div class="a2-dd">
      <button class="icon-btn a2-bell" data-dd title="Notifikasi"><i class="fa-regular fa-bell"></i><?php if ($nNotif > 0): ?><span class="bdg"><?= $nNotif ?></span><?php endif; ?></button>
      <div class="a2-dd-menu" style="min-width:290px">
        <div class="a2-dd-head"><i class="fa-regular fa-bell"></i> NOTIFIKASI <small><?= $nNotif ?> baru</small></div>
        <?php if ($nDraftB > 0): ?><a class="a2-dd-item" href="<?= url('admin/berita.php') ?>"><i class="fa-solid fa-newspaper" style="color:#f59e0b"></i> <?= $nDraftB ?> berita masih berupa draft</a><?php endif; ?>
        <?php if ($nDraftP > 0): ?><a class="a2-dd-item" href="<?= url('admin/halaman.php') ?>"><i class="fa-solid fa-file-lines" style="color:#f59e0b"></i> <?= $nDraftP ?> halaman masih berupa draft</a><?php endif; ?>
        <a class="a2-dd-item" href="<?= url('admin/galeri.php') ?>"><i class="fa-solid fa-video" style="color:#0a66c2"></i> <?= $nVid ?> video pada galeri</a>
        <?php if ($nNotif === 0): ?><div class="a2-dd-empty"><i class="fa-solid fa-circle-check"></i> Semua konten telah tayang</div><?php endif; ?>
      </div>
    </div>

    <button class="icon-btn" id="a2Full" title="Layar penuh"><i class="fa-solid fa-expand"></i></button>
    <a class="icon-btn" title="Lihat website" target="_blank" href="<?= url('index.php') ?>"><i class="fa-solid fa-globe"></i></a>

    <div class="a2-dd">
      <button class="user-chip" data-dd>
        <span class="uc-ava"><i class="fa-solid fa-user-shield"></i></span>
        <span class="uc-txt"><b><?= e($_SESSION['admin_name']) ?></b><small><?= e($_SESSION['admin_role']) ?></small></span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
      <div class="a2-dd-menu">
        <div class="a2-dd-head"><i class="fa-solid fa-circle-user"></i> <?= e($_SESSION['admin_name']) ?> <small><?= ucfirst(e($_SESSION['admin_role'])) ?></small></div>
        <a class="a2-dd-item" href="<?= url('admin/users.php?edit=' . (int)$_SESSION['admin_id']) ?>"><i class="fa-solid fa-id-badge"></i> Profil Saya</a>
        <a class="a2-dd-item" href="<?= url('admin/pengaturan.php') ?>"><i class="fa-solid fa-sliders"></i> Pengaturan Situs</a>
        <a class="a2-dd-item" target="_blank" href="<?= url('index.php') ?>"><i class="fa-solid fa-globe"></i> Lihat Website</a>
        <div class="a2-dd-div"></div>
        <a class="a2-dd-item danger" href="<?= url('admin/logout.php') ?>"><i class="fa-solid fa-right-from-bracket"></i> Keluar Aman</a>
      </div>
    </div>
  </div>
</div>

<!-- Command Palette -->
<div class="a2-palette" id="a2Palette">
  <div class="a2-pal-box">
    <div class="a2-pal-in"><i class="fa-solid fa-magnifying-glass"></i><input id="a2PalInput" placeholder="Ketik nama menu atau aksi..." autocomplete="off"><kbd>ESC</kbd></div>
    <div class="a2-pal-list">
      <?php foreach ($palNav as $pn): ?>
      <a href="<?= url('admin/' . $pn[0]) ?>"><i class="fa-solid <?= $pn[1] ?>"></i> <?= e($pn[2]) ?> <span>ADMIN</span></a>
      <?php endforeach; ?>
    </div>
    <div class="a2-pal-foot"><span><kbd>Ctrl</kbd>+<kbd>K</kbd> buka / tutup</span><span><kbd>Enter</kbd> pilih hasil pertama • <kbd>Esc</kbd> tutup</span></div>
  </div>
</div>

<div class="layout">
<?php require_once __DIR__ . '/sidebar.php'; ?>
<main class="content">
  <div class="page-head">
    <div>
      <h1><?= e($TITLE) ?></h1>
      <span class="ph-sub"><?= e($SUB[$ACTIVE] ?? 'Panel manajemen konten Program Studi Fisika') ?></span>
    </div>
    <div class="ph-right">
      <span class="ph-date"><i class="fa-regular fa-calendar-days"></i> <?= date('d F Y') ?></span>
      <span class="ph-crumb" style="margin:0"><i class="fa-solid fa-house"></i> Admin <i class="fa-solid fa-angle-right"></i> <?= e($TITLE) ?></span>
    </div>
  </div>