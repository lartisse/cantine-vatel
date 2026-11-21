/* =========================================================
   Écrans Super administrateur (S1–S5)
   S1 Administrateurs · S2 Comptes et corbeille · S3 Demandes de suppression
   S4 Moyens de paiement · S5 Journal d'audit
   Accès : rôle « superadmin » uniquement (voir le contrôle d'accès dans core.js)
   ========================================================= */
(function () {
  const { D, UI, A, esc, fcfa, user, me, fullName, fmtLong, fmtShort, modal, closeModal, confirmModal, toast, render, go, route,
    notify, audit, avatar, displayName, roleLabel, typeLabel, requireSudo, downloadCSV, isActive } = App;
  const head = (title, sub, actions) => `<div class="page-head"><div><h1>${title}</h1>${sub ? `<p>${sub}</p>` : ''}</div><div class="row wrap">${actions || ''}</div></div>`;
  const daysBetween = (a, b) => Math.round((new Date(b + 'T12:00:00') - new Date(a + 'T12:00:00')) / 86400000);
  const statusB = (u) => u.status === 'trashed' ? '<span class="badge orange">En corbeille</span>' : u.disabled ? '<span class="badge gray">Désactivé</span>' : '<span class="badge green">Actif</span>';

  /* ================= S1 — Administrateurs ================= */
  route('/s/admins', () => {
    const list = D.users.filter(u => u.role === 'admin' || u.role === 'superadmin');
    return { layout: 'admin', nav: 'admins', title: 'Administrateurs', html: `
      ${head('Administrateurs', 'Comptes ayant accès à l’administration de la cantine.', '<button class="btn sm" onclick="App.A.adminForm()">+ Créer un administrateur</button>')}
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Nom</th><th>E-mail</th><th>Fonction</th><th>Rôle</th><th>Statut</th><th></th></tr></thead><tbody>
        ${list.map(u => `<tr><td><div class="row">${avatar(u, '', 'width:30px;height:30px;font-size:11px')}<b>${esc(fullName(u))}</b>${u.id === me().id ? ' <span class="badge">Vous</span>' : ''}</div></td><td>${esc(u.email)}</td><td class="small">${esc(u.title || '—')}</td><td>${u.role === 'superadmin' ? '<span class="badge" style="background:var(--ink);color:#fff">Super admin</span>' : '<span class="badge">Administrateur</span>'}</td><td>${statusB(u)}</td>
          <td class="num"><button class="btn sm ghost" onclick="App.A.adminForm('${u.id}')">Modifier</button> ${u.id === me().id ? '' : u.disabled ? `<button class="btn sm light" onclick="App.A.adminToggle('${u.id}')">Réactiver</button>` : `<button class="btn sm danger" onclick="App.A.adminToggle('${u.id}')">Désactiver</button>`}</td></tr>`).join('')}
      </tbody></table></div>
      <p class="xs muted mt">Un administrateur désactivé ne peut plus se connecter. Toutes les actions sont tracées dans le journal d’audit.</p>` };
  });
  A.adminForm = (id) => {
    const u = id ? user(id) : { first: '', last: '', email: '', title: 'Gestionnaire de la cantine', role: 'admin' };
    modal(`<h2>${id ? 'Modifier l’administrateur' : 'Créer un administrateur'}</h2>
      <div class="row"><div class="field grow"><label>Prénom *</label><input class="input" id="ad-f" value="${esc(u.first)}"></div><div class="field grow"><label>Nom *</label><input class="input" id="ad-l" value="${esc(u.last)}"></div></div>
      <div class="field"><label>E-mail de connexion *</label><input class="input" id="ad-e" type="email" value="${esc(u.email)}"></div>
      <div class="field"><label>Fonction</label><input class="input" id="ad-t" value="${esc(u.title || '')}"></div>
      <div class="field"><label>Rôle</label><select class="input" id="ad-r" ${id === me().id ? 'disabled' : ''}><option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Administrateur (gestion de la cantine)</option><option value="superadmin" ${u.role === 'superadmin' ? 'selected' : ''}>Super administrateur</option></select>${id === me().id ? '<span class="xs muted">Vous ne pouvez pas changer votre propre rôle.</span>' : ''}</div>
      ${id ? '' : '<p class="xs muted">Un e-mail d’activation avec un lien pour définir le mot de passe sera envoyé.</p>'}
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.adminSave(${id ? `'${id}'` : 'null'})">${id ? 'Enregistrer' : 'Créer'}</button></div>`);
  };
  A.adminSave = (id) => {
    const v = (k) => document.getElementById(k).value.trim();
    const f = v('ad-f'), l = v('ad-l'), e = v('ad-e'), t = v('ad-t'), r = document.getElementById('ad-r').value;
    if (!f || !l || !/^\S+@\S+\.\S+$/.test(e)) return toast('Prénom, nom et e-mail valide sont obligatoires', 'red');
    if (D.users.some(u => u.email === e && u.id !== id)) return toast('Cet e-mail est déjà utilisé', 'red');
    if (id) {
      const u = user(id), oldRole = u.role;
      Object.assign(u, { first: f, last: l, email: e, title: t });
      if (id !== me().id && r !== oldRole) { u.role = r; audit('Changement de rôle', fullName(u), `Rôle : ${roleLabel(oldRole)} → ${roleLabel(r)}`); }
      else audit('Modification administrateur', fullName(u), 'Informations du compte mises à jour');
      toast('Administrateur mis à jour', 'green');
    } else {
      const u = { id: 'a' + Date.now(), role: r, first: f, last: l, email: e, phone: '', title: t, status: 'active' };
      D.users.push(u); audit('Création administrateur', fullName(u), 'Rôle : ' + roleLabel(r)); toast('Compte créé — e-mail d’activation envoyé', 'green');
    }
    closeModal(); render();
  };
  A.adminToggle = (id) => {
    const u = user(id);
    if (u.disabled) return confirmModal('Réactiver ce compte ?', `${esc(fullName(u))} pourra de nouveau se connecter.`, 'Réactiver', () => { u.disabled = false; audit('Réactivation administrateur', fullName(u), ''); toast('Compte réactivé', 'green'); render(); });
    modal(`<h2>Désactiver ${esc(fullName(u))}</h2><p class="small muted mb">Le compte ne pourra plus se connecter. Il peut être réactivé à tout moment.</p><div class="field"><label>Motif *</label><input class="input" id="ad-why"></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn danger" onclick="App.A.adminDisable('${id}')">Désactiver</button></div>`);
  };
  A.adminDisable = (id) => {
    const why = document.getElementById('ad-why').value.trim();
    if (!why) return toast('Le motif est obligatoire', 'red');
    const u = user(id); u.disabled = true; audit('Désactivation administrateur', fullName(u), 'Motif : ' + why); closeModal(); toast('Compte désactivé'); render();
  };

  /* ================= S2 — Comptes et corbeille (suppression en 2 phases) ================= */
  const purgeLeft = (u) => D.settings.purgeDays - daysBetween(u.trashedAt, D.NOW.date);
  route('/s/accounts', () => {
    const tab = UI.accTab || 'active';
    const trash = D.users.filter(u => u.status === 'trashed');
    let body;
    if (tab === 'active') {
      const q = (UI.accSearch || '').toLowerCase();
      const list = D.users.filter(u => isActive(u) && ['convive', 'parent', 'comptoir'].includes(u.role) && (!q || fullName(u).toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q)));
      body = `<div class="toolbar"><input class="input" placeholder="Rechercher un nom ou un e-mail" value="${esc(UI.accSearch || '')}" onchange="App.UI.accSearch=this.value;App.render()"><span class="small muted">${list.length} compte(s)</span></div>
        <div class="table-wrap"><table class="tbl"><thead><tr><th>Nom</th><th>Type</th><th>Classe ou poste</th><th>Parent lié</th><th class="num">Solde</th><th></th></tr></thead><tbody>
        ${list.slice(0, 40).map(u => `<tr><td><div class="row">${avatar(u, '', 'width:30px;height:30px;font-size:11px')}<b>${esc(fullName(u))}</b></div></td><td>${u.role === 'convive' ? typeLabel(u) : roleLabel(u.role)}</td><td>${esc(u.type === 'personnel' ? (u.poste || '—') : (u.group || '—'))}</td><td class="small">${u.parentId ? esc(fullName(user(u.parentId))) : u.children ? u.children.length + ' enfant(s)' : '—'}</td><td class="num">${u.balance != null ? fcfa(u.balance) : '—'}</td><td class="num"><button class="btn sm danger" onclick="App.A.trashUser('${u.id}')">Mettre en corbeille</button></td></tr>`).join('')}
        </tbody></table>${list.length > 40 ? '<p class="xs muted center" style="padding:10px">Affinez la recherche pour voir les autres comptes.</p>' : ''}</div>`;
    } else {
      body = `<div class="grid-2" style="grid-template-columns:2fr 1fr">
        <div>${trash.length ? `<div class="table-wrap"><table class="tbl"><thead><tr><th>Compte</th><th>Désactivé le</th><th>Motif</th><th>Purge auto.</th><th></th></tr></thead><tbody>
          ${trash.map(u => { const left = purgeLeft(u); return `<tr><td><b>${esc(fullName(u))}</b><div class="xs muted">${u.role === 'convive' ? typeLabel(u) : roleLabel(u.role)}${u.group ? ' · ' + esc(u.group) : ''}</div></td><td class="small">${fmtShort(u.trashedAt)}<div class="xs muted">par ${esc(u.trashedBy || '—')}</div></td><td class="small">${esc(u.trashReason)}</td><td>${left <= 0 ? '<span class="badge red">Due</span>' : `<span class="badge ${left <= 7 ? 'orange' : 'gray'}">dans ${left} j</span>`}</td>
            <td class="num" style="white-space:nowrap"><button class="btn sm light" onclick="App.A.restoreUser('${u.id}')">Restaurer</button> <button class="btn sm danger" onclick="App.A.purgeUser('${u.id}')">Supprimer définitivement</button></td></tr>`; }).join('')}
          </tbody></table></div>` : '<div class="empty"><div class="ico"><svg class=ic><use href=#i-trash></use></svg></div>La corbeille est vide.</div>'}</div>
        <div class="card"><h3>Purge automatique</h3><p class="small muted">Les comptes restés en corbeille plus longtemps que ce délai sont supprimés définitivement (données personnelles effacées, statistiques conservées).</p>
          <div class="field mt"><label>Délai avant purge (jours)</label><div class="row"><input class="input" type="number" min="1" max="365" id="purge-days" value="${D.settings.purgeDays}"><button class="btn sm ghost" onclick="App.A.savePurgeDays()">Enregistrer</button></div></div>
          <button class="btn sm danger block" onclick="App.A.runPurge()">Exécuter la purge maintenant</button><p class="xs muted mt">En production : tâche planifiée quotidienne (Laravel Scheduler).</p></div></div>`;
    }
    return { layout: 'admin', nav: 'accounts', title: 'Comptes et corbeille', html: `
      ${head('Comptes et corbeille', 'Phase 1 : mise en corbeille (réversible). Phase 2 : suppression définitive (super admin, compte déjà en corbeille).')}
      <div class="tabs" style="max-width:380px"><button class="${tab === 'active' ? 'active' : ''}" onclick="App.UI.accTab='active';App.render()">Comptes actifs</button><button class="${tab === 'trash' ? 'active' : ''}" onclick="App.UI.accTab='trash';App.render()">Corbeille (${trash.length})</button></div>${body}` };
  });
  // Phase 1 — désactivation (corbeille) : motif obligatoire, données conservées
  A.trashUser = (id, presetReason, requestId) => {
    const u = user(id);
    modal(`<h2>Mettre en corbeille</h2><p><b>${esc(fullName(u))}</b> — ${u.role === 'convive' ? typeLabel(u) : roleLabel(u.role)}</p>
      <ul class="small muted" style="padding-left:18px;margin:10px 0"><li>Le compte ne peut plus se connecter et n’apparaît plus dans les listes.</li><li>Toutes les données sont conservées : profil, réservations, avis, liaison parent.</li><li>Restauration possible à tout moment depuis la corbeille.</li><li>${esc(u.first)}${u.parentId ? ' et son parent lié' : ''} seront notifiés.</li></ul>
      <div class="field"><label>Motif *</label><textarea class="input" id="tr-why" rows="2">${esc(presetReason || '')}</textarea><span class="error" id="tr-err"></span></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn danger" onclick="App.A.trashOk('${id}',${requestId ? `'${requestId}'` : 'null'})">Mettre en corbeille</button></div>`);
  };
  A.trashOk = (id, requestId) => {
    const why = document.getElementById('tr-why').value.trim();
    if (!why) { document.getElementById('tr-err').textContent = 'Le motif est obligatoire.'; return; }
    const u = user(id);
    Object.assign(u, { status: 'trashed', trashedAt: D.NOW.date, trashReason: why, trashedBy: fullName(me()) });
    notify(u.id, '<svg class=ic><use href=#i-lock></use></svg>', 'Compte désactivé', 'Votre compte a été désactivé. Motif : ' + why, '#/');
    if (u.parentId) notify(u.parentId, '<svg class=ic><use href=#i-lock></use></svg>', 'Compte de ' + u.first + ' désactivé', 'Motif : ' + why, '#/p/home');
    if (requestId) { const r = D.deletionRequests.find(x => x.id === requestId); r.status = 'accepted'; r.decidedAt = D.NOW.date; }
    audit('Désactivation', fullName(u), 'Motif : ' + why + (requestId ? ' (demande acceptée)' : ''));
    closeModal(); toast(fullName(u) + ' mis(e) en corbeille'); render();
  };
  A.restoreUser = (id) => {
    const u = user(id);
    confirmModal('Restaurer ce compte ?', `${esc(fullName(u))} pourra de nouveau se connecter et retrouvera toutes ses données.`, 'Restaurer', () => {
      u.status = 'active'; delete u.trashedAt; delete u.trashReason;
      notify(u.id, '<svg class=ic><use href=#i-check-circle></use></svg>', 'Compte réactivé', 'Votre compte est de nouveau actif.', '#/');
      if (u.parentId) notify(u.parentId, '<svg class=ic><use href=#i-check-circle></use></svg>', 'Compte de ' + u.first + ' réactivé', '', '#/p/home');
      audit('Restauration', fullName(u), 'Compte restauré depuis la corbeille'); toast('Compte restauré', 'green'); render();
    });
  };
  // Phase 2 — suppression définitive : uniquement depuis la corbeille, confirmation renforcée
  A.purgeUser = (id) => {
    const u = user(id);
    if (u.status !== 'trashed') return toast('Le compte doit d’abord être mis en corbeille', 'red');
    UI.purgeMode = UI.purgeMode || 'name';
    const name = fullName(u);
    modal(`<h2 style="color:var(--red)">Suppression définitive</h2>
      <div class="alert red mb"><span>Action irréversible. Les données personnelles de <b>${esc(name)}</b> seront effacées.</span></div>
      <div class="grid-2-eq small mb"><div><b>Effacé</b><ul class="muted" style="padding-left:18px;margin:6px 0"><li>Nom, e-mail, téléphone, photo</li><li>Classe, matricule, poste</li><li>Allergies, liaison parent</li></ul></div><div><b>Conservé (anonymisé)</b><ul class="muted" style="padding-left:18px;margin:6px 0"><li>Réservations (statistiques)</li><li>Avis, affichés « Utilisateur supprimé »</li><li>Écritures comptables</li></ul></div></div>
      <div class="tabs"><button class="${UI.purgeMode === 'name' ? 'active' : ''}" onclick="App.UI.purgeMode='name';App.A.purgeUser('${id}')">Retaper le nom</button><button class="${UI.purgeMode === 'pw' ? 'active' : ''}" onclick="App.UI.purgeMode='pw';App.A.purgeUser('${id}')">Mon mot de passe</button></div>
      ${UI.purgeMode === 'name' ? `<div class="field"><label>Pour confirmer, tapez <b>${esc(name)}</b></label><input class="input" id="pg-confirm" autocomplete="off" oninput="document.getElementById('pg-btn').disabled=this.value.trim()!==${JSON.stringify(name).replace(/"/g, '&quot;')}"></div>`
        : `<div class="field"><label>Mot de passe du super administrateur</label><input class="input" type="password" id="pg-confirm" oninput="document.getElementById('pg-btn').disabled=this.value.length<6"><span class="xs muted">Démo : 6 caractères ou plus.</span></div>`}
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn danger" id="pg-btn" disabled onclick="App.A.purgeOk('${id}')">Supprimer définitivement</button></div>`, { width: 560 });
  };
  function anonymize(u) {
    const parent = u.parentId && user(u.parentId);
    if (parent && parent.children) parent.children = parent.children.filter(k => k !== u.id);
    if (u.children) u.children.forEach(k => { const c = user(k); if (c) c.parentId = null; });
    Object.assign(u, { status: 'deleted', first: 'Utilisateur', last: 'supprimé', email: '', phone: '', photo: null, group: null, matricule: null, poste: null, allergies: [], diets: [], parentId: null, children: u.children ? [] : undefined, linkCode: null, trashReason: null, deletedAt: D.NOW.date });
    D.notifications = D.notifications.filter(n => n.userId !== u.id);
  }
  A.purgeOk = (id) => {
    const u = user(id), name = fullName(u);
    anonymize(u);
    audit('Suppression définitive', 'Compte ' + id, 'Données personnelles effacées ; réservations et avis anonymisés');
    closeModal(); toast(name + ' : données personnelles effacées'); render();
  };
  A.savePurgeDays = () => {
    const v = parseInt(document.getElementById('purge-days').value, 10);
    if (!(v >= 1 && v <= 365)) return toast('Entre 1 et 365 jours', 'red');
    const old = D.settings.purgeDays; D.settings.purgeDays = v; audit('Paramètre de purge', 'Corbeille', `Délai : ${old} → ${v} jours`); toast('Délai de purge : ' + v + ' jours', 'green'); render();
  };
  A.runPurge = () => {
    const due = D.users.filter(u => u.status === 'trashed' && purgeLeft(u) <= 0);
    if (!due.length) return toast('Aucun compte n’a dépassé le délai de ' + D.settings.purgeDays + ' jours');
    confirmModal('Exécuter la purge ?', `${due.length} compte(s) en corbeille depuis plus de ${D.settings.purgeDays} jours seront supprimés définitivement.`, 'Purger', () => {
      due.forEach(u => { anonymize(u); audit('Purge automatique', 'Compte ' + u.id, `Plus de ${D.settings.purgeDays} jours en corbeille`); });
      toast(due.length + ' compte(s) purgé(s)'); render();
    }, true);
  };

  /* ================= S3 — Demandes de suppression ================= */
  route('/s/requests', () => {
    const pending = D.deletionRequests.filter(r => r.status === 'pending');
    const done = D.deletionRequests.filter(r => r.status !== 'pending' && r.status !== 'cancelled');
    const card = (r) => { const u = user(r.userId); return `<div class="review-card"><div class="row between wrap"><div class="row">${avatar(u, '', 'width:36px;height:36px;font-size:13px')}<div><b>${esc(displayName(u))}</b><div class="xs muted">${u.role === 'convive' ? typeLabel(u) : roleLabel(u.role)}${u.group ? ' · ' + esc(u.group) : ''} · demande du ${fmtShort(r.date)}</div></div></div>
      ${r.status === 'pending' ? `<div class="row"><button class="btn sm danger" onclick="App.A.acceptRequest('${r.id}')">Accepter</button><button class="btn sm ghost" onclick="App.A.refuseRequest('${r.id}')">Refuser</button></div>` : r.status === 'accepted' ? '<span class="badge orange">Acceptée → corbeille</span>' : '<span class="badge gray">Refusée</span>'}</div>
      <p class="small mt">${r.reason ? '« ' + esc(r.reason) + ' »' : '<span class="muted">Aucun motif indiqué</span>'}</p>${r.answer ? `<p class="xs muted mt">Réponse envoyée : ${esc(r.answer)}</p>` : ''}</div>`; };
    return { layout: 'admin', nav: 'requests', title: 'Demandes de suppression', html: `
      ${head('Demandes de suppression', 'Accepter place le compte en corbeille (phase 1). Refuser envoie un message de justification à l’utilisateur.')}
      <h3 class="mb">En attente (${pending.length})</h3>${pending.length ? `<div class="stack">${pending.map(card).join('')}</div>` : '<div class="empty">Aucune demande en attente.</div>'}
      ${done.length ? `<h3 class="mt mb">Traitées</h3><div class="stack">${done.map(card).join('')}</div>` : ''}` };
  });
  A.acceptRequest = (id) => { const r = D.deletionRequests.find(x => x.id === id); A.trashUser(r.userId, 'Demande de l’utilisateur' + (r.reason ? ' : ' + r.reason : ''), id); };
  A.refuseRequest = (id) => modal(`<h2>Refuser la demande</h2><div class="field"><label>Message de justification à l’utilisateur *</label><textarea class="input" id="rf-msg" rows="4" placeholder="Ex. Votre solde de 2 500 FCFA doit d’abord être remboursé. Passez au bureau de la cantine."></textarea></div>
    <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.refuseOk('${id}')">Envoyer le refus</button></div>`);
  A.refuseOk = (id) => {
    const msg = document.getElementById('rf-msg').value.trim();
    if (!msg) return toast('Le message de justification est obligatoire', 'red');
    const r = D.deletionRequests.find(x => x.id === id), u = user(r.userId);
    Object.assign(r, { status: 'refused', answer: msg, decidedAt: D.NOW.date });
    notify(u.id, '<svg class=ic><use href=#i-mail></use></svg>', 'Demande de suppression refusée', msg, '#/profile');
    audit('Refus de suppression', fullName(u), 'Message : ' + msg);
    closeModal(); toast('Refus envoyé à ' + u.first); render();
  };

  /* ================= S4 — Moyens de paiement (clés API) ================= */
  const keyState = (p, envKey) => { const e = p[envKey]; const n = p.fields.filter(f => e.keys[f[0]]).length; return { e, n, full: n === p.fields.length }; };
  // Masque une clé : préfixe lisible + 8 points + 4 derniers caractères. La valeur claire n'est jamais conservée côté interface.
  const maskKey = (v) => { const m = v.match(/^([A-Za-z]+_(?:[A-Za-z]+_){0,2})/); const prefix = m && m[1].length <= 14 ? m[1] : ''; return prefix + '••••••••' + v.slice(-4); };
  route('/s/payments', () => {
    const envKey = UI.payEnv || 'prod';
    const sudo = D.sudoUntil > Date.now();
    return { layout: 'admin', nav: 'payments', title: 'Moyens de paiement', html: `
      ${head('Moyens de paiement', 'Clés API par moyen de paiement et par environnement.', `<div class="env-tabs"><button class="test ${envKey === 'test' ? 'active' : ''}" onclick="App.UI.payEnv='test';App.render()">Test</button><button class="prod ${envKey === 'prod' ? 'active' : ''}" onclick="App.UI.payEnv='prod';App.render()">Production</button></div>`)}
      <div class="secure-note"><span style="font-size:20px"><svg class=ic><use href=#i-lock></use></svg></span><div><b>Écran réservé au super administrateur.</b> Les clés sont chiffrées en base (Laravel Crypt, AES-256) et ne sont jamais réaffichées : seule une version masquée est visible. Toute modification exige la ressaisie de votre mot de passe${sudo ? ' — <b>identité confirmée</b>, valable encore ' + Math.ceil((D.sudoUntil - Date.now()) / 60000) + ' min' : ''}.</div></div>
      ${envKey === 'test' ? '<div class="alert orange mb"><span>Environnement <b>Test</b> : aucun débit réel. Les utilisateurs ne voient que les moyens activés en Production.</span></div>' : ''}
      <div class="prov-grid">${D.providers.map(p => { const { e, n, full } = keyState(p, envKey); return `<div class="card">
        <div class="prov-head"><span class="logo" style="background:${p.color};color:${p.id === 'momo' ? '#1d1d1b' : '#fff'}">${p.logo}</span><div class="grow"><b>${p.name}</b><div class="xs muted">${p.kind}</div></div>
          <button class="switch ${e.enabled ? 'on' : ''}" title="${e.enabled ? 'Désactiver' : 'Activer'}" onclick="App.A.payToggle('${p.id}')"></button></div>
        <div class="row mb">${e.enabled ? '<span class="badge green">Activé</span>' : '<span class="badge gray">Désactivé</span>'}${full ? '<span class="badge">Clés configurées</span>' : n ? `<span class="badge orange">${n}/${p.fields.length} clés</span>` : '<span class="badge orange">Aucune clé</span>'}${e.lastTest ? `<span class="badge ${e.lastTest.ok ? 'green' : 'red'}">${e.lastTest.ok ? '<svg class=ic><use href=#i-check></use></svg> Test OK' : '<svg class=ic><use href=#i-x></use></svg> Test échoué'}</span>` : ''}</div>
        ${p.fields.map(([k, l]) => `<div class="key-row"><span class="muted">${l}</span>${e.keys[k] ? `<code title="Enregistrée le ${e.keys[k].savedAt}">${esc(e.keys[k].masked)}</code>` : '<span class="xs muted">Non configurée</span>'}</div>`).join('')}
        <div class="row mt wrap"><button class="btn sm light" onclick="App.A.payKeys('${p.id}')">${n ? 'Remplacer les clés' : 'Ajouter les clés'}</button><button class="btn sm ghost" onclick="App.A.payTest('${p.id}')">Tester la connexion</button>${n ? `<button class="btn sm danger" onclick="App.A.payDelete('${p.id}')">Supprimer</button>` : ''}</div>
        ${e.lastTest ? `<p class="xs muted mt">Dernier test : ${e.lastTest.at} · ${esc(e.lastTest.msg)}</p>` : ''}</div>`; }).join('')}</div>` };
  });
  const envLabel = () => (UI.payEnv || 'prod') === 'test' ? 'Test' : 'Production';
  A.payToggle = (pid) => {
    const p = D.providers.find(x => x.id === pid), envKey = UI.payEnv || 'prod', e = p[envKey];
    requireSudo((e.enabled ? 'Désactiver ' : 'Activer ') + p.name, () => {
      if (!e.enabled) {
        if (!keyState(p, envKey).full) return toast('Ajoutez toutes les clés avant d’activer ' + p.name, 'red');
        if (!e.lastTest || !e.lastTest.ok) return toast('Testez la connexion avant la mise en service', 'red');
      }
      e.enabled = !e.enabled;
      audit(e.enabled ? 'Activation paiement' : 'Désactivation paiement', `${p.name} (${envLabel()})`, 'Clés conservées');
      toast(`${p.name} ${e.enabled ? 'activé' : 'désactivé'} en ${envLabel()}`, e.enabled ? 'green' : ''); render();
    });
  };
  A.payKeys = (pid) => {
    const p = D.providers.find(x => x.id === pid), e = p[UI.payEnv || 'prod'];
    requireSudo('Modifier les clés de ' + p.name, () => {
      modal(`<h2>Clés ${esc(p.name)} — ${envLabel()}</h2><p class="small muted mb">Laissez un champ vide pour conserver la clé actuelle. Une clé enregistrée ne pourra plus être affichée en clair.</p>
        ${p.fields.map(([k, l]) => `<div class="field"><label>${l}</label><input class="input" type="password" autocomplete="off" spellcheck="false" id="pk-${k}" placeholder="${e.keys[k] ? esc(e.keys[k].masked) + ' (actuelle)' : 'Coller la clé'}"></div>`).join('')}
        <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.payKeysSave('${pid}')">Chiffrer et enregistrer</button></div>`, { width: 520 });
    });
  };
  A.payKeysSave = (pid) => {
    const envKey = UI.payEnv || 'prod', p = D.providers.find(x => x.id === pid), e = p[envKey];
    const changed = [];
    for (const [k, l] of p.fields) {
      const inp = document.getElementById('pk-' + k), v = inp.value.trim();
      inp.value = '';
      if (!v) continue;
      if (v.length < 8) return toast(l + ' : clé trop courte', 'red');
      if (envKey === 'prod' && /test/i.test(v)) return toast(l + ' : clé de test détectée en Production', 'red');
      // En production : envoi HTTPS → chiffrement serveur (Crypt::encryptString) → seule la version masquée revient
      e.keys[k] = { masked: maskKey(v), savedAt: new Date().toLocaleDateString('fr-FR') };
      changed.push(l);
    }
    if (!changed.length) return toast('Aucune clé saisie');
    e.lastTest = null;
    audit('Clés API', `${p.name} (${envLabel()})`, 'Remplacée(s) : ' + changed.join(', '));
    closeModal(); toast('Clés chiffrées et enregistrées — testez la connexion', 'green'); render();
  };
  A.payDelete = (pid) => {
    const p = D.providers.find(x => x.id === pid), e = p[UI.payEnv || 'prod'];
    requireSudo('Supprimer les clés de ' + p.name, () => confirmModal('Supprimer les clés ?', `Les clés ${esc(p.name)} (${envLabel()}) seront effacées et le moyen de paiement désactivé.`, 'Supprimer', () => {
      e.keys = {}; e.enabled = false; e.lastTest = null; audit('Clés API', `${p.name} (${envLabel()})`, 'Clés supprimées'); toast('Clés supprimées'); render();
    }, true));
  };
  A.payTest = (pid) => {
    const envKey = UI.payEnv || 'prod', p = D.providers.find(x => x.id === pid), e = p[envKey];
    const missing = p.fields.filter(f => !e.keys[f[0]]).map(f => f[1]);
    modal(`<div class="center"><h2>Test de connexion</h2><p class="muted">${esc(p.name)} · ${envLabel()}</p><div class="spinner"></div><p class="xs muted">Appel d’authentification à l’API du fournisseur…</p></div>`, { locked: true });
    setTimeout(() => {
      const ok = !missing.length;
      e.lastTest = { ok, at: App.nowStamp(), msg: ok ? `Connexion réussie (${150 + Math.floor(Math.random() * 200)} ms)` : 'Clés manquantes : ' + missing.join(', ') };
      closeModal(); toast(e.lastTest.msg, ok ? 'green' : 'red'); render();
    }, 1100);
  };

  /* ================= S5 — Journal d'audit ================= */
  route('/s/audit', () => {
    const types = [...new Set(D.audit.map(a => a.action))].sort();
    const f = UI.auditType || 'all', q = (UI.auditSearch || '').toLowerCase();
    const list = D.audit.filter(a => (f === 'all' || a.action === f) && (!q || (a.who + a.target + a.detail).toLowerCase().includes(q)));
    return { layout: 'admin', nav: 'audit', title: 'Journal d’audit', html: `
      ${head('Journal d’audit', 'Actions sensibles : qui, quand, quoi. Le journal ne peut être ni modifié ni supprimé.', `<button class="btn ghost sm" onclick="App.downloadCSV('journal-audit.csv',[['Date','Auteur','Rôle','Action','Cible','Détail']].concat(App.D.audit.map(a=>[a.at,a.who,a.role,a.action,a.target,a.detail])))"><svg class=ic><use href=#i-download></use></svg> Exporter CSV</button>`)}
      <div class="toolbar"><select class="input" style="min-width:0" onchange="App.UI.auditType=this.value;App.render()"><option value="all">Toutes les actions</option>${types.map(t => `<option ${f === t ? 'selected' : ''}>${esc(t)}</option>`).join('')}</select>
        <input class="input" placeholder="Rechercher" value="${esc(UI.auditSearch || '')}" onchange="App.UI.auditSearch=this.value;App.render()"><span class="small muted">${list.length} entrée(s)</span></div>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Quand</th><th>Qui</th><th>Action</th><th>Cible</th><th>Détail</th></tr></thead><tbody>
        ${list.map(a => `<tr><td class="small" style="white-space:nowrap">${a.at}</td><td><b class="small">${esc(a.who)}</b><div class="xs muted">${roleLabel(a.role)}</div></td><td><span class="badge ${/Suppression|Purge|Désactivation/.test(a.action) ? 'red' : /Clés|paiement/.test(a.action) ? 'orange' : ''}">${esc(a.action)}</span></td><td class="small">${esc(a.target)}</td><td class="small muted">${esc(a.detail)}</td></tr>`).join('') || '<tr><td colspan="5" class="muted center">Aucune entrée.</td></tr>'}
      </tbody></table></div>` };
  });
})();
