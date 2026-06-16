<?php
require_once 'includes/header.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('account.php');
}
$orderId = (int)$_GET['id'];

// Vérifier que la commande appartient à l'utilisateur
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

<h1>Commande confirmée</h1>
<p>Merci pour votre commande n° <?= $order['id'] ?>.</p>
<p>Statut : <?= h($order['status']) ?></p>
<p>Total : <?= number_format($order['total'], 2) ?> €</p>
<p>Adresse de livraison : <?= nl2br(h($order['shipping_address'])) ?></p>

<h2>Détails</h2>
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

<a href="index.php" class="btn">Retour à l'accueil</a>

<?php require_once 'includes/footer.php'; ?>