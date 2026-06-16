<?php
require_once __DIR__ . '/../header.php';
if (!is_admin()) {
    redirect(BASE_URL . '/admin/admin_login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$product = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'image' => ''
];

if ($editing) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
    if (!$product) {
        echo "<p>Produit introuvable.</p>";
        require_once __DIR__ . '/../footer.php';
        exit;
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($name === '') {
        $errors[] = "Le nom est obligatoire.";
    }

    $imageName = $product['image'] ?? null;

    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('prod_') . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName);
        } else {
            $errors[] = "Erreur lors du téléversement de l'image.";
        }
    }

    if (!$errors) {
        if ($editing) {
            $stmt = $pdo->prepare("UPDATE products
                                   SET name = :name, description = :description, price = :price,
                                       stock = :stock, category_id = :cat, image = :img
                                   WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':cat' => $category_id ?: null,
                ':img' => $imageName,
                ':id' => $id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image)
                                   VALUES (:name, :description, :price, :stock, :cat, :img)");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':cat' => $category_id ?: null,
                ':img' => $imageName
            ]);
        }
        redirect(BASE_URL . '/admin/products.php');
    }
}
?>
<h2><?= $editing ? 'Modifier' : 'Ajouter' ?> un produit</h2>

<?php foreach ($errors as $e): ?>
    <p class="error"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <label>Nom :
        <input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
    </label>
    <label>Description :
        <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </label>
    <label>Prix :
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>
    </label>
    <label>Stock :
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock'] ?? '') ?>" required>
    </label>
    <label>Catégorie :
        <select name="category_id">
            <option value="0">Aucune</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Image :
        <input type="file" name="image">
        <?php if (!empty($product['image'])): ?>
            <br>Image actuelle : <?= htmlspecialchars($product['image']) ?>
        <?php endif; ?>
    </label>
    <button type="submit"><?= $editing ? 'Mettre à jour' : 'Créer' ?></button>
</form>

<?php require_once __DIR__ . '/../footer.php'; ?>
