/* Cantine de Vatel — petits comportements côté navigateur (sans framework). */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const App = (window.App = window.App || {});

  // ---------- Toasts ----------
  App.toast = (msg, kind) => {
    const t = document.createElement('div');
    t.className = 'toast ' + (kind || '');
    t.textContent = msg;
    $('#toasts').appendChild(t);
    setTimeout(() => t.remove(), 3500);
  };
  $$('#toasts .toast').forEach((t) => setTimeout(() => t.remove(), 4000));

  // ---------- Modales : <div class="modal-bg hidden" id="m-xxx"> ; ouverture data-open="m-xxx" ----------
  App.open = (id) => { const m = document.getElementById(id); if (m) { m.classList.remove('hidden'); const f = m.querySelector('input:not([type=hidden]),textarea,select'); if (f) f.focus(); } };
  App.close = (el) => { const m = el.closest ? el.closest('.modal-bg') : el; if (m) m.classList.add('hidden'); };
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); App.open(o.dataset.open); return; }
    const c = e.target.closest('[data-close]');
    if (c) { e.preventDefault(); App.close(c); return; }
    if (e.target.classList && e.target.classList.contains('modal-bg') && !e.target.dataset.locked) e.target.classList.add('hidden');
  });

  // ---------- Confirmation avant action : <form data-confirm="Texte"> ----------
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      e.preventDefault();
      App.confirm(f.dataset.confirm, f.dataset.confirmText || '', f.dataset.confirmOk || 'Confirmer', () => { f.dataset.confirmed = '1'; f.requestSubmit ? f.requestSubmit() : f.submit(); }, f.dataset.danger !== undefined);
    }
  });
  App.confirm = (title, text, ok, onOk, danger) => {
    let m = document.getElementById('m-confirm');
    if (!m) {
      m = document.createElement('div');
      m.className = 'modal-bg hidden'; m.id = 'm-confirm';
      m.innerHTML = '<div class="modal"><h2></h2><p class="muted"></p><div class="actions"><button class="btn ghost" data-close>Annuler</button><button class="btn" data-ok></button></div></div>';
      document.body.appendChild(m);
    }
    m.querySelector('h2').textContent = title;
    m.querySelector('p').textContent = text;
    const b = m.querySelector('[data-ok]');
    b.textContent = ok; b.className = 'btn ' + (danger ? 'danger' : '');
    b.onclick = () => { m.classList.add('hidden'); onOk(); };
    m.classList.remove('hidden');
  };

  // ---------- QR codes : <div data-qr="texte"> ----------
  App.renderQr = () => $$('[data-qr]').forEach((el) => {
    if (el.dataset.done || !window.QRCode) return;
    el.dataset.done = '1';
    new QRCode(el, { text: el.dataset.qr, width: +(el.dataset.size || 200), height: +(el.dataset.size || 200), correctLevel: QRCode.CorrectLevel.M });
  });
  App.renderQr();

  // ---------- Sélecteurs de quantité : .stepper [data-step] ----------
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-step]');
    if (!b) return;
    e.preventDefault();
    const input = document.getElementById(b.dataset.target);
    const max = +(input.max || 5);
    input.value = Math.max(0, Math.min(max, (+input.value || 0) + (+b.dataset.step)));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // ---------- Interrupteurs : <button class="switch" data-toggle="input-id"> ----------
  document.addEventListener('click', (e) => {
    const s = e.target.closest('.switch[data-toggle]');
    if (!s) return;
    e.preventDefault();
    const i = document.getElementById(s.dataset.toggle);
    i.checked = !i.checked;
    s.classList.toggle('on', i.checked);
    i.dispatchEvent(new Event('change', { bubbles: true }));
    if (s.dataset.submit !== undefined) i.form.submit();
  });

  // ---------- Hors ligne : QR codes des réservations à venir enregistrés sur l'appareil (localStorage) ----------
  const store = {
    get: (k) => { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* mode privé ou stockage plein */ } },
    keys: () => { try { return Object.keys(localStorage).filter((k) => k.startsWith('cv:')); } catch (e) { return []; } },
    del: (k) => { try { localStorage.removeItem(k); } catch (e) { /* ignore */ } },
  };
  const todayIso = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  App.saveOffline = (key, data) => store.set('cv:' + key, data);
  // Remplace la liste complète (réservations annulées ou servies retirées de l'appareil)
  App.syncOfflineReservations = (list) => {
    store.keys().filter((k) => k.startsWith('cv:resa-')).forEach(store.del);
    (list || []).forEach((r) => store.set('cv:resa-' + r.id, r));
  };
  // Purge des QR codes dont la date est passée
  store.keys().filter((k) => k.startsWith('cv:resa-')).forEach((k) => { const r = store.get(k); if (!r || !r.iso || r.iso < todayIso) store.del(k); });
  const offlineData = document.getElementById('offline-reservations');
  if (offlineData) { try { App.syncOfflineReservations(JSON.parse(offlineData.textContent)); } catch (e) { /* données invalides */ } }

  // Déconnexion : effacer les QR et les pages personnelles mises en cache (téléphone partagé)
  App.clearOffline = () => {
    store.keys().forEach(store.del);
    if (navigator.serviceWorker && navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage('clear');
  };
  document.addEventListener('submit', (e) => { if (/\/deconnexion$/.test(e.target.getAttribute('action') || '')) App.clearOffline(); }, true);

  // Bandeau « hors ligne »
  const offlineBar = () => {
    let bar = document.getElementById('offline-bar');
    if (navigator.onLine) { if (bar) bar.remove(); return; }
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'offline-bar';
      bar.className = 'offline-bar';
      bar.setAttribute('role', 'status');
      bar.textContent = document.documentElement.lang === 'en' ? 'Offline — showing the last saved version. Your QR codes remain available.' : 'Hors ligne — affichage de la dernière version enregistrée. Vos QR codes restent disponibles.';
      document.body.prepend(bar);
    }
  };
  window.addEventListener('online', () => { offlineBar(); App.toast(document.documentElement.lang === 'en' ? 'Back online' : 'Connexion rétablie', 'green'); });
  window.addEventListener('offline', offlineBar);
  offlineBar();

  // ---------- Carrousels : <div data-carousel data-interval="5"> [data-track] [data-slide] [data-dots] ----------
  // Défilement automatique toutes les N secondes (0 = désactivé), en boucle ; pause au survol, au toucher, au focus
  // et quand l'onglet est masqué ; flèches, points cliquables et balayage tactile natif.
  App.carousel = (root) => {
    const track = root.querySelector('[data-track]');
    const slides = Array.from(root.querySelectorAll('[data-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-dots] button'));
    if (!track || slides.length < 2) return;
    const delay = Math.max(0, +(root.dataset.interval || 0)) * 1000;
    const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let timer = null, paused = false, resumeTimer = null;

    const current = () => {
      const x = track.scrollLeft;
      let best = 0, dist = Infinity;
      slides.forEach((s, i) => { const d = Math.abs(s.offsetLeft - slides[0].offsetLeft - x); if (d < dist) { dist = d; best = i; } });
      return best;
    };
    const atEnd = () => track.scrollLeft >= track.scrollWidth - track.clientWidth - 4;
    const go = (i) => {
      const n = (i + slides.length) % slides.length;
      track.scrollTo({ left: slides[n].offsetLeft - slides[0].offsetLeft, behavior: reduced ? 'auto' : 'smooth' });
    };
    const next = () => (atEnd() ? go(0) : go(current() + 1));
    const prev = () => (track.scrollLeft <= 4 ? go(slides.length - 1) : go(current() - 1));
    const paint = () => {
      const i = atEnd() ? slides.length - 1 : current();
      dots.forEach((d, k) => { d.classList.toggle('active', k === i); d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
    };
    const restartProgress = () => {
      const d = dots.find((x) => x.classList.contains('active'));
      dots.forEach((x) => x.classList.remove('run'));
      if (d && delay && !paused && !reduced) { void d.offsetWidth; d.classList.add('run'); }
    };
    const stop = () => { clearInterval(timer); timer = null; };
    const start = () => {
      stop();
      if (!delay || reduced || paused) { restartProgress(); return; }
      root.style.setProperty('--sugg-delay', delay + 'ms');
      timer = setInterval(next, delay);
      restartProgress();
    };
    const pause = () => { paused = true; clearTimeout(resumeTimer); stop(); root.classList.add('is-paused'); restartProgress(); };
    const resume = (after = 0) => { clearTimeout(resumeTimer); resumeTimer = setTimeout(() => { paused = false; root.classList.remove('is-paused'); start(); }, after); };

    dots.forEach((d, k) => d.addEventListener('click', () => { go(k); start(); }));

    // Glisser à la souris (le doigt utilise le défilement tactile natif), puis aimantation sur la carte la plus proche
    let drag = null, moved = false;
    track.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      drag = { x: e.clientX, left: track.scrollLeft, from: current() };
      moved = false;
      pause();
    });
    window.addEventListener('pointermove', (e) => {
      if (!drag) return;
      const dx = e.clientX - drag.x;
      if (!moved && Math.abs(dx) > 5) { moved = true; root.classList.add('is-dragging'); }
      if (moved) { e.preventDefault(); track.scrollLeft = drag.left - dx; }
    });
    window.addEventListener('pointerup', (e) => {
      if (!drag) return;
      const dx = e.clientX - drag.x;
      const from = drag.from;
      drag = null;
      root.classList.remove('is-dragging');
      if (moved) {
        // Geste franc (> 40 px) : carte voisine dans le sens du geste ; sinon carte la plus proche
        let target = dx < -40 ? from + 1 : dx > 40 ? from - 1 : current();
        target = Math.max(0, Math.min(slides.length - 1, target));
        go(target);
      }
      if (!root.matches(':hover')) resume(0);
    });
    // Après un glissement, on n'ouvre pas le lien « Réserver ce plat » sous la souris
    track.addEventListener('click', (e) => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
    track.addEventListener('dragstart', (e) => e.preventDefault());
    let raf = null;
    track.addEventListener('scroll', () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(() => { paint(); restartProgress(); }); }, { passive: true });
    root.addEventListener('mouseenter', pause);
    root.addEventListener('mouseleave', () => resume(0));
    root.addEventListener('focusin', pause);
    root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) resume(0); });
    track.addEventListener('touchstart', pause, { passive: true });
    track.addEventListener('touchend', () => resume(4000), { passive: true });
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    root.addEventListener('keydown', (e) => { if (e.key === 'ArrowRight') { e.preventDefault(); next(); } if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); } });
    paint();
    start();
  };
  $$('[data-carousel]').forEach(App.carousel);

  // ---------- Service worker (PWA) ----------
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }
})();
