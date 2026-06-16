<?php
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { die("Accès refusé."); }

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Erreur CSRF."); }

    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    $image_name = null;

    // Gestion du Téléversement d'image sécurisé
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Vérification du Type MIME réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($file_ext, $allowed_extensions) && in_array($mime_type, $allowed_mimes) && $file_size <= 2 * 1024 * 1024) {
            // Renommer le fichier pour éviter les collisions ou attaques par nom de fichier
            $image_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
            $upload_dir = __DIR__ . '/../public/uploads/';
            move_uploaded_file($file_tmp, $upload_dir . $image_name);
        } else {
            $error = "Fichier invalide (Format accepté: JPG, PNG, WEBP. Taille max: 2Mo).";
        }
    }

    if (!isset($error) && !empty($name) && $price >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $stock, $image_name]);
        header("Location: index.php");
        exit;
    }
}
include '../includes/header.php';
?>
<h2>Ajouter un Produit</h2>
<?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" style="max-width:600px; background:white; padding:20px; border-radius:8px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <div class="form-group">
        <label>Nom du Produit</label>
        <input type="text" name="name" required class="form-control">
    </div>
    <div class="form-group">
        <label>Catégorie</label>
        <select name="category_id" required class="form-control">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4"></textarea>
    </div>
    <div class="form-group">
        <label>Prix (€)</label>
        <input type="number" step="0.01" name="price" required class="form-control">
    </div>
    <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" required class="form-control">
    </div>
    <div class="form-group">
        <label>Image du Produit</label>
        <input type="file" name="image" class="form-control">
    </div>
    <button type="submit" class="btn">Ajouter le produit</button>
</form>
<?php include '../includes/footer.php'; ?>