<?php
require_once __DIR__ . '/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = "Tous les champs sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }
    if ($password !== $password2) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Un compte existe déjà avec cet email.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (:email, :hash, :name)");
            $stmt->execute([
                ':email' => $email,
                ':hash' => $hash,
                ':name' => $name
            ]);
            echo "<p class='success'>Compte créé. Vous pouvez maintenant vous connecter.</p>";
        }
    }
}
?>
<h2>Créer un compte</h2>
<?php foreach ($errors as $e): ?>
    <p class="error"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<form method="post" class="auth-form">
    <label>Nom :
        <input type="text" name="name" required>
    </label>
    <label>Email :
        <input type="email" name="email" required>
    </label>
    <label>Mot de passe :
        <input type="password" name="password" required>
    </label>
    <label>Confirmer le mot de passe :
        <input type="password" name="password2" required>
    </label>
    <button type="submit">Créer mon compte</button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
