<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Ajouter un produit au panier
function addToCart($productId, $quantity = 1) {
    global $db;

    // Vérifier si le produit existe
    $product = $db->query("SELECT * FROM products WHERE product_id = ?", [$productId]);
    if ($product->num_rows === 0) {
        return ['success' => false, 'message' => 'Produit introuvable.'];
    }

    $product = $product->fetch_assoc();

    // Vérifier le stock
    if ($product['stock_quantity'] < $quantity) {
        return ['success' => false, 'message' => 'Stock insuffisant.'];
    }

    // Ajouter au panier
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image_path' => $product['image_path'],
            'quantity' => $quantity
        ];
    }

    return ['success' => true, 'message' => 'Produit ajouté au panier.'];
}

// Retirer un produit du panier
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return ['success' => true, 'message' => 'Produit retiré du panier.'];
    }
    return ['success' => false, 'message' => 'Produit introuvable dans le panier.'];
}

// Mettre à jour la quantité d'un produit dans le panier
function updateCartQuantity($productId, $quantity) {
    global $db;

    if ($quantity <= 0) {
        return removeFromCart($productId);
    }

    // Vérifier le stock
    $product = $db->query("SELECT stock_quantity FROM products WHERE product_id = ?", [$productId]);
    if ($product->num_rows === 0) {
        return ['success' => false, 'message' => 'Produit introuvable.'];
    }

    $product = $product->fetch_assoc();
    if ($product['stock_quantity'] < $quantity) {
        return ['success' => false, 'message' => 'Stock insuffisant.'];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        return ['success' => true, 'message' => 'Quantité mise à jour.'];
    }

    return ['success' => false, 'message' => 'Produit introuvable dans le panier.'];
}

// Vider le panier
function clearCart() {
    $_SESSION['cart'] = [];
    return ['success' => true, 'message' => 'Panier vidé.'];
}

// Calculer le total du panier
function getCartTotal() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Calculer le nombre d'articles dans le panier
function getCartItemCount() {
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

// Récupérer les produits du panier
function getCartItems() {
    return $_SESSION['cart'];
}
?>