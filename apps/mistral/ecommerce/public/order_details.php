<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer l'ID de la commande
\$orderId = isset(\$_GET['id']) ? (int)\$_GET['id'] : 0;

// Vérifier que la commande appartient à l'utilisateur
\$order = \$db->query(
    "SELECT * FROM orders WHERE order_id = ? AND user_id = ?",
    [\$orderId, getUserId()]
);

if (\$order->num_rows === 0) {
    redirect('order_history.php');
}

\$order = \$order->fetch_assoc();

// Récupérer les articles de la commande
\$orderItems = \$db->query(
    "SELECT oi.*, p.name as product_name, p.image_path, p.price as current_price
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?",
    [\$orderId]
);

\$pageTitle = "Détails de la commande #\$orderId - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Détails de la commande #<?php echo \$order['order_id']; ?></h1>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <h2>Informations de la commande</h2>
            <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
            <p><strong>Statut:</strong>
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
            </p>
            <p><strong>Montant total:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
            <p><strong>Méthode de paiement:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        </div>

        <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <h2>Adresse de livraison</h2>
            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            <p><?php echo htmlspecialchars($order['shipping_postal_code']); ?> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
            <p><?php echo htmlspecialchars($order['shipping_country']); ?></p>
        </div>
    </div>

    <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <h2>Articles commandés</h2>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="<?php echo UPLOADS_URL . ($item['image_path'] ?? 'default.jpg'); ?>"
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="order_history.php" class="btn btn-secondary">Retour à l'historique</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>