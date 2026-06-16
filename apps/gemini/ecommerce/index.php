<?php
require 'config/database.php';

// Récupération des catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Filtres et Recherche
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if (!empty($category_id)) {
    $query .= " AND p.category_id = ?";
    $params[] = (int)$category_id;
}
if (!empty($max_price)) {
    $query .= " AND p.price <= ?";
    $params[] = (float)$max_price;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2>Catalogue des Produits</h2>

<div class="filter-bar">
    <form method="GET" class="filter-form">
        <div class="form-group">
            <label>Recherche</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Nom du produit...">
        </div>
        <div class="form-group">
            <label>Catégorie</label>
            <select name="category_id" class="form-control">
                <option value="">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endphp ?>
            </select>
        </div>
        <div class="form-group">
            <label>Prix max (€)</label>
            <input type="number" step="0.01" name="max_price" value="<?= htmlspecialchars($max_price) ?>" class="form-control">
        </div>
        <div>
            <button type="submit" class="btn" style="background:#34495e;">Filtrer</button>
        </div>
    </form>
</div>

<div class="grid">
    <?php if (empty($products)): ?>
        <p>Aucun produit ne correspond à vos critères.</p>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="card">
                <?php if ($product['image_url']): ?>
                    <img src="/maboutique/public/uploads/<?= htmlspecialchars($product['image_url']) ?>" class="card-img" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <div class="card-img" style="display:flex;align-items:center;justify-content:center;background:#ddd;">Pas d'image</div>
                <?php endif; ?>
                <div class="card-body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><small style="color:#7f8c8d;"><?= htmlspecialchars($product['category_name']) ?></small></p>
                    <p><?= htmlspecialchars(substr($product['description'] ?? '', 0, 100)) ?>...</p>
                    <p class="price"><?= number_format($product['price'], 2, ',', ' ') ?> €</p>
                    <p><small>Stock disponible : <?= $product['stock'] ?></small></p>
                    
                    <form action="cart.php" method="POST" style="margin-top:auto;">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn" <?= $product['stock'] <= 0 ? 'disabled style="background:#7f8c8d;"' : '' ?>>
                            <?= $product['stock'] > 0 ? 'Ajouter au panier' : 'Rupture de stock' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>