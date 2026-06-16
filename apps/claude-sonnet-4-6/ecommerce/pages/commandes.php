<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_client();
$pageTitle = 'Mes commandes — ' . SITE_NAME;

// Détail d'une commande spécifique
$detail_id = (int)($_GET['id'] ?? 0);
$detail    = null;
$lignes    = [];
if ($detail_id) {
    $s = db()->prepare('SELECT * FROM commandes WHERE id=? AND client_id=?');
    $s->execute([$detail_id, $_SESSION['client_id']]);
    $detail = $s->fetch();
    if ($detail) {
        $l = db()->prepare('SELECT * FROM commande_lignes WHERE commande_id=?');
        $l->execute([$detail_id]);
        $lignes = $l->fetchAll();
    }
}

// Liste de toutes les commandes
$stmt = db()->prepare('SELECT * FROM commandes WHERE client_id=? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['client_id']]);
$commandes = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="account-grid">
  <nav class="account-nav">
    <a href="compte.php">Mon profil</a>
    <a href="commandes.php" class="active">Mes commandes</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
  <div>
    <?php if ($detail): ?>
    <!-- Détail commande -->
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
      <a href="commandes.php" style="font-size:13px;color:var(--slate);">← Retour</a>
      <h1>Commande #<?= $detail['id'] ?></h1>
      <span class="badge badge--<?= $detail['statut'] ?>"><?= str_replace('_',' ', $detail['statut']) ?></span>
    </div>

    <div class="card-block" style="margin-bottom:20px;">
      <h3>Adresse de livraison</h3>
      <p style="margin-top:12px;font-size:14px;line-height:1.8;">
        <?= e($detail['adresse_livraison']) ?><br>
        <?= e($detail['code_postal']) ?> <?= e($detail['ville']) ?><br>
        <?= e($detail['pays']) ?>
      </p>
      <?php if ($detail['notes']): ?>
      <p style="margin-top:10px;font-size:13px;color:var(--slate);">Note : <?= e($detail['notes']) ?></p>
      <?php endif ?>
    </div>

    <div class="card-block">
      <h3>Articles commandés</h3>
      <table class="orders-table" style="margin-top:16px;">
        <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th></tr></thead>
        <tbody>
        <?php foreach ($lignes as $l): ?>
        <tr>
          <td><?= e($l['nom_produit']) ?></td>
          <td><?= format_prix((float)$l['prix_unit']) ?></td>
          <td><?= $l['quantite'] ?></td>
          <td><?= format_prix($l['prix_unit'] * $l['quantite']) ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3" style="text-align:right;font-weight:600;font-family:var(--font-head);">Total</td><td style="font-weight:600;font-family:var(--font-head);"><?= format_prix((float)$detail['total']) ?></td></tr>
        </tfoot>
      </table>
    </div>

    <?php else: ?>
    <!-- Liste commandes -->
    <h1 style="margin-bottom:28px;">Mes commandes</h1>
    <?php if ($commandes): ?>
    <table class="orders-table">
      <thead><tr><th>#</th><th>Date</th><th>Montant</th><th>Ville</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($commandes as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
        <td><?= format_prix((float)$c['total']) ?></td>
        <td><?= e($c['ville']) ?></td>
        <td><span class="badge badge--<?= $c['statut'] ?>"><?= str_replace('_',' ', $c['statut']) ?></span></td>
        <td><a href="?id=<?= $c['id'] ?>" style="font-size:13px;color:var(--accent);">Détail →</a></td>
      </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <p>Aucune commande pour l'instant.</p>
      <a href="catalogue.php" class="btn mt-4">Découvrir le catalogue</a>
    </div>
    <?php endif ?>
    <?php endif ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
