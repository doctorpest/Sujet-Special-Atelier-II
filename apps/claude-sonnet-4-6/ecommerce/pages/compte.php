<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_client();
$pageTitle = 'Mon compte — ' . SITE_NAME;

$stmt = db()->prepare('SELECT * FROM clients WHERE id=?');
$stmt->execute([$_SESSION['client_id']]);
$client = $stmt->fetch();

// Dernières commandes
$orders = db()->prepare('SELECT * FROM commandes WHERE client_id=? ORDER BY created_at DESC LIMIT 5');
$orders->execute([$_SESSION['client_id']]);
$commandes = $orders->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="account-grid">
  <nav class="account-nav">
    <a href="compte.php" class="active">Mon profil</a>
    <a href="commandes.php">Mes commandes</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
  <div>
    <h1 style="margin-bottom:28px;">Bonjour, <?= e($client['prenom']) ?> !</h1>

    <div class="card-block">
      <h3>Informations personnelles</h3>
      <table style="width:100%;font-size:14px;border-collapse:collapse;">
        <tr><td style="padding:8px 0;color:var(--slate);width:160px;">Nom complet</td><td><?= e($client['prenom'].' '.$client['nom']) ?></td></tr>
        <tr><td style="padding:8px 0;color:var(--slate);">Email</td><td><?= e($client['email']) ?></td></tr>
        <tr><td style="padding:8px 0;color:var(--slate);">Téléphone</td><td><?= e($client['telephone'] ?: '—') ?></td></tr>
        <tr><td style="padding:8px 0;color:var(--slate);">Membre depuis</td><td><?= date('d/m/Y', strtotime($client['created_at'])) ?></td></tr>
      </table>
    </div>

    <div class="card-block">
      <h3 style="margin-bottom:20px;">Dernières commandes</h3>
      <?php if ($commandes): ?>
      <table class="orders-table">
        <thead><tr><th>#</th><th>Date</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($commandes as $c): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
          <td><?= format_prix((float)$c['total']) ?></td>
          <td><span class="badge badge--<?= $c['statut'] ?>"><?= str_replace('_',' ', $c['statut']) ?></span></td>
          <td><a href="commandes.php?id=<?= $c['id'] ?>" style="font-size:13px;color:var(--accent);">Détail →</a></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <a href="commandes.php" style="display:inline-block;margin-top:14px;font-size:13px;color:var(--accent);">Voir toutes mes commandes →</a>
      <?php else: ?>
      <p style="color:var(--slate);">Vous n'avez pas encore passé de commande. <a href="catalogue.php" style="color:var(--accent);">Découvrir le catalogue →</a></p>
      <?php endif ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
