<?php
require_once __DIR__ . '/../header.php';
if (!is_admin()) {
    redirect(BASE_URL . '/admin/admin_login.php');
}

$stmt = $pdo->query("SELECT p.*, c.name AS category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY p.id DESC");
$products = $stmt->fetchAll();
?>
<h2>Produits</h2>
<p><a class="btn" href="product_form.php">Ajouter un produit</a></p>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Catégorie</th>
        <th>Prix</th>
        <th>Stock</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category_name'] ?? '') ?></td>
            <td><?= number_format($p['price'], 2, ',', ' ') ?> €</td>
            <td><?= (int)$p['stock'] ?></td>
            <td><?= htmlspecialchars($p['image']) ?></td>
            <td>
                <a href="product_form.php?id=<?= $p['id'] ?>">Modifier</a> |
                <a href="delete_product.php?id=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce produit ?');">Supprimer</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../footer.php'; ?>
