<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (client_logged()) redirect('pages/compte.php');
$pageTitle = 'Créer un compte — ' . SITE_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $prenom   = trim($_POST['prenom'] ?? '');
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $tel      = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$prenom) $errors[] = 'Prénom requis.';
    if (!$nom)    $errors[] = 'Nom requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if (strlen($password) < 8) $errors[] = 'Mot de passe : 8 caractères minimum.';
    if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        $chk = db()->prepare('SELECT id FROM clients WHERE email=?');
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Cet email est déjà utilisé.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = db()->prepare('INSERT INTO clients (prenom, nom, email, password, telephone) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$prenom, $nom, $email, $hash, $tel]);
        session_regenerate_id(true);
        $_SESSION['client_id'] = (int)db()->lastInsertId();
        flash('Compte créé avec succès ! Bienvenue, ' . $prenom . '.', 'success');
        redirect('pages/compte.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <h1>Créer un compte</h1>
  <p class="subtitle">Rejoignez notre communauté.</p>

  <?php foreach ($errors as $err): ?>
  <div class="alert alert--error"><?= e($err) ?></div>
  <?php endforeach ?>

  <div class="auth-form">
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label>Prénom</label>
          <input type="text" name="prenom" value="<?= e($_POST['prenom'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Nom</label>
          <input type="text" name="nom" value="<?= e($_POST['nom'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Téléphone (optionnel)</label>
        <input type="tel" name="telephone" value="<?= e($_POST['telephone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required minlength="8">
      </div>
      <div class="form-group">
        <label>Confirmer le mot de passe</label>
        <input type="password" name="confirm" required>
      </div>
      <button class="btn btn--full" type="submit">Créer mon compte</button>
    </form>
  </div>
  <p class="auth-footer">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
