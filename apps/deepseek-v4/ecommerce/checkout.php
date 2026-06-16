<?php
require_once 'includes/header.php';
requireLogin();

$cart = getCart();
if (empty($cart)) {
    redirect('cart.php');
}

// Vérification stocks
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($cart));
$products = $stmt->fetchAll();
$error = '';
$total = 0;
$items = [];

foreach ($products as $product) {
    $qty = $cart[$product['id']];
    if ($qty > $product['stock']) {
        $error .= "Stock insuffisant pour {$product['name']}. ";
        $qty = $product['stock'];
        $cart[$product['id']] = $qty;
        $_SESSION['cart'] = $cart;
    }
    if ($qty < 1) continue;
    $items[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $qty,
        'subtotal' => $product['price'] * $qty
    ];
    $total += $product['price'] * $qty;
}

if ($error) {
    echo '<p class="alert alert-error">' . h($error) . '</p>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    $address = trim($_POST['address']);
    $card_number = trim($_POST['card_number']);
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);

    // Validation basique
    if (empty($address)) {
        $error = 'Adresse de livraison obligatoire.';
    } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $card_number))) {
        $error = 'Numéro de carte invalide (16 chiffres).';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $error = 'Date d\'expiration invalide (MM/AA).';
    } elseif (!preg_match('/^\d{3}$/', $cvv)) {
        $error = 'CVV invalide (3 chiffres).';
    } else {
        // Simulation de paiement réussi
        $pdo->beginTransaction();
        try {
            // Insérer la commande
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, shipping_address) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $address]);
            $orderId = $pdo->lastInsertId();

            // Insérer les articles et décrémenter les stocks
            foreach ($items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            $pdo->commit();
            clearCart();
            redirect('order_confirmation.php?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Erreur lors de la commande. Veuillez réessayer.';
        }
    }
}

// Récupérer l'adresse par défaut de l'utilisateur
$userStmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
$defaultAddress = $user['address'] ?? '';
?>

<h1>Passer la commande</h1>

<?php if (!empty($error)): ?>
    <p class="alert alert-error"><?= h($error) ?></p>
<?php endif; ?>

<div class="order-summary">
    <h2>Récapitulatif</h2>
    <table>
        <tr><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th></tr>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= h($item['name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= number_format($item['price'], 2) ?> €</td>
            <td><?= number_format($item['subtotal'], 2) ?> €</td>
        </tr>
        <?php endforeach; ?>
        <tr><td colspan="3"><strong>Total</strong></td><td><strong><?= number_format($total, 2) ?> €</strong></td></tr>
    </table>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <label>Adresse de livraison :</label>
    <textarea name="address" required><?= h($defaultAddress) ?></textarea>

    <h2>Paiement simulé</h2>
    <label>Numéro de carte (16 chiffres) :</label>
    <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>

    <label>Date d'expiration (MM/AA) :</label>
    <input type="text" name="expiry" placeholder="12/26" required>

    <label>CVV (3 chiffres) :</label>
    <input type="text" name="cvv" placeholder="123" required>

    <button type="submit" class="btn">Payer <?= number_format($total, 2) ?> €</button>
</form>

<?php require_once 'includes/footer.php'; ?>