/* =========================================================
   Cantine de Vatel — noyau : utilitaires, routeur, mises en page,
   composants partagés (modale, toast, QR, graphiques)
   ========================================================= */
(function () {
  const D = window.DB;

  // État d'interface (non persistant)
  const UI = {
    menuDay: D.NOW.date, compatOnly: false, resaTab: 'avenir', walletMonth: '11',
    ordersDate: D.NOW.date, ordersSlot: 'all', ordersStatus: 'all', ordersSearch: '', ordersLimit: 40,
    planWeek: 'current', dishFilter: 'all', dishSearch: '', reportType: 'freq', dashPeriod: 7,
    usersTab: 'users', usersSearch: '', usersRole: 'all', usersLimit: 40,
    kResult: null, reg: { step: 1, profile: null, data: {}, allergies: [], diets: [] },
    book: null, topup: { amount: 5000, method: 'wave', custom: '' },
    ptopup: { amount: 5000, method: 'wave', auto: false, custom: '' },
    rate: null, payChild: 'all', lastBooked: null, linkFound: null, dishDraft: null, dishTab: 'fiche'
  };

  /* ---------------- Utilitaires ---------------- */
  const $ = (s, root) => (root || document).querySelector(s);
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const num = (n) => Math.round(Math.abs(n)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  const fcfa = (n) => (n < 0 ? '−' : '') + num(n) + ' FCFA';
  const dish = (id) => D.dishes.find(d => d.id === id);
  const user = (id) => D.users.find(u => u.id === id);
  const me = () => (D.session ? user(D.session.userId) : null);
  const fullName = (u) => u.first + ' ' + u.last;
  const initials = (u) => (u.first[0] + u.last[0]).toUpperCase();
  const toDate = (iso) => new Date(iso + 'T12:00:00');
  const fmtLong = (iso) => { const s = toDate(iso).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }); return s.charAt(0).toUpperCase() + s.slice(1); };
  const fmtShort = (iso) => toDate(iso).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
  const fmtDM = (iso) => iso.slice(8, 10) + '/' + iso.slice(5, 7);
  const allDays = () => D.week.concat(D.nextWeek);
  const dayOf = (date) => allDays().find(d => d.date === date);
  const isToday = (date) => date === D.NOW.date;
  const bookingOpen = (date) => date > D.NOW.date || (date === D.NOW.date && D.NOW.time < D.cutoff);
  const conflicts = (d, u) => (u && u.allergies ? d.allergens.filter(a => u.allergies.includes(a)) : []);
  const activeRes = (uid, date) => D.reservations.find(r => r.userId === uid && r.date === date && r.status !== 'annulee');
  const dayRes = (date) => D.reservations.filter(r => r.date === date && r.status !== 'annulee');
  const reservedCount = (date, id) => dayRes(date).reduce((n, r) => n + r.items.filter(i => i === id).length, 0);
  const remaining = (date, id) => ((D.portions[date] && D.portions[date][id]) || 0) - reservedCount(date, id);
  const slotCount = (date, slotId) => dayRes(date).filter(r => r.slot === slotId).length;
  const slotLabel = (id) => (D.slots.find(s => s.id === id) || { label: id }).label;
  const dishesOfDay = (day) => ['entree', 'plat', 'dessert', 'boisson'].flatMap(c => day[c]);
  const statusBadge = (s) => ({
    payee: '<span class="badge">Payée</span>',
    servie: '<span class="badge green">Servie</span>',
    'non-retiree': '<span class="badge orange">Non retirée</span>',
    annulee: '<span class="badge gray">Annulée</span>'
  }[s] || s);
  const stars = (r, count) => `<span class="stars"><svg class=ic-star><use href=#i-star></use></svg></span> <b>${r.toFixed(1).replace('.', ',')}</b>${count != null ? ` <span class="muted xs">(${count})</span>` : ''}`;
  const allergenBadges = (d, u) => d.allergens.map(a => `<span class="badge ${u && u.allergies && u.allergies.includes(a) ? 'red' : 'gray'}">${D.ALLERGENS[a]}</span>`).join(' ');
  // Regroupe les articles d'une réservation : « 2× Attiéké · Bissap »
  const groupItems = (items) => { const m = {}; items.forEach(id => { m[id] = (m[id] || 0) + 1; }); return m; };
  const resItemsText = (r) => Object.entries(groupItems(r.items)).map(([id, n]) => (n > 1 ? n + '× ' : '') + dish(id).name).join(' · ');
  const displayName = (u) => (!u || u.status === 'deleted' ? 'Utilisateur supprimé' : u.first + ' ' + u.last);
  const isActive = (u) => !u.status || u.status === 'active';
  // Avatar : photo si présente, sinon initiales
  const avatar = (u, cls = '', style = '') => u && u.photo && u.status !== 'deleted'
    ? `<div class="avatar ${cls}" style="${style}"><img src="${u.photo}" alt=""></div>`
    : `<div class="avatar ${cls}" style="${style}">${u && u.status !== 'deleted' ? initials(u) : '?'}</div>`;
  // Image de plat : photo téléversée, sinon image par défaut (icône de catégorie)
  const dishImg = (d, cls = '') => d.photo ? `<div class="dish-img ${cls}"><img src="${d.photo}" alt=""></div>` : `<div class="dish-img ph ${cls}" title="Image par défaut">${App.ic(App.catIcon(d.cat))}</div>`;
  const typeLabel = (u) => (u.type === 'personnel' ? 'Personnel' : 'Élève');
  // Champs facultatifs manquants (indicateur non bloquant)
  const missingFields = (u) => {
    if (u.role !== 'convive') return u.photo ? [] : ['photo'];
    const m = [];
    if (u.type === 'eleve') { if (!u.group) m.push('classe'); if (!u.matricule) m.push('matricule'); }
    if (u.type === 'personnel' && !u.poste) m.push('poste');
    if (!u.photo) m.push('photo');
    return m;
  };
  // Modération automatique : repère les mots interdits
  const findForbidden = (text) => { const t = ' ' + (text || '').toLowerCase() + ' '; return D.forbiddenWords.filter(w => w && (w.includes('.') ? t.includes(w) : new RegExp('[^a-zà-ÿ]' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '[^a-zà-ÿ]').test(t))); };
  const scanReview = (r) => { r.flagWords = findForbidden(r.comment); r.flagged = r.flagWords.length > 0; if (r.flagged && r.status === 'visible') r.status = 'pending'; };
  D.reviews.forEach(scanReview);
  const roleLabel = (r) => ({ convive: 'Convive', parent: 'Parent', admin: 'Administrateur', superadmin: 'Super admin', comptoir: 'Comptoir' }[r] || r);
  const providerName = (id) => (D.providers.find(p => p.id === id) || { name: id }).name;
  // Moyens de paiement proposés aux utilisateurs = activés en Production avec clés
  const userPayMethods = (withCard) => D.providers.filter(p => ['wave', 'om', 'momo', 'moov', 'card'].includes(p.id) && (withCard || p.id !== 'card') && p.prod.enabled && p.fields.every(f => p.prod.keys[f[0]]));
  const mainDish = (r) => dish(r.items.find(id => dish(id).cat === 'plat') || r.items[0]);
  const currentSlot = () => {
    const t = D.NOW.time;
    const toMin = (x) => parseInt(x.slice(0, 2), 10) * 60 + parseInt(x.slice(3, 5), 10);
    return D.slots.find(s => toMin(t) >= toMin(s.id) && toMin(t) < toMin(s.id) + 30) || null;
  };
  let txSeq = 100, resSeq = 1000, notifSeq = 100;
  const newId = (p) => p + (++txSeq + resSeq++ + notifSeq++);
  const nowStamp = () => fmtDM(D.NOW.date) + ' · ' + D.NOW.time;

  function addTx(userId, type, label, amount, ref) {
    D.transactions.unshift({ id: newId('t'), userId, date: D.NOW.date, type, label, amount, ref });
  }
  function notify(userId, icon, title, text, go) {
    D.notifications.unshift({ id: newId('n'), userId, date: nowStamp(), icon, title, text, go, read: false });
  }
  function audit(action, target, detail) {
    const u = me();
    D.audit.unshift({ at: nowStamp(), who: u ? fullName(u) : 'Système', role: u ? u.role : 'system', action, target, detail: detail || '' });
  }
  // Alerte les administrateurs et super administrateurs actifs (une notification chacun, lue séparément)
  function notifyAdmins(icon, title, text, extra) {
    D.users.filter(x => (x.role === 'admin' || x.role === 'superadmin') && isActive(x) && !x.disabled).forEach(a => {
      D.notifications.unshift(Object.assign({ id: newId('n'), userId: a.id, date: nowStamp(), icon, title, text, go: '#/a/notifs', read: false }, extra || {}));
    });
    if (D.session && (D.session.role === 'admin' || D.session.role === 'superadmin')) toast(title, 'red');
  }
  // Détecte les plats d'une réservation contenant un allergène déclaré par le convive
  function allergyConflicts(u, items) {
    return [...new Set(items)].map(id => ({ dishId: id, allergens: conflicts(dish(id), u) })).filter(c => c.allergens.length);
  }
  function alertAllergy(u, r, byParent) {
    r.allergyConflict = allergyConflicts(u, r.items);
    if (!r.allergyConflict.length) return;
    const what = r.allergyConflict.map(c => `« ${dish(c.dishId).name} » (${c.allergens.map(a => D.ALLERGENS[a].toLowerCase()).join(', ')})`).join(' et ');
    const who = `${u.first} ${u.last} (${u.type === 'personnel' ? (u.poste || 'personnel') : (u.group || 'élève, classe non renseignée')})`;
    notifyAdmins('<svg class=ic><use href=#i-alert></use></svg>', 'Allergie : réservation à risque', `${who} a réservé ${what}, allergène déclaré dans son profil${byParent ? ' — réservation faite par le parent' : ''}. ${fmtShort(r.date)}, créneau ${slotLabel(r.slot)}.`, { resId: r.id, kind: 'allergy' });
  }
  function checkLowBalance(u) {
    if (u.role !== 'convive' || u.balance >= (u.threshold || 1000)) return;
    notify(u.id, '<svg class=ic><use href=#i-alert></use></svg>', 'Solde bas', `Votre solde est de ${fcfa(u.balance)}.`, '#/u/wallet');
    if (u.parentId) notify(u.parentId, '<svg class=ic><use href=#i-alert></use></svg>', 'Solde bas — ' + u.first, `Le solde de ${u.first} est de ${fcfa(u.balance)} (seuil : ${fcfa(u.threshold || 1000)}).`, '#/p/topup/' + u.id);
  }

  /* ---------------- QR code (visuel de maquette) ---------------- */
  function qrSvg(code) {
    const n = 25; let h = 0;
    for (const c of String(code)) h = (h * 31 + c.charCodeAt(0)) >>> 0;
    const bit = (x, y) => { h = (h * 1103515245 + 12345 + x * 7 + y * 13) >>> 0; return (h >>> 16) & 1; };
    const inFinder = (x, y) => (x < 8 && y < 8) || (x > n - 9 && y < 8) || (x < 8 && y > n - 9);
    let cells = '';
    for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) if (!inFinder(x, y) && bit(x, y)) cells += `<rect x="${x}" y="${y}" width="1" height="1"/>`;
    const finder = (fx, fy) => `<rect x="${fx}" y="${fy}" width="7" height="7"/><rect x="${fx + 1}" y="${fy + 1}" width="5" height="5" fill="#fff"/><rect x="${fx + 2}" y="${fy + 2}" width="3" height="3"/>`;
    return `<svg viewBox="-1 -1 ${n + 2} ${n + 2}" shape-rendering="crispEdges" aria-label="QR code ${code}"><rect x="-1" y="-1" width="${n + 2}" height="${n + 2}" fill="#fff"/><g fill="#1d1d1b">${cells}${finder(0, 0)}${finder(n - 7, 0)}${finder(0, n - 7)}</g></svg>`;
  }

  /* ---------------- Graphiques SVG ---------------- */
  // Maximum « rond » dont le quart est aussi rond (graduations propres)
  function niceMax(v) {
    if (v <= 0) return 4;
    const raw = v / 4, p = Math.pow(10, Math.floor(Math.log10(raw)));
    for (const m of [1, 2, 2.5, 5, 10]) if (m * p >= raw) return m * p * 4;
    return 40 * p;
  }
  function barChart({ labels, series, height = 220, stacked = false, fmt = (v) => num(v), width = 680 }) {
    const W = width, H = height, pl = 46, pr = 8, pt = 12, pb = 28;
    const totals = labels.map((_, i) => series.reduce((s, se) => s + se.values[i], 0));
    const max = niceMax(stacked ? Math.max(...totals) : Math.max(...series.flatMap(s => s.values)));
    const iw = W - pl - pr, ih = H - pt - pb, cw = iw / labels.length;
    const y = (v) => pt + ih - (v / max) * ih;
    let g = '';
    for (let k = 0; k <= 4; k++) {
      const v = (max / 4) * k, yy = y(v);
      g += `<line x1="${pl}" x2="${W - pr}" y1="${yy}" y2="${yy}" stroke="#e9ecf1"/><text x="${pl - 8}" y="${yy + 4}" text-anchor="end" font-size="11" fill="#6b6f76">${fmt(v)}</text>`;
    }
    const step = Math.ceil(labels.length / 12);
    labels.forEach((l, i) => {
      const cx = pl + cw * i + cw / 2;
      if (stacked) {
        let acc = 0; const bw = Math.min(34, cw * 0.62);
        series.forEach(se => { const v = se.values[i]; g += `<rect x="${cx - bw / 2}" y="${y(acc + v)}" width="${bw}" height="${Math.max(0, y(acc) - y(acc + v))}" fill="${se.color}"><title>${esc(l)} — ${se.name} : ${fmt(v)}</title></rect>`; acc += v; });
      } else {
        const n = series.length, gw = Math.min(cw * 0.72, n * 26), bw = gw / n - 2;
        series.forEach((se, si) => { const v = se.values[i]; const x = cx - gw / 2 + si * (bw + 2); g += `<rect x="${x}" y="${y(v)}" width="${bw}" height="${Math.max(0, y(0) - y(v))}" rx="3" fill="${se.color}"><title>${esc(l)} — ${se.name} : ${fmt(v)}</title></rect>`; });
      }
      if (i % step === 0) g += `<text x="${cx}" y="${H - 8}" text-anchor="middle" font-size="11" fill="#6b6f76">${esc(l)}</text>`;
    });
    const legend = series.length > 1 ? `<div class="legend">${series.map(s => `<span><i style="background:${s.color}"></i>${s.name}</span>`).join('')}</div>` : '';
    return `<div class="chart"><svg viewBox="0 0 ${W} ${H}" role="img">${g}</svg>${legend}</div>`;
  }
  function hbars(items, max, fmt = (v) => v) {
    return `<div class="bars-h">${items.map(it => `<div class="bh"><span>${esc(it.label)}</span><div class="track"><div class="fill" style="width:${Math.min(100, (it.value / (max || 1)) * 100)}%;background:${it.color || 'var(--sky)'}"></div></div><b class="num">${fmt(it.value)}</b></div>`).join('')}</div>`;
  }

  /* ---------------- Modale & toast ---------------- */
  const isEnglish = () => !!(App.i18n && D.session && ['convive', 'parent'].includes(D.session.role) && App.i18n.lang() === 'en');
  function modal(html, opts = {}) {
    closeModal();
    const bg = document.createElement('div');
    bg.className = 'modal-bg'; bg.id = 'modal';
    bg.innerHTML = `<div class="modal" style="${opts.width ? 'max-width:' + opts.width + 'px' : ''}">${html}</div>`;
    bg.addEventListener('click', (e) => { if (e.target === bg && !opts.locked) closeModal(); });
    document.body.appendChild(bg);
    if (isEnglish()) App.i18n.translateDOM(bg);
    const f = bg.querySelector('input,select,textarea'); if (f && !opts.noFocus) f.focus();
  }
  function closeModal() { const m = $('#modal'); if (m) m.remove(); }
  function confirmModal(title, text, okLabel, onOk, danger) {
    App._onOk = onOk;
    modal(`<h2>${title}</h2><p class="muted">${text}</p><div class="actions"><button class="btn ghost" onclick="App.closeModal()">Retour</button><button class="btn ${danger ? 'danger' : ''}" onclick="App.closeModal();App._onOk()">${okLabel}</button></div>`);
  }
  function toast(msg, kind) {
    let w = $('.toast-wrap');
    if (!w) { w = document.createElement('div'); w.className = 'toast-wrap'; document.body.appendChild(w); }
    const t = document.createElement('div'); t.className = 'toast ' + (kind || ''); t.textContent = isEnglish() ? App.i18n.translate(msg) : msg;
    w.appendChild(t); setTimeout(() => t.remove(), 3200);
  }
  // Paiement Mobile Money simulé
  function simulatePayment(label, amount, done) {
    modal(`<div class="center"><h2>Validation du paiement</h2><p class="muted">${esc(label)} — <b>${fcfa(amount)}</b></p><div class="spinner"></div><p class="small muted">Confirmez le paiement sur votre téléphone (code PIN)…</p><p class="xs muted mt">Paiement simulé pour la démo</p></div>`, { locked: true });
    setTimeout(() => { closeModal(); done('MM-' + Math.floor(10000 + Math.random() * 89999)); }, 1600);
  }
  function downloadCSV(name, rows) {
    const csv = '﻿' + rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(';')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    a.download = name; document.body.appendChild(a); a.click(); a.remove();
  }

  /* ---------------- Ressaisie du mot de passe (actions sensibles) ---------------- */
  function requireSudo(reason, cb) {
    if (D.sudoUntil > Date.now()) return cb();
    App._sudoCb = cb;
    modal(`<h2><svg class=ic><use href=#i-lock></use></svg> Confirmez votre identité</h2><p class="muted small mb">${reason}. Saisissez votre mot de passe de super administrateur.</p>
      <form onsubmit="event.preventDefault();App.A.sudoOk()"><div class="field"><label>Mot de passe</label><input class="input" type="password" id="sudo-pw" autocomplete="current-password"><span class="error" id="sudo-err"></span></div>
      <p class="xs muted">Démo : n’importe quel mot de passe de 6 caractères ou plus. Valable 5 minutes.</p>
      <div class="actions"><button type="button" class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn">Confirmer</button></div></form>`);
  }

  /* ---------------- Images : validation, recadrage, compression ---------------- */
  const IMG_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  const IMG_MAX = 5 * 1024 * 1024;
  const fmtSize = (b) => (b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko');
  // Validation côté client (la même règle est revérifiée côté serveur)
  function validateImage(file) {
    if (!file) return 'Aucun fichier sélectionné.';
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!IMG_TYPES.includes(file.type) || !['jpg', 'jpeg', 'png', 'webp'].includes(ext)) return 'Format non accepté (JPG, PNG ou WebP uniquement).';
    if (file.size > IMG_MAX) return 'Image trop lourde (5 Mo maximum)';
    return null;
  }
  // Ouvre le recadreur ; onDone(dataUrl, info) reçoit l'image recadrée et compressée
  function openCropper(file, opts) {
    const err = validateImage(file);
    if (err) { toast(err, 'red'); return; }
    const reader = new FileReader();
    reader.onload = () => {
      const img = new Image();
      img.onload = () => {
        const C = { img, file, zoom: 1, x: 0, y: 0, shape: opts.shape || 'circle', opts };
        App._crop = C;
        const shapes = opts.allowShape ? `<div class="chips mb"><button class="chip ${C.shape === 'circle' ? 'active' : ''}" id="sh-circle" onclick="App.A.cropShape('circle')">● Cercle</button><button class="chip ${C.shape === 'square' ? 'active' : ''}" id="sh-square" onclick="App.A.cropShape('square')">■ Carré</button></div>` : '';
        modal(`<h2>${opts.title || 'Recadrer la photo'}</h2>${shapes}
          <div class="crop-stage"><canvas id="crop-cv" width="300" height="300"></canvas><div class="crop-mask ${C.shape}" id="crop-mask"></div></div>
          <div class="row mt"><span class="small">−</span><input type="range" id="crop-zoom" min="1" max="3" step="0.01" value="1" class="grow" oninput="App._crop.zoom=+this.value;App.A.cropDraw()"><span class="small">+</span></div>
          <p class="xs muted center">Faites glisser l’image pour la cadrer. Original : ${fmtSize(file.size)} · ${img.naturalWidth}×${img.naturalHeight}px</p>
          <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.cropSave()">${opts.okLabel || 'Enregistrer'}</button></div>`, { noFocus: true, locked: true, width: 400 });
        const cv = document.getElementById('crop-cv');
        let drag = null;
        cv.addEventListener('pointerdown', (e) => { drag = { x: e.clientX, y: e.clientY, ox: C.x, oy: C.y }; cv.setPointerCapture(e.pointerId); });
        cv.addEventListener('pointermove', (e) => { if (!drag) return; C.x = drag.ox + (e.clientX - drag.x); C.y = drag.oy + (e.clientY - drag.y); A.cropDraw(); });
        cv.addEventListener('pointerup', () => { drag = null; });
        cv.addEventListener('wheel', (e) => { e.preventDefault(); C.zoom = Math.min(3, Math.max(1, C.zoom - e.deltaY * 0.001)); document.getElementById('crop-zoom').value = C.zoom; A.cropDraw(); }, { passive: false });
        A.cropDraw();
      };
      img.onerror = () => toast('Image illisible ou corrompue.', 'red');
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  }
  function cropGeometry(C, size) {
    const base = Math.max(size / C.img.naturalWidth, size / C.img.naturalHeight) * C.zoom;
    const w = C.img.naturalWidth * base, h = C.img.naturalHeight * base;
    // empêche de laisser un bord vide
    const scale = size / 300;
    let x = (size - w) / 2 + C.x * scale, y = (size - h) / 2 + C.y * scale;
    x = Math.min(0, Math.max(size - w, x)); y = Math.min(0, Math.max(size - h, y));
    C.x = (x - (size - w) / 2) / scale; C.y = (y - (size - h) / 2) / scale;
    return { x, y, w, h };
  }
  // Champ fichier caché réutilisable : source « gallery » ou « camera »
  function pickImage(source, onFile) {
    const inp = document.createElement('input');
    inp.type = 'file'; inp.accept = 'image/jpeg,image/png,image/webp';
    if (source === 'camera') inp.setAttribute('capture', 'environment');
    inp.style.display = 'none';
    inp.onchange = () => { const f = inp.files[0]; inp.remove(); if (f) onFile(f); };
    document.body.appendChild(inp); inp.click();
  }

  /* ---------------- Routeur ---------------- */
  const routes = [];
  const homeFor = () => !D.session ? '#/' : ({ convive: '#/u/home', parent: '#/p/home', admin: '#/a/dashboard', superadmin: '#/a/dashboard', comptoir: '#/k/scan' }[D.session.role]);
  function go(h) { if (location.hash === h) render(); else location.hash = h; }
  function matchRoute(pat, path) {
    const a = pat.split('/'), b = path.split('/');
    if (a.length !== b.length) return null;
    const p = {};
    for (let i = 0; i < a.length; i++) { if (a[i].startsWith(':')) p[a[i].slice(1)] = decodeURIComponent(b[i]); else if (a[i] !== b[i]) return null; }
    return p;
  }
  let lastHash = null;
  function render() {
    const hash = location.hash.slice(1) || '/';
    const same = hash === lastHash;
    const body = $('.phone-body');
    const keep = same ? { win: window.scrollY, body: body ? body.scrollTop : 0 } : null;
    lastHash = hash;
    const path = hash.split('?')[0];
    const area = path.split('/')[1];
    // Contrôle d'accès par rôle : chaque espace liste les rôles autorisés
    const allowed = { u: ['convive'], p: ['parent'], a: ['admin', 'superadmin'], s: ['superadmin'], k: ['comptoir'] }[area];
    if (D.session && (!isActive(me()) || me().disabled)) { D.session = null; toast('Ce compte est désactivé.', 'red'); }
    let forbidden = false;
    if (allowed && (!D.session || !allowed.includes(D.session.role))) {
      if (D.session && area === 's' && D.session.role === 'admin') forbidden = true;
      else { location.hash = D.session ? homeFor() : '#/login'; return; }
    }
    if (path === '/profile' && !D.session) { location.hash = '#/login'; return; }
    let view = null;
    if (forbidden) view = { layout: 'admin', nav: '', title: 'Accès refusé', html: `<div class="empty" style="padding:80px 20px"><div class="ico"><svg class=ic><use href=#i-lock></use></svg></div><h2>Accès refusé</h2><p class="mt">Cet écran est réservé au super administrateur.</p><p class="xs muted mt">Code 403 — la même règle est appliquée côté serveur (middleware de rôle).</p><a class="btn mt" href="#/a/dashboard">Retour au tableau de bord</a></div>` };
    else for (const [pat, fn] of routes) { const p = matchRoute(pat, path); if (p) { view = fn(p); break; } }
    if (!view) { location.hash = homeFor(); return; }
    if (view.redirect) { location.hash = view.redirect; return; }
    $('#app').innerHTML = layout(view);
    renderDemoBar();
    document.title = (view.title ? view.title.replace(/<[^>]+>/g, '') + ' · ' : '') + 'Cantine de Vatel';
    // Langue de l'interface mobile (élève, personnel, parent)
    const en = isEnglish();
    document.documentElement.lang = en ? 'en' : 'fr';
    if (en) App.i18n.translateDOM($('.phone'));
    if (view.after) view.after();
    if (keep) { window.scrollTo(0, keep.win); const b = $('.phone-body'); if (b) b.scrollTop = keep.body; }
    else window.scrollTo(0, 0);
  }

  /* ---------------- Mises en page ---------------- */
  const brand = (sub) => `<div class="brand"><img src="img/vatel_logo.png" alt="Vatel"><span>Cantine de Vatel${sub ? `<small>${sub}</small>` : ''}</span></div>`;
  function layout(v) {
    if (v.layout === 'public') {
      return `<div class="public"><header class="public-header">${brand('Réservez · Payez · Mangez')}<div class="row">${D.session ? `<a class="btn sm" href="${homeFor()}">Mon espace</a>` : `<a class="btn ghost sm" href="#/login">Se connecter</a><a class="btn sm" href="#/register">Créer un compte</a>`}</div></header>${v.html}</div>`;
    }
    if (v.layout === 'kiosk') return `<div class="kiosk">${v.html}</div>`;
    if (v.layout === 'admin') {
      const nav = [
        ['dashboard', '<svg class=ic><use href=#i-chart></use></svg>', 'Tableau de bord'], ['menus', '<svg class=ic><use href=#i-calendar></use></svg>', 'Menus'], ['orders', '<svg class=ic><use href=#i-scan></use></svg>', 'Commandes du jour'],
        ['stocks', '<svg class=ic><use href=#i-box></use></svg>', 'Stocks'], ['dishes', '<svg class=ic><use href=#i-utensils></use></svg>', 'Plats'], ['users', '<svg class=ic><use href=#i-users></use></svg>', 'Utilisateurs'], ['reports', '<svg class=ic><use href=#i-trending></use></svg>', 'Rapports']
      ];
      nav.splice(5, 0, ['reviews', '<svg class=ic><use href=#i-message></use></svg>', 'Avis']);
      nav.splice(1, 0, ['notifs', '<svg class=ic><use href=#i-bell></use></svg>', 'Alertes']);
      const low = D.ingredients.filter(i => i.stock < i.threshold).length;
      const flagged = D.reviews.filter(r => r.status === 'pending').length;
      const u = me();
      const sup = [['admins', '<svg class=ic><use href=#i-shield></use></svg>', 'Administrateurs'], ['accounts', '<svg class=ic><use href=#i-trash></use></svg>', 'Comptes et corbeille'], ['requests', '<svg class=ic><use href=#i-mail></use></svg>', 'Demandes de suppression'], ['payments', '<svg class=ic><use href=#i-key></use></svg>', 'Moyens de paiement'], ['audit', '<svg class=ic><use href=#i-file></use></svg>', 'Journal d’audit']];
      const pending = D.deletionRequests.filter(r => r.status === 'pending').length;
      const unreadAdm = D.notifications.filter(n => n.userId === u.id && !n.read).length;
      const count = (k) => (k === 'stocks' && low ? low : k === 'reviews' && flagged ? flagged : k === 'requests' && pending ? pending : k === 'notifs' ? unreadAdm : 0);
      const link = (area) => ([k, ic, l]) => `<a href="#/${area}/${k}" class="${v.nav === k ? 'active' : ''}"><span>${ic}</span>${l}${count(k) ? `<span class="count">${count(k)}</span>` : ''}</a>`;
      return `<div class="admin"><aside class="sidebar">${brand(u.role === 'superadmin' ? 'Super administration' : 'Administration')}<nav>${nav.map(link('a')).join('')}
        ${u.role === 'superadmin' ? `<div class="nav-sep">Super admin</div>${sup.map(link('s')).join('')}` : ''}</nav>
        <div class="me">${avatar(u, '', 'width:34px;height:34px;font-size:13px')}<div class="grow"><b>${esc(fullName(u))}</b><div class="xs" style="color:#aaa">${esc(u.title)}</div></div><button class="btn sm ghost" style="color:#fff;border-color:#444" onclick="App.A.logout()" title="Se déconnecter"><svg class=ic><use href=#i-logout></use></svg></button></div></aside>
        <main class="main">${v.html}</main></div>`;
    }
    // mobile
    const role = D.session.role;
    const u = me();
    const tabs = role === 'convive'
      ? [['home', '#/u/home', '<svg class=ic><use href=#i-home></use></svg>', 'Accueil'], ['menu', '#/u/menu', '<svg class=ic><use href=#i-calendar></use></svg>', 'Menu'], ['resa', '#/u/resa', '<svg class=ic><use href=#i-ticket></use></svg>', 'Réservations'], ['wallet', '#/u/wallet', '<svg class=ic><use href=#i-card></use></svg>', 'Portefeuille'], ['profile', '#/profile', '<svg class=ic><use href=#i-user></use></svg>', 'Profil']]
      : [['home', '#/p/home', '<svg class=ic><use href=#i-home></use></svg>', 'Accueil'], ['child', '#/p/child/' + (u.children[0] || 'none'), '<svg class=ic><use href=#i-users></use></svg>', 'Enfants'], ['topup', '#/p/topup/' + (u.children[0] || 'none'), '<svg class=ic><use href=#i-card></use></svg>', 'Recharger'], ['alerts', '#/p/alerts', '<svg class=ic><use href=#i-bell></use></svg>', 'Alertes'], ['profile', '#/profile', '<svg class=ic><use href=#i-user></use></svg>', 'Profil']];
    return `<div class="mobile-stage"><div class="phone">
      <div class="phone-top">${v.back ? `<button class="back" onclick="${v.back === 'history' ? 'history.back()' : `App.go('${v.back}')`}" aria-label="Retour">${App.ic('chevron-left')}</button>` : ''}<h2>${v.title}</h2>${v.right || ''}</div>
      <div class="phone-body">${v.html}</div>
      ${v.noTabs ? '' : `<nav class="tabbar">${tabs.map(([k, h, ic, l]) => `<a href="${h}" class="${v.tab === k ? 'active' : ''}"><span class="ti">${ic}${k === 'profile' && missingFields(u).length ? '<i class="tab-dot" title="Profil incomplet"></i>' : ''}</span>${l}</a>`).join('')}</nav>`}
    </div></div>`;
  }
  const bell = (count, href) => `<button class="icon-btn" onclick="App.go('${href}')" aria-label="Notifications"><svg class=ic><use href=#i-bell></use></svg>${count ? `<span class="dot">${count}</span>` : ''}</button>`;

  /* ---------------- Barre de démo ---------------- */
  function renderDemoBar() {
    const r = D.session ? D.session.userId : null;
    const b = (id, label) => `<button class="${r === id ? 'active' : ''}" onclick="App.A.demoLogin('${id}')">${label}</button>`;
    $('#demo-bar').innerHTML = `<strong>DÉMO</strong>
      <button class="${!r ? 'active' : ''}" onclick="App.A.logout()">Visiteur</button>
      ${b('u1', '<svg class=ic><use href=#i-graduation></use></svg> Élève')}${b('u4', '<svg class=ic><use href=#i-briefcase></use></svg> Personnel')}${b('p1', '<svg class=ic><use href=#i-users></use></svg> Parent')}${b('a1', '<svg class=ic><use href=#i-briefcase></use></svg> Admin')}${b('s1', '<svg class=ic><use href=#i-shield></use></svg> Super admin')}${b('k1', '<svg class=ic><use href=#i-scan></use></svg> Comptoir')}
      <span class="spacer"></span>
      <span class="clock">${fmtShort(D.NOW.date)} ·</span>
      <select onchange="App.A.setTime(this.value)" style="background:#2b2b28;color:#fff;border:1px solid #444;border-radius:6px;padding:2px 6px">
        ${['09:40', '11:40', '12:10', '12:40', '13:05', '15:00'].map(t => `<option ${D.NOW.time === t ? 'selected' : ''}>${t}</option>`).join('')}
      </select>
      <button onclick="location.hash='#/';location.reload()" title="Remettre la démo à zéro"><svg class=ic><use href=#i-refresh></use></svg> Réinitialiser</button>`;
  }

  /* ---------------- Actions communes ---------------- */
  const A = {
    demoLogin(id) {
      const u = user(id);
      D.session = { userId: id, role: u.role };
      if (u.role === 'comptoir' && D.NOW.time < '11:30') { D.NOW.time = '12:10'; toast('Heure de démo passée à 12:10 (service du midi)'); }
      UI.kResult = null;
      go(homeFor());
    },
    logout() { D.session = null; UI.book = null; go('#/'); },
    cropDraw() {
      const C = App._crop, cv = document.getElementById('crop-cv'); if (!cv) return;
      const g = cropGeometry(C, 300), ctx = cv.getContext('2d');
      ctx.fillStyle = '#111'; ctx.fillRect(0, 0, 300, 300); ctx.drawImage(C.img, g.x, g.y, g.w, g.h);
    },
    cropShape(sh) { App._crop.shape = sh; document.getElementById('crop-mask').className = 'crop-mask ' + sh; document.getElementById('sh-circle').classList.toggle('active', sh === 'circle'); document.getElementById('sh-square').classList.toggle('active', sh === 'square'); },
    cropSave() {
      const C = App._crop, size = C.opts.size || 512, cv = document.createElement('canvas');
      cv.width = size; cv.height = size;
      const g = cropGeometry(C, size), ctx = cv.getContext('2d');
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size); ctx.drawImage(C.img, g.x, g.y, g.w, g.h);
      // Compression automatique : WebP 82 % (repli JPEG si non pris en charge)
      let data = cv.toDataURL('image/webp', 0.82);
      if (!data.startsWith('data:image/webp')) data = cv.toDataURL('image/jpeg', 0.82);
      const bytes = Math.round((data.length - data.indexOf(',') - 1) * 0.75);
      closeModal();
      // Libellé honnête : on n'affiche « avant → après » que si l'image a réellement été allégée
      const label = bytes < C.file.size ? fmtSize(C.file.size) + ' → ' + fmtSize(bytes) : fmtSize(bytes);
      C.opts.onDone(data, { shape: C.shape, before: fmtSize(C.file.size), after: fmtSize(bytes), label: label + ' · ' + size + '×' + size + ' px' });
    },
    sudoOk() {
      const pw = document.getElementById('sudo-pw').value;
      if (pw.length < 6) { document.getElementById('sudo-err').textContent = 'Mot de passe incorrect.'; return; }
      D.sudoUntil = Date.now() + 5 * 60 * 1000; closeModal(); toast('Identité confirmée pour 5 minutes', 'green');
      const cb = App._sudoCb; App._sudoCb = null; if (cb) cb();
    },
    setTime(t) { D.NOW.time = t; toast('Heure de démo : ' + t + (t < D.cutoff ? ' — réservations du jour ouvertes' : ' — réservations du jour closes')); render(); }
  };

  window.App = {
    D, UI, A, routes, $, esc, num, fcfa, dish, user, me, fullName, initials, fmtLong, fmtShort, fmtDM, dayOf, allDays,
    isToday, bookingOpen, conflicts, activeRes, dayRes, reservedCount, remaining, slotCount, slotLabel, dishesOfDay,
    statusBadge, stars, allergenBadges, resItemsText, mainDish, groupItems, displayName, isActive, avatar, dishImg, typeLabel, missingFields,
    findForbidden, scanReview, roleLabel, notifyAdmins, allergyConflicts, alertAllergy, providerName, userPayMethods, audit, requireSudo, validateImage, openCropper, pickImage, fmtSize, currentSlot, newId, nowStamp, addTx, notify, checkLowBalance,
    qrSvg, barChart, hbars, modal, closeModal, confirmModal, toast, simulatePayment, downloadCSV,
    go, render, homeFor, bell, brand,
    route(pat, fn) { routes.push([pat, fn]); },
    start() { window.addEventListener('hashchange', render); render(); }
  };
})();
