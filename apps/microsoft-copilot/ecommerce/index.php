<?php
require_once __DIR__ . '/header.php';

// Récupérer catégories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id";
$params = [];
if ($category_id > 0) {
    $sql .= " WHERE p.category_id = :cat";
    $params[':cat'] = $category_id;
}
$sql .= " ORDER BY p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<h2>Catalogue</h2>

<form method="get" class="filter-form">
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
    <button type="submit">Filtrer</button>
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
