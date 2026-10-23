  <div class="a2-footbar">
    <span class="chip"><i class="fa-solid fa-copyright"></i> <?= date('Y') ?> Prodi Fisika</span>
    <span class="chip"><i class="fa-brands fa-php"></i> PHP <?= PHP_VERSION ?></span>
    <span class="chip"><i class="fa-solid fa-database"></i> DB: fisika</span>
    <span class="chip"><i class="fa-solid fa-bolt"></i> <?= round((microtime(true) - A2_START) * 1000, 1) ?> ms</span>
    <span class="chip"><i class="fa-solid fa-microchip"></i> <?= round(memory_get_usage(true) / 1048576, 1) ?> MB</span>
    <span class="sp">
      <a class="chip" target="_blank" href="<?= url('index.php') ?>"><i class="fa-solid fa-globe"></i> Lihat Situs</a>
      <a class="chip" target="_blank" href="http://localhost/phpmyadmin"><i class="fa-solid fa-database"></i> phpMyAdmin</a>
      <button class="chip" style="cursor:pointer;border:1px solid var(--line)" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fa-solid fa-arrow-up"></i> Ke Atas</button>
    </span>
  </div>
</main>
</div>

<div class="a2-toasts" id="a2Toasts"></div>

<!-- Modal Icon Picker (diperkaya dengan pencarian) -->
<div class="modal" id="iconModal">
  <div class="modal-box">
    <div class="modal-head"><h3><i class="fa-solid fa-icons"></i> Pilih Ikon</h3><button type="button" class="icon-btn" id="iconClose"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="a2-icon-search"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="iconSearch" placeholder="Cari ikon... contoh: atom, flask, rocket" autocomplete="off"></div>
    <div class="icon-grid" id="iconGrid"></div>
  </div>
</div>

<script src="<?= url('assets/admin/js/admin.js') ?>"></script>
<script>
/* ========= ADMIN ULTIMATE v4 (inline — admin.js tetap final) ========= */
(function () {
  /* ---- Dropdown elegan ---- */
  document.querySelectorAll('[data-dd]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      var dd = btn.closest('.a2-dd');
      var was = dd.classList.contains('open');
      document.querySelectorAll('.a2-dd.open').forEach(function (d) { d.classList.remove('open'); });
      if (!was) dd.classList.add('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.a2-dd.open').forEach(function (d) { d.classList.remove('open'); });
  });

  /* ---- Jam WIB ---- */
  var ck = document.getElementById('a2Clock');
  if (ck) {
    var fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    var tk = function () { ck.textContent = fmt.format(new Date()).replace(/\./g, ':'); };
    tk(); setInterval(tk, 1000);
  }

  /* ---- Layar penuh ---- */
  var fb = document.getElementById('a2Full');
  if (fb) fb.addEventListener('click', function () {
    if (!document.fullscreenElement) { document.documentElement.requestFullscreen().catch(function () {}); }
    else { document.exitFullscreen(); }
  });

  /* ---- Command palette ---- */
  var pal = document.getElementById('a2Palette');
  var pi  = document.getElementById('a2PalInput');
  var links = pal ? Array.prototype.slice.call(pal.querySelectorAll('.a2-pal-list a')) : [];
  function palFilter(v) {
    v = (v || '').toLowerCase();
    links.forEach(function (a) { a.style.display = a.textContent.toLowerCase().indexOf(v) > -1 ? '' : 'none'; });
  }
  function palOpen(o) {
    pal.classList.toggle('open', o);
    if (o) { pi.value = ''; palFilter(''); setTimeout(function () { pi.focus(); }, 60); }
  }
  var palBtn = document.getElementById('a2PalBtn');
  if (palBtn) palBtn.addEventListener('click', function () { palOpen(true); });
  if (pal) pal.addEventListener('click', function (ev) { if (ev.target === pal) palOpen(false); });
  if (pi) {
    pi.addEventListener('input', function () { palFilter(this.value); });
    pi.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter') {
        var vis = links.filter(function (a) { return a.style.display !== 'none'; })[0];
        if (vis) location.href = vis.href;
      }
    });
  }
  document.addEventListener('keydown', function (ev) {
    if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'k') { ev.preventDefault(); palOpen(!pal.classList.contains('open')); }
    if (ev.key === 'Escape') {
      palOpen(false);
      document.querySelectorAll('.a2-dd.open').forEach(function (d) { d.classList.remove('open'); });
    }
  });

  /* ---- Accordion sidebar ---- */
  document.querySelectorAll('.a2-glabel').forEach(function (g) {
    g.addEventListener('click', function () { g.closest('.a2-group').classList.toggle('open'); });
  });

  /* ---- Pencarian ikon dalam Icon Picker ---- */
  var is = document.getElementById('iconSearch');
  if (is) is.addEventListener('input', function () {
    var v = this.value.toLowerCase();
    document.querySelectorAll('#iconGrid button').forEach(function (b) {
      b.style.display = ((b.title || '') + ' ' + (b.textContent || '')).toLowerCase().indexOf(v) > -1 ? '' : 'none';
    });
  });

  /* ---- Toast helper global ---- */
  window.a2Toast = function (msg, type) {
    var box = document.getElementById('a2Toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'a2-toast' + (type === 'err' ? ' err' : '');
    t.innerHTML = '<i class="fa-solid ' + (type === 'err' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span>' + msg + '</span>';
    box.appendChild(t);
    setTimeout(function () {
      t.style.opacity = '0'; t.style.transform = 'translateX(30px)'; t.style.transition = '.4s';
      setTimeout(function () { t.remove(); }, 400);
    }, 3200);
  };
})();
</script>
</body>
</html>