<?php
require_once 'config/db.php';
require_once 'config/helpers.php';
require_login();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user']['id']]);
$orders = $stmt->fetchAll();
include 'header.php';
?>
<h1>Mes commandes</h1>
<?php foreach ($orders as $order): ?>
<section class="order">
    <h2>Commande #<?= $order['id'] ?> — <?= number_format($order['total'], 2, ',', ' ') ?> €</h2>
    <p><?= e($order['created_at']) ?> — Statut : <?= e($order['status']) ?> — Carte : **** <?= e($order['payment_last4']) ?></p>
    <p>Livraison : <?= nl2br(e($order['shipping_address'])) ?></p>
    <?php
    $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$order['id']]);
    ?>
    <ul>
        <?php foreach ($items as $item): ?>
            <li><?= e($item['product_name']) ?> x <?= (int)$item['quantity'] ?> — <?= number_format($item['price'], 2, ',', ' ') ?> €</li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endforeach; ?>
<?php if (!$orders): ?><p>Aucune commande.</p><?php endif; ?>
<?php include 'footer.php'; ?>
