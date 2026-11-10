# Helpdesk de cours — Projet PHP

Mini-application web permettant à des étudiants de créer des tickets
(questions/problèmes liés au cours, TD ou TP) et à un tuteur de les consulter
et de les traiter.

## Membres du groupe

- CHENOUF Zarha Souhad
- OSMANI Lisa
- MOUTAKI Farah 

## Comptes de démonstration

Ces comptes sont créés automatiquement au premier lancement.

| Identifiant | Mot de passe | Rôle |
|-------------|--------------|------|
| `etudiant1` | `motdepasse` | étudiant |
| `etudiant2` | `motdepasse` | étudiant |
| `tuteur`    | `motdepasse` | tuteur |

## Choix techniques

### Architecture

```
helpdesk/
├── index.php              # redirection login/dashboard
├── login.php              # page de connexion
├── logout.php             # déconnexion
├── dashboard.php          # liste des tickets (selon rôle)
├── create_ticket.php      # création d'un ticket (étudiant)
├── ticket.php             # détail + commentaires + statut
├── includes/
│   ├── config.php         # constantes globales et choix du stockage
│   ├── auth.php           # sessions, login, logout, vérif rôles
│   ├── helpers.php        # échappement, validation, format
│   ├── storage.php        # routeur entre les deux backends
│   ├── storage_json.php   # backend JSON (Option 1)
│   ├── storage_pdo.php    # backend SQLite/PDO (Option 2)
│   ├── header.php         # en-tête HTML + nav
│   └── footer.php         # pied de page
├── assets/style.css       # palette burgundy / blanc inspirée de l'ENT
├── data/                  # créé automatiquement (JSON et/ou SQLite)
└── sql/schema.sql         # script de création des tables
```

### Stockage : les deux options sont implémentées

Notre projet propose les deux options demandées dans le sujet, avec un
basculement par une simple constante dans `includes/config.php` :

```php
define('STORAGE_MODE', 'sqlite'); // ou 'json'
```

- **Mode `json`** *(Option 1)* : trois fichiers `users.json`, `tickets.json`
  et `comments.json` dans `/data`. Lectures/écritures protégées par
  `flock()` pour éviter la corruption en cas d'accès concurrents.

- **Mode `sqlite`** *(Option 2 — bonus)* : base SQLite `data/helpdesk.sqlite`
  créée automatiquement au premier démarrage. Toutes les requêtes utilisent
  PDO avec des **requêtes préparées**.

Les deux backends exposent **exactement la même API** de fonctions
(`find_user_by_id`, `create_ticket`, `add_comment`, etc.), si bien que les
pages PHP sont strictement identiques quel que soit le mode.

### Sécurité

- **Mots de passe** : hachés avec `password_hash()` / vérifiés avec
  `password_verify()`. Aucun mot de passe en clair n'est stocké.
- **XSS** : toutes les sorties dynamiques passent par la fonction `e()`,
  qui appelle `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Injection SQL** : en mode SQLite, **toutes** les requêtes sont préparées,
  les valeurs sont passées en paramètres.
- **Validation côté serveur** : titre/description non vides et bornés en
  longueur, catégorie/priorité/statut vérifiés avec `validate_in()` contre
  des listes blanches.
- **Vérification des paramètres GET** : `ticket.php` vérifie l'existence et
  la nature numérique de `id` avant tout accès.
- **Contrôle d'accès** :
    - les pages internes redirigent vers `login.php` si pas de session,
    - `create_ticket.php` est interdite au tuteur,
    - un étudiant ne peut **pas** consulter un ticket qui n'est pas le sien
      (réponse HTTP 403),
    - la mise à jour du statut est strictement réservée au tuteur.
- **Sessions** : cookie `HttpOnly` + `SameSite=Lax`, et
  `session_regenerate_id(true)` lors du login pour limiter la fixation.

### Organisation du code

- **Séparation traitement / affichage** : chaque page traite d'abord les
  POST/GET et calcule ses variables, puis inclut `header.php`, génère le HTML
  et inclut `footer.php`. Aucune requête de stockage n'est faite après
  l'envoi de la première ligne HTML.
- **Pas de framework** : uniquement PHP, HTML, CSS, conformément à la
  consigne.
- **Constantes** des valeurs autorisées (catégories, priorités, statuts,
  rôles) centralisées dans `config.php`.

### Design

Palette de couleurs inspirée de l'ENT : burgundy (`#7B1F2B`)
et blanc (`#FFFFFF`).
Codes couleurs pour les priorités et statuts.

## Fonctionnalités implémentées

- [x] Authentification (login, logout, sessions, hash des mots de passe)
- [x] Deux rôles : étudiant et tuteur
- [x] Protection des pages internes (redirection si non connecté)
- [x] Création d'un ticket (étudiant)
- [x] Liste des tickets (filtrée pour l'étudiant, complète pour le tuteur)
- [x] Détail d'un ticket avec contrôle d'accès
- [x] Mise à jour du statut par le tuteur
- [x] Commentaires (ajout depuis la page détail, plusieurs par ticket)
- [x] Validation côté serveur, protection XSS, contrôle des GET
- [x] Stockage JSON (Option 1)
- [x] Stockage SQLite via PDO (Option 2)
- [x] Script `sql/schema.sql`

## Personnalisation rapide

- **Changer le mode de stockage** : `includes/config.php`,
  constante `STORAGE_MODE`.
- **Réinitialiser les données** : supprimer le contenu de `data/` puis
  recharger une page (les fichiers/base sont recréés automatiquement avec
  les comptes par défaut).