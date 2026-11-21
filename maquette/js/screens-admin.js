/* =========================================================
   Écrans Administrateur (A1–A7) et Comptoir (K1)
   ========================================================= */
(function () {
  const { D, UI, A, esc, num, fcfa, dish, user, me, fullName, initials, fmtLong, fmtShort, fmtDM, dayOf,
    activeRes, dayRes, reservedCount, slotCount, slotLabel, dishesOfDay, statusBadge, stars, resItemsText, mainDish,
    currentSlot, addTx, notify, barChart, hbars, modal, closeModal, confirmModal, toast, downloadCSV, go, render, route } = App;
  const CATS = D.CATS, ALL = D.ALLERGENS;
  const C = { sky: '#87c1e9', ink: '#1d1d1b', green: '#4caf82', orange: '#f0a35e', red: '#e06666', gray: '#c9ced6' };
  const head = (title, sub, actions) => `<div class="page-head"><div><h1>${title}</h1>${sub ? `<p>${sub}</p>` : ''}</div><div class="row wrap">${actions || ''}</div></div>`;
  const stockStatus = (i) => i.stock <= 0 ? '<span class="badge red">Rupture</span>' : i.stock < i.threshold ? '<span class="badge orange">Bas</span>' : '<span class="badge green">OK</span>';
  // Besoin de la semaine en cours pour un ingrédient (portions prévues × recette)
  const weekNeed = (ingId) => {
    let need = 0;
    D.week.filter(d => d.date >= D.NOW.date).forEach(day => dishesOfDay(day).forEach(id => {
      const r = dish(id).recipe.find(x => x[0] === ingId);
      if (r) need += r[1] * ((D.portions[day.date] && D.portions[day.date][id]) || 0);
    }));
    return Math.round(need);
  };
  const dayStats = (date) => { const rs = dayRes(date); return { reserved: rs.length, served: rs.filter(r => r.status === 'servie').length, missed: rs.filter(r => r.status === 'non-retiree').length, revenue: rs.reduce((s, r) => s + r.total, 0) }; };
  const activeDishes = () => D.dishes.filter(d => !d.archived);
  const classStats = () => {
    const m = {};
    D.reservations.filter(r => r.status !== 'annulee').forEach(r => {
      const u = user(r.userId); if (!u || u.type !== 'eleve' || !u.group) return;
      const c = m[u.group] = m[u.group] || { cls: u.group, reserved: 0, served: 0, missed: 0, students: new Set() };
      c.reserved++; c.students.add(u.id); if (r.status === 'servie') c.served++; if (r.status === 'non-retiree') c.missed++;
    });
    return Object.values(m).sort((a, b) => b.reserved - a.reserved);
  };

  /* ================= A0 — Alertes de l'administration (allergies à risque…) ================= */
  route('/a/notifs', () => {
    const u = me(), list = D.notifications.filter(n => n.userId === u.id);
    return { layout: 'admin', nav: 'notifs', title: 'Alertes', html: `
      ${head('Alertes', 'Réservations à risque et événements à traiter. Chaque administrateur reçoit l’alerte.', `<button class="btn ghost sm" onclick="App.A.admReadAll()">Tout marquer comme lu</button>`)}
      ${list.length ? `<div class="stack">${list.map(n => { const r = n.resId && D.reservations.find(x => x.id === n.resId); const done = r && (r.status === 'annulee' || r.allergyChecked); return `<div class="review-card ${n.kind === 'allergy' && !done ? 'flag' : ''} ${n.read ? 'hidden-r' : ''}">
        <div class="row between wrap"><div class="row"><span class="notif-ic">${App.notifIcon(n.icon)}</span><div><b>${esc(n.title)}</b>${n.read ? '' : ' <span class="badge red">Nouveau</span>'}<div class="xs muted">${n.date}</div></div></div>
        ${r ? (r.status === 'annulee' ? '<span class="badge gray">Réservation annulée</span>' : r.allergyChecked ? `<span class="badge green"><svg class=ic><use href=#i-check></use></svg> Vérifié par ${esc(r.allergyChecked)}</span>` : statusBadge(r.status)) : ''}</div>
        <p class="small mt">${esc(n.text)}</p>
        ${r ? `<div class="row mt wrap"><button class="btn sm light" onclick="App.A.openRes('${r.id}','${n.id}')">Voir la commande</button>${!done ? `<button class="btn sm" onclick="App.A.allergyCheck('${r.id}','${n.id}')">Marquer comme vérifié</button><button class="btn sm ghost" onclick="App.A.allergyContact('${r.id}')">Contacter ${r && user(r.userId).parentId ? 'le parent' : 'le convive'}</button>` : ''}</div>` : ''}</div>`; }).join('')}</div>`
        : '<div class="empty"><div class="ico"><svg class=ic><use href=#i-bell></use></svg></div>Aucune alerte.</div>'}` };
  });
  A.admReadAll = () => { D.notifications.filter(n => n.userId === me().id).forEach(n => { n.read = true; }); render(); };
  A.openRes = (rid, nid) => { const r = D.reservations.find(x => x.id === rid); if (nid) D.notifications.find(n => n.id === nid).read = true; Object.assign(UI, { ordersDate: r.date, ordersSearch: r.code, ordersSlot: 'all', ordersStatus: 'all', ordersView: 'day' }); go('#/a/orders'); };
  A.allergyCheck = (rid, nid) => {
    const r = D.reservations.find(x => x.id === rid);
    r.allergyChecked = me().first + ' ' + me().last[0] + '.';
    D.notifications.filter(n => n.resId === rid).forEach(n => { n.read = true; });
    App.audit('Allergie vérifiée', App.displayName(user(r.userId)), resItemsText(r));
    toast('Réservation marquée comme vérifiée', 'green'); render();
  };
  A.allergyContact = (rid) => {
    const r = D.reservations.find(x => x.id === rid), u = user(r.userId), target = u.parentId ? user(u.parentId) : u;
    const what = r.allergyConflict.map(c => dish(c.dishId).name + ' (' + c.allergens.map(a => ALL[a].toLowerCase()).join(', ') + ')').join(', ');
    modal(`<h2>Contacter ${esc(fullName(target))}</h2><p class="small muted mb">${esc(target.phone || 'Pas de téléphone')} · ${esc(target.email || '')}</p>
      <div class="field"><label>Message (notification + SMS)</label><textarea class="input" id="al-msg" rows="4">Bonjour, la réservation de ${esc(u.first)} du ${fmtShort(r.date)} contient ${esc(what)}, allergène déclaré dans son profil. Souhaitez-vous remplacer ce plat ? — La cantine</textarea></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.allergyContactOk('${rid}','${target.id}')">Envoyer</button></div>`);
  };
  A.allergyContactOk = (rid, tid) => { notify(tid, '<svg class=ic><use href=#i-alert></use></svg>', 'Message de la cantine', document.getElementById('al-msg').value, tid.startsWith('p') ? '#/p/home' : '#/u/resa/' + rid); closeModal(); toast('Message envoyé', 'green'); };

  /* ================= A1 — Tableau de bord ================= */
  route('/a/dashboard', () => {
    const t = dayStats(D.NOW.date), mon = dayStats('2026-11-23');
    const avg = activeDishes().reduce((s, d) => s + d.rating, 0) / activeDishes().length;
    const series = D.history.map(h => ({ label: h.date, reserved: h.reserved, served: h.served })).concat([
      { label: '23/11', reserved: mon.reserved, served: mon.served },
      { label: '24/11', reserved: t.reserved, served: t.served }
    ]);
    const period = series.slice(-UI.dashPeriod);
    const done = period.filter(p => p.label !== fmtDM(D.NOW.date));
    const rate = Math.round((mon.served / mon.reserved) * 100);
    const top = activeDishes().filter(d => d.cat === 'plat').sort((a, b) => b.rating - a.rating);
    const low = D.ingredients.filter(i => i.stock < i.threshold);
    const tomorrow = dayStats('2026-11-25');
    return { layout: 'admin', nav: 'dashboard', title: 'Tableau de bord', html: `
      ${head('Tableau de bord', fmtLong(D.NOW.date) + ' · ' + D.NOW.time, `<div class="chips">${[[7, '7 jours'], [22, '30 jours']].map(([v, l]) => `<button class="chip ${UI.dashPeriod === v ? 'active' : ''}" onclick="App.UI.dashPeriod=${v};App.render()">${l}</button>`).join('')}</div>`)}
      ${(() => { const risk = D.reservations.filter(r => r.date >= D.NOW.date && r.status === 'payee' && r.allergyConflict && r.allergyConflict.length && !r.allergyChecked); return risk.length ? `<div class="alert red mb" style="cursor:pointer" onclick="App.go('#/a/notifs')"><svg class=ic><use href=#i-alert></use></svg> <span class="grow"><b>${risk.length} réservation(s) à risque allergique</b> à vérifier : ${risk.slice(0, 3).map(r => esc(App.displayName(user(r.userId))) + ' — ' + esc(dish(r.allergyConflict[0].dishId).name)).join(' · ')}</span><b>Voir ›</b></div>` : ''; })()}
      <div class="kpis">
        <div class="kpi" onclick="App.go('#/a/orders')"><div class="label">Repas réservés aujourd’hui</div><div class="value">${t.reserved}</div><div class="delta muted">${t.served} servis · ${t.reserved - t.served} à servir</div></div>
        <div class="kpi" onclick="App.go('#/a/orders')"><div class="label">Recettes du jour</div><div class="value">${num(t.revenue)}<small style="font-size:14px"> FCFA</small></div><div class="delta muted">Panier moyen ${fcfa(Math.round(t.revenue / Math.max(1, t.reserved)))}</div></div>
        <div class="kpi" onclick="App.UI.reportType='waste';App.go('#/a/reports')"><div class="label">Taux de retrait (hier)</div><div class="value">${rate} %</div><div class="delta" style="color:var(--orange)">${mon.missed} repas non retirés</div></div>
        <div class="kpi" onclick="App.go('#/a/dishes')"><div class="label">Note moyenne des plats</div><div class="value">${avg.toFixed(1).replace('.', ',')}<small style="font-size:14px"> / 5</small></div><div class="delta muted">${activeDishes().reduce((s, d) => s + d.ratingCount, 0)} avis</div></div>
      </div>
      <div class="grid-2">
        <div class="card"><div class="row between"><h3>Réservations par jour</h3><span class="xs muted">Jours de service</span></div>${barChart({ labels: period.map(p => p.label), series: [{ name: 'Réservés', color: C.sky, values: period.map(p => p.reserved) }, { name: 'Servis', color: C.ink, values: period.map(p => p.served) }] })}</div>
        <div class="card"><h3>Affluence par créneau — aujourd’hui</h3>${hbars(D.slots.map(s => ({ label: s.label, value: slotCount(D.NOW.date, s.id), color: slotCount(D.NOW.date, s.id) / s.capacity > 0.85 ? C.orange : C.sky })), Math.max(...D.slots.map(s => s.capacity)))}
          <p class="xs muted mt">Capacité : ${D.slots.map(s => s.label + ' ' + s.capacity).join(' · ')}</p>
          <div class="divider"></div><h3>À préparer demain</h3><p class="small"><b>${tomorrow.reserved}</b> repas déjà réservés pour mercredi (clôture ${D.cutoff.replace(':', 'h')}).</p></div>
      </div>
      <div class="grid-2-eq mt">
        <div class="card"><div class="row between"><h3>Réservations par classe</h3><a class="small" href="#/a/reports" onclick="App.UI.reportType='classes'">Détail ›</a></div>${(() => { const cs = classStats().slice(0, 8); return hbars(cs.map(c => ({ label: c.cls, value: c.reserved })), Math.max(1, ...cs.map(c => c.reserved))); })()}
          <p class="xs muted mt">Uniquement les élèves ayant renseigné leur classe (${D.users.filter(u => u.type === 'eleve' && u.group).length} sur ${D.users.filter(u => u.type === 'eleve').length}).</p></div>
        <div class="card"><div class="row between"><h3>Prévision : réservations par plat et par jour</h3><a class="small" href="#/a/orders" onclick="App.UI.ordersView='week'">Semaine ›</a></div>${(() => { const ids = [...new Set(D.week.flatMap(d => d.plat))]; return `<table class="tbl"><thead><tr><th>Plat</th>${D.week.map(d => `<th class="num">${d.label}</th>`).join('')}</tr></thead><tbody>${ids.map(id => `<tr><td class="small">${esc(dish(id).name)}</td>${D.week.map(d => `<td class="num">${d.plat.includes(id) ? reservedCount(d.date, id) : '<span class="muted">·</span>'}</td>`).join('')}</tr>`).join('')}</tbody></table>`; })()}</div>
      </div>
      <div class="grid-3 mt">
        <div class="card"><h3>Gaspillage : réservé vs non retiré</h3>${barChart({ labels: done.map(p => p.label), series: [{ name: 'Servis', color: C.ink, values: done.map(p => p.served) }, { name: 'Non retirés', color: C.orange, values: done.map(p => p.reserved - p.served) }], stacked: true, height: 220, width: 360 })}</div>
        <div class="card"><h3>Plats les mieux notés</h3>${top.slice(0, 5).map((d, i) => `<div class="row mb"><b class="muted" style="width:16px">${i + 1}</b>${App.dishImg(d, 'sm')}<span class="grow small">${esc(d.name)}</span>${stars(d.rating)}</div>`).join('')}
          <div class="divider"></div><p class="xs muted mb">À surveiller</p>${top.slice(-2).map(d => `<div class="row mb small"><span class="grow">${esc(d.name)}</span>${stars(d.rating)}</div>`).join('')}</div>
        <div class="card"><div class="row between"><h3>Alertes stock</h3><a class="small" href="#/a/stocks">Voir les stocks ›</a></div>${low.length ? low.map(i => `<div class="row between mb small"><span>${esc(i.name)}</span><span>${num(i.stock)} ${i.unit} ${stockStatus(i)}</span></div>`).join('') : '<p class="small muted">Aucune alerte.</p>'}
          <button class="btn sm light block mt" onclick="App.A.purchaseOrder()">Générer le bon de commande</button></div>
      </div>` };
  });

  /* ================= A2 — Planification des menus ================= */
  const planWeek = () => (UI.planWeek === 'current' ? D.week : D.nextWeek);
  route('/a/menus', () => {
    const wk = planWeek(), published = !!D.menuPublished[wk[0].date];
    const cats = ['entree', 'plat', 'dessert', 'boisson'];
    const q = (UI.catSearch || '').toLowerCase();
    return { layout: 'admin', nav: 'menus', title: 'Menus', html: `
      ${head('Planification des menus', 'Glissez un plat du catalogue dans une case, ou utilisez « + Ajouter ».', `
        <div class="chips"><button class="chip ${UI.planWeek === 'current' ? 'active' : ''}" onclick="App.UI.planWeek='current';App.render()">Semaine du 23 nov.</button><button class="chip ${UI.planWeek === 'next' ? 'active' : ''}" onclick="App.UI.planWeek='next';App.render()">Semaine du 30 nov.</button></div>`)}
      <div class="toolbar">
        ${published ? '<span class="badge green">● Publiée</span>' : '<span class="badge orange">● Brouillon</span>'}
        <span class="small muted">Clôture des réservations</span><input class="input" type="time" value="${D.cutoff}" style="min-width:0;width:120px" onchange="App.D.cutoff=this.value;App.toast('Heure limite : '+this.value)">
        <span class="grow"></span>
        ${UI.planWeek === 'next' ? '<button class="btn ghost sm" onclick="App.A.duplicateWeek()">⧉ Dupliquer la semaine précédente</button>' : ''}
        <button class="btn sm" ${published ? 'disabled' : ''} onclick="App.A.publishWeek()">${published ? 'Menu publié' : 'Publier le menu'}</button>
      </div>
      <div class="planner">
        <div class="plan-grid">
          <div class="ph"></div>${wk.map(d => `<div class="ph">${d.label}<small>${fmtDM(d.date)}</small></div>`).join('')}
          ${cats.map(c => `<div class="rh">${CATS[c]}</div>${wk.map(d => `<div class="plan-cell" ondragover="event.preventDefault();this.classList.add('drop')" ondragleave="this.classList.remove('drop')" ondrop="App.A.dropDish(event,'${d.date}','${c}')">
            <div class="stack" style="--g:6px">${d[c].map(id => { const di = dish(id), n = reservedCount(d.date, id); return `<div class="plan-item"><button class="rm" title="Retirer" onclick="App.A.removeFromMenu('${d.date}','${c}','${id}')">×</button><b>${esc(di.name)}</b><div class="q">${(D.portions[d.date] || {})[id] || 0} portions${n ? ' · ' + n + ' rés.' : ''}</div></div>`; }).join('')}</div>
            <button class="plan-add mt" onclick="App.A.addToMenu('${d.date}','${c}')">+ Ajouter</button></div>`).join('')}`).join('')}
        </div>
        <aside class="catalog"><div class="row between mb"><h3 style="margin:0">Catalogue</h3><a class="small" href="#/a/dishes/new">+ Nouveau</a></div>
          <input class="input mb" placeholder="Rechercher un plat" value="${esc(UI.catSearch || '')}" oninput="App.UI.catSearch=this.value;clearTimeout(window._cs);window._cs=setTimeout(()=>{App.render();const i=document.querySelector('.catalog input');i.focus();i.setSelectionRange(i.value.length,i.value.length)},250)">
          ${cats.map(c => { const l = activeDishes().filter(d => d.cat === c && d.name.toLowerCase().includes(q)); return l.length ? `<div class="cat-label">${CATS[c]}s</div>${l.map(d => `<div class="cat-item" draggable="true" ondragstart="event.dataTransfer.setData('text/plain','${d.id}')">${App.dishImg(d, 'sm')}<div class="grow small"><b>${esc(d.name)}</b><div class="xs muted">${fcfa(d.price)} · <svg class=ic-star><use href=#i-star></use></svg> ${d.rating.toFixed(1)}</div></div><span class="muted">⋮⋮</span></div>`).join('')}` : ''; }).join('')}
        </aside>
      </div>` };
  });
  const placeDish = (date, cat, id, portions) => {
    const day = dayOf(date), d = dish(id);
    if (d.cat !== cat) return toast(`« ${d.name} » est un(e) ${CATS[d.cat].toLowerCase()} : déposez-le dans la ligne ${CATS[d.cat]}.`, 'red');
    if (day[cat].includes(id)) return toast('Ce plat est déjà au menu ce jour-là.');
    day[cat].push(id);
    D.portions[date] = D.portions[date] || {};
    D.portions[date][id] = portions || (cat === 'plat' ? 80 : 60);
    if (D.menuPublished[planWeek()[0].date]) notify('u1', '<svg class=ic><use href=#i-calendar></use></svg>', 'Menu mis à jour', `${d.name} ajouté le ${fmtShort(date)}.`, '#/u/menu');
    toast(`${d.name} ajouté — ${fmtShort(date)}`, 'green'); render();
  };
  A.dropDish = (e, date, cat) => { e.preventDefault(); placeDish(date, cat, e.dataTransfer.getData('text/plain')); };
  A.addToMenu = (date, cat) => {
    const list = activeDishes().filter(d => d.cat === cat && !dayOf(date)[cat].includes(d.id));
    modal(`<h2>Ajouter — ${CATS[cat]}, ${fmtLong(date).toLowerCase()}</h2>
      <div class="field"><label>Plat</label><select class="input" id="am-dish">${list.map(d => `<option value="${d.id}">${d.name} — ${fcfa(d.price)}</option>`).join('')}</select></div>
      <div class="field"><label>Portions prévues</label><input class="input" id="am-qty" type="number" value="${cat === 'plat' ? 80 : 60}" min="1"></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.addToMenuOk('${date}','${cat}')">Ajouter</button></div>`, { noFocus: true });
  };
  A.addToMenuOk = (date, cat) => { const id = document.getElementById('am-dish').value, q = +document.getElementById('am-qty').value; closeModal(); if (id) placeDish(date, cat, id, q); };
  A.removeFromMenu = (date, cat, id) => {
    const n = reservedCount(date, id);
    const doIt = () => {
      const day = dayOf(date); day[cat] = day[cat].filter(x => x !== id);
      if (n) dayRes(date).filter(r => r.items.includes(id)).forEach(r => { r.status = 'annulee'; user(r.userId).balance += r.total; addTx(r.userId, 'remboursement', 'Plat retiré du menu — ' + dish(id).name, r.total); notify(r.userId, '<svg class=ic><use href=#i-undo></use></svg>', 'Réservation annulée', `${dish(id).name} a été retiré du menu du ${fmtShort(date)}. ${fcfa(r.total)} remboursés.`, '#/u/menu'); });
      toast(dish(id).name + ' retiré du menu' + (n ? ` — ${n} convives remboursés` : '')); render();
    };
    if (n) confirmModal('Retirer ce plat ?', `<b>${n} réservation(s)</b> concernent ce plat. Elles seront annulées, remboursées et les convives notifiés.`, 'Retirer et rembourser', doIt, true);
    else doIt();
  };
  A.duplicateWeek = () => {
    D.nextWeek.forEach((d, i) => { ['entree', 'plat', 'dessert', 'boisson'].forEach(c => { d[c] = D.week[i][c].slice(); }); D.portions[d.date] = Object.assign({}, D.portions[D.week[i].date]); });
    toast('Menu de la semaine du 23 nov. copié', 'green'); render();
  };
  A.publishWeek = () => {
    const wk = planWeek();
    const empty = wk.filter(d => !d.plat.length);
    const doIt = () => {
      D.menuPublished[wk[0].date] = true;
      const n = D.users.filter(u => u.role === 'convive').length;
      D.users.filter(u => u.role === 'convive' && !u.id.startsWith('x')).forEach(u => notify(u.id, '<svg class=ic><use href=#i-calendar></use></svg>', 'Nouveau menu publié', `Le menu de la semaine du ${fmtDM(wk[0].date)} est en ligne.`, '#/u/menu'));
      toast(`Menu publié — ${n} convives notifiés`, 'green'); render();
    };
    if (empty.length) confirmModal('Publier quand même ?', `${empty.length} jour(s) n’ont aucun plat : ${empty.map(d => d.label).join(', ')}.`, 'Publier', doIt);
    else doIt();
  };

  /* ================= A3 — Commandes du jour ================= */
  route('/a/orders', () => {
    const date = UI.ordersDate, day = dayOf(date);
    const all = D.reservations.filter(r => r.date === date && r.status !== 'annulee');
    const q = UI.ordersSearch.toLowerCase();
    const list = all.filter(r => (UI.ordersSlot === 'all' || r.slot === UI.ordersSlot) && (UI.ordersStatus === 'all' || (UI.ordersStatus === 'risk' ? r.allergyConflict && r.allergyConflict.length : r.status === UI.ordersStatus)) && (!q || fullName(user(r.userId)).toLowerCase().includes(q) || r.code.includes(q)))
      .sort((a, b) => a.slot.localeCompare(b.slot));
    const dishIds = dishesOfDay(day);
    const st = dayStats(date);
    return { layout: 'admin', nav: 'orders', title: 'Commandes', html: `
      ${head('Commandes du jour', fmtLong(date) + ` · ${st.reserved} repas réservés`, `
        <div class="chips">${D.week.slice(0, 3).map(d => `<button class="chip ${date === d.date ? 'active' : ''}" onclick="App.UI.ordersDate='${d.date}';App.UI.ordersLimit=40;App.render()">${d.label} ${fmtDM(d.date)}</button>`).join('')}</div>
        <button class="btn ghost sm" onclick="App.A.productionSheet()"><svg class=ic><use href=#i-printer></use></svg> Fiche de production</button>
        ${date === D.NOW.date ? '<button class="btn sm" onclick="App.A.closeDay()">Clôturer la journée</button>' : ''}`)}
      <div class="kpis">
        <div class="kpi"><div class="label">Réservés</div><div class="value">${st.reserved}</div></div>
        <div class="kpi"><div class="label">Servis</div><div class="value" style="color:var(--green)">${st.served}</div></div>
        <div class="kpi"><div class="label">Restant à servir</div><div class="value">${all.filter(r => r.status === 'payee').length}</div></div>
        <div class="kpi" onclick="App.UI.ordersStatus='risk';App.render()"><div class="label">Plat contenant un allergène déclaré</div><div class="value" style="color:var(--red)">${all.filter(r => r.allergyConflict && r.allergyConflict.length).length}</div><div class="delta muted">${all.filter(r => user(r.userId).allergies.length).length} convives avec allergie</div></div>
      </div>
      <div class="tabs" style="max-width:420px"><button class="${UI.ordersView !== 'week' ? 'active' : ''}" onclick="App.UI.ordersView='day';App.render()">Jour : par créneau</button><button class="${UI.ordersView === 'week' ? 'active' : ''}" onclick="App.UI.ordersView='week';App.render()">Semaine : par jour</button></div>
      ${UI.ordersView === 'week' ? (() => { const ids = [...new Set(D.week.flatMap(d => ['plat', 'entree', 'dessert', 'boisson'].flatMap(c => d[c])))]; return `<div class="card mb"><h3>Prévision de la semaine — réservations par plat et par jour</h3><div style="overflow:auto"><table class="tbl"><thead><tr><th>Plat</th>${D.week.map(d => `<th class="num">${d.label} ${fmtDM(d.date)}</th>`).join('')}<th class="num">Total</th></tr></thead><tbody>
        ${ids.map(id => { const per = D.week.map(d => (dishesOfDay(d).includes(id) ? reservedCount(d.date, id) : null)); return `<tr><td>${esc(dish(id).name)} <span class="xs muted">${CATS[dish(id).cat]}</span></td>${per.map((n, k) => n == null ? '<td class="num muted">—</td>' : `<td class="num"><b>${n}</b><span class="xs muted"> / ${(D.portions[D.week[k].date] || {})[id] || 0}</span></td>`).join('')}<td class="num"><b>${per.reduce((a, b) => a + (b || 0), 0)}</b></td></tr>`; }).join('')}
        </tbody></table></div><p class="xs muted mt">Réservés / portions prévues. Les jours à venir se remplissent jusqu’à l’heure limite (${D.cutoff.replace(':', 'h')}).</p></div>`; })() : ''}
      <div class="card mb" style="${UI.ordersView === 'week' ? 'display:none' : ''}"><h3>À préparer — quantités par plat et par créneau</h3><div style="overflow:auto"><table class="tbl"><thead><tr><th>Plat</th>${D.slots.map(s => `<th class="num">${s.label}</th>`).join('')}<th class="num">Total</th><th class="num">Prévu</th></tr></thead><tbody>
        ${['plat', 'entree', 'dessert', 'boisson'].flatMap(c => day[c]).map(id => { const per = D.slots.map(s => all.filter(r => r.slot === s.id).reduce((n, r) => n + r.items.filter(x => x === id).length, 0)); const tot = per.reduce((a, b) => a + b, 0); const planned = (D.portions[date] || {})[id] || 0; return `<tr><td>${esc(dish(id).name)} <span class="xs muted">${CATS[dish(id).cat]}</span></td>${per.map(n => `<td class="num">${n || '—'}</td>`).join('')}<td class="num"><b>${tot}</b></td><td class="num ${tot > planned ? '' : 'muted'}" style="${tot > planned ? 'color:var(--red);font-weight:700' : ''}">${planned}</td></tr>`; }).join('')}
      </tbody></table></div><p class="xs muted mt">${dishIds.length} articles au menu. Les quantités se mettent à jour à chaque réservation et à chaque service.</p></div>
      <div class="toolbar">
        <input class="input" placeholder="Rechercher un nom ou un code" value="${esc(UI.ordersSearch)}" onchange="App.UI.ordersSearch=this.value;App.render()">
        <select class="input" style="min-width:0" onchange="App.UI.ordersSlot=this.value;App.render()"><option value="all">Tous les créneaux</option>${D.slots.map(s => `<option value="${s.id}" ${UI.ordersSlot === s.id ? 'selected' : ''}>${s.label}</option>`).join('')}</select>
        <select class="input" style="min-width:0" onchange="App.UI.ordersStatus=this.value;App.render()">${[['all', 'Tous les statuts'], ['risk', '<svg class=ic><use href=#i-alert></use></svg> Allergène réservé'], ['payee', 'Payée'], ['servie', 'Servie'], ['non-retiree', 'Non retirée']].map(([v, l]) => `<option value="${v}" ${UI.ordersStatus === v ? 'selected' : ''}>${l}</option>`).join('')}</select>
        <span class="small muted">${list.length} commande(s)</span>
      </div>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Convive</th><th>Classe / service</th><th>Plateau</th><th>Créneau</th><th>Code</th><th>Statut</th><th></th></tr></thead><tbody>
        ${list.slice(0, UI.ordersLimit).map(r => { const u = user(r.userId); return `<tr><td><b>${esc(App.displayName(u))}</b>${u.allergies.length ? ` <span class="badge red" title="Allergie"><svg class=ic><use href=#i-alert></use></svg> ${u.allergies.map(a => ALL[a]).join(', ')}</span>` : ''}</td><td>${esc(u.type === 'personnel' ? 'Personnel' : (u.group || '—'))}</td><td class="small">${esc(resItemsText(r))}${r.allergyConflict && r.allergyConflict.length ? `<div><span class="badge red" title="Plat contenant un allergène déclaré"><svg class=ic><use href=#i-alert></use></svg> ${r.allergyConflict.map(c => esc(dish(c.dishId).name) + ' : ' + c.allergens.map(a => ALL[a]).join(', ')).join(' · ')}</span>${r.allergyChecked ? ' <span class="badge green"><svg class=ic><use href=#i-check></use></svg> vérifié</span>' : ''}</div>` : ''}</td><td>${slotLabel(r.slot)}</td><td class="muted">${r.code}</td><td>${statusBadge(r.status)}${r.servedAt ? ` <span class="xs muted">${r.servedAt}</span>` : ''}</td><td>${r.status === 'payee' && date === D.NOW.date ? `<button class="btn sm light" onclick="App.A.markServed('${r.id}')">Marquer servi</button>` : ''}</td></tr>`; }).join('')}
      </tbody></table>${list.length > UI.ordersLimit ? `<div class="center" style="padding:12px"><button class="btn ghost sm" onclick="App.UI.ordersLimit+=40;App.render()">Afficher plus (${list.length - UI.ordersLimit})</button></div>` : ''}</div>` };
  });
  A.markServed = (id) => { const r = D.reservations.find(x => x.id === id); serve(r); toast('Repas de ' + user(r.userId).first + ' marqué servi', 'green'); render(); };
  A.closeDay = () => {
    const left = dayRes(D.NOW.date).filter(r => r.status === 'payee');
    confirmModal('Clôturer la journée ?', `${left.length} repas n’ont pas été retirés. Ils seront marqués « Non retirés » (sans remboursement) et comptés dans le gaspillage.`, 'Clôturer', () => { left.forEach(r => { r.status = 'non-retiree'; }); toast('Journée clôturée', 'green'); render(); });
  };
  A.productionSheet = () => {
    const date = UI.ordersDate, day = dayOf(date), all = dayRes(date);
    const rows = ['plat', 'entree', 'dessert', 'boisson'].flatMap(c => day[c]).map(id => [dish(id).name, ...D.slots.map(s => all.filter(r => r.slot === s.id && r.items.includes(id)).length), all.filter(r => r.items.includes(id)).length]);
    const allergic = all.filter(r => user(r.userId).allergies.length);
    modal(`<h2>Fiche de production — ${fmtShort(date)}</h2><table class="tbl"><thead><tr><th>Plat</th>${D.slots.map(s => `<th class="num">${s.label}</th>`).join('')}<th class="num">Total</th></tr></thead><tbody>${rows.map(r => `<tr>${r.map((c, i) => `<td class="${i ? 'num' : ''}">${i === r.length - 1 ? '<b>' + c + '</b>' : esc(c)}</td>`).join('')}</tr>`).join('')}</tbody></table>
      <h3 class="mt">Plateaux avec allergie (${allergic.length})</h3><p class="small muted">${allergic.slice(0, 12).map(r => esc(fullName(user(r.userId))) + ' — ' + user(r.userId).allergies.map(a => ALL[a]).join(', ')).join('<br>') || 'Aucun'}</p>
      <div class="actions"><button class="btn ghost" onclick="App.downloadCSV('production-${date}.csv',[['Plat',${D.slots.map(s => `'${s.label}'`).join(',')},'Total']].concat(${JSON.stringify(rows).replace(/"/g, '&quot;')}))">Exporter CSV</button><button class="btn" onclick="window.print()">Imprimer / PDF</button></div>`, { width: 720 });
  };

  /* ================= A4 — Stocks ================= */
  route('/a/stocks', () => {
    const toOrder = D.ingredients.map(i => ({ i, need: weekNeed(i.id), qty: Math.max(weekNeed(i.id) - i.stock, i.threshold - i.stock, 0) })).filter(x => x.qty > 0);
    return { layout: 'admin', nav: 'stocks', title: 'Stocks', html: `
      ${head('Stocks', 'Besoins calculés à partir des menus planifiés et des recettes.', `<button class="btn ghost sm" onclick="App.A.stockMove('Entrée')"><svg class=ic><use href=#i-upload></use></svg> Entrée de stock</button><button class="btn ghost sm" onclick="App.A.stockMove('Perte')"><svg class=ic><use href=#i-x></use></svg> Déclarer une perte</button><button class="btn sm" onclick="App.A.newIngredient()">+ Ingrédient</button>`)}
      <div class="grid-2">
        <div class="table-wrap"><table class="tbl"><thead><tr><th>Ingrédient</th><th class="num">En stock</th><th class="num">Seuil</th><th class="num">Besoin semaine</th><th>Niveau</th><th>Statut</th></tr></thead><tbody>
          ${D.ingredients.map(i => { const need = weekNeed(i.id), pct = Math.min(100, (i.stock / Math.max(i.threshold * 2, 1)) * 100); return `<tr><td><b>${esc(i.name)}</b></td><td class="num">${num(i.stock)} ${i.unit}</td><td class="num muted">${i.threshold} ${i.unit}</td><td class="num">${need} ${i.unit}</td><td><span class="stock-bar"><span style="width:${pct}%;background:${i.stock <= 0 ? C.red : i.stock < i.threshold ? C.orange : C.green}"></span></span></td><td>${stockStatus(i)}</td></tr>`; }).join('')}
        </tbody></table></div>
        <div>
          <div class="card mb"><h3>À commander</h3>${toOrder.length ? toOrder.map(x => `<div class="row between small mb"><span>${esc(x.i.name)}</span><b>${num(Math.ceil(x.qty))} ${x.i.unit}</b></div>`).join('') : '<p class="small muted">Rien à commander.</p>'}<button class="btn block mt" onclick="App.A.purchaseOrder()">Générer le bon de commande</button></div>
          <div class="card"><h3>Derniers mouvements</h3>${D.stockMoves.slice(0, 8).map(m => `<div class="row between small mb"><span><b>${esc(m.ing)}</b><br><span class="xs muted">${m.date} · ${m.type} · ${esc(m.who)}</span></span><span style="color:${m.type === 'Entrée' ? 'var(--green)' : m.type === 'Perte' ? 'var(--red)' : 'inherit'}">${m.qty}</span></div>`).join('')}</div>
        </div>
      </div>` };
  });
  A.stockMove = (type) => {
    modal(`<h2>${type === 'Entrée' ? 'Entrée de stock' : 'Déclarer une perte'}</h2>
      <div class="field"><label>Ingrédient</label><select class="input" id="sm-ing">${D.ingredients.map(i => `<option value="${i.id}">${i.name} (${num(i.stock)} ${i.unit})</option>`).join('')}</select></div>
      <div class="field"><label>Quantité</label><input class="input" id="sm-qty" type="number" min="0" step="0.5" placeholder="Ex. 20"></div>
      ${type === 'Perte' ? '<div class="field"><label>Motif</label><select class="input"><option>Périmé</option><option>Abîmé</option><option>Erreur de préparation</option></select></div>' : ''}
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.stockMoveOk('${type}')">Enregistrer</button></div>`, { noFocus: true });
  };
  A.stockMoveOk = (type) => {
    const i = D.ingredients.find(x => x.id === document.getElementById('sm-ing').value), q = parseFloat(document.getElementById('sm-qty').value);
    if (!(q > 0)) return toast('Quantité invalide', 'red');
    i.stock = Math.max(0, i.stock + (type === 'Entrée' ? q : -q));
    D.stockMoves.unshift({ date: fmtDM(D.NOW.date), ing: i.name, type, qty: (type === 'Entrée' ? '+' : '−') + q + ' ' + i.unit, who: me().first[0] + '. ' + me().last });
    closeModal(); toast(`${type} enregistrée : ${i.name}`, 'green'); render();
  };
  A.newIngredient = () => {
    modal(`<h2>Nouvel ingrédient</h2><div class="field"><label>Nom</label><input class="input" id="ni-name"></div><div class="row"><div class="field grow"><label>Unité</label><select class="input" id="ni-unit"><option>kg</option><option>L</option><option>pièce</option></select></div><div class="field grow"><label>Stock initial</label><input class="input" id="ni-stock" type="number" value="0"></div><div class="field grow"><label>Seuil d’alerte</label><input class="input" id="ni-th" type="number" value="10"></div></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.newIngredientOk()">Ajouter</button></div>`);
  };
  A.newIngredientOk = () => {
    const name = document.getElementById('ni-name').value.trim(); if (!name) return toast('Nom requis', 'red');
    D.ingredients.push({ id: 'i' + Date.now(), name, unit: document.getElementById('ni-unit').value, stock: +document.getElementById('ni-stock').value, threshold: +document.getElementById('ni-th').value });
    closeModal(); toast(name + ' ajouté', 'green'); render();
  };
  A.purchaseOrder = () => {
    const rows = D.ingredients.map(i => ({ i, qty: Math.ceil(Math.max(weekNeed(i.id) - i.stock, i.threshold - i.stock, 0)) })).filter(x => x.qty > 0);
    modal(`<h2>Bon de commande</h2><p class="small muted mb">Établi le ${fmtLong(D.NOW.date).toLowerCase()} — Cantine de Vatel</p><table class="tbl"><thead><tr><th>Ingrédient</th><th class="num">Quantité</th></tr></thead><tbody>${rows.map(x => `<tr><td>${esc(x.i.name)}</td><td class="num"><b>${x.qty} ${x.i.unit}</b></td></tr>`).join('')}</tbody></table>
      <div class="actions"><button class="btn ghost" onclick="App.downloadCSV('bon-de-commande.csv',[['Ingrédient','Quantité','Unité']].concat(${JSON.stringify(rows.map(x => [x.i.name, x.qty, x.i.unit])).replace(/"/g, '&quot;')}))">Exporter CSV</button><button class="btn" onclick="window.print()">Imprimer / PDF</button></div>`, { width: 560 });
  };

  /* ================= A5 — Catalogue des plats ================= */
  route('/a/dishes', () => {
    const q = UI.dishSearch.toLowerCase();
    const list = activeDishes().filter(d => (UI.dishFilter === 'all' || d.cat === UI.dishFilter) && d.name.toLowerCase().includes(q));
    return { layout: 'admin', nav: 'dishes', title: 'Plats', html: `
      ${head('Catalogue des plats', activeDishes().length + ' plats actifs', '<a class="btn sm" href="#/a/dishes/new">+ Nouveau plat</a>')}
      <div class="toolbar"><input class="input" placeholder="Rechercher" value="${esc(UI.dishSearch)}" onchange="App.UI.dishSearch=this.value;App.render()">
        <div class="chips">${[['all', 'Tous']].concat(Object.entries(CATS)).map(([k, l]) => `<button class="chip ${UI.dishFilter === k ? 'active' : ''}" onclick="App.UI.dishFilter='${k}';App.render()">${l}${k !== 'all' ? 's' : ''}</button>`).join('')}</div></div>
      <div class="dish-grid">${list.map(d => `<div class="dish-card" onclick="App.go('#/a/dishes/${d.id}')">${App.dishImg(d, '')}<div class="info"><div class="row between"><b>${esc(d.name)}</b></div><div class="row between small mt"><span class="muted">${CATS[d.cat]}</span><b>${fcfa(d.price)}</b></div><div class="row between small mt">${stars(d.rating, d.ratingCount)}<span class="xs muted">${d.kcal} kcal</span></div>${d.allergens.length ? `<div class="chips mt">${d.allergens.map(a => `<span class="badge gray">${ALL[a]}</span>`).join('')}</div>` : ''}</div></div>`).join('')}</div>` };
  });
  const DEFAULT_ICON = { entree: '🥗', plat: '🍽️', dessert: '🍰', boisson: '🥤' };
  route('/a/dishes/:id', ({ id }) => {
    const isNew = id === 'new';
    if (!isNew && !dish(id)) return { redirect: '#/a/dishes' };
    if (!UI.dishDraft || UI.dishDraft._id !== id) {
      const src = isNew ? { name: '', cat: 'plat', price: 1000, emoji: DEFAULT_ICON.plat, photo: null, desc: '', allergens: [], diets: [], kcal: 500, recipe: [], rating: 0, ratingCount: 0, reviews: [] } : dish(id);
      UI.dishDraft = JSON.parse(JSON.stringify(src)); UI.dishDraft._id = id; UI.dishTab = 'fiche';
    }
    const d = UI.dishDraft;
    const sync = "App.A.dishSync()";
    const used = isNew ? 0 : App.allDays().filter(day => day[d.cat] && day[d.cat].includes(id)).length;
    return { layout: 'admin', nav: 'dishes', title: isNew ? 'Nouveau plat' : d.name, html: `
      ${head(isNew ? 'Nouveau plat' : esc(d.name), '<a href="#/a/dishes">‹ Catalogue</a>', `${isNew ? '' : `<button class="btn ghost sm" onclick="App.A.archiveDish('${id}')">Archiver</button><button class="btn danger sm" onclick="App.A.deleteDish('${id}')">Supprimer</button>`}<button class="btn sm" onclick="App.A.previewDish()">Aperçu et publication</button>`)}
      <div class="grid-2">
        <div class="card">
          <div class="row" style="align-items:flex-start"><div class="field grow"><label>Nom du plat *</label><input class="input" id="df-name" value="${esc(d.name)}" onchange="${sync}"></div>
            <div class="field"><label>Catégorie</label><select class="input" id="df-cat" onchange="${sync}">${Object.entries(CATS).map(([k, l]) => `<option value="${k}" ${d.cat === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div></div>
          <div class="row"><div class="field grow"><label>Prix (FCFA) *</label><input class="input" id="df-price" type="number" step="50" value="${d.price}" onchange="${sync}"></div><div class="field grow"><label>Calories (kcal)</label><input class="input" id="df-kcal" type="number" value="${d.kcal}" onchange="${sync}"></div></div>
          <div class="field"><label>Description</label><textarea class="input" id="df-desc" rows="3" onchange="${sync}">${esc(d.desc)}</textarea></div>
          <div class="field"><label>Allergènes</label><div class="check-grid">${Object.entries(ALL).map(([k, l]) => `<label class="check"><input type="checkbox" class="df-al" value="${k}" ${d.allergens.includes(k) ? 'checked' : ''} onchange="${sync}">${l}</label>`).join('')}</div></div>
          <div class="field"><label>Régimes compatibles</label><div class="check-grid">${Object.entries(D.DIETS).map(([k, l]) => `<label class="check"><input type="checkbox" class="df-di" value="${k}" ${d.diets.includes(k) ? 'checked' : ''} onchange="${sync}">${l}</label>`).join('')}</div></div>
          <div class="field"><label>Recette — ingrédients (quantité par portion)</label>
            ${d.recipe.map((r, i) => `<div class="row mb"><select class="input df-ri" onchange="${sync}">${D.ingredients.map(g => `<option value="${g.id}" ${r[0] === g.id ? 'selected' : ''}>${g.name}</option>`).join('')}</select><input class="input df-rq" type="number" step="0.01" value="${r[1]}" style="width:110px" onchange="${sync}"><span class="small muted" style="width:30px">${(D.ingredients.find(g => g.id === r[0]) || {}).unit || ''}</span><button class="btn sm ghost" onclick="App.A.dishSync();App.UI.dishDraft.recipe.splice(${i},1);App.render()"><svg class=ic><use href=#i-x></use></svg></button></div>`).join('')}
            <button class="btn sm light" onclick="App.A.dishSync();App.UI.dishDraft.recipe.push(['i1',0.1]);App.render()">+ Ajouter un ingrédient</button></div>
        </div>
        <div>
          <div class="card mb"><h3>Photo du plat</h3>
            <div class="upload-zone">${App.dishImg(d, '')}
              ${d.photo ? `<p class="xs">${d.photoInfo ? 'Optimisée : ' + d.photoInfo : 'Photo personnalisée'}</p><div class="row mt" style="justify-content:center"><button class="btn sm light" onclick="App.A.dishPhoto('gallery')">Remplacer</button><button class="btn sm danger" onclick="App.A.dishPhotoDelete()">Supprimer</button></div>`
                : `<p class="xs">Aucune photo : l’image par défaut de la catégorie est affichée.</p><div class="row mt" style="justify-content:center;flex-wrap:wrap"><button class="btn sm" onclick="App.A.dishPhoto('gallery')"><svg class=ic><use href=#i-upload></use></svg> Depuis l’ordinateur</button><button class="btn sm light" onclick="App.A.dishPhoto('camera')"><svg class=ic><use href=#i-camera></use></svg> Depuis le téléphone</button></div>`}
              <p class="xs muted mt">JPG, PNG ou WebP · 5 Mo maximum · recadrage puis compression automatique</p></div></div>
          ${isNew ? '' : `<div class="card"><h3>Utilisation</h3><p class="small">Présent dans <b>${used}</b> menu(s) planifié(s) · ${stars(d.rating, d.ratingCount + ' notes')}</p></div>`}
        </div>
      </div>` };
  });
  A.dishSync = () => {
    const d = UI.dishDraft, v = (id) => { const el = document.getElementById(id); return el ? el.value : null; };
    if (!document.getElementById('df-name')) return;
    const oldCat = d.cat;
    d.name = v('df-name').trim(); d.cat = v('df-cat'); d.price = +v('df-price'); d.kcal = +v('df-kcal'); d.desc = v('df-desc');
    if (d.cat !== oldCat && d.emoji === DEFAULT_ICON[oldCat]) d.emoji = DEFAULT_ICON[d.cat];
    d.allergens = [...document.querySelectorAll('.df-al:checked')].map(i => i.value);
    d.diets = [...document.querySelectorAll('.df-di:checked')].map(i => i.value);
    const ri = [...document.querySelectorAll('.df-ri')], rq = [...document.querySelectorAll('.df-rq')];
    d.recipe = ri.map((s, i) => [s.value, parseFloat(rq[i].value) || 0]);
    render();
  };
  A.dishPhoto = (source) => { A.dishSync(); App.pickImage(source, (f) => App.openCropper(f, { title: 'Recadrer la photo du plat', shape: 'square', size: 800, okLabel: 'Valider le recadrage', onDone: (data, info) => { UI.dishDraft.photo = data; UI.dishDraft.photoInfo = info.label; toast('Photo prête (' + info.label + '). Publiez pour l’enregistrer.', 'green'); render(); } })); };
  A.dishPhotoDelete = () => confirmModal('Supprimer la photo ?', 'L’image par défaut de la catégorie sera affichée.', 'Supprimer', () => { UI.dishDraft.photo = null; UI.dishDraft.photoInfo = null; render(); }, true);
  // Aperçu de la fiche telle que la verront les convives, avant publication
  A.previewDish = () => {
    if (document.getElementById('df-name')) A.dishSync();
    const d = UI.dishDraft;
    if (!d.name) return toast('Le nom du plat est requis', 'red');
    if (!(d.price > 0)) return toast('Prix invalide', 'red');
    const ing = d.recipe.map(r => (D.ingredients.find(g => g.id === r[0]) || {}).name).filter(Boolean);
    modal(`<h2>Aperçu de la fiche</h2><p class="xs muted mb">Voici ce que verront les convives.</p>
      <div style="max-width:360px;margin:0 auto;border:1px solid var(--line);border-radius:16px;overflow:hidden">${App.dishImg(d, 'lg')}<div style="padding:14px">
        <div class="row between"><h3>${esc(d.name)}</h3><b>${fcfa(d.price)}</b></div><p class="xs muted">${CATS[d.cat]} · ${d.kcal} kcal</p>
        <p class="small mt">${esc(d.desc) || '<span class="muted">Pas de description</span>'}</p>
        <p class="xs mt"><b>Ingrédients :</b> ${esc(ing.join(', ')) || '—'}</p>
        <div class="chips mt">${d.allergens.map(a => `<span class="badge gray">${ALL[a]}</span>`).join('') || '<span class="badge green">Aucun allergène majeur</span>'}${d.diets.map(x => `<span class="badge green">${D.DIETS[x]}</span>`).join('')}</div></div></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Continuer à modifier</button><button class="btn" onclick="App.closeModal();App.A.saveDish()">Publier</button></div>`, { width: 460 });
  };
  A.saveDish = () => {
    const d = UI.dishDraft;
    if (d._id === 'new') { const nd = Object.assign({}, d, { id: 'd' + Date.now(), rating: 0, ratingCount: 0, reviews: [] }); delete nd._id; D.dishes.push(nd); toast(d.name + ' publié dans le catalogue', 'green'); }
    else { const orig = dish(d._id); Object.assign(orig, d); delete orig._id; toast('Fiche publiée', 'green'); }
    UI.dishDraft = null; go('#/a/dishes');
  };
  A.archiveDish = (id) => confirmModal('Archiver ce plat ?', 'Il ne sera plus proposé dans le catalogue. Les menus déjà publiés ne sont pas modifiés.', 'Archiver', () => { dish(id).archived = true; UI.dishDraft = null; toast('Plat archivé'); go('#/a/dishes'); }, true);
  A.deleteDish = (id) => {
    const d = dish(id), booked = D.reservations.some(r => r.items.includes(id));
    if (booked) return confirmModal('Suppression impossible', `<b>${esc(d.name)}</b> figure dans des réservations : il doit être conservé pour l’historique et les statistiques. Vous pouvez l’archiver à la place.`, 'Archiver', () => A.archiveDish(id));
    confirmModal('Supprimer définitivement ?', `<b>${esc(d.name)}</b> sera retiré du catalogue et des menus à venir.`, 'Supprimer', () => {
      App.allDays().forEach(day => { if (day[d.cat]) day[d.cat] = day[d.cat].filter(x => x !== id); });
      D.dishes.splice(D.dishes.indexOf(d), 1); UI.dishDraft = null; App.audit('Suppression de plat', d.name, ''); toast('Plat supprimé'); go('#/a/dishes');
    }, true);
  };

  /* ================= A8 — Modération des avis ================= */
  route('/a/reviews', () => {
    const f = UI.revFilter || 'pending', sort = UI.revSort || 'recent';
    const counts = { all: D.reviews.length, pending: D.reviews.filter(r => r.status === 'pending').length, visible: D.reviews.filter(r => r.status === 'visible').length, hidden: D.reviews.filter(r => r.status === 'hidden').length };
    const kind = UI.revKind || 'all';
    let list = D.reviews.filter(r => (f === 'all' || r.status === f) && (kind === 'all' || (kind === 'dish' ? r.type === 'dish' : r.type !== 'dish')));
    list = list.slice().sort((a, b) => sort === 'low' ? a.stars - b.stars : sort === 'high' ? b.stars - a.stars : b.createdAt.localeCompare(a.createdAt));
    const hl = (r) => { let t = esc(r.comment); r.flagWords.forEach(w => { t = t.replace(new RegExp(w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'), (m) => `<mark class="bad">${m}</mark>`); }); return t; };
    const st = { visible: '<span class="badge green">Publié</span>', pending: '<span class="badge red">Signalé — à vérifier</span>', hidden: '<span class="badge gray">Masqué</span>' };
    return { layout: 'admin', nav: 'reviews', title: 'Avis', html: `
      ${head('Modération des avis', 'Avis sur les plats et sur les menus. Les avis contenant des mots interdits sont signalés automatiquement et masqués en attendant votre décision.', `<button class="btn ghost sm" onclick="App.A.editWords()">Mots interdits (${D.forbiddenWords.length})</button>`)}
      <div class="toolbar"><div class="chips">${[['pending', 'Signalés'], ['visible', 'Publiés'], ['hidden', 'Masqués'], ['all', 'Tous']].map(([k, l]) => `<button class="chip ${f === k ? 'active' : ''}" onclick="App.UI.revFilter='${k}';App.render()">${l} (${counts[k]})</button>`).join('')}</div>
        <span class="grow"></span><select class="input" style="min-width:0" onchange="App.UI.revKind=this.value;App.render()">${[['all', 'Plats et menus'], ['dish', 'Avis sur un plat'], ['menu', 'Avis sur le menu']].map(([k, l]) => `<option value="${k}" ${kind === k ? 'selected' : ''}>${l}</option>`).join('')}</select>
        <select class="input" style="min-width:0" onchange="App.UI.revSort=this.value;App.render()">${[['recent', 'Plus récents'], ['low', 'Note croissante'], ['high', 'Note décroissante']].map(([k, l]) => `<option value="${k}" ${sort === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div>
      ${list.length ? `<div class="stack">${list.map(r => { const u = user(r.userId); return `<div class="review-card ${r.status === 'pending' ? 'flag' : ''} ${r.status === 'hidden' ? 'hidden-r' : ''}">
        <div class="row between wrap"><div class="row">${App.avatar(u, '', 'width:32px;height:32px;font-size:12px')}<div><b class="small">${esc(App.displayName(u))}</b><div class="xs muted">${u && u.group ? esc(u.group) + ' · ' : ''}${r.type === 'dish' ? `<b>Plat : ${esc(dish(r.dishId).name)}</b> · servi le ${fmtShort(r.date)}` : `Menu du ${fmtLong(r.date).toLowerCase()}`} · ${r.createdAt}</div></div></div><div class="row"><span class="stars">${'<svg class=ic-star><use href=#i-star></use></svg>'.repeat(r.stars)}${'<svg class=ic-off><use href=#i-star></use></svg>'.repeat(5 - r.stars)}</span>${st[r.status]}</div></div>
        <p class="mt">${hl(r) || '<span class="muted">(sans commentaire)</span>'}</p>
        ${r.flagWords.length ? `<p class="xs mt" style="color:var(--red)">Mots détectés : ${r.flagWords.map(esc).join(', ')}</p>` : ''}${r.hideReason ? `<p class="xs muted mt">Masqué : ${esc(r.hideReason)}</p>` : ''}
        <div class="row mt">${r.status !== 'visible' ? `<button class="btn sm light" onclick="App.A.revShow('${r.id}')">${r.status === 'pending' ? 'Approuver et publier' : 'Republier'}</button>` : ''}${r.status !== 'hidden' ? `<button class="btn sm danger" onclick="App.A.revHide('${r.id}')">Masquer</button>` : ''}</div></div>`; }).join('')}</div>`
        : '<div class="empty"><div class="ico"><svg class=ic><use href=#i-message></use></svg></div>Aucun avis dans cette catégorie.</div>'}` };
  });
  A.revShow = (id) => { const r = D.reviews.find(x => x.id === id); r.status = 'visible'; r.approved = true; r.hideReason = null; toast('Avis publié', 'green'); render(); };
  A.revHide = (id) => modal(`<h2>Masquer l’avis</h2><p class="small muted mb">L’avis reste en base mais n’est plus visible par les convives.</p><div class="stack">${['Insulte ou propos irrespectueux', 'Spam ou publicité', 'Hors sujet', 'Informations personnelles'].map(m => `<button class="btn ghost block" onclick="App.A.revHideOk('${id}','${m}')">${m}</button>`).join('')}</div><div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button></div>`, { noFocus: true });
  A.revHideOk = (id, why) => { const r = D.reviews.find(x => x.id === id); r.status = 'hidden'; r.hideReason = why; closeModal(); toast('Avis masqué'); render(); };
  A.editWords = () => modal(`<h2>Mots interdits</h2><p class="small muted mb">Un avis contenant l’un de ces mots est signalé et masqué automatiquement jusqu’à vérification. Un mot ou une expression par ligne.</p><textarea class="input" id="fw" rows="9">${esc(D.forbiddenWords.join('\n'))}</textarea>
    <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.saveWords()">Enregistrer et réanalyser</button></div>`);
  A.saveWords = () => {
    D.forbiddenWords = document.getElementById('fw').value.split('\n').map(w => w.trim().toLowerCase()).filter(Boolean);
    let n = 0; D.reviews.filter(r => r.status !== 'hidden' && !r.approved).forEach(r => { const was = r.status; r.status = was === 'pending' ? 'visible' : was; App.scanReview(r); if (r.status === 'pending') n++; });
    closeModal(); toast(`Liste enregistrée — ${n} avis signalé(s)`, 'green'); render();
  };

  /* ================= A6 — Rapports ================= */
  route('/a/reports', () => {
    const type = UI.reportType;
    const weeks = [...new Set(D.history.map(h => h.week))];
    const byWeek = weeks.map(w => { const hs = D.history.filter(h => h.week === w); return { w, reserved: hs.reduce((s, h) => s + h.reserved, 0), served: hs.reduce((s, h) => s + h.served, 0), revenue: hs.reduce((s, h) => s + h.revenue, 0), peak: hs.reduce((a, b) => (b.reserved > a.reserved ? b : a)) }; });
    const tot = byWeek.reduce((a, b) => ({ reserved: a.reserved + b.reserved, served: a.served + b.served, revenue: a.revenue + b.revenue }), { reserved: 0, served: 0, revenue: 0 });
    let kpis = [], chart = '', table = { head: [], rows: [] };
    if (type === 'freq') {
      kpis = [['Repas réservés', num(tot.reserved)], ['Moyenne par jour', num(Math.round(tot.reserved / D.history.length))], ['Jour le plus chargé', D.history.reduce((a, b) => (b.reserved > a.reserved ? b : a)).date]];
      chart = barChart({ labels: D.history.map(h => h.date), series: [{ name: 'Réservés', color: C.sky, values: D.history.map(h => h.reserved) }] });
      table = { head: ['Semaine du', 'Réservés', 'Moyenne / jour', 'Pic'], rows: byWeek.map(b => [b.w, b.reserved, Math.round(b.reserved / 5), b.peak.day + ' ' + b.peak.date + ' (' + b.peak.reserved + ')']) };
    } else if (type === 'revenue') {
      kpis = [['Recettes totales', fcfa(tot.revenue)], ['Panier moyen', fcfa(Math.round(tot.revenue / tot.reserved))], ['Moyenne par semaine', fcfa(Math.round(tot.revenue / weeks.length))]];
      chart = barChart({ labels: byWeek.map(b => 'Sem. ' + b.w), series: [{ name: 'Recettes', color: C.ink, values: byWeek.map(b => b.revenue) }], fmt: (v) => (v >= 1000 ? Math.round(v / 1000) + ' k' : v) });
      table = { head: ['Semaine du', 'Repas', 'Recettes (FCFA)', 'Panier moyen (FCFA)'], rows: byWeek.map(b => [b.w, b.reserved, b.revenue, Math.round(b.revenue / b.reserved)]) };
    } else if (type === 'waste') {
      const missed = tot.reserved - tot.served;
      kpis = [['Repas non retirés', num(missed)], ['Taux de non-retrait', Math.round((missed / tot.reserved) * 100) + ' %'], ['Valeur perdue estimée', fcfa(Math.round((missed * tot.revenue) / tot.reserved))]];
      chart = barChart({ labels: byWeek.map(b => 'Sem. ' + b.w), series: [{ name: 'Servis', color: C.ink, values: byWeek.map(b => b.served) }, { name: 'Non retirés', color: C.orange, values: byWeek.map(b => b.reserved - b.served) }], stacked: true });
      table = { head: ['Semaine du', 'Réservés', 'Servis', 'Non retirés', 'Taux'], rows: byWeek.map(b => [b.w, b.reserved, b.served, b.reserved - b.served, Math.round(((b.reserved - b.served) / b.reserved) * 100) + ' %']) };
    } else if (type === 'satisfaction') {
      const ds = activeDishes().slice().sort((a, b) => b.rating - a.rating);
      kpis = [['Note moyenne', (ds.reduce((s, d) => s + d.rating, 0) / ds.length).toFixed(1).replace('.', ',') + ' / 5'], ['Avis reçus', num(ds.reduce((s, d) => s + d.ratingCount, 0))], ['Plat préféré', ds[0].name]];
      chart = hbars(ds.filter(d => d.cat === 'plat').map(d => ({ label: d.name.split(',')[0].slice(0, 12), value: d.rating, color: d.rating < 3.8 ? C.orange : C.sky })), 5, (v) => v.toFixed(1));
      table = { head: ['Plat', 'Catégorie', 'Note', 'Avis'], rows: ds.map(d => [d.name, CATS[d.cat], d.rating.toFixed(1), d.ratingCount]) };
    } else if (type === 'classes') {
      const cs = classStats();
      const withClass = D.users.filter(u => u.type === 'eleve' && u.group).length, eleves = D.users.filter(u => u.type === 'eleve').length;
      kpis = [['Classes représentées', cs.length], ['Élèves avec classe', withClass + ' / ' + eleves], ['Classe la plus active', cs[0] ? cs[0].cls : '—']];
      chart = barChart({ labels: cs.map(c => c.cls), series: [{ name: 'Réservations', color: C.sky, values: cs.map(c => c.reserved) }, { name: 'Servis', color: C.ink, values: cs.map(c => c.served) }] });
      table = { head: ['Classe', 'Élèves', 'Réservations', 'Servis', 'Non retirés', 'Repas / élève'], rows: cs.map(c => [c.cls, c.students.size, c.reserved, c.served, c.missed, (c.reserved / c.students.size).toFixed(1)]) };
    } else {
      kpis = [['Ingrédients suivis', D.ingredients.length], ['En alerte', D.ingredients.filter(i => i.stock < i.threshold).length], ['En rupture', D.ingredients.filter(i => i.stock <= 0).length]];
      chart = hbars(D.ingredients.map(i => ({ label: i.name.slice(0, 12), value: i.stock, color: i.stock <= 0 ? C.red : i.stock < i.threshold ? C.orange : C.green })), Math.max(...D.ingredients.map(i => i.stock)), (v) => v);
      table = { head: ['Ingrédient', 'Stock', 'Seuil', 'Besoin semaine', 'Unité'], rows: D.ingredients.map(i => [i.name, i.stock, i.threshold, weekNeed(i.id), i.unit]) };
    }
    UI._report = table;
    const types = [['freq', 'Fréquentation'], ['revenue', 'Recettes'], ['waste', 'Gaspillage'], ['satisfaction', 'Satisfaction'], ['classes', 'Par classe'], ['stock', 'Stocks']];
    return { layout: 'admin', nav: 'reports', title: 'Rapports', html: `
      ${head('Rapports d’activité', type === 'classes' ? 'Statistiques par classe : seuls les élèves ayant renseigné leur classe sont comptés.' : 'Période : 4 dernières semaines (26 oct. – 20 nov. 2026)', `<button class="btn ghost sm" onclick="window.print()"><svg class=ic><use href=#i-printer></use></svg> Exporter PDF</button><button class="btn ghost sm" onclick="App.downloadCSV('rapport-${type}.csv',[App.UI._report.head].concat(App.UI._report.rows))"><svg class=ic><use href=#i-download></use></svg> Exporter Excel (CSV)</button><button class="btn sm" onclick="App.toast('Rapport envoyé à ${esc(me().email)}','green')"><svg class=ic><use href=#i-mail></use></svg> Envoyer par e-mail</button>`)}
      <div class="report-tabs">${types.map(([k, l]) => `<button class="chip ${type === k ? 'active' : ''}" onclick="App.UI.reportType='${k}';App.render()">${l}</button>`).join('')}</div>
      <div class="kpis" style="grid-template-columns:repeat(3,1fr)">${kpis.map(([l, v]) => `<div class="kpi"><div class="label">${l}</div><div class="value" style="font-size:24px">${esc(v)}</div></div>`).join('')}</div>
      <div class="card mb">${chart}</div>
      <div class="table-wrap"><table class="tbl"><thead><tr>${table.head.map((h, i) => `<th class="${i ? 'num' : ''}">${h}</th>`).join('')}</tr></thead><tbody>${table.rows.map(r => `<tr>${r.map((c, i) => `<td class="${i ? 'num' : ''}">${typeof c === 'number' ? num(c) : esc(c)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>` };
  });

  /* ================= A7 — Utilisateurs et paramètres ================= */
  route('/a/users', () => {
    const roleL = { convive: 'Convive', parent: 'Parent', admin: 'Admin', comptoir: 'Comptoir' };
    let html;
    if (UI.usersTab === 'users') {
      const q = UI.usersSearch.toLowerCase();
      const list = D.users.filter(u => App.isActive(u) && !['admin', 'superadmin'].includes(u.role) && (UI.usersRole === 'all' || u.role === UI.usersRole) && (!q || fullName(u).toLowerCase().includes(q) || (u.matricule || '').toLowerCase().includes(q)));
      html = `<div class="toolbar"><input class="input" placeholder="Nom ou matricule" value="${esc(UI.usersSearch)}" onchange="App.UI.usersSearch=this.value;App.UI.usersLimit=40;App.render()">
        <select class="input" style="min-width:0" onchange="App.UI.usersRole=this.value;App.render()">${[['all', 'Tous les rôles']].concat(Object.entries(roleL)).map(([k, l]) => `<option value="${k}" ${UI.usersRole === k ? 'selected' : ''}>${l}</option>`).join('')}</select>
        <span class="small muted">${list.length} utilisateur(s)</span></div>
        <div class="table-wrap"><table class="tbl"><thead><tr><th>Nom</th><th>Rôle</th><th>Classe ou poste</th><th>Matricule</th><th class="num">Solde</th><th>Statut</th></tr></thead><tbody>
        ${list.slice(0, UI.usersLimit).map(u => `<tr style="cursor:pointer" onclick="App.A.userCard('${u.id}')"><td><div class="row">${App.avatar(u, '', 'width:30px;height:30px;font-size:11px')}<b>${esc(fullName(u))}</b></div></td><td>${u.role === 'convive' ? App.typeLabel(u) : roleL[u.role]}</td><td>${esc(u.type === 'personnel' ? (u.poste || '—') : (u.group || '—'))}</td><td class="muted">${esc(u.matricule || '—')}</td><td class="num">${u.balance != null ? fcfa(u.balance) : '—'}</td><td>${u.disabled ? '<span class="badge gray">Désactivé</span>' : '<span class="badge green">Actif</span>'}</td></tr>`).join('')}
        </tbody></table>${list.length > UI.usersLimit ? `<div class="center" style="padding:12px"><button class="btn ghost sm" onclick="App.UI.usersLimit+=40;App.render()">Afficher plus (${list.length - UI.usersLimit})</button></div>` : ''}</div>`;
    } else {
      html = `<div class="grid-2-eq">
        <div class="card"><h3>Créneaux et capacité</h3><table class="tbl"><thead><tr><th>Créneau</th><th class="num">Capacité</th></tr></thead><tbody>${D.slots.map((s, i) => `<tr><td>${s.label}</td><td class="num"><input class="input" type="number" value="${s.capacity}" style="width:90px;padding:6px 8px;text-align:right" onchange="App.D.slots[${i}].capacity=+this.value"></td></tr>`).join('')}</tbody></table>
          <div class="field mt"><label>Heure limite de réservation (jour même)</label><input class="input" type="time" value="${D.cutoff}" onchange="App.D.cutoff=this.value"></div></div>
        <div class="card"><h3>Paiements et alertes</h3>
          <div class="field"><label>Seuil d’alerte de solde par défaut</label><select class="input">${[500, 1000, 2000].map(v => `<option ${v === 1000 ? 'selected' : ''}>${fcfa(v)}</option>`).join('')}</select></div>
          <div class="stack">${[['Wave', true], ['Orange Money', true], ['MTN MoMo', true], ['Carte bancaire (parents)', true], ['Espèces au guichet', false]].map(([l, on], i) => `<div class="toggle"><span class="grow">${l}</span><button class="switch ${on ? 'on' : ''}" onclick="this.classList.toggle('on')"></button></div>`).join('')}</div>
          <div class="field mt"><label>Remboursement en cas d’annulation</label><select class="input"><option>Intégral avant l’heure limite</option><option>50 % après l’heure limite</option></select></div></div>
      </div>
      <div class="card mt"><h3>Période scolaire</h3><p class="small">Période en cours : <b>${D.settings.period.label}</b>. Au démarrage d’une nouvelle période, chaque élève confirme ou corrige sa classe (ou est invité à l’ajouter).</p><button class="btn ghost sm mt" onclick="App.A.newPeriod()">Démarrer une nouvelle période</button></div>
      <button class="btn mt" onclick="App.toast('Paramètres enregistrés','green')">Enregistrer les paramètres</button>`;
    }
    return { layout: 'admin', nav: 'users', title: 'Utilisateurs', html: `
      ${head('Utilisateurs et paramètres', '', `<button class="btn ghost sm" onclick="App.A.importCSV()"><svg class=ic><use href=#i-upload></use></svg> Importer des élèves (CSV)</button><button class="btn sm" onclick="App.A.newAgent()">+ Créer un agent de comptoir</button>`)}
      <div class="tabs" style="max-width:360px"><button class="${UI.usersTab === 'users' ? 'active' : ''}" onclick="App.UI.usersTab='users';App.render()">Utilisateurs</button><button class="${UI.usersTab === 'settings' ? 'active' : ''}" onclick="App.UI.usersTab='settings';App.render()">Paramètres</button></div>${html}` };
  });
  A.newPeriod = () => {
    const periods = [['2026-T2', '2e trimestre 2026-2027'], ['2026-T3', '3e trimestre 2026-2027'], ['2027-T1', 'Rentrée 2027-2028']];
    modal(`<h2>Nouvelle période</h2><div class="field"><label>Période</label><select class="input" id="per">${periods.map(([k, l]) => `<option value="${k}" ${k === D.settings.period.id ? 'disabled' : ''}>${l}</option>`).join('')}</select></div><p class="small muted">Les élèves verront « Êtes-vous toujours en [classe] ? » à leur prochaine connexion.</p>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.newPeriodOk()">Démarrer</button></div>`, { noFocus: true });
  };
  A.newPeriodOk = () => { const sel = document.getElementById('per'); D.settings.period = { id: sel.value, label: sel.options[sel.selectedIndex].text }; App.audit('Nouvelle période', D.settings.period.label, 'Confirmation de classe demandée aux élèves'); closeModal(); toast('Période démarrée : ' + D.settings.period.label, 'green'); render(); };
  A.userCard = (id) => {
    const u = user(id);
    const res = D.reservations.filter(r => r.userId === id).length;
    modal(`<div class="row mb">${App.avatar(u, 'lg', '')}<div><h2 style="margin:0">${esc(fullName(u))}</h2><p class="muted small">${u.role === 'convive' ? App.typeLabel(u) + (u.type === 'personnel' ? (u.poste ? ' · ' + esc(u.poste) : '') : (u.group ? ' · ' + esc(u.group) : ' · classe non renseignée')) + ' · ' : ''}${esc(u.email || 'Pas d’e-mail')} · ${esc(u.phone || '—')}</p></div></div>
      ${u.role === 'convive' ? `<div class="grid-2-eq"><div class="card flat"><div class="xs muted">Solde</div><b style="font-size:20px">${fcfa(u.balance)}</b></div><div class="card flat"><div class="xs muted">Réservations</div><b style="font-size:20px">${res}</b></div></div>
        <p class="small mt"><b>Allergies :</b> ${u.allergies.length ? u.allergies.map(a => ALL[a]).join(', ') : 'aucune'} · <b>Parent :</b> ${u.parentId ? esc(fullName(user(u.parentId))) : 'non lié'}</p>
        <div class="field mt"><label>Ajustement manuel du solde (FCFA)</label><div class="row"><input class="input" id="adj" type="number" step="100" placeholder="Ex. 1000 ou -500"><input class="input" id="adj-why" placeholder="Motif"></div></div>` : `<p class="small">Rôle : ${u.role}${u.children ? ' · Enfants : ' + u.children.map(k => esc(user(k).first)).join(', ') : ''}</p>`}
      <p class="xs muted mt">${me().role === 'superadmin' ? '' : 'La désactivation et la suppression de comptes sont réservées au super administrateur.'}</p>
      <div class="actions">${me().role === 'superadmin' ? `<button class="btn danger" onclick="App.closeModal();App.A.trashUser('${id}')">Mettre en corbeille</button>` : ''}<span class="grow"></span><button class="btn ghost" onclick="App.closeModal()">Fermer</button>${u.role === 'convive' ? `<button class="btn" onclick="App.A.adjust('${id}')">Appliquer</button>` : ''}</div>`, { noFocus: true });
  };
  A.adjust = (id) => {
    const v = parseInt(document.getElementById('adj').value, 10), why = document.getElementById('adj-why').value || 'Ajustement administrateur';
    if (!v) return toast('Montant invalide', 'red');
    const u = user(id); u.balance += v; addTx(id, 'ajustement', why, v); closeModal(); toast(`Solde de ${u.first} ajusté de ${v > 0 ? '+' : ''}${fcfa(v)}`, 'green'); render();
  };
  A.newAgent = () => {
    modal(`<h2>Créer un agent de comptoir</h2><div class="row"><div class="field grow"><label>Prénom</label><input class="input" id="ag-f"></div><div class="field grow"><label>Nom</label><input class="input" id="ag-l"></div></div><div class="field"><label>E-mail de connexion</label><input class="input" id="ag-e" placeholder="comptoir2@cantine-vatel.ci"></div><p class="xs muted">Un mot de passe provisoire sera envoyé par e-mail.</p>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.newAgentOk()">Créer</button></div>`);
  };
  A.newAgentOk = () => {
    const f = document.getElementById('ag-f').value.trim(), l = document.getElementById('ag-l').value.trim(), e = document.getElementById('ag-e').value.trim();
    if (!f || !l || !e) return toast('Tous les champs sont requis', 'red');
    D.users.push({ id: 'k' + Date.now(), role: 'comptoir', first: f, last: l, email: e, phone: '', title: 'Agent de comptoir' });
    closeModal(); toast('Agent créé : ' + f + ' ' + l, 'green'); UI.usersRole = 'comptoir'; render();
  };
  A.importCSV = () => {
    modal(`<h2>Importer des élèves</h2><p class="small muted mb">Fichier CSV avec les colonnes : prénom ; nom ; classe ; matricule</p><input type="file" accept=".csv,.txt" id="csv" class="input">
      <p class="xs muted mt">Ou collez le contenu :</p><textarea class="input" id="csv-txt" rows="4" placeholder="Awa;Traoré;5e B;VAT-5001"></textarea>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.importOk()">Importer</button></div>`, { noFocus: true });
  };
  A.importOk = () => {
    const f = document.getElementById('csv').files[0];
    const doImport = (txt) => {
      const rows = txt.split(/\r?\n/).map(l => l.split(/[;,]/).map(s => s.trim())).filter(r => r.length >= 3 && r[0] && !/pr[ée]nom/i.test(r[0]));
      rows.forEach((r, i) => D.users.push({ id: 'imp' + Date.now() + i, role: 'convive', first: r[0], last: r[1], group: r[2], matricule: r[3] || 'VAT-IMP' + i, email: '', phone: '', balance: 0, allergies: [], diets: [], parentId: null, threshold: 1000, linkCode: (r[0].slice(0, 3) + '-' + Math.random().toString(36).slice(2, 5)).toUpperCase() }));
      closeModal(); toast(rows.length + ' élève(s) importé(s)', rows.length ? 'green' : 'red'); render();
    };
    if (f) { const rd = new FileReader(); rd.onload = () => doImport(rd.result); rd.readAsText(f); } else doImport(document.getElementById('csv-txt').value);
  };

  /* ================= K1 — Comptoir : validation des repas ================= */
  function serve(r) {
    r.status = 'servie'; r.servedAt = D.NOW.time.replace(':', 'h');
    const u = user(r.userId);
    D.kioskLog.unshift({ name: fullName(u), dish: mainDish(r).name, at: r.servedAt });
    notify(u.id, '<svg class=ic-star><use href=#i-star></use></svg>', 'Bon appétit !', `Comment était votre ${mainDish(r).name} ? Donnez votre avis.`, '#/u/rate/' + r.id);
    if (u.parentId) notify(u.parentId, '<svg class=ic><use href=#i-check-circle></use></svg>', 'Repas servi — ' + u.first, `${mainDish(r).name} servi à ${r.servedAt}.`, '#/p/child/' + u.id);
  }
  route('/k/scan', () => {
    const k = me(), cur = currentSlot();
    const today = dayRes(D.NOW.date);
    const served = today.filter(r => r.status === 'servie').length;
    const res = UI.kResult;
    let right;
    if (!res) right = `<div class="result"><div class="status" style="color:#aaa">En attente d’un scan…</div><p style="color:#aaa">Demandez au convive d’afficher son QR code, ou saisissez son code à 6 chiffres.</p></div>`;
    else {
      const r = res.r, u = r && user(r.userId);
      const items = r ? `<div class="serve-list">${r.items.map(i => `<div>${esc(dish(i).name)}</div>`).join('')}</div>` : '';
      const who = u ? `<div class="row">${App.avatar(u, 'lg', '')}<div><div style="font-size:24px;font-weight:800">${esc(fullName(u))}</div><div style="color:#ddd">${esc(u.type === 'personnel' ? (u.poste || 'Personnel') : (u.group || 'Élève'))} · réservé ${slotLabel(r.slot)}</div></div></div>` : '';
      const risk = r && r.allergyConflict && r.allergyConflict.length;
      const allergy = u && u.allergies.length ? `<div class="alert red" style="font-size:18px;font-weight:700;${risk ? 'background:#fff;border:4px solid var(--red)' : ''}"><span><svg class=ic><use href=#i-alert></use></svg> ALLERGIE : ${u.allergies.map(a => ALL[a].toUpperCase()).join(', ')}${risk ? `<br><span style="font-size:15px">Le plateau contient ${r.allergyConflict.map(c => esc(dish(c.dishId).name)).join(', ')} — vérifier avant de servir${r.allergyChecked ? ' (vérifié par ' + esc(r.allergyChecked) + ')' : ''}</span>` : ''}</span></div>` : '';
      if (res.kind === 'ok') right = `<div class="result ok"><div class="status"><svg class=ic><use href=#i-check></use></svg> Validé — servir</div>${who}${allergy}${items}</div>`;
      else if (res.kind === 'warn') right = `<div class="result warn"><div class="status"><svg class=ic><use href=#i-alert></use></svg> ${res.msg}</div>${who}${allergy}${items}${res.canForce ? `<button class="btn sky" onclick="App.A.kForce('${r.id}')">Servir quand même</button>` : ''}</div>`;
      else right = `<div class="result err"><div class="status"><svg class=ic><use href=#i-x></use></svg> ${res.msg}</div><p>${res.detail || ''}</p></div>`;
    }
    return { layout: 'kiosk', title: 'Comptoir', html: `
      <div class="kiosk-head">${App.brand('Comptoir · ' + esc(k.first + ' ' + k.last))}<div><div style="font-size:13px;color:#aaa">Créneau en cours</div><b style="font-size:18px">${cur ? cur.label + ' – ' + (D.slots[D.slots.indexOf(cur) + 1] || { label: '13h30' }).label : 'Hors service (' + D.NOW.time + ')'}</b></div>
        <div class="counter">${served} / ${today.length}<small>repas servis</small></div><button class="btn sm ghost" style="color:#fff;border-color:#444" onclick="App.A.logout()">Quitter</button></div>
      <div class="kiosk-body">
        <div class="scan-zone">
          <div class="viewfinder" onclick="App.A.kSimulate()" title="Cliquer pour simuler un scan"><div class="laser"></div></div>
          <button class="btn sky" onclick="App.A.kSimulate()"><svg class=ic><use href=#i-camera></use></svg> Simuler un scan de QR code</button>
          <form class="row" style="width:100%;max-width:320px;flex-direction:column;gap:10px" onsubmit="event.preventDefault();App.A.kValidate(document.getElementById('k-code').value)">
            <input class="code-input" id="k-code" maxlength="6" inputmode="numeric" placeholder="000000" autocomplete="off">
            <button class="btn block" style="background:#fff;color:#1d1d1b">Valider le code</button></form>
          <div class="recent" style="width:100%"><div class="small" style="color:#aaa;margin-bottom:6px">Derniers servis</div>${D.kioskLog.slice(0, 5).map(l => `<div class="r"><span>${esc(l.name)}</span><span>${esc(l.dish)} · ${l.at}</span></div>`).join('') || '<div class="r"><span>Aucun pour le moment</span></div>'}</div>
        </div>
        <div class="stack">${right}<button class="btn ghost" style="color:#fff;border-color:#444" onclick="App.A.kProblem()">Signaler un problème</button></div>
      </div>`, after: () => { const i = document.getElementById('k-code'); if (i) i.focus(); } };
  });
  A.kValidate = (code) => {
    code = String(code || '').trim();
    const r = D.reservations.find(x => x.code === code && x.status !== 'annulee');
    const cur = currentSlot();
    if (!/^\d{6}$/.test(code)) UI.kResult = { kind: 'err', msg: 'Code invalide', detail: 'Le code doit contenir 6 chiffres.' };
    else if (!r) UI.kResult = { kind: 'err', msg: 'Aucune réservation', detail: 'Ce code ne correspond à aucune réservation.' };
    else if (r.date !== D.NOW.date) UI.kResult = { kind: 'err', msg: 'Pas pour aujourd’hui', detail: 'Cette réservation est pour le ' + fmtLong(r.date).toLowerCase() + '.' };
    else if (r.status === 'servie') UI.kResult = { kind: 'warn', r, msg: 'Déjà servi à ' + r.servedAt };
    else if (!cur || cur.id !== r.slot) UI.kResult = { kind: 'warn', r, msg: 'Mauvais créneau (réservé ' + slotLabel(r.slot) + ')', canForce: true };
    else { serve(r); UI.kResult = { kind: 'ok', r }; }
    render();
  };
  A.kForce = (id) => { const r = D.reservations.find(x => x.id === id); serve(r); UI.kResult = { kind: 'ok', r }; render(); };
  A.kSimulate = () => {
    const cur = currentSlot();
    const pending = dayRes(D.NOW.date).filter(r => r.status === 'payee');
    const last = UI.lastBooked && pending.find(r => r.id === UI.lastBooked);
    const pick = last || pending.find(r => cur && r.slot === cur.id) || pending[0];
    if (!pick) { UI.kResult = { kind: 'err', msg: 'Plus aucun repas à servir' }; return render(); }
    if (last) UI.lastBooked = null;
    A.kValidate(pick.code);
  };
  A.kProblem = () => {
    modal(`<h2>Signaler un problème</h2><div class="stack">${['Plat épuisé', 'QR code illisible', 'Convive sans réservation', 'Erreur de plateau', 'Autre'].map(p => `<button class="btn ghost block" onclick="App.closeModal();App.toast('Signalement envoyé à l’administration : ${p}')">${p}</button>`).join('')}</div>`);
  };
})();
