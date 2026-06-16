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
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Vérifier que la commande appartient à l'utilisateur
$order = $db->query(
    "SELECT * FROM orders WHERE order_id = ? AND user_id = ?",
    [$orderId, getUserId()]
);

if ($order->num_rows === 0) {
    redirect('order_history.php');
}

$order = $order->fetch_assoc();

// Récupérer les articles de la commande
$orderItems = $db->query(
    "SELECT oi.*, p.name as product_name, p.image_path
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?",
    [$orderId]
);

$pageTitle = "Confirmation de commande - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-check-circle" style="font-size: 4rem; color: #2ecc71; margin-bottom: 1rem;"></i>
        <h1>Merci pour votre commande !</h1>
        <p>Votre commande (n°<?php echo $order['order_id']; ?>) a été passée avec succès.</p>
    </div>

    <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 2rem;">
        <h2>Détails de la commande</h2>
        <p><strong>Numéro de commande:</strong> <?php echo $order['order_id']; ?></p>
        <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
        <p><strong>Montant total:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
        <p><strong>Méthode de paiement:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
    </div>

    <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 2rem;">
        <h2>Adresse de livraison</h2>
        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        <p><?php echo htmlspecialchars($order['shipping_postal_code']); ?> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
        <p><?php echo htmlspecialchars($order['shipping_country']); ?></p>
    </div>

    <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <h2>Articles commandés</h2>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="<?php echo UPLOADS_URL . ($item['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
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
        <a href="order_history.php" class="btn">Voir mes commandes</a>
        <a href="products.php" class="btn btn-secondary">Continuer mes achats</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>