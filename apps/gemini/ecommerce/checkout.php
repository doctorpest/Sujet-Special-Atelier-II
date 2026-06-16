<?php
require 'config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (empty($_SESSION['cart'])) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Erreur CSRF."); }
    
    $address = trim($_POST['shipping_address']);
    
    if (!empty($address)) {
        try {
            $pdo->beginTransaction();

            // Calcul du total d'après la base de données
            $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute(array_keys($_SESSION['cart']));
            $products = $stmt->fetchAll();

            $total = 0;
            foreach ($products as $p) {
                $qty = $_SESSION['cart'][$p['id']];
                if ($p['stock'] < $qty) {
                    throw new Exception("Le stock pour le produit " . $p['name'] . " est insuffisant.");
                }
                $total += $p['price'] * $qty;
            }

            // Insertion commande
            $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_price, shipping_address) VALUES (?, ?, ?)");
            $stmt_order->execute([$_SESSION['user_id'], $total, $address]);
            $order_id = $pdo->lastInsertId();

            // Insertion articles et mise à jour stock
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
            $stmt_update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($products as $p) {
                $qty = $_SESSION['cart'][$p['id']];
                $stmt_item->execute([$order_id, $p['id'], $qty, $p['price']]);
                $stmt_update_stock->execute([$qty, $p['id']]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);
            $success = "Votre commande n°$order_id a été payée avec succès !";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = "L'adresse de livraison est requise.";
    }
}
include 'includes/header.php';
?>
<h2>Passer la commande</h2>
<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <a href="orders_history.php" class="btn">Voir mon historique de commandes</a>
<?php else: ?>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST" style="max-width: 600px; background: white; padding: 20px; border-radius: 8px;">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <div class="form-group">
            <label>Adresse Complète de Livraison</label>
            <textarea name="shipping_address" required class="form-control" rows="4"></textarea>
        </div>
        <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <legend>Paiement Sécurisé (Simulation)</legend>
            <div class="form-group">
                <label>Numéro de Carte Numéraire</label>
                <input type="text" placeholder="4532 ...." required class="form-control">
            </div>
            <div style="display:flex; gap:10px;">
                <input type="text" placeholder="MM/AA" required class="form-control">
                <input type="text" placeholder="CVC" required class="form-control">
            </div>
        </fieldset>
        <button type="submit" class="btn">Confirmer le paiement simulé</button>
    </form>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>