<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — <?= e($pageTitle ?? SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-brand">✦ Admin</div>
    <nav class="admin-nav">
      <a href="<?= SITE_URL ?>/admin/index.php" class="<?= $current_page==='index.php'?'active':'' ?>">📊 Tableau de bord</a>
      <a href="<?= SITE_URL ?>/admin/produits.php" class="<?= $current_page==='produits.php'?'active':'' ?>">📦 Produits</a>
      <a href="<?= SITE_URL ?>/admin/categories.php" class="<?= $current_page==='categories.php'?'active':'' ?>">🏷️ Catégories</a>
      <a href="<?= SITE_URL ?>/admin/commandes.php" class="<?= $current_page==='commandes.php'?'active':'' ?>">🛍️ Commandes</a>
      <a href="<?= SITE_URL ?>/admin/clients.php" class="<?= $current_page==='clients.php'?'active':'' ?>">👤 Clients</a>
      <hr style="border:none;border-top:1px solid rgba(255,255,255,.1);margin:12px 0;">
      <a href="<?= SITE_URL ?>/" target="_blank">🌐 Voir la boutique</a>
      <a href="<?= SITE_URL ?>/admin/logout.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="admin-main">
    <?= render_flash() ?>
