<?php
require 'config/database.php';

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Erreur CSRF."); }
    
    $id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Vérifier le stock réel
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if ($prod) {
        if ($action === 'add') {
            $current_in_cart = $_SESSION['cart'][$id] ?? 0;
            if ($current_in_cart < $prod['stock']) {
                $_SESSION['cart'][$id] = $current_in_cart + 1;
            }
        } elseif ($action === 'remove') {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]--;
                if ($_SESSION['cart'][$id] <= 0) { unset($_SESSION['cart'][$id]); }
            }
        }
    }
    header("Location: cart.php");
    exit;
}

$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $subtotal = $p['price'] * $qty;
        $total += $subtotal;
        $p['quantity'] = $qty;
        $p['subtotal'] = $subtotal;
        $cart_items[] = $p;
    }
}
include 'includes/header.php';
?>
<h2>Votre Panier</h2>
<?php if (empty($cart_items)): ?>
    <p>Le panier est vide.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Produit</th><th>Prix</th><th>Quantité</th><th>Sous-total</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($item['price'], 2) ?> €</td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['subtotal'], 2) ?> €</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;">- 1</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3 style="text-align: right; margin-top: 20px;">Total Général : <?= number_format($total, 2) ?> €</h3>
    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        <a href="index.php" class="btn" style="background:#7f8c8d; max-width:200px;">Continuer les achats</a>
        <a href="checkout.php" class="btn" style="max-width:200px;">Passer la commande</a>
    </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>