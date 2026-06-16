
<?php require_once 'includes/header.php'; ?>

<h1>Catalogue</h1>

<!-- Formulaire de recherche et filtres -->
<form method="GET" action="index.php" class="search-form">
    <input type="text" name="search" placeholder="Rechercher un produit..." value="<?= h($_GET['search'] ?? '') ?>">
    <select name="category">
        <option value="">Toutes les catégories</option>
        <?php
        $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        while ($cat = $catStmt->fetch()):
            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
        ?>
            <option value="<?= $cat['id'] ?>" <?= $selected ?>><?= h($cat['name']) ?></option>
        <?php endwhile; ?>
    </select>
    <input type="number" name="min_price" placeholder="Prix min" step="0.01" value="<?= h($_GET['min_price'] ?? '') ?>">
    <input type="number" name="max_price" placeholder="Prix max" step="0.01" value="<?= h($_GET['max_price'] ?? '') ?>">
    <button type="submit" class="btn">Filtrer</button>
</form>

<div class="products-grid">
    <?php
    // Construction dynamique de la requête
    $where = [];
    $params = [];

    if (!empty($_GET['search'])) {
        $where[] = "p.name LIKE ?";
        $params[] = '%' . $_GET['search'] . '%';
    }
    if (!empty($_GET['category'])) {
        $where[] = "p.category_id = ?";
        $params[] = (int)$_GET['category'];
    }
    if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
        $where[] = "p.price >= ?";
        $params[] = (float)$_GET['min_price'];
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $where[] = "p.price <= ?";
        $params[] = (float)$_GET['max_price'];
    }

    $sql = "SELECT p.*, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY p.created_at DESC";

    // Pagination simple
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 8;
    $offset = ($page - 1) * $perPage;

    // Compter le nombre total (mêmes conditions)
    $countSql = "SELECT COUNT(*) FROM products p";
    if ($where) {
        $countSql .= " WHERE " . implode(' AND ', $where);
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);

    $sql .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    ?>

    <?php if (count($products) > 0): ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="uploads/<?= h($product['image'] ?: 'placeholder.jpg') ?>" alt="<?= h($product['name']) ?>">
                <h3><?= h($product['name']) ?></h3>
                <p class="price"><?= number_format($product['price'], 2) ?> €</p>
                <p>En stock : <?= $product['stock'] ?></p>
                <a href="product.php?id=<?= $product['id'] ?>" class="btn">Voir le produit</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun produit trouvé.</p>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>