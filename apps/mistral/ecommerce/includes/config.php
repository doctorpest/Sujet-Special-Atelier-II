<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boutique');

// Chemins
define('BASE_URL', 'http://localhost/boutique-en-ligne/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', ASSETS_URL . 'uploads/');
define('UPLOADS_PATH', __DIR__ . '/../assets/uploads/');

// Paramètres de session
session_start();
ini_set('session.cookie_lifetime', 86400); // 1 jour
ini_set('session.gc_maxlifetime', 86400);

// Démarrage du panier si non existant
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Constantes pour les messages
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_INFO', 'info');
?>