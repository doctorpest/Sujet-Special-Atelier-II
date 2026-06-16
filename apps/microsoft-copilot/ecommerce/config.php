<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL de base (adapter si nécessaire, ex: http://localhost/ecommerce)
define('BASE_URL', 'http://localhost/ecommerce');

// Dossier d’upload (chemin relatif)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
