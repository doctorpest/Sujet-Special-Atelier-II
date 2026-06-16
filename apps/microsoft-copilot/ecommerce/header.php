<?php require_once __DIR__ . '/db.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Boutique en ligne</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1><a href="<?= BASE_URL ?>/index.php">Ma Boutique</a></h1>
        <nav>
            <a href="<?= BASE_URL ?>/index.php">Catalogue</a>
            <a href="<?= BASE_URL ?>/search.php">Recherche</a>
            <a href="<?= BASE_URL ?>/cart.php">Panier</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/account_orders.php">Mes commandes</a>
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/logout.php">Déconnexion (<?= htmlspecialchars($_SESSION['user']['name']) ?>)</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php">Connexion</a>
                <a href="<?= BASE_URL ?>/register.php">Créer un compte</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
