<?php
require_once __DIR__ . '/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p>Produit introuvable.</p>";
    require_once __DIR__ . '/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = 0;
    }
    $_SESSION['cart'][$id] += $qty;
    echo "<p class='success'>Produit ajouté au panier.</p>";
}
?>
<h2><?= htmlspecialchars($product['name']) ?></h2>
<div class="product-detail">
    <div>
        <?php if ($product['image']): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars($product['image']) ?>" alt="">
        <?php else: ?>
            <div class="placeholder-img">Aucune image</div>
        <?php endif; ?>
    </div>
    <div>
        <p><strong>Catégorie :</strong> <?= htmlspecialchars($product['category_name'] ?? 'Sans catégorie') ?></p>
        <p><strong>Prix :</strong> <?= number_format($product['price'], 2, ',', ' ') ?> €</p>
        <p><strong>Stock :</strong> <?= (int)$product['stock'] ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <?php if ($product['stock'] > 0): ?>
            <form method="post">
                <label>Quantité :
                    <input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>">
                </label>
                <button type="submit">Ajouter au panier</button>
            </form>
        <?php else: ?>
            <p class="error">Rupture de stock.</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
