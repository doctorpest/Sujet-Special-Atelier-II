<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les commandes de l'utilisateur
$orders = $db->query(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC",
    [getUserId()]
);

$pageTitle = "Historique des commandes - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Mon historique de commandes</h1>

    <?php if ($orders->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Méthode de paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
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
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">Voir les détails</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Vous n'avez pas encore passé de commande.</p>
        <p><a href="products.php" class="btn">Découvrir nos produits</a></p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>