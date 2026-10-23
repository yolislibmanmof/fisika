<?php require_once __DIR__ . '/includes/header.php'; ?>

<!-- LOADER KHUSUS BERANDA + PENGAMAN -->
<div class="loader" id="loader">
  <div class="atom-loader">
    <span class="nucleus"></span>
    <span class="orbit o1"><i></i></span>
    <span class="orbit o2"><i></i></span>
    <span class="orbit o3"><i></i></span>
  </div>
  <p class="loader-txt">PROGRAM STUDI <b>FISIKA</b></p>
  <div class="loader-bar"><span></span></div>
</div>
<script>setTimeout(function(){var l=document.getElementById('loader');if(l&&!window.__fisikaJs)l.classList.add('done');},4000);</script>

<!-- ===== HERO SLIDER ===== -->
<section class="hero">
  <canvas id="particles"></canvas>
  <div class="hero-slides" id="heroSlides">
    <?php
    $heroes = q($conn, "SELECT * FROM heroes WHERE aktif=1 ORDER BY urutan ASC");
    if (!$heroes) $heroes[] = ['tipe'=>'image','file'=>'','judul'=>$S['hero_title'] ?? '','subtitle'=>$S['hero_subtitle'] ?? '','badge'=>$S['badge_hero'] ?? ''];
    foreach ($heroes as $i => $h): ?>
    <div class="slide <?= $i === 0 ? 'active' : '' ?>">
      <?php if ($h['tipe'] === 'video' && $h['file']): ?>
        <video class="slide-media" src="<?= e(media_url($h['file'])) ?>" autoplay muted loop playsinline></video>
      <?php elseif ($h['file']): ?>
        <div class="slide-media slide-img" style="background-image:url('<?= e(media_url($h['file'])) ?>')"></div>
      <?php else: ?>
        <div class="slide-media slide-empty"></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="hero-overlay"></div>

  <span class="formula f1">E = mc²</span>
  <span class="formula f2">F = m·a</span>
  <span class="formula f3">λ = h/p</span>
  <span class="formula f4">iℏ ∂ψ/∂t = Ĥψ</span>
  <span class="formula f5">∇ · E = ρ/ε₀</span>

  <div class="container hero-content">
    <span class="hero-badge"><i class="fa-solid fa-wave-square"></i> <?= e($heroes[0]['badge'] ?: ($S['badge_hero'] ?? '')) ?></span>
    <h1 class="hero-title"><?= e($heroes[0]['judul'] ?: ($S['hero_title'] ?? '')) ?></h1>
    <p class="hero-sub"><?= e($heroes[0]['subtitle'] ?: ($S['hero_subtitle'] ?? '')) ?></p>
    <div class="hero-btns">
      <a class="btn-primary" href="<?= url('page.php?slug=sejarah') ?>">JELAJAHI PRODI <i class="fa-solid fa-arrow-right"></i></a>
      <a class="btn-ghost" href="<?= url('page.php?slug=kurikulum') ?>"><i class="fa-solid fa-play"></i> LIHAT KURIKULUM</a>
    </div>
    <div class="hero-dots" id="heroDots"></div>
  </div>

  <div class="hero-rail">
    <a href="<?= e($S['facebook'] ?? '#') ?>"><i class="fa-brands fa-facebook-f"></i></a>
    <a href="<?= e($S['instagram'] ?? '#') ?>"><i class="fa-brands fa-instagram"></i></a>
    <a href="<?= e($S['youtube'] ?? '#') ?>"><i class="fa-brands fa-youtube"></i></a>
    <a href="<?= e($S['linkedin'] ?? '#') ?>"><i class="fa-brands fa-linkedin-in"></i></a>
  </div>
  <button class="hero-arrow prev" id="heroPrev"><i class="fa-solid fa-chevron-left"></i></button>
  <button class="hero-arrow next" id="heroNext"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="scroll-hint"><span></span>GULIR</div>
</section>

<div class="wave-div">
  <svg viewBox="0 0 1440 90" preserveAspectRatio="none">
    <path class="w1" d="M0,50 C240,90 480,10 720,50 C960,90 1200,10 1440,50 L1440,90 L0,90 Z"></path>
    <path class="w2" d="M0,60 C240,20 480,95 720,55 C960,15 1200,90 1440,55 L1440,90 L0,90 Z"></path>
  </svg>
</div>

