<?php
require_once __DIR__ . '/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = "Identifiants invalides.";
    } else {
        $_SESSION['user'] = $user;
        redirect(BASE_URL . '/index.php');
    }
}
?>
<h2>Connexion</h2>
<?php foreach ($errors as $e): ?>
    <p class="error"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<form method="post" class="auth-form">
    <label>Email :
        <input type="email" name="email" required>
    </label>
    <label>Mot de passe :
        <input type="password" name="password" required>
    </label>
    <button type="submit">Se connecter</button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
