<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$produit = get_produit($id);
if (!$produit || !$produit['actif']) { flash('Produit introuvable.','error'); redirect('pages/catalogue.php'); }

$pageTitle = e($produit['nom']) . ' — ' . SITE_NAME;
include __DIR__ . '/../includes/header.php';
?>

<nav style="font-size:13px;color:var(--slate);margin-bottom:28px;">
  <a href="<?= SITE_URL ?>/">Accueil</a> /
  <a href="catalogue.php?cat=<?= $produit['categorie_id'] ?>"><?= e($produit['cat_nom']) ?></a> /
  <?= e($produit['nom']) ?>
</nav>

<div class="product-detail">
  <div class="product-detail__img">
    <img src="<?= produit_image_url($produit['image']) ?>" alt="<?= e($produit['nom']) ?>">
  </div>
  <div>
    <p class="product-detail__cat"><?= e($produit['cat_nom']) ?></p>
    <h1 class="product-detail__name"><?= e($produit['nom']) ?></h1>
    <p class="product-detail__price"><?= format_prix((float)$produit['prix']) ?></p>
    <p class="product-detail__desc"><?= nl2br(e($produit['description'])) ?></p>

    <?php if ($produit['stock'] > 0): ?>
      <p style="font-size:13px;color:var(--slate);margin-bottom:16px;">En stock : <?= $produit['stock'] ?> unité<?= $produit['stock']>1?'s':'' ?></p>
      <form method="post" action="panier_action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ajouter">
        <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">
        <div class="qty-row">
          <button type="button" class="btn btn--outline btn--sm qty-btn" data-dir="-">−</button>
          <input class="qty-input" type="number" name="qte" value="1" min="1" max="<?= $produit['stock'] ?>">
          <button type="button" class="btn btn--outline btn--sm qty-btn" data-dir="+">+</button>
        </div>
        <button class="btn" type="submit">Ajouter au panier</button>
      </form>
    <?php else: ?>
      <p style="color:var(--danger);font-weight:500;">Rupture de stock</p>
    <?php endif ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
