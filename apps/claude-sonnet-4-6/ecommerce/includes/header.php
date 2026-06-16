<?php
// includes/header.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
$cats    = get_categories();
$nb_cart = panier_count();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= SITE_URL ?>/" class="logo">
      <span class="logo-accent">✦</span> <?= SITE_NAME ?>
    </a>

    <nav class="main-nav">
      <ul>
        <li><a href="<?= SITE_URL ?>/">Accueil</a></li>
        <li class="has-dropdown">
          <a href="<?= SITE_URL ?>/pages/catalogue.php">Catalogue</a>
          <ul class="dropdown">
            <?php foreach ($cats as $c): ?>
            <li><a href="<?= SITE_URL ?>/pages/catalogue.php?cat=<?= $c['id'] ?>"><?= e($c['nom']) ?></a></li>
            <?php endforeach ?>
          </ul>
        </li>
        <li><a href="<?= SITE_URL ?>/pages/recherche.php">Recherche</a></li>
      </ul>
    </nav>

    <div class="header-actions">
      <?php if (client_logged()): ?>
        <a href="<?= SITE_URL ?>/pages/compte.php" class="btn-icon" title="Mon compte">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        </a>
        <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-icon" title="Déconnexion">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/pages/login.php" class="btn-icon" title="Connexion">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        </a>
      <?php endif ?>

      <a href="<?= SITE_URL ?>/pages/panier.php" class="btn-icon cart-btn" title="Panier">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <?php if ($nb_cart > 0): ?>
          <span class="cart-badge"><?= $nb_cart ?></span>
        <?php endif ?>
      </a>
    </div>
  </div>
</header>

<main class="main-content">
  <div class="container">
    <?= render_flash() ?>
