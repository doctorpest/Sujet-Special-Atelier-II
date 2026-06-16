<?php
$pageTitle = 'Clients';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

$clients = $pdo->query(
    'SELECT cl.*, COUNT(c.id) AS nb_commandes, COALESCE(SUM(c.total),0) AS ca
     FROM clients cl
     LEFT JOIN commandes c ON c.client_id=cl.id
     GROUP BY cl.id ORDER BY cl.created_at DESC'
)->fetchAll();
?>

<h1 style="margin-bottom:24px;">Clients (<?= count($clients) ?>)</h1>

<table class="admin-table">
  <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Commandes</th><th>CA</th><th>Depuis</th></tr></thead>
  <tbody>
  <?php foreach ($clients as $cl): ?>
  <tr>
    <td><?= e($cl['prenom'].' '.$cl['nom']) ?></td>
    <td><?= e($cl['email']) ?></td>
    <td><?= e($cl['telephone'] ?: '—') ?></td>
    <td><?= $cl['nb_commandes'] ?></td>
    <td><?= format_prix((float)$cl['ca']) ?></td>
    <td><?= date('d/m/Y', strtotime($cl['created_at'])) ?></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
