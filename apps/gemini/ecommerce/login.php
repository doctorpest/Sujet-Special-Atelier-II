<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Erreur CSRF détectée.");
    }
    
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        }
    }
    $error = "Identifiants invalides.";
}
include 'includes/header.php';
?>
<div style="max-width: 400px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px;">
    <h2>Connexion</h2>
    <?php if(isset($_GET['registered'])): ?><div class="alert alert-success">Inscription réussie ! Connectez-vous.</div><?php endif; ?>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required class="form-control">
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" required class="form-control">
        </div>
        <button type="submit" class="btn">Se connecter</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>