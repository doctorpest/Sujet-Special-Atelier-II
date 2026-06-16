<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Recherche — ' . SITE_NAME;

$q       = trim($_GET['q'] ?? '');
$cat_id  = (int)($_GET['cat'] ?? 0);
$prix_max= (float)($_GET['prix_max'] ?? 0);
$cats    = get_categories();

$produits = [];
$total = 0;
if ($q !== '' || $cat_id || $prix_max) {
    $where = ['p.actif=1'];
    $params = [];
    if ($q)       { $where[] = '(p.nom LIKE ? OR p.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    if ($cat_id)  { $where[] = 'p.categorie_id=?'; $params[] = $cat_id; }
    if ($prix_max){ $where[] = 'p.prix<=?'; $params[] = $prix_max; }
    $wstr = implode(' AND ', $where);
    $stmt = db()->prepare("SELECT p.*, c.nom AS cat_nom FROM produits p JOIN categories c ON c.id=p.categorie_id WHERE $wstr ORDER BY p.nom LIMIT 50");
    $stmt->execute($params);
    $produits = $stmt->fetchAll();
    $total = count($produits);
}

include __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom:28px;">Recherche</h1>

<form class="search-bar" method="get">
  <input type="text" name="q" placeholder="Nom du produit…" value="<?= e($q) ?>">
  <select name="cat">
    <option value="">Toutes catégories</option>
    <?php foreach ($cats as $c): ?>
    <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= e($c['nom']) ?></option>
    <?php endforeach ?>
  </select>
  <input type="number" name="prix_max" placeholder="Prix max (€)" value="<?= $prix_max ?: '' ?>" min="0" step="0.01">
  <button class="btn" type="submit">Rechercher</button>
</form>

<?php if ($q !== '' || $cat_id || $prix_max): ?>
  <p style="margin-bottom:24px;color:var(--slate);"><?= $total ?> résultat<?= $total>1?'s':'' ?> <?= $q ? 'pour « '.e($q).' »' : '' ?></p>
  <?php if ($produits): ?>
  <div class="product-grid">
    <?php foreach ($produits as $p): ?>
    <article class="product-card">
      <a href="produit.php?id=<?= $p['id'] ?>" class="product-card__img">
        <img src="<?= produit_image_url($p['image']) ?>" alt="<?= e($p['nom']) ?>" loading="lazy">
      </a>
      <div class="product-card__body">
        <p class="product-card__cat"><?= e($p['cat_nom']) ?></p>
        <h3 class="product-card__name"><a href="produit.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a></h3>
        <p class="product-card__price"><?= format_prix((float)$p['prix']) ?></p>
        <?php if ($p['stock'] > 0): ?>
        <form method="post" action="panier_action.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="ajouter">
          <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="qte" value="1">
          <button class="btn btn--sm btn--full">Ajouter au panier</button>
        </form>
        <?php else: ?>
        <button class="btn btn--sm btn--full" disabled>Indisponible</button>
        <?php endif ?>
      </div>
    </article>
    <?php endforeach ?>
  </div>
  <?php else: ?>
  <div class="empty-state"><p>Aucun produit trouvé.</p></div>
  <?php endif ?>
<?php endif ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
