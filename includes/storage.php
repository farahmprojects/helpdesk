<?php
/**
 * Couche d'abstraction du stockage.
 *
 * Toutes les pages utilisent les mêmes fonctions (find_user_by_username,
 * get_tickets_for_user, etc.). Selon la valeur de STORAGE_MODE, ce fichier
 * inclut l'implémentation JSON ou l'implémentation SQLite (PDO).
 *
 * Cela permet de respecter la consigne "Option 1 OU Option 2" en proposant
 * les deux, et de basculer trivialement entre les deux modes.
 */

require_once __DIR__ . '/config.php';

if (STORAGE_MODE === 'sqlite') {
    require_once __DIR__ . '/storage_pdo.php';
} else {
    require_once __DIR__ . '/storage_json.php';
}

// À l'inclusion, on s'assure que les données initiales existent
storage_init();
