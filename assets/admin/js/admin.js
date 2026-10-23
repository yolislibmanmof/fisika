/* ============ PRODI FISIKA — ADMIN JS ============ */
document.addEventListener('DOMContentLoaded', () => {

  /* ---- Sidebar toggle ---- */
  const btn = document.getElementById('btnSidebar');
  btn?.addEventListener('click', () => {
    if (window.innerWidth <= 860) document.body.classList.toggle('sb-open');
    else document.body.classList.toggle('sb-min');
  });

  /* ---- Auto slug dari judul ---- */
  document.querySelectorAll('[data-slug]').forEach(src => {
    src.addEventListener('input', () => {
      const t = document.querySelector(src.dataset.slug);
      if (t && !t.dataset.locked) t.value = src.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
  });

  /* ---- Live preview gambar (FileReader) ---- */
  document.querySelectorAll('input[type=file][data-preview]').forEach(inp => {
    inp.addEventListener('change', () => {
      const target = document.querySelector(inp.dataset.preview);
      if (!target || !inp.files[0]) return;
      const rd = new FileReader();
      rd.onload = ev => {
        target.src = ev.target.result;
        target.classList.remove('hidden');
        target.parentElement?.querySelector('.up-ph')?.classList.add('hidden');
      };
      rd.readAsDataURL(inp.files[0]);
    });
  });

  /* ---- Live preview teks sambutan / field ber-atribut data-bind ---- */
  document.querySelectorAll('[data-bind]').forEach(src => {
    const tgt = document.querySelector(src.dataset.bind);
    if (!tgt) return;
    const sync = () => { tgt.textContent = src.value || '—'; };
    src.addEventListener('input', sync); sync();
  });

  /* ---- ICON PICKER (ikon real Font Awesome) ---- */
  const ICONS = [
    'fa-solid fa-atom','fa-solid fa-flask','fa-solid fa-flask-vial','fa-solid fa-magnet','fa-solid fa-bolt',
    'fa-solid fa-satellite','fa-solid fa-satellite-dish','fa-solid fa-microscope','fa-solid fa-calculator',
    'fa-solid fa-ruler-combined','fa-solid fa-temperature-half','fa-solid fa-lightbulb','fa-solid fa-rocket',
    'fa-solid fa-gears','fa-solid fa-chart-line','fa-solid fa-book-open','fa-solid fa-graduation-cap',
    'fa-solid fa-user-graduate','fa-solid fa-users','fa-solid fa-handshake','fa-solid fa-globe','fa-solid fa-laptop-code',
    'fa-solid fa-database','fa-solid fa-brain','fa-solid fa-eye','fa-solid fa-wave-square','fa-solid fa-circle-nodes',
    'fa-solid fa-solar-panel','fa-solid fa-wind','fa-solid fa-droplet','fa-solid fa-fire','fa-solid fa-star',
    'fa-solid fa-trophy','fa-solid fa-medal','fa-solid fa-calendar-days','fa-solid fa-newspaper','fa-solid fa-images',
    'fa-solid fa-video','fa-solid fa-phone','fa-solid fa-envelope','fa-solid fa-location-dot','fa-solid fa-clock',
    'fa-solid fa-compass','fa-solid fa-map','fa-solid fa-diagram-project','fa-solid fa-infinity','fa-solid fa-meteor',
    'fa-solid fa-shuttle-space','fa-solid fa-chalkboard-user','fa-solid fa-book','fa-solid fa-award','fa-solid fa-microchip'
  ];
  const modal = document.getElementById('iconModal');
  const grid  = document.getElementById('iconGrid');
  let targetInput = null, targetPrev = null;
  if (grid) {
    ICONS.forEach(ic => {
      const b = document.createElement('button');
      b.type = 'button'; b.title = ic;
      b.innerHTML = `<i class="${ic}"></i>`;
      b.addEventListener('click', () => {
        if (targetInput) targetInput.value = ic;
        if (targetPrev) targetPrev.className = ic;
        modal.classList.remove('open');
      });
      grid.appendChild(b);
    });
  }
  document.querySelectorAll('[data-iconpicker]').forEach(b => {
    b.addEventListener('click', () => {
      targetInput = document.querySelector(b.dataset.iconpicker);
      targetPrev  = b.dataset.previewicon ? document.querySelector(b.dataset.previewicon) : null;
      modal?.classList.add('open');
    });
  });
  document.getElementById('iconClose')?.addEventListener('click', () => modal.classList.remove('open'));
  modal?.addEventListener('click', ev => { if (ev.target === modal) modal.classList.remove('open'); });
});