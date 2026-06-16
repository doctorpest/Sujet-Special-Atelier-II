<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('../public/login.php');
}

// Récupérer les catégories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Récupérer les produits avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$products = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
", [$limit, $offset]);

// Compter le nombre total de produits
$totalProducts = $db->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

$pageTitle = "Gestion des produits - Administration";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <h3>Menu Admin</h3>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="products.php" class="active">Produits</a></li>
                <li><a href="orders.php">Commandes</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1>Gestion des produits</h1>
                <a href="add_product.php" class="btn">Ajouter un produit</a>
            </div>

            <?php if ($products->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td>
                                        <img src="<?php echo UPLOADS_URL . ($product['image_path'] ?? 'default.jpg'); ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Non classé'); ?></td>
                                    <td><?php echo formatPrice($product['price']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $product['stock_quantity'] < 10 ? '#e74c3c' : '#2ecc71'; ?>;">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                    <td class="admin-actions">
                                        <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm">Modifier</a>
                                        <a href="delete_product.php?id=<?php echo $product['product_id']; ?>"
                                           class="btn btn-sm btn-error"
                                           onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                                            Supprimer
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-sm">Précédent</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>"
                               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-sm">Suivant</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Aucun produit trouvé.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>