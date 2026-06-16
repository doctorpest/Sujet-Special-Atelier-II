<?php
require_once '../config/db.php';
require_once '../config/helpers.php';
require_admin();
$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();
include '../header.php';
?>
<h1>Administration des produits</h1>
<a class="button" href="product_form.php">Ajouter un produit</a>
<table>
<tr><th>ID</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr>
<?php foreach ($products as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><?php if ($p['image']): ?><img class="thumb" src="../uploads/<?= e($p['image']) ?>"><?php endif; ?></td>
<td><?= e($p['name']) ?></td>
<td><?= e($p['category_name']) ?></td>
<td><?= number_format($p['price'], 2, ',', ' ') ?> €</td>
<td><?= (int)$p['stock'] ?></td>
<td><a href="product_form.php?id=<?= $p['id'] ?>">Modifier</a> | <a onclick="return confirm('Supprimer ?')" href="delete_product.php?id=<?= $p['id'] ?>">Supprimer</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php include '../footer.php'; ?>
