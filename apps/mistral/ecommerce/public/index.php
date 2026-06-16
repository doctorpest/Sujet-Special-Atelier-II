<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer les produits en vedette (limité à 6)
$featuredProducts = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
    LIMIT 6
");

$pageTitle = "Accueil - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Bienvenue sur notre boutique en ligne</h1>
    <p>Découvrez nos produits les plus populaires.</p>

    <h2>Produits en vedette</h2>
    <div class="products-grid">
        <?php while ($product = $featuredProducts->fetch_assoc()): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?php echo UPLOADS_URL . ($product['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
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

    <div style="text-align: center; margin: 2rem 0;">
        <a href="products.php" class="btn">Voir tous les produits</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>