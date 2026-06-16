<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('../public/login.php');
}

// Récupérer les statistiques
$totalProducts = $db->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$totalOrders = $db->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$totalUsers = $db->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalRevenue = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'")->fetch_assoc()['total'] ?? 0;

// Récupérer les dernières commandes
$recentOrders = $db->query("
    SELECT o.*, u.username, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
");

$pageTitle = "Tableau de bord - Administration";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Tableau de bord</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); text-align: center;">
            <i class="fas fa-box" style="font-size: 2rem; color: #3498db; margin-bottom: 0.5rem;"></i>
            <h3>Produits</h3>
            <p style="font-size: 1.5rem; font-weight: bold;"><?php echo $totalProducts; ?></p>
            <a href="products.php" class="btn btn-sm">Voir les produits</a>
        </div>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); text-align: center;">
            <i class="fas fa-shopping-cart" style="font-size: 2rem; color: #2ecc71; margin-bottom: 0.5rem;"></i>
            <h3>Commandes</h3>
            <p style="font-size: 1.5rem; font-weight: bold;"><?php echo $totalOrders; ?></p>
            <a href="orders.php" class="btn btn-sm">Voir les commandes</a>
        </div>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); text-align: center;">
            <i class="fas fa-users" style="font-size: 2rem; color: #f39c12; margin-bottom: 0.5rem;"></i>
            <h3>Utilisateurs</h3>
            <p style="font-size: 1.5rem; font-weight: bold;"><?php echo $totalUsers; ?></p>
        </div>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); text-align: center;">
            <i class="fas fa-dollar-sign" style="font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem;"></i>
            <h3>Revenus</h3>
            <p style="font-size: 1.5rem; font-weight: bold;"><?php echo formatPrice($totalRevenue); ?></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <h2>Dernières commandes</h2>
            <?php if ($recentOrders->num_rows > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.5rem; border-radius: 4px; background-color:
                                        <?php
                                        switch ($order['status']) {
                                            case 'pending': echo '#fff3cd'; break;
                                            case 'processing': echo '#cce5ff'; break;
                                            case 'shipped': echo '#d1ecf1'; break;
                                            case 'delivered': echo '#d4edda'; break;
                                            case 'cancelled': echo '#f8d7da'; break;
                                            default: echo 'transparent';
                                        }
                                        ?>;
                                        color:
                                        <?php
                                        switch ($order['status']) {
                                            case 'pending': echo '#856404'; break;
                                            case 'processing': echo '#004085'; break;
                                            case 'shipped': echo '#0c5460'; break;
                                            case 'delivered': echo '#155724'; break;
                                            case 'cancelled': echo '#721c24'; break;
                                            default: echo 'inherit';
                                        }
                                        ?>;">
                                        <?php
                                        switch ($order['status']) {
                                            case 'pending': echo 'En attente'; break;
                                            case 'processing': echo 'En cours'; break;
                                            case 'shipped': echo 'Expédiée'; break;
                                            case 'delivered': echo 'Livrée'; break;
                                            case 'cancelled': echo 'Annulée'; break;
                                            default: echo $order['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="orders.php" class="btn btn-sm" style="margin-top: 1rem;">Voir toutes les commandes</a>
            <?php else: ?>
                <p>Aucune commande récente.</p>
            <?php endif; ?>
        </div>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <h2>Produits les plus vendus</h2>
            <?php
            $topProducts = $db->query("
                SELECT p.product_id, p.name, p.image_path, SUM(oi.quantity) as total_sold
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                GROUP BY p.product_id
                ORDER BY total_sold DESC
                LIMIT 5
            ");
            ?>
            <?php if ($topProducts->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($product = $topProducts->fetch_assoc()): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; border-bottom: 1px solid #eee;">
                            <img src="<?php echo UPLOADS_URL . ($product['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <div style="flex: 1;">
                                <p style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($product['name']); ?></p>
                                <p style="margin: 0; color: #777;">Vendus: <?php echo $product['total_sold']; ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <a href="products.php" class="btn btn-sm" style="margin-top: 1rem;">Voir tous les produits</a>
            <?php else: ?>
                <p>Aucun produit vendu pour l'instant.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>