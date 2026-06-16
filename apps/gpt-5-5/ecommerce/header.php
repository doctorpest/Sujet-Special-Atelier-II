<?php require_once __DIR__ . '/config/helpers.php'; ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boutique PHP</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">Boutique PHP</a>
    <nav>
        <a href="index.php">Catalogue</a>
        <a href="cart.php">Panier (<?= cart_count() ?>)</a>
        <?php if (is_logged_in()): ?>
            <a href="orders.php">Mes commandes</a>
            <?php if (is_admin()): ?><a href="admin/products.php">Admin</a><?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="login.php">Connexion</a>
            <a href="register.php">Compte</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>
