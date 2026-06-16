<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Récupérer l'image pour suppression
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if ($product['image'] !== 'placeholder.jpg') {
            @unlink(__DIR__ . '/../uploads/' . $product['image']);
        }
    }
}
header('Location: index.php');
exit;
?>