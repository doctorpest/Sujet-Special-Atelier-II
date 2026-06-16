<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (admin_logged()) { header('Location: ' . SITE_URL . '/admin/'); exit; }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM admins WHERE email=?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password'])) {
        $errors[] = 'Identifiants incorrects.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: ' . SITE_URL . '/admin/'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Administration — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body style="background:var(--ink);display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="max-width:400px;width:100%;padding:24px;">
  <div style="text-align:center;margin-bottom:32px;">
    <p style="font-family:var(--font-head);font-size:1.8rem;color:#fff;">✦ Admin</p>
    <p style="color:rgba(255,255,255,.5);font-size:14px;"><?= SITE_NAME ?></p>
  </div>
  <?php foreach ($errors as $e): ?>
  <div class="alert alert--error"><?= e($e) ?></div>
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
      <button class="btn btn--full" type="submit">Connexion admin</button>
    </form>
  </div>
  <p style="text-align:center;margin-top:16px;font-size:13px;color:rgba(255,255,255,.4);">
    Défaut : admin@boutique.fr / Admin1234!
  </p>
</div>
</body>
</html>
