<?php
require_once 'config/db.php';
require_once 'config/helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !$email || strlen($password) < 6) {
        $error = 'Tous les champs sont requis. Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['flash'] = 'Compte créé. Connecte-toi.';
            header('Location: login.php'); exit;
        } catch (PDOException $e) { $error = 'Email déjà utilisé.'; }
    }
}
include 'header.php';
?>
<h1>Créer un compte</h1>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form class="form" method="post">
    <label>Nom <input name="name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Mot de passe <input type="password" name="password" required></label>
    <button>Créer le compte</button>
</form>
<?php include 'footer.php'; ?>
