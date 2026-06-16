<?php
require_once __DIR__ . '/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$name = trim($_GET['q'] ?? '');
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";
$params = [];

if ($name !== '') {
    $sql .= " AND p.name LIKE :name";
    $params[':name'] = '%' . $name . '%';
}
if ($category_id > 0) {
    $sql .= " AND p.category_id = :cat";
    $params[':cat'] = $category_id;
}
if ($min_price !== '' && is_numeric($min_price)) {
    $sql .= " AND p.price >= :minp";
    $params[':minp'] = (float)$min_price;
}
if ($max_price !== '' && is_numeric($max_price)) {
    $sql .= " AND p.price <= :maxp";
    $params[':maxp'] = (float)$max_price;
}

$sql .= " ORDER BY p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<h2>Recherche de produits</h2>

<form method="get" class="filter-form">
    <label>Nom :
        <input type="text" name="q" value="<?= htmlspecialchars($name) ?>">
    </label>
    <label>Catégorie :
        <select name="category">
            <option value="0">Toutes</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Prix min :
        <input type="number" step="0.01" name="min_price" value="<?= htmlspecialchars($min_price) ?>">
    </label>
    <label>Prix max :
        <input type="number" step="0.01" name="max_price" value="<?= htmlspecialchars($max_price) ?>">
    </label>
    <button type="submit">Rechercher</button>
</form>

<div class="product-grid">
    <?php foreach ($products as $product): ?>
        <div class="product-card">
            <?php if ($product['image']): ?>
                <img src="<?= UPLOAD_URL . htmlspecialchars($product['image']) ?>" alt="">
            <?php else: ?>
                <div class="placeholder-img">Aucune image</div>
            <?php endif; ?>
            <h3><a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
            <p class="price"><?= number_format($product['price'], 2, ',', ' ') ?> €</p>
            <p class="category"><?= htmlspecialchars($product['category_name'] ?? 'Sans catégorie') ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
