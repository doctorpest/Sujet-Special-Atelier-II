<?php require_once 'includes/header.php';

$cart = getCart();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ajouter au panier (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    verifyCsrfToken($_POST['csrf_token']);
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    if ($quantity < 1) $quantity = 1;

    // Vérifier le stock
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();

    if ($stock !== false) {
        if ($quantity > $stock) {
            $quantity = $stock;
            echo '<p class="alert alert-error">Quantité demandée supérieure au stock. Ajusté à ' . $stock . '.</p>';
        }
        // Ajouter ou mettre à jour
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
            if ($cart[$productId] > $stock) $cart[$productId] = $stock;
        } else {
            $cart[$productId] = $quantity;
        }
        $_SESSION['cart'] = $cart;
    }
    redirect('cart.php');
}

// Mise à jour quantité (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    verifyCsrfToken($_POST['csrf_token']);
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    if ($quantity < 1) {
        unset($cart[$productId]);
    } else {
        // Vérifier stock max
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $stock = $stmt->fetchColumn();
        if ($stock !== false && $quantity > $stock) {
            $quantity = $stock;
        }
        $cart[$productId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
    redirect('cart.php');
}

// Supprimer (GET)
if ($action === 'remove' && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
    redirect('cart.php');
}
?>

<h1>Votre panier</h1>

<?php if (empty($cart)): ?>
    <p>Votre panier est vide.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Produit</th>
            <th>Prix unitaire</th>
            <th>Quantité</th>
            <th>Sous-total</th>
            <th>Action</th>
        </tr>
        <?php
        $total = 0;
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $products = [];
        while ($product = $stmt->fetch()) {
            $products[$product['id']] = $product;
        }
        foreach ($cart as $pid => $qty):
            if (!isset($products[$pid])) continue;
            $product = $products[$pid];
            $subtotal = $product['price'] * $qty;
            $total += $subtotal;
        ?>
        <tr>
            <td><?= h($product['name']) ?></td>
            <td><?= number_format($product['price'], 2) ?> €</td>
            <td>
                <form method="POST" action="cart.php" style="display:inline;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="number" name="quantity" value="<?= $qty ?>" min="1" max="<?= $product['stock'] ?>" style="width:70px;">
                    <button type="submit" class="btn">Mettre à jour</button>
                </form>
            </td>
            <td><?= number_format($subtotal, 2) ?> €</td>
            <td><a href="cart.php?action=remove&id=<?= $pid ?>" class="btn btn-danger">Supprimer</a></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3"><strong>Total</strong></td>
            <td colspan="2"><strong><?= number_format($total, 2) ?> €</strong></td>
        </tr>
    </table>

    <?php if (isLoggedIn()): ?>
        <a href="checkout.php" class="btn">Passer la commande</a>
    <?php else: ?>
        <p>Veuillez vous <a href="login.php">connecter</a> pour passer commande.</p>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>