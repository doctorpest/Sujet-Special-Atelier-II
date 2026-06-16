<?php
require_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<p class="alert alert-error">Produit introuvable.</p>';
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="product-detail">
    <img src="uploads/<?= h($product['image'] ?: 'placeholder.jpg') ?>" alt="<?= h($product['name']) ?>" style="max-width: 300px;">
    <h1><?= h($product['name']) ?></h1>
    <p>Catégorie : <?= h($product['category_name'] ?? 'Non classé') ?></p>
    <p class="price"><?= number_format($product['price'], 2) ?> €</p>
    <p>Stock disponible : <?= $product['stock'] ?></p>
    <p><?= nl2br(h($product['description'])) ?></p>

    <?php if ($product['stock'] > 0): ?>
        <form action="cart.php" method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <label for="quantity">Quantité :</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" style="width:80px;">
            <button type="submit" class="btn">Ajouter au panier</button>
        </form>
    <?php else: ?>
        
        <p class="alert alert-error">Produit en rupture de stock.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>