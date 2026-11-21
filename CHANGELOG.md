# Changelog

Toutes les évolutions notables du projet. Format : [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions : [SemVer](https://semver.org/lang/fr/).

## [Non publié]
### Ajouté
- Suggestions : un plat par catégorie dans un carrousel interactif (flèches, points, balayage, clavier) à défilement automatique ; délai réglable par le super administrateur (5 s par défaut).
- Espaces élève/personnel/parent en plein écran responsive ; administration utilisable sur mobile.
### Supprimé
- Barre de démonstration (changement de rôle et d'heure), horloge figée, connexion en un clic, comptes affichés sur la page de connexion et bouton « Simuler un scan » du comptoir.
### Modifié
- L'application utilise l'heure réelle (fuseau Africa/Abidjan) ; le seeder génère des données alignées sur la date du jour.

## [1.0.0] — 2026-11-21
### Ajouté
- Revue de sécurité : `ReservationPolicy`, redirection sûre après confirmation du mot de passe, test d'isolation entre utilisateurs.
- Affichage vérifié à 360 px ; captures d'écran dans `docs/captures/`.
- `docs/DEPLOIEMENT.md` (VPS Nginx/PHP 8.2/MySQL, HTTPS, cron, lancement local de secours).
- README final fidèle à l'application, `SOUMISSION.md`.

## [0.5.0] — 2026-11-21
### Ajouté
- Traduction anglaise des espaces élève, personnel et parent (`lang/en.json`), langue enregistrée sur le compte.
- Suggestions personnalisées par règles et classement « Chef de la semaine ».
- Panier anti-gaspi à -50 %, application installable (manifest + service worker), menu et QR consultables hors ligne.

## [0.4.0] — 2026-11-21
### Ajouté
- Super administrateur : administrateurs et rôles, comptes, corbeille en 2 phases, suppression définitive avec anonymisation, purge planifiée, demandes de suppression.
- Moyens de paiement Test/Production : clés chiffrées et masquées, test de connexion obligatoire, ressaisie du mot de passe.
- Journal d'audit non modifiable, export CSV.
- 20 tests Feature des règles critiques.

## [0.3.0] — 2026-11-21
### Ajouté
- Plats et recettes (photo, archivage, suppression douce), menus par glisser-déposer, duplication, publication avec notification.
- Commandes du jour et de la semaine, fiche de production, clôture, comptoir QR/code de secours, alertes allergie.
- Tableau de bord (Chart.js), prévision anti-gaspi et impact, stocks calculés depuis les recettes, bon de commande, modération des avis, rapports CSV/PDF, statistiques par classe, paramètres.

## [0.2.0] — 2026-11-21
### Ajouté
- Inscription (élève, personnel, parent), connexion, profil, photo recadrée, notifications, confirmation de classe, QR de liaison parent, demande de suppression.
- Menu de la semaine avec filtres et allergènes, fiche plat, réservation multi-plats avec créneaux et QR code, portefeuille, recharge simulée, avis.
- Espace parent : liaison par QR/code, suivi, réservation pour l'enfant, recharge et recharge automatique, alertes.

## [0.1.0] — 2026-11-21
### Ajouté
- Projet Laravel 12, rôles et middleware `role:` / `EnsureAccountActive`, 19 tables, seeder des données de la maquette.
- Design de la maquette en Blade, icônes SVG, composants, upload d'image (Cropper.js + GD WebP).
- Analyse (`docs/ARCHITECTURE.md`) et inventaire des écrans (`docs/ECRANS.md`).
