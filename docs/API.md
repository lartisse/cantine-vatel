# API Cantine de Vatel — v1

API REST en JSON pour les applications et services partenaires (application mobile, ERP de l'établissement, borne de comptoir, prestataire de paiement, outil de reporting…).

- **URL de base** : `https://<votre-domaine>/api/v1`
- **Format** : JSON (UTF-8). Dates `AAAA-MM-JJ`, horodatages ISO 8601, montants en FCFA (entiers).
- **Gestion des clés** : *Super admin › Clés d'API* (`/super/api`).

## Authentification

Chaque requête porte une clé d'API créée par le super administrateur :

```http
Authorization: Bearer cvk_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

ou `X-Api-Key: cvk_…`. La clé complète n'est affichée qu'une fois, à sa création : elle est stockée hachée (SHA-256) et ne peut plus être relue. En cas de perte, utilisez « Renouveler » (nouvelle clé, mêmes permissions, ancienne révoquée).

| Réglage par clé | Rôle |
| --- | --- |
| Permissions (portées) | Ce que la clé peut lire ou écrire (voir ci-dessous) |
| Limite | Requêtes par minute (60 par défaut). En-têtes `X-RateLimit-Limit` et `X-RateLimit-Remaining` |
| Expiration | Date au-delà de laquelle la clé est refusée (facultatif) |
| État | Active, suspendue (réactivable), révoquée (définitif) |

Chaque appel met à jour « dernier appel » (date, IP) et le compteur de requêtes de la clé. Création, modification, suspension, renouvellement, révocation et suppression sont tracés dans le journal d'audit.

## Permissions

| Portée | Accès |
| --- | --- |
| `menus:read` | Menus publiés, catalogue des plats, prix, allergènes, portions restantes |
| `reservations:read` | Réservations par jour et statut (**sans** code de retrait ni QR) |
| `orders:read` | Commandes du jour : quantités par plat et par créneau, prévision anti-gaspi |
| `orders:write` | Valider le retrait d'un repas (borne ou caisse externe) |
| `stats:read` | Fréquentation, recettes, taux de retrait, note moyenne |
| `stocks:read` | Ingrédients, seuils, besoins de la semaine |
| `users:read` | Comptes actifs élèves, personnel, parents — **données personnelles** |
| `wallet:write` | Créditer le solde d'un convive après un paiement encaissé ailleurs |

## Points d'accès

| Méthode | Chemin | Portée | Description |
| --- | --- | --- | --- |
| GET | `/ping` | — | Vérifie la clé : nom, permissions, limite, expiration |
| GET | `/menus?du=&au=` | menus:read | Menus publiés de la période (semaine en cours par défaut, 62 jours max) |
| GET | `/menus/{date}` | menus:read | Menu publié d'un jour (créneaux et places restantes, portions restantes) |
| GET | `/plats?categorie=&archives=1` | menus:read | Catalogue (`entree`, `plat`, `dessert`, `boisson`) |
| GET | `/reservations?date=&statut=&par_page=` | reservations:read | Réservations d'un jour (`paid`, `served`, `missed`, `cancelled`), paginées |
| GET | `/commandes?date=` | orders:read | Totaux du jour + par plat : prévu, réservé, à préparer, par créneau |
| POST | `/comptoir/servir` | orders:write | `{ "code": "204863", "forcer": false }` — code à 6 chiffres ou contenu du QR |
| GET | `/statistiques?jours=7` | stats:read | Totaux et détail par jour (1 à 90 jours) |
| GET | `/stocks` | stocks:read | Stock, seuil, besoin de la semaine, statut `ok`/`low`/`out` |
| GET | `/utilisateurs?type=&par_page=` | users:read | Comptes actifs (`eleve`, `personnel`, `parent`), paginés |
| POST | `/utilisateurs/{id}/recharges` | wallet:write | `{ "amount": 5000, "reference": "TX-123", "label": "Recharge Wave" }` |

### Exemples

```bash
curl -H "Authorization: Bearer $CLE" https://cantine.exemple.ci/api/v1/menus
```

```json
{
  "from": "2026-11-23", "to": "2026-11-29",
  "data": [{
    "date": "2026-11-24", "published": true, "open_for_booking": true,
    "booking_cutoff": "2026-11-24T10:00:00+00:00",
    "slots": [{ "time": "11:30", "capacity": 40, "left": 18 }],
    "dishes": [{ "id": 4, "name": "Attiéké poisson braisé", "category": "plat", "price": 1500,
                 "allergens": ["poisson"], "diets": ["sans-porc"], "rating": 4.7, "portions_left": 22 }]
  }]
}
```

```bash
# Valider un retrait depuis une borne
curl -X POST -H "Authorization: Bearer $CLE" -H "Content-Type: application/json" \
     -d '{"code":"204863"}' https://cantine.exemple.ci/api/v1/comptoir/servir
```

Réponse `200` `{"state":"ok","message":"Validé — servir", "reservation":{…}, "allergy_warning":[]}` ; `409` pour « mauvais créneau » (`can_force: true`, renvoyer avec `"forcer": true`) ou « déjà servi » ; `404` code invalide. Toute alerte allergie est renvoyée dans `allergy_warning`.

```bash
# Créditer un solde après un paiement encaissé par un prestataire
curl -X POST -H "Authorization: Bearer $CLE" -H "Content-Type: application/json" \
     -d '{"amount":5000,"reference":"WAVE-88213"}' https://cantine.exemple.ci/api/v1/utilisateurs/1/recharges
```

Réponse `201` avec le nouveau solde. **Idempotent** : renvoyer la même `reference` pour le même convive renvoie `200` avec `"duplicate": true` sans créditer deux fois. Le convive reçoit une notification ; l'opération est tracée dans le journal d'audit.

## Erreurs

| Code | Signification |
| --- | --- |
| 401 | Clé absente, invalide, suspendue, révoquée ou expirée |
| 403 | La clé n'a pas la permission requise |
| 404 | Ressource introuvable (menu, convive, code) |
| 409 | Retrait refusé (mauvais créneau, déjà servi, annulé, pas pour aujourd'hui) |
| 422 | Paramètres invalides (`{"message": "...", "errors": {...}}`) |
| 429 | Limite de requêtes atteinte (en-tête `Retry-After`, en secondes) |

Format d'erreur : `{"error": "forbidden", "message": "Cette clé n'a pas la portée « stocks:read »."}`.

## Bonnes pratiques

- Une clé par application ou service, avec le minimum de permissions.
- Conserver la clé côté serveur (jamais dans le code d'une page web publique).
- Renouveler régulièrement les clés, et immédiatement en cas de fuite.
- L'API exige HTTPS en production.
