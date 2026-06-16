<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Boutique</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <nav>
        <a href="index.php" class="logo">🛍️ Ma Boutique</a>
        <div class="nav-links">
            <a href="index.php">Catalogue</a>
            <a href="cart.php">Panier (<?= count(getCart()) ?>)</a>
            <?php if (isLoggedIn()): ?>
                <a href="account.php">Mon compte</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/index.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<main>