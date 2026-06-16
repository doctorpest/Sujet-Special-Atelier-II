<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Erreur CSRF détectée.");
    }

    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if ($name && $email && strlen($password) >= 6) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hashed_password]);
            header("Location: login.php?registered=1");
            exit;
        } catch (PDOException $e) {
            $error = "Cet email est déjà pris.";
        }
    } else {
        $error = "Veuillez remplir les champs correctement (Mot de passe : 6 caractères min).";
    }
}
include 'includes/header.php';
?>
<div style="max-width: 400px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px;">
    <h2>Créer un compte</h2>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <div class="form-group">
            <label>Nom Complet</label>
            <input type="text" name="name" required class="form-control">
        </div>
        <div class="form-group">
            <label>Adresse Email</label>
            <input type="email" name="email" required class="form-control">
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" required class="form-control">
        </div>
        <button type="submit" class="btn">S'inscrire</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>