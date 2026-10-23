<?php
require_once __DIR__ . '/includes/header.php';
$kat    = trim($_GET['kat'] ?? '');
$tahun  = trim($_GET['tahun'] ?? '');
$tipe   = trim($_GET['tipe'] ?? '');
$size   = trim($_GET['size'] ?? 'medium');

$where = " WHERE 1=1"; $params = []; $types = '';
if ($kat !== '')   { $where .= " AND kategori=?"; $params[] = $kat; $types .= 's'; }
if ($tahun !== '') { $where .= " AND YEAR(tanggal)=?"; $params[] = (int)$tahun; $types .= 'i'; }
if ($tipe !== '')  { $where .= " AND tipe=?"; $params[] = $tipe; $types .= 's'; }

$items = qp($conn, "SELECT * FROM galeri$where ORDER BY urutan ASC, tanggal DESC", $types, $params);
$kats  = q($conn, "SELECT kategori, COUNT(*) AS c FROM galeri GROUP BY kategori ORDER BY c DESC");
$tahuns = q($conn, "SELECT YEAR(tanggal) AS y, COUNT(*) AS c FROM galeri WHERE tanggal IS NOT NULL GROUP BY YEAR(tanggal) ORDER BY y DESC");
$totalFoto  = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri WHERE tipe='image'")['c'] ?? 0);
$totalVideo = (int)(q1($conn, "SELECT COUNT(*) AS c FROM galeri WHERE tipe='video'")['c'] ?? 0);
$totalKats  = count($kats);
?>
<style>
/* ========= ULTIMATE GALERI ========= */
.u-gal-hero{position:relative;min-height:480px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:linear-gradient(135deg,#071a39 0%,#0a2a5e 50%,#0a66c2 100%);padding:90px 0;color:#fff}
.u-gal-hero::before{content:'';position:absolute;inset:0;background-image:
  radial-gradient(circle at 15% 25%, rgba(0,184,116,.2), transparent 40%),
  radial-gradient(circle at 85% 75%, rgba(10,102,194,.3), transparent 40%);pointer-events:none}
.u-gal-hero::after{content:'';position:absolute;inset:0;background-image:
  linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
  linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
  background-size:50px 50px;animation:uGrid 20s linear infinite}
@keyframes uGrid{to{background-position:50px 50px}}
.u-gal-hero .u-inner{position:relative;z-index:2;text-align:center;max-width:760px;padding:0 20px}
.u-gal-hero h1{font-family:var(--display);font-size:clamp(34px,5vw,54px);line-height:1.15;margin-bottom:16px}
.u-gal-hero h1 span{background:linear-gradient(90deg,#7ef0c4,#4da3ff);-webkit-background-clip:text;background-clip:text;color:transparent}
.u-gal-hero p{color:#c8d6ea;font-size:16px;line-height:1.8;margin-bottom:28px}
.u-gal-stats{display:flex;gap:28px;justify-content:center;flex-wrap:wrap;margin-top:28px}
.u-gal-stats .gs{background:rgba(255,255,255,.06);backdrop-filter:blur(10px);padding:16px 28px;border-radius:14px;border:1px solid rgba(255,255,255,.1);min-width:140px}
.u-gal-stats .gs b{display:block;font-family:var(--display);font-size:28px;color:#fff}
.u-gal-stats .gs b em{color:#7ef0c4;font-style:normal}
.u-gal-stats .gs small{font-size:11px;letter-spacing:1.5px;color:#9fb3cd;font-weight:700;text-transform:uppercase}

.u-gal-tools{background:#fff;border-radius:18px;padding:20px 24px;box-shadow:var(--shadow);border:1px solid var(--line);margin:-40px auto 34px;position:relative;z-index:3;width:min(1200px,92%);display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.u-gal-tools .u-gt-group{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.u-gal-tools .u-gt-label{font-size:12px;font-weight:800;color:var(--blue-dark);letter-spacing:1px;text-transform:uppercase;display:flex;align-items:center;gap:6px}
.u-gal-tools .u-gt-label i{color:#00b874}
.u-gal-tools select{padding:10px 14px;border:1.6px solid var(--line);border-radius:10px;background:#fbfcfe;font-family:var(--body);font-size:12.5px;font-weight:700;color:#33415e;cursor:pointer;outline:0}
.u-gal-tools select:focus{border-color:var(--blue)}
.u-gal-tools .u-size-toggle{display:flex;background:var(--gray);border-radius:10px;padding:4px;border:1px solid var(--line)}
.u-gal-tools .u-size-toggle button{border:0;background:transparent;padding:8px 14px;border-radius:7px;cursor:pointer;font-weight:700;font-size:12px;color:#68758a;display:flex;align-items:center;gap:6px;transition:.25s}
.u-gal-tools .u-size-toggle button.on{background:var(--grad);color:#fff;box-shadow:0 4px 12px rgba(10,102,194,.25)}
.u-gal-tools .u-gt-info{margin-left:auto;font-size:13px;color:#68758a;font-weight:600}
.u-gal-tools .u-gt-info b{color:var(--blue-dark);font-family:var(--display);font-size:16px}

.u-gal-grid{column-gap:16px;column-count:3}
.u-gal-grid.u-small{column-count:4;column-gap:10px}
.u-gal-grid.u-large{column-count:2;column-gap:22px}
.u-gal-item{break-inside:avoid;margin-bottom:16px;position:relative;border-radius:16px;overflow:hidden;cursor:pointer;background:#04101f;box-shadow:var(--shadow);transition:.4s}
.u-gal-grid.u-small .u-gal-item{margin-bottom:10px;border-radius:12px}
.u-gal-grid.u-large .u-gal-item{margin-bottom:22px;border-radius:18px}
.u-gal-item:hover{transform:translateY(-6px);box-shadow:0 24px 60px rgba(10,42,94,.25)}
.u-gal-item img,.u-gal-item video{width:100%;display:block;transition:.6s}
.u-gal-item:hover img,.u-gal-item:hover video{transform:scale(1.06)}
.u-gal-item .u-video-ph{position:relative;aspect-ratio:16/10;background:#04101f;display:grid;place-items:center;color:#7ef0c4}
.u-gal-item .u-video-ph i{font-size:48px;opacity:.9}
.u-gal-item .u-over{position:absolute;inset:0;background:linear-gradient(180deg,transparent 30%,rgba(7,26,57,.96));display:flex;flex-direction:column;justify-content:flex-end;padding:20px;opacity:0;transition:.4s}
.u-gal-item:hover .u-over{opacity:1}
.u-gal-item .u-over p{color:#fff;font-family:var(--display);font-weight:700;font-size:15px;line-height:1.4;margin-bottom:6px}
.u-gal-item .u-over small{color:#7ef0c4;font-weight:700;font-size:11px;letter-spacing:1.4px;text-transform:uppercase}
.u-gal-item .u-over .u-actions{display:flex;gap:8px;margin-top:14px;opacity:0;transform:translateY(10px);transition:.4s .1s}
.u-gal-item:hover .u-over .u-actions{opacity:1;transform:none}
.u-gal-item .u-over button{width:36px;height:36px;border-radius:10px;border:0;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);color:#fff;cursor:pointer;font-size:13px;transition:.25s;border:1px solid rgba(255,255,255,.2)}
.u-gal-item .u-over button:hover{background:var(--grad);border-color:transparent;transform:scale(1.1)}
.u-gal-item .u-badge-type{position:absolute;top:12px;left:12px;background:rgba(7,26,57,.85);backdrop-filter:blur(10px);color:#fff;padding:5px 12px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;z-index:2;border:1px solid rgba(255,255,255,.15)}
.u-gal-item .u-badge-type i{color:#7ef0c4;margin-right:5px}
.u-gal-item .u-date{position:absolute;top:12px;right:12px;background:rgba(0,184,116,.95);color:#04231a;padding:4px 10px;border-radius:9px;font-size:10px;font-weight:800;letter-spacing:.5px;z-index:2}

.u-empty-gal{text-align:center;padding:80px 20px;background:#fff;border-radius:18px;border:2px dashed var(--line)}
.u-empty-gal i{font-size:64px;color:#cfd8e3;margin-bottom:18px}
.u-empty-gal h3{font-family:var(--display);color:var(--blue-dark);margin-bottom:10px}
.u-empty-gal p{color:#68758a;margin-bottom:20px}

.u-slideshow{position:fixed;inset:0;background:rgba(4,16,31,.98);z-index:1500;display:none;align-items:center;justify-content:center;padding:5vh}
.u-slideshow.open{display:flex}
.u-slideshow img{max-height:88vh;max-width:90vw;border-radius:16px;box-shadow:0 30px 80px rgba(0,0,0,.6);animation:uZoomIn .5s}
.u-slideshow .u-sl-caption{position:absolute;bottom:30px;left:50%;transform:translateX(-50%);background:rgba(255,255,255,.1);backdrop-filter:blur(14px);padding:12px 24px;border-radius:12px;color:#fff;text-align:center;border:1px solid rgba(255,255,255,.15);max-width:500px}
.u-slideshow .u-sl-caption b{font-family:var(--display);display:block;margin-bottom:4px}
.u-slideshow .u-sl-caption small{color:#7ef0c4;font-weight:700;font-size:11.5px;letter-spacing:1px}
.u-slideshow button{position:absolute;width:56px;height:56px;border-radius:50%;border:0;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);color:#fff;font-size:18px;cursor:pointer;transition:.3s;border:1px solid rgba(255,255,255,.2)}
.u-slideshow button:hover{background:var(--grad);border-color:transparent;transform:scale(1.1)}
.u-slideshow .u-sl-close{top:26px;right:30px}
.u-slideshow .u-sl-prev{left:30px;top:50%;transform:translateY(-50%)}
.u-slideshow .u-sl-next{right:30px;top:50%;transform:translateY(-50%)}
.u-slideshow .u-sl-counter{position:absolute;top:30px;left:30px;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);padding:10px 18px;border-radius:99px;color:#fff;font-weight:800;font-size:12.5px;letter-spacing:1px;border:1px solid rgba(255,255,255,.15)}
.u-slideshow .u-sl-progress{position:absolute;top:0;left:0;right:0;height:3px;background:rgba(255,255,255,.1)}
.u-slideshow .u-sl-progress span{display:block;height:100%;background:var(--grad);transition:width .3s}
.u-slideshow .u-sl-thumbs{position:absolute;bottom:100px;left:50%;transform:translateX(-50%);display:flex;gap:8px;max-width:90vw;overflow-x:auto;padding:6px}
.u-slideshow .u-sl-thumbs img{width:60px;height:44px;object-fit:cover;border-radius:8px;cursor:pointer;opacity:.5;transition:.25s;border:2px solid transparent}
.u-slideshow .u-sl-thumbs img.on{opacity:1;border-color:#7ef0c4}
@keyframes uZoomIn{from{transform:scale(.85);opacity:0}}

.u-gal-info-strip{background:linear-gradient(135deg,#0a2a5e,#071a39);color:#fff;border-radius:18px;padding:28px 32px;margin-top:44px;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;position:relative;overflow:hidden}
.u-gal-info-strip::before{content:'';position:absolute;right:-60px;top:-60px;width:180px;height:180px;border:2px dashed rgba(0,184,116,.25);border-radius:50%;animation:uSpin 22s linear infinite}
.u-gal-info-strip .u-gii{text-align:center;position:relative;z-index:1}
.u-gal-info-strip .u-gii i{font-size:26px;color:#7ef0c4;margin-bottom:10px}
.u-gal-info-strip .u-gii b{display:block;font-family:var(--display);font-size:26px;color:#fff}
.u-gal-info-strip .u-gii small{color:#c8d6ea;font-size:11.5px;font-weight:600;letter-spacing:1px;text-transform:uppercase}

@media(max-width:1024px){.u-gal-grid{column-count:2}.u-gal-grid.u-small{column-count:3}.u-gal-grid.u-large{column-count:2}.u-gal-info-strip{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.u-gal-grid,.u-gal-grid.u-small,.u-gal-grid.u-large{column-count:1}.u-slideshow .u-sl-thumbs{display:none}.u-gal-info-strip{grid-template-columns:1fr}}
</style>

<!-- ===== HERO GALERI ===== -->
<section class="u-gal-hero">
  <div class="container u-inner reveal">
    <span class="sec-tag light"><i class="fa-solid fa-images"></i> DOKUMENTASI VISUAL</span>
    <h1>Galeri <span>Kegiatan & Aktivitas</span></h1>
    <p>Jelajahi koleksi dokumentasi visual Program Studi Fisika — dari kegiatan praktikum, seminar ilmiah, hingga momen kebersamaan mahasiswa dan dosen.</p>
    <div class="u-gal-stats">
      <div class="gs reveal"><b><?= count($items) ?><em>+</em></b><small>Ditampilkan</small></div>
      <div class="gs reveal"><b><?= $totalFoto ?></b><small>Foto</small></div>
      <div class="gs reveal"><b><?= $totalVideo ?></b><small>Video</small></div>
      <div class="gs reveal"><b><?= $totalKats ?></b><small>Kategori</small></div>
    </div>
  </div>
</section>

<!-- ===== TOOLS BAR ===== -->
<div class="u-gal-tools container reveal">
  <div class="u-gt-group">
    <span class="u-gt-label"><i class="fa-solid fa-filter"></i>Kategori</span>
    <select onchange="location.href='galeri.php?kat='+this.value+'<?= $tahun ? '&tahun='.urlencode($tahun) : '' ?><?= $tipe ? '&tipe='.urlencode($tipe) : '' ?>'">
      <option value="">Semua Kategori</option>
      <?php foreach ($kats as $k): ?>
      <option value="<?= e($k['kategori']) ?>" <?= $kat === $k['kategori'] ? 'selected' : '' ?>><?= e($k['kategori']) ?> (<?= (int)$k['c'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="u-gt-group">
    <span class="u-gt-label"><i class="fa-regular fa-calendar"></i>Tahun</span>
    <select onchange="location.href='galeri.php?tahun='+this.value+'<?= $kat ? '&kat='.urlencode($kat) : '' ?><?= $tipe ? '&tipe='.urlencode($tipe) : '' ?>'">
      <option value="">Semua Tahun</option>
      <?php foreach ($tahuns as $t): ?>
      <option value="<?= (int)$t['y'] ?>" <?= $tahun === (string)$t['y'] ? 'selected' : '' ?>><?= (int)$t['y'] ?> (<?= (int)$t['c'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="u-gt-group">
    <span class="u-gt-label"><i class="fa-solid fa-photo-film"></i>Tipe</span>
    <select onchange="location.href='galeri.php?tipe='+this.value+'<?= $kat ? '&kat='.urlencode($kat) : '' ?><?= $tahun ? '&tahun='.urlencode($tahun) : '' ?>'">
      <option value="">Semua Tipe</option>
      <option value="image" <?= $tipe === 'image' ? 'selected' : '' ?>>Foto</option>
      <option value="video" <?= $tipe === 'video' ? 'selected' : '' ?>>Video</option>
    </select>
  </div>
  <div class="u-gt-group">
    <span class="u-gt-label"><i class="fa-solid fa-table-cells"></i>Ukuran</span>
    <div class="u-size-toggle">
      <button class="<?= $size === 'small' ? 'on' : '' ?>" onclick="location.href='galeri.php?<?= http_build_query(array_filter(['kat'=>$kat,'tahun'=>$tahun,'tipe'=>$tipe,'size'=>'small'])) ?>'"><i class="fa-solid fa-grip"></i> Kecil</button>
      <button class="<?= $size === 'medium' ? 'on' : '' ?>" onclick="location.href='galeri.php?<?= http_build_query(array_filter(['kat'=>$kat,'tahun'=>$tahun,'tipe'=>$tipe,'size'=>'medium'])) ?>'"><i class="fa-solid fa-table-cells-large"></i> Sedang</button>
      <button class="<?= $size === 'large' ? 'on' : '' ?>" onclick="location.href='galeri.php?<?= http_build_query(array_filter(['kat'=>$kat,'tahun'=>$tahun,'tipe'=>$tipe,'size'=>'large'])) ?>'"><i class="fa-solid fa-expand"></i> Besar</button>
    </div>
  </div>
  <div class="u-gt-info">
    Menampilkan <b><?= count($items) ?></b> item
    <?php if ($kat || $tahun || $tipe): ?> &nbsp;<a href="galeri.php" style="color:var(--blue);font-weight:700"><i class="fa-solid fa-xmark"></i> Reset</a><?php endif; ?>
  </div>
</div>

<!-- ===== GALLERY GRID ===== -->
<section class="page-sec" style="padding-top:0">
  <div class="container">
    <?php if (!$items): ?>
    <div class="u-empty-gal reveal">
      <i class="fa-solid fa-images"></i>
      <h3>Dokumentasi tidak ditemukan</h3>
      <p>Coba gunakan filter lain atau lihat seluruh koleksi.</p>
      <a href="galeri.php" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Tampilkan Semua</a>
    </div>
    <?php else: ?>
    <div class="u-gal-grid <?= $size === 'small' ? 'u-small' : ($size === 'large' ? 'u-large' : '') ?>">
      <?php foreach ($items as $idx => $g): ?>
      <div class="u-gal-item reveal" data-idx="<?= $idx ?>" data-full="<?= e(media_url($g['file'])) ?>" data-title="<?= e($g['judul']) ?>" data-cat="<?= e($g['kategori']) ?>" data-tipe="<?= e($g['tipe']) ?>">
        <span class="u-badge-type"><i class="fa-solid <?= $g['tipe'] === 'video' ? 'fa-film' : 'fa-image' ?>"></i><?= strtoupper($g['tipe']) ?></span>
        <?php if ($g['tanggal']): ?><span class="u-date"><?= date('d M Y', strtotime($g['tanggal'])) ?></span><?php endif; ?>
        <?php if ($g['tipe'] === 'video'): ?>
          <div class="u-video-ph"><i class="fa-solid fa-circle-play"></i></div>
        <?php else: ?>
          <img src="<?= e(media_url($g['file'])) ?>" alt="<?= e($g['judul']) ?>" loading="lazy">
        <?php endif; ?>
        <div class="u-over">
          <p><?= e($g['judul']) ?></p>
          <small><?= e($g['kategori']) ?></small>
          <div class="u-actions">
            <button data-action="view" title="Lihat"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
            <button data-action="slideshow" title="Slideshow"><i class="fa-solid fa-play"></i></button>
            <button data-action="download" title="Unduh" onclick="window.open('<?= e(media_url($g['file'])) ?>','_blank')"><i class="fa-solid fa-download"></i></button>
            <button data-action="share" title="Bagikan" onclick="navigator.share && navigator.share({title:'<?= e(addslashes($g['judul'])) ?>',url:'<?= e(media_url($g['file'])) ?>'})"><i class="fa-solid fa-share"></i></button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="u-gal-info-strip reveal">
      <div class="u-gii"><i class="fa-solid fa-camera-retro"></i><b><?= $totalFoto ?></b><small>Total Foto</small></div>
      <div class="u-gii"><i class="fa-solid fa-video"></i><b><?= $totalVideo ?></b><small>Total Video</small></div>
      <div class="u-gii"><i class="fa-solid fa-layer-group"></i><b><?= $totalKats ?></b><small>Kategori</small></div>
      <div class="u-gii"><i class="fa-solid fa-calendar-check"></i><b><?= date('Y') ?></b><small>Tahun Aktif</small></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ===== SLIDESHOW MODE ===== -->
<div class="u-slideshow" id="uSlideshow">
  <div class="u-sl-progress"><span id="uSlProg"></span></div>
  <span class="u-sl-counter"><span id="uSlCur">1</span> / <span id="uSlTot"><?= count($items) ?></span></span>
  <img id="uSlImg" src="" alt="">
  <div class="u-sl-caption"><b id="uSlTitle"></b><small id="uSlCat"></small></div>
  <button class="u-sl-close" id="uSlClose"><i class="fa-solid fa-xmark"></i></button>
  <button class="u-sl-prev" id="uSlPrev"><i class="fa-solid fa-chevron-left"></i></button>
  <button class="u-sl-next" id="uSlNext"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="u-sl-thumbs" id="uSlThumbs"></div>
</div>

<script>
/* ========= GALERI ULTIMATE ========= */
(function(){
  var items = Array.from(document.querySelectorAll('.u-gal-item')).filter(function(it){ return it.dataset.tipe === 'image'; });
  var sl    = document.getElementById('uSlideshow');
  var sImg  = document.getElementById('uSlImg');
  var sTitle= document.getElementById('uSlTitle');
  var sCat  = document.getElementById('uSlCat');
  var sCur  = document.getElementById('uSlCur');
  var sTot  = document.getElementById('uSlTot');
  var sProg = document.getElementById('uSlProg');
  var sThumbs = document.getElementById('uSlThumbs');
  var idx   = 0;

  if (items.length && sl) {
    /* Build thumbs */
    items.forEach(function(it, i){
      var img = document.createElement('img');
      img.src = it.querySelector('img').src;
      img.addEventListener('click', function(){ show(i); });
      sThumbs.appendChild(img);
    });
    sTot.textContent = items.length;

    function show(i){
      idx = (i + items.length) % items.length;
      var it = items[idx];
      sImg.src = it.dataset.full;
      sTitle.textContent = it.dataset.title;
      sCat.textContent = it.dataset.cat;
      sCur.textContent = idx + 1;
      sProg.style.width = (((idx + 1) / items.length) * 100).toFixed(1) + '%';
      Array.from(sThumbs.children).forEach(function(t, j){ t.classList.toggle('on', j === idx); });
    }
    function open(i){ show(i); sl.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function close(){ sl.classList.remove('open'); document.body.style.overflow = ''; }

    items.forEach(function(it, i){
      it.querySelectorAll('[data-action="view"]').forEach(function(b){ b.addEventListener('click', function(e){ e.stopPropagation(); open(i); }); });
      it.querySelectorAll('[data-action="slideshow"]').forEach(function(b){ b.addEventListener('click', function(e){ e.stopPropagation(); open(i); }); });
      it.addEventListener('click', function(){ if (it.dataset.tipe === 'image') open(i); });
    });

    document.getElementById('uSlClose').addEventListener('click', close);
    document.getElementById('uSlPrev').addEventListener('click', function(){ show(idx - 1); });
    document.getElementById('uSlNext').addEventListener('click', function(){ show(idx + 1); });
    sl.addEventListener('click', function(e){ if (e.target === sl) close(); });
    document.addEventListener('keydown', function(e){
      if (!sl.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(idx - 1);
      if (e.key === 'ArrowRight') show(idx + 1);
    });
  }

  /* Auto slideshow interval */
  var autoTimer = null;
  function startAuto(){ autoTimer = setInterval(function(){ show(idx + 1); }, 4500); }
  function stopAuto(){ clearInterval(autoTimer); }
  if (sl) {
    sl.addEventListener('mouseenter', stopAuto);
    sl.addEventListener('mouseleave', startAuto);
  }

  /* Stats counter animation */
  var counters = document.querySelectorAll('.u-gal-stats .gs b, .u-gal-info-strip .u-gii b');
  if (counters.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if (!en.isIntersecting) return;
        var el = en.target;
        var text = el.textContent.replace(/[^\d]/g,'');
        var target = parseInt(text) || 0;
        var suffix = el.textContent.replace(/[\d]/g,'');
        var n = 0, step = Math.max(1, Math.ceil(target / 50));
        var t = setInterval(function(){
          n += step; if (n >= target) { n = target; clearInterval(t); }
          el.innerHTML = n + '<em>' + (suffix || '') + '</em>';
        }, 24);
        io.unobserve(el);
      });
    }, { threshold: .5 });
    counters.forEach(function(c){ io.observe(c); });
  }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>