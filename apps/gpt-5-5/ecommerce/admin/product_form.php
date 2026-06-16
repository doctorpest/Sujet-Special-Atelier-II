<?php
require_once '../config/db.php';
require_once '../config/helpers.php';
require_admin();
$id = $_GET['id'] ?? null;
$product = ['name'=>'','description'=>'','price'=>'','stock'=>'','category_id'=>'','image'=>''];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch() ?: $product;
}
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $categoryId = $_POST['category_id'] ?: null;
    $imageName = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
        $type = mime_content_type($_FILES['image']['tmp_name']);
        if (isset($allowed[$type])) {
            $imageName = uniqid('prod_', true) . '.' . $allowed[$type];
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imageName);
        }
    }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?');
        $stmt->execute([$categoryId, $name, $description, $price, $stock, $imageName, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$categoryId, $name, $description, $price, $stock, $imageName]);
    }
    header('Location: products.php'); exit;
}
include '../header.php';
?>
<h1><?= $id ? 'Modifier' : 'Ajouter' ?> un produit</h1>
<form class="form" method="post" enctype="multipart/form-data">
    <label>Nom <input name="name" value="<?= e($product['name']) ?>" required></label>
    <label>Description <textarea name="description"><?= e($product['description']) ?></textarea></label>
    <label>Catégorie <select name="category_id"><option value="">Sans catégorie</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= $cat['id']==$product['category_id']?'selected':'' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></label>
    <label>Prix <input type="number" step="0.01" name="price" value="<?= e($product['price']) ?>" required></label>
    <label>Stock <input type="number" name="stock" value="<?= e($product['stock']) ?>" required></label>
    <label>Image <input type="file" name="image" accept="image/png,image/jpeg,image/webp"></label>
    <?php if ($product['image']): ?><img class="preview" src="../uploads/<?= e($product['image']) ?>"><?php endif; ?>
    <button>Enregistrer</button>
</form>
<?php include '../footer.php'; ?>
