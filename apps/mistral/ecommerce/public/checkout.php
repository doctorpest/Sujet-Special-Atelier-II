<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si le panier est vide
if (empty(getCartItems())) {
    redirect('cart.php');
}

// Rediriger si non connecté
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = BASE_URL . 'public/checkout.php';
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById(getUserId());

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token CSRF invalide.', MSG_ERROR);
    } else {
        // Récupérer les données du formulaire
        $shippingAddress = trim($_POST['shipping_address'] ?? '');
        $shippingCity = trim($_POST['shipping_city'] ?? '');
        $shippingPostalCode = trim($_POST['shipping_postal_code'] ?? '');
        $shippingCountry = trim($_POST['shipping_country'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');

        // Validation
        if (empty($shippingAddress) || empty($shippingCity) || empty($shippingPostalCode) || empty($shippingCountry) || empty($paymentMethod)) {
            setMessage('Tous les champs sont obligatoires.', MSG_ERROR);
        } else {
            // Calculer le total
            $cartTotal = getCartTotal();
            $cartItems = getCartItems();

            // Créer la commande
            $orderId = $db->getLastInsertId();
            $db->execute(
                "INSERT INTO orders (user_id, total_amount, shipping_address, shipping_city, shipping_postal_code, shipping_country, payment_method)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [getUserId(), $cartTotal, $shippingAddress, $shippingCity, $shippingPostalCode, $shippingCountry, $paymentMethod]
            );
            $orderId = $db->getLastInsertId();

            if ($orderId) {
                // Ajouter les articles de la commande
                foreach ($cartItems as $item) {
                    $db->execute(
                        "INSERT INTO order_items (order_id, product_id, quantity, price)
                         VALUES (?, ?, ?, ?)",
                        [$orderId, $item['product_id'], $item['quantity'], $item['price']]
                    );

                    // Mettre à jour le stock
                    $db->execute(
                        "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?",
                        [$item['quantity'], $item['product_id']]
                    );
                }

                // Vider le panier
                clearCart();

                // Rediriger vers la confirmation de commande
                redirect('order_confirmation.php?order_id=' . $orderId);
            } else {
                setMessage('Erreur lors de la création de la commande.', MSG_ERROR);
            }
        }
    }
}

$pageTitle = "Paiement - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>Paiement</h1>

    <div class="checkout-form">
        <h2>Adresse de livraison</h2>

        <form method="post" action="checkout.php">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="checkout-section">
                <h3>Informations de livraison</h3>

                <div class="form-group">
                    <label for="shipping_address">Adresse</label>
                    <textarea id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shipping_city">Ville</label>
                    <input type="text" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="shipping_postal_code">Code postal</label>
                    <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="shipping_country">Pays</label>
                    <input type="text" id="shipping_country" name="shipping_country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="checkout-section">
                <h3>Méthode de paiement</h3>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" id="payment_credit_card" name="payment_method" value="credit_card" required>
                        <label for="payment_credit_card">Carte de crédit/débit</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="payment_paypal" name="payment_method" value="paypal">
                        <label for="payment_paypal">PayPal</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="payment_bank_transfer" name="payment_method" value="bank_transfer">
                        <label for="payment_bank_transfer">Virement bancaire</label>
                    </div>
                </div>

                <!-- Formulaire de carte de crédit (simulé) -->
                <div id="credit_card_form" style="display: none; margin-top: 1rem; padding: 1rem; background-color: #f9f9f9; border-radius: 4px;">
                    <h4>Informations de la carte</h4>
                    <div class="form-group">
                        <label for="card_number">Numéro de carte</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label for="card_expiry">Date d'expiration</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/AA">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="card_name">Nom sur la carte</label>
                        <input type="text" id="card_name" name="card_name" placeholder="Nom complet">
                    </div>
                </div>
            </div>

            <div class="checkout-section">
                <h3>Récapitulatif de la commande</h3>
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
                        <?php foreach (getCartItems() as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><?php echo formatPrice(getCartTotal()); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <a href="cart.php" class="btn btn-secondary">Retour au panier</a>
                <button type="submit" class="btn btn-lg">Passer la commande</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Afficher/Masquer le formulaire de carte de crédit
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardForm = document.getElementById('credit_card_form');

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                creditCardForm.style.display = 'block';
            } else {
                creditCardForm.style.display = 'none';
            }
        });
    });

    // Formater le numéro de carte
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i += 4) {
                formattedValue += value.substr(i, 4) + ' ';
            }
            e.target.value = formattedValue.trim();
        });
    }

    // Formater la date d'expiration
    const cardExpiryInput = document.getElementById('card_expiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>