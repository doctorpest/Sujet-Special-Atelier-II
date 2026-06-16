<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // Upload image
    $image = 'placeholder.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Format d\'image non autorisé.';
        } else {
            $image = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $image);
        }
    }

    if (empty($name) || $price <= 0) {
        $error = 'Nom et prix valide obligatoires.';
    }

    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un produit</title>
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
<h1>Ajouter un produit</h1>
<?php if ($error): ?><p class="alert alert-error"><?= h($error) ?></p><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <label>Nom</label>
    <input type="text" name="name" required>
    <label>Description</label>
    <textarea name="description"></textarea>
    <label>Prix (€)</label>
    <input type="number" step="0.01" name="price" required>
    <label>Stock</label>
    <input type="number" name="stock" value="0" required>
    <label>Catégorie</label>
    <select name="category_id">
        <option value="">Aucune</option>
        <?php
        $cats = $pdo->query("SELECT * FROM categories");
        while ($cat = $cats->fetch()):
        ?>
            <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
        <?php endwhile; ?>
    </select>
    <label>Image</label>
    <input type="file" name="image">
    <button type="submit">Ajouter</button>
</form>
</main>
</body>
</html>