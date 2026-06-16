<?php
require_once 'config/db.php';
require_once 'config/helpers.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        header('Location: index.php'); exit;
    } else { $error = 'Identifiants incorrects.'; }
}
include 'header.php';
?>
<h1>Connexion</h1>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form class="form" method="post">
    <label>Email <input type="email" name="email" required></label>
    <label>Mot de passe <input type="password" name="password" required></label>
    <button>Se connecter</button>
</form>
<p>Admin de démonstration : admin@example.com / admin123</p>
<?php include 'footer.php'; ?>
