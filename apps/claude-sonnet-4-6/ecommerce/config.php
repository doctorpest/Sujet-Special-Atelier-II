<?php
// ============================================================
//  Configuration globale
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'boutique');
define('DB_USER',     'root');        // ← adaptez
define('DB_PASS',     '');            // ← adaptez
define('DB_CHARSET',  'utf8mb4');

define('SITE_NAME',   'Maison Lumière');
define('SITE_URL',    'http://localhost/boutique');   // sans slash final
define('UPLOAD_DIR',  __DIR__ . '/uploads/');
define('UPLOAD_URL',  SITE_URL . '/uploads/');
define('ITEMS_PER_PAGE', 9);

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Afficher les erreurs en dev (désactivez en prod)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrage de session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // true si HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
