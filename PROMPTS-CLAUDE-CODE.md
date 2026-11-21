# Cantine de Vatel — Feuille de route pour Claude Code

Ce fichier rassemble **toutes les étapes de développement** de l'application « Cantine de Vatel » (hackathon Vatel, novembre 2026), dans l'ordre d'exécution.
Il est destiné à **Claude Code**, qui l'exécute de bout en bout à partir du **prompt maître** ci-dessous.

- Dépôt GitHub : https://github.com/lartisse/cantile-vatel
- Stack : Laravel 11 + Blade + Tailwind CSS, SQLite en développement, MySQL en production
- Langue de l'interface : français (anglais en bonus pour les espaces utilisateur et parent)

---

## 1. Fichiers d'entrée (dossier principal du projet)

| Fichier | Rôle |
| --- | --- |
| `PROMPTS-CLAUDE-CODE.md` | Ce fichier : la feuille de route et les 27 étapes |
| `CAHIER-DES-CHARGES.md` | Cahier des charges détaillé v2 (4 rôles, toutes les règles métier) |
| `README-template.md` | README cible : architecture, 19 tables, 42 écrans, routes, sécurité, comptes de démo |
| `Cantine de Vatel — Inventaire des écrans et wireframes.md` | Détail de chaque écran : contenu, actions, états, parcours |
| `cantine-vatel-maquette/` | Maquette interactive HTML/CSS/JS : **référence visuelle et fonctionnelle** (design, icônes, données de démo, parcours) |
| `Guide des participants Cantine Vatel.pdf` | Guide officiel du hackathon (exigences minimum, bonus, critères du jury) |

**Ordre de priorité en cas de contradiction :** Guide officiel > Cahier des charges > README-template > Inventaire des écrans > Maquette.

---

## 2. Prompt maître (à copier dans Claude Code)

```text
Tu développes l'application « Cantine de Vatel » pour un hackathon. Tout ce qu'il te faut est dans le dossier principal.

1. LECTURE. Lis entièrement, avant d'écrire du code : PROMPTS-CLAUDE-CODE.md (feuille de route), CAHIER-DES-CHARGES.md, README-template.md, « Cantine de Vatel — Inventaire des écrans et wireframes.md », le Guide des participants (PDF) et la maquette dans cantine-vatel-maquette/ (index.html, css/style.css, js/*.js). La maquette est la référence visuelle et fonctionnelle : reproduis son design, ses icônes, ses données de démo et ses parcours en Laravel. Tu peux ajouter tout ce qui manque.

2. EXÉCUTION. Exécute les étapes 1 à 27 de PROMPTS-CLAUDE-CODE.md, dans l'ordre, sans attendre de validation entre les étapes. Tu exécutes toi-même les commandes (installation, migrations, tests) au lieu de me les donner. Ne t'arrête que pour un vrai blocage (identifiants, accès refusé, décision irréversible) : dans ce cas, explique le blocage, propose une solution et continue sur les étapes qui n'en dépendent pas.

3. GIT. Le dépôt distant est https://github.com/lartisse/cantile-vatel.
   - Au début : git init si nécessaire, branche main, .gitignore Laravel (vendor, node_modules, .env, database/*.sqlite, storage/*.key…), remote origin vers ce dépôt.
   - Après CHAQUE étape terminée et vérifiée : un commit au format Conventional Commits en français, préfixé du numéro d'étape, par exemple « feat(reservation): étape 08 — réservation multi-plats, créneaux et QR code », puis git push.
   - Ne commite jamais de secret (.env, clés API, mots de passe réels).
   - Si le push échoue (authentification), continue en commits locaux et signale-le-moi à la fin avec la commande à lancer.

4. VERSIONS. Crée un tag annoté et pousse-le (git push --tags) à chaque jalon : v0.1.0 (fin étape 5), v0.2.0 (fin étape 10), v0.3.0 (fin étape 15), v0.4.0 (fin étape 18), v0.5.0 (fin étape 21), v1.0.0 (fin étape 25). Tiens un CHANGELOG.md (format Keep a Changelog) mis à jour à chaque tag.

5. VÉRIFICATION. Avant chaque commit : php artisan migrate:fresh --seed sans erreur, php artisan route:list sans erreur, npm run build sans erreur, php artisan test au vert. Écris des tests Feature pour les règles critiques (accès par rôle, heure limite, capacité des créneaux, allergènes, corbeille, chiffrement des clés).

6. SUIVI. Tiens à jour PROGRESS.md : étape en cours, étapes terminées, ce qui est simulé, ce qui reste à faire, problèmes rencontrés.

7. PRIORITÉS. Si une étape prend trop de temps, fais d'abord une version minimale qui fonctionne, note le reste dans PROGRESS.md et passe à la suite. Ne sacrifie jamais les exigences minimum du guide ni les trois innovations : créneaux + QR code, prévision anti-gaspi, bouclier allergènes.

8. FIN. Quand tout est terminé, donne-moi un résumé : ce qui fonctionne, ce qui est simulé, les tags créés, l'état du push et les commandes pour lancer le projet.

Commence par l'étape 1.
```

