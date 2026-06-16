<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer les catégories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Récupérer tous les produits avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filtres
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$sql = "SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE 1=1";
$params = [];

if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($minPrice !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $maxPrice;
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$products = $db->query($sql, $params);

// Compter le nombre total de produits pour la pagination
$countSql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
$countParams = [];

if ($categoryId) {
    $countSql .= " AND p.category_id = ?";
    $countParams[] = $categoryId;
}

if ($minPrice !== null) {
    $countSql .= " AND p.price >= ?";
    $countParams[] = $minPrice;
}

if ($maxPrice !== null) {
    $countSql .= " AND p.price <= ?";
    $countParams[] = $maxPrice;
}

if ($search) {
    $countSql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$search%";
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
}

$countResult = $db->query($countSql, $countParams);
$totalProducts = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

$pageTitle = "Nos Produits - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Nos Produits</h1>

    <!-- Barre de recherche -->
    <form method="get" action="products.php" class="search-bar">
        <input type="text" name="search" placeholder="Rechercher un produit..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
        <select name="category">
            <option value="">Toutes les catégories</option>
            <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?php echo $category['category_id']; ?>" <?php echo ($categoryId === $category['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn">Rechercher</button>
    </form>

    <!-- Filtres -->
    <div class="filters">
        <h3>Filtres</h3>
        <form method="get" action="products.php">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <input type="hidden" name="category" value="<?php echo $categoryId ?? ''; ?>">

            <div class="filter-group">
                <label for="min_price">Prix minimum (€)</label>
                <input type="number" name="min_price" id="min_price" min="0" step="0.01" value="<?php echo $minPrice ?? ''; ?>">
            </div>

            <div class="filter-group">
                <label for="max_price">Prix maximum (€)</label>
                <input type="number" name="max_price" id="max_price" min="0" step="0.01" value="<?php echo $maxPrice ?? ''; ?>">
            </div>

            <button type="submit" class="btn btn-sm">Appliquer les filtres</button>
            <a href="products.php" class="btn btn-sm btn-secondary">Réinitialiser</a>
        </form>
    </div>

    <!-- Produits -->
    <div class="products-grid">
        <?php if ($products->num_rows > 0): ?>
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product.php?id=<?php echo $product['product_id']; ?>">
                            <img src="<?php echo UPLOADS_URL . ($product['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product.php?id=<?php echo $product['product_id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-actions">
                            <span class="stock <?php echo $product['stock_quantity'] < 10 ? 'low' : ''; ?>">
                                Stock: <?php echo $product['stock_quantity']; ?>
                            </span>
                            <form class="add-to-cart-form" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-sm">Ajouter au panier</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Aucun produit trouvé.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin: 2rem 0;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $categoryId ?? ''; ?>&min_price=<?php echo $minPrice ?? ''; ?>&max_price=<?php echo $maxPrice ?? ''; ?>" class="btn btn-sm">Précédent</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $categoryId ?? ''; ?>&min_price=<?php echo $minPrice ?? ''; ?>&max_price=<?php echo $maxPrice ?? ''; ?>"
                   class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $categoryId ?? ''; ?>&min_price=<?php echo $minPrice ?? ''; ?>&max_price=<?php echo $maxPrice ?? ''; ?>" class="btn btn-sm">Suivant</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>