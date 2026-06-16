<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = db();
$nb_produits  = $pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
$nb_clients   = $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$nb_commandes = $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
$ca_total     = $pdo->query('SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut != "annulee"')->fetchColumn();

$recentes = $pdo->query(
    'SELECT c.*, cl.prenom, cl.nom FROM commandes c JOIN clients cl ON cl.id=c.client_id ORDER BY c.created_at DESC LIMIT 10'
)->fetchAll();
?>

<h1>Tableau de bord</h1>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:28px 0;">
  <?php foreach ([
    ['Produits',   $nb_produits,            '📦'],
    ['Clients',    $nb_clients,             '👤'],
    ['Commandes',  $nb_commandes,           '🛍️'],
    ['CA total',   format_prix((float)$ca_total), '💰'],
  ] as [$label, $val, $icon]): ?>
  <div class="card-block" style="text-align:center;margin:0;">
    <div style="font-size:2rem;margin-bottom:4px;"><?= $icon ?></div>
    <div style="font-family:var(--font-head);font-size:1.8rem;"><?= $val ?></div>
    <div style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--slate);margin-top:4px;"><?= $label ?></div>
  </div>
  <?php endforeach ?>
</div>

<h2 style="margin-bottom:16px;">Dernières commandes</h2>
<table class="admin-table">
  <thead><tr><th>#</th><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($recentes as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= e($c['prenom'].' '.$c['nom']) ?></td>
    <td><?= format_prix((float)$c['total']) ?></td>
    <td><span class="badge badge--<?= $c['statut'] ?>"><?= str_replace('_',' ', $c['statut']) ?></span></td>
    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
    <td><a href="commandes.php?id=<?= $c['id'] ?>" class="btn btn--sm">Voir</a></td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
