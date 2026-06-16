<?php
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { die("Accès refusé."); }

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (verify_csrf_token($token)) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php");
exit;