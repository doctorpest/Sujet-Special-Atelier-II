<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (client_logged()) redirect('pages/compte.php');
$pageTitle = 'Connexion — ' . SITE_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM clients WHERE email=?');
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if (!$client || !password_verify($password, $client['password'])) {
        $errors[] = 'Email ou mot de passe incorrect.';
    } else {
        session_regenerate_id(true);
        $_SESSION['client_id'] = $client['id'];
        flash('Bienvenue, ' . $client['prenom'] . ' !', 'success');
        $redir = $_SESSION['redirect_after_login'] ?? 'pages/compte.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . SITE_URL . '/' . $redir);
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <h1>Connexion</h1>
  <p class="subtitle">Accédez à votre espace client.</p>

  <?php foreach ($errors as $err): ?>
  <div class="alert alert--error"><?= e($err) ?></div>
  <?php endforeach ?>

  <div class="auth-form">
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn btn--full" type="submit">Se connecter</button>
    </form>
  </div>
  <p class="auth-footer">Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
