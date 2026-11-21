/* =========================================================
   Traduction de l'interface mobile (élève, personnel, parent) : Français / English
   Le français reste la langue par défaut. La langue est choisie dans Profil › Préférences
   et enregistrée sur le compte (u.lang). Les noms de plats et les contenus saisis
   (avis, messages) ne sont pas traduits.
   En production (Laravel) : fichiers lang/fr.json et lang/en.json + __('…') dans les vues Blade.
   ========================================================= */
(function () {
  const EXACT = {
    // Navigation
    'Accueil': 'Home', 'Menu': 'Menu', 'Réservations': 'Bookings', 'Portefeuille': 'Wallet', 'Profil': 'Profile',
    'Enfants': 'Children', 'Recharger': 'Top up', 'Alertes': 'Alerts', 'Notifications': 'Notifications',
    // Accueil convive
    'Mon solde': 'My balance', '+ Recharger': '+ Top up', 'Historique': 'History', 'Plats du jour': 'Today’s dishes',
    'Tout le menu ›': 'Full menu ›', 'Suggestion pour vous': 'Suggested for you', 'IA': 'AI', 'Réserver ce plat': 'Book this dish',
    'Vous n’avez pas encore réservé': 'You haven’t booked yet', 'Réserver maintenant': 'Book now',
    'Il reste jusqu’à 10h00 pour réserver le repas d’aujourd’hui.': 'You have until 10:00 to book today’s meal.',
    'Mon repas d’aujourd’hui': 'Today’s meal', 'Afficher mon QR code': 'Show my QR code', 'Noter ce repas': 'Rate this meal',
    'Réservations du jour closes': 'Today’s bookings are closed', 'Oui, c’est bien ça': 'Yes, that’s right', 'Corriger': 'Correct it',
    'Ajouter ma classe': 'Add my class', 'Ignorer': 'Skip', 'Êtes-vous toujours en': 'Are you still in',
    'Voulez-vous indiquer votre classe ? Cela aide la cantine à mieux prévoir les repas.': 'Would you like to add your class? It helps the canteen plan meals.',
    'Solde bas : rechargez pour pouvoir réserver.': 'Low balance: top up to be able to book.',
    // Menu
    'Menu de la semaine': 'Weekly menu', 'Tout': 'All', 'Entrées': 'Starters', 'Plats': 'Mains', 'Desserts': 'Desserts', 'Boissons': 'Drinks',
    'Tous régimes': 'All diets', 'Compatible avec mes allergies': 'Safe for my allergies', 'Aucune allergie déclarée': 'No allergy declared',
    'Avis': 'Reviews', 'Sans allergène majeur': 'No major allergen', 'Épuisé': 'Sold out', 'Jour passé': 'Past day',
    'Donner mon avis sur ce menu': 'Review this menu', 'Effacer les filtres': 'Clear filters',
    'Aucun plat ne correspond à ces filtres ce jour-là.': 'No dish matches these filters that day.',
    'Déjà réservé — voir ma réservation': 'Already booked — see my booking',
    'Lun': 'Mon', 'Mar': 'Tue', 'Mer': 'Wed', 'Jeu': 'Thu', 'Ven': 'Fri',
    // Fiche plat
    'Ingrédients': 'Ingredients', 'Allergènes': 'Allergens', 'Régimes': 'Diets', 'Non renseignés': 'Not provided',
    'Aucun allergène majeur': 'No major allergen', 'Retour au menu': 'Back to menu',
    // Allergènes, régimes, catégories, statuts
    'Arachide': 'Peanut', 'Gluten': 'Gluten', 'Lactose': 'Lactose', 'Fruits de mer': 'Seafood', 'Poisson': 'Fish', 'Œuf': 'Egg',
    'Végétarien': 'Vegetarian', 'Végétarien': 'Vegetarian', 'Sans porc': 'Pork-free',
    'Entrée': 'Starter', 'Plat': 'Main', 'Dessert': 'Dessert', 'Boisson': 'Drink',
    'Payée': 'Paid', 'Servie': 'Served', 'Non retirée': 'Not collected', 'Annulée': 'Cancelled',
    // Réservation
    'Réserver': 'Book', 'Modifier ma réservation': 'Edit my booking', '1. Composez votre commande': '1. Build your order',
    'Choisissez un ou plusieurs plats, puis ajustez les quantités (5 max par article).': 'Pick one or more dishes, then adjust quantities (max 5 per item).',
    '2. Choisissez votre créneau': '2. Choose your time slot', 'Places restantes par créneau': 'Seats left per slot', 'Complet': 'Full',
    '3. Récapitulatif': '3. Summary', 'Aucun plat sélectionné.': 'No dish selected.', 'Solde actuel': 'Current balance',
    'Solde après paiement': 'Balance after payment', 'Choisissez au moins un plat': 'Choose at least one dish', 'Choisissez un créneau': 'Choose a time slot',
    'Modifiable ou annulable jusqu’à 10h00 le jour même': 'Can be changed or cancelled until 10:00 on the day',
    'Réservation confirmée': 'Booking confirmed', 'Repas réservé !': 'Meal booked!', 'Payé': 'Paid', 'Voir mon QR code': 'Show my QR code',
    'Retour à l’accueil': 'Back to home', 'Réservations closes': 'Bookings closed', 'Déjà réservé': 'Already booked', 'Voir ma réservation': 'See my booking',
    // Mes réservations
    'Mes réservations': 'My bookings', 'Passées': 'Past', 'Ma réservation': 'My booking', 'Afficher le QR code ›': 'Show QR code ›',
    'Noter ce repas ›': 'Rate this meal ›', 'Aucune réservation à venir': 'No upcoming booking', 'Aucune réservation passée': 'No past booking',
    'Consultez le menu pour réserver votre prochain repas.': 'Check the menu to book your next meal.', 'Voir le menu': 'See the menu',
    'Code de secours à donner au comptoir': 'Backup code for the counter', 'Disponible hors ligne': 'Available offline',
    'Créneau': 'Slot', 'Total payé': 'Total paid', 'Modifier': 'Edit', 'Annuler': 'Cancel', 'Voir mon avis': 'See my review',
    // Portefeuille et recharge
    'Solde disponible': 'Available balance', '+ Recharger mon compte': '+ Top up my account', 'Novembre': 'November', 'Octobre': 'October',
    'Dépensé ce mois': 'Spent this month', 'Repas pris': 'Meals taken', 'Aucune transaction ce mois-ci.': 'No transaction this month.',
    'Montant': 'Amount', 'Autre montant': 'Other amount', 'Moyen de paiement': 'Payment method', 'Numéro Mobile Money': 'Mobile Money number',
    'Carte bancaire': 'Bank card', 'repas': 'meal', 'recharge': 'top-up', 'remboursement': 'refund', 'ajustement': 'adjustment',
    // Avis
    'Avis sur le menu': 'Menu review', 'Votre note *': 'Your rating *', 'Commentaire': 'Comment', '(facultatif)': '(optional)',
    'Les avis sont modérés : insultes, spam et hors sujet sont masqués.': 'Reviews are moderated: insults, spam and off-topic posts are hidden.',
    'En quelques mots': 'In a few words', 'Délicieux': 'Delicious', 'Bien assaisonné': 'Well seasoned', 'Copieux': 'Generous',
    'Trop salé': 'Too salty', 'Trop pimenté': 'Too spicy', 'Portion petite': 'Small portion', 'Servi froid': 'Served cold',
    'Avez-vous tout mangé ?': 'Did you finish your meal?', 'Tout mangé': 'Ate everything', 'Laissé une partie': 'Left some',
    'Laissé beaucoup': 'Left a lot', 'Cette réponse aide à réduire le gaspillage.': 'This answer helps reduce food waste.',
    'Publier mon avis': 'Post my review', 'Plus tard': 'Later', 'Mon avis': 'My review', 'Publié': 'Published',
    'En attente de vérification': 'Pending review', 'Masqué par la modération': 'Hidden by moderation', 'Donner mon avis': 'Review',
    'Seuls les convives ayant réservé ce menu peuvent le noter.': 'Only diners who booked this menu can review it.', 'Fermer': 'Close',
    'Pas encore d’avis sur ce menu.': 'No reviews for this menu yet.',
    'Donner mon avis': 'Leave a review', 'Mes avis': 'My reviews', 'Les plats': 'The dishes', 'Le menu dans son ensemble': 'The menu as a whole',
    'Notez les plats que vous avez pris, le menu dans son ensemble, ou les deux. Touchez une étoile à nouveau pour l’effacer.': 'Rate the dishes you had, the menu as a whole, or both. Tap a star again to clear it.',
    'Un mot sur ce plat ? (facultatif)': 'A word about this dish? (optional)', 'Notez au moins un plat ou le menu': 'Rate at least one dish or the menu',
    'Publier mon avis': 'Post my review', 'Avis sur ce plat': 'Reviews of this dish', 'Pas encore d’avis écrit sur ce plat.': 'No written review of this dish yet.',
    'Par plat': 'By dish', 'Sur le menu': 'On the menu', 'Tout mangé': 'Ate everything', 'Laissé une partie': 'Left some', 'Laissé beaucoup': 'Left a lot',
    // Notifications
    'Tout marquer comme lu': 'Mark all as read', 'Tout lire': 'Read all', 'Aucune notification.': 'No notifications.',
    // Profil
    'Mon profil': 'My profile', 'Mes informations': 'My information', 'Prénom': 'First name', 'Nom': 'Last name',
    'Prénom *': 'First name *', 'Nom *': 'Last name *', 'Type de compte': 'Account type', 'Classe': 'Class', 'Matricule': 'Student ID',
    'Poste': 'Position', 'Téléphone': 'Phone', 'E-mail': 'Email', 'Élève': 'Student', 'Personnel': 'Staff', 'Parent': 'Parent',
    'Compte parent': 'Parent account', 'Enfants liés': 'Linked children', 'Non renseignée': 'Not provided',
    'Les champs facultatifs peuvent être complétés, modifiés ou vidés à tout moment.': 'Optional fields can be filled in, changed or cleared at any time.',
    'Galerie': 'Gallery', 'Appareil photo': 'Camera', 'Modifier la photo': 'Change photo', 'Supprimer': 'Delete',
    'JPG, PNG ou WebP · 5 Mo maximum · recadrée et compressée automatiquement': 'JPG, PNG or WebP · 5 MB max · cropped and compressed automatically',
    'Lier un parent': 'Link a parent', 'Votre parent scanne ce QR code depuis son application': 'Your parent scans this QR code from their app',
    '(Enfants › Ajouter un enfant)': '(Children › Add a child)', 'Code de secours :': 'Backup code:', '· la liaison n’utilise pas le matricule.': '· linking does not use the student ID.',
    'Générer un nouveau QR code': 'Generate a new QR code', 'Lié à': 'Linked to',
    'Régime et allergies': 'Diet and allergies', 'Les plats contenant ces allergènes sont signalés dans le menu.': 'Dishes containing these allergens are flagged in the menu.',
    'Alerte de solde bas': 'Low balance alert', 'M’avertir sous': 'Warn me below', 'Préférences': 'Preferences',
    'Langue de l’interface': 'Interface language', 'Notifications push': 'Push notifications', 'Notifications SMS': 'SMS notifications',
    'Notifications e-mail': 'Email notifications', 'Enregistrer': 'Save', 'Changer le mot de passe': 'Change password', 'Se déconnecter': 'Log out', 'Changer le mot de passe': 'Change password', 'Se déconnecter': 'Log out', 'Langue de l’interface': 'Interface language',
    'Suppression du compte': 'Account deletion', 'Demander la suppression de mon compte': 'Request account deletion',
    'Votre demande sera transmise au super administrateur. Vos données sont d’abord conservées en corbeille, puis effacées définitivement.': 'Your request will be sent to the super administrator. Your data is first kept in the trash, then permanently erased.',
    'Annuler ma demande': 'Cancel my request',
    // Parent
    'Ajouter un enfant': 'Add a child', '+ Ajouter un enfant': '+ Add a child', 'Voir ses repas': 'See meals', 'Pas de réservation': 'No booking',
    'Aujourd’hui': 'Today', 'Solde': 'Balance', 'Ce mois-ci': 'This month', 'Total rechargé': 'Total topped up',
    'Repas pris par vos enfants': 'Meals taken by your children', 'Historique des paiements ›': 'Payment history ›', 'Historique des paiements': 'Payment history',
    'Repas de la semaine': 'This week’s meals', 'Pas réservé': 'Not booked', 'Aucun repas': 'No meal', 'Réservations à venir': 'Upcoming bookings',
    'Aucune réservation à venir.': 'No upcoming booking.', 'Équilibre alimentaire': 'Nutritional balance', 'Apport moyen par repas': 'Average intake per meal',
    'Allergies déclarées': 'Declared allergies', 'Aucune': 'None', 'Dernières dépenses': 'Recent spending', 'Définir un plafond journalier': 'Set a daily limit',
    'Pour': 'For', 'Recharge automatique': 'Automatic top-up', 'Seuil de solde bas': 'Low balance threshold', 'Recevoir les alertes par': 'Receive alerts by',
    'Notification push': 'Push notification', 'SMS': 'SMS', 'Alertes reçues': 'Alerts received', 'Tous': 'All', 'Valider le code': 'Confirm code',
    'Scanner le QR code': 'Scan the QR code', 'Demandez à votre enfant d’ouvrir': 'Ask your child to open', 'Profil › Lier un parent': 'Profile › Link a parent',
    'et scannez le QR code affiché.': 'and scan the QR code shown.', 'Le scan ne fonctionne pas ? Saisir le code de secours': 'Scan not working? Enter the backup code',
    'La liaison se fait uniquement par le QR code de l’élève (le matricule n’est pas utilisé). Vous pouvez lier plusieurs enfants.': 'Linking only uses the student’s QR code (not the student ID). You can link several children.',
    'Confirmer le lien': 'Confirm link', 'Ce n’est pas mon enfant': 'This is not my child', 'Élève trouvé': 'Student found', 'Classe non renseignée': 'Class not provided',
    'Le solde de': 'The balance of', 'Réserver pour': 'Book for',
    // Notifications
    'Pensez à réserver': 'Remember to book', 'Comment était votre repas ?': 'How was your meal?', 'Menu de la semaine publié': 'Weekly menu published',
    'Le menu du 23 au 27 novembre est en ligne.': 'The menu for November 23–27 is online.', 'Les réservations pour aujourd’hui ferment à 10h00.': 'Bookings for today close at 10:00.',
    'Solde bas': 'Low balance', 'Bon appétit !': 'Enjoy your meal!', 'Compte rechargé': 'Account topped up', 'Repas réservé pour vous': 'Meal booked for you',
    'Message de la cantine': 'Message from the canteen', 'Compte parent lié': 'Parent account linked', 'Nouveau menu publié': 'New menu published',
    'Demande de suppression refusée': 'Deletion request declined', 'Réservation annulée': 'Booking cancelled', 'Menu mis à jour': 'Menu updated',
    // Placeholders
    'Votre avis aide la cuisine à s’améliorer': 'Your feedback helps the kitchen improve', 'Ex. Comptable': 'E.g. Accountant', 'Ex. INE-5Q1': 'E.g. INE-5Q1',
    // Toasts
    'Profil enregistré': 'Profile saved', 'Nom et prénom sont obligatoires': 'First and last name are required', 'Le prénom est obligatoire': 'First name is required',
    'Le nom est obligatoire': 'Last name is required', 'Photo supprimée': 'Photo deleted', 'Image trop lourde (5 Mo maximum)': 'Image too large (5 MB max)',
    'Format non accepté (JPG, PNG ou WebP uniquement).': 'Format not accepted (JPG, PNG or WebP only).', 'Merci, classe confirmée': 'Thanks, class confirmed',
    'Classe mise à jour': 'Class updated', 'Merci, votre avis est publié !': 'Thanks, your review is published!', 'Ce plat est épuisé': 'This dish is sold out',
    'E-mail de changement de mot de passe envoyé': 'Password change email sent', 'Code copié': 'Code copied', 'Langue : Français': 'Langue : Français', 'Language: English': 'Language: English'
  };
  const DAYS = { lundi: 'Monday', mardi: 'Tuesday', mercredi: 'Wednesday', jeudi: 'Thursday', vendredi: 'Friday', samedi: 'Saturday', dimanche: 'Sunday' };
  const SDAYS = { 'lun.': 'Mon', 'mar.': 'Tue', 'mer.': 'Wed', 'jeu.': 'Thu', 'ven.': 'Fri', 'sam.': 'Sat', 'dim.': 'Sun' };
  const MONTHS = { janvier: 'January', février: 'February', mars: 'March', avril: 'April', mai: 'May', juin: 'June', juillet: 'July', août: 'August', septembre: 'September', octobre: 'October', novembre: 'November', décembre: 'December' };
  const SMONTHS = { 'oct.': 'Oct', 'nov.': 'Nov', 'déc.': 'Dec', 'janv.': 'Jan' };
  const FIELDS = { classe: 'class', matricule: 'student ID', poste: 'position', photo: 'photo' };
  const longDate = (d, n, m) => `${DAYS[d.toLowerCase()]}, ${MONTHS[m]} ${n}`;
  const shortDate = (d, n, m) => `${SDAYS[d]} ${SMONTHS[m] || m} ${n}`;
  const DATE_L = '(Lundi|Mardi|Mercredi|Jeudi|Vendredi|lundi|mardi|mercredi|jeudi|vendredi) (\\d+) (\\p{L}+)';
  const DATE_S = '(lun\\.|mar\\.|mer\\.|jeu\\.|ven\\.) (\\d+) (\\p{L}+\\.)';
  const tr = (s) => translate(s);
  // Motifs pour les textes dynamiques
  const PATTERNS = [
    [/^Bonjour, (.+)$/u, (m) => 'Hello, ' + m[1]],
    [new RegExp('^' + DATE_L + '$', 'u'), (m) => longDate(m[1], m[2], m[3])],
    [new RegExp('^' + DATE_L + ' · (.+)$', 'u'), (m) => longDate(m[1], m[2], m[3]) + ' · ' + m[4]],
    [new RegExp('^' + DATE_S + '$', 'u'), (m) => shortDate(m[1], m[2], m[3])],
    [new RegExp('^Menu du ' + DATE_L + '$', 'u'), (m) => 'Menu of ' + longDate(m[1], m[2], m[3])],
    [/^Semaine du (\d+) (\p{L}+\.)$/u, (m) => `Week of ${SMONTHS[m[2]] || m[2]} ${m[1]}`],
    [/^Réserver pour (.+)$/u, (m) => 'Book for ' + tr(m[1])],
    [/^Ajouter à ma réservation · (.+)$/u, (m) => 'Add to my booking · ' + tr(m[1])],
    [/^Solde de (.+)$/u, (m) => m[1] + '’s balance'], [/^Suivi de (.+)$/u, (m) => m[1] + '’s meals'],
    [/^Solde : (.+)$/u, (m) => 'Balance: ' + m[1]],
    [/^Total \((\d+) articles?\)$/u, (m) => `Total (${m[1]} item${m[1] === '1' ? '' : 's'})`],
    [/^(\d+) places$/u, (m) => m[1] + ' seats'], [/^(\d+) non lue\(s\)$/u, (m) => m[1] + ' unread'],
    [/^(\d+) repas servis$/u, (m) => m[1] + ' meals served'], [/^À venir \((\d+)\)$/u, (m) => `Upcoming (${m[1]})`],
    [/^Plus que (\d+) portions$/u, (m) => `Only ${m[1]} portions left`],
    [/^Nouvelle période · (\d)e trimestre (.+)$/u, (m) => `New period · Term ${m[1]} ${m[2]}`],
    [/^Profil incomplet : (.+)$/u, (m) => 'Incomplete profile: ' + m[1].split(', ').map(f => FIELDS[f] || f).join(', ')],
    [/^Payer (.+)$/u, (m) => 'Pay ' + m[1]], [/^Confirmer et payer (.+)$/u, (m) => 'Confirm and pay ' + m[1]],
    [/^Payer (.+) et réserver$/u, (m) => `Pay ${m[1]} and book`], [/^Enregistrer les modifications · (.+)$/u, (m) => 'Save changes · ' + m[1]],
    [/^Réf\. (.+)$/u, (m) => 'Ref. ' + m[1]], [/^Repas — (.+)$/u, (m) => 'Meal — ' + m[1]],
    [/^Recharge (.+) \(par (.+)\)$/u, (m) => `${m[1]} top-up (by ${m[2]})`], [/^Recharge (.+)$/u, (m) => m[1] + ' top-up'],
    [/^Annulation — (.+)$/u, (m) => 'Cancellation — ' + m[1]],
    [/^(Élève|Personnel) · (.+)$/u, (m) => tr(m[1]) + ' · ' + m[2]],
    [/^Masque les plats contenant : (.+)$/u, (m) => 'Hides dishes containing: ' + m[1].split(', ').map(tr).join(', ')],
    [/^Incompatible : (.+)$/u, (m) => 'Not compatible: ' + m[1].split(', ').map(tr).join(', ')],
    [/^Recharger (.+) quand le solde passe sous (.+)$/u, (m) => `Top up ${m[1]} when the balance drops below ${m[2]}`],
    [/^est de (.+)\.$/u, (m) => `is ${m[1]}.`], [/^(.+) · (Wave|Orange Money|MTN MoMo|Moov Money|Carte bancaire)$/u, (m) => m[1] + ' · ' + tr(m[2])],
    [/^Noté ([\d,]+)\/5 et compatible avec votre profil(.*)\.$/u, (m) => `Rated ${m[1]}/5 and safe for your profile${m[2].replace(/\(sans (.+)\)/u, (x, a) => '(no ' + a.split(', ').map(w => tr(w.charAt(0).toUpperCase() + w.slice(1)).toLowerCase()).join(', ') + ')')}.`],
    [/^Vous l’avez déjà apprécié\. (.+)$/u, (m) => 'You liked it before. ' + tr(m[1])],
    [/^Publier (\d+) avis$/u, (m) => `Post ${m[1]} reviews`], [/^Merci, vos (\d+) avis sont publiés !$/u, (m) => `Thanks, your ${m[1]} reviews are published!`],
    [/^Votre commande : (.+)$/u, (m) => 'Your order: ' + m[1]],
    [/^(Repas réservé|Repas servi|Solde bas|Compte de (.+) désactivé) — (.+)$/u, (m) => ({ 'Repas réservé': 'Meal booked', 'Repas servi': 'Meal served', 'Solde bas': 'Low balance' }[m[1]] || m[1]) + ' — ' + m[3]],
    [/^Le solde de (.+) est de (.+) \(seuil : (.+)\)\.$/u, (m) => `${m[1]}’s balance is ${m[2]} (threshold: ${m[3]}).`],
    [/^Votre solde est de (.+)\.$/u, (m) => `Your balance is ${m[1]}.`],
    [/^Notez l’(.+) de (\p{L}+)\.$/u, (m) => `Rate the ${m[1]} from ${DAYS[m[2]] || m[2]}.`],
    [/^Comment était votre (.+) \? Donnez votre avis\.$/u, (m) => `How was your ${m[1]}? Leave a review.`],
    [/^(\d+)× $/u, (m) => m[0]], [/^Photo enregistrée \((.+)\)$/u, (m) => `Photo saved (${m[1]})`],
    [/^Recharge réussie : (.+)$/u, (m) => 'Top-up successful: ' + m[1]],
    [/^(.+) · servi à (.+)$/u, (m) => `${m[1]} · served at ${m[2]}`], [/^Repas servi à (.+)\.$/u, (m) => `Meal served at ${m[1]}.`]
  ];
  function translate(s) {
    if (EXACT[s] != null) return EXACT[s];
    for (const [re, fn] of PATTERNS) { const m = s.match(re); if (m) return fn(m); }
    return s;
  }
  // Traduit les textes, placeholders et titres d'un bloc déjà rendu
  function translateDOM(root) {
    if (!root) return;
    const w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = []; let n;
    while ((n = w.nextNode())) nodes.push(n);
    nodes.forEach(node => {
      const raw = node.nodeValue, t = raw.trim();
      if (!t || /^[\d\s.,:/·+−-]*(FCFA)?$/.test(t)) return;
      const out = translate(t);
      if (out !== t) node.nodeValue = raw.replace(t, out);
    });
    root.querySelectorAll('[placeholder],[title],[aria-label]').forEach(el => {
      ['placeholder', 'title', 'aria-label'].forEach(a => { const v = el.getAttribute(a); if (v) { const o = translate(v); if (o !== v) el.setAttribute(a, o); } });
    });
  }
  App.i18n = { translate, translateDOM, lang: () => { const u = App.me(); return (u && u.lang) || 'fr'; } };
})();
