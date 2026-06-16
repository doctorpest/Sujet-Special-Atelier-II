<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "Boutique en Ligne"; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>">Boutique en Ligne</a>
            </div>
            <nav class="nav">
                <a href="<?php echo BASE_URL; ?>">Accueil</a>
                <a href="<?php echo BASE_URL; ?>public/products.php">Produits</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>public/account.php">Mon compte</a>
                    <a href="<?php echo BASE_URL; ?>public/order_history.php">Mes commandes</a>
                    <a href="<?php echo BASE_URL; ?>public/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>public/login.php">Connexion</a>
                    <a href="<?php echo BASE_URL; ?>public/register.php">Inscription</a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>public/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">0</span>
                </a>
            </nav>
        </div>
    </header>

    <main>
        <?php $message = getMessage(); ?>
        <?php if ($message): ?>
            <div class="container">
                <div class="message <?php echo $message['type']; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            </div>
        <?php endif; ?>