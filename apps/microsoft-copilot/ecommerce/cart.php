<?php
require_once __DIR__ . '/header.php';

$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mise à jour quantités / suppression
    foreach ($_POST['qty'] as $product_id => $qty) {
        $product_id = (int)$product_id;
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($cart[$product_id]);
        } else {
            $cart[$product_id] = $qty;
        }
    }
    $_SESSION['cart'] = $cart;
}

$items = [];
$total = 0.0;

if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($row = $stmt->fetch()) {
        $row['quantity'] = $cart[$row['id']];
        $row['line_total'] = $row['quantity'] * $row['price'];
        $total += $row['line_total'];
        $items[] = $row;
    }
}
?>
<h2>Panier</h2>

<?php if (!$items): ?>
    <p>Votre panier est vide.</p>
<?php else: ?>
    <form method="post">
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
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($item['price'], 2, ',', ' ') ?> €</td>
                    <td>
                        <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="0">
                    </td>
                    <td><?= number_format($item['line_total'], 2, ',', ' ') ?> €</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cart-total"><strong>Total :</strong> <?= number_format($total, 2, ',', ' ') ?> €</p>
        <button type="submit">Mettre à jour le panier</button>
        <a class="btn" href="checkout.php">Passer la commande</a>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
