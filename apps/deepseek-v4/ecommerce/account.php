<?php
require_once 'includes/header.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<h1>Mon compte</h1>
<h2>Historique des commandes</h2>

<?php if (count($orders) > 0): ?>
    <table>
        <tr>
            <th>N° commande</th>
            <th>Date</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Détail</th>
        </tr>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= $order['id'] ?></td>
            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
            <td><?= number_format($order['total'], 2) ?> €</td>
            <td><?= h($order['status']) ?></td>
            <td><a href="order_detail.php?id=<?= $order['id'] ?>" class="btn">Voir</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Vous n'avez pas encore passé de commande.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>