/* =========================================================
   Écrans communs (C1–C4), Convive (U1–U9) et Parent (P1–P6)
   ========================================================= */
(function () {
  const { D, UI, A, esc, fcfa, dish, user, me, fullName, initials, fmtLong, fmtShort, fmtDM, dayOf,
    bookingOpen, conflicts, activeRes, remaining, slotCount, slotLabel, statusBadge, stars, allergenBadges,
    resItemsText, mainDish, missingFields, typeLabel, addTx, notify, checkLowBalance, qrSvg, hbars, modal, closeModal, confirmModal, toast,
    simulatePayment, go, render, homeFor, bell, route } = App;
  const ALL = D.ALLERGENS, CATS = D.CATS;

  const dishRow = (d, u, opts = {}) => {
    const conf = conflicts(d, u);
    const left = opts.date ? remaining(opts.date, d.id) : 99;
    const out = left <= 0;
    return `<div class="dish-row ${out ? 'disabled' : ''} ${opts.selected ? 'selected' : ''}" onclick="${opts.onclick || `App.go('#/u/dish/${d.id}')`}">
      ${App.dishImg(d, 'md')}
      <div class="info"><h4>${esc(d.name)}</h4>
        <div class="row wrap" style="gap:6px;margin-top:4px">${stars(d.rating)} ${d.allergens.length ? allergenBadges(d, u) : '<span class="badge green">Sans allergène majeur</span>'}</div>
        ${conf.length ? `<div class="xs" style="color:var(--red);margin-top:4px;font-weight:600"><svg class=ic><use href=#i-alert></use></svg> Incompatible : ${conf.map(a => ALL[a]).join(', ')}</div>` : ''}
        ${out ? '<div class="xs bold" style="color:var(--red);margin-top:4px">Épuisé</div>' : left < 15 ? `<div class="xs bold" style="color:var(--orange);margin-top:4px">Plus que ${left} portions</div>` : ''}
      </div>
      ${opts.qty ? `<div style="text-align:right"><div class="price">${fcfa(d.price)}</div><div class="stepper" onclick="event.stopPropagation()"><button onclick="App.A.qty('${d.id}',-1)" aria-label="Moins">−</button><b>${opts.qty}</b><button onclick="App.A.qty('${d.id}',1)" aria-label="Plus">+</button></div></div>` : `<div class="price">${fcfa(d.price)}</div>`}</div>`;
  };

  /* ================= C1 — Accueil public ================= */
  route('/', () => {
    const today = dayOf(D.NOW.date);
    const items = today.plat.concat(today.entree.slice(0, 1)).slice(0, 3).map(dish);
    return { layout: 'public', title: 'Accueil', html: `
      <section class="hero">
        <div>
          <span class="badge">Cantine scolaire & d’entreprise</span>
          <h1 style="margin-top:12px">Réservez, payez,<br><em>mangez sans attendre.</em></h1>
          <p class="lead">Consultez le menu de la semaine, réservez votre repas depuis votre téléphone, payez avec Wave ou Orange Money et récupérez votre plateau en scannant un QR code.</p>
          <div class="row wrap"><a class="btn" href="#/register">Créer un compte</a><a class="btn ghost" href="#/login">Se connecter</a><button class="btn light" onclick="App.A.weekPreview()">Voir le menu de la semaine</button></div>
        </div>
        <div class="hero-visual">
          <div class="row between mb"><b>Menu du ${fmtLong(D.NOW.date).toLowerCase()}</b><span class="badge" style="background:var(--sky);color:var(--ink)">Aujourd’hui</span></div>
          <div class="today">${items.map(d => `<div class="item">${App.dishImg(d, 'sm')}<div class="grow"><b>${esc(d.name)}</b><div class="xs" style="color:#aaa">${CATS[d.cat]} · ${stars(d.rating)}</div></div><b>${fcfa(d.price)}</b></div>`).join('')}</div>
          <p class="xs mt" style="color:#aaa">Réservations ouvertes jusqu’à ${D.cutoff.replace(':', 'h')} le jour même.</p>
        </div>
      </section>
      <section class="benefits">
        <div class="benefit"><div class="ico">⏱️</div><h3>Moins d’attente</h3><p class="muted">Créneaux de passage et QR code : on présente son téléphone, on est servi.</p></div>
        <div class="benefit"><div class="ico"><svg class=ic><use href=#i-leaf></use></svg></div><h3>Moins de gaspillage</h3><p class="muted">La cuisine connaît le nombre exact de repas à préparer chaque jour.</p></div>
        <div class="benefit"><div class="ico"><svg class=ic><use href=#i-alert></use></svg></div><h3>Allergies prises en compte</h3><p class="muted">Les plats incompatibles avec votre profil sont signalés automatiquement.</p></div>
      </section>` };
  });
  A.weekPreview = () => {
    modal(`<h2>Menu du 23 au 27 novembre</h2>${D.week.map(d => `<div class="mb"><b>${fmtLong(d.date)}</b><div class="small muted">${d.plat.map(id => dish(id).emoji + ' ' + dish(id).name).join(' · ')}</div></div>`).join('')}<div class="actions"><button class="btn ghost" onclick="App.closeModal()">Fermer</button><a class="btn" href="#/register" onclick="App.closeModal()">Réserver mon repas</a></div>`);
  };

  /* ================= C2 — Inscription (Élève, Personnel ou Parent) ================= */
  const REG_TYPES = { eleve: ['<svg class=ic><use href=#i-graduation></use></svg>', 'Élève', 'Je mange à la cantine de mon établissement'], personnel: ['<svg class=ic><use href=#i-briefcase></use></svg>', 'Personnel', 'Enseignant, administration, service…'], parent: ['<svg class=ic><use href=#i-users></use></svg>', 'Parent', 'Je gère le compte de mes enfants'] };
  route('/register', () => {
    const r = UI.reg, conv = r.profile === 'eleve' || r.profile === 'personnel';
    let body = '';
    if (r.step === 1) {
      body = `<p class="muted mb">Quel type de compte ?</p><div class="choice-cards">
        ${Object.entries(REG_TYPES).map(([k, [ic, l, d]]) => `<button class="choice ${r.profile === k ? 'active' : ''}" onclick="App.UI.reg.profile='${k}';App.render()"><span class="ico">${ic}</span><span><b>${l}</b><br><span class="small muted">${d}</span></span></button>`).join('')}
      </div><button class="btn block mt" ${r.profile ? '' : 'disabled'} onclick="App.UI.reg.step=2;App.render()">Continuer</button>`;
    } else if (r.step === 2) {
      const v = r.data, e = r.errors || {};
      const f = (id, label, type = 'text', ph = '') => `<div class="field"><label for="${id}">${label}</label><input class="input" id="${id}" type="${type}" placeholder="${ph}" value="${esc(v[id] || '')}">${e[id] ? `<span class="error">${e[id]}</span>` : ''}</div>`;
      const typeFields = r.profile === 'eleve'
        ? `<div class="cat-label">Informations scolaires (facultatif)</div>
           <div class="field"><label for="group">Classe</label><select class="input" id="group"><option value="">Je l’indiquerai plus tard</option>${D.CLASSES.map(c => `<option ${v.group === c ? 'selected' : ''}>${c}</option>`).join('')}</select></div>${f('matricule', 'Matricule', 'text', 'VAT-0000')}`
        : r.profile === 'personnel'
          ? `<div class="cat-label">Informations professionnelles (facultatif)</div>${f('poste', 'Poste', 'text', 'Ex. Professeur de français')}`
          : `<div class="alert sky">Vous lierez vos enfants ensuite en scannant le QR code affiché dans leur profil.</div>`;
      body = `<span class="badge mb">${REG_TYPES[r.profile][0]} Compte ${REG_TYPES[r.profile][1]}</span>
        <div class="row mt" style="gap:10px;align-items:flex-start"><div class="grow">${f('first', 'Prénom *', 'text', 'Awa')}</div><div class="grow">${f('last', 'Nom *', 'text', 'Koné')}</div></div>
        ${f('phone', 'Téléphone *', 'tel', '07 00 00 00 00')}${f('email', 'E-mail *', 'email', 'vous@exemple.ci')}${f('password', 'Mot de passe *', 'password', '8 caractères minimum')}
        ${typeFields}
        <div class="row mt"><button class="btn ghost" onclick="App.A.regKeep();App.UI.reg.step=1;App.render()">Retour</button><button class="btn grow" onclick="App.A.regNext()">${conv ? 'Continuer' : 'Créer mon compte'}</button></div>`;
    } else {
      body = `<p class="muted mb">Indiquez votre régime et vos allergies : les plats incompatibles seront signalés. Vous pourrez les modifier plus tard.</p>
        <div class="cat-label">Régime</div><div class="check-grid">${Object.entries(D.DIETS).map(([k, l]) => `<label class="check"><input type="checkbox" ${r.diets.includes(k) ? 'checked' : ''} onchange="App.A.toggleArr(App.UI.reg.diets,'${k}')">${l}</label>`).join('')}</div>
        <div class="cat-label">Allergies</div><div class="check-grid">${Object.entries(ALL).map(([k, l]) => `<label class="check"><input type="checkbox" ${r.allergies.includes(k) ? 'checked' : ''} onchange="App.A.toggleArr(App.UI.reg.allergies,'${k}')">${l}</label>`).join('')}</div>
        <div class="row mt"><button class="btn ghost" onclick="App.UI.reg.step=2;App.render()">Retour</button><button class="btn grow" onclick="App.A.regFinish()">Créer mon compte</button></div>`;
    }
    const total = conv ? 3 : 2;
    return { layout: 'public', title: 'Créer un compte', html: `<div class="auth-wrap"><h1>Créer un compte</h1><p class="muted">Étape ${Math.min(r.step, total)} sur ${total}</p>
      <div class="steps">${Array.from({ length: total }, (_, i) => `<span class="${i < r.step ? 'on' : ''}"></span>`).join('')}</div>${body}
      <p class="center small mt">Déjà inscrit ? <a href="#/login">Se connecter</a></p></div>` };
  });
  A.toggleArr = (arr, k) => { const i = arr.indexOf(k); if (i >= 0) arr.splice(i, 1); else arr.push(k); };
  A.regKeep = () => { ['first', 'last', 'phone', 'email', 'password', 'group', 'matricule', 'poste'].forEach(id => { const el = document.getElementById(id); if (el) UI.reg.data[id] = el.value.trim(); }); };
  A.regNext = () => {
    const r = UI.reg, v = r.data, e = {};
    A.regKeep();
    if (!v.first) e.first = 'Le prénom est obligatoire';
    if (!v.last) e.last = 'Le nom est obligatoire';
    if (!/^[0-9 +]{8,}$/.test(v.phone || '')) e.phone = 'Numéro invalide';
    if (!/^\S+@\S+\.\S+$/.test(v.email || '')) e.email = 'E-mail invalide';
    else if (D.users.some(u => u.email === v.email)) e.email = 'Un compte existe déjà avec cet e-mail';
    if ((v.password || '').length < 8) e.password = '8 caractères minimum';
    r.errors = e;
    if (Object.keys(e).length) return render();
    if (r.profile === 'parent') return A.regFinish();
    r.step = 3; render();
  };
  A.regFinish = () => {
    const r = UI.reg, v = r.data, id = 'n' + Date.now();
    const base = { id, role: r.profile === 'parent' ? 'parent' : 'convive', status: 'active', photo: null, first: v.first, last: v.last, email: v.email, phone: v.phone, notify: { push: true, sms: false, email: true } };
    if (base.role === 'convive') Object.assign(base, {
      type: r.profile, group: r.profile === 'eleve' ? (v.group || null) : null, matricule: r.profile === 'eleve' ? (v.matricule || null) : null,
      poste: r.profile === 'personnel' ? (v.poste || null) : null, classPeriod: D.settings.period.id,
      balance: 0, allergies: r.allergies.slice(), diets: r.diets.slice(), parentId: null, threshold: 1000,
      linkCode: r.profile === 'eleve' ? newLinkCode(v.first) : null
    });
    else base.children = [];
    D.users.push(base);
    D.session = { userId: id, role: base.role };
    UI.reg = { step: 1, profile: null, data: {}, allergies: [], diets: [] };
    toast('Compte créé. Bienvenue, ' + v.first + ' !', 'green');
    go(homeFor());
  };
  const newLinkCode = (first) => (first.normalize('NFD').replace(/[^A-Za-z]/g, '').slice(0, 3).padEnd(3, 'X') + '-' + Math.random().toString(36).slice(2, 5)).toUpperCase();

  /* ================= C3 — Connexion ================= */
  route('/login', () => ({ layout: 'public', title: 'Connexion', html: `<div class="auth-wrap">
    <h1>Connexion</h1><p class="muted mb">Accédez à votre espace cantine.</p>
    ${UI.loginError ? `<div class="alert red mb">${UI.loginError}</div>` : ''}
    <form onsubmit="event.preventDefault();App.A.login()">
      <div class="field"><label for="lg-email">E-mail ou téléphone</label><input class="input" id="lg-email" value="${esc(UI.loginEmail || '')}" placeholder="vous@exemple.ci"></div>
      <div class="field"><label for="lg-pass">Mot de passe</label><input class="input" id="lg-pass" type="password" placeholder="••••••••"></div>
      <div class="row between mb"><label class="row small"><input type="checkbox" checked> Rester connecté</label><a class="small" href="javascript:App.toast('Un lien de réinitialisation a été envoyé par e-mail.')">Mot de passe oublié ?</a></div>
      <button class="btn block">Se connecter</button>
    </form>
    <p class="center small mt">Pas encore de compte ? <a href="#/register">Créer un compte</a></p>
    <div class="demo-accounts"><b class="small">Comptes de démonstration</b><p class="xs muted">Cliquez pour préremplir (mot de passe : n’importe lequel).</p>
      <div class="row wrap">${[['u1', 'Élève'], ['u4', 'Personnel'], ['p1', 'Parent'], ['a1', 'Admin'], ['s1', 'Super admin'], ['k1', 'Comptoir']].map(([id, l]) => `<button class="btn sm ghost" onclick="document.getElementById('lg-email').value='${user(id).email}';document.getElementById('lg-pass').value='demo1234'">${l}</button>`).join('')}</div></div>
  </div>` }));
  A.login = () => {
    const id = document.getElementById('lg-email').value.trim().toLowerCase();
    const pw = document.getElementById('lg-pass').value;
    UI.loginEmail = id;
    const u = D.users.find(x => (x.email && x.email.toLowerCase() === id) || (x.phone && x.phone.replace(/ /g, '') === id.replace(/ /g, '')));
    if (!u || !pw || u.status === 'deleted') { UI.loginError = 'Identifiants incorrects.'; return render(); }
    if (u.status === 'trashed' || u.disabled) { UI.loginError = 'Ce compte est désactivé. Contactez l’administration de la cantine.'; return render(); }
    UI.loginError = null;
    A.demoLogin(u.id);
  };

  /* ================= C4 — Profil (photo, champs selon le type, liaison parent, suppression) ================= */
  route('/profile', () => {
    const u = me();
    if (u.role !== 'convive' && u.role !== 'parent') return { redirect: homeFor() };
    const conv = u.role === 'convive', el = conv && u.type === 'eleve', staff = conv && u.type === 'personnel';
    const miss = missingFields(u);
    const req = D.deletionRequests.find(x => x.userId === u.id && x.status === 'pending');
    const refused = D.deletionRequests.find(x => x.userId === u.id && x.status === 'refused');
    const classOpts = D.CLASSES.concat(u.group && !D.CLASSES.includes(u.group) ? [u.group] : []);
    const info = [['Prénom', u.first], ['Nom', u.last], ['Type de compte', conv ? typeLabel(u) : 'Parent']]
      .concat(el ? [['Classe', u.group || '—'], ['Matricule', u.matricule || '—']] : staff ? [['Poste', u.poste || '—']] : [['Enfants liés', u.children.map(k => user(k).first).join(', ') || '—']])
      .concat([['Téléphone', u.phone || '—'], ['E-mail', u.email || '—']]);
    return { layout: 'mobile', title: 'Mon profil', tab: 'profile', html: `
      <div class="photo-block mb">
        ${App.avatar(u, 'xl', u.photoShape === 'square' ? 'border-radius:18px' : '')}
        <div class="center"><h2>${esc(fullName(u))}</h2><p class="muted small">${conv ? typeLabel(u) + (el && u.group ? ' · ' + esc(u.group) : '') + (staff && u.poste ? ' · ' + esc(u.poste) : '') : 'Compte parent'}</p></div>
        ${miss.length ? `<button class="incomplete" onclick="document.getElementById('pf-edit').scrollIntoView({behavior:'smooth'})" title="Non bloquant">Profil incomplet : ${miss.join(', ')}</button>` : ''}
        <div class="photo-actions">${u.photo
          ? `<button class="btn sm light" onclick="App.A.photoMenu()">Modifier la photo</button><button class="btn sm danger" onclick="App.A.photoDelete()">Supprimer</button>`
          : `<button class="btn sm light" onclick="App.A.photo('gallery')"><svg class=ic><use href=#i-image></use></svg> Galerie</button><button class="btn sm light" onclick="App.A.photo('camera')"><svg class=ic><use href=#i-camera></use></svg> Appareil photo</button>`}</div>
        <p class="xs muted">JPG, PNG ou WebP · 5 Mo maximum · recadrée et compressée automatiquement</p>
      </div>
      <div class="section-title"><h3>Mes informations</h3></div>
      <div class="card info-list">${info.map(([k, v]) => `<div class="li"><span>${k}</span><b>${esc(v)}</b></div>`).join('')}</div>
      <div class="section-title" id="pf-edit"><h3>Modifier</h3></div>
      <div class="card">
        <div class="field"><label>Prénom *</label><input class="input" id="pf-first" value="${esc(u.first)}"><span class="error" id="err-first"></span></div>
        <div class="field"><label>Nom *</label><input class="input" id="pf-last" value="${esc(u.last)}"><span class="error" id="err-last"></span></div>
        ${el ? `<div class="field"><label>Classe <span class="muted">(facultatif)</span></label><select class="input" id="pf-group"><option value="">Non renseignée</option>${classOpts.map(c => `<option ${u.group === c ? 'selected' : ''}>${c}</option>`).join('')}</select></div>
          <div class="field"><label>Matricule <span class="muted">(facultatif)</span></label><input class="input" id="pf-matricule" value="${esc(u.matricule || '')}" placeholder="VAT-0000"></div>` : ''}
        ${staff ? `<div class="field"><label>Poste <span class="muted">(facultatif)</span></label><input class="input" id="pf-poste" value="${esc(u.poste || '')}" placeholder="Ex. Comptable"></div>` : ''}
        <div class="field"><label>Téléphone</label><input class="input" id="pf-phone" value="${esc(u.phone || '')}"></div>
        <div class="field"><label>E-mail</label><input class="input" id="pf-email" value="${esc(u.email || '')}"></div>
        ${conv ? '<p class="xs muted">Les champs facultatifs peuvent être complétés, modifiés ou vidés à tout moment.</p>' : ''}
      </div>
      ${el ? `<div class="section-title"><h3>Lier un parent</h3></div>
        <div class="card center"><div class="qr-mini">${qrSvg('LINK:' + u.linkCode)}</div><p class="small mt">Votre parent scanne ce QR code depuis son application <b>(Enfants › Ajouter un enfant)</b>.</p>
        <p class="xs muted mt">Code de secours : <b>${u.linkCode}</b> · la liaison n’utilise pas le matricule.</p>
        <button class="btn sm ghost mt" onclick="App.A.newLink()">Générer un nouveau QR code</button>
        ${u.parentId ? `<p class="small mt"><svg class=ic><use href=#i-check></use></svg> Lié à <b>${esc(fullName(user(u.parentId)))}</b></p>` : ''}</div>` : ''}
      ${conv ? `<div class="section-title"><h3>Régime et allergies</h3></div><div class="card">
        <div class="check-grid">${Object.entries(D.DIETS).map(([k, l]) => `<label class="check"><input type="checkbox" ${u.diets.includes(k) ? 'checked' : ''} onchange="App.A.toggleArr(App.me().diets,'${k}')">${l}</label>`).join('')}
        ${Object.entries(ALL).map(([k, l]) => `<label class="check"><input type="checkbox" ${u.allergies.includes(k) ? 'checked' : ''} onchange="App.A.toggleArr(App.me().allergies,'${k}')">${l}</label>`).join('')}</div>
        <p class="xs muted mt">Les plats contenant ces allergènes sont signalés dans le menu.</p></div>
        <div class="section-title"><h3>Alerte de solde bas</h3></div><div class="card"><div class="field" style="margin:0"><label>M’avertir sous</label><select class="input" id="pf-th">${[500, 1000, 2000, 3000].map(v => `<option value="${v}" ${u.threshold === v ? 'selected' : ''}>${fcfa(v)}</option>`).join('')}</select></div></div>` : ''}
      <div class="section-title"><h3>Préférences</h3></div>
      <div class="stack">
        <div class="field" style="margin:0"><label for="pf-lang">Langue de l’interface</label><select class="input" id="pf-lang" onchange="App.A.setLang(this.value)"><option value="fr" ${(u.lang || 'fr') === 'fr' ? 'selected' : ''}>Français</option><option value="en" ${u.lang === 'en' ? 'selected' : ''}>English</option></select></div>
        ${['push', 'sms', 'email'].map(k => `<div class="toggle"><span class="grow">Notifications ${{ push: 'push', sms: 'SMS', email: 'e-mail' }[k]}</span><button class="switch ${u.notify[k] ? 'on' : ''}" onclick="App.me().notify['${k}']=!App.me().notify['${k}'];App.render()" aria-label="${k}"></button></div>`).join('')}
      </div>
      <button class="btn block mt" onclick="App.A.saveProfile()">Enregistrer</button>
      <div class="btn-pair mt"><button class="btn ghost" onclick="App.toast('E-mail de changement de mot de passe envoyé')"><svg class=ic><use href=#i-key></use></svg> Changer le mot de passe</button><button class="btn ghost" onclick="App.A.logout()"><svg class=ic><use href=#i-logout></use></svg> Se déconnecter</button></div>
      <div class="section-title"><h3>Suppression du compte</h3></div>
      <div class="card mb">${req
        ? `<div class="alert orange"><svg class=ic><use href=#i-clock></use></svg> <span>Demande envoyée le ${fmtShort(req.date)}. Elle est en cours d’examen par le super administrateur.</span></div><button class="btn sm ghost mt" onclick="App.A.cancelDeletion('${req.id}')">Annuler ma demande</button>`
        : `${refused ? `<div class="alert sky mb">Votre précédente demande a été refusée : « ${esc(refused.answer)} »</div>` : ''}<p class="small muted">Votre demande sera transmise au super administrateur. Vos données sont d’abord conservées en corbeille, puis effacées définitivement.</p><button class="btn danger block mt" onclick="App.A.requestDeletion()">Demander la suppression de mon compte</button>`}</div>` };
  });
  A.photo = (source) => App.pickImage(source, (f) => App.openCropper(f, {
    title: 'Recadrer ma photo', allowShape: true, shape: me().photoShape || 'circle', size: 400,
    onDone: (data, info) => { const u = me(); u.photo = data; u.photoShape = info.shape; toast(`Photo enregistrée (${info.label})`, 'green'); render(); }
  }));
  A.setLang = (lang) => { me().lang = lang; render(); toast(lang === 'en' ? 'Language: English' : 'Langue : Français', 'green'); };
  A.photoMenu = () => modal(`<h2>Modifier la photo</h2><div class="stack"><button class="btn ghost block" onclick="App.closeModal();App.A.photo('gallery')"><svg class=ic><use href=#i-image></use></svg> Choisir dans la galerie</button><button class="btn ghost block" onclick="App.closeModal();App.A.photo('camera')"><svg class=ic><use href=#i-camera></use></svg> Prendre une photo</button></div><div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button></div>`, { noFocus: true });
  A.photoDelete = () => confirmModal('Supprimer la photo ?', 'Vos initiales seront affichées à la place.', 'Supprimer la photo', () => { me().photo = null; toast('Photo supprimée'); render(); }, true);
  A.newLink = () => confirmModal('Générer un nouveau QR code ?', 'L’ancien QR code ne fonctionnera plus. Les parents déjà liés restent liés.', 'Générer', () => { const u = me(); u.linkCode = newLinkCode(u.first); toast('Nouveau QR code généré', 'green'); render(); });
  A.saveProfile = () => {
    const u = me(), val = (id) => { const el = document.getElementById(id); return el ? el.value.trim() : undefined; };
    const first = val('pf-first'), last = val('pf-last');
    document.getElementById('err-first').textContent = first ? '' : 'Le prénom est obligatoire';
    document.getElementById('err-last').textContent = last ? '' : 'Le nom est obligatoire';
    if (!first || !last) return toast('Nom et prénom sont obligatoires', 'red');
    u.first = first; u.last = last; u.phone = val('pf-phone'); u.email = val('pf-email');
    if (val('pf-group') !== undefined) { const g = val('pf-group') || null; if (g !== u.group) u.classPeriod = D.settings.period.id; u.group = g; }
    if (val('pf-matricule') !== undefined) u.matricule = val('pf-matricule') || null;
    if (val('pf-poste') !== undefined) u.poste = val('pf-poste') || null;
    const th = document.getElementById('pf-th'); if (th) u.threshold = +th.value;
    toast('Profil enregistré', 'green'); render();
  };
  A.requestDeletion = () => modal(`<h2>Supprimer mon compte</h2><p class="small muted mb">Votre demande sera examinée par le super administrateur. Votre solde éventuel (${me().balance != null ? fcfa(me().balance) : '—'}) devra être régularisé avec l’administration.</p>
    <div class="field"><label>Motif <span class="muted">(facultatif)</span></label><textarea class="input" id="del-reason" rows="3" placeholder="Ex. Je quitte l’établissement"></textarea></div>
    <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn danger" onclick="App.A.requestDeletionOk()">Envoyer la demande</button></div>`);
  A.requestDeletionOk = () => {
    const u = me();
    D.deletionRequests.unshift({ id: 'dr' + Date.now(), userId: u.id, reason: document.getElementById('del-reason').value.trim(), date: D.NOW.date, status: 'pending' });
    D.users.filter(x => x.role === 'superadmin').forEach(s => notify(s.id, '<svg class=ic><use href=#i-mail></use></svg>', 'Demande de suppression', `${fullName(u)} demande la suppression de son compte.`, '#/s/requests'));
    closeModal(); toast('Demande transmise au super administrateur', 'green'); render();
  };
  A.cancelDeletion = (id) => { D.deletionRequests.find(x => x.id === id).status = 'cancelled'; toast('Demande annulée'); render(); };

  /* ================= U1 — Accueil convive ================= */
  route('/u/home', () => {
    const u = me(), today = dayOf(D.NOW.date);
    const res = activeRes(u.id, D.NOW.date);
    const unread = D.notifications.filter(n => n.userId === u.id && !n.read).length;
    let todayCard;
    if (res) {
      todayCard = `<div class="card"><div class="row between"><b>Mon repas d’aujourd’hui</b>${statusBadge(res.status)}</div>
        <div class="row mt">${App.dishImg(mainDish(res), 'md')}<div class="grow"><b>${esc(mainDish(res).name)}</b><div class="small muted">${esc(resItemsText(res))}</div><div class="small mt"><svg class=ic><use href=#i-clock></use></svg> Créneau <b>${slotLabel(res.slot)}</b></div></div></div>
        ${res.status === 'payee' ? `<button class="btn block mt" onclick="App.go('#/u/resa/${res.id}')">Afficher mon QR code</button>` : res.status === 'servie' && !res.rated ? `<button class="btn light block mt" onclick="App.go('#/u/rate/${res.id}')"><svg class=ic-star><use href=#i-star></use></svg> Noter ce repas</button>` : ''}</div>`;
    } else if (bookingOpen(D.NOW.date)) {
      todayCard = `<div class="card" style="border:2px dashed var(--sky)"><b>Vous n’avez pas encore réservé</b><p class="small muted">Il reste jusqu’à ${D.cutoff.replace(':', 'h')} pour réserver le repas d’aujourd’hui.</p><button class="btn block mt" onclick="App.A.startBook('${D.NOW.date}')">Réserver maintenant</button></div>`;
    } else {
      const next = D.week.find(d => d.date > D.NOW.date && !activeRes(u.id, d.date));
      todayCard = `<div class="card"><b>Réservations du jour closes</b><p class="small muted">Les réservations ferment à ${D.cutoff.replace(':', 'h')}.${next ? ' Pensez à réserver pour ' + fmtLong(next.date).toLowerCase() + '.' : ''}</p>${next ? `<button class="btn light block mt" onclick="App.A.startBook('${next.date}')">Réserver pour ${fmtShort(next.date)}</button>` : ''}</div>`;
    }
    const sug = suggestion(u);
    const P = D.settings.period;
    let classQ = '';
    if (u.type === 'eleve' && u.classPeriod !== P.id) classQ = u.group
      ? `<div class="card banner-q mb"><span class="badge">Nouvelle période · ${P.label}</span><p class="mt">Êtes-vous toujours en <b>${esc(u.group)}</b> ?</p><div class="row mt"><button class="btn sm" onclick="App.A.classConfirm()">Oui, c’est bien ça</button><button class="btn sm ghost" onclick="App.A.classEdit()">Corriger</button></div></div>`
      : `<div class="card banner-q mb"><span class="badge">Nouvelle période · ${P.label}</span><p class="mt small">Voulez-vous indiquer votre classe ? Cela aide la cantine à mieux prévoir les repas.</p><div class="row mt"><button class="btn sm" onclick="App.A.classEdit()">Ajouter ma classe</button><button class="btn sm ghost" onclick="App.A.classConfirm(true)">Ignorer</button></div></div>`;
    return { layout: 'mobile', title: `Bonjour, ${esc(u.first)}`, tab: 'home', right: bell(unread, '#/u/notifs'), html: `
      ${classQ}<div class="balance-card"><div class="small" style="color:#bbb">Mon solde</div><div class="amount">${App.num(u.balance)} <small>FCFA</small></div>
        <div class="row"><button class="btn sky sm" onclick="App.UI.topupReturn='#/u/home';App.go('#/u/topup')">+ Recharger</button><button class="btn sm" style="background:#2b2b28" onclick="App.go('#/u/wallet')">Historique</button></div></div>
      ${u.balance < u.threshold ? `<div class="alert orange mt"><svg class=ic><use href=#i-alert></use></svg> Solde bas : rechargez pour pouvoir réserver.</div>` : ''}
      <div class="mt">${todayCard}</div>
      <div class="section-title"><h3>Plats du jour</h3><a class="small" href="#/u/menu" onclick="App.UI.menuDay='${D.NOW.date}'">Tout le menu ›</a></div>
      <div class="hscroll">${today.plat.concat(today.entree, today.dessert).map(id => { const d = dish(id), c = conflicts(d, u); return `<div class="mini-dish" onclick="App.UI.menuDay='${D.NOW.date}';App.go('#/u/dish/${d.id}')">${App.dishImg(d, '')}<div class="info"><b class="small">${esc(d.name)}</b><div class="xs">${fcfa(d.price)}</div>${c.length ? `<span class="badge red xs mt"><svg class=ic><use href=#i-alert></use></svg> ${ALL[c[0]]}</span>` : `<div class="xs">${stars(d.rating)}</div>`}</div></div>`; }).join('')}</div>
      ${sug ? `<div class="section-title"><h3>Suggestion pour vous</h3><span class="badge"><svg class=ic><use href=#i-sparkles></use></svg> IA</span></div>
      <div class="card flat"><div class="row">${App.dishImg(sug.d, 'md')}<div class="grow"><b>${esc(sug.d.name)}</b><div class="small muted">${fmtLong(sug.date)}</div><div class="xs mt">${sug.reason}</div></div></div>
      <button class="btn light block mt" onclick="App.A.startBook('${sug.date}','${sug.d.id}')">Réserver ce plat</button></div>` : ''}` };
  });
  A.classConfirm = (skip) => { const u = me(); u.classPeriod = D.settings.period.id; toast(skip ? 'D’accord. Vous pourrez l’ajouter depuis votre profil.' : 'Merci, classe confirmée', skip ? '' : 'green'); render(); };
  A.classEdit = () => {
    const u = me();
    modal(`<h2>Ma classe</h2><p class="small muted mb">${D.settings.period.label}</p><div class="field"><label>Classe</label><select class="input" id="cls">${['<option value="">Je préfère ne pas l’indiquer</option>'].concat(D.CLASSES.map(c => `<option ${u.group === c ? 'selected' : ''}>${c}</option>`)).join('')}</select></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.classSave()">Enregistrer</button></div>`, { noFocus: true });
  };
  A.classSave = () => { const u = me(); u.group = document.getElementById('cls').value || null; u.classPeriod = D.settings.period.id; closeModal(); toast('Classe mise à jour', 'green'); render(); };
  // Suggestion « IA » : règle simple (préférences passées + note + compatibilité)
  function suggestion(u) {
    const liked = D.reservations.filter(r => r.userId === u.id && r.status === 'servie').flatMap(r => r.items);
    let best = null;
    D.week.forEach(day => {
      if (!bookingOpen(day.date) || activeRes(u.id, day.date)) return;
      day.plat.forEach(id => {
        const d = dish(id); if (conflicts(d, u).length || remaining(day.date, id) <= 0) return;
        const score = d.rating + (liked.includes(id) ? 0.6 : 0) + (u.diets.includes('vegetarien') && d.diets.includes('vegetarien') ? 1 : 0);
        if (!best || score > best.score) best = { d, date: day.date, score, reason: (liked.includes(id) ? 'Vous l’avez déjà apprécié. ' : '') + `Noté ${d.rating.toFixed(1).replace('.', ',')}/5 et compatible avec votre profil${u.allergies.length ? ' (sans ' + u.allergies.map(a => ALL[a].toLowerCase()).join(', ') + ')' : ''}.` };
      });
    });
    return best;
  }

  /* ================= U2 — Menu de la semaine (filtres jour, catégorie, régime) ================= */
  const menuReviews = (date) => D.reviews.filter(v => v.date === date && v.status === 'visible' && v.type !== 'dish');
  const dishReviews = (id) => D.reviews.filter(v => v.type === 'dish' && v.dishId === id && v.status === 'visible');
  const canReview = (u, date) => { const r = activeRes(u.id, date); return r && date <= D.NOW.date && !D.reviews.some(v => v.resId === r.id) ? r : null; };
  route('/u/menu', () => {
    const u = me(), day = dayOf(UI.menuDay) || dayOf(D.NOW.date);
    const res = activeRes(u.id, day.date);
    const cat = UI.menuCat || 'all', diet = UI.menuDiet || 'all';
    const list = (c) => day[c].map(dish).filter(d => !(UI.compatOnly && conflicts(d, u).length) && (diet === 'all' || d.diets.includes(diet)));
    const cats = ['entree', 'plat', 'dessert', 'boisson'].filter(c => cat === 'all' || c === cat);
    const shown = cats.reduce((n, c) => n + list(c).length, 0);
    const revs = menuReviews(day.date), avg = revs.length ? revs.reduce((s, v) => s + v.stars, 0) / revs.length : 0;
    const rateRes = canReview(u, day.date);
    let cta;
    if (res) cta = `<button class="btn light block" onclick="App.go('#/u/resa/${res.id}')"><svg class=ic><use href=#i-check></use></svg> Déjà réservé — voir ma réservation</button>`;
    else if (bookingOpen(day.date)) cta = `<button class="btn block" onclick="App.A.startBook('${day.date}')">Réserver pour ${fmtShort(day.date)}</button>`;
    else cta = `<button class="btn block" disabled>${day.date < D.NOW.date ? 'Jour passé' : 'Réservations closes à ' + D.cutoff.replace(':', 'h')}</button>`;
    const chip = (key, val, label) => `<button class="chip ${(UI[key] || 'all') === val ? 'active' : ''}" onclick="App.UI.${key}='${val}';App.render()">${label}</button>`;
    return { layout: 'mobile', title: 'Menu de la semaine', tab: 'menu', html: `
      <div class="row between mb"><button class="btn sm ghost" onclick="App.toast('Semaine précédente : archive')" aria-label="Semaine précédente">${App.ic('chevron-left')}</button><b>Semaine du 23 nov.</b><button class="btn sm ghost" onclick="App.toast('Le menu du 30 nov. n’est pas encore publié')" aria-label="Semaine suivante">${App.ic('chevron-right')}</button></div>
      <div class="days">${D.week.map(d => `<button class="day ${d.date === day.date ? 'active' : ''} ${d.date === D.NOW.date ? 'today' : ''}" onclick="App.UI.menuDay='${d.date}';App.render()"><small>${d.label}</small><b>${d.date.slice(8)}</b></button>`).join('')}</div>
      <div class="hscroll mb" style="gap:8px">${chip('menuCat', 'all', 'Tout')}${Object.entries(CATS).map(([k, l]) => chip('menuCat', k, l + 's')).join('')}</div>
      <div class="hscroll mb" style="gap:8px">${chip('menuDiet', 'all', 'Tous régimes')}${Object.entries(D.DIETS).map(([k, l]) => chip('menuDiet', k, (k === 'vegetarien' ? '<svg class=ic><use href=#i-leaf></use></svg> ' : '') + l)).join('')}</div>
      <div class="toggle"><span class="grow small"><b>Compatible avec mes allergies</b><br><span class="muted xs">${u.allergies.length ? 'Masque les plats contenant : ' + u.allergies.map(a => ALL[a]).join(', ') : 'Aucune allergie déclarée'}</span></span><button class="switch ${UI.compatOnly ? 'on' : ''}" onclick="App.UI.compatOnly=!App.UI.compatOnly;App.render()" aria-label="Filtrer"></button></div>
      <div class="row between mt"><h3>${fmtLong(day.date)}</h3><button class="btn sm ghost" onclick="App.A.showMenuReviews('${day.date}')">${revs.length ? `${stars(avg)} · ${revs.length} avis` : 'Avis'}</button></div>
      ${shown ? cats.map(c => { const l = list(c); return l.length ? `<div class="cat-label">${CATS[c]}s</div><div class="stack">${l.map(d => dishRow(d, u, { date: day.date })).join('')}</div>` : ''; }).join('')
        : `<div class="empty"><div class="ico"><svg class=ic><use href=#i-search></use></svg></div>Aucun plat ne correspond à ces filtres ce jour-là.<br><button class="btn sm ghost mt" onclick="App.UI.menuCat='all';App.UI.menuDiet='all';App.UI.compatOnly=false;App.render()">Effacer les filtres</button></div>`}
      ${rateRes ? `<button class="btn light block mt" onclick="App.go('#/u/rate/${rateRes.id}')"><svg class=ic-star><use href=#i-star></use></svg> Donner mon avis sur ce menu</button>` : ''}
      <div class="sticky-cta">${cta}</div>` };
  });
  A.showMenuReviews = (date) => {
    const revs = menuReviews(date), u = me();
    const dayDishes = App.dishesOfDay(dayOf(date)).filter(i => dishReviews(i).some(v => v.date === date));
    modal(`<h2>Avis — ${fmtLong(date)}</h2>
      ${dayDishes.length ? `<div class="cat-label" style="margin-top:0">Par plat</div><div class="card flat mb">${dayDishes.map(i => { const dv = dishReviews(i).filter(v => v.date === date); const avg = dv.reduce((a, v) => a + v.stars, 0) / dv.length; return `<div class="summary-line"><span class="small">${esc(dish(i).name)}</span><span>${stars(avg, dv.length)}</span></div>`; }).join('')}</div>` : ''}
      <div class="cat-label" style="margin-top:0">Sur le menu</div>${revs.length ? revs.map(v => `<div class="card flat mb"><div class="row between"><div class="row">${App.avatar(user(v.userId), '', 'width:28px;height:28px;font-size:11px')}<b class="small">${esc(App.displayName(user(v.userId)))}</b></div><span class="stars">${'<svg class=ic-star><use href=#i-star></use></svg>'.repeat(v.stars)}${'<svg class=ic-off><use href=#i-star></use></svg>'.repeat(5 - v.stars)}</span></div><p class="small mt">${esc(v.comment)}</p><span class="xs muted">${v.createdAt}</span></div>`).join('') : '<p class="muted">Pas encore d’avis sur ce menu.</p>'}
      <p class="xs muted mt">Seuls les convives ayant réservé ce menu peuvent le noter.</p>
      <div class="actions">${canReview(u, date) ? `<button class="btn" onclick="App.closeModal();App.go('#/u/rate/${canReview(u, date).id}')">Donner mon avis</button>` : ''}<button class="btn ghost" onclick="App.closeModal()">Fermer</button></div>`);
  };

  /* ================= U3 — Détail d'un plat ================= */
  route('/u/dish/:id', ({ id }) => {
    const u = me(), d = dish(id), date = UI.menuDay;
    const conf = conflicts(d, u);
    const res = activeRes(u.id, date);
    const canBook = bookingOpen(date) && !res && dayOf(date)[d.cat].includes(id);
    return { layout: 'mobile', title: esc(d.name), back: 'history', noTabs: true, html: `
      <div style="margin:-16px -16px 16px">${App.dishImg(d, 'lg')}</div>
      <div class="row between"><h2>${esc(d.name)}</h2><h2>${fcfa(d.price)}</h2></div>
      <div class="row mt small">${stars(d.rating, d.ratingCount + ' avis')}<span class="muted">· ${CATS[d.cat]} · ${d.kcal} kcal</span></div>
      ${conf.length ? `<div class="alert red mt"><svg class=ic><use href=#i-alert></use></svg> <span><b>Contient : ${conf.map(a => ALL[a].toLowerCase()).join(', ')}</b> — incompatible avec votre profil.</span></div>` : ''}
      <p class="mt">${esc(d.desc)}</p>
      <div class="section-title"><h3>Ingrédients</h3></div><p class="small">${d.recipe.length ? d.recipe.map(r => esc((D.ingredients.find(g => g.id === r[0]) || { name: '?' }).name)).join(', ') : '<span class="muted">Non renseignés</span>'}</p>
      <div class="section-title"><h3>Allergènes</h3></div><div class="chips">${d.allergens.length ? allergenBadges(d, u) : '<span class="badge green">Aucun allergène majeur</span>'}</div>
      <div class="section-title"><h3>Avis sur ce plat</h3><span class="small">${stars(d.rating, d.ratingCount)}</span></div>
      ${dishReviews(d.id).filter(v => v.comment).slice(0, 3).map(v => `<div class="card flat mb"><div class="row between"><b class="small">${esc(App.displayName(user(v.userId)))}</b>${starRow(v.stars)}</div><p class="small muted mt">${esc(v.comment)}</p></div>`).join('') || '<p class="muted small">Pas encore d’avis écrit sur ce plat.</p>'}
      <div class="section-title"><h3>Régimes</h3></div><div class="chips">${d.diets.map(x => `<span class="badge green">${D.DIETS[x]}</span>`).join(' ')}</div>

      <div class="sticky-cta">${canBook ? `<button class="btn block" onclick="App.A.startBook('${date}','${id}')">Ajouter à ma réservation · ${fmtShort(date)}</button>` : `<button class="btn ghost block" onclick="history.back()">Retour au menu</button>`}</div>` };
  });

  /* ================= U4 — Réservation (plusieurs plats, quantités) et paiement ================= */
  const MAX_QTY = 5;
  A.startBook = (date, dishId, replaceId) => {
    const items = {};
    if (dishId) items[dishId] = 1;
    let slot = null;
    if (replaceId) { const old = D.reservations.find(r => r.id === replaceId); old.items.forEach(id => { items[id] = (items[id] || 0) + 1; }); slot = old.slot; }
    UI.book = { date, items, slot, method: (App.userPayMethods()[0] || { id: 'wave' }).id, replaceId: replaceId || null };
    go('#/u/book/' + date);
  };
  route('/u/book/:date', ({ date }) => {
    const u = me(), day = dayOf(date);
    if (!UI.book || UI.book.date !== date) UI.book = { date, items: {}, slot: null, method: (App.userPayMethods()[0] || { id: 'wave' }).id, replaceId: null };
    const b = UI.book;
    const existing = activeRes(u.id, date);
    if (!bookingOpen(date)) return { layout: 'mobile', title: 'Réserver', back: '#/u/menu', noTabs: true, html: `<div class="empty"><div class="ico"><svg class=ic><use href=#i-clock></use></svg></div><b>Réservations closes</b><p class="small">Les réservations pour ${fmtLong(date).toLowerCase()} ont fermé à ${D.cutoff.replace(':', 'h')}.</p></div>` };
    if (existing && existing.id !== b.replaceId) return { layout: 'mobile', title: 'Réserver', back: '#/u/menu', noTabs: true, html: `<div class="empty"><div class="ico"><svg class=ic><use href=#i-ticket></use></svg></div><b>Déjà réservé</b><p class="small">Vous avez déjà une réservation ce jour-là. Vous pouvez la modifier pour ajouter des plats.</p><button class="btn mt" onclick="App.go('#/u/resa/${existing.id}')">Voir ma réservation</button></div>` };
    const chosen = Object.entries(b.items).map(([id, q]) => [dish(id), q]);
    const count = chosen.reduce((s, [, q]) => s + q, 0);
    const total = chosen.reduce((s, [d, q]) => s + d.price * q, 0);
    const refund = b.replaceId ? D.reservations.find(r => r.id === b.replaceId).total : 0;
    const after = u.balance + refund - total;
    const ready = count > 0 && b.slot;
    const warn = chosen.flatMap(([d]) => conflicts(d, u).map(a => d.name + ' (' + ALL[a].toLowerCase() + ')'));
    return { layout: 'mobile', title: b.replaceId ? 'Modifier ma réservation' : 'Réserver', back: '#/u/menu', noTabs: true, html: `
      <div class="card sky mb"><b>${fmtLong(date)}</b><div class="small">Modifiable ou annulable jusqu’à ${D.cutoff.replace(':', 'h')} le jour même</div></div>
      <h3>1. Composez votre commande</h3><p class="xs muted">Choisissez un ou plusieurs plats, puis ajustez les quantités (${MAX_QTY} max par article).</p>
      ${['plat', 'entree', 'dessert', 'boisson'].map(c => `<div class="cat-label">${CATS[c]}s</div><div class="stack">${day[c].map(id => dishRow(dish(id), u, { date, selected: !!b.items[id], qty: b.items[id], onclick: `App.A.pick('${id}')` })).join('')}</div>`).join('')}
      <h3 class="mt">2. Choisissez votre créneau</h3><p class="xs muted mb">Places restantes par créneau</p>
      <div class="slots">${D.slots.map(s => { const left = s.capacity - slotCount(date, s.id) + (b.replaceId && D.reservations.find(r => r.id === b.replaceId).slot === s.id ? 1 : 0); return `<button class="slot ${b.slot === s.id ? 'active' : ''}" ${left <= 0 ? 'disabled' : ''} onclick="App.UI.book.slot='${s.id}';App.render()"><b>${s.label}</b><span class="xs ${left < 10 ? '' : 'muted'}" style="${left < 10 ? 'color:var(--orange);font-weight:700' : ''}">${left <= 0 ? 'Complet' : left + ' places'}</span></button>`; }).join('')}</div>
      <h3 class="mt">3. Récapitulatif</h3>
      <div class="card mt">
        ${chosen.length ? chosen.map(([d, q]) => `<div class="summary-line"><span>${q > 1 ? `<b>${q}×</b> ` : ''}${esc(d.name)}</span><span>${fcfa(d.price * q)}</span></div>`).join('') : '<p class="muted small">Aucun plat sélectionné.</p>'}
        <div class="summary-line total"><span>Total (${count} article${count > 1 ? 's' : ''})</span><span>${fcfa(total)}</span></div>
        <div class="summary-line small muted"><span>Solde actuel${refund ? ' (+ remboursement ' + fcfa(refund) + ')' : ''}</span><span>${fcfa(u.balance + refund)}</span></div>
        <div class="summary-line small"><span>Solde après paiement</span><b style="color:${after < 0 ? 'var(--red)' : 'inherit'}">${fcfa(after)}</b></div>
      </div>
      ${warn.length ? `<div class="alert red mt"><svg class=ic><use href=#i-alert></use></svg> <span>Attention : ${warn.map(esc).join(', ')} — incompatible avec votre profil.</span></div>` : ''}
      ${after < 0 && total ? `<div class="alert orange mt">Solde insuffisant : il manque ${fcfa(-after)}. Payez la différence par Mobile Money.</div>
        <div class="pay-methods mt">${payMethods(b.method, "App.UI.book.method='$1';App.render()", false)}</div>` : ''}
      <div class="sticky-cta"><button class="btn block" ${ready ? '' : 'disabled'} onclick="App.A.confirmBook()">${!ready ? (count ? 'Choisissez un créneau' : 'Choisissez au moins un plat') : after < 0 ? 'Payer ' + fcfa(-after) + ' et réserver' : (b.replaceId ? 'Enregistrer les modifications · ' : 'Confirmer et payer ') + fcfa(total)}</button></div>` };
  });
  function payMethods(active, onclickTpl, withCard) {
    const list = App.userPayMethods(withCard);
    if (!list.length) return '<div class="alert red">Aucun moyen de paiement n’est activé pour le moment.</div>';
    return list.map(p => `<button class="pay-method ${active === p.id ? 'active' : ''}" onclick="${onclickTpl.replace('$1', p.id)}"><span class="logo" style="background:${p.color};color:${p.id === 'momo' ? '#1d1d1b' : '#fff'}">${p.logo}</span><span class="grow"><b>${p.name}</b></span>${active === p.id ? '<svg class=ic><use href=#i-check></use></svg>' : ''}</button>`).join('');
  }
  App.payMethods = payMethods;
  A.pick = (id) => {
    const b = UI.book, u = me(), d = dish(id);
    if (b.items[id]) { delete b.items[id]; return render(); }
    if (remaining(b.date, id) <= 0) return toast('Ce plat est épuisé', 'red');
    const c = conflicts(d, u);
    if (c.length) return confirmModal('Plat incompatible', `<b>${esc(d.name)}</b> contient : ${c.map(a => ALL[a].toLowerCase()).join(', ')}. Cet allergène figure dans votre profil.<br><br><span class="small">Si vous confirmez, l’équipe de la cantine sera prévenue pour vérifier votre plateau.</span>`, 'Ajouter quand même', () => { b.items[id] = 1; render(); }, true);
    b.items[id] = 1; render();
  };
  A.qty = (id, delta) => {
    const b = UI.book, q = (b.items[id] || 0) + delta;
    if (q <= 0) delete b.items[id];
    else if (q > MAX_QTY) return toast(MAX_QTY + ' maximum par article');
    else if (delta > 0 && remaining(b.date, id) - (b.items[id] || 0) <= 0) return toast('Plus assez de portions disponibles', 'red');
    else b.items[id] = q;
    render();
  };
  A.confirmBook = () => {
    const u = me(), b = UI.book;
    const order = ['entree', 'plat', 'dessert', 'boisson'];
    const items = Object.entries(b.items).sort((x, y) => order.indexOf(dish(x[0]).cat) - order.indexOf(dish(y[0]).cat)).flatMap(([id, q]) => Array(q).fill(id));
    const total = items.reduce((s, id) => s + dish(id).price, 0);
    const old = b.replaceId ? D.reservations.find(r => r.id === b.replaceId) : null;
    const finish = () => {
      if (old) { old.status = 'annulee'; u.balance += old.total; addTx(u.id, 'remboursement', 'Modification — ' + mainDish(old).name, old.total); }
      u.balance -= total;
      const r = { id: App.newId('r'), userId: u.id, date: b.date, slot: b.slot, items, total, status: 'payee', code: String(100000 + Math.floor(Math.random() * 899999)), servedAt: null, rated: false };
      D.reservations.push(r);
      addTx(u.id, 'repas', 'Repas — ' + resItemsText(r), -total);
      App.alertAllergy(u, r, false);
      if (u.parentId) notify(u.parentId, '<svg class=ic><use href=#i-utensils></use></svg>', 'Repas réservé — ' + u.first, `${resItemsText(r)}, ${fmtShort(b.date)}, créneau ${slotLabel(b.slot)}.`, '#/p/child/' + u.id);
      checkLowBalance(u);
      UI.lastBooked = r.id; UI.book = null;
      go('#/u/booked/' + r.id);
    };
    const missing = total - (u.balance + (old ? old.total : 0));
    if (missing > 0) {
      const amount = Math.ceil(missing / 500) * 500, label = App.providerName(b.method);
      return simulatePayment('Recharge ' + label, amount, (ref) => { u.balance += amount; addTx(u.id, 'recharge', 'Recharge ' + label, amount, ref); finish(); });
    }
    finish();
  };
  route('/u/booked/:id', ({ id }) => {
    const r = D.reservations.find(x => x.id === id);
    return { layout: 'mobile', title: 'Réservation confirmée', noTabs: true, html: `<div class="center" style="padding-top:30px">
      <div class="success-ico"><svg class=ic><use href=#i-check></use></svg></div><h2>Repas réservé !</h2><p class="muted mt">${fmtLong(r.date)} · créneau ${slotLabel(r.slot)}</p>
      <div class="card mt" style="text-align:left">${Object.entries(App.groupItems(r.items)).map(([i, q]) => `<div class="summary-line"><span>${q > 1 ? '<b>' + q + '×</b> ' : ''}${esc(dish(i).name)}</span><span>${fcfa(dish(i).price * q)}</span></div>`).join('')}<div class="summary-line total"><span>Payé</span><span>${fcfa(r.total)}</span></div></div>
      <p class="small muted mt">Nouveau solde : <b>${fcfa(me().balance)}</b></p>
      <button class="btn block mt" onclick="App.go('#/u/resa/${r.id}')">Voir mon QR code</button>
      <button class="btn ghost block mt" onclick="App.go('#/u/home')">Retour à l’accueil</button></div>` };
  });

  /* ================= U5 — Mes réservations et QR code ================= */
  route('/u/resa', () => {
    const u = me();
    const mine = D.reservations.filter(r => r.userId === u.id);
    const upcoming = mine.filter(r => r.status === 'payee' && r.date >= D.NOW.date).sort((a, b) => a.date.localeCompare(b.date));
    const past = mine.filter(r => !(r.status === 'payee' && r.date >= D.NOW.date)).sort((a, b) => b.date.localeCompare(a.date));
    const list = UI.resaTab === 'avenir' ? upcoming : past;
    return { layout: 'mobile', title: 'Mes réservations', tab: 'resa', html: `
      <div class="tabs"><button class="${UI.resaTab === 'avenir' ? 'active' : ''}" onclick="App.UI.resaTab='avenir';App.render()">À venir (${upcoming.length})</button><button class="${UI.resaTab === 'passees' ? 'active' : ''}" onclick="App.UI.resaTab='passees';App.render()">Passées</button></div>
      ${list.length ? `<div class="stack">${list.map(r => `<div class="card" onclick="App.go('#/u/resa/${r.id}')" style="cursor:pointer">
        <div class="row between"><b>${fmtLong(r.date)}</b>${statusBadge(r.status)}</div>
        <div class="row mt">${App.dishImg(mainDish(r), 'sm')}<div class="grow small"><b>${esc(mainDish(r).name)}</b><div class="muted xs">${esc(resItemsText(r))}</div></div><div class="small"><svg class=ic><use href=#i-clock></use></svg> ${slotLabel(r.slot)}</div></div>
        ${r.status === 'payee' ? '<div class="small bold mt" style="color:var(--blue)">Afficher le QR code ›</div>' : r.status === 'servie' && !r.rated ? '<div class="small bold mt" style="color:var(--orange)"><svg class=ic-star><use href=#i-star></use></svg> Noter ce repas ›</div>' : ''}</div>`).join('')}</div>`
        : `<div class="empty"><div class="ico"><svg class=ic><use href=#i-ticket></use></svg></div><b>Aucune réservation ${UI.resaTab === 'avenir' ? 'à venir' : 'passée'}</b>${UI.resaTab === 'avenir' ? '<p class="small">Consultez le menu pour réserver votre prochain repas.</p><button class="btn mt" onclick="App.go(\'#/u/menu\')">Voir le menu</button>' : ''}</div>`}` };
  });
  route('/u/resa/:id', ({ id }) => {
    const r = D.reservations.find(x => x.id === id);
    if (!r) return { redirect: '#/u/resa' };
    const editable = r.status === 'payee' && bookingOpen(r.date);
    return { layout: 'mobile', title: 'Ma réservation', back: '#/u/resa', noTabs: true, html: `
      ${r.status === 'payee' ? `<div class="qr-box">${qrSvg(r.code)}<div class="qr-code-text">${r.code}</div><p class="xs muted">Code de secours à donner au comptoir</p><span class="badge green mt"><svg class=ic><use href=#i-check></use></svg> Disponible hors ligne</span></div>`
        : `<div class="card center">${statusBadge(r.status)}<p class="mt">${r.status === 'servie' ? 'Repas servi' + (r.servedAt ? ' à ' + r.servedAt : '') + '.' : r.status === 'annulee' ? 'Réservation annulée et remboursée.' : 'Repas non retiré.'}</p></div>`}
      <div class="card mt"><div class="row between"><b>${fmtLong(r.date)}</b>${statusBadge(r.status)}</div><div class="small mt"><svg class=ic><use href=#i-clock></use></svg> Créneau <b>${slotLabel(r.slot)}</b></div><div class="divider"></div>
        ${Object.entries(App.groupItems(r.items)).map(([i, q]) => `<div class="summary-line"><span>${q > 1 ? '<b>' + q + '×</b> ' : ''}${esc(dish(i).name)}</span><span>${fcfa(dish(i).price * q)}</span></div>`).join('')}<div class="summary-line total"><span>Total payé</span><span>${fcfa(r.total)}</span></div></div>
      ${editable ? `<div class="row mt"><button class="btn ghost grow" onclick="App.A.startBook('${r.date}',null,'${r.id}')">Modifier</button><button class="btn danger grow" onclick="App.A.cancelRes('${r.id}')">Annuler</button></div><p class="xs muted center mt">Modifiable jusqu’à ${D.cutoff.replace(':', 'h')} le ${fmtLong(r.date).toLowerCase()} — remboursement immédiat sur le solde.</p>` : ''}
      ${r.status !== 'annulee' && r.date <= App.D.NOW.date ? `<button class="btn ${r.rated ? 'ghost' : ''} block mt" onclick="App.go('#/u/rate/${r.id}')">${r.rated ? 'Voir mon avis' : '<svg class=ic-star><use href=#i-star></use></svg> Donner mon avis sur ce menu'}</button>` : ''}` };
  });
  A.cancelRes = (id) => {
    const r = D.reservations.find(x => x.id === id), u = user(r.userId);
    confirmModal('Annuler la réservation ?', `${fcfa(r.total)} seront recrédités sur le solde.`, 'Annuler la réservation', () => {
      r.status = 'annulee'; u.balance += r.total; addTx(u.id, 'remboursement', 'Annulation — ' + mainDish(r).name, r.total);
      toast('Réservation annulée — ' + fcfa(r.total) + ' remboursés', 'green'); render();
    }, true);
  };

  /* ================= U6 — Portefeuille ================= */
  route('/u/wallet', () => {
    const u = me();
    const txs = D.transactions.filter(t => t.userId === u.id && t.date.slice(5, 7) === UI.walletMonth);
    const spent = txs.filter(t => t.type === 'repas').reduce((s, t) => s - t.amount, 0);
    const groups = {};
    txs.forEach(t => { (groups[t.date] = groups[t.date] || []).push(t); });
    const icon = { recharge: '<svg class=ic><use href=#i-arrow-up></use></svg>', repas: '<svg class=ic><use href=#i-utensils></use></svg>', remboursement: '<svg class=ic><use href=#i-undo></use></svg>', ajustement: '<svg class=ic><use href=#i-wrench></use></svg>' };
    return { layout: 'mobile', title: 'Portefeuille', tab: 'wallet', html: `
      <div class="balance-card center"><div class="small" style="color:#bbb">Solde disponible</div><div class="amount">${App.num(u.balance)} <small>FCFA</small></div><button class="btn sky block" onclick="App.UI.topupReturn='#/u/wallet';App.go('#/u/topup')">+ Recharger mon compte</button></div>
      <div class="row between mt"><div class="chips">${[['11', 'Novembre'], ['10', 'Octobre']].map(([m, l]) => `<button class="chip ${UI.walletMonth === m ? 'active' : ''}" onclick="App.UI.walletMonth='${m}';App.render()">${l}</button>`).join('')}</div></div>
      <div class="card mt"><div class="row between"><span class="muted small">Dépensé ce mois</span><b>${fcfa(spent)}</b></div><div class="row between mt"><span class="muted small">Repas pris</span><b>${txs.filter(t => t.type === 'repas').length}</b></div></div>
      ${Object.keys(groups).length ? Object.keys(groups).sort().reverse().map(dt => `<div class="cat-label">${fmtLong(dt)}</div><div class="card" style="padding:4px 14px">${groups[dt].map(t => `<div class="tx" onclick="App.A.receipt('${t.id}')" style="cursor:pointer"><div class="ico">${icon[t.type]}</div><div class="grow small"><b>${esc(t.label)}</b><div class="xs muted">${t.ref ? 'Réf. ' + t.ref : t.type}</div></div><div class="amt ${t.amount > 0 ? 'plus' : ''}">${t.amount > 0 ? '+' : ''}${fcfa(t.amount)}</div></div>`).join('')}</div>`).join('')
        : '<div class="empty"><div class="ico"><svg class=ic><use href=#i-scan></use></svg></div>Aucune transaction ce mois-ci.</div>'}` };
  });
  A.receipt = (id) => {
    const t = D.transactions.find(x => x.id === id);
    modal(`<h2>Reçu</h2><div class="summary-line"><span class="muted">Date</span><span>${fmtLong(t.date)}</span></div><div class="summary-line"><span class="muted">Opération</span><span>${esc(t.label)}</span></div><div class="summary-line"><span class="muted">Référence</span><span>${t.ref || t.id.toUpperCase()}</span></div><div class="summary-line total"><span>Montant</span><span>${t.amount > 0 ? '+' : ''}${fcfa(t.amount)}</span></div><div class="actions"><button class="btn ghost" onclick="App.closeModal()">Fermer</button><button class="btn" onclick="App.toast('Reçu PDF téléchargé')">Télécharger le PDF</button></div>`);
  };

  /* ================= U7 — Recharger ================= */
  route('/u/topup', () => {
    const u = me(), t = UI.topup;
    const amount = t.custom ? parseInt(t.custom, 10) || 0 : t.amount;
    return { layout: 'mobile', title: 'Recharger', back: UI.topupReturn || '#/u/wallet', noTabs: true, html: `
      <div class="card sky mb"><div class="small">Solde actuel</div><b style="font-size:22px">${fcfa(u.balance)}</b></div>
      <h3 class="mb">Montant</h3>
      <div class="amounts">${[1000, 2500, 5000, 10000].map(v => `<button class="amount-btn ${!t.custom && t.amount === v ? 'active' : ''}" onclick="App.UI.topup.amount=${v};App.UI.topup.custom='';App.render()">${fcfa(v)}</button>`).join('')}</div>
      <div class="field mt"><label>Autre montant</label><input class="input" type="number" min="500" step="500" placeholder="Ex. 7 500" value="${esc(t.custom)}" onchange="App.UI.topup.custom=this.value;App.render()"></div>
      <h3 class="mb">Moyen de paiement</h3>
      <div class="pay-methods">${payMethods(t.method, "App.UI.topup.method='$1';App.render()", false)}</div>
      <div class="field mt"><label>Numéro Mobile Money</label><input class="input" id="tp-phone" value="${esc(u.phone)}"></div>
      <div class="sticky-cta"><button class="btn block" ${amount >= 500 ? '' : 'disabled'} onclick="App.A.topup(${amount})">Payer ${fcfa(amount)}</button></div>` };
  });
  A.topup = (amount) => {
    const u = me(), label = App.providerName(UI.topup.method);
    simulatePayment('Recharge ' + label, amount, (ref) => {
      u.balance += amount; addTx(u.id, 'recharge', 'Recharge ' + label, amount, ref);
      toast('Recharge réussie : +' + fcfa(amount), 'green');
      go(UI.topupReturn || '#/u/wallet');
    });
  };

  /* ================= U8 — Avis : sur chaque plat et/ou sur le menu entier ================= */
  const starRow = (n) => `<span class="stars">${'<svg class=ic-star><use href=#i-star></use></svg>'.repeat(n)}${'<svg class=ic-off><use href=#i-star></use></svg>'.repeat(5 - n)}</span>`;
  const revStatus = (v) => v.status === 'visible' ? '<span class="badge green">Publié</span>' : v.status === 'pending' ? '<span class="badge orange">En attente de vérification</span>' : '<span class="badge gray">Masqué par la modération</span>';
  route('/u/rate/:id', ({ id }) => {
    const u = me(), r = D.reservations.find(x => x.id === id);
    if (!r || r.userId !== u.id) return { layout: 'mobile', title: 'Donner mon avis', back: '#/u/resa', noTabs: true, html: `<div class="empty"><div class="ico">${App.ic('lock')}</div><b>Avis impossible</b><p class="small">Seuls les convives ayant réservé ce menu peuvent le noter.</p></div>` };
    const mine = D.reviews.filter(v => v.resId === id);
    const day = dayOf(r.date);
    const menuLine = ['entree', 'plat', 'dessert'].flatMap(c => day[c]).map(i => dish(i).name).join(' · ');
    if (mine.length) return { layout: 'mobile', title: 'Mes avis', back: 'history', noTabs: true, html: `
      <div class="card sky mb"><b>Menu du ${fmtLong(r.date).toLowerCase()}</b><div class="small">${esc(resItemsText(r))}</div></div>
      <div class="stack">${mine.map(v => `<div class="card"><div class="row between"><b class="small">${v.type === 'dish' ? esc(dish(v.dishId).name) : 'Le menu dans son ensemble'}</b>${starRow(v.stars)}</div>
        ${v.comment ? `<p class="small mt">${esc(v.comment)}</p>` : ''}<div class="mt">${revStatus(v)}</div></div>`).join('')}</div>` };
    if (r.status === 'annulee' || r.date > D.NOW.date) return { layout: 'mobile', title: 'Donner mon avis', back: 'history', noTabs: true, html: `<div class="empty"><div class="ico">${App.ic('clock')}</div><b>Pas encore</b><p class="small">Vous pourrez donner votre avis à partir du ${fmtLong(r.date).toLowerCase()}.</p></div>` };
    const items = [...new Set(r.items)];
    if (!UI.rate || UI.rate.id !== id) UI.rate = { id, stars: 0, tags: [], waste: null, comment: '', dish: {} };
    const s = UI.rate;
    items.forEach(i => { s.dish[i] = s.dish[i] || { stars: 0, comment: '' }; });
    const tags = ['Délicieux', 'Bien assaisonné', 'Copieux', 'Trop salé', 'Trop pimenté', 'Portion petite', 'Servi froid'];
    const keep = 'App.A.rateKeep();';
    const starsInput = (cur, setter) => `<div class="rate-stars">${[1, 2, 3, 4, 5].map(n => `<button class="${cur >= n ? 'on' : ''}" onclick="${keep}${setter}=${cur === n ? 0 : n};App.render()" aria-label="${n} étoiles">${'<svg class=ic-star><use href=#i-star></use></svg>'}</button>`).join('')}</div>`;
    const count = (s.stars ? 1 : 0) + items.filter(i => s.dish[i].stars).length;
    return { layout: 'mobile', title: 'Donner mon avis', back: 'history', noTabs: true, html: `
      <div class="card sky mb"><b>Menu du ${fmtLong(r.date).toLowerCase()}</b><div class="small">${esc(menuLine)}</div></div>
      <p class="small muted mb">Notez les plats que vous avez pris, le menu dans son ensemble, ou les deux. Touchez une étoile à nouveau pour l’effacer.</p>
      <h3 class="mb">Les plats</h3>
      <div class="stack">${items.map(i => { const d = dish(i), ds = s.dish[i]; return `<div class="card">
        <div class="row">${App.dishImg(d, 'sm')}<div class="grow"><b class="small">${esc(d.name)}</b><div class="xs muted">${CATS[d.cat]}</div></div></div>
        <div class="mt">${starsInput(ds.stars, `App.UI.rate.dish['${i}'].stars`)}</div>
        ${ds.stars ? `<textarea class="input rt-dc mt" data-id="${i}" rows="2" maxlength="300" placeholder="Un mot sur ce plat ? (facultatif)">${esc(ds.comment)}</textarea>` : ''}</div>`; }).join('')}</div>
      <h3 class="mt mb">Le menu dans son ensemble</h3>
      <div class="card">${starsInput(s.stars, 'App.UI.rate.stars')}
        <div class="field mt" style="margin-bottom:0"><label>Commentaire <span class="muted">(facultatif)</span></label><textarea class="input" id="rt-comment" rows="3" maxlength="500" placeholder="Votre avis aide la cuisine à s’améliorer">${esc(s.comment)}</textarea></div></div>
      <p class="xs muted mt">Les avis sont modérés : insultes, spam et hors sujet sont masqués.</p>
      <h3 class="mt mb">En quelques mots</h3>
      <div class="chips">${tags.map(t => `<button class="chip ${s.tags.includes(t) ? 'active' : ''}" onclick="${keep}App.A.toggleArr(App.UI.rate.tags,'${t}');App.render()">${t}</button>`).join('')}</div>
      <h3 class="mt mb">Avez-vous tout mangé ?</h3>
      <div class="chips">${[['tout', 'smile', 'Tout mangé'], ['partie', 'meh', 'Laissé une partie'], ['beaucoup', 'frown', 'Laissé beaucoup']].map(([k, ic, l]) => `<button class="chip ${s.waste === k ? 'active' : ''}" onclick="${keep}App.UI.rate.waste='${k}';App.render()">${App.ic(ic)} ${l}</button>`).join('')}</div>
      <p class="xs muted mt">Cette réponse aide à réduire le gaspillage.</p>
      <div class="sticky-cta"><button class="btn block" ${count ? '' : 'disabled'} onclick="App.A.sendRating('${id}')">${count ? 'Publier ' + (count > 1 ? count + ' avis' : 'mon avis') : 'Notez au moins un plat ou le menu'}</button><button class="btn ghost block mt" onclick="App.go('#/u/home')">Plus tard</button></div>` };
  });
  // Conserve les textes saisis avant chaque nouveau rendu
  A.rateKeep = () => {
    const s = UI.rate; if (!s) return;
    const c = document.getElementById('rt-comment'); if (c) s.comment = c.value;
    document.querySelectorAll('.rt-dc').forEach(t => { if (s.dish[t.dataset.id]) s.dish[t.dataset.id].comment = t.value; });
  };
  A.sendRating = (id) => {
    A.rateKeep();
    const r = D.reservations.find(x => x.id === id), s = UI.rate, u = me();
    const base = { userId: u.id, date: r.date, resId: r.id, status: 'visible', flagged: false, flagWords: [], createdAt: App.nowStamp() };
    const created = [];
    Object.entries(s.dish).filter(([, ds]) => ds.stars).forEach(([dishId, ds], k) => {
      created.push(Object.assign({}, base, { id: 'rv' + Date.now() + '-' + k, type: 'dish', dishId, stars: ds.stars, comment: ds.comment.trim(), tags: [] }));
      const d = dish(dishId); d.rating = (d.rating * d.ratingCount + ds.stars) / (d.ratingCount + 1); d.ratingCount++;
    });
    if (s.stars) created.push(Object.assign({}, base, { id: 'rv' + Date.now() + '-m', type: 'menu', stars: s.stars, comment: s.comment.trim() || s.tags.join(', '), tags: s.tags.slice(), waste: s.waste }));
    created.forEach(rv => { App.scanReview(rv); D.reviews.unshift(rv); });
    r.rated = true; r.waste = s.waste; UI.rate = null;
    D.notifications.filter(n => n.userId === u.id && n.go === '#/u/rate/' + id).forEach(n => { n.read = true; });
    if (created.some(v => v.flagged)) toast('Merci ! Votre avis sera vérifié avant publication.');
    else toast(created.length > 1 ? `Merci, vos ${created.length} avis sont publiés !` : 'Merci, votre avis est publié !', 'green');
    go('#/u/home');
  };

  /* ================= U9 — Notifications (convive et parent) ================= */
  const notifView = (tab, back) => () => {
    const u = me();
    const list = D.notifications.filter(n => n.userId === u.id);
    return { layout: 'mobile', title: 'Notifications', back, tab, html: `
      <div class="row between mb"><span class="muted small">${list.filter(n => !n.read).length} non lue(s)</span><button class="btn sm ghost" onclick="App.A.readAll()">Tout marquer comme lu</button></div>
      ${list.length ? `<div class="stack">${list.map(n => `<div class="notif ${n.read ? '' : 'unread'}" onclick="App.A.openNotif('${n.id}')"><div class="ico">${App.notifIcon(n.icon)}</div><div class="grow"><b class="small">${esc(n.title)}</b><p class="small muted">${esc(n.text)}</p><span class="xs muted">${n.date}</span></div></div>`).join('')}</div>` : '<div class="empty"><div class="ico"><svg class=ic><use href=#i-bell></use></svg></div>Aucune notification.</div>'}` };
  };
  route('/u/notifs', notifView('home', '#/u/home'));
  A.readAll = () => { D.notifications.filter(n => n.userId === me().id).forEach(n => { n.read = true; }); render(); };
  A.openNotif = (id) => { const n = D.notifications.find(x => x.id === id); n.read = true; if (n.go.startsWith('#/u/menu')) UI.menuDay = D.NOW.date; go(n.go); };

  /* ================= P1 — Accueil parent ================= */
  const balDot = (c) => c.balance < (c.threshold || 1000) / 2 ? 'var(--red)' : c.balance < (c.threshold || 1000) ? 'var(--orange)' : 'var(--green)';
  route('/p/home', () => {
    const p = me(), kids = p.children.map(user);
    const low = kids.filter(c => c.balance < (c.threshold || 1000));
    const unread = D.notifications.filter(n => n.userId === p.id && !n.read).length;
    return { layout: 'mobile', title: `Bonjour, ${esc(p.first)}`, tab: 'home', right: bell(unread, '#/p/notifs'), html: `
      ${low.map(c => `<div class="alert orange mb"><svg class=ic><use href=#i-alert></use></svg> <span class="grow">Le solde de <b>${esc(c.first)}</b> est de ${fcfa(c.balance)}.</span><button class="btn sm" onclick="App.go('#/p/topup/${c.id}')">Recharger</button></div>`).join('')}
      ${kids.length ? `<div class="stack">${kids.map(c => { const r = activeRes(c.id, D.NOW.date); return `<div class="child-card" onclick="App.go('#/p/child/${c.id}')">
        <div class="row">${App.avatar(c, '', '')}<div class="grow"><b>${esc(fullName(c))}</b><div class="small muted">${c.group ? esc(c.group) : 'Classe non renseignée'}</div></div><div style="text-align:right"><div class="xs muted">Solde</div><b><span class="pill-dot" style="background:${balDot(c)}"></span> ${fcfa(c.balance)}</b></div></div>
        <div class="divider"></div>
        <div class="row between small"><span class="muted">Aujourd’hui</span>${r ? `<span>${mainDish(r).emoji} ${esc(mainDish(r).name)} · ${statusBadge(r.status)}</span>` : '<span class="badge gray">Pas de réservation</span>'}</div>
        <div class="row mt"><button class="btn sm light grow" onclick="event.stopPropagation();App.go('#/p/topup/${c.id}')">Recharger</button><button class="btn sm ghost grow" onclick="event.stopPropagation();App.go('#/p/child/${c.id}')">Voir ses repas</button></div></div>`; }).join('')}</div>`
        : `<div class="empty"><div class="ico"><svg class=ic><use href=#i-users></use></svg></div><b>Aucun enfant lié</b><p class="small">Ajoutez votre premier enfant avec son code de liaison.</p></div>`}
      <button class="btn ghost block mt" onclick="App.UI.linkFound=null;App.UI.linkError=null;App.go('#/p/link')">+ Ajouter un enfant</button>
      <div class="section-title"><h3>Ce mois-ci</h3></div>
      <div class="card"><div class="row between"><span class="muted small">Total rechargé</span><b>${fcfa(D.payments.filter(x => x.parentId === p.id && x.date.slice(5, 7) === '11').reduce((s, x) => s + x.amount, 0))}</b></div><div class="row between mt"><span class="muted small">Repas pris par vos enfants</span><b>${D.reservations.filter(r => p.children.includes(r.userId) && r.status === 'servie').length}</b></div><a class="small mt" style="display:block" href="#/p/payments">Historique des paiements ›</a></div>` };
  });
  route('/p/notifs', notifView('alerts', '#/p/home'));

  /* ================= P2 — Lier un enfant par QR code ================= */
  route('/p/link', () => {
    const f = UI.linkFound;
    return { layout: 'mobile', title: 'Ajouter un enfant', back: '#/p/home', noTabs: true, html: `
      ${f ? `<div class="card center"><p class="small muted">Élève trouvé</p>${App.avatar(user(f), 'lg', 'margin:10px auto')}<h2>${esc(fullName(user(f)))}</h2><p class="muted small">${user(f).group ? esc(user(f).group) : 'Classe non renseignée'}</p>
          ${user(f).parentId ? `<p class="xs muted mt">Déjà lié à un autre parent : les deux comptes pourront suivre l’élève.</p>` : ''}
          <button class="btn block mt" onclick="App.A.linkChild('${f}')">Confirmer le lien</button><button class="btn ghost block mt" onclick="App.UI.linkFound=null;App.render()">Ce n’est pas mon enfant</button></div>`
        : `<div class="card center">
          <div class="viewfinder" style="max-width:220px;margin:0 auto" onclick="App.A.scanChild()"><div class="laser"></div></div>
          <p class="small mt">Demandez à votre enfant d’ouvrir <b>Profil › Lier un parent</b> et scannez le QR code affiché.</p>
          <button class="btn block mt" onclick="App.A.scanChild()"><svg class=ic><use href=#i-camera></use></svg> Scanner le QR code</button></div>
          <details class="mt"><summary class="small" style="cursor:pointer">Le scan ne fonctionne pas ? Saisir le code de secours</summary>
            <div class="field mt"><input class="input" id="lk-code" placeholder="Ex. INE-5Q1" style="text-transform:uppercase;letter-spacing:.1em" value="${esc(UI.linkCode || '')}"></div>
            <button class="btn ghost block" onclick="App.A.findChild(document.getElementById('lk-code').value)">Valider le code</button></details>
          ${UI.linkError ? `<div class="alert red mt">${UI.linkError}</div>` : ''}
          <p class="xs muted mt">La liaison se fait uniquement par le QR code de l’élève (le matricule n’est pas utilisé). Vous pouvez lier plusieurs enfants.</p>`}` };
  });
  // Simulation de la caméra : en production, lecture du QR via la caméra (ex. bibliothèque html5-qrcode)
  A.scanChild = () => {
    const p = me(), candidates = D.users.filter(u => u.role === 'convive' && u.type === 'eleve' && u.linkCode && !p.children.includes(u.id) && App.isActive(u) && !u.id.startsWith('x')).slice(0, 4);
    modal(`<h2>Scanner le QR code</h2><div class="viewfinder" style="max-width:260px;margin:0 auto"><div class="laser"></div></div>
      <p class="xs muted center mt">Démo : choisissez le QR code « présenté » à la caméra</p>
      <div class="stack mt">${candidates.map(c => `<button class="btn ghost block" onclick="App.closeModal();App.A.findChild('${c.linkCode}')">QR de ${esc(c.first)} ${esc(c.last)}</button>`).join('')}<button class="btn ghost block" onclick="App.closeModal();App.A.findChild('QR-INVALIDE')">QR invalide</button></div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button></div>`, { noFocus: true });
  };
  A.findChild = (code) => {
    code = (code || '').trim().toUpperCase(); UI.linkCode = code;
    const c = D.users.find(u => u.linkCode === code && App.isActive(u));
    UI.linkError = null; UI.linkFound = null;
    if (!c) UI.linkError = 'QR code non reconnu ou expiré. Demandez à votre enfant d’en générer un nouveau.';
    else if (me().children.includes(c.id)) UI.linkError = 'Cet enfant est déjà lié à votre compte.';
    else UI.linkFound = c.id;
    render();
  };
  A.linkChild = (id) => { const p = me(); p.children.push(id); user(id).parentId = p.id; UI.linkFound = null; UI.linkCode = ''; notify(id, '<svg class=ic><use href=#i-users></use></svg>', 'Compte parent lié', `${fullName(p)} suit désormais vos réservations.`, '#/profile'); toast(user(id).first + ' a été ajouté(e)', 'green'); go('#/p/home'); };

  /* ================= P3 — Fiche enfant ================= */
  route('/p/child/:id', ({ id }) => {
    const p = me();
    if (!p.children.length) return { redirect: '#/p/link' };
    if (!p.children.includes(id)) return { redirect: '#/p/child/' + p.children[0] };
    const c = user(id);
    const weekRes = D.week.map(d => ({ d, r: activeRes(c.id, d.date) || D.reservations.find(r => r.userId === c.id && r.date === d.date) }));
    const served = D.reservations.filter(r => r.userId === c.id && r.status === 'servie');
    const kcal = served.length ? Math.round(served.reduce((s, r) => s + r.items.reduce((a, i) => a + dish(i).kcal, 0), 0) / served.length) : 0;
    const catCount = (cat) => served.reduce((s, r) => s + r.items.filter(i => dish(i).cat === cat).length, 0);
    const txs = D.transactions.filter(t => t.userId === c.id).slice(0, 5);
    return { layout: 'mobile', title: 'Suivi de ' + esc(c.first), tab: 'child', back: '#/p/home', html: `
      ${p.children.length > 1 ? `<div class="chips mb">${p.children.map(k => `<button class="chip ${k === id ? 'active' : ''}" onclick="App.go('#/p/child/${k}')">${esc(user(k).first)}</button>`).join('')}</div>` : ''}
      <div class="balance-card"><div class="row between"><div><div class="small" style="color:#bbb">Solde de ${esc(c.first)}</div><div class="amount" style="margin-bottom:0">${App.num(c.balance)} <small>FCFA</small></div></div><button class="btn sky sm" onclick="App.go('#/p/topup/${c.id}')">Recharger</button></div>${c.dailyCap ? `<div class="xs mt" style="color:#bbb">Plafond journalier : ${fcfa(c.dailyCap)}</div>` : ''}</div>
      <div class="section-title"><h3>Repas de la semaine</h3><button class="btn sm light" onclick="App.A.pBook('${c.id}')">Réserver pour ${esc(c.first)}</button></div>
      <div class="card week-list">${weekRes.map(({ d, r }) => `<div class="wl"><div class="d">${d.label}<small>${d.date.slice(8)}/11</small></div><div class="grow small">${r && r.status !== 'annulee' ? `<b>${mainDish(r).emoji} ${esc(mainDish(r).name)}</b><div class="xs muted">${slotLabel(r.slot)}${r.servedAt ? ' · servi à ' + r.servedAt : ''}</div>` : '<span class="muted">—</span>'}</div>${r && r.status !== 'annulee' ? statusBadge(r.status) : `<span class="badge gray">${d.date < D.NOW.date ? 'Aucun repas' : 'Pas réservé'}</span>`}</div>`).join('')}</div>
      <div class="section-title"><h3>Réservations à venir</h3></div>
      ${(() => { const up = D.reservations.filter(r => r.userId === c.id && r.status === 'payee' && r.date >= D.NOW.date).sort((a, b) => a.date.localeCompare(b.date)); return up.length ? `<div class="stack">${up.map(r => `<div class="card flat"><div class="row between"><b class="small">${fmtLong(r.date)}</b><span class="small"><svg class=ic><use href=#i-clock></use></svg> ${slotLabel(r.slot)}</span></div><div class="small muted mt">${esc(resItemsText(r))}</div><div class="row between mt"><b>${fcfa(r.total)}</b>${statusBadge(r.status)}</div></div>`).join('')}</div>` : '<p class="small muted">Aucune réservation à venir.</p>'; })()}
      <div class="section-title"><h3>Équilibre alimentaire</h3><span class="xs muted">${served.length} repas servis</span></div>
      <div class="card">${served.length ? `${hbars([{ label: 'Plats', value: catCount('plat') }, { label: 'Entrées', value: catCount('entree'), color: '#7fc8a9' }, { label: 'Desserts', value: catCount('dessert'), color: '#f2c879' }, { label: 'Boissons', value: catCount('boisson'), color: '#c9b6f2' }], Math.max(1, served.length))}<div class="row between small mt"><span class="muted">Apport moyen par repas</span><b>${kcal} kcal</b></div>` : '<p class="small muted">Pas encore de repas servi.</p>'}</div>
      <div class="section-title"><h3>Allergies déclarées</h3><button class="btn sm ghost" onclick="App.A.editAllergies('${c.id}')">Modifier</button></div>
      <div class="chips">${c.allergies.length ? c.allergies.map(a => `<span class="badge red">${ALL[a]}</span>`).join('') : '<span class="badge green">Aucune</span>'}</div>
      <div class="section-title"><h3>Dernières dépenses</h3></div>
      <div class="card" style="padding:4px 14px">${txs.map(t => `<div class="tx"><div class="grow small"><b>${esc(t.label)}</b><div class="xs muted">${fmtShort(t.date)}</div></div><div class="amt ${t.amount > 0 ? 'plus' : ''}">${t.amount > 0 ? '+' : ''}${fcfa(t.amount)}</div></div>`).join('') || '<p class="small muted" style="padding:10px 0">Aucune opération.</p>'}</div>
      <button class="btn ghost block mt" onclick="App.A.setCap('${c.id}')">Définir un plafond journalier</button>` };
  });
  A.editAllergies = (id) => {
    const c = user(id);
    modal(`<h2>Allergies de ${esc(c.first)}</h2><div class="check-grid">${Object.entries(ALL).map(([k, l]) => `<label class="check"><input type="checkbox" value="${k}" ${c.allergies.includes(k) ? 'checked' : ''}>${l}</label>`).join('')}</div>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.user('${id}').allergies=[...document.querySelectorAll('#modal input:checked')].map(i=>i.value);App.closeModal();App.toast('Allergies mises à jour','green');App.render()">Enregistrer</button></div>`, { noFocus: true });
  };
  A.setCap = (id) => {
    const c = user(id);
    modal(`<h2>Plafond journalier</h2><p class="muted small mb">${esc(c.first)} ne pourra pas dépenser plus que ce montant par jour.</p><select class="input" id="cap">${[0, 1500, 2000, 2500, 3000].map(v => `<option value="${v}" ${(c.dailyCap || 0) === v ? 'selected' : ''}>${v ? fcfa(v) : 'Aucun plafond'}</option>`).join('')}</select>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.user('${id}').dailyCap=+document.getElementById('cap').value;App.closeModal();App.toast('Plafond enregistré','green');App.render()">Enregistrer</button></div>`);
  };
  // Réservation faite par le parent pour son enfant
  A.pBook = (id) => {
    const c = user(id);
    const days = D.week.filter(d => bookingOpen(d.date) && !activeRes(c.id, d.date));
    if (!days.length) return toast('Aucun jour disponible : tout est déjà réservé ou clos.');
    const day = dayOf(UI.pBookDay && days.find(d => d.date === UI.pBookDay) ? UI.pBookDay : days[0].date);
    modal(`<h2>Réserver pour ${esc(c.first)}</h2>
      <div class="field"><label>Jour</label><select class="input" onchange="App.UI.pBookDay=this.value;App.A.pBook('${id}')">${days.map(d => `<option value="${d.date}" ${d.date === day.date ? 'selected' : ''}>${fmtLong(d.date)}</option>`).join('')}</select></div>
      <div class="field"><label>Plat</label><select class="input" id="pb-dish">${day.plat.map(i => { const d = dish(i), cf = conflicts(d, c); return `<option value="${i}" ${cf.length ? 'disabled' : ''}>${d.name} — ${fcfa(d.price)}${cf.length ? ' (contient ' + ALL[cf[0]].toLowerCase() + ')' : ''}</option>`; }).join('')}</select></div>
      <div class="field"><label>Boisson</label><select class="input" id="pb-drink"><option value="">Aucune</option>${day.boisson.map(i => `<option value="${i}">${dish(i).name} — ${fcfa(dish(i).price)}</option>`).join('')}</select></div>
      <div class="field"><label>Créneau</label><select class="input" id="pb-slot">${D.slots.map(s => `<option value="${s.id}">${s.label}</option>`).join('')}</select></div>
      <p class="small muted">Payé avec le solde de ${esc(c.first)} (${fcfa(c.balance)}).</p>
      <div class="actions"><button class="btn ghost" onclick="App.closeModal()">Annuler</button><button class="btn" onclick="App.A.pBookConfirm('${id}','${day.date}')">Réserver</button></div>`, { noFocus: true });
  };
  A.pBookConfirm = (id, date) => {
    const c = user(id);
    const items = [document.getElementById('pb-dish').value, document.getElementById('pb-drink').value].filter(Boolean);
    const total = items.reduce((s, i) => s + dish(i).price, 0);
    if (total > c.balance) return toast('Solde insuffisant : rechargez d’abord le compte de ' + c.first, 'red');
    if (c.dailyCap && total > c.dailyCap) return toast('Montant supérieur au plafond journalier', 'red');
    c.balance -= total;
    D.reservations.push({ id: App.newId('r'), userId: id, date, slot: document.getElementById('pb-slot').value, items, total, status: 'payee', code: String(100000 + Math.floor(Math.random() * 899999)), servedAt: null, rated: false });
    addTx(id, 'repas', 'Repas — ' + dish(items[0]).name + ' (réservé par le parent)', -total);
    App.alertAllergy(c, D.reservations[D.reservations.length - 1], true);
    notify(id, '<svg class=ic><use href=#i-utensils></use></svg>', 'Repas réservé pour vous', `${dish(items[0]).name}, ${fmtShort(date)}.`, '#/u/resa');
    checkLowBalance(c); UI.pBookDay = null;
    closeModal(); toast('Repas réservé pour ' + c.first, 'green'); render();
  };

  /* ================= P4 — Recharger le compte d'un enfant ================= */
  route('/p/topup/:id', ({ id }) => {
    const p = me();
    if (!p.children.length) return { redirect: '#/p/link' };
    if (!p.children.includes(id)) return { redirect: '#/p/topup/' + p.children[0] };
    const c = user(id), t = UI.ptopup;
    const amount = t.custom ? parseInt(t.custom, 10) || 0 : t.amount;
    return { layout: 'mobile', title: 'Recharger', tab: 'topup', html: `
      <div class="cat-label" style="margin-top:0">Pour</div>
      <div class="stack">${p.children.map(k => { const ch = user(k); return `<button class="pay-method ${k === id ? 'active' : ''}" onclick="App.go('#/p/topup/${k}')">${App.avatar(ch, '', 'width:36px;height:36px;font-size:13px')}<span class="grow"><b>${esc(ch.first)}</b><br><span class="xs muted">Solde : ${fcfa(ch.balance)}</span></span>${k === id ? '<svg class=ic><use href=#i-check></use></svg>' : ''}</button>`; }).join('')}</div>
      <div class="cat-label">Montant</div>
      <div class="amounts">${[2500, 5000, 10000, 20000].map(v => `<button class="amount-btn ${!t.custom && t.amount === v ? 'active' : ''}" onclick="App.UI.ptopup.amount=${v};App.UI.ptopup.custom='';App.render()">${fcfa(v)}</button>`).join('')}</div>
      <div class="field mt"><label>Autre montant</label><input class="input" type="number" min="500" step="500" value="${esc(t.custom)}" placeholder="Ex. 15 000" onchange="App.UI.ptopup.custom=this.value;App.render()"></div>
      <div class="cat-label">Moyen de paiement</div>
      <div class="pay-methods">${payMethods(t.method, "App.UI.ptopup.method='$1';App.render()", true)}</div>
      <div class="toggle mt"><span class="grow small"><b>Recharge automatique</b><br><span class="xs muted">Recharger ${fcfa(amount || 5000)} quand le solde passe sous ${fcfa(c.threshold || 1000)}</span></span><button class="switch ${c.autoTopup ? 'on' : ''}" onclick="App.user('${id}').autoTopup=!App.user('${id}').autoTopup;App.render()" aria-label="Recharge automatique"></button></div>
      <div class="sticky-cta"><button class="btn block" ${amount >= 500 ? '' : 'disabled'} onclick="App.A.pTopup('${id}',${amount})">Payer ${fcfa(amount)}</button></div>` };
  });
  A.pTopup = (id, amount) => {
    const c = user(id), p = me();
    const label = App.providerName(UI.ptopup.method);
    simulatePayment('Recharge pour ' + c.first + ' (' + label + ')', amount, (ref) => {
      c.balance += amount;
      addTx(id, 'recharge', 'Recharge ' + label + ' (par ' + fullName(p) + ')', amount, ref);
      D.payments.unshift({ id: App.newId('pay'), parentId: p.id, childId: id, date: D.NOW.date, amount, method: label, ref });
      D.notifications.filter(n => n.userId === p.id && n.go === '#/p/topup/' + id).forEach(n => { n.read = true; });
      notify(id, '<svg class=ic><use href=#i-coins></use></svg>', 'Compte rechargé', `${p.first} a rechargé ${fcfa(amount)}.`, '#/u/wallet');
      modal(`<div class="center"><div class="success-ico"><svg class=ic><use href=#i-check></use></svg></div><h2>Recharge réussie</h2><p class="muted mt">${fcfa(amount)} crédités sur le compte de ${esc(c.first)}.</p>
        <div class="card flat mt" style="text-align:left"><div class="summary-line"><span class="muted">Nouveau solde</span><b>${fcfa(c.balance)}</b></div><div class="summary-line"><span class="muted">Moyen</span><span>${label}</span></div><div class="summary-line"><span class="muted">Référence</span><span>${ref}</span></div></div>
        <div class="actions" style="justify-content:center"><button class="btn ghost" onclick="App.toast('Reçu PDF téléchargé')">Reçu PDF</button><button class="btn" onclick="App.closeModal();App.go('#/p/home')">Terminer</button></div></div>`);
    });
  };

  /* ================= P5 — Historique des paiements ================= */
  route('/p/payments', () => {
    const p = me();
    const list = D.payments.filter(x => x.parentId === p.id && (UI.payChild === 'all' || x.childId === UI.payChild));
    return { layout: 'mobile', title: 'Historique des paiements', back: '#/p/home', tab: 'home', html: `
      <div class="chips mb"><button class="chip ${UI.payChild === 'all' ? 'active' : ''}" onclick="App.UI.payChild='all';App.render()">Tous</button>${p.children.map(k => `<button class="chip ${UI.payChild === k ? 'active' : ''}" onclick="App.UI.payChild='${k}';App.render()">${esc(user(k).first)}</button>`).join('')}</div>
      <div class="card mb"><div class="row between"><span class="muted small">Total rechargé</span><b>${fcfa(list.reduce((s, x) => s + x.amount, 0))}</b></div></div>
      <div class="card" style="padding:4px 14px">${list.map(x => `<div class="tx" style="cursor:pointer" onclick="App.A.payReceipt('${x.id}')"><div class="ico"><svg class=ic><use href=#i-arrow-up></use></svg></div><div class="grow small"><b>${esc(user(x.childId).first)} · ${x.method}</b><div class="xs muted">${fmtLong(x.date)} · ${x.ref}</div></div><div class="amt plus">+${fcfa(x.amount)}</div></div>`).join('') || '<p class="small muted" style="padding:12px 0">Aucun paiement.</p>'}</div>` };
  });
  A.payReceipt = (id) => {
    const x = D.payments.find(y => y.id === id);
    modal(`<h2>Reçu de paiement</h2><div class="summary-line"><span class="muted">Date</span><span>${fmtLong(x.date)}</span></div><div class="summary-line"><span class="muted">Bénéficiaire</span><span>${esc(fullName(user(x.childId)))}</span></div><div class="summary-line"><span class="muted">Moyen</span><span>${x.method}</span></div><div class="summary-line"><span class="muted">Référence</span><span>${x.ref}</span></div><div class="summary-line total"><span>Montant</span><span>${fcfa(x.amount)}</span></div><div class="actions"><button class="btn ghost" onclick="App.closeModal()">Fermer</button><button class="btn" onclick="App.toast('Reçu PDF téléchargé')">Télécharger le PDF</button></div>`);
  };

  /* ================= P6 — Alertes ================= */
  route('/p/alerts', () => {
    const p = me();
    const list = D.notifications.filter(n => n.userId === p.id);
    return { layout: 'mobile', title: 'Alertes', tab: 'alerts', html: `
      <h3 class="mb">Seuil de solde bas</h3>
      <div class="card stack">${p.children.map(k => { const c = user(k); return `<div class="row between"><span><b>${esc(c.first)}</b><br><span class="xs muted">Solde : ${fcfa(c.balance)}</span></span><select class="input" style="width:auto" onchange="App.user('${k}').threshold=+this.value">${[500, 1000, 2000, 3000, 5000].map(v => `<option value="${v}" ${(c.threshold || 1000) === v ? 'selected' : ''}>${fcfa(v)}</option>`).join('')}</select></div>`; }).join('')}</div>
      <h3 class="mt mb">Recevoir les alertes par</h3>
      <div class="stack">${['push', 'sms', 'email'].map(k => `<div class="toggle"><span class="grow">${{ push: 'Notification push', sms: 'SMS', email: 'E-mail' }[k]}</span><button class="switch ${p.notify[k] ? 'on' : ''}" onclick="App.me().notify['${k}']=!App.me().notify['${k}'];App.render()"></button></div>`).join('')}</div>
      <button class="btn block mt" onclick="App.toast('Préférences enregistrées','green')">Enregistrer</button>
      <div class="section-title"><h3>Alertes reçues</h3><button class="btn sm ghost" onclick="App.A.readAll()">Tout lire</button></div>
      <div class="stack">${list.map(n => `<div class="notif ${n.read ? '' : 'unread'}" onclick="App.A.openNotif('${n.id}')"><div class="ico">${App.notifIcon(n.icon)}</div><div class="grow"><b class="small">${esc(n.title)}</b><p class="small muted">${esc(n.text)}</p><span class="xs muted">${n.date}</span></div></div>`).join('')}</div>` };
  });
})();
