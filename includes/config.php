<?php
/**
 * Configuration globale du Helpdesk.
 *
 * STORAGE_MODE permet de basculer entre les deux options demandées :
 *   - 'json'   : stockage dans des fichiers JSON (Option 1)
 *   - 'sqlite' : stockage dans une base SQLite via PDO (Option 2 - bonus)
 *
 * Il suffit de changer cette constante pour passer d'un mode à l'autre.
 * La base SQLite est créée automatiquement au premier accès.
 */

// Mode de stockage actif : 'json' ou 'sqlite'
define('STORAGE_MODE', 'sqlite');

// Chemins des fichiers de données (mode JSON)
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_FILE',    DATA_DIR . '/users.json');
define('TICKETS_FILE',  DATA_DIR . '/tickets.json');
define('COMMENTS_FILE', DATA_DIR . '/comments.json');

// Chemin de la base SQLite (mode sqlite)
define('SQLITE_FILE', DATA_DIR . '/helpdesk.sqlite');

// Valeurs autorisées pour les champs contraints
define('CATEGORIES', ['Cours', 'TD', 'TP']);
define('PRIORITIES', ['Basse', 'Moyenne', 'Haute']);
define('STATUSES',   ['Ouvert', 'En cours', 'Résolu']);
define('ROLES',      ['etudiant', 'tuteur']);
