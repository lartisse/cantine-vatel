# Cantine de Vatel — Inventaire des écrans et wireframes (v2)

Nov 21, 2026 · @Camso

## Vue d'ensemble

L'application compte 42 écrans pour 4 rôles. Les écrans Utilisateur et Parent sont pensés pour le mobile d'abord, avec une barre d'onglets en bas. Les écrans Administrateur et Super administrateur sont pensés pour l'ordinateur, avec un menu latéral. Le comptoir n'est plus un rôle : c'est l'écran A11 de l'administrateur, utilisé sur tablette.

| Rôle | Qui | Support | Navigation |
| --- | --- | --- | --- |
| Utilisateur | Élève ou Personnel, type choisi à l'inscription | Mobile | Onglets : Accueil · Menu · Réservations · Portefeuille · Profil |
| Parent | Parent d'un ou plusieurs élèves | Mobile | Onglets : Accueil · Enfants · Recharger · Alertes · Profil |
| Administrateur | Gestionnaire de la cantine | Ordinateur, tablette pour A11 | Menu latéral : Tableau de bord · Alertes · Menus · Commandes · Comptoir · Stocks · Plats · Avis · Utilisateurs · Rapports · Paramètres |
| Super administrateur | Niveau le plus élevé | Ordinateur | Menu de l'administrateur + section Super admin : Administrateurs · Comptes · Corbeille · Demandes · Paiements · Journal d'audit |

