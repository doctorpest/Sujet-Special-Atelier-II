<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer le terme de recherche
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
if (empty($searchTerm)) {
    redirect('products.php');
}

// Récupérer les produits correspondant à la recherche
$products = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.name LIKE ? OR p.description LIKE ?
    ORDER BY p.name
", ["%$searchTerm%", "%$searchTerm%"]);

$pageTitle = "Résultats de recherche pour \"$searchTerm\" - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Résultats de recherche pour "<?php echo htmlspecialchars($searchTerm); ?>"</h1>

    <?php if ($products->num_rows > 0): ?>
        <div class="products-grid">
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
        </div>
    <?php else: ?>
        <p>Aucun produit trouvé pour "<?php echo htmlspecialchars($searchTerm); ?>".</p>
        <p><a href="products.php">Retour à tous les produits</a></p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>