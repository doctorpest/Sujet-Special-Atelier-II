<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('../public/login.php');
}

// Récupérer l'ID du produit
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer le produit
$product = $db->query("SELECT * FROM products WHERE product_id = ?", [$productId]);

if ($product->num_rows === 0) {
    redirect('products.php');
}

$product = $product->fetch_assoc();

// Récupérer les catégories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token CSRF invalide.', MSG_ERROR);
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);

        // Validation
        if (empty($name) || empty($price) || $price <= 0 || $stockQuantity < 0) {
            setMessage('Les champs Nom, Prix et Stock sont obligatoires et doivent être valides.', MSG_ERROR);
        } else {
            // Télécharger l'image si une nouvelle est fournie
            $imagePath = $product['image_path'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Supprimer l'ancienne image si elle existe
                if (!empty($product['image_path']) && file_exists(UPLOADS_PATH . $product['image_path'])) {
                    deleteImage($product['image_path']);
                }

                $uploadResult = uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['filename'];
                } else {
                    setMessage($uploadResult['message'], MSG_ERROR);
                }
            }

            // Mettre à jour le produit
            if (empty($uploadResult) || $uploadResult['success']) {
                $result = $db->execute(
                    "UPDATE products
                     SET name = ?, description = ?, price = ?, stock_quantity = ?, category_id = ?, image_path = ?
                     WHERE product_id = ?",
                    [$name, $description, $price, $stockQuantity, $categoryId, $imagePath, $productId]
                );

                if ($result) {
                    setMessage('Produit mis à jour avec succès.', MSG_SUCCESS);
                    redirect('products.php');
                } else {
                    setMessage('Erreur lors de la mise à jour du produit.', MSG_ERROR);
                }
            }
        }
    }
}

$pageTitle = "Modifier un produit - Administration";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <h3>Menu Admin</h3>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="products.php">Produits</a></li>
                <li><a href="add_product.php">Ajouter un produit</a></li>
                <li><a href="edit_product.php?id=<?php echo $productId; ?>" class="active">Modifier un produit</a></li>
                <li><a href="orders.php">Commandes</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <h1>Modifier un produit</h1>

            <form method="post" action="edit_product.php?id=<?php echo $productId; ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label for="name">Nom du produit</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="price">Prix (€)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Quantité en stock</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $product['stock_quantity']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_id">Catégorie</label>
                    <select id="category_id" name="category_id">
                        <option value="">Aucune catégorie</option>
                        <?php
                        $categories->data_seek(0);
                        while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                <?php echo $category['category_id'] === $product['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image du produit</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <p style="font-size: 0.8rem; color: #777; margin-top: 0.5rem;">
                        Formats acceptés : JPG, PNG, JPEG, GIF (max 2Mo)
                    </p>
                    <?php if (!empty($product['image_path']) && file_exists(UPLOADS_PATH . $product['image_path'])): ?>
                        <div style="margin-top: 1rem;">
                            <p>Image actuelle :</p>
                            <img src="<?php echo UPLOADS_URL . $product['image_path']; ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 200px; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <img class="image-preview" style="display: none; max-width: 200px; margin-top: 1rem; border-radius: 4px;">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Mettre à jour</button>
                    <a href="products.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>