**Conventions des wireframes.** Chaque écran est décrit de haut en bas : **En-tête**, **Zones** (blocs de contenu), **Actions** (boutons et liens), **États** (vide, erreur, chargement) et **Parcours** (d'où l'on vient, où l'on va). Les icônes sont des pictogrammes au trait (style iOS / Lucide), jamais des emojis. L'interface est en français ; les espaces Utilisateur et Parent peuvent passer en anglais depuis le profil.

| Rôle | Écran | Route | Statut v2 |
| --- | --- | --- | --- |
| Commun | C1 Accueil public | `/` | Inchangé |
| Commun | C2 Inscription : Élève, Personnel ou Parent | `/inscription` | Modifié |
| Commun | C3 Connexion | `/connexion` | Modifié (comptes désactivés refusés) |
| Commun | C4 Profil : champs selon le type, profil incomplet, langue | `/profil` | Modifié |
| Commun | C5 Notifications | `/notifications` | Modifié (ex-U9, commun) |
| Commun | C6 Confirmation du mot de passe | `/confirmer-mot-de-passe` | Nouveau |
| Utilisateur | U1 Accueil + confirmation de classe | `/accueil` | Modifié |
| Utilisateur | U2 Menu de la semaine : filtres jour, catégorie, régime, allergies | `/menu` | Modifié |
| Utilisateur | U3 Fiche plat : photo, ingrédients, allergènes, avis | `/plats/{plat}` | Modifié |
| Utilisateur | U4 Réserver : plusieurs plats, quantités, créneau | `/reservations/nouvelle/{menu}` | Modifié |
| Utilisateur | U5 Mes réservations + QR code | `/reservations` | Inchangé |
| Utilisateur | U6 Portefeuille | `/portefeuille` | Inchangé |
| Utilisateur | U7 Recharger | `/portefeuille/recharger` | Modifié (moyens actifs seulement) |
| Utilisateur | U8 Avis sur les plats et/ou le menu | `/avis/{menu}` | Modifié |
| Utilisateur | U10 Photo de profil (modale) | `/profil` | Nouveau |
| Utilisateur | U11 Confirmation de classe (modale) | `/accueil` | Nouveau |
| Utilisateur | U12 QR code de liaison parent | `/profil/qr-parent` | Nouveau |
| Utilisateur | U13 Demande de suppression de compte | `/profil/suppression` | Nouveau |
| Parent | P1 Accueil | `/parent` | Inchangé |
| Parent | P2 Lier un enfant par QR code | `/parent/lier` | Modifié |
| Parent | P3 Suivi d'un enfant | `/parent/enfants/{enfant}` | Modifié (réservations à venir) |
| Parent | P4 Recharger le compte d'un enfant | `/parent/enfants/{enfant}/recharger` | Inchangé |
| Parent | P5 Historique des paiements | `/parent/paiements` | Inchangé |
| Parent | P6 Alertes | `/parent/alertes` | Inchangé |
| Admin | A1 Tableau de bord | `/admin` | Modifié |
| Admin | A2 Menus de la semaine | `/admin/menus` | Inchangé |
| Admin | A3 Commandes du jour et prévision | `/admin/commandes` | Modifié |
| Admin | A4 Stocks | `/admin/ingredients` | Inchangé |
| Admin | A5 Plats et recettes | `/admin/plats` | Modifié |
| Admin | A6 Rapports | `/admin/rapports` | Modifié (rapport par classe) |
| Admin | A7 Utilisateurs (consultation) | `/admin/utilisateurs` | Modifié (plus de suppression) |
| Admin | A8 Modération des avis | `/admin/avis` | Nouveau |
| Admin | A9 Statistiques par classe | `/admin/statistiques/classes` | Nouveau |
| Admin | A10 Paramètres | `/admin/parametres` | Nouveau |
| Admin | A11 Comptoir | `/admin/comptoir` | Déplacé (ex-K1) |
| Admin | A12 Alertes allergie | `/admin/alertes` | Nouveau |
| Super admin | S1 Administrateurs | `/super/admins` | Nouveau |
| Super admin | S2 Comptes | `/super/comptes` | Nouveau |
| Super admin | S3 Corbeille | `/super/corbeille` | Nouveau |
| Super admin | S4 Demandes de suppression | `/super/demandes` | Nouveau |
| Super admin | S5 Moyens de paiement | `/super/paiements` | Nouveau |
| Super admin | S6 Journal d'audit | `/super/audit` | Nouveau |

## Écrans communs

Ces six écrans servent tous les rôles. Après connexion, chacun arrive sur l'accueil de son rôle.

### C1 — Accueil public

- **En-tête :** logo Vatel, « Se connecter », « Créer un compte ».
- **Zones :** accroche « Réservez, payez, mangez sans attendre » ; aperçu du menu du jour (3 plats avec vignette, catégorie, note, prix) ; heure limite de réservation ; 3 bénéfices avec icône (moins d'attente, moins de gaspillage, allergies prises en compte).
- **Actions :** « Créer un compte », « Se connecter », « Voir le menu de la semaine » (modale en lecture seule).
- **Parcours :** visiteur → C2 ou C3.

### C2 — Inscription

- **En-tête :** titre, indicateur d'étape (3 étapes pour un utilisateur, 2 pour un parent).
- **Zones :** étape 1, choix du type en 3 cartes (Élève, Personnel, Parent). Étape 2, prénom et nom obligatoires, téléphone, e-mail, mot de passe ; puis seulement les champs du type choisi : classe (liste) et matricule pour un élève, poste pour le personnel, tous facultatifs ; pour un parent, un rappel que la liaison se fait par QR code. Étape 3 (utilisateur), régime et allergies.
- **Actions :** « Continuer », « Retour », « Créer mon compte ».
- **États :** erreur sous chaque champ (« Le nom est obligatoire », e-mail déjà utilisé, mot de passe trop court).
- **Parcours :** C1 → C2 → U1 ou P1. Les comptes Admin et Super admin sont créés en S1.

### C3 — Connexion

- **Zones :** e-mail ou téléphone, mot de passe, « Rester connecté ».
- **Actions :** « Se connecter », « Mot de passe oublié », « Créer un compte ».
- **États :** « Identifiants incorrects » ; « Ce compte est désactivé. Contactez l'administration de la cantine. » pour un compte en corbeille ou désactivé.
- **Parcours :** Utilisateur → U1, Parent → P1, Admin et Super admin → A1.

### C4 — Profil

- **En-tête :** « Mon profil » ; une pastille orange sur l'onglet Profil si le profil est incomplet.
- **Zones :** photo ronde (ou initiales) avec « Galerie » et « Appareil photo », ou « Modifier la photo » et « Supprimer » (voir U10) ; nom et type de compte ; pastille discrète « Profil incomplet : classe, photo », non bloquante ; carte « Mes informations » en lecture ; formulaire « Modifier » avec un champ par ligne en pleine largeur (prénom et nom obligatoires, puis les seuls champs du type, vidables) ; pour un élève, carte « Lier un parent » (voir U12) ; régime et allergies ; seuil d'alerte de solde ; préférences : langue de l'interface (Français ou English, liste déroulante) et notifications push, SMS, e-mail ; carte « Suppression du compte » (voir U13).
- **Actions :** « Enregistrer » ; « Changer le mot de passe » et « Se déconnecter » côte à côte sur une seule ligne.
- **États :** erreur si prénom ou nom vide ; changement de langue immédiat.
- **Parcours :** onglet Profil → C4. Les allergies saisies ici filtrent et signalent les plats en U2, U3 et U4.

### C5 — Notifications

- **Zones :** compteur de non lues ; liste chronologique avec pastille d'icône, titre, texte, date ; les non lues ont un liseré bleu. Exemples : rappel de réservation, solde bas, menu publié, repas servi, invitation à donner son avis, message de la cantine.
- **Actions :** toucher une notification ouvre l'écran concerné ; « Tout marquer comme lu ».
- **Parcours :** cloche de U1 ou P1 → C5.

### C6 — Confirmation du mot de passe

- **Zones :** modale « Confirmez votre identité », raison de la demande, champ mot de passe.
- **Actions :** « Confirmer », « Annuler ».
- **États :** « Mot de passe incorrect » ; confirmation valable 5 minutes.
- **Parcours :** déclenchée avant toute modification en S5 et avant une suppression définitive en S3.

## Utilisateur (élève ou personnel)

Le parcours cœur est : voir le menu → réserver un ou plusieurs plats → payer avec le solde → présenter son QR code au comptoir → donner son avis. Élève et personnel partagent les mêmes écrans ; seuls le profil (classe et matricule, ou poste) et la confirmation de classe diffèrent.

### U1 — Accueil

- **En-tête :** « Bonjour, Awa », cloche avec pastille du nombre de non lues.
- **Zones :** carte de confirmation de classe au début d'une nouvelle période (voir U11) ; carte solde (montant en FCFA, « Recharger », « Historique ») et alerte si le solde est sous le seuil ; carte « Mon repas d'aujourd'hui » (plats, créneau, statut, « Afficher mon QR code »), ou « Vous n'avez pas encore réservé — il reste jusqu'à 10h00 », ou réservations closes avec lien vers le jour suivant ; carrousel « Plats du jour » avec badge d'allergène incompatible ; carte « Suggestion pour vous » (IA) avec la raison (note, historique, compatibilité).
- **Actions :** « Réserver maintenant », « Afficher mon QR code », « Réserver ce plat », « Donner mon avis » si le repas est servi.
- **Parcours :** C3 → U1 → U2, U4, U5 ou U6.

### U2 — Menu de la semaine

- **En-tête :** « Menu de la semaine », flèches de semaine (chevrons) et « Semaine du 23 nov. ».
- **Zones :** barre de jours Lun → Ven ; filtres en pastilles défilantes : catégorie (Tout, Entrées, Plats, Desserts, Boissons) et régime (Tous, Végétarien, Sans porc) ; interrupteur « Compatible avec mes allergies » ; titre du jour et bouton « Avis » (note moyenne et nombre) ; plats par catégorie : vignette (photo ou image par défaut de la catégorie), nom, note, allergènes (en rouge si incompatibles), « Plus que 12 portions » ou « Épuisé », prix.
- **Actions :** toucher un plat → U3 ; « Réserver pour mar. 24 nov. » fixé en bas ; « Donner mon avis sur ce menu » si l'utilisateur a réservé ce jour passé ; « Effacer les filtres ».
- **États :** aucun plat pour ces filtres ; jour passé ; réservations closes ; déjà réservé (« Voir ma réservation »).
- **Modale Avis :** moyenne par plat, puis avis sur le menu (avatar, nom, étoiles, commentaire) ; rappel que seuls les réservataires peuvent noter.

### U3 — Fiche plat

- **Zones :** grande photo (ou image par défaut), nom, prix, note et nombre d'avis, catégorie et calories ; alerte rouge si un allergène du profil est présent ; description ; ingrédients (issus de la recette) ; allergènes ; avis sur ce plat (3 derniers commentés) ; régimes.
- **Actions :** « Ajouter à ma réservation » si le jour est ouvert, sinon « Retour au menu ».
- **Parcours :** U2 → U3 → U4.

### U4 — Réserver

- **En-tête :** « Réserver » ou « Modifier ma réservation » ; rappel de l'heure limite.
- **Zones :** 1. Composer la commande : plats par catégorie, sélection multiple ; un plat choisi affiche un sélecteur de quantité (− / + , 5 maximum). 2. Créneau : 11h30, 12h00, 12h30, 13h00 avec places restantes, « Complet » grisé. 3. Récapitulatif : lignes « 2× Attiéké », total et nombre d'articles, solde actuel (plus remboursement en cas de modification), solde après paiement.
- **Actions :** « Confirmer et payer 3 550 FCFA » ; si le solde manque, choix du moyen Mobile Money actif et « Payer la différence et réserver ».
- **États :** plat incompatible choisi → modale « Plat incompatible … Si vous confirmez, l'équipe de la cantine sera prévenue » (voir A12) ; plat épuisé ; réservations closes ; déjà réservé.
- **Parcours :** U2 ou U3 → U4 → écran « Repas réservé ! » → U5.

### U5 — Mes réservations et QR code

- **Zones :** onglets « À venir » et « Passées » ; cartes avec date, plat principal, articles, créneau, statut (Payée, Servie, Non retirée, Annulée). Détail : grand QR code, code de secours à 6 chiffres, mention « Disponible hors ligne », articles regroupés avec quantités, total.
- **Actions :** « Modifier » ou « Annuler » avant l'heure limite (remboursement immédiat) ; « Donner mon avis sur ce menu » ou « Voir mon avis ».
- **Parcours :** U1 ou onglet Réservations → U5 → A11 au comptoir → U8.

### U6 — Portefeuille

- **Zones :** solde en grand ; filtres de mois ; dépensé ce mois et repas pris ; transactions groupées par jour (recharge, repas, remboursement, ajustement) avec icône et montant signé.
- **Actions :** « Recharger mon compte » → U7 ; toucher une transaction → reçu (téléchargeable en PDF).

### U7 — Recharger

- **Zones :** solde actuel ; montants rapides (1 000, 2 500, 5 000, 10 000 FCFA) et montant libre ; moyens de paiement activés en Production par le super admin (S5) ; numéro Mobile Money prérempli.
- **Actions :** « Payer 5 000 FCFA ».
- **États :** attente de validation sur le téléphone, succès, échec ; aucun moyen actif.
- **Parcours :** U6, U1 ou U4 → U7 → retour à l'écran d'origine.

### U8 — Avis sur les plats et/ou le menu

- **Zones :** rappel du menu du jour ; consigne « Notez les plats que vous avez pris, le menu dans son ensemble, ou les deux » ; **Les plats** : une carte par plat commandé (vignette, nom, catégorie, 5 étoiles ; dès qu'il est noté, un champ de commentaire facultatif) ; **Le menu dans son ensemble** : 5 étoiles et commentaire ; mots rapides (Délicieux, Trop salé, Portion petite…) ; « Avez-vous tout mangé ? » (3 choix avec icône) pour mesurer le gaspillage.
- **Actions :** toucher à nouveau une étoile efface la note ; bouton « Publier mon avis » ou « Publier 3 avis », grisé tant que rien n'est noté ; « Plus tard ».
- **États :** réservé à l'auteur de la réservation (sinon « Seuls les convives ayant réservé ce menu peuvent le noter ») ; pas avant le jour du repas ; déjà noté → vue « Mes avis » avec statut (publié, en attente de vérification, masqué).
- **Parcours :** notification après le service, U1, U2 ou U5 → U8 → U1.

### U10 — Photo de profil (modale)

- **Zones :** choix « Galerie » ou « Appareil photo » ; recadreur : image déplaçable au doigt, zoom, masque Cercle ou Carré, taille et dimensions d'origine.
- **Actions :** « Enregistrer » (recadrage puis compression WebP), « Annuler » ; sur une photo existante : « Modifier la photo » ou « Supprimer » (confirmation, retour aux initiales).
- **États :** « Image trop lourde (5 Mo maximum) » ; « Format non accepté (JPG, PNG ou WebP uniquement) » ; image illisible ; succès « Photo enregistrée (2,4 Mo → 48 Ko) ».

### U11 — Confirmation de classe (élève, sur U1)

- **Déclencheur :** la période scolaire courante (A10) diffère de la dernière période confirmée par l'élève.
- **Classe renseignée :** « Nouvelle période · 2e trimestre — Êtes-vous toujours en 3e A ? » avec « Oui, c'est bien ça » et « Corriger » (liste des classes, ou « Je préfère ne pas l'indiquer »).
- **Classe vide :** « Voulez-vous indiquer votre classe ? » avec « Ajouter ma classe » et « Ignorer » ; ignorer ne repose pas la question avant la période suivante.

### U12 — QR code de liaison parent (élève)

- **Zones :** QR code, consigne « Votre parent scanne ce QR code depuis son application (Enfants › Ajouter un enfant) », code de secours, mention « la liaison n'utilise pas le matricule », parent(s) déjà lié(s).
- **Actions :** « Générer un nouveau QR code » (l'ancien devient invalide, les parents déjà liés le restent).
- **Parcours :** C4 → U12 ; le parent scanne en P2.

### U13 — Demande de suppression de compte

- **Zones :** explication (transmission au super admin, conservation en corbeille puis effacement) ; modale avec motif facultatif et rappel du solde à régulariser.
- **Actions :** « Envoyer la demande » ; ensuite « Annuler ma demande ».
- **États :** demande en attente ; demande refusée, avec le message du super admin.
- **Parcours :** C4 → U13 → S4.

## Parent

Le parent gère un ou plusieurs enfants depuis un seul compte : il les lie par QR code, recharge leur compte, suit leurs repas et reçoit les alertes.

### P1 — Accueil

- **En-tête :** « Bonjour, Ibrahim », cloche avec pastille.
- **Zones :** bandeau orange par enfant sous son seuil (« Le solde de Yao est de 800 FCFA » + « Recharger ») ; une carte par enfant : photo ou initiales, nom, classe (ou « Classe non renseignée »), solde avec pastille verte, orange ou rouge, repas du jour et statut ; carte « Ce mois-ci » : total rechargé, repas pris, lien vers l'historique.
- **Actions :** toucher une carte → P3 ; « Recharger » → P4 ; « Voir ses repas » ; « Ajouter un enfant » → P2.
- **États :** aucun enfant lié (« Ajoutez votre premier enfant »).

### P2 — Lier un enfant par QR code

- **Zones :** viseur de caméra ; consigne « Demandez à votre enfant d'ouvrir Profil › Lier un parent et scannez le QR code affiché » ; repli repliable « Le scan ne fonctionne pas ? Saisir le code de secours » ; mention « La liaison se fait uniquement par le QR code de l'élève (le matricule n'est pas utilisé). Vous pouvez lier plusieurs enfants. »
- **Actions :** « Scanner le QR code » (ouvre la caméra) ; « Valider le code ».
- **États :** élève trouvé → carte de confirmation (photo, nom, classe ; pas de matricule) avec « Confirmer le lien » et « Ce n'est pas mon enfant » ; QR non reconnu ou expiré ; enfant déjà lié ; élève déjà lié à un autre parent (les deux suivent).
- **Parcours :** P1 → P2 → P1 ; l'élève reçoit « Compte parent lié ».

### P3 — Suivi d'un enfant

- **En-tête :** « Suivi de Awa », pastilles pour passer d'un enfant à l'autre.
- **Zones :** solde et « Recharger », plafond journalier ; repas de la semaine (Lun → Ven : plat, créneau, heure de service, statut) ; réservations à venir (date, créneau, articles, total) ; équilibre alimentaire (barres par catégorie, apport moyen en kcal) ; allergies déclarées ; dernières dépenses.
- **Actions :** « Réserver pour Awa » (modale : jour, plat — les plats incompatibles sont désactivés —, boisson, créneau) ; « Modifier » les allergies ; « Définir un plafond journalier ».

### P4 — Recharger le compte d'un enfant

- **Zones :** choix de l'enfant (solde affiché) ; montants rapides (2 500 à 20 000 FCFA) et montant libre ; moyens actifs (Mobile Money et carte bancaire) ; interrupteur « Recharge automatique » sous le seuil.
- **Actions :** « Payer ».
- **États :** validation en cours ; succès avec reçu (nouveau solde, moyen, référence, « Reçu PDF ») ; échec.
- **Parcours :** P1, P3 ou alerte de solde bas → P4 → P1 ; l'enfant reçoit « Compte rechargé ».

### P5 — Historique des paiements

- **Zones :** filtre par enfant ; total rechargé ; liste (enfant, moyen, date, référence, montant).
- **Actions :** toucher une ligne → reçu téléchargeable.

### P6 — Alertes

- **Zones :** seuil de solde bas par enfant ; canaux (push, SMS, e-mail) ; alertes reçues : solde bas, repas réservé ou servi, compte désactivé, message de la cantine (par exemple au sujet d'une réservation à risque allergique).
- **Actions :** « Enregistrer », « Tout lire », toucher une alerte → P3 ou P4.

## Administrateur

L'administrateur planifie les menus, sait combien de repas préparer, sert au comptoir, gère les stocks, modère les avis et suit les réservations à risque allergique. Mise en page : menu latéral sombre avec icônes et compteurs rouges (Alertes, Stocks, Avis), contenu à droite.

### A1 — Tableau de bord

- **En-tête :** date et heure ; filtre 7 jours / 30 jours.
- **Zones :** bandeau rouge « N réservation(s) à risque allergique à vérifier » (lien vers A12) ; 4 indicateurs : repas réservés aujourd'hui (servis / à servir), recettes du jour, taux de retrait d'hier, note moyenne ; graphique réservés / servis par jour ; affluence par créneau et repas déjà réservés pour demain ; réservations par classe ; prévision par plat et par jour ; gaspillage (servis / non retirés) ; plats les mieux et moins bien notés ; alertes de stock.
- **Actions :** clic sur un indicateur → écran détaillé ; « Générer le bon de commande ».

### A2 — Menus de la semaine

- **Zones :** choix de la semaine ; statut Brouillon ou Publiée ; heure limite de réservation ; grille jours × catégories, chaque case listant ses plats (portions prévues, réservations) ; catalogue latéral avec recherche, plats glissables.
- **Actions :** glisser-déposer un plat, « + Ajouter » (plat et portions), retirer un plat (si réservations : confirmation, remboursement et notification des convives), « Dupliquer la semaine précédente », « Publier le menu » (notification des convives).
- **États :** plat déposé dans la mauvaise catégorie ; plat déjà présent ; jour sans plat au moment de publier.

### A3 — Commandes du jour et prévision

- **En-tête :** choix du jour ; « Fiche de production », « Clôturer la journée ».
- **Zones :** indicateurs (réservés, servis, restant à servir, plats contenant un allergène déclaré) ; onglets « Jour : par créneau » (quantités par plat × créneau, total, prévu en rouge si dépassé) et « Semaine : par jour » (réservés / portions prévues par plat × jour) ; liste des commandes (convive, classe ou poste, plateau, badge rouge « ⚠ plat : allergène » et « vérifié », créneau, code, statut).
- **Actions :** recherche par nom ou code, filtres créneau et statut (dont « Allergène réservé »), « Marquer servi », export CSV, impression.

### A4 — Stocks

- **Zones :** tableau des ingrédients (stock, seuil, besoin de la semaine calculé depuis les menus et recettes, jauge, statut OK / Bas / Rupture) ; liste « À commander » ; derniers mouvements.
- **Actions :** « Entrée de stock », « Déclarer une perte » (motif), « + Ingrédient », « Générer le bon de commande ».

### A5 — Plats et recettes

- **Zones (catalogue) :** recherche, filtre par catégorie, grille de fiches (photo ou image par défaut, nom, prix, note, allergènes).
- **Zones (fiche) :** nom et prix obligatoires, catégorie, calories, description, allergènes, régimes, recette (ingrédient + quantité par portion) ; carte Photo : « Depuis l'ordinateur », « Depuis le téléphone », puis recadrage carré, compression, « Remplacer » et « Supprimer » ; sans photo, l'image par défaut de la catégorie est affichée ; carte Utilisation.
- **Actions :** « Aperçu et publication » (modale montrant la fiche telle que la verront les convives, puis « Publier »), « Archiver », « Supprimer ».
- **États :** format ou taille d'image refusés ; suppression impossible si le plat figure dans des réservations (archivage proposé).

### A6 — Rapports

- **Zones :** onglets Fréquentation, Recettes, Gaspillage, Satisfaction, Par classe, Stocks ; 3 indicateurs, un graphique et un tableau par rapport.
- **Actions :** « Exporter PDF », « Exporter Excel (CSV) », « Envoyer par e-mail ».

### A7 — Utilisateurs (consultation)

- **Zones :** recherche par nom ou matricule, filtre par rôle ; tableau (nom et photo, type Élève / Personnel / Parent, classe ou poste, matricule, solde, statut). Les comptes en corbeille n'apparaissent pas.
- **Actions :** fiche utilisateur (type, classe ou poste, allergies, parent lié) avec ajustement manuel du solde et motif ; import d'élèves par CSV. La désactivation et la suppression sont réservées au super admin.

### A8 — Modération des avis

- **Zones :** pastilles Signalés / Publiés / Masqués / Tous avec compteurs ; filtre « Plats et menus / Avis sur un plat / Avis sur le menu » ; tri (plus récents, note croissante ou décroissante) ; cartes d'avis : auteur (ou « Utilisateur supprimé »), classe, « Plat : Alloco » ou « Menu du lundi 23 novembre », étoiles, commentaire avec les mots interdits surlignés en rouge, statut.
- **Actions :** « Approuver et publier », « Masquer » (motif : insulte, spam, hors sujet, informations personnelles), « Republier », « Mots interdits » (modale d'édition + réanalyse).
- **Règle :** un avis contenant un mot interdit est signalé et masqué jusqu'à décision ; un avis approuvé n'est plus resignalé.

### A9 — Statistiques par classe

- **Zones :** classes représentées, élèves ayant renseigné leur classe sur le total, classe la plus active ; graphique réservations / servis par classe ; tableau (élèves, réservations, servis, non retirés, repas par élève).
- **Règle :** seuls les élèves ayant renseigné leur classe sont comptés ; la classe est figée au moment de chaque réservation.

### A10 — Paramètres

- **Zones :** créneaux et capacité ; heure limite de réservation ; seuil d'alerte de solde par défaut ; règle de remboursement ; période scolaire en cours ; liste des mots interdits.
- **Actions :** « Démarrer une nouvelle période » (déclenche U11 chez les élèves, tracé au journal), « Enregistrer ».

### A11 — Comptoir (tablette)

- **En-tête :** créneau en cours, compteur « 84 / 120 repas servis », « Quitter ».
- **Zones :** à gauche, viseur de caméra, champ « code à 6 chiffres », derniers servis ; à droite, carte résultat : photo et nom, classe ou poste, créneau réservé, plats à servir en grand.
- **États :** vert « Validé — servir » ; orange « Mauvais créneau » (avec « Servir quand même ») ou « Déjà servi à 12h04 » ; rouge « Code invalide », « Aucune réservation », « Pas pour aujourd'hui » ; encadré « ALLERGIE : ARACHIDE », renforcé en « Le plateau contient Riz sauce arachide — vérifier avant de servir » (avec le nom de qui a vérifié).
- **Actions :** scan ou « Valider le code », « Signaler un problème ».
- **Parcours :** chaque service met à jour A1 et A3 et envoie à l'utilisateur l'invitation à donner son avis (U8), et au parent « Repas servi ».

### A12 — Alertes allergie

- **Déclencheur :** un utilisateur (ou un parent pour son enfant) réserve un plat contenant un allergène déclaré dans son profil. Tous les administrateurs et super administrateurs reçoivent l'alerte.
- **Zones :** liste d'alertes (icône, titre « Allergie : réservation à risque », « Nouveau », date ; texte : qui, classe ou poste, plat, allergène, jour, créneau ; statut de la réservation ou « Vérifié par Mariam D. »).
- **Actions :** « Voir la commande » (A3 filtré sur la réservation), « Contacter le parent » ou « Contacter le convive » (message prérempli), « Marquer comme vérifié » (tracé au journal), « Tout marquer comme lu ».
- **États :** réservation annulée entre-temps ; aucune alerte.

## Super administrateur

Le super administrateur a tous les écrans de l'administrateur, plus une section « Super admin » dans le menu latéral. Un administrateur qui ouvre l'une de ces adresses voit une page « Accès refusé » (403). Chaque action sensible est écrite dans le journal d'audit (S6).

### S1 — Administrateurs

- **Zones :** tableau des comptes admin et super admin (photo, nom, « Vous », e-mail, fonction, rôle, statut Actif / Désactivé).
- **Actions :** « Créer un administrateur » (prénom, nom, e-mail, fonction, rôle ; e-mail d'activation) ; « Modifier » (changement de rôle tracé ; impossible sur son propre compte) ; « Désactiver » (motif obligatoire) ou « Réactiver ».
- **États :** e-mail déjà utilisé ; champs obligatoires manquants.

### S2 — Comptes

- **Zones :** recherche par nom ou e-mail ; tableau des comptes actifs utilisateurs et parents (photo, nom, type, classe ou poste, parent ou enfants liés, solde).
- **Actions :** « Mettre en corbeille » : modale qui rappelle les effets (connexion bloquée, compte invisible, données conservées, restauration possible, notification de l'utilisateur et de son parent) avec motif obligatoire.
- **Parcours :** S2 ou S4 → corbeille (S3).

### S3 — Corbeille

- **Zones :** tableau des comptes en corbeille (nom, type, date et auteur de la mise en corbeille, motif, badge « Purge dans 26 j », orange sous 7 jours, rouge « Due ») ; carte « Purge automatique » : délai en jours (30 par défaut, modifiable).
- **Actions :** « Restaurer » (confirmation ; l'utilisateur et le parent sont notifiés) ; « Supprimer définitivement » ; « Exécuter la purge maintenant ».
- **Modale de suppression définitive :** bandeau rouge « Action irréversible » ; deux colonnes « Effacé » (identité, contact, photo, classe, matricule, poste, allergies, liaison parent) et « Conservé, anonymisé » (réservations, avis affichés « Utilisateur supprimé », écritures comptables) ; onglets « Retaper le nom » ou « Mon mot de passe » ; bouton rouge actif seulement quand la saisie correspond.

### S4 — Demandes de suppression

- **Zones :** demandes en attente (photo, nom, type, classe, date, motif ou « Aucun motif indiqué »), puis demandes traitées (acceptée → corbeille, ou refusée avec la réponse envoyée).
- **Actions :** « Accepter » (ouvre la mise en corbeille avec le motif prérempli) ; « Refuser » (message de justification obligatoire, envoyé à l'utilisateur et visible dans son profil).

### S5 — Moyens de paiement

- **En-tête :** onglets d'environnement Test (orange) et Production (vert).
- **Zones :** bandeau sombre « Écran réservé au super administrateur — clés chiffrées, jamais réaffichées » et durée restante de l'identité confirmée ; en Test, rappel « aucun débit réel » ; une carte par moyen (Wave, Orange Money, MTN MoMo, Moov Money, Carte bancaire, CinetPay, PayDunya) : logo, type, interrupteur, badges (Activé, Clés configurées ou N / 3 clés, Test OK ou échoué), une ligne par clé avec la valeur masquée (`sk_live_••••••••4F2a`) ou « Non configurée », date du dernier test.
- **Actions :** « Ajouter les clés » ou « Remplacer les clés » (champs masqués, vides = conserver), « Tester la connexion », « Supprimer », interrupteur Activer / Désactiver (les clés sont conservées). Toute modification passe d'abord par C6.
- **États :** activation refusée sans toutes les clés ou sans test réussi ; clé trop courte ; clé de test saisie en Production ; seuls les moyens activés en Production apparaissent en U7 et P4.

### S6 — Journal d'audit

- **Zones :** filtre par type d'action, recherche ; tableau Quand · Qui (et rôle) · Action (badge rouge pour suppressions et désactivations, orange pour les clés) · Cible · Détail.
- **Actions tracées :** désactivation, restauration, suppression définitive, purge automatique, refus de suppression, création et modification d'administrateur, changement de rôle, clés API, activation de paiement, paramètre de purge, nouvelle période, allergie vérifiée, suppression de plat.
- **Actions :** « Exporter CSV ». Le journal ne peut être ni modifié ni supprimé.

## Parcours transverses

Quatre parcours traversent plusieurs rôles ; ce sont eux que le jury verra en démo.

**De la réservation au plateau**

1. Admin, A2 : compose et publie le menu ; les convives sont notifiés.
2. Utilisateur, U2 → U4 : choisit ses plats et un créneau avant l'heure limite, paie avec son solde.
3. Utilisateur, U5 : affiche son QR code (disponible hors ligne).
4. Admin, A11 : scanne le QR au créneau ; le repas passe à « Servie », A1 et A3 se mettent à jour, le parent reçoit « Repas servi ».
5. Utilisateur, U8 : reçoit l'invitation et note les plats et/ou le menu ; l'avis passe par la modération (A8).

**Alerte allergie**

1. Utilisateur, U4 : choisit un plat contenant un allergène de son profil ; la modale prévient que la cantine sera informée.
2. Il confirme ; tous les admins et super admins reçoivent l'alerte (A12, compteur rouge, bandeau sur A1, badge en A3).
3. Admin, A12 : contacte le parent ou le convive pour proposer un autre plat, puis marque la réservation comme vérifiée.
4. Admin, A11 : au scan, l'encadré rouge rappelle le plat à risque et qui l'a vérifié.

**Liaison parent par QR code**

1. Élève, U12 : affiche son QR code de liaison.
2. Parent, P2 : scanne le QR, vérifie le nom et la classe, confirme.
3. L'enfant apparaît en P1 ; l'élève reçoit « Compte parent lié ». Aucun matricule n'est utilisé.

**Suppression d'un compte en deux phases**

1. Utilisateur, U13 : demande la suppression (motif facultatif).
2. Super admin, S4 : refuse avec un message, ou accepte.
3. Acceptée : le compte part en corbeille (S2 ou S4 → S3) avec motif ; il ne peut plus se connecter ; l'utilisateur et son parent sont notifiés.
4. Super admin, S3 : restaure, ou supprime définitivement après avoir retapé le nom ou son mot de passe ; sinon la purge automatique agit après 30 jours.
5. Les données personnelles sont effacées ; réservations et avis restent, anonymisés. Chaque étape est tracée en S6.

## Priorités et démo du pitch

Dix écrans couvrent les sept exigences minimum du guide ; ils passent en premier, le reste suit par valeur pour le jury.

| Priorité | Écrans | Ce que ça couvre |
| --- | --- | --- |
| 1 — Minimum du guide | C2, C3, U1, U2, U4, U5, U6, A1, A2, A3 | Inscription, connexion, menu, réservation, solde et historique, menus, commandes du jour, tableau de bord |
| 2 — Différenciant | A11, A12, U8, A8, U7, P1, P2, P4 | Files d'attente, allergies, notation, Mobile Money, parents |
| 3 — Cahier des charges v2 | C4, C6, U3, U10 à U13, A5, A9, A10, S1 à S6 | Profils, photos, classes, modération, super administration |
| 4 — Si le temps le permet | C1, C5, P3, P5, P6, A4, A6, A7 | Stocks, rapports, confort |

**Démo de 10 minutes, dans cet ordre :**

1. Admin, A2 : publier le menu de la semaine.
2. Utilisateur, U2 : le plat à l'arachide est signalé ; réserver deux plats et un créneau (U4), obtenir le QR (U5).
3. Utilisateur, U4 : réserver malgré tout un plat à risque ; Admin, A12 : l'alerte arrive, contacter le parent, marquer vérifié.
4. Admin, A11 : scanner le QR, le repas est servi avec l'alerte allergie.
5. Admin, A1 et A3 : statistiques et prévision mises à jour.
6. Parent, P1 → P4 : solde bas de Yao, recharge Wave ; P2 : lier un enfant par QR.
7. Utilisateur, U8 : noter un plat et le menu ; Admin, A8 : un avis contenant un mot interdit est signalé.
8. Super admin, S5 et S3 : ressaisie du mot de passe, clés masquées, corbeille et journal d'audit.

## Architecture et choix techniques

L'application est un monolithe Laravel rendu côté serveur en Blade, avec Tailwind CSS et quelques bibliothèques JavaScript légères. Ce choix tient en une journée de hackathon, se déploie sur un simple hébergement PHP et reste lisible pour le jury (critère « Qualité du code »).

| Couche | Choix | Pourquoi |
| --- | --- | --- |
| Framework | Laravel (PHP 8.2+) | Authentification, validation, migrations, chiffrement, planificateur de tâches inclus ; maîtrisé par l'équipe |
| Interface | Blade + Tailwind CSS | Pages rendues côté serveur, mobile d'abord, aucun framework JS à apprendre |
| JavaScript | Cropper.js, qrcodejs, html5-qrcode | Recadrage des images, affichage et scan des QR codes (caméra du téléphone) |
| Base de données | MySQL en production, SQLite en développement | Gratuit, supporté par tous les hébergeurs ivoiriens courants |
| Images | Extension GD de PHP | Recadrage, redimensionnement et compression WebP côté serveur sans dépendance |
| Paiements | Interface PaymentGateway + un pilote par moyen | Ajouter Wave, CinetPay ou PayDunya sans toucher au reste ; pilote « simulé » en environnement Test |
| Tâches planifiées | Planificateur Laravel | Purge quotidienne de la corbeille après 30 jours (paramétrable) |

**Organisation du code.** Un dossier de contrôleurs par rôle (User, Parent, Admin, Super), et la logique métier dans des services réutilisables :

- ImageService : validation, recadrage, compression.
- ReservationService : heure limite, débit du solde, remboursement.
- AccountService : corbeille, restauration, suppression définitive et anonymisation.
- ModerationService : détection des mots interdits.
- AuditLogger : trace qui a fait quoi, et quand.

**Sécurité.**

- Contrôle d'accès : un middleware `role:` sur chaque groupe de routes. Un administrateur reçoit une erreur 403 sur /super/\*.
- Comptes désactivés ou en corbeille : un middleware déconnecte le compte à la requête suivante.
- Fichiers : validation dans le navigateur (taille, format) puis à nouveau sur le serveur (règles `mimes` et `max:5120`, type MIME réel lu par GD).
- Clés API : chiffrées en base par le chiffrement de Laravel (AES-256, clé APP\_KEY). Seuls les 4 derniers caractères sont gardés en clair pour l'affichage masqué.
- Modifications sensibles : la saisie du mot de passe est exigée à chaque modification (règle `current_password`).
- Formulaires et sessions : jeton CSRF, mots de passe hachés (bcrypt), sessions régénérées à la connexion.

**Hors périmètre de la journée.** Les appels réels aux API Wave, Orange Money et autres. Le pilote simulé permet la démo ; chaque pilote réel se branche sur l'interface PaymentGateway avec la documentation du fournisseur.

## Modèle de données

19 tables métier (plus les tables standard de Laravel : sessions, cache, jobs). Le compte utilisateur est central : les tables qui doivent survivre à une suppression définitive (réservations, avis, transactions) ont une clé `user_id` nullable. Elles sont ainsi anonymisées au lieu d'être effacées.

| Table | Champs clés | Relations |
| --- | --- | --- |
| users | role (user, parent, admin, super\_admin), account\_type (eleve, personnel), first\_name, last\_name, email, class\_name, matricule, position, avatar\_path, balance, allergies, diets, class\_confirmed\_for, status (active, disabled, trashed), trashed\_at, trash\_reason | Centre du modèle |
| parent\_child | parent\_id, child\_id, linked\_at | users ↔ users (plusieurs à plusieurs) |
| link\_tokens | child\_id, token, expires\_at, used\_at | users 1-n ; jeton du QR valable 15 min, usage unique |
| dishes | name, category (entree, plat, dessert, boisson), price, description, image\_path, kcal, allergens, diets, deleted\_at | Suppression douce pour garder l'historique |
| ingredients | name, unit, stock, threshold | — |
| dish\_ingredient | dish\_id, ingredient\_id, quantity | dishes ↔ ingredients (recette) |
| menus | date (unique), is\_published | 1 menu par jour |
| menu\_items | menu\_id, dish\_id, planned\_portions | menus ↔ dishes |
| reservations | user\_id (nullable), menu\_id, slot, status (paid, served, missed, cancelled), code, total, class\_snapshot, booked\_by, served\_at | users 1-n, menus 1-n |
| reservation\_items | reservation\_id, dish\_id, quantity, unit\_price | Plusieurs plats par réservation |
| reviews | user\_id (nullable), menu\_id, rating, comment, status (visible, flagged, hidden), flag\_reason, moderated\_by | 1 avis par utilisateur et par menu |
| transactions | user\_id (nullable), type (topup, meal, refund, adjustment), amount, label, reference | Historique du portefeuille |
| payments | payer\_id, beneficiary\_id, payment\_method\_id, amount, status, environment, provider\_reference | Recharges Mobile Money |
| payment\_methods | code, name, is\_active, environment (test, live) | 1-n payment\_credentials |
| payment\_credentials | payment\_method\_id, environment, key\_name, value (chiffré), last4 | Clés distinctes par environnement |
| deletion\_requests | user\_id, reason, status (pending, accepted, refused), response\_message, handled\_by | Demandes traitées par le super admin |
| audit\_logs | actor\_id, action, target\_type, target\_id, details, ip, created\_at | Journal non modifiable |
| app\_notifications, settings | user\_id, title, body, url, read\_at ; key, value | Notifications internes ; heure limite, période scolaire, durée de corbeille, mots interdits |

**Statistiques par classe.** `class_snapshot` copie la classe de l'élève au moment de la réservation, si elle est renseignée. Les statistiques par classe restent donc justes après un changement de classe ou une suppression de compte.

**Suppression définitive.** La ligne `users` est effacée et la photo supprimée. Les liens parent sont retirés. Dans `reservations`, `reviews` et `transactions`, `user_id` passe à NULL. Un avis sans auteur s'affiche « Utilisateur supprimé ».
