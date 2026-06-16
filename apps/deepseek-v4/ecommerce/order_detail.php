<?php
require_once 'includes/header.php';
requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<p class="alert alert-error">Commande introuvable.</p>';
    require_once 'includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();
?>

<h1>Détail de la commande n°<?= $order['id'] ?></h1>
<p>Date : <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
<p>Total : <?= number_format($order['total'], 2) ?> €</p>
<p>Statut : <?= h($order['status']) ?></p>
<p>Adresse : <?= nl2br(h($order['shipping_address'])) ?></p>

<table>
    <tr><th>Produit</th><th>Quantité</th><th>Prix unitaire</th></tr>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><?= h($item['name']) ?></td>
        <td><?= $item['quantity'] ?></td>
        <td><?= number_format($item['price'], 2) ?> €</td>
    </tr>
    <?php endforeach; ?>
</table>

<a href="account.php" class="btn">Retour aux commandes</a>

<?php require_once 'includes/footer.php'; ?>