<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer le produit
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $db->query("
    SELECT p.*, c.name as category_name, c.description as category_description
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
", [$productId]);

if ($product->num_rows === 0) {
    redirect('products.php');
}

$product = $product->fetch_assoc();

// Récupérer d'autres produits de la même catégorie
$relatedProducts = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.category_id = ? AND p.product_id != ?
    ORDER BY RAND()
    LIMIT 4
", [$product['category_id'], $productId]);

$pageTitle = htmlspecialchars($product['name']) . " - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="product-detail">
        <div class="product-detail-image">
            <img src="<?php echo UPLOADS_URL . ($product['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <div class="product-detail-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="product-detail-price"><?php echo formatPrice($product['price']); ?></div>

            <div style="margin-bottom: 1rem;">
                <span class="stock <?php echo $product['stock_quantity'] < 10 ? 'low' : ''; ?>">
                    Stock: <?php echo $product['stock_quantity']; ?> disponible<?php echo $product['stock_quantity'] > 1 ? 's' : ''; ?>
                </span>
            </div>

            <div style="margin-bottom: 1rem;">
                <strong>Catégorie:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Non classé'); ?>
            </div>

            <div class="product-detail-description">
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <form class="add-to-cart-form" method="post">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <div class="add-to-cart">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                    <button type="submit" class="btn btn-lg">Ajouter au panier</button>
                </div>
            </form>
        </div>
    </div>

    <h2>Produits similaires</h2>
    <div class="products-grid">
        <?php while ($relatedProduct = $relatedProducts->fetch_assoc()): ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="product.php?id=<?php echo $relatedProduct['product_id']; ?>">
                        <img src="<?php echo UPLOADS_URL . ($relatedProduct['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                    </a>
                </div>
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product.php?id=<?php echo $relatedProduct['product_id']; ?>">
                            <?php echo htmlspecialchars($relatedProduct['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price"><?php echo formatPrice($relatedProduct['price']); ?></div>
                    <div class="product-actions">
                        <form class="add-to-cart-form" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $relatedProduct['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-sm">Ajouter au panier</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>