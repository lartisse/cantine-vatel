# Checklist de soumission — Cantine de Vatel

Soumission : 21/11/2026 à 18h00.

| Élément | État | Détail |
| --- | --- | --- |
| Dépôt public avec commits réguliers | ✅ | https://github.com/lartisse/cantine-vatel — un commit par étape (Conventional Commits) |
| Tags poussés | ✅ | v0.1.0, v0.2.0, v0.3.0, v0.4.0, v0.5.0, v1.0.0 |
| URL de démo | ☐ À compléter | `https://…` (voir `docs/DEPLOIEMENT.md`) — à défaut, démo locale `php artisan serve` |
| README complet | ✅ | `README.md` : installation, comptes, parcours de démo, architecture, sécurité, simulé/prévu |
| Comptes de test | ✅ | 6 comptes, mot de passe `password`, recréés par `php artisan migrate:fresh --seed` |
| Exigences minimum du guide | ✅ | Les 7 (voir README §3), couvertes par les tests Feature |
| Innovations | ✅ | Créneaux + QR + comptoir, prévision anti-gaspi, bouclier allergènes |
| Tests | ✅ | `php artisan test` : 21 tests verts |
| Build | ✅ | `npm run build` sans erreur, `php artisan route:list` sans erreur |
| Aucun secret dans le dépôt | ✅ | `.env` ignoré ; clés de paiement de démonstration factices et chiffrées en base |
| Vidéo de secours du parcours de démo | ☐ À enregistrer | Suivre le parcours en 8 étapes du README §6 (≈ 5 min) |
| Maquette interactive | ✅ | `maquette/index.html` |

## Avant la démo
```bash
php artisan migrate:fresh --seed    # remet les données de démonstration à zéro
php artisan serve
```
L'application suit l'heure réelle : les réservations du jour ferment à 10h00, les jours suivants restent réservables.
