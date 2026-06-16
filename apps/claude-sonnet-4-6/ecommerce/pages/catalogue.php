<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$cat_id   = (int)($_GET['cat'] ?? 0);
$prix_min = (float)($_GET['prix_min'] ?? 0);
$prix_max = (float)($_GET['prix_max'] ?? 0);
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * ITEMS_PER_PAGE;

$cats = get_categories();
$cat_active = null;
if ($cat_id) {
    foreach ($cats as $c) if ($c['id'] == $cat_id) { $cat_active = $c; break; }
}

$where  = ['p.actif=1'];
$params = [];
if ($cat_id)   { $where[] = 'p.categorie_id=?'; $params[] = $cat_id; }
if ($prix_min) { $where[] = 'p.prix>=?';        $params[] = $prix_min; }
if ($prix_max) { $where[] = 'p.prix<=?';        $params[] = $prix_max; }
$wstr = implode(' AND ', $where);

$sorts = ['newest'=>'p.created_at DESC','price_asc'=>'p.prix ASC','price_desc'=>'p.prix DESC','name'=>'p.nom ASC'];
$order = $sorts[$sort] ?? 'p.created_at DESC';

$count_stmt = db()->prepare("SELECT COUNT(*) FROM produits p WHERE $wstr");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / ITEMS_PER_PAGE));

$stmt = db()->prepare(
    "SELECT p.*, c.nom AS cat_nom FROM produits p
     JOIN categories c ON c.id=p.categorie_id
     WHERE $wstr ORDER BY $order LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset"
);
$stmt->execute($params);
$produits = $stmt->fetchAll();

$pageTitle = ($cat_active ? e($cat_active['nom']) . ' — ' : '') . 'Catalogue — ' . SITE_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
  <h1><?= $cat_active ? e($cat_active['nom']) : 'Catalogue' ?></h1>
  <span class="text-muted"><?= $total ?> produit<?= $total > 1 ? 's' : '' ?></span>
</div>

<!-- Filtres -->
<form class="search-bar" method="get">
  <?php if ($cat_id): ?><input type="hidden" name="cat" value="<?= $cat_id ?>"><?php endif ?>
  <select name="cat" onchange="this.form.submit()">
    <option value="">Toutes catégories</option>
    <?php foreach ($cats as $c): ?>
    <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= e($c['nom']) ?></option>
    <?php endforeach ?>
  </select>
  <input type="number" name="prix_min" placeholder="Prix min (€)" value="<?= $prix_min ?: '' ?>" min="0" step="0.01">
  <input type="number" name="prix_max" placeholder="Prix max (€)" value="<?= $prix_max ?: '' ?>" min="0" step="0.01">
  <select name="sort">
    <option value="newest"     <?= $sort=='newest'?'selected':'' ?>>Plus récents</option>
    <option value="price_asc"  <?= $sort=='price_asc'?'selected':'' ?>>Prix ↑</option>
    <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Prix ↓</option>
    <option value="name"       <?= $sort=='name'?'selected':'' ?>>Nom A–Z</option>
  </select>
  <button class="btn" type="submit">Filtrer</button>
  <a href="catalogue.php" class="btn btn--outline">Réinitialiser</a>
</form>

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
      <p class="product-card__stock <?= $p['stock']==0?'out':'' ?>">
        <?= $p['stock']>0 ? 'En stock ('.$p['stock'].')' : 'Rupture de stock' ?>
      </p>
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

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Pages">
  <?php for ($i=1; $i<=$pages; $i++):
    $qs = http_build_query(array_merge($_GET, ['page'=>$i]));
  ?>
    <?php if ($i==$page): ?>
      <span class="current"><?= $i ?></span>
    <?php else: ?>
      <a href="?<?= $qs ?>"><?= $i ?></a>
    <?php endif ?>
  <?php endfor ?>
</nav>
<?php endif ?>

<?php else: ?>
<div class="empty-state">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
  <p>Aucun produit ne correspond à votre sélection.</p>
</div>
<?php endif ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