---

## 3. Règles communes à toutes les étapes

- **Langue** : code et noms techniques en anglais (modèles, tables, méthodes) ; interface, messages, commits et documentation en français.
- **Architecture** (README-template, section 7) : contrôleurs par rôle `User`, `Parent`, `Admin`, `Super` ; logique métier dans les services `ImageService`, `ReservationService`, `AccountService`, `ModerationService`, `AuditLogger` ; paiements via l'interface `PaymentGateway`.
- **Routes** : celles du README-template, section 9 (routes en français : `/menu`, `/reservations`, `/admin/comptoir`…).
- **Sécurité** : middleware `role:` sur chaque groupe de routes, policies, `EnsureAccountActive`, CSRF, validation côté client ET serveur.
- **Mobile d'abord** : chaque écran doit être utilisable sur un téléphone de 360 px de large.
- **Données de démo** : toujours alimentées par le seeder, pour que la démo soit vivante.

---

## 4. Les étapes

### Jalon v0.1.0 : socle (étapes 1 à 5)

**Étape 1 : analyse et architecture**
Lis tous les fichiers d'entrée. Confirme l'architecture du README-template et le modèle de données de 19 tables. Liste dans `docs/ARCHITECTURE.md` les choix techniques justifiés, les incohérences repérées entre les documents et la façon dont tu les tranches (selon l'ordre de priorité).

**Étape 2 : écrans**
À partir de l'inventaire des écrans, produis `docs/ECRANS.md` : les 42 écrans (C1–C6, U1–U13, P1–P6, A1–A12, S1–S6) avec route, rôle, contenu, actions, états. Marque ceux qui répondent aux exigences minimum du guide : ils passent en premier.

**Étape 3 : installation, rôles et Git**
Crée le projet Laravel 11 dans le dossier principal (sans écraser les fichiers d'entrée), installe Breeze (Blade) et Tailwind, configure SQLite pour le développement et MySQL dans `.env.example`. Ajoute Cropper.js, qrcodejs, html5-qrcode, Chart.js, un outil d'export PDF et CSV, et vérifie l'extension GD. Mets en place les 4 rôles (`user`, `parent`, `admin`, `super_admin`), le middleware `role:`, le middleware `EnsureAccountActive` et les policies. Ajoute les variables `APP_LOCALE`, `CANTINE_CUTOFF`, `CANTINE_TRASH_DAYS`, `PAYMENT_ENV`, `UPLOAD_MAX_KB`. Déplace la maquette dans `maquette/` (structure du README-template). Initialise Git, configure le remote et fais le premier push.

**Étape 4 : base de données**
Migrations et modèles Eloquent des 19 tables du README-template, avec leurs relations : `user_id` nullable pour l'anonymisation, `class_snapshot`, `link_tokens` (15 min, usage unique), clés de paiement chiffrées avec `last4`, suppression douce des plats. Seeder qui reprend les données de `maquette/js/data.js` (plats ivoiriens, 2 semaines de menus, créneaux 11h30, 12h00, 12h30, 13h00, avis, transactions) et les comptes de démo du README-template : awa.kone@vatel.ci, jm.aka@vatel.ci, i.kone@gmail.com, gestion@cantine-vatel.ci, superadmin@cantine-vatel.ci, comptoir1@cantine-vatel.ci (mot de passe de démo documenté dans le README).

**Étape 5 : design et composants**
Reproduis en Blade + Tailwind le design de la maquette (`css/style.css`, icônes SVG de `js/icons.js`), mobile-first, aux couleurs de Vatel. Layout avec navigation par rôle. Composants Blade : cartes, badges allergènes et régimes, alertes, boutons, avatar à initiales. `ImageService` + composant d'upload commun : galerie ou caméra, JPG/PNG/WebP, 5 Mo maximum avec le message « Image trop lourde (5 Mo maximum) », recadrage carré/cercle (Cropper.js), aperçu, compression WebP par GD, validation côté client ET serveur (`mimes:jpg,jpeg,png,webp`, `max:5120`, type réel vérifié), confirmation avant suppression.
→ **Tag v0.1.0**

### Jalon v0.2.0 : utilisateur et parent (étapes 6 à 10)

**Étape 6 : écrans communs et profil (C1–C6, U1, U10–U13)**
Accueil public, inscription (élève, personnel ou parent), connexion, profil avec les champs selon le type (élève : classe, matricule ; personnel : poste), indicateur discret de profil incomplet, allergies, régimes, langue. Photo de profil avec le composant d'upload. Notifications internes, confirmation du mot de passe. Modale de confirmation de classe (« Êtes-vous toujours en [classe] ? ») ou proposition ignorable d'ajouter la classe. QR code de liaison parent + code de secours. Demande de suppression de compte avec motif facultatif.

**Étape 7 : menu et fiches plats (U2, U3)**
Menu de la semaine avec filtres (jour, catégorie, type, régime, allergies). Plats incompatibles avec le profil signalés en rouge, avec une alternative proposée. Fiche plat : photo ou image par défaut par catégorie, description, ingrédients, allergènes, avis.

**Étape 8 : réservation (U4, U5) et ReservationService**
Plusieurs plats avec quantités pour un même jour, choix d'un créneau à capacité limitée, paiement par le solde ou complément Mobile Money. Modification ou annulation jusqu'à l'heure limite, remboursement immédiat sur le solde. QR code et code de secours à 6 chiffres, consultables hors ligne. Allergène déclaré dans un plat : avertissement avant validation ; si l'utilisateur confirme, alerte à l'administration (`app_notifications`, `allergy_conflict`). `class_snapshot` enregistré.

**Étape 9 : portefeuille et avis (U6–U8)**
Portefeuille : solde, recharge via les moyens de paiement actifs (pilote simulé en Test), transactions et reçus, notification de solde bas. Avis sur chaque plat et/ou sur le menu, réservé à ceux qui ont réservé ce menu, question « Avez-vous tout mangé ? », `ModerationService` qui signale automatiquement les mots interdits.

**Étape 10 : espace parent (P1–P6)**
Liaison d'un enfant par scan du QR (html5-qrcode) ou code de secours, plusieurs enfants par compte. Suivi de chaque enfant : repas de la semaine, réservations à venir, équilibre alimentaire, dépenses, allergies, plafond journalier. Recharge, recharge automatique sous un seuil, historique des paiements et reçus. Alertes : solde bas (seuil par enfant), repas réservé ou servi, allergie, messages de la cantine.
→ **Tag v0.2.0**

### Jalon v0.3.0 : administrateur (étapes 11 à 15)

**Étape 11 : plats et menus (A5, A2)**
Plats et recettes : création, modification, archivage, suppression douce. Fiche : catégorie, prix, description, kcal, allergènes, régimes, recette reliée aux ingrédients, photo via le composant d'upload, aperçu avant publication. Menus de la semaine par glisser-déposer depuis le catalogue, portions prévues, duplication de la semaine précédente, publication avec notification des convives.

**Étape 12 : commandes, comptoir et alertes (A3, A11, A12)**
Commandes du jour : quantités par plat et par créneau, prévision de la semaine par plat et par jour, filtres (créneau, statut, « allergène réservé »), fiche de production imprimable, clôture de la journée (non retirés = `missed`). Comptoir : scan QR par la caméra ou saisie du code, résultat immédiat (validé, mauvais créneau, déjà servi, code invalide), avertissement allergie renforcé. Alertes allergies : voir la commande, contacter le parent ou le convive, marquer comme vérifié (tracé dans l'audit). Documente dans `docs/DEPLOIEMENT.md` que la caméra exige HTTPS.

**Étape 13 : tableau de bord et statistiques (A1, A9)**
Réservations et recettes du jour, taux de retrait, note moyenne, affluence par créneau, gaspillage, prévision « X réservés → préparez Y » basée sur le taux d'absence, alertes de stock, bandeau des réservations à risque allergique, compteur d'impact anti-gaspi (repas sauvés, kg évités, FCFA économisés). Statistiques par classe à partir de `class_snapshot`. Graphiques Chart.js.

**Étape 14 : stocks, avis et rapports (A4, A8, A6)**
Stocks : besoins calculés depuis les menus et les recettes, seuils d'alerte, entrées, pertes, bon de commande. Modération des avis : tri, filtres (plat ou menu, signalés, publiés, masqués), masquage avec motif. Rapports : fréquentation, recettes, gaspillage, satisfaction, classes, stocks ; export CSV et impression PDF.

**Étape 15 : utilisateurs et paramètres (A7, A10)**
Consultation des comptes, ajustement manuel du solde (paiement en espèces au guichet). Paramètres : créneaux et capacités, heure limite, démarrage d'une nouvelle période scolaire (déclenche la confirmation de classe), liste des mots interdits.
→ **Tag v0.3.0**

### Jalon v0.4.0 : super administrateur (étapes 16 à 18)

**Étape 16 : administrateurs, comptes et audit (S1, S2, S6)**
Créer, modifier, changer de rôle, désactiver les administrateurs ; consulter tous les comptes. `AuditLogger` non modifiable (qui, quand, quoi, IP) pour les désactivations, suppressions, restaurations, changements de rôle, clés API et vérifications d'allergie ; page filtrable et export CSV.

**Étape 17 : corbeille et suppressions (S3, S4) avec l'AccountService**
Phase 1, corbeille : motif obligatoire, déconnexion à la requête suivante, données conservées, restauration possible, notification à l'utilisateur et au parent lié. Phase 2, suppression définitive : seulement pour un compte en corbeille, en retapant le nom du compte ou le mot de passe ; données personnelles et photo effacées, liens parent retirés, `user_id` à `NULL` dans les réservations, avis et transactions, avis affichés « Utilisateur supprimé ». Purge planifiée (scheduler Laravel) après `CANTINE_TRASH_DAYS` jours. Demandes de suppression : accepter (corbeille) ou refuser avec un message de justification.

**Étape 18 : moyens de paiement (S5)**
Interface `PaymentGateway` avec un pilote par moyen (Wave, Orange Money, MTN MoMo, Moov Money, carte bancaire, CinetPay, PayDunya) et un pilote simulé en Test. Clés par moyen et par environnement (Test / Production), activation et désactivation sans perte des clés, « Tester la connexion » obligatoire avant mise en service. Clés chiffrées (`Crypt::encryptString`), seuls les 4 derniers caractères gardés, affichage masqué `sk_live_••••••••4F2a`, jamais réaffichées ni envoyées au navigateur. Ressaisie du mot de passe (`current_password`) avant toute modification. Erreur 403 pour les administrateurs.
→ **Tag v0.4.0**

### Jalon v0.5.0 : bonus (étapes 19 à 21)

**Étape 19 : anglais**
Traduction anglaise des espaces utilisateur et parent (`lang/fr.json`, `lang/en.json`), langue enregistrée sur le compte. L'administration reste en français.

**Étape 20 : suggestions et Chef de la semaine**
Moteur de suggestions par règles (historique, notes, allergies, régimes). Classement « Chef de la semaine » à partir des avis, avec le nom de l'étudiant créateur de chaque plat.

**Étape 21 : anti-gaspi et PWA**
Panier anti-gaspi : l'administration met en vente les portions restantes à -50 %, bannière côté utilisateur. Application installable (manifest + service worker), menu et QR code consultables hors ligne.
→ **Tag v0.5.0**

### Jalon v1.0.0 : qualité et livraison (étapes 22 à 25)

**Étape 22 : revue de sécurité et de qualité**
Revois tout le projet selon la section Sécurité du README-template : 403 pour un admin sur `/super/*`, `EnsureAccountActive`, validation des fichiers des deux côtés, chiffrement des clés, CSRF, policies. Corrige les bugs, nettoie le code, complète les tests Feature. Vérifie l'affichage de chaque écran en 360 px.

**Étape 23 : déploiement**
Rédige `docs/DEPLOIEMENT.md` pour un VPS (LWS) : PHP 8.2 et extensions, Nginx ou Apache, `.env` de production, `composer install --no-dev`, `migrate --seed`, `storage:link`, `npm run build`, cron du planificateur, HTTPS (Let's Encrypt), et une procédure de lancement local de secours.

**Étape 24 : README final**
Transforme `README-template.md` en `README.md` final, fidèle à ce qui fonctionne réellement : passe en « Prévu » ce qui n'est pas terminé, ajoute des captures d'écran (dossier `docs/captures/`), vérifie les comptes de démo, les commandes d'installation et la structure du dépôt.

**Étape 25 : checklist de soumission**
Crée `SOUMISSION.md` et vérifie : dépôt public avec commits réguliers, tags poussés, URL de démo (à compléter), README complet, comptes de test, vidéo de secours du parcours de démo (à enregistrer).
→ **Tag v1.0.0**

### Après le hackathon (étapes 26 et 27)

**Étape 26 : pitch deck**
Prépare dans `docs/PITCH.md` le contenu de 10 slides maximum selon la structure du guide : problème, solution, démo, architecture, perspectives.

**Étape 27 : script de démo et questions du jury**
Dans `docs/DEMO.md` : le parcours de démonstration du README (8 étapes) transformé en script minuté de 10 minutes, et les 10 questions les plus probables du jury avec des réponses.

---

## 5. Jalons et versions

| Tag | Fin de l'étape | Contenu |
| --- | --- | --- |
| v0.1.0 | 5 | Socle : installation, rôles, base de données, design, upload d'images |
| v0.2.0 | 10 | Utilisateur et parent : profil, menu, réservation, QR, portefeuille, avis, liaison parent |
| v0.3.0 | 15 | Administrateur : plats, menus, commandes, comptoir, alertes, tableau de bord, stocks, rapports |
| v0.4.0 | 18 | Super administrateur : administrateurs, audit, corbeille, moyens de paiement |
| v0.5.0 | 21 | Bonus : anglais, suggestions, Chef de la semaine, anti-gaspi, PWA |
| v1.0.0 | 25 | Revue, déploiement, README final, soumission |

## 6. Ce qui doit rester vrai à la fin

- Les 7 exigences minimum du guide fonctionnent.
- Chaque rôle a un compte de démo qui marche après `php artisan migrate:fresh --seed`.
- Un administrateur ne peut jamais atteindre un écran du super administrateur.
- Aucune clé ni aucun secret dans le dépôt.
- Le README décrit exactement ce qui fonctionne.
