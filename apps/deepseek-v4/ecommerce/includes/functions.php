<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Redirection
function redirect($url) {
    header("Location: $url");
    exit;
}

// Échappement HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Exiger connexion utilisateur
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

// Exiger droits admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die('Accès refusé.');
    }
}

// Génération token CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérification token CSRF
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Token CSRF invalide.');
    }
    return true;
}

// Récupérer le panier
function getCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

// Calculer le total du panier
function cartTotal($pdo) {
    $cart = getCart();
    $total = 0;
    if (!empty($cart)) {
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll();
        foreach ($products as $product) {
            $total += $product['price'] * $cart[$product['id']];
        }
    }
    return $total;
}

// Vider le panier
function clearCart() {
    $_SESSION['cart'] = [];
}
?>