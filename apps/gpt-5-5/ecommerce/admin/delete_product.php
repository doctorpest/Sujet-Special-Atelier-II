<?php
require_once '../config/db.php';
require_once '../config/helpers.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$id]);
header('Location: products.php');
exit;
