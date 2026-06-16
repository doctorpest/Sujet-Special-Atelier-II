<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('../public/login.php');
}

// Récupérer les commandes avec pagination
\$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;
\$limit = 10;
\$offset = (\$page - 1) * \$limit;

\$orders = \$db->query("
    SELECT o.*, u.username, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
", [\$limit, \$offset]);

// Compter le nombre total de commandes
\$totalOrders = \$db->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
\$totalPages = ceil(\$totalOrders / \$limit);

// Mettre à jour le statut d'une commande
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['update_status'])) {
    \$orderId = (int)(\$_POST['order_id'] ?? 0);
    \$status = trim(\$_POST['status'] ?? '');

    if (!empty(\$orderId) && !empty(\$status)) {
        \$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array(\$status, \$validStatuses)) {
            \$db->execute("UPDATE orders SET status = ? WHERE order_id = ?", [\$status, \$orderId]);
            setMessage('Statut de la commande mis à jour.', MSG_SUCCESS);
            redirect('orders.php');
        }
    }
}

\$pageTitle = "Gestion des commandes - Administration";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <h3>Menu Admin</h3>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="products.php">Produits</a></li>
                <li><a href="orders.php" class="active">Commandes</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <h1>Gestion des commandes</h1>

            <?php if (\$orders->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while (\$order = \$orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo \$order['order_id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(\$order['first_name'] . ' ' . \$order['last_name']); ?><br>
                                        <small><?php echo htmlspecialchars(\$order['username']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime(\$order['order_date'])); ?></td>
                                    <td><?php echo formatPrice(\$order['total_amount']); ?></td>
                                    <td>
                                        <form method="post" style="display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="hidden" name="order_id" value="<?php echo \$order['order_id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 0.25rem; border-radius: 4px;">
                                                <option value="pending" <?php echo \$order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                                <option value="processing" <?php echo \$order['status'] === 'processing' ? 'selected' : ''; ?>>En cours</option>
                                                <option value="shipped" <?php echo \$order['status'] === 'shipped' ? 'selected' : ''; ?>>Expédiée