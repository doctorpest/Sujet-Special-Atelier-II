<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($username, $password);

    if ($result['success']) {
        // Rediriger vers la page précédente ou l'accueil
        $redirectUrl = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']);
        redirect($redirectUrl);
    } else {
        setMessage($result['message'], MSG_ERROR);
    }
}

$pageTitle = "Connexion - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <h1>Connexion</h1>

    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
        <div class="message <?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div class="form-group">
            <label for="username">Nom d'utilisateur ou email</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-lg">Se connecter</button>
            <a href="register.php" class="btn btn-secondary">Créer un compte</a>
        </div>
    </form>

    <p style="margin-top: 1rem;">
        <a href="forgot_password.php">Mot de passe oublié ?</a>
    </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>