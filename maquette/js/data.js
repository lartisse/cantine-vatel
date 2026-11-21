/* =========================================================
   Cantine de Vatel — données de démonstration
   Tout est en mémoire : un rechargement de page remet la démo à zéro.
   En production, ces données viendront de l'API Laravel.
   ========================================================= */
(function () {
  // Générateur pseudo-aléatoire déterministe (même démo à chaque chargement)
  let seed = 42;
  const rnd = () => { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; };
  const pick = (arr) => arr[Math.floor(rnd() * arr.length)];

  // Date de démo : mardi 24 novembre 2026, 09:40
  const NOW = { date: '2026-11-24', time: '09:40' };

  const ALLERGENS = {
    arachide: 'Arachide', gluten: 'Gluten', lactose: 'Lactose',
    'fruits-de-mer': 'Fruits de mer', poisson: 'Poisson', oeuf: 'Œuf'
  };
  const DIETS = { vegetarien: 'Végétarien', 'sans-porc': 'Sans porc' };
  const CATS = { entree: 'Entrée', plat: 'Plat', dessert: 'Dessert', boisson: 'Boisson' };

  const dishes = [
    { id: 'd1', name: 'Salade composée', cat: 'entree', price: 300, emoji: '🥗', kcal: 120, allergens: ['oeuf'], diets: ['vegetarien', 'sans-porc'], desc: 'Laitue, tomate, concombre, carotte râpée et œuf dur, vinaigrette maison.', recipe: [['i6', 0.08], ['i12', 0.05]] },
    { id: 'd2', name: 'Avocat crevettes', cat: 'entree', price: 500, emoji: '🥑', kcal: 210, allergens: ['fruits-de-mer'], diets: ['sans-porc'], desc: 'Demi-avocat garni de crevettes, sauce cocktail.', recipe: [['i12', 0.1]] },
    { id: 'd3', name: 'Soupe de légumes', cat: 'entree', price: 300, emoji: '🍲', kcal: 90, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Velouté de légumes de saison.', recipe: [['i6', 0.1], ['i7', 0.03]] },
    { id: 'd4', name: 'Attiéké poisson braisé', cat: 'plat', price: 1500, emoji: '🐟', kcal: 640, allergens: ['poisson'], diets: ['sans-porc'], desc: 'Attiéké frais, carpe braisée, oignons et tomates crus, piment à part.', recipe: [['i4', 0.25], ['i3', 0.3], ['i6', 0.05], ['i7', 0.04]] },
    { id: 'd5', name: 'Riz sauce arachide, poulet', cat: 'plat', price: 1200, emoji: '🍗', kcal: 720, allergens: ['arachide'], diets: ['sans-porc'], desc: 'Riz blanc, sauce arachide onctueuse et cuisse de poulet.', recipe: [['i1', 0.15], ['i2', 0.25], ['i8', 0.06]] },
    { id: 'd6', name: 'Foutou sauce graine', cat: 'plat', price: 1300, emoji: '🥘', kcal: 780, allergens: ['poisson'], diets: ['sans-porc'], desc: 'Foutou banane, sauce graine au poisson fumé et viande de bœuf.', recipe: [['i9', 0.3], ['i3', 0.1]] },
    { id: 'd7', name: 'Alloco, poulet braisé', cat: 'plat', price: 1200, emoji: '🍌', kcal: 690, allergens: [], diets: ['sans-porc'], desc: 'Bananes plantain frites, demi-poulet braisé, sauce tomate oignon.', recipe: [['i9', 0.25], ['i2', 0.3], ['i5', 0.03]] },
    { id: 'd8', name: 'Spaghetti bolognaise', cat: 'plat', price: 1000, emoji: '🍝', kcal: 650, allergens: ['gluten', 'lactose'], diets: ['sans-porc'], desc: 'Spaghetti, sauce tomate au bœuf haché, fromage râpé.', recipe: [['i10', 0.12], ['i6', 0.08]] },
    { id: 'd9', name: 'Riz gras aux légumes', cat: 'plat', price: 900, emoji: '🍛', kcal: 560, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Riz cuit dans une sauce tomate aux légumes, version végétarienne.', recipe: [['i1', 0.18], ['i6', 0.1], ['i5', 0.03]] },
    { id: 'd10', name: 'Placali sauce gombo', cat: 'plat', price: 1100, emoji: '🥣', kcal: 600, allergens: ['poisson'], diets: ['sans-porc'], desc: 'Placali, sauce gombo au poisson fumé.', recipe: [['i3', 0.12]] },
    { id: 'd11', name: 'Poulet DG', cat: 'plat', price: 1500, emoji: '🍗', kcal: 820, allergens: [], diets: ['sans-porc'], desc: 'Poulet sauté aux plantains mûrs, carottes et poivrons.', recipe: [['i2', 0.3], ['i9', 0.15], ['i5', 0.03]] },
    { id: 'd12', name: 'Salade de fruits', cat: 'dessert', price: 300, emoji: '🍉', kcal: 110, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Ananas, papaye, mangue et pastèque de saison.', recipe: [['i12', 0.15]] },
    { id: 'd13', name: 'Yaourt nature', cat: 'dessert', price: 250, emoji: '🥛', kcal: 90, allergens: ['lactose'], diets: ['vegetarien', 'sans-porc'], desc: 'Yaourt local au lait entier.', recipe: [['i11', 0.12]] },
    { id: 'd14', name: 'Dêguê', cat: 'dessert', price: 350, emoji: '🍮', kcal: 180, allergens: ['lactose'], diets: ['vegetarien', 'sans-porc'], desc: 'Mil au lait caillé sucré.', recipe: [['i11', 0.15]] },
    { id: 'd15', name: 'Gâteau marbré', cat: 'dessert', price: 400, emoji: '🍰', kcal: 260, allergens: ['gluten', 'lactose', 'oeuf'], diets: ['vegetarien', 'sans-porc'], desc: 'Part de gâteau marbré chocolat-vanille.', recipe: [] },
    { id: 'd16', name: 'Jus de bissap', cat: 'boisson', price: 200, emoji: '🥤', kcal: 80, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Infusion d’hibiscus sucrée, servie fraîche.', recipe: [['i13', 0.02]] },
    { id: 'd17', name: 'Jus de gingembre', cat: 'boisson', price: 200, emoji: '🧃', kcal: 85, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Gingembre frais pressé, citron, menthe.', recipe: [['i14', 0.02]] },
    { id: 'd18', name: 'Eau minérale 50 cl', cat: 'boisson', price: 150, emoji: '💧', kcal: 0, allergens: [], diets: ['vegetarien', 'sans-porc'], desc: 'Bouteille 50 cl.', recipe: [] }
  ];

  // Notes initiales
  const baseRatings = { d4: [4.7, 128], d5: [4.5, 96], d6: [4.2, 61], d7: [4.6, 110], d8: [3.6, 74], d9: [3.9, 40], d10: [3.4, 33], d11: [4.4, 52], d1: [4.0, 30], d2: [4.3, 21], d3: [3.5, 18], d12: [4.6, 70], d13: [4.1, 44], d14: [4.5, 58], d15: [4.2, 39], d16: [4.8, 140], d17: [4.4, 80], d18: [4.0, 12] };
  dishes.forEach(d => { const r = baseRatings[d.id] || [4, 10]; d.rating = r[0]; d.ratingCount = r[1]; d.reviews = []; });
  dishes.find(d => d.id === 'd4').reviews = [
    { who: 'Koffi B.', stars: 5, text: 'Le poisson était bien braisé, portion généreuse.' },
    { who: 'Fatou S.', stars: 4, text: 'Très bon, un peu trop de piment.' }
  ];
  dishes.find(d => d.id === 'd8').reviews = [
    { who: 'Marc A.', stars: 3, text: 'Pâtes trop cuites.' }
  ];

  // Semaine du 23 au 27 novembre 2026
  const week = [
    { date: '2026-11-23', label: 'Lun', entree: ['d1'], plat: ['d7', 'd9'], dessert: ['d12'], boisson: ['d16', 'd18'] },
    { date: '2026-11-24', label: 'Mar', entree: ['d3', 'd2'], plat: ['d4', 'd5'], dessert: ['d14', 'd12'], boisson: ['d16', 'd17', 'd18'] },
    { date: '2026-11-25', label: 'Mer', entree: ['d1'], plat: ['d8', 'd6'], dessert: ['d13'], boisson: ['d17', 'd18'] },
    { date: '2026-11-26', label: 'Jeu', entree: ['d3'], plat: ['d11', 'd9'], dessert: ['d15'], boisson: ['d16', 'd18'] },
    { date: '2026-11-27', label: 'Ven', entree: ['d2'], plat: ['d10', 'd7'], dessert: ['d12'], boisson: ['d16', 'd17'] }
  ];
  // Portions prévues par plat et par jour
  const portions = {};
  week.forEach(d => { portions[d.date] = {}; ['entree', 'plat', 'dessert', 'boisson'].forEach(c => d[c].forEach(id => { portions[d.date][id] = c === 'plat' ? 80 : 60; })); });

  const slots = [
    { id: '11:30', label: '11h30', capacity: 40 },
    { id: '12:00', label: '12h00', capacity: 50 },
    { id: '12:30', label: '12h30', capacity: 50 },
    { id: '13:00', label: '13h00', capacity: 40 }
  ];

  const users = [
    { id: 'u1', role: 'convive', type: 'eleve', photo: null, classPeriod: '2026-T1', first: 'Awa', last: 'Koné', email: 'awa.kone@vatel.ci', phone: '07 07 12 34 56', group: '3e A', matricule: 'VAT-2031', balance: 4500, allergies: ['arachide'], diets: [], parentId: 'p1', threshold: 1000, linkCode: 'AWA-7K2', notify: { push: true, sms: false, email: true } },
    { id: 'u2', role: 'convive', type: 'eleve', photo: null, classPeriod: '2026-T1', first: 'Yao', last: 'Koné', email: 'yao.kone@vatel.ci', phone: '07 07 22 33 44', group: '6e B', matricule: 'VAT-2290', balance: 800, allergies: [], diets: [], parentId: 'p1', threshold: 1000, linkCode: 'YAO-3P9', notify: { push: true, sms: false, email: false } },
    { id: 'p1', role: 'parent', first: 'Ibrahim', last: 'Koné', email: 'i.kone@gmail.com', phone: '05 55 01 02 03', children: ['u1', 'u2'], notify: { push: true, sms: true, email: true } },
    { id: 'a1', role: 'admin', first: 'Mariam', last: 'Diallo', email: 'gestion@cantine-vatel.ci', phone: '27 22 00 00 00', title: 'Gestionnaire de la cantine' },
    { id: 'a2', role: 'admin', first: 'Hervé', last: 'Konan', email: 'h.konan@cantine-vatel.ci', phone: '07 48 11 22 33', title: 'Adjoint gestionnaire' },
    { id: 's1', role: 'superadmin', first: 'Kader', last: 'Touré', email: 'superadmin@cantine-vatel.ci', phone: '07 00 11 22 33', title: 'Super administrateur' },
    { id: 'u4', role: 'convive', type: 'personnel', photo: null, first: 'Jean-Marc', last: 'Aka', email: 'jm.aka@vatel.ci', phone: '07 58 44 33 22', poste: 'Professeur de mathématiques', group: null, matricule: null, balance: 6000, allergies: [], diets: ['sans-porc'], parentId: null, threshold: 1000, linkCode: null, notify: { push: true, sms: false, email: true } },
    { id: 'k1', role: 'comptoir', first: 'Serge', last: 'Kouadio', email: 'comptoir1@cantine-vatel.ci', phone: '', title: 'Agent de comptoir' }
  ];
  // Un élève non lié (pour la démo « Lier un enfant »)
  users.push({ id: 'u3', role: 'convive', type: 'eleve', photo: null, classPeriod: '2026-T2', first: 'Inès', last: 'Koné', email: 'ines.kone@vatel.ci', phone: '', group: 'CM2', matricule: 'VAT-2402', balance: 2000, allergies: ['lactose'], diets: [], parentId: null, threshold: 1000, linkCode: 'INE-5Q1', notify: { push: true, sms: false, email: false } });

  // Autres convives (pour les commandes du jour côté admin)
  const firsts = ['Koffi', 'Aya', 'Moussa', 'Fatou', 'Jean', 'Mariam', 'Ismaël', 'Adjoua', 'Serge', 'Grâce', 'Ibrahim', 'Christelle', 'Yves', 'Aminata', 'Paul', 'Salimata', 'Kevin', 'Nadia', 'Hervé', 'Rokia', 'Arnaud', 'Esther', 'Bakary', 'Prisca', 'Didier', 'Awa', 'Lucien', 'Sandrine', 'Oumar', 'Laetitia'];
  const lasts = ['Kouassi', 'Traoré', 'Yao', 'Ouattara', 'Bamba', 'Coulibaly', 'Diabaté', 'N’Guessan', 'Koffi', 'Soro', 'Touré', 'Aka', 'Gnamien', 'Konan', 'Fofana', 'Doumbia'];
  const groups = ['6e A', '6e B', '5e A', '4e B', '3e A', '2nde C', '1re D', 'Tle A', 'Personnel', 'Personnel'];
  const CLASSES = ['6e A', '6e B', '5e A', '5e B', '4e A', '4e B', '3e A', '3e B', '2nde C', '1re D', 'Tle A', 'CM2'];
  const postes = ['Comptable', 'Professeur de français', 'Surveillant', 'Secrétaire', 'Agent d’entretien', 'Bibliothécaire'];
  for (let i = 0; i < 120; i++) {
    const g = pick(groups), staff = g === 'Personnel';
    const noClass = !staff && rnd() < 0.12;
    users.push({
      id: 'x' + i, role: 'convive', type: staff ? 'personnel' : 'eleve', photo: null, classPeriod: '2026-T2',
      poste: staff ? pick(postes) : null, first: pick(firsts), last: pick(lasts), email: '', phone: '',
      group: staff || noClass ? null : g, matricule: staff ? null : 'VAT-' + (3000 + i), balance: Math.round(rnd() * 12) * 500,
      allergies: rnd() < 0.12 ? [pick(['arachide', 'gluten', 'lactose', 'fruits-de-mer'])] : [], diets: [], parentId: null, threshold: 1000
    });
  }

  // Réservations
  const reservations = [];
  let rid = 1;
  const code6 = () => String(100000 + Math.floor(rnd() * 899999));
  const makeRes = (userId, day, slot, items, status) => {
    const total = items.reduce((s, id) => s + dishes.find(d => d.id === id).price, 0);
    const r = { id: 'r' + (rid++), userId, date: day.date, slot, items, total, status, code: code6(), servedAt: status === 'servie' ? slot.replace(':', 'h') : null, rated: status === 'servie' && rnd() < 0.6 };
    reservations.push(r);
    return r;
  };
  // Lundi (passé) et mardi (aujourd'hui) pour les autres convives
  const others = users.filter(u => u.id.startsWith('x'));
  [week[0], week[1]].forEach((day, di) => {
    const count = di === 0 ? 112 : 96;
    for (let i = 0; i < count; i++) {
      const u = others[i % others.length];
      const items = [pick(day.plat)];
      if (rnd() < 0.5) items.unshift(pick(day.entree));
      if (rnd() < 0.6) items.push(pick(day.dessert));
      if (rnd() < 0.7) items.push(pick(day.boisson));
      const slot = pick(slots).id;
      const status = di === 0 ? (rnd() < 0.91 ? 'servie' : 'non-retiree') : 'payee';
      makeRes(u.id, day, slot, items, status);
    }
  });
  // Mercredi : quelques réservations anticipées
  for (let i = 0; i < 41; i++) { const u = others[(i * 3) % others.length]; makeRes(u.id, week[2], pick(slots).id, [pick(week[2].plat), pick(week[2].boisson)], 'payee'); }
  // Historique d'Awa (lundi servi) et Yao (lundi servi, mardi réservé)
  const awaMon = makeRes('u1', week[0], '12:00', ['d1', 'd7', 'd12', 'd16'], 'servie'); awaMon.rated = false; awaMon.code = '481516';
  const yaoMon = makeRes('u2', week[0], '11:30', ['d9', 'd16'], 'servie'); yaoMon.rated = true;
  const yaoTue = makeRes('u2', week[1], '11:30', ['d4', 'd12'], 'payee'); yaoTue.code = '204863';

  // Transactions
  const transactions = [
    { id: 't1', userId: 'u1', date: '2026-11-02', type: 'recharge', label: 'Recharge Wave (par Ibrahim Koné)', amount: 10000, ref: 'WV-88213' },
    { id: 't2', userId: 'u1', date: '2026-11-03', type: 'repas', label: 'Repas — Attiéké poisson braisé', amount: -1700 },
    { id: 't3', userId: 'u1', date: '2026-11-05', type: 'repas', label: 'Repas — Alloco, poulet braisé', amount: -1400 },
    { id: 't4', userId: 'u1', date: '2026-11-10', type: 'repas', label: 'Repas — Poulet DG', amount: -1700 },
    { id: 't5', userId: 'u1', date: '2026-11-12', type: 'remboursement', label: 'Annulation — Spaghetti bolognaise', amount: 1200 },
    { id: 't6', userId: 'u1', date: '2026-11-16', type: 'recharge', label: 'Recharge Orange Money', amount: 2500, ref: 'OM-55102' },
    { id: 't7', userId: 'u1', date: '2026-11-18', type: 'repas', label: 'Repas — Riz gras aux légumes', amount: -1100 },
    { id: 't8', userId: 'u1', date: '2026-11-23', type: 'repas', label: 'Repas — Alloco, poulet braisé', amount: -2000 },
    { id: 't9', userId: 'u2', date: '2026-11-09', type: 'recharge', label: 'Recharge Wave (par Ibrahim Koné)', amount: 5000, ref: 'WV-80044' },
    { id: 't10', userId: 'u2', date: '2026-11-23', type: 'repas', label: 'Repas — Riz gras aux légumes', amount: -1100 },
    { id: 't11', userId: 'u2', date: '2026-11-24', type: 'repas', label: 'Repas — Attiéké poisson braisé', amount: -1800 }
  ];
  const payments = [
    { id: 'pay1', parentId: 'p1', childId: 'u1', date: '2026-11-02', amount: 10000, method: 'Wave', ref: 'WV-88213' },
    { id: 'pay2', parentId: 'p1', childId: 'u2', date: '2026-11-09', amount: 5000, method: 'Wave', ref: 'WV-80044' },
    { id: 'pay3', parentId: 'p1', childId: 'u1', date: '2026-10-19', amount: 10000, method: 'Orange Money', ref: 'OM-41120' },
    { id: 'pay4', parentId: 'p1', childId: 'u2', date: '2026-10-19', amount: 5000, method: 'Orange Money', ref: 'OM-41121' }
  ];

  const notifications = [
    { id: 'n1', userId: 'u1', date: '24/11 · 07:30', icon: '<svg class=ic><use href=#i-clock></use></svg>', title: 'Pensez à réserver', text: 'Les réservations pour aujourd’hui ferment à 10h00.', go: '#/u/menu', read: false },
    { id: 'n2', userId: 'u1', date: '23/11 · 13:10', icon: '<svg class=ic-star><use href=#i-star></use></svg>', title: 'Comment était votre repas ?', text: 'Notez l’Alloco, poulet braisé de lundi.', go: '#/u/rate/' + awaMon.id, read: false },
    { id: 'n3', userId: 'u1', date: '22/11 · 18:00', icon: '<svg class=ic><use href=#i-calendar></use></svg>', title: 'Menu de la semaine publié', text: 'Le menu du 23 au 27 novembre est en ligne.', go: '#/u/menu', read: true },
    { id: 'n4', userId: 'p1', date: '24/11 · 08:05', icon: '<svg class=ic><use href=#i-alert></use></svg>', title: 'Solde bas — Yao', text: 'Le solde de Yao est de 800 FCFA (seuil : 1 000 FCFA).', go: '#/p/topup/u2', read: false },
    { id: 'n5', userId: 'p1', date: '24/11 · 07:58', icon: '<svg class=ic><use href=#i-utensils></use></svg>', title: 'Repas réservé — Yao', text: 'Attiéké poisson braisé, créneau 11h30.', go: '#/p/child/u2', read: false },
    { id: 'n6', userId: 'p1', date: '23/11 · 12:04', icon: '<svg class=ic><use href=#i-check-circle></use></svg>', title: 'Repas servi — Awa', text: 'Alloco, poulet braisé servi à 12h04.', go: '#/p/child/u1', read: true }
  ];

  // Cas de démo : un élève allergique à l'arachide a réservé le riz sauce arachide aujourd'hui
  const riskRes = reservations.find(r => r.date === '2026-11-24' && r.items.includes('d5') && r.userId.startsWith('x'));
  const riskUser = users.find(u => u.id === riskRes.userId);
  riskUser.allergies = ['arachide'];
  riskRes.allergyConflict = [{ dishId: 'd5', allergens: ['arachide'] }];
  ['a1', 'a2', 's1'].forEach(aid => notifications.unshift({ id: 'na-' + aid, userId: aid, date: '24/11 · 08:12', icon: '<svg class=ic><use href=#i-alert></use></svg>', title: 'Allergie : réservation à risque',
    text: `${riskUser.first} ${riskUser.last} (${riskUser.group || riskUser.poste || 'sans classe'}) a réservé « Riz sauce arachide, poulet », qui contient de l’arachide, allergène déclaré dans son profil. Mardi 24 nov., créneau ${riskRes.slot.replace(':', 'h')}.`,
    go: '#/a/notifs', resId: riskRes.id, kind: 'allergy', read: false }));

  const ingredients = [
    { id: 'i1', name: 'Riz', unit: 'kg', stock: 180, threshold: 60 },
    { id: 'i2', name: 'Poulet', unit: 'kg', stock: 38, threshold: 40 },
    { id: 'i3', name: 'Poisson (carpe)', unit: 'kg', stock: 22, threshold: 30 },
    { id: 'i4', name: 'Attiéké', unit: 'kg', stock: 45, threshold: 20 },
    { id: 'i5', name: 'Huile', unit: 'L', stock: 24, threshold: 10 },
    { id: 'i6', name: 'Tomates', unit: 'kg', stock: 30, threshold: 15 },
    { id: 'i7', name: 'Oignons', unit: 'kg', stock: 26, threshold: 10 },
    { id: 'i8', name: 'Pâte d’arachide', unit: 'kg', stock: 4, threshold: 6 },
    { id: 'i9', name: 'Banane plantain', unit: 'kg', stock: 70, threshold: 40 },
    { id: 'i10', name: 'Spaghetti', unit: 'kg', stock: 25, threshold: 10 },
    { id: 'i11', name: 'Lait', unit: 'L', stock: 18, threshold: 15 },
    { id: 'i12', name: 'Fruits frais', unit: 'kg', stock: 0, threshold: 15 },
    { id: 'i13', name: 'Fleurs de bissap', unit: 'kg', stock: 6, threshold: 3 },
    { id: 'i14', name: 'Gingembre', unit: 'kg', stock: 5, threshold: 3 }
  ];
  const stockMoves = [
    { date: '23/11', ing: 'Riz', type: 'Entrée', qty: '+100 kg', who: 'M. Diallo' },
    { date: '23/11', ing: 'Poulet', type: 'Sortie', qty: '−32 kg', who: 'Cuisine' },
    { date: '23/11', ing: 'Fruits frais', type: 'Perte', qty: '−4 kg', who: 'M. Diallo' },
    { date: '20/11', ing: 'Attiéké', type: 'Entrée', qty: '+40 kg', who: 'M. Diallo' }
  ];

  // Historique sur 4 semaines pour les rapports (réservés / servis / recettes)
  const history = [];
  const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'];
  const startDates = ['26/10', '02/11', '09/11', '16/11'];
  startDates.forEach((w, wi) => days.forEach((d, di) => {
    const reserved = 90 + Math.round(rnd() * 35) + (di === 2 ? -15 : 0);
    const served = Math.round(reserved * (0.84 + rnd() * 0.1));
    const dd = String(parseInt(w, 10) + di).padStart(2, '0') + '/' + w.split('/')[1];
    history.push({ week: w, day: d, date: dd, reserved, served, revenue: reserved * (1350 + Math.round(rnd() * 200)) });
  }));

  // Avis par menu (un menu = le repas d'un jour)
  const reviewTexts = [
    [5, 'Excellent alloco, le poulet était bien braisé.'], [4, 'Bon repas, un peu trop pimenté.'], [4, 'La salade de fruits était parfaite.'],
    [3, 'Portions un peu petites aujourd’hui.'], [5, 'Rien à dire, très bon.'], [2, 'Le riz était froid au deuxième créneau.'],
    [4, 'Bon menu, merci à l’équipe.'], [1, 'Ce menu est nul, vous êtes des idiots en cuisine.'],
    [5, 'Gagnez 50 000 FCFA en 24h, cliquez ici : bit.ly/argent-facile'], [3, 'Quelqu’un veut acheter mon téléphone ? Écrivez-moi.']
  ];
  const reviews = [];
  reviewTexts.forEach((t, i) => {
    const r = reservations.find((x, k) => k >= i * 9 && x.date === '2026-11-23' && x.status === 'servie');
    reviews.push({ id: 'rv' + (i + 1), userId: r.userId, date: '2026-11-23', resId: r.id, stars: t[0], comment: t[1], tags: [], status: 'visible', flagged: false, flagWords: [], createdAt: '23/11 · ' + (13 + (i % 3)) + ':' + String(10 + i * 4).padStart(2, '0') });
  });
  reviews.push({ id: 'rv11', userId: 'u2', date: '2026-11-23', resId: yaoMon.id, stars: 4, comment: 'J’ai bien aimé le riz gras.', tags: ['Délicieux'], status: 'visible', flagged: false, flagWords: [], createdAt: '23/11 · 13:40' });

  reviews.forEach(r => { r.type = 'menu'; });
  const monServed = reservations.filter(r => r.date === '2026-11-23' && r.status === 'servie');
  [['d7', 5, 'Poulet bien braisé, l’alloco était croustillant.'], ['d7', 4, ''], ['d9', 3, 'Un peu fade, il manquait du sel.'], ['d12', 5, 'Fruits très frais.'], ['d1', 4, '']].forEach(([dishId, stars, comment], k) => {
    const r = monServed.find((x, i) => i > 40 + k * 7 && x.items.includes(dishId)) || monServed[k];
    reviews.push({ id: 'rd' + (k + 1), type: 'dish', dishId, userId: r.userId, date: '2026-11-23', resId: r.id, stars, comment, tags: [], status: 'visible', flagged: false, flagWords: [], createdAt: '23/11 · 13:' + (20 + k * 5) });
  });

  const forbiddenWords = ['idiot', 'idiots', 'imbécile', 'imbéciles', 'arnaque', 'bit.ly', 'http', 'www', 'cliquez', 'gagnez', 'connard'];

  const deletionRequests = [
    { id: 'dr1', userId: 'x7', reason: 'Je quitte l’établissement à la fin du mois.', date: '2026-11-20', status: 'pending' },
    { id: 'dr2', userId: 'x15', reason: '', date: '2026-11-22', status: 'pending' }
  ];

  // Compte déjà en corbeille (pour la démo de la purge)
  const trashed = users.find(u => u.id === 'x30');
  Object.assign(trashed, { status: 'trashed', trashedAt: '2026-10-22', trashReason: 'Départ de l’établissement (demande de l’utilisateur)', trashedBy: 'Kader Touré' });
  const trashed2 = users.find(u => u.id === 'x31');
  Object.assign(trashed2, { status: 'trashed', trashedAt: '2026-11-18', trashReason: 'Compte en double', trashedBy: 'Kader Touré' });

  const audit = [
    { at: '22/10 · 16:05', who: 'Kader Touré', role: 'superadmin', action: 'Désactivation', target: users.find(u => u.id === 'x30').first + ' ' + users.find(u => u.id === 'x30').last, detail: 'Motif : Départ de l’établissement (demande de l’utilisateur)' },
    { at: '12/11 · 09:12', who: 'Kader Touré', role: 'superadmin', action: 'Clés API', target: 'Wave (Production)', detail: 'Clé secrète remplacée' },
    { at: '14/11 · 11:30', who: 'Kader Touré', role: 'superadmin', action: 'Changement de rôle', target: 'Hervé Konan', detail: 'Rôle : Convive → Administrateur' },
    { at: '18/11 · 08:47', who: 'Kader Touré', role: 'superadmin', action: 'Désactivation', target: users.find(u => u.id === 'x31').first + ' ' + users.find(u => u.id === 'x31').last, detail: 'Motif : Compte en double' }
  ].reverse();

  // Moyens de paiement : seules les versions masquées des clés existent côté interface
  const mk = (prefix, last4) => ({ masked: prefix + '••••••••' + last4, savedAt: '12/11/2026' });
  const env = (enabled, keys) => ({ enabled, keys: keys || {}, lastTest: null });
  const providers = [
    { id: 'wave', name: 'Wave', kind: 'Mobile Money', color: '#1dc8f2', logo: 'W', fields: [['apiKey', 'Clé API'], ['webhookSecret', 'Secret du webhook']],
      test: env(true, { apiKey: mk('wave_sn_test_', '91aB'), webhookSecret: mk('whsec_', '0c7D') }), prod: env(true, { apiKey: mk('wave_sn_live_', '4F2a'), webhookSecret: mk('whsec_', '88eE') }) },
    { id: 'om', name: 'Orange Money', kind: 'Mobile Money', color: '#ff7900', logo: 'OM', fields: [['clientId', 'Client ID'], ['clientSecret', 'Client secret'], ['merchantKey', 'Clé marchand']],
      test: env(true, { clientId: mk('om_', '77a1'), clientSecret: mk('cs_test_', 'Z9q2'), merchantKey: mk('mk_', '1234') }), prod: env(true, { clientId: mk('om_', '0b3c'), clientSecret: mk('cs_live_', 'Kp41'), merchantKey: mk('mk_', '8812') }) },
    { id: 'momo', name: 'MTN MoMo', kind: 'Mobile Money', color: '#ffcc00', logo: 'M', fields: [['subscriptionKey', 'Clé d’abonnement'], ['apiUser', 'API user'], ['apiKey', 'Clé API']],
      test: env(true, { subscriptionKey: mk('', 'e3f9'), apiUser: mk('', '5a6b'), apiKey: mk('', 'c0de') }), prod: env(true, { subscriptionKey: mk('', '19ff'), apiUser: mk('', '72aa'), apiKey: mk('', 'b6d1') }) },
    { id: 'moov', name: 'Moov Money', kind: 'Mobile Money', color: '#0066b3', logo: 'Mv', fields: [['merchantId', 'ID marchand'], ['apiKey', 'Clé API']], test: env(false), prod: env(false) },
    { id: 'card', name: 'Carte bancaire', kind: 'Carte', color: '#1d1d1b', logo: '💳', fields: [['publicKey', 'Clé publique'], ['secretKey', 'Clé secrète']],
      test: env(true, { publicKey: mk('pk_test_', 'aa01'), secretKey: mk('sk_test_', 'Qe7x') }), prod: env(true, { publicKey: mk('pk_live_', 'b2c3'), secretKey: mk('sk_live_', '4F2a') }) },
    { id: 'cinetpay', name: 'CinetPay', kind: 'Agrégateur', color: '#2e7d32', logo: 'CP', fields: [['siteId', 'Site ID'], ['apiKey', 'Clé API'], ['secretKey', 'Clé secrète']], test: env(false), prod: env(false) },
    { id: 'paydunya', name: 'PayDunya', kind: 'Agrégateur', color: '#5b2d90', logo: 'PD', fields: [['masterKey', 'Master key'], ['privateKey', 'Clé privée'], ['token', 'Token']], test: env(false), prod: env(false) }
  ];

  window.DB = {
    CLASSES, reviews, forbiddenWords, deletionRequests, audit, providers,
    settings: { purgeDays: 30, period: { id: '2026-T2', label: '2e trimestre 2026-2027' } },
    sudoUntil: 0,
    NOW, ALLERGENS, DIETS, CATS, dishes, week, portions, slots, users, reservations,
    transactions, payments, notifications, ingredients, stockMoves, history,
    cutoff: '10:00', menuPublished: { '2026-11-23': true, '2026-11-30': false },
    nextWeek: [
      { date: '2026-11-30', label: 'Lun', entree: [], plat: ['d4'], dessert: [], boisson: [] },
      { date: '2026-12-01', label: 'Mar', entree: [], plat: [], dessert: [], boisson: [] },
      { date: '2026-12-02', label: 'Mer', entree: [], plat: [], dessert: [], boisson: [] },
      { date: '2026-12-03', label: 'Jeu', entree: [], plat: [], dessert: [], boisson: [] },
      { date: '2026-12-04', label: 'Ven', entree: [], plat: [], dessert: [], boisson: [] }
    ],
    kioskLog: [],
    session: null
  };
})();
