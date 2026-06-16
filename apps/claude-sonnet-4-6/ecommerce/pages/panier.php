<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Mon panier — ' . SITE_NAME;
$panier    = panier();
$total     = panier_total();
include __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom:32px;">Mon panier</h1>

<?php if ($panier): ?>
<table class="cart-table">
  <thead>
    <tr>
      <th>Produit</th>
      <th>Prix unitaire</th>
      <th>Quantité</th>
      <th>Sous-total</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($panier as $item): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:14px;">
          <img class="prod-img" src="<?= produit_image_url(null) ?>" alt="">
          <a href="produit.php?id=<?= $item['id'] ?>"><?= e($item['nom']) ?></a>
        </div>
      </td>
      <td><?= format_prix((float)$item['prix']) ?></td>
      <td>
        <form method="post" action="panier_action.php" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="modifier">
          <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
          <input class="qty-input cart-qty-input" type="number" name="qte"
                 value="<?= $item['qte'] ?>" min="0" max="<?= $item['stock'] ?>"
                 style="width:64px;">
        </form>
      </td>
      <td><?= format_prix((float)$item['prix'] * $item['qte']) ?></td>
      <td>
        <form method="post" action="panier_action.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="supprimer">
          <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
          <button class="btn btn--danger btn--sm">✕</button>
        </form>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<div class="cart-totals">
  <table>
    <tr><td>Sous-total</td><td style="text-align:right"><?= format_prix($total) ?></td></tr>
    <tr><td>Livraison</td><td style="text-align:right">Offerte</td></tr>
    <tr class="total-row"><td>Total</td><td style="text-align:right"><?= format_prix($total) ?></td></tr>
  </table>
  <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;">
    <a href="commande.php" class="btn btn--full">Passer la commande →</a>
    <form method="post" action="panier_action.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="vider">
      <button class="btn btn--outline btn--full btn--sm">Vider le panier</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="empty-state">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
  <p>Votre panier est vide.</p>
  <a href="catalogue.php" class="btn mt-4">Continuer mes achats</a>
</div>
<?php endif ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
