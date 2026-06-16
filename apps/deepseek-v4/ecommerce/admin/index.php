<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <nav>
        <a href="../index.php" class="logo">🛍️ Admin</a>
        <div class="nav-links">
            <a href="../index.php">Site</a>
            <a href="add_product.php">Ajouter produit</a>
            <a href="../logout.php">Déconnexion</a>
        </div>
    </nav>
</header>
<main>
<h1>Gestion des produits</h1>

<table>
    <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Nom</th>
        <th>Catégorie</th>
        <th>Prix</th>
        <th>Stock</th>
        <th>Actions</th>
    </tr>
    <?php
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    while ($product = $stmt->fetch()):
    ?>
    <tr>
        <td><?= $product['id'] ?></td>
        <td><img src="../uploads/<?= h($product['image'] ?: 'placeholder.jpg') ?>" width="50"></td>
        <td><?= h($product['name']) ?></td>
        <td><?= h($product['category_name'] ?? '-') ?></td>
        <td><?= number_format($product['price'], 2) ?> €</td>
        <td><?= $product['stock'] ?></td>
        <td>
            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn">Modifier</a>
            <a href="delete_product.php?id=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</main>
</body>
</html>