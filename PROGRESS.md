# Avancement

**État :** étapes 1 à 25 terminées — version **v1.0.0**.

## Terminé
| Étapes | Contenu | Tag |
| --- | --- | --- |
| 1–2 | Analyse, arbitrages, inventaire des écrans | — |
| 3–5 | Laravel 12, rôles, 19 tables + seeder, design de la maquette, upload d'image | v0.1.0 |
| 6–9 | Comptes, profil, menu, réservation + QR, portefeuille, avis | — |
| 10 | Espace parent | v0.2.0 |
| 11–15 | Administration complète (A1–A12) | v0.3.0 |
| 16–18 | Super administration (S1–S6) | v0.4.0 |
| 19–21 | Anglais, suggestions, Chef de la semaine, anti-gaspi, PWA | v0.5.0 |
| 22–25 | Revue sécurité/mobile, déploiement, README, soumission | v1.0.0 |

Ordre réel : les étapes 11 à 13 (exigences minimum côté administration) ont été faites avant l'étape 10 (parent), conformément à l'ordre de priorité demandé.

## Simulé
- Paiements (pilote `SimulatedGateway`, aucun débit réel).
- E-mails, SMS, e-mail d'activation des administrateurs (notifications internes uniquement).
- Export PDF = impression du navigateur.
- Horloge de démonstration figée au mardi 24/11/2026 (`CANTINE_DEMO_NOW`).

## Prévu (non fait)
- Pilotes de paiement réels, notifications push et SMS.
- « Mot de passe oublié » par e-mail, envoi des rapports par e-mail, import CSV des élèves.
- Étapes 26–27 : pitch deck (`docs/PITCH.md`) et script de démo minuté (`docs/DEMO.md`).

## Problèmes rencontrés
- Laravel 11 non installable (avis de sécurité Composer sur toute la branche 11.x) → Laravel 12, même API.
- PHP et Composer absents de la machine : extensions activées dans `C:\php\php.ini` (sauvegarde `php.ini.bak-cantine`), Composer installé dans `~/bin`.
- Écritures de fichiers intermittentes refusées (antivirus) pendant `composer install` / `npm install` : relancées.
- Push GitHub impossible depuis le terminal de Claude (pas d’identifiants) : poussé par l’auteur. Dépôt renommé en `lartisse/cantine-vatel` ; commits et tags v0.1.0 à v1.0.0 en ligne.