<!-- ===== STATISTIK ===== -->
<section class="stats-sec">
  <div class="container stats-grid">
    <?php foreach (q($conn, "SELECT * FROM stats ORDER BY urutan ASC") as $r): ?>
    <div class="stat-card reveal">
      <span class="stat-ic"><i class="<?= e($r['icon']) ?>"></i></span>
      <b class="counter" data-target="<?= (int)$r['nilai'] ?>">0</b><em><?= e($r['suffix']) ?></em>
      <p><?= e($r['label']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== SAMBUTAN KAPRODI ===== -->
<section class="sambutan-sec">
  <div class="container sambutan-grid">
    <?php $sb = q1($conn, "SELECT * FROM sambutan LIMIT 1") ?? ['nama'=>'','jabatan'=>'','foto'=>'','isi'=>'','tanda_tangan'=>'']; ?>
    <div class="samb-photo reveal">
      <div class="photo-frame">
        <img src="<?= e(media_url($sb['foto']) ?: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=800&q=60') ?>" alt="Kaprodi">
        <span class="orbit-ring r1"></span><span class="orbit-ring r2"></span>
      </div>
      <div class="photo-badge"><i class="fa-solid fa-signature"></i> <?= e($sb['tanda_tangan']) ?></div>
    </div>
    <div class="samb-txt reveal">
      <span class="sec-tag"><i class="fa-solid fa-atom"></i> SAMBUTAN KAPRODI</span>
      <h2><?= e($sb['nama']) ?></h2>
      <small><?= e($sb['jabatan']) ?></small>
      <div class="samb-isi x-quote"><?= render_content($sb['isi']) ?></div>
      <a class="btn-primary" href="<?= url('page.php?slug=visi-misi') ?>">VISI & MISI <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ===== JELAJAH AKADEMIK (sinkron menu & halaman admin) ===== -->
<section class="x-akad-sec">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-compass"></i> JELAJAH AKADEMIK</span>
      <h2>Profil & Layanan <span class="grad-txt">Program Studi</span></h2>
    </div>
    <div class="x-akad-grid">
      <?php
      $iconMap = ['sejarah'=>'fa-landmark','visi-misi'=>'fa-bullseye','struktur-organisasi'=>'fa-sitemap','kurikulum'=>'fa-book-open','laboratorium'=>'fa-flask','penelitian'=>'fa-microscope','kontak'=>'fa-envelope'];
      $akadKids = array_merge($getKids(2), $getKids(3)); /* anak menu Profil & Akademik */
      $n = 0;
      foreach ($akadKids as $k):
        parse_str((string)parse_url($k['url'], PHP_URL_QUERY), $qs);
        $slug = $qs['slug'] ?? '';
        $pg = $slug ? q1($conn, "SELECT konten FROM pages WHERE slug='" . $conn->real_escape_string($slug) . "' AND status=1") : null;
        $excerpt = $pg ? mb_strimwidth(trim(strip_tags($pg['konten'])), 0, 110, '...') : 'Informasi resmi Program Studi Fisika.';
        $n++; ?>
      <a class="x-akad-card reveal" href="<?= url($k['url']) ?>">
        <span class="num">0<?= $n ?></span>
        <span class="x-akad-ic"><i class="fa-solid <?= e($iconMap[$slug] ?? 'fa-atom') ?>"></i></span>
        <h3><?= e($k['label']) ?></h3>
        <p><?= e($excerpt) ?></p>
        <span class="go">SELENGKAPNYA <i class="fa-solid fa-arrow-right"></i></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== FITUR / KEUNGGULAN ===== -->
<section class="fitur-sec" style="background:var(--gray)">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-bolt"></i> KEUNGGULAN KAMI</span>
      <h2>Ekosistem Akademik <span class="grad-txt">Berstandar Global</span></h2>
    </div>
    <div class="fitur-grid">
      <?php foreach (q($conn, "SELECT * FROM fitur WHERE aktif=1 ORDER BY urutan ASC") as $f): ?>
      <div class="fitur-card reveal">
        <span class="fitur-ic"><i class="<?= e($f['icon']) ?>"></i></span>
        <h3><?= e($f['judul']) ?></h3>
        <p><?= e($f['deskripsi']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== BERITA UTAMA ===== -->
<section class="berita-sec" style="background:#fff">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-newspaper"></i> INFORMASI TERKINI</span>
      <h2>Berita & <span class="grad-txt">Kegiatan Prodi</span></h2>
      <a class="link-all" href="<?= url('berita.php') ?>">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="berita-grid">
      <?php foreach (q($conn, "SELECT * FROM berita WHERE status=1 ORDER BY tanggal DESC LIMIT 3") as $b): ?>
      <a class="berita-card reveal" href="<?= url('berita-detail.php?slug=' . e($b['slug'])) ?>">
        <div class="bc-img"><img src="<?= e(media_url($b['gambar'])) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
          <span class="bc-cat"><?= e($b['kategori']) ?></span></div>
        <div class="bc-body">
          <span class="bc-meta"><i class="fa-regular fa-calendar-days"></i> <?= date('d M Y', strtotime($b['tanggal'])) ?> &nbsp;•&nbsp; <i class="fa-regular fa-eye"></i> <?= (int)$b['views'] ?></span>
          <h3><?= e($b['judul']) ?></h3>
          <span class="bc-more">Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== TIMELINE AGENDA + MARQUEE PRESTASI ===== -->
<section class="x-act-sec">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag"><i class="fa-solid fa-timeline"></i> AKTIVITAS & PENCAPAIAN</span>
      <h2>Agenda Kegiatan serta <span class="grad-txt">Prestasi Terbaru</span></h2>
    </div>
    <div class="x-timeline reveal">
      <?php
      $agenda = q($conn, "SELECT * FROM berita WHERE status=1 AND kategori='Kegiatan' ORDER BY tanggal DESC LIMIT 4");
      if (!$agenda) $agenda = q($conn, "SELECT * FROM berita WHERE status=1 ORDER BY tanggal DESC LIMIT 4");
      foreach ($agenda as $a): ?>
      <div class="x-tl-item">
        <span class="x-tl-date"><?= strtoupper(date('d F Y', strtotime($a['tanggal']))) ?></span>
        <h4><a href="<?= url('berita-detail.php?slug=' . e($a['slug'])) ?>"><?= e($a['judul']) ?></a></h4>
        <p><?= e(mb_strimwidth(trim(strip_tags($a['isi'])), 0, 150, '...')) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="x-marq reveal">
      <div class="x-marq-track">
        <?php
        $prest = q($conn, "SELECT * FROM berita WHERE status=1 AND kategori IN ('Prestasi','Akademik') ORDER BY tanggal DESC LIMIT 6");
        if (!$prest) $prest = q($conn, "SELECT * FROM berita WHERE status=1 ORDER BY views DESC LIMIT 6");
        for ($g = 0; $g < 2; $g++):
          foreach ($prest as $p): ?>
        <a class="x-marq-card" href="<?= url('berita-detail.php?slug=' . e($p['slug'])) ?>">
          <img src="<?= e(media_url($p['gambar'])) ?>" alt="" loading="lazy">
          <span><small><?= strtoupper(e($p['kategori'])) ?></small><b><?= e($p['judul']) ?></b></span>
        </a>
        <?php endforeach; endfor; ?>
      </div>
    </div>
  </div>
</section>

<!-- ===== GALERI PREVIEW ===== -->
<section class="galeri-sec">
  <div class="container">
    <div class="sec-head reveal">
      <span class="sec-tag light"><i class="fa-solid fa-images"></i> DOKUMENTASI</span>
      <h2 class="light">Galeri <span class="grad-txt">Aktivitas</span></h2>
    </div>
    <div class="galeri-grid">
      <?php foreach (q($conn, "SELECT * FROM galeri ORDER BY urutan ASC LIMIT 6") as $g): ?>
      <div class="g-item reveal" data-full="<?= e(media_url($g['file'])) ?>">
        <img src="<?= e(media_url($g['file'])) ?>" alt="<?= e($g['judul']) ?>" loading="lazy">
        <div class="g-over"><i class="fa-solid fa-magnifying-glass-plus"></i><p><?= e($g['judul']) ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="g-cta"><a class="btn-primary" href="<?= url('galeri.php') ?>">GALERI LENGKAP <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta-sec">
  <div class="cta-ring r1"></div><div class="cta-ring r2"></div>
  <div class="container cta-in reveal">
    <h2>Bergabunglah Menjadi Bagian dari <span>Prodi Fisika</span></h2>
    <p>Pendaftaran Mahasiswa Baru <?= date('Y') ?> telah dibuka. Wujudkan karier sains Anda bersama kami.</p>
    <a class="btn-white" href="<?= url('page.php?slug=kontak') ?>"><i class="fa-solid fa-rocket"></i> DAFTAR SEKARANG</a>
  </div>
</section>

<div class="lightbox" id="lightbox"><img src="" alt=""><button id="lbClose"><i class="fa-solid fa-xmark"></i></button></div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>