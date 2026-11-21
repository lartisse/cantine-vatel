# Architecture — Cantine de Vatel (étape 1)

## Choix techniques

| Couche | Choix | Justification |
| --- | --- | --- |
| Framework | **Laravel 12** (PHP 8.2) | Laravel 11 est bloqué par Composer (avis de sécurité non corrigés sur toute la branche 11.x). Laravel 12 garde la même structure et la même API ; c'est la version maintenue compatible PHP 8.2. |
| Vues | Blade + CSS de la maquette (`public/css/app.css`) + Tailwind 4 via Vite | Reproduction fidèle du design de la maquette (mêmes classes CSS). Tailwind reste disponible pour les ajouts. |
| JS | qrcodejs, html5-qrcode, Chart.js, Cropper.js copiés dans `public/vendor` | Pas de CDN : fonctionne hors ligne et en démo sans réseau. |
| Icônes | Sprite SVG repris de `maquette/js/icons.js`, composant `<x-icon>` | Identique à la maquette. |
| Base | SQLite (dev), MySQL (prod, `.env.example`) | |
| Authentification | Contrôleurs maison (pas de Breeze) | Routes françaises `/connexion`, `/inscription` ; plus rapide à adapter aux 4 rôles. |
| Paiements | Interface `PaymentGateway` + pilote `SimulatedGateway` | Démo sans débit réel. |
| Planificateur | `routes/console.php` : purge quotidienne de la corbeille | |

## Organisation

- Contrôleurs : `App\Http\Controllers\{Auth,User,Parent,Admin,Super}`.
- Services : `ImageService`, `ReservationService`, `AccountService`, `ModerationService`, `AuditLogger`.
- Middleware : `role:` (`EnsureRole`), `EnsureAccountActive`.
- 19 tables métier du README-template (section 8), sans écart.

## Incohérences repérées et arbitrages (Guide > CDC > README > Inventaire > Maquette)

1. **Laravel 11 demandé, impossible à installer** → Laravel 12 (voir ci-dessus).
2. **Rôle « comptoir »** : la maquette a un rôle `comptoir` ; le CDC dit « Administrateur, inclut un compte comptoir ». → `role = admin`, `position = Agent de comptoir`, redirigé vers A11 à la connexion.
3. **Rôle « convive »** (maquette) = `user` (CDC/README).
4. **Statuts de réservation** : maquette `payee/servie/non-retiree`, README `paid/served/missed/cancelled` → valeurs du README en base, libellés français à l'écran.
5. **Date de démo** : la maquette fige « mardi 24/11/2026 09:40 ». L'application utilise la vraie date ; le seeder génère les menus de la semaine courante et de la suivante pour que la démo soit toujours vivante.
6. **Mot de passe de démo** : maquette « n'importe lequel » → en Laravel un mot de passe unique documenté : `password`.
7. **U9** absent de l'inventaire (numérotation U1–U13 sans U9) : 42 écrans = 41 + A12, conforme au README.
