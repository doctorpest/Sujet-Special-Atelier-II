<?php
require_once __DIR__ . '/header.php';

if (!is_logged_in()) {
    redirect(BASE_URL . '/login.php');
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    echo "<p>Votre panier est vide.</p>";
    require_once __DIR__ . '/footer.php';
    exit;
}

$items = [];
$total = 0.0;
$ids = implode(',', array_map('intval', array_keys($cart)));
$stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
while ($row = $stmt->fetch()) {
    $qty = $cart[$row['id']];
    $row['quantity'] = $qty;
    $row['line_total'] = $qty * $row['price'];
    $total += $row['line_total'];
    $items[] = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $card_exp = trim($_POST['card_exp'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');

    if ($address === '' || $card_number === '' || $card_name === '' || $card_exp === '' || $card_cvv === '') {
        $errors[] = "Tous les champs sont obligatoires.";
    }

    // Paiement simulé : on ne fait que valider la présence des champs
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, shipping_address) VALUES (:uid, :total, :addr)");
            $stmt->execute([
                ':uid' => $_SESSION['user']['id'],
                ':total' => $total,
                ':addr' => $address
            ]);
            $order_id = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                                       VALUES (:oid, :pid, :qty, :price)");
            $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

            foreach ($items as $item) {
                $stmtItem->execute([
                    ':oid' => $order_id,
                    ':pid' => $item['id'],
                    ':qty' => $item['quantity'],
                    ':price' => $item['price']
                ]);
                $stmtStock->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['id']
                ]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            redirect(BASE_URL . '/order_success.php?id=' . $order_id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Erreur lors de la création de la commande.";
        }
    }
}
?>
<h2>Passer la commande</h2>

<?php foreach ($errors as $e): ?>
    <p class="error"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<h3>Récapitulatif</h3>
<ul>
    <?php foreach ($items as $item): ?>
        <li><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> — <?= number_format($item['line_total'], 2, ',', ' ') ?> €</li>
    <?php endforeach; ?>
</ul>
<p><strong>Total :</strong> <?= number_format($total, 2, ',', ' ') ?> €</p>

<h3>Adresse de livraison</h3>
<form method="post" class="checkout-form">
    <label>Adresse complète :
        <textarea name="address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
    </label>

    <h3>Paiement (simulé)</h3>
    <label>Numéro de carte :
        <input type="text" name="card_number" required>
    </label>
    <label>Nom sur la carte :
        <input type="text" name="card_name" required>
    </label>
    <label>Expiration (MM/AA) :
        <input type="text" name="card_exp" required>
    </label>
    <label>CVV :
        <input type="text" name="card_cvv" required>
    </label>

    <button type="submit">Confirmer la commande</button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
