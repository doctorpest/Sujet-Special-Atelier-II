<?php
require_once __DIR__ . '/header.php';

if (!is_logged_in()) {
    redirect(BASE_URL . '/login.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $_SESSION['user']['id']]);
$orders = $stmt->fetchAll();
?>
<h2>Mes commandes</h2>

<?php if (!$orders): ?>
    <p>Vous n'avez pas encore passé de commande.</p>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <h3>Commande n° <?= $order['id'] ?> — <?= number_format($order['total'], 2, ',', ' ') ?> €</h3>
            <p>Date : <?= htmlspecialchars($order['created_at']) ?></p>
            <p>Adresse :</p>
            <pre><?= htmlspecialchars($order['shipping_address']) ?></pre>

            <?php
            $stmtItems = $pdo->prepare("SELECT oi.*, p.name
                                        FROM order_items oi
                                        JOIN products p ON oi.product_id = p.id
                                        WHERE oi.order_id = :oid");
            $stmtItems->execute([':oid' => $order['id']]);
            $items = $stmtItems->fetchAll();
            ?>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> — <?= number_format($item['unit_price'], 2, ',', ' ') ?> €</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
