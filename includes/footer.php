<?php
$S = $S ?? get_setting($conn);
$waNum = preg_replace('/\D/', '', (string)($S['telepon'] ?? ''));
$waUrl = $waNum !== '' ? 'https://wa.me/' . $waNum : '#';
$f2NavIcon = function (string $label): string {
    $l = mb_strtolower($label);
    $map = ['beranda'=>'fa-house','profil'=>'fa-id-badge','akademik'=>'fa-graduation-cap','berita'=>'fa-newspaper','galeri'=>'fa-images','kontak'=>'fa-headset','layanan'=>'fa-concierge-bell'];
    foreach ($map as $k => $ic) { if (str_contains($l, $k)) return $ic; }
    return 'fa-star';
};

/* ===== PREMIUM: Safe module check ===== */
$useAsset = file_exists(__DIR__ . '/Asset.php');
if ($useAsset && !class_exists('Asset')) require_once __DIR__ . '/Asset.php';

function footer_asset_js($path) {
    global $useAsset;
    return $useAsset && class_exists('Asset')
        ? Asset::js($path)
        : '<script src="' . url($path) . '" defer></script>';
}
?>
<style>
/* ========= FOOTER ULTIMATE ICONS v4 (inline — public.css tetap final) ========= */
@keyframes f2spin{to{transform:rotate(360deg)}}
@keyframes f2ping{0%{transform:scale(.92);opacity:.85}100%{transform:scale(1.28);opacity:0}}
@keyframes f2beat{70%{box-shadow:0 0 0 9px rgba(0,184,116,0)}100%{box-shadow:0 0 0 0 rgba(0,184,116,0)}}
@keyframes f2float{50%{transform:translateY(-6px)}}

/* Wordmark: pemisah atom berputar */
.x-wordmark span em i{display:inline-block;font-size:24px;vertical-align:middle;color:rgba(0,184,116,.55);animation:f2spin 6s linear infinite;margin:0 6px}

