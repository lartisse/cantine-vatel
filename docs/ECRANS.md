# Écrans (étape 2)

★ = exigence minimum du guide (développée en premier). Détail complet : « Cantine de Vatel — Inventaire des écrans et wireframes.md ».

| Écran | Route | Rôle | ★ |
| --- | --- | --- | --- |
| C1 Accueil public | `/` | tous | |
| C2 Inscription (3 types, étapes) | `/inscription` | visiteur | ★ |
| C3 Connexion (compte désactivé refusé) | `/connexion` | visiteur | ★ |
| C4 Profil (champs selon type, langue, allergies) | `/profil` | connecté | |
| C5 Notifications | `/notifications` | connecté | |
| C6 Confirmation mot de passe | `/confirmer-mot-de-passe` | super admin | |
| U1 Accueil (solde, repas du jour, classe) | `/accueil` | user | ★ (solde) |
| U2 Menu de la semaine + filtres + allergènes | `/menu` | user | ★ |
| U3 Fiche plat | `/plats/{plat}` | user | |
| U4 Réserver (plats, quantités, créneau) | `/reservations/nouvelle/{menu}` | user | ★ |
| U5 Mes réservations + QR | `/reservations` | user | ★ (historique) |
| U6 Portefeuille | `/portefeuille` | user | ★ |
| U7 Recharger | `/portefeuille/recharger` | user | |
| U8 Avis | `/avis/{menu}` | user | |
| U10 Photo de profil | `/profil` | user | |
| U11 Confirmation de classe | `/accueil` | élève | |
| U12 QR liaison parent | `/profil/qr-parent` | élève | |
| U13 Demande de suppression | `/profil/suppression` | user | |
| P1–P6 Parent | `/parent…` | parent | |
| A1 Tableau de bord | `/admin` | admin | ★ |
| A2 Menus de la semaine | `/admin/menus` | admin | ★ |
| A3 Commandes du jour | `/admin/commandes` | admin | ★ |
| A4 Stocks · A5 Plats · A6 Rapports · A7 Utilisateurs · A8 Avis · A9 Classes · A10 Paramètres | `/admin/…` | admin | A5 ★ (plats nécessaires aux menus) |
| A11 Comptoir | `/admin/comptoir` | admin | innovation |
| A12 Alertes allergies | `/admin/alertes` | admin | innovation |
| S1–S6 Super admin | `/super/…` | super_admin (403 admin) | |

États communs : liste vide, réservations closes (heure limite), créneau complet, solde insuffisant, plat incompatible.
