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

// Vérifier que le produit existe
$product = $db->query("SELECT * FROM products WHERE product_id = ?", [$productId]);

if ($product->num_rows === 0) {
    setMessage('Produit introuvable.', MSG_ERROR);
    redirect('products.php');
}

$product = $product->fetch_assoc();

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token CSRF invalide.', MSG_ERROR);
    } else {
        // Supprimer l'image si elle existe
        if (!empty($product['image_path']) && file_exists(UPLOADS_PATH . $product['image_path'])) {
            deleteImage($product['image_path']);
        }

        // Supprimer le produit
        $result = $db->execute("DELETE FROM products WHERE product_id = ?", [$productId]);

        if ($result) {
            setMessage('Produit supprimé avec succès.', MSG_SUCCESS);
        } else {
            setMessage('Erreur lors de la suppression du produit.', MSG_ERROR);
        }

        redirect('products.php');
    }
}

$pageTitle = "Supprimer un produit - Administration";
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="admin-sidebar">
            <h3>Menu Admin</h3>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="products.php">Produits</a></li>
                <li><a href="orders.php">Commandes</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <h1>Supprimer un produit</h1>

            <p>Êtes-vous sûr de vouloir supprimer le produit <strong><?php echo htmlspecialchars($product['name']); ?></strong> ?</p>
            <p>Cette action est irréversible.</p>

            <form method="post" action="delete_product.php?id=<?php echo $productId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-actions">
                    <button type="submit" class="btn btn-error">Supprimer définitivement</button>
                    <a href="products.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>