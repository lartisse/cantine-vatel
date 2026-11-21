# Cantine de Vatel — maquette interactive v2 (HTML / CSS / JS)

Ouvrir `index.html` dans un navigateur (double-clic). Aucune installation, aucune dépendance.
Toutes les données sont en mémoire : ↺ Réinitialiser (ou un rechargement) remet la démo à zéro.

## Démo

La barre noire en haut permet de changer de rôle (Visiteur, Élève, Personnel, Parent, Admin,
Super admin, Comptoir) et l'heure de démo (09:40 = réservations du jour ouvertes ; 12:10 = service).

Comptes (mot de passe : n'importe lequel ; pour la ressaisie super admin : 6 caractères ou plus)

| Rôle | E-mail |
| --- | --- |
| Élève | awa.kone@vatel.ci |
| Personnel | jm.aka@vatel.ci |
| Parent | i.kone@gmail.com |
| Administrateur | gestion@cantine-vatel.ci |
| Super administrateur | superadmin@cantine-vatel.ci |
| Comptoir | comptoir1@cantine-vatel.ci |

## Rôles et contrôle d'accès

| Espace | Routes | Rôles autorisés |
| --- | --- | --- |
| Utilisateur (élève ou personnel) | `#/u/…` | convive |
| Parent | `#/p/…` | parent |
| Administration | `#/a/…` | admin, superadmin |
| Super administration | `#/s/…` | superadmin (un admin reçoit une page 403) |
| Comptoir | `#/k/…` | comptoir |

Un compte en corbeille ou désactivé ne peut plus se connecter.

## Nouveautés v2

**Utilisateur (élève ou personnel)**
- Type choisi à l'inscription. Nom et prénom obligatoires ; seuls les champs du type s'affichent
  (élève : classe, matricule ; personnel : poste). Ils peuvent être modifiés ou vidés à tout moment.
- Indicateur discret « Profil incomplet » (pastille sur l'onglet Profil, non bloquant).
- Photo : galerie ou appareil photo, recadrage cercle ou carré, compression WebP automatique,
  JPG/PNG/WebP, 5 Mo max (« Image trop lourde (5 Mo maximum) »), suppression confirmée, initiales sinon.
- Confirmation de classe à chaque nouvelle période (« Êtes-vous toujours en 3e A ? ») ou proposition
  d'ajout ignorable. L'admin démarre une période dans Utilisateurs › Paramètres.
- Réservation de plusieurs plats avec quantités ; modification et annulation jusqu'à l'heure limite.
- Menu : filtres jour, catégorie, régime et allergies ; fiche plat avec photo, ingrédients, allergènes.
- Avis sur chaque plat commandé et/ou sur le menu dans son ensemble (note + commentaire), réservé à ceux
  qui ont réservé ce menu. Les avis par plat alimentent la note du plat et s'affichent sur sa fiche.
- Demande de suppression du compte (motif facultatif), transmise au super admin.

**Langue** : Profil › Préférences › Langue de l'interface (Français par défaut, ou English), enregistrée
par compte. Elle s'applique aux espaces élève, personnel et parent ; l'administration reste en français.
Les noms de plats et les textes saisis (avis, messages) ne sont pas traduits (`js/i18n.js`).

**Parent** : liaison par scan du QR code affiché dans le profil de l'élève (pas de matricule),
plusieurs enfants, suivi des réservations à venir.

**Alerte allergie** : quand un convive (ou un parent pour son enfant) réserve un plat contenant un
allergène déclaré dans son profil, tous les administrateurs et super administrateurs reçoivent une
alerte (écran Alertes, bandeau du tableau de bord, badge rouge dans les commandes, filtre
« Allergène réservé »). L'admin peut voir la commande, contacter le parent ou le convive et marquer la
réservation comme vérifiée ; le comptoir affiche un avertissement renforcé au moment du service.

**Administrateur** : photo de plat (ordinateur ou téléphone, recadrage, compression, image par défaut),
aperçu de la fiche avant publication, suppression ou archivage ; modération des avis avec signalement
automatique par mots interdits ; prévision par plat et par jour ; statistiques par classe.

**Super administrateur**
- Administrateurs : créer, modifier, changer de rôle, désactiver.
- Comptes et corbeille : phase 1 (corbeille, motif obligatoire, restauration, notification de
  l'utilisateur et du parent), phase 2 (suppression définitive depuis la corbeille, confirmation en
  retapant le nom ou le mot de passe, anonymisation), purge automatique après 30 jours (paramétrable).
- Demandes de suppression : accepter (→ corbeille) ou refuser avec message.
- Moyens de paiement : Wave, Orange Money, MTN MoMo, Moov Money, carte bancaire, CinetPay, PayDunya ;
  environnements Test et Production séparés ; activer ou désactiver sans perdre les clés ; « Tester la
  connexion » obligatoire avant activation ; clés masquées (`sk_live_••••••••4F2a`) et jamais
  réaffichées ; ressaisie du mot de passe avant toute modification.
- Journal d'audit (export CSV).

## Ce qui est simulé dans la maquette

| Simulé ici | En production |
| --- | --- |
| Caméra (scan QR) | Lecture du QR via la caméra du téléphone |
| Paiements et « Tester la connexion » | Appels API des fournisseurs |
| Chiffrement des clés | `Crypt::encryptString` côté serveur ; seule la version masquée revient au navigateur |
| Validation des images (client) | Revalidation serveur : `mimes:jpg,jpeg,png,webp`, `max:5120`, vérification du contenu réel |
| Purge automatique (bouton) | Tâche planifiée quotidienne (Laravel Scheduler) |
| Contrôle d'accès (routeur) | Middleware de rôle sur chaque route et policy sur chaque action |

## Structure

```
index.html              point d'entrée
css/style.css           styles (couleurs du logo Vatel)
js/data.js              données de démonstration
js/core.js              routeur et contrôle d'accès, mises en page, images (validation, recadrage,
                        compression), ressaisie du mot de passe, audit, QR, graphiques
js/icons.js             jeu d'icônes SVG (style trait, type Lucide / SF Symbols), hors ligne
js/i18n.js              traduction français / anglais des écrans mobiles
js/screens-user.js      C1–C4 (communs), U1–U9 (élève/personnel), P1–P6 (parent)
js/screens-admin.js     A1–A8 (administrateur), K1 (comptoir)
js/screens-super.js     S1–S5 (super administrateur)
img/vatel_logo.png      logo
```
