<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $image = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $image = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $image);
            // Supprimer l'ancienne image si différente du placeholder
            if ($product['image'] !== 'placeholder.jpg' && file_exists(__DIR__ . '/../uploads/' . $product['image'])) {
                unlink(__DIR__ . '/../uploads/' . $product['image']);
            }
        }
    }

    if (empty($name) || $price <= 0) {
        $error = 'Nom et prix valide obligatoires.';
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier produit</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <nav>
        <a href="index.php" class="logo">Admin</a>
        <a href="../index.php">Site</a>
    </nav>
</header>
<main>
<h1>Modifier le produit</h1>
<?php if ($error): ?><p class="alert alert-error"><?= h($error) ?></p><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <label>Nom</label>
    <input type="text" name="name" value="<?= h($product['name']) ?>" required>
    <label>Description</label>
    <textarea name="description"><?= h($product['description']) ?></textarea>
    <label>Prix</label>
    <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
    <label>Stock</label>
    <input type="number" name="stock" value="<?= $product['stock'] ?>" required>
    <label>Catégorie</label>
    <select name="category_id">
        <option value="">Aucune</option>
        <?php
        $cats = $pdo->query("SELECT * FROM categories");
        while ($cat = $cats->fetch()):
            $selected = $product['category_id'] == $cat['id'] ? 'selected' : '';
        ?>
            <option value="<?= $cat['id'] ?>" <?= $selected ?>><?= h($cat['name']) ?></option>
        <?php endwhile; ?>
    </select>
    <label>Image actuelle</label>
    <img src="../uploads/<?= h($product['image']) ?>" width="100"><br>
    <label>Nouvelle image (laisser vide pour conserver)</label>
    <input type="file" name="image">
    <button type="submit">Enregistrer</button>
</form>
</main>
</body>
</html>