<?php
require 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>
<h2>Mon Historique de Commandes</h2>
<?php if (empty($orders)): ?>
    <p>Vous n'avez pas encore passé de commande.</p>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3>Commande n°<?= $order['id'] ?> <small style="float:right; font-size:1rem; color:#7f8c8d;"><?= $order['created_at'] ?></small></h3>
            <p><strong>Statut :</strong> Paid (Simulé)</p>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
            
            <table class="table" style="box-shadow: none; margin-top: 10px;">
                <thead>
                    <tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $stmt_items->execute([$order['id']]);
                    $items = $stmt_items->fetchAll();
                    foreach ($items as $item):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= number_format($item['price_at_time'], 2) ?> €</td>
                        <td><?= $item['quantity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h4 style="text-align:right; margin-top:10px;">Montant Total : <?= number_format($order['total_price'], 2) ?> €</h4>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>