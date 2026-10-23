/* ============ PRODI FISIKA — PUBLIC JS ============ */
document.addEventListener('DOMContentLoaded', () => {

  /* ---- Loader khusus beranda ---- */
  const loader = document.getElementById('loader');
  if (loader) {
    const hide = () => loader.classList.add('done');
    window.addEventListener('load', () => setTimeout(hide, 900));
    setTimeout(hide, 4500); // fallback
  }

  /* ---- Jam WIB ---- */
  const clock = document.getElementById('clockWIB');
  if (clock) {
    const fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const tick = () => { clock.textContent = fmt.format(new Date()).replace(/\./g, ':'); };
    tick(); setInterval(tick, 1000);
  }

  /* ---- Navbar scroll ---- */
  const nav = document.getElementById('navbar');
  const toTop = document.getElementById('toTop');
  window.addEventListener('scroll', () => {
    nav?.classList.toggle('scrolled', window.scrollY > 40);
    toTop?.classList.toggle('show', window.scrollY > 500);
  });
  toTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* ---- Mobile menu & accordion dropdown ---- */
  const burger = document.getElementById('btnBurger');
  const menu = document.getElementById('navMenu');
  burger?.addEventListener('click', () => menu.classList.toggle('open'));
  document.querySelectorAll('.nav-item.has-child > a').forEach(a => {
    a.addEventListener('click', ev => {
      if (window.innerWidth <= 860) { ev.preventDefault(); a.parentElement.classList.toggle('open'); }
    });
  });

  /* ---- Search overlay ---- */
  const so = document.getElementById('searchOverlay');
  document.getElementById('btnSearch')?.addEventListener('click', () => { so.classList.add('open'); so.querySelector('input')?.focus(); });
  document.getElementById('searchClose')?.addEventListener('click', () => so.classList.remove('open'));
  so?.addEventListener('click', ev => { if (ev.target === so) so.classList.remove('open'); });

  /* ---- Hero slider (foto & video) ---- */
  const slides = [...document.querySelectorAll('.slide')];
  const dotsBox = document.getElementById('heroDots');
  if (slides.length && dotsBox) {
    let cur = 0, timer = null;
    slides.forEach((_, i) => {
      const d = document.createElement('button');
      d.className = 'dot' + (i === 0 ? ' active' : '');
      d.addEventListener('click', () => { go(i); restart(); });
      dotsBox.appendChild(d);
    });
    const dots = [...dotsBox.children];
    function go(i) {
      slides[cur].classList.remove('active'); dots[cur].classList.remove('active');
      const vOld = slides[cur].querySelector('video'); if (vOld) vOld.pause();
      cur = (i + slides.length) % slides.length;
      slides[cur].classList.add('active'); dots[cur].classList.add('active');
      const v = slides[cur].querySelector('video'); if (v) v.play().catch(() => {});
    }
    const restart = () => { clearInterval(timer); timer = setInterval(() => go(cur + 1), 6500); };
    document.getElementById('heroNext')?.addEventListener('click', () => { go(cur + 1); restart(); });
    document.getElementById('heroPrev')?.addEventListener('click', () => { go(cur - 1); restart(); });
    restart();
  }

  /* ---- Partikel fisika (canvas hero) ---- */
  const cv = document.getElementById('particles');
  if (cv) {
    const ctx = cv.getContext('2d');
    let W, H, pts = [];
    const N = 70;
    const resize = () => { W = cv.width = cv.offsetWidth; H = cv.height = cv.offsetHeight; };
    resize(); window.addEventListener('resize', resize);
    for (let i = 0; i < N; i++) pts.push({ x: Math.random() * 900, y: Math.random() * 600, vx: (Math.random() - .5) * .6, vy: (Math.random() - .5) * .6, r: Math.random() * 2 + 1, g: Math.random() > .7 });
    (function loop() {
      ctx.clearRect(0, 0, W, H);
      pts.forEach(p => {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > W) p.vx *= -1;
        if (p.y < 0 || p.y > H) p.vy *= -1;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 7);
        ctx.fillStyle = p.g ? 'rgba(0,184,116,.8)' : 'rgba(120,180,255,.8)'; ctx.fill();
      });
      for (let i = 0; i < N; i++) for (let j = i + 1; j < N; j++) {
        const dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y, d = Math.hypot(dx, dy);
        if (d < 110) {
          ctx.strokeStyle = `rgba(120,180,255,${(1 - d / 110) * .35})`;
          ctx.lineWidth = 1; ctx.beginPath();
          ctx.moveTo(pts[i].x, pts[i].y); ctx.lineTo(pts[j].x, pts[j].y); ctx.stroke();
        }
      }
      requestAnimationFrame(loop);
    })();
  }

  /* ---- Reveal on scroll ---- */
  const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('on'); io.unobserve(en.target); } }), { threshold: .15 });
  document.querySelectorAll('.reveal').forEach((el, i) => { el.style.transitionDelay = (i % 4) * 90 + 'ms'; io.observe(el); });

  /* ---- Counter statistik ---- */
  const cio = new IntersectionObserver(es => es.forEach(en => {
    if (!en.isIntersecting) return;
    const el = en.target, target = +el.dataset.target; let n = 0;
    const step = Math.max(1, Math.ceil(target / 70));
    const t = setInterval(() => { n += step; if (n >= target) { n = target; clearInterval(t); } el.textContent = n; }, 24);
    cio.unobserve(el);
  }), { threshold: .6 });
  document.querySelectorAll('.counter').forEach(el => cio.observe(el));

  /* ---- Lightbox galeri ---- */
  const lb = document.getElementById('lightbox');
  if (lb) {
    document.querySelectorAll('.g-item').forEach(it => it.addEventListener('click', () => {
      lb.querySelector('img').src = it.dataset.full; lb.classList.add('open');
    }));
    document.getElementById('lbClose')?.addEventListener('click', () => lb.classList.remove('open'));
    lb.addEventListener('click', ev => { if (ev.target === lb) lb.classList.remove('open'); });
  }
});