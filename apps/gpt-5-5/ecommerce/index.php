<?php
require_once 'config/db.php';
require_once 'config/helpers.php';

$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$min = $_GET['min'] ?? '';
$max = $_GET['max'] ?? '';

$where = [];
$params = [];
if ($q !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$q%"; }
if ($category !== '') { $where[] = 'p.category_id = ?'; $params[] = $category; }
if ($min !== '') { $where[] = 'p.price >= ?'; $params[] = $min; }
if ($max !== '') { $where[] = 'p.price <= ?'; $params[] = $max; }
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
include 'header.php';
?>
<h1>Catalogue</h1>
<form class="filters" method="get">
    <input name="q" placeholder="Nom du produit" value="<?= e($q) ?>">
    <select name="category">
        <option value="">Toutes les catégories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (string)$cat['id'] === (string)$category ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="min" type="number" step="0.01" placeholder="Prix min" value="<?= e($min) ?>">
    <input name="max" type="number" step="0.01" placeholder="Prix max" value="<?= e($max) ?>">
    <button>Rechercher</button>
</form>
<div class="grid">
<?php foreach ($products as $p): ?>
    <article class="card">
        <?php if ($p['image']): ?><img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>"><?php else: ?><div class="placeholder">Image</div><?php endif; ?>
        <h2><?= e($p['name']) ?></h2>
        <p><?= e($p['category_name'] ?? 'Sans catégorie') ?></p>
        <p><?= e($p['description']) ?></p>
        <strong><?= number_format($p['price'], 2, ',', ' ') ?> €</strong>
        <p>Stock : <?= (int)$p['stock'] ?></p>
        <form method="post" action="cart.php">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="number" name="quantity" min="1" max="<?= max(1, (int)$p['stock']) ?>" value="1">
            <button <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>Ajouter au panier</button>
        </form>
    </article>
<?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
