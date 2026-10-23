<?php
$cnt = fn(string $sql): int => (int)(q1($conn, $sql)['c'] ?? 0);
$groups = [
  ['UTAMA', 'fa-compass', [
    ['index.php', 'fa-gauge-high', 'Dashboard', ''],
  ]],
  ['KONTEN', 'fa-pen-nib', [
    ['berita.php', 'fa-newspaper', 'Berita', $cnt("SELECT COUNT(*) AS c FROM berita")],
    ['galeri.php', 'fa-images', 'Galeri', $cnt("SELECT COUNT(*) AS c FROM galeri")],
    ['halaman.php', 'fa-file-lines', 'Halaman Statis', $cnt("SELECT COUNT(*) AS c FROM pages")],
    ['sambutan.php', 'fa-comment-dots', 'Sambutan Kaprodi', ''],
  ]],
  ['TAMPILAN', 'fa-wand-magic-sparkles', [
    ['hero.php', 'fa-photo-film', 'Hero Slider', $cnt("SELECT COUNT(*) AS c FROM heroes")],
    ['fitur.php', 'fa-shapes', 'Fitur & Ikon', $cnt("SELECT COUNT(*) AS c FROM fitur")],
    ['stats.php', 'fa-chart-simple', 'Statistik', $cnt("SELECT COUNT(*) AS c FROM stats")],
    ['menu.php', 'fa-bars-progress', 'Menu Navigasi', $cnt("SELECT COUNT(*) AS c FROM menus")],
  ]],
  ['SISTEM', 'fa-shield-halved', [
    ['pengaturan.php', 'fa-sliders', 'Pengaturan Situs', ''],
    ['users.php', 'fa-users-gear', 'Pengguna', $cnt("SELECT COUNT(*) AS c FROM users")],
    ['logs.php', 'fa-shield-halved', 'Audit Log', $cnt("SELECT COUNT(*) AS c FROM admin_logs WHERE created_at >= (NOW() - INTERVAL 1 DAY)")],
  ]],
];
$allKeys = [];
foreach ($groups as $g) foreach ($g[2] as $it) $allKeys[] = $it[0];
$anyOpen = in_array($ACTIVE, $allKeys, true);
?>
<style>
/* ========= SIDEBAR ULTIMATE (inline) ========= */
.a2-sb-profile{display:flex;gap:12px;align-items:center;margin:14px 14px 8px;padding:12px;border-radius:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09)}
.a2-ava{position:relative;width:40px;height:40px;border-radius:12px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:17px;flex:none}
.a2-on{position:absolute;right:-3px;bottom:-3px;width:11px;height:11px;border-radius:50%;background:#00e08a;border:2px solid #071a39;animation:a2beat 1.8s infinite}
.a2-pt b{display:block;color:#fff;font-size:13px}
.a2-pt small{color:#7f97bd;font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase}
.a2-glabel{display:flex;align-items:center;gap:9px;width:100%;padding:9px 16px;border:0;background:transparent;color:#6d87ad;font-size:10.5px;font-weight:800;letter-spacing:1.8px;cursor:pointer;text-transform:uppercase;transition:.25s;font-family:var(--body)}
.a2-glabel:hover{color:#c8d6ea}
.a2-glabel .gi{width:22px;height:22px;border-radius:7px;background:rgba(255,255,255,.07);display:grid;place-items:center;font-size:10px;color:#7ef0c4;flex:none}
.a2-glabel .caret{margin-left:auto;font-size:9px;transition:.35s cubic-bezier(.22,.61,.36,1)}
.a2-group.open .a2-glabel .caret{transform:rotate(90deg);color:#7ef0c4}
.a2-gbody{max-height:0;overflow:hidden;transition:max-height .5s cubic-bezier(.22,.61,.36,1)}
.a2-group.open .a2-gbody{max-height:420px}
.a2-badge{margin-left:auto;background:rgba(0,184,116,.2);color:#7ef0c4;font-size:9.5px;font-weight:800;padding:2px 8px;border-radius:99px}
.sidebar a.on .a2-badge{background:rgba(255,255,255,.25);color:#fff}
.sb-foot{display:flex;align-items:center;justify-content:center;gap:8px}
.a2-ver{background:rgba(0,184,116,.15);border:1px solid rgba(0,184,116,.35);color:#7ef0c4;font-size:9.5px;font-weight:800;padding:3px 8px;border-radius:99px;letter-spacing:.6px}
</style>
<aside class="sidebar" id="sidebar">
  <div class="a2-sb-profile">
    <span class="a2-ava"><i class="fa-solid fa-user-astronaut"></i><span class="a2-on"></span></span>
    <span class="a2-pt"><b><?= e($_SESSION['admin_name']) ?></b><small><?= ucfirst(e($_SESSION['admin_role'])) ?> • Online</small></span>
  </div>
  <nav>
    <?php foreach ($groups as $gi => $g):
      $open = false;
      foreach ($g[2] as $it) if ($ACTIVE === $it[0]) $open = true;
      if (!$anyOpen && $gi === 0) $open = true; ?>
    <div class="a2-group <?= $open ? 'open' : '' ?>">
      <button type="button" class="a2-glabel">
        <span class="gi"><i class="fa-solid <?= $g[1] ?>"></i></span> <?= e($g[0]) ?>
        <i class="fa-solid fa-chevron-right caret"></i>
      </button>
      <div class="a2-gbody">
        <?php foreach ($g[2] as $n): ?>
        <a class="<?= $ACTIVE === $n[0] ? 'on' : '' ?>" href="<?= url('admin/' . $n[0]) ?>">
          <i class="fa-solid <?= $n[1] ?>"></i> <?= e($n[2]) ?>
          <?php if ($n[3] !== ''): ?><span class="a2-badge"><?= (int)$n[3] ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot"><i class="fa-solid fa-atom"></i> Prodi Fisika <?= date('Y') ?> <span class="a2-ver">v4.0</span></div>
</aside>