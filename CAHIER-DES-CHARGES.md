# Cahier des charges détaillé — Cantine de Vatel (v2)

Complète le Guide des participants. Aligné sur le README v2 et la maquette interactive v2.
Stack : Laravel 11 + Blade + Tailwind + MySQL (SQLite en développement).

## Rôles

1. Utilisateur : Élève ou Personnel (type choisi à l'inscription)
2. Parent (type également proposé à l'inscription)
3. Administrateur (gestion de la cantine) — inclut un compte « comptoir » qui utilise l'écran A11
4. Super administrateur (niveau le plus élevé, a aussi tous les droits de l'administrateur)

## 1. Utilisateur (élève ou personnel)

### Profil
- Nom et prénom obligatoires.
- Champs facultatifs selon le type de compte :
  - Élève : classe, matricule
  - Personnel : poste
- Seuls les champs du type choisi s'affichent ; ils peuvent être renseignés, modifiés ou vidés à tout moment.
- Indicateur discret de profil incomplet (non bloquant).
- Affichage de ses informations sur son profil.
- Allergies et régimes déclarés dans le profil.
- Choix de la langue (français par défaut, ou anglais), enregistré sur le compte.

### Photo de profil
- Ajouter (galerie ou appareil photo sur mobile), modifier (remplacer), supprimer.
- Taille max : 5 Mo (message d'erreur : « Image trop lourde (5 Mo maximum) »).
- Formats : JPG, PNG, WebP.
- Recadrage carré/cercle avant enregistrement.
- Compression automatique après envoi (ré-encodage WebP).
- Confirmation avant suppression.
- Sans photo : avatar par défaut avec les initiales.

### Confirmation de classe (élève uniquement)
- Si la classe est renseignée : à chaque rentrée ou changement de période, demander « Êtes-vous toujours en [classe] ? » (confirmer ou corriger).
- Si la classe n'est pas renseignée : proposer de l'ajouter à la rentrée (ignorable).
- La nouvelle période scolaire est démarrée par l'administration (A10).

### Réservation
- Réserver plusieurs plats, avec quantités, pour un même jour.
- Choix d'un créneau de passage (11h30, 12h00, 12h30, 13h00), avec capacité par créneau.
- Paiement par le solde, ou complément Mobile Money si le solde est insuffisant.
- Modifier ou annuler une réservation jusqu'à une heure limite définie par l'administration ; remboursement immédiat sur le solde.
- Historique de ses réservations (à venir et passées).
- QR code de retrait + code de secours à 6 chiffres ; QR consultable hors ligne.
- Plat contenant un allergène déclaré : signalé avant validation ; si l'utilisateur réserve malgré tout, une alerte part à l'administration.

### Menu et plats
- Menu de la semaine public.
- Filtrer les plats par catégorie, jour, type (entrée, plat, dessert, boisson), régime (végétarien, sans porc…) et allergies.
- Fiche plat : photo, description, ingrédients, allergènes, avis.
- Plats incompatibles avec le profil signalés.

### Portefeuille
- Solde, recharge Mobile Money, historique des transactions et reçus.

### Avis
- Donner un avis sur chaque plat commandé et/ou sur le menu entier (note + commentaire).
- Seuls les utilisateurs ayant réservé le menu peuvent le noter.
- Question « Avez-vous tout mangé ? » après le repas (mesure du gaspillage).

### Suggestions
- Suggestions de repas personnalisées par règles (historique, notes, allergies).

### Notifications
- Notifications internes : réservation, solde bas, repas servi, publication du menu.

### Suppression de compte
- Demander la suppression de son compte (motif facultatif) ; la demande est transmise au super admin.

### Liaison parent
- Génération d'un QR code de liaison depuis le profil (jeton valable 15 min, usage unique) + code de secours en saisie manuelle.

## 2. Parent
- Se lier au compte de son enfant (élève) en scannant le QR code généré depuis le profil de l'élève, ou en saisissant le code de secours. La liaison ne dépend pas du matricule.
- Gérer plusieurs enfants depuis un seul compte.
- Suivre ses enfants : repas de la semaine, réservations à venir, équilibre alimentaire, dépenses, allergies déclarées, plafond journalier.
- Recharger le compte d'un enfant (Mobile Money ou carte) ; recharge automatique sous un seuil ; historique des paiements et reçus.
- Alertes : solde bas (seuil réglable par enfant), repas réservé ou servi, alerte allergie, messages de la cantine.
- Langue : français ou anglais.

## 3. Administrateur

### Tableau de bord
- Réservations et recettes du jour, taux de retrait, note moyenne, affluence par créneau, gaspillage, réservations par classe, prévision par plat, alertes de stock, bandeau des réservations à risque allergique.

### Alertes allergies (A12)
- Notification à chaque réservation d'un plat contenant un allergène déclaré par le convive.
- Depuis l'alerte : voir la commande, contacter le parent ou le convive, marquer comme vérifié (action tracée dans le journal d'audit).

### Plats et recettes
- Ajouter, modifier, archiver, supprimer des plats et recettes (suppression douce pour garder l'historique).
- Fiche plat : nom, catégorie, prix, description, kcal, allergènes, régimes, recette (ingrédients + quantités) reliée aux stocks.
- Uploader sa propre image pour chaque plat (ordinateur ou téléphone), la remplacer ou la supprimer.
- Recadrage et aperçu de la fiche avant publication.
- Images : JPG, PNG, WebP, 5 Mo max, compression automatique.
- Image par défaut (par catégorie) si un plat n'a pas encore de photo.

### Menus
- Composer les menus de la semaine à partir du catalogue (glisser-déposer), avec portions prévues.
- Dupliquer la semaine précédente.
- Publier le menu avec notification des convives.

### Commandes du jour
- Quantités par plat et par créneau, prévision de la semaine par plat et par jour.
- Filtres : créneau, statut, « allergène réservé ».
- Fiche de production imprimable, clôture de la journée (repas non retirés = « missed »).

### Comptoir (A11)
- Scan du QR code (caméra du téléphone) ou saisie du code de secours.
- Résultat immédiat : validé, mauvais créneau, déjà servi, code invalide.
- Avertissement allergie renforcé.

### Stocks
- Besoins calculés à partir des menus et des recettes, seuils d'alerte, entrées, pertes, bon de commande.

### Modération des avis
- Trier, filtrer (plat ou menu, signalés, publiés, masqués) et masquer avec motif les avis indésirables (insultes, spam, hors sujet).
- Signalement automatique des avis contenant des mots interdits (liste modifiable).

### Suivi et statistiques
- Nombre de réservations par plat et par jour (prévision des quantités).
- Statistiques par classe (uniquement pour les élèves ayant renseigné leur classe) ; la classe est copiée sur la réservation (class_snapshot) pour rester juste après un changement de classe ou une suppression.

### Rapports
- Fréquentation, recettes, gaspillage, satisfaction, classes, stocks. Export CSV et impression PDF.

### Utilisateurs et paramètres
- Consultation des comptes, ajustement manuel du solde (paiement en espèces au guichet).
- Paramètres : créneaux et capacités, heure limite, démarrage d'une nouvelle période scolaire, liste des mots interdits.

## 4. Super administrateur

### Gestion des administrateurs
- Créer, modifier, changer de rôle, désactiver les comptes administrateurs.
- Consulter l'ensemble des comptes (S2).

### Suppression de comptes en 2 phases

**Phase 1 – Désactivation (corbeille)**
- Le compte ne peut plus se connecter et n'est plus visible (déconnexion à la requête suivante).
- Toutes les données sont conservées (profil, réservations, avis, liaison parent).
- Motif obligatoire, enregistré dans l'historique.
- Restauration possible à tout moment depuis la corbeille.
- Notification à l'utilisateur et au parent lié.

**Phase 2 – Suppression définitive**
- Uniquement par le super admin, uniquement sur un compte déjà en corbeille.
- Confirmation renforcée (retaper le nom du compte ou son mot de passe).
- Effacement irréversible des données personnelles (photo supprimée, liens parent retirés).
- Conserver les statistiques : réservations, avis et transactions anonymisés (user_id à NULL), avis affichés comme « Utilisateur supprimé ».
- Purge automatique après X jours en corbeille (durée paramétrable, 30 jours par défaut), via le planificateur Laravel.

### Demandes de suppression des utilisateurs
- Accepter : le compte passe en phase 1 (corbeille).
- Refuser : envoyer un message de justification à l'utilisateur.

### Configuration des moyens de paiement
- Ajouter, modifier, supprimer les clés API de chaque moyen de paiement (Wave, Orange Money, MTN MoMo, Moov Money, carte bancaire, agrégateurs type CinetPay / PayDunya).
- Activer ou désactiver chaque moyen de paiement sans supprimer ses clés.
- Deux environnements séparés : Test et Production, avec des clés distinctes.
- Bouton « Tester la connexion », obligatoire avant mise en service.
- Architecture : interface PaymentGateway + un pilote par moyen ; pilote « simulé » en environnement Test.
- Sécurité :
  - Clés chiffrées en base (Crypt::encryptString, AES-256, APP_KEY), jamais stockées en clair, jamais dans .env.
  - Clés masquées après enregistrement (ex : sk_live_••••••••4F2a), seuls les 4 derniers caractères gardés en clair, remplaçables mais jamais réaffichables ni renvoyées au navigateur.
  - Ressaisie du mot de passe du super admin avant toute modification (règle current_password).
  - Écran inaccessible aux administrateurs (403).

### Journal d'audit
- Tracer les actions sensibles : désactivations, suppressions, restaurations, changements de rôle, modifications des clés API, vérifications d'allergie (qui, quand, quoi, IP).
- Journal non modifiable, export CSV.

## Exigences transversales
- Contrôle d'accès strict par rôle : middleware role: sur chaque groupe de routes + policies ; middleware EnsureAccountActive pour les comptes désactivés ou en corbeille.
- Interface responsive, simple et adaptée au mobile, installable sur l'écran d'accueil.
- Validation des fichiers côté client ET côté serveur (mimes:jpg,jpeg,png,webp, max:5120, vérification du type réel par GD).
- Jeton CSRF, mots de passe hachés, session régénérée à la connexion.
- Langue de l'interface : français par défaut ; anglais disponible pour les espaces utilisateur et parent ; administration en français.

## Architecture de référence (README v2)
- Monolithe Laravel rendu côté serveur (Blade + Tailwind).
- JS : Cropper.js (recadrage), qrcodejs (affichage QR), html5-qrcode (scan caméra). Icônes SVG en ligne.
- Images : extension GD (recadrage, redimensionnement, WebP).
- Contrôleurs par rôle : User, Parent, Admin, Super.
- Services : ImageService, ReservationService, AccountService, ModerationService, AuditLogger.
- 19 tables métier : users, parent_child, link_tokens, dishes, ingredients, dish_ingredient, menus, menu_items, reservations, reservation_items, reviews, transactions, payments, payment_methods, payment_credentials, deletion_requests, audit_logs, app_notifications, settings.
- 42 écrans (C1–C6, U1–U13, P1–P6, A1–A12, S1–S6) — routes listées dans le README.
- Variables : APP_LOCALE, CANTINE_CUTOFF, CANTINE_TRASH_DAYS, PAYMENT_ENV, UPLOAD_MAX_KB.

## Livrables attendus
1. Architecture et choix techniques justifiés.
2. Modèle de données (entités, relations).
3. Liste des écrans par rôle.
4. Code, module par module, avec instructions d'installation.
5. README à jour, maquette interactive, comptes de démonstration des rôles.
