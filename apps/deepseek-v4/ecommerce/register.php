<?php require_once 'includes/header.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $address = trim($_POST['address']);

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        // Vérifier unicité
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Nom d\'utilisateur ou email déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $address]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            redirect('index.php');
        }
    }
}
?>

<h1>Créer un compte</h1>
<?php if ($error): ?><p class="alert alert-error"><?= h($error) ?></p><?php endif; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <label>Nom d'utilisateur</label>
    <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
    <label>Mot de passe</label>
    <input type="password" name="password" required>
    <label>Confirmer le mot de passe</label>
    <input type="password" name="password_confirm" required>
    <label>Adresse (optionnelle)</label>
    <textarea name="address"><?= h($_POST['address'] ?? '') ?></textarea>
    <button type="submit">S'inscrire</button>
</form>

<?php require_once 'includes/footer.php'; ?>