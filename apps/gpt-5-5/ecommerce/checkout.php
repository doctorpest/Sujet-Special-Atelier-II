<?php
require_once 'config/db.php';
require_once 'config/helpers.php';
require_login();
$error = '';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
if (!$_SESSION['cart']) { header('Location: cart.php'); exit; }

function current_cart($pdo) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $card = preg_replace('/\D/', '', $_POST['card'] ?? '');
    if (!$address || strlen($card) < 12) {
        $error = 'Adresse et carte simulée valides requises.';
    } else {
        $pdo->beginTransaction();
        try {
            $products = current_cart($pdo);
            $total = 0;
            foreach ($products as $p) {
                $qty = $_SESSION['cart'][$p['id']];
                if ($qty > $p['stock']) throw new Exception('Stock insuffisant pour ' . $p['name']);
                $total += $qty * $p['price'];
            }
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, shipping_address, total, payment_last4) VALUES (?, ?, ?, ?)');
            $stmt->execute([$_SESSION['user']['id'], $address, $total, substr($card, -4)]);
            $orderId = $pdo->lastInsertId();
            foreach ($products as $p) {
                $qty = $_SESSION['cart'][$p['id']];
                $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$orderId, $p['id'], $p['name'], $p['price'], $qty]);
                $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$qty, $p['id']]);
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            $_SESSION['flash'] = 'Commande validée.';
            header('Location: orders.php'); exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
include 'header.php';
?>
<h1>Paiement simulé</h1>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form class="form" method="post">
    <label>Adresse de livraison <textarea name="address" required></textarea></label>
    <label>Numéro de carte simulé <input name="card" placeholder="4242 4242 4242 4242" required></label>
    <label>Expiration <input placeholder="12/30" required></label>
    <label>CVV <input placeholder="123" required></label>
    <button>Valider la commande</button>
</form>
<?php include 'footer.php'; ?>
