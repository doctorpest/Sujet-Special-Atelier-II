<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token CSRF invalide.', MSG_ERROR);
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName)) {
            setMessage('Tous les champs sont obligatoires.', MSG_ERROR);
        } elseif ($password !== $confirmPassword) {
            setMessage('Les mots de passe ne correspondent pas.', MSG_ERROR);
        } elseif (strlen($password) < 6) {
            setMessage('Le mot de passe doit contenir au moins 6 caractères.', MSG_ERROR);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage('Adresse email invalide.', MSG_ERROR);
        } else {
            $result = registerUser($username, $email, $password, $firstName, $lastName);

            if ($result['success']) {
                setMessage($result['message'], MSG_SUCCESS);
                redirect('login.php');
            } else {
                setMessage($result['message'], MSG_ERROR);
            }
        }
    }
}

$pageTitle = "Inscription - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <h1>Créer un compte</h1>

    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
        <div class="message <?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="first_name">Prénom</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>

        <div class="form-group">
            <label for="last_name">Nom</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-lg">S'inscrire</button>
            <a href="login.php" class="btn btn-secondary">Déjà un compte ? Se connecter</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>