/* Brand atom berorbit + lencana terverifikasi */
.f2-brand{display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap}
.f2-brand-atom{position:relative;width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#0a66c2,#00b874);display:grid;place-items:center;color:#fff;font-size:23px;box-shadow:0 10px 26px rgba(0,184,116,.35);flex:none}
.f2-brand-atom i{animation:f2spin 7s linear infinite}
.f2-brand-atom::before,.f2-brand-atom::after{content:'';position:absolute;inset:-6px;border:1.5px dashed rgba(0,184,116,.5);border-radius:50%;animation:f2spin 12s linear infinite;pointer-events:none}
.f2-brand-atom::after{inset:-12px;border-color:rgba(77,163,255,.35);animation-direction:reverse;animation-duration:16s}
.f2-brand b{font-family:var(--display);font-size:21px;color:#fff;letter-spacing:.5px}
.f2-verify{display:inline-flex;align-items:center;gap:6px;background:rgba(0,184,116,.15);border:1px solid rgba(0,184,116,.4);color:#7ef0c4;font-size:10px;font-weight:800;letter-spacing:1.4px;padding:5px 11px;border-radius:99px}
.f2-verify i{animation:f2beat 1.8s infinite;border-radius:50%}

/* Sosial media premium: kaca buram + tooltip + cincin denyut */
.f2-soc{display:flex;gap:12px;margin-top:20px;flex-wrap:wrap}
.f2-soc a{position:relative;width:46px;height:46px;border-radius:14px;display:grid;place-items:center;color:#e6eefc;font-size:17px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(8px);transition:.35s cubic-bezier(.22,.61,.36,1)}
.f2-soc a i{position:relative;z-index:1;transition:.35s}
.f2-soc a::before{content:'';position:absolute;inset:0;border-radius:14px;background:var(--bg,linear-gradient(135deg,#0a66c2,#00b874));opacity:0;transition:.35s}
.f2-soc a:hover::before{opacity:1}
.f2-soc a:hover{transform:translateY(-6px) rotate(-6deg);box-shadow:0 14px 28px rgba(0,0,0,.45);border-color:transparent}
.f2-soc a:hover i{transform:scale(1.18)}
.f2-soc a .ring{position:absolute;inset:-4px;border-radius:18px;border:1.5px solid rgba(0,184,116,.55);opacity:0;pointer-events:none}
.f2-soc a:hover .ring{opacity:1;animation:f2ping 1.2s ease-out infinite}
.f2-soc a::after{content:attr(data-label);position:absolute;bottom:calc(100% + 10px);left:50%;transform:translateX(-50%) translateY(6px);background:#fff;color:#0a2a5e;font-size:10px;font-weight:800;letter-spacing:1.2px;padding:5px 10px;border-radius:8px;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap}
.f2-soc a:hover::after{opacity:1;transform:translateX(-50%) translateY(0)}
.f2-soc .fb{--bg:linear-gradient(135deg,#1877f2,#0a66c2)}
.f2-soc .ig{--bg:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#bc1888)}
.f2-soc .yt{--bg:linear-gradient(135deg,#ff0000,#c4302b)}
.f2-soc .li{--bg:linear-gradient(135deg,#0a66c2,#0077b5)}
.f2-soc .xx{--bg:linear-gradient(135deg,#14171a,#3f4650)}
.f2-soc .tt{--bg:linear-gradient(135deg,#010101,#69c9d0)}
.f2-soc .wa{--bg:linear-gradient(135deg,#25d366,#128c7e)}
.f2-soc .em{--bg:linear-gradient(135deg,#00b874,#00915c)}

/* Judul kolom dengan lencana ikon */
.f2-title{display:flex;align-items:center;gap:10px;color:#fff;font-family:var(--display);font-size:15.5px;margin-bottom:20px;padding-bottom:12px;position:relative}
.f2-title::after{content:'';position:absolute;left:0;bottom:0;width:42px;height:3px;border-radius:9px;background:linear-gradient(90deg,#0a66c2,#00b874)}
.f2-title .tic{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,rgba(10,102,194,.28),rgba(0,184,116,.28));display:grid;place-items:center;color:#7ef0c4;font-size:12px;border:1px solid rgba(0,184,116,.3);transition:.3s}
.f-col:hover .f2-title .tic{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;transform:rotate(-8deg) scale(1.08)}

/* Tautan dengan penanda ikon + panah meluncur */
.f2-links{display:flex;flex-direction:column;gap:4px}
.f2-links a{position:relative;display:flex;align-items:center;gap:10px;padding:8px 12px;margin-left:-12px;border-radius:10px;font-size:14px;color:#a9b6c8;transition:.3s}
.f2-links a i.mk{width:26px;height:26px;border-radius:8px;display:grid;place-items:center;font-size:10px;background:rgba(255,255,255,.07);color:#7ef0c4;transition:.3s;flex:none;border:1px solid rgba(255,255,255,.08)}
.f2-links a i.arr{margin-left:auto;font-size:11px;color:#7ef0c4;opacity:0;transform:translateX(-8px);transition:.3s}
.f2-links a:hover{background:rgba(255,255,255,.05);color:#fff;transform:translateX(6px)}
.f2-links a:hover i.mk{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;transform:rotate(-8deg);border-color:transparent}
.f2-links a:hover i.arr{opacity:1;transform:none}

/* Kontak dengan orb berdenyut */
.f2-contact{display:flex;flex-direction:column;gap:16px}
.f2-ci{display:flex;gap:14px;align-items:flex-start}
.f2-ci .orb{position:relative;width:42px;height:42px;flex:none;border-radius:12px;background:linear-gradient(135deg,rgba(10,102,194,.2),rgba(0,184,116,.2));display:grid;place-items:center;color:#7ef0c4;font-size:15px;transition:.35s;border:1px solid rgba(0,184,116,.28)}
.f2-ci .orb::after{content:'';position:absolute;inset:0;border-radius:12px;border:1.5px solid rgba(0,184,116,.45);animation:f2ping 2.6s ease-out infinite;pointer-events:none}
.f2-ci:hover .orb{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;transform:rotate(-8deg) scale(1.1);border-color:transparent;box-shadow:0 8px 20px rgba(0,184,116,.35)}
.f2-ci b{display:block;color:#fff;font-size:11px;letter-spacing:1.6px;text-transform:uppercase;margin-bottom:3px}
.f2-ci p{color:#a9b6c8;font-size:13.5px;line-height:1.65;margin:0}
.f2-ci a{color:#a9b6c8;transition:.25s}
.f2-ci a:hover{color:#7ef0c4}

/* Strip aksi cepat berikon */
.f2-quick{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;padding:28px 0 0;border-top:1px solid rgba(255,255,255,.07);margin-top:38px}
.f2-quick a{display:inline-flex;align-items:center;gap:10px;padding:12px 20px;border-radius:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#cfd8e3;font-size:12.5px;font-weight:700;transition:.3s}
.f2-quick a i{color:#7ef0c4;transition:.3s;font-size:14px}
.f2-quick a:hover{background:linear-gradient(135deg,#0a66c2,#00b874);color:#fff;transform:translateY(-4px);border-color:transparent;box-shadow:0 12px 26px rgba(0,184,116,.3)}
.f2-quick a:hover i{color:#fff;transform:scale(1.25) rotate(-8deg)}

/* Bar bawah: status online + versi */
.f2-status{display:inline-flex;align-items:center;gap:8px;background:rgba(0,184,116,.12);border:1px solid rgba(0,184,116,.35);padding:6px 14px;border-radius:99px;color:#7ef0c4;font-size:11px;font-weight:800;letter-spacing:.6px}
.f2-status .dot{width:8px;height:8px;border-radius:50%;background:#00b874;animation:f2beat 1.6s infinite}
.f2-ver{display:inline-flex;align-items:center;gap:7px;background:rgba(77,163,255,.12);border:1px solid rgba(77,163,255,.35);padding:6px 14px;border-radius:99px;color:#9ecbff;font-size:11px;font-weight:800;letter-spacing:.6px}
.f-bottom-in{align-items:center}
.f-bottom-in .f2-bright{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

/* Tombol ke atas dengan cincin progres scroll */
#toTop{background:conic-gradient(#00b874 calc(var(--sp,0)*1%), rgba(255,255,255,.14) 0);border-radius:16px}
#toTop::before{content:'';position:absolute;inset:3px;border-radius:13px;background:linear-gradient(135deg,#0a66c2,#071a39)}
#toTop i{position:relative;z-index:1}
#toTop:hover::before{background:linear-gradient(135deg,#00b874,#00915c)}

/* PREMIUM: Smooth transitions untuk semua link di footer (dark mode) */
.footer a,
.footer button {
    transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
}
</style>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="x-wordmark">
    <div class="ticker-track">
      <?php $wm = '<span>FISIKA <em><i class="fa-solid fa-atom"></i></em> SAINS <em><i class="fa-solid fa-atom"></i></em> INOVASI <em><i class="fa-solid fa-atom"></i></em> RISET <em><i class="fa-solid fa-atom"></i></em> TEKNOLOGI <em><i class="fa-solid fa-atom"></i></em> EDUKASI <em><i class="fa-solid fa-atom"></i></em> </span>'; echo $wm . $wm; ?>
    </div>
  </div>

  <div class="container footer-grid">
    <!-- KOLOM 1 : BRAND -->
    <div class="f-col">
      <div class="f2-brand">
        <?php if (!empty($S['logo'])): ?>
          <img src="<?= e(media_url($S['logo'])) ?>" alt="Logo" style="height:52px;border-radius:14px">
        <?php else: ?>
          <span class="f2-brand-atom"><i class="fa-solid fa-atom"></i></span>
        <?php endif; ?>
        <b>PRODI <span class="green">FISIKA</span></b>
        <span class="f2-verify"><i class="fa-solid fa-circle-check"></i> TERVERIFIKASI</span>
      </div>
      <p><?= e($S['meta_description'] ?? '') ?></p>
      <div class="f2-soc">
        <a href="<?= e($S['facebook'] ?? '#') ?>" class="fb" data-label="FACEBOOK" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i><span class="ring"></span></a>
        <a href="<?= e($S['instagram'] ?? '#') ?>" class="ig" data-label="INSTAGRAM" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i><span class="ring"></span></a>
        <a href="<?= e($S['youtube'] ?? '#') ?>" class="yt" data-label="YOUTUBE" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fa-brands fa-youtube"></i><span class="ring"></span></a>
        <a href="<?= e($S['linkedin'] ?? '#') ?>" class="li" data-label="LINKEDIN" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i><span class="ring"></span></a>
        <a href="#" class="xx" data-label="X / TWITTER" aria-label="X Twitter"><i class="fa-brands fa-x-twitter"></i><span class="ring"></span></a>
        <a href="#" class="tt" data-label="TIKTOK" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i><span class="ring"></span></a>
        <a href="<?= e($waUrl) ?>" class="wa" data-label="WHATSAPP" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i><span class="ring"></span></a>
        <a href="mailto:<?= e($S['email'] ?? '') ?>" class="em" data-label="EMAIL" aria-label="Email"><i class="fa-solid fa-envelope"></i><span class="ring"></span></a>
      </div>
    </div>

    <!-- KOLOM 2 : NAVIGASI -->
    <div class="f-col">
      <h4 class="f2-title"><span class="tic"><i class="fa-solid fa-compass"></i></span>Navigasi</h4>
      <div class="f2-links">
        <?php foreach ($parents as $p): ?>
        <a href="<?= $getKids($p['id']) ? '#' : url($p['url']) ?>">
          <i class="mk fa-solid <?= $f2NavIcon($p['label']) ?>"></i> <?= e($p['label']) ?>
          <i class="arr fa-solid fa-arrow-right"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- KOLOM 3 : LAYANAN -->
    <div class="f-col">
      <h4 class="f2-title"><span class="tic"><i class="fa-solid fa-book-open"></i></span>Layanan Akademik</h4>
      <div class="f2-links">
        <?php foreach (q($conn, "SELECT slug, judul FROM pages WHERE status=1 ORDER BY urutan ASC LIMIT 5") as $row): ?>
        <a href="<?= url('page.php?slug=' . e($row['slug'])) ?>">
          <i class="mk fa-solid fa-file-lines"></i> <?= e($row['judul']) ?>
          <i class="arr fa-solid fa-arrow-right"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- KOLOM 4 : KONTAK -->
    <div class="f-col">
      <h4 class="f2-title"><span class="tic"><i class="fa-solid fa-address-card"></i></span>Kontak</h4>
      <div class="f2-contact">
        <div class="f2-ci">
          <span class="orb"><i class="fa-solid fa-location-dot"></i></span>
          <span><b>Alamat</b><p><?= e($S['alamat'] ?? '') ?></p></span>
        </div>
        <div class="f2-ci">
          <span class="orb"><i class="fa-solid fa-phone"></i></span>
          <span><b>Telepon</b><p><a href="tel:<?= e(preg_replace('/\s/','', (string)($S['telepon'] ?? ''))) ?>"><?= e($S['telepon'] ?? '') ?></a></p></span>
        </div>
        <div class="f2-ci">
          <span class="orb"><i class="fa-solid fa-envelope"></i></span>
          <span><b>Email</b><p><a href="mailto:<?= e($S['email'] ?? '') ?>"><?= e($S['email'] ?? '') ?></a></p></span>
        </div>
        <div class="f2-ci">
          <span class="orb"><i class="fa-solid fa-clock"></i></span>
          <span><b>Jam Layanan</b><p>Senin–Jumat, 08.00–16.00 WIB</p></span>
        </div>
      </div>
    </div>
  </div>

  <!-- STRIP AKSI CEPAT -->
  <div class="container">
    <div class="f2-quick">
      <a href="tel:<?= e(preg_replace('/\s/','', (string)($S['telepon'] ?? ''))) ?>"><i class="fa-solid fa-phone-volume"></i> Hubungi Kami</a>
      <a href="mailto:<?= e($S['email'] ?? '') ?>"><i class="fa-solid fa-paper-plane"></i> Kirim Email</a>
      <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp"></i> Chat WhatsApp</a>
      <a href="<?= url('page.php?slug=kontak') ?>"><i class="fa-solid fa-map-location-dot"></i> Petunjuk Lokasi</a>
      <a href="<?= url('berita.php') ?>"><i class="fa-solid fa-rss"></i> Langganan Berita</a>
    </div>
  </div>

  <div class="f-bottom">
    <div class="container f-bottom-in">
      <span><?= e($S['footer_text'] ?? '') ?></span>
      <span class="f2-bright">
        <span class="f2-status"><span class="dot"></span>SISTEM ONLINE</span>
        <span class="f2-ver"><i class="fa-solid fa-code-branch"></i> v4.0 ULTIMATE</span>
        <span>Dikembangkan oleh <b>Tim IT Prodi Fisika</b></span>
      </span>
    </div>
  </div>
</footer>

<button class="to-top" id="toTop" title="Kembali ke atas" aria-label="Scroll ke atas"><i class="fa-solid fa-arrow-up"></i></button>

<?= footer_asset_js('assets/public/js/public.js') ?>

<script>
/* ========= EKSTENSI PREMIUM v4 (inline — public.js tetap final) ========= */
(function () {
  var bar = document.getElementById('xProgress');
  var tt  = document.getElementById('toTop');
  var onScroll = function () {
    var h = document.documentElement;
    var max = h.scrollHeight - h.clientHeight;
    var p = max > 0 ? (h.scrollTop / max) * 100 : 0;
    if (bar) bar.style.width = p + '%';
    if (tt) tt.style.setProperty('--sp', p.toFixed(1));
    
    /* Show/hide toTop button based on scroll position */
    if (tt) {
      if (h.scrollTop > 400) {
        tt.classList.add('visible');
      } else {
        tt.classList.remove('visible');
      }
    }
  };
  document.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ===== PREMIUM: Smooth scroll to top ===== */
  if (tt) {
    tt.addEventListener('click', function(e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(pointer: fine)').matches;
  if (fine && !reduce) {
    document.querySelectorAll('.fitur-card, .stat-card, .x-akad-card, .x-marq-card').forEach(function (c) {
      c.addEventListener('mousemove', function (ev) {
        var r = c.getBoundingClientRect();
        var x = (ev.clientX - r.left) / r.width - .5;
        var y = (ev.clientY - r.top) / r.height - .5;
        c.style.transform = 'perspective(900px) rotateX(' + (-y * 5).toFixed(2) + 'deg) rotateY(' + (x * 7).toFixed(2) + 'deg) translateY(-6px)';
      });
      c.addEventListener('mouseleave', function () { c.style.transform = ''; });
    });
    var hero = document.querySelector('.hero');
    if (hero) {
      hero.addEventListener('mousemove', function (ev) {
        var x = ev.clientX / window.innerWidth - .5;
        var y = ev.clientY / window.innerHeight - .5;
        document.querySelectorAll('.formula').forEach(function (f, i) {
          var d = (i + 1) * 9;
          f.style.translate = (x * d).toFixed(1) + 'px ' + (y * d).toFixed(1) + 'px';
        });
      });
    }
  }
  
  /* ===== PREMIUM: Clock WIB real-time ===== */
  function updateClock() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    
    var clockWIB = document.getElementById('clockWIB');
    if (clockWIB) clockWIB.textContent = hours + ':' + minutes + ':' + seconds;
    
    var a2Clock = document.getElementById('a2Clock');
    if (a2Clock) a2Clock.textContent = hours + ':' + minutes + ':' + seconds;
  }
  setInterval(updateClock, 1000);
  updateClock();

  /* ===== PREMIUM: Dark Mode Toggle Logic ===== */
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function() {
      var current = document.documentElement.getAttribute('data-theme');
      var next = current === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', next);
      try {
        localStorage.setItem('fisika-theme', next);
      } catch(e) {}
      
      /* Update theme-color meta tag */
      var metaTheme = document.querySelector('meta[name="theme-color"]');
      if (metaTheme) {
        metaTheme.content = next === 'dark' ? '#0B1120' : '#0A66C2';
      }
      
      /* Dispatch custom event for other scripts */
      window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: next } }));
    });
  }
  
  /* ===== PREMIUM: Listen to system theme changes ===== */
  try {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
      if (!localStorage.getItem('fisika-theme')) {
        var newTheme = e.matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
      }
    });
  } catch(e) {}

  /* ===== PREMIUM: Keyboard shortcut (Alt+D untuk toggle dark mode) ===== */
  document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'd') {
      e.preventDefault();
      var themeToggle = document.getElementById('themeToggle');
      if (themeToggle) themeToggle.click();
    }
  });
  
  /* ===== PREMIUM: Search overlay keyboard shortcut (/ untuk focus search) ===== */
  document.addEventListener('keydown', function(e) {
    if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
      var active = document.activeElement;
      if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
      e.preventDefault();
      var searchOverlay = document.getElementById('searchOverlay');
      if (searchOverlay) {
        searchOverlay.classList.add('open');
        var searchInput = searchOverlay.querySelector('input[name="q"]');
        if (searchInput) setTimeout(function(){ searchInput.focus(); }, 100);
      }
    }
    if (e.key === 'Escape') {
      var searchOverlay = document.getElementById('searchOverlay');
      if (searchOverlay) searchOverlay.classList.remove('open');
    }
  });

})();
</script>
</body>
</html>