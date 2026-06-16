<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/database.php';

// Gestion des actions du panier
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    switch ($action) {
        case 'add':
            $result = addToCart($productId, $quantity);
            break;
        case 'update':
            $result = updateCartQuantity($productId, $quantity);
            break;
        case 'remove':
            $result = removeFromCart($productId);
            break;
        case 'clear':
            $result = clearCart();
            break;
        default:
            $result = ['success' => false, 'message' => 'Action invalide.'];
    }

    echo json_encode($result);
    exit();
}

// Compter les articles dans le panier
if ($action === 'count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => getCartItemCount()]);
    exit();
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();

$pageTitle = "Mon Panier - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Mon Panier</h1>

    <?php if (empty($cartItems)): ?>
        <p>Votre panier est vide.</p>
        <p><a href="products.php" class="btn">Continuer vos achats</a></p>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="<?php echo UPLOADS_URL . ($item['image_path'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-image">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td>
                            <form class="update-quantity-form" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <div class="cart-quantity">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                    <button type="submit" class="btn btn-sm">Mettre à jour</button>
                                </div>
                            </form>
                        </td>
                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        <td>
                            <form class="remove-from-cart-form" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-error">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td colspan="2" class="cart-total"><?php echo formatPrice($cartTotal); ?></td>
                </tr>
            </tfoot>
        </table>

        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
            <a href="products.php" class="btn btn-secondary">Continuer vos achats</a>
            <form method="post" action="cart.php?action=clear">
                <button type="submit" class="btn btn-error">Vider le panier</button>
            </form>
            <a href="checkout.php" class="btn">Passer à la caisse</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>