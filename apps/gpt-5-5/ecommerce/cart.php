<?php
require_once 'config/db.php';
require_once 'config/helpers.php';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if ($product && $product['stock'] > 0) {
        $_SESSION['cart'][$productId] = min($product['stock'], ($_SESSION['cart'][$productId] ?? 0) + $quantity);
        $_SESSION['flash'] = 'Produit ajouté au panier.';
    }
    header('Location: cart.php'); exit;
}

if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header('Location: cart.php'); exit;
}

$items = [];
$total = 0;
if ($_SESSION['cart']) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $line = $qty * $p['price'];
        $total += $line;
        $items[] = ['product' => $p, 'qty' => $qty, 'line' => $line];
    }
}
include 'header.php';
?>
<h1>Panier</h1>
<?php if (!$items): ?>
<p>Ton panier est vide.</p>
<?php else: ?>
<table>
<tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th><th></th></tr>
<?php foreach ($items as $item): $p = $item['product']; ?>
<tr>
    <td><?= e($p['name']) ?></td>
    <td><?= (int)$item['qty'] ?></td>
    <td><?= number_format($p['price'], 2, ',', ' ') ?> €</td>
    <td><?= number_format($item['line'], 2, ',', ' ') ?> €</td>
    <td><a href="cart.php?remove=<?= $p['id'] ?>">Retirer</a></td>
</tr>
<?php endforeach; ?>
</table>
<h2>Total : <?= number_format($total, 2, ',', ' ') ?> €</h2>
<a class="button" href="checkout.php">Passer commande</a>
<?php endif; ?>
<?php include 'footer.php'; ?>
