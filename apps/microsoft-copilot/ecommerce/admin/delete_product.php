<?php
require_once __DIR__ . '/../db.php';
if (!is_admin()) {
    redirect(BASE_URL . '/admin/admin_login.php');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);

redirect(BASE_URL . '/admin/products.php');
