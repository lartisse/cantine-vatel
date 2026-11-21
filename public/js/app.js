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

  // ---------- Sauvegarde hors ligne des QR (localStorage) ----------
  App.saveOffline = (key, data) => { try { localStorage.setItem('cv:' + key, JSON.stringify(data)); } catch (e) { /* mode privé */ } };

  // ---------- Service worker (PWA) ----------
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }
})();
