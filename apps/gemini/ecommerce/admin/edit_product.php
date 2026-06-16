<?php
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { die("Accès refusé."); }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { die("Produit introuvable."); }
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Erreur CSRF."); }

    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image_name = $product['image_url']; // Par défaut conserve l'ancienne image

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed_extensions)) {
            $image_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
            move_uploaded_file($file_tmp, __DIR__ . '/../public/uploads/' . $image_name);
        }
    }

    if (!empty($name) && $price >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$category_id, $name, $description, $price, $stock, $image_name, $id]);
        header("Location: index.php");
        exit;
    }
}
include '../includes/header.php';
?>
<h2>Modifier le Produit : <?= htmlspecialchars($product['name']) ?></h2>
<form method="POST" enctype="multipart/form-data" style="max-width:600px; background:white; padding:20px; border-radius:8px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <div class="form-group">
        <label>Nom du Produit</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required class="form-control">
    </div>
    <div class="form-group">
        <label>Catégorie</label>
        <select name="category_id" required class="form-control">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Prix (€)</label>
        <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required class="form-control">
    </div>
    <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" value="<?= $product['stock'] ?>" required class="form-control">
    </div>
    <div class="form-group">
        <label>Changer l'image (optionnel)</label>
        <input type="file" name="image" class="form-control">
    </div>
    <button type="submit" class="btn">Mettre à jour</button>
</form>
<?php include '../includes/footer.php'; ?>