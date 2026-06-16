<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Accueil — ' . SITE_NAME;

// Produits vedettes (8 derniers)
$produits = db()->query(
    'SELECT p.*, c.nom AS cat_nom FROM produits p
     JOIN categories c ON c.id=p.categorie_id
     WHERE p.actif=1 ORDER BY p.created_at DESC LIMIT 8'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="hero-inner">
    <div>
      <span class="hero-tag">Nouvelle collection</span>
      <h1>Des objets qui<br><em>méritent votre attention</em></h1>
      <p>Une sélection soigneuse de produits pensés pour durer — du design, de la qualité, sans compromis.</p>
      <div class="hero-actions">
        <a href="pages/catalogue.php" class="btn">Découvrir le catalogue</a>
        <a href="pages/recherche.php" class="btn btn--outline">Rechercher</a>
      </div>
    </div>
    <div class="hero-visual">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="1">
        <rect x="10" y="10" width="60" height="60" rx="2"/>
        <path d="M25 40 L35 50 L55 30"/>
      </svg>
    </div>
  </div>
</section>

<!-- Produits vedettes -->
<section>
  <div class="section-header">
    <h2>Sélection du moment</h2>
    <a href="pages/catalogue.php" class="section-link">Tout voir →</a>
  </div>
  <div class="product-grid">
    <?php foreach ($produits as $p): ?>
    <article class="product-card">
      <a href="pages/produit.php?id=<?= $p['id'] ?>" class="product-card__img">
        <img src="<?= produit_image_url($p['image']) ?>" alt="<?= e($p['nom']) ?>" loading="lazy">
      </a>
      <div class="product-card__body">
        <p class="product-card__cat"><?= e($p['cat_nom']) ?></p>
        <h3 class="product-card__name"><a href="pages/produit.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a></h3>
        <p class="product-card__price"><?= format_prix((float)$p['prix']) ?></p>
        <p class="product-card__stock <?= $p['stock'] == 0 ? 'out' : '' ?>">
          <?= $p['stock'] > 0 ? 'En stock (' . $p['stock'] . ')' : 'Rupture de stock' ?>
        </p>
        <?php if ($p['stock'] > 0): ?>
        <form method="post" action="pages/panier_action.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="ajouter">
          <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="qte" value="1">
          <button class="btn btn--sm btn--full" type="submit">Ajouter au panier</button>
        </form>
        <?php else: ?>
        <button class="btn btn--sm btn--full" disabled>Indisponible</button>
        <?php endif ?>
      </div>
    </article>
    <?php endforeach ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
