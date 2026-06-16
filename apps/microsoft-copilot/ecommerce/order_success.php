<?php
require_once __DIR__ . '/header.php';

if (!is_logged_in()) {
    redirect(BASE_URL . '/login.php');
}

$order_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid");
$stmt->execute([
    ':id' => $order_id,
    ':uid' => $_SESSION['user']['id']
]);
$order = $stmt->fetch();
?>
<h2>Commande confirmée</h2>
<?php if ($order): ?>
    <p>Merci pour votre commande n° <?= $order['id'] ?>.</p>
    <p>Total : <?= number_format($order['total'], 2, ',', ' ') ?> €</p>
    <p>Adresse de livraison :</p>
    <pre><?= htmlspecialchars($order['shipping_address']) ?></pre>
<?php else: ?>
    <p>Commande introuvable.</p>
<?php endif; ?>

<a href="account_orders.php">Voir mes commandes</a>

<?php require_once __DIR__ . '/footer.php'; ?>
