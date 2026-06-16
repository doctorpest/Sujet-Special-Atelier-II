<?php
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Accès refusé. Réservé aux administrateurs.");
}

$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$products = $stmt->fetchAll();

include '../includes/header.php';
?>
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2>Gestion des Produits (Admin)</h2>
    <a href="add_product.php" class="btn" style="max-width:200px;">+ Ajouter un produit</a>
</div>

<table class="table">
    <thead>
        <tr><th>ID</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>
                    <?php if($p['image_url']): ?>
                        <img src="/maboutique/public/uploads/<?= htmlspecialchars($p['image_url']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category_name']) ?></td>
                <td><?= number_format($p['price'], 2) ?> €</td>
                <td><?= $p['stock'] ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $p['id'] ?>" style="color:#2980b9; font-weight:bold; margin-right:15px;">Modifier</a>
                    <a href="delete_product.php?id=<?= $p['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" onclick="return confirm('Supprimer ce produit ?');" style="color:var(--danger); font-weight:bold;">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>