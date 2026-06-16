<?php
$pageTitle = 'Commandes';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

// Changer statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    csrf_check();
    $statuts_valides = ['en_attente','confirmee','expediee','livree','annulee'];
    $statut = $_POST['statut'] ?? '';
    $id     = (int)$_POST['id'];
    if (in_array($statut, $statuts_valides, true)) {
        $pdo->prepare('UPDATE commandes SET statut=? WHERE id=?')->execute([$statut, $id]);
        flash('Statut mis à jour.', 'success');
    }
    header('Location: commandes.php' . ($id ? '?id='.$id : '')); exit;
}

// Détail
$detail_id = (int)($_GET['id'] ?? 0);
$detail = null; $lignes = []; $client = null;
if ($detail_id) {
    $s = $pdo->prepare('SELECT c.*, cl.prenom, cl.nom, cl.email FROM commandes c JOIN clients cl ON cl.id=c.client_id WHERE c.id=?');
    $s->execute([$detail_id]);
    $detail = $s->fetch();
    if ($detail) {
        $l = $pdo->prepare('SELECT * FROM commande_lignes WHERE commande_id=?');
        $l->execute([$detail_id]);
        $lignes = $l->fetchAll();
    }
}

$commandes = $pdo->query(
    'SELECT c.*, cl.prenom, cl.nom FROM commandes c JOIN clients cl ON cl.id=c.client_id ORDER BY c.created_at DESC'
)->fetchAll();
?>

<div style="display:flex;gap:20px;align-items:center;margin-bottom:24px;">
  <?php if ($detail): ?><a href="commandes.php" class="btn btn--outline btn--sm">← Retour</a><?php endif ?>
  <h1><?= $detail ? 'Commande #'.$detail_id : 'Commandes' ?></h1>
</div>

<?php if ($detail): ?>
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;">
  <div>
    <div class="admin-card" style="margin-bottom:20px;">
      <h3>Client</h3>
      <p style="margin-top:10px;font-size:14px;">
        <?= e($detail['prenom'].' '.$detail['nom']) ?><br>
        <?= e($detail['email']) ?>
      </p>
    </div>
    <div class="admin-card" style="margin-bottom:20px;">
      <h3>Livraison</h3>
      <p style="margin-top:10px;font-size:14px;line-height:1.8;">
        <?= e($detail['adresse_livraison']) ?><br>
        <?= e($detail['code_postal'].' '.$detail['ville']) ?><br>
        <?= e($detail['pays']) ?>
      </p>
    </div>
    <div class="admin-card">
      <h3>Articles</h3>
      <table class="admin-table" style="margin-top:16px;">
        <thead><tr><th>Produit</th><th>Prix</th><th>Qté</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($lignes as $l): ?>
        <tr>
          <td><?= e($l['nom_produit']) ?></td>
          <td><?= format_prix((float)$l['prix_unit']) ?></td>
          <td><?= $l['quantite'] ?></td>
          <td><?= format_prix($l['prix_unit']*$l['quantite']) ?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3" style="text-align:right;font-weight:600;">Total</td><td><?= format_prix((float)$detail['total']) ?></td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div>
    <div class="admin-card">
      <h3>Statut</h3>
      <p style="margin:12px 0;"><span class="badge badge--<?= $detail['statut'] ?>"><?= str_replace('_',' ', $detail['statut']) ?></span></p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="statut">
        <input type="hidden" name="id" value="<?= $detail_id ?>">
        <div class="form-group">
          <label>Changer le statut</label>
          <select name="statut">
            <?php foreach (['en_attente','confirmee','expediee','livree','annulee'] as $s): ?>
            <option value="<?= $s ?>" <?= $detail['statut']===$s?'selected':'' ?>><?= str_replace('_',' ',$s) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <button class="btn" type="submit">Mettre à jour</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<table class="admin-table">
  <thead><tr><th>#</th><th>Client</th><th>Montant</th><th>Ville</th><th>Statut</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($commandes as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= e($c['prenom'].' '.$c['nom']) ?></td>
    <td><?= format_prix((float)$c['total']) ?></td>
    <td><?= e($c['ville']) ?></td>
    <td><span class="badge badge--<?= $c['statut'] ?>"><?= str_replace('_',' ',$c['statut']) ?></span></td>
    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
    <td><a href="?id=<?= $c['id'] ?>" class="btn btn--sm">Détail</a></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
