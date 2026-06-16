<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Boutique Pro</title>
    <link rel="stylesheet" href="/maboutique/public/css/style.css">
</head>
<body>
<header class="main-header">
    <div class="container header-flex">
        <a href="/maboutique/index.php" class="logo">ShopVanilla</a>
        <nav class="nav-links">
            <a href="/maboutique/index.php">Catalogue</a>
            <a href="/maboutique/cart.php">Panier (<span><?= $cart_count ?></span>)</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/maboutique/orders_history.php">Mes Commandes</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/maboutique/admin/index.php" class="admin-link">Admin</a>
                <?php endif; ?>
                <a href="/maboutique/logout.php" class="btn-logout">Déconnexion</a>
            <?php else: ?>
                <a href="/maboutique/login.php">Connexion</a>
                <a href="/maboutique/register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">