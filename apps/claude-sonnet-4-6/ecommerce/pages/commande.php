<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_client();

$panier = panier();
if (empty($panier)) { flash('Votre panier est vide.','warning'); redirect('pages/panier.php'); }
$total = panier_total();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $adresse    = trim($_POST['adresse'] ?? '');
    $ville      = trim($_POST['ville'] ?? '');
    $code_postal= trim($_POST['code_postal'] ?? '');
    $pays       = trim($_POST['pays'] ?? 'France');
    $notes      = trim($_POST['notes'] ?? '');
    $cc_num     = preg_replace('/\s/', '', $_POST['cc_number'] ?? '');
    $cc_exp     = trim($_POST['cc_exp'] ?? '');
    $cc_cvv     = trim($_POST['cc_cvv'] ?? '');

    if (!$adresse)    $errors[] = 'Adresse requise.';
    if (!$ville)      $errors[] = 'Ville requise.';
    if (!$code_postal)$errors[] = 'Code postal requis.';
    if (strlen($cc_num) !== 16 || !ctype_digit($cc_num))  $errors[] = 'Numéro de carte invalide.';
    if (!preg_match('/^\d{2}\/\d{2}$/', $cc_exp))         $errors[] = 'Date d\'expiration invalide (MM/AA).';
    if (!preg_match('/^\d{3,4}$/', $cc_cvv))              $errors[] = 'CVV invalide.';

    if (empty($errors)) {
        // Vérifier stocks
        foreach ($panier as $item) {
            $row = db()->prepare('SELECT stock FROM produits WHERE id=? AND actif=1');
            $row->execute([$item['id']]);
            $prod = $row->fetch();
            if (!$prod || $prod['stock'] < $item['qte']) {
                $errors[] = 'Stock insuffisant pour « ' . e($item['nom']) . ' ».';
            }
        }
    }

    if (empty($errors)) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Créer commande
            $stmt = $pdo->prepare(
                'INSERT INTO commandes (client_id, total, adresse_livraison, ville, code_postal, pays, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$_SESSION['client_id'], $total, $adresse, $ville, $code_postal, $pays, $notes]);
            $commande_id = (int)$pdo->lastInsertId();

            // Lignes + décrémenter stock
            $ins = $pdo->prepare(
                'INSERT INTO commande_lignes (commande_id, produit_id, nom_produit, prix_unit, quantite)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $upd = $pdo->prepare('UPDATE produits SET stock=stock-? WHERE id=?');
            foreach ($panier as $item) {
                $ins->execute([$commande_id, $item['id'], $item['nom'], $item['prix'], $item['qte']]);
                $upd->execute([$item['qte'], $item['id']]);
            }

            $pdo->commit();
            panier_vider();
            flash('Commande #' . $commande_id . ' passée avec succès ! Merci.', 'success');
            redirect('pages/commandes.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de la commande, réessayez.';
        }
    }
}

$pageTitle = 'Passer la commande — ' . SITE_NAME;
include __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom:36px;">Passer la commande</h1>

<?php foreach ($errors as $err): ?>
<div class="alert alert--error"><?= e($err) ?></div>
<?php endforeach ?>

<form method="post" class="checkout-grid">
  <?= csrf_field() ?>

  <div>
    <!-- Livraison -->
    <div class="card-block">
      <h3>Adresse de livraison</h3>
      <div class="form-group">
        <label>Adresse</label>
        <input type="text" name="adresse" value="<?= e($_POST['adresse'] ?? '') ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Ville</label>
          <input type="text" name="ville" value="<?= e($_POST['ville'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Code postal</label>
          <input type="text" name="code_postal" value="<?= e($_POST['code_postal'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Pays</label>
        <input type="text" name="pays" value="<?= e($_POST['pays'] ?? 'France') ?>">
      </div>
      <div class="form-group">
        <label>Notes (optionnel)</label>
        <textarea name="notes"><?= e($_POST['notes'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Paiement simulé -->
    <div class="card-block">
      <h3>💳 Paiement (simulation)</h3>
      <p style="font-size:13px;color:var(--slate);margin-bottom:16px;">Aucune vraie transaction n'est effectuée. Utilisez 4242 4242 4242 4242.</p>
      <div class="form-group">
        <label>Numéro de carte</label>
        <input id="cc_number" type="text" name="cc_number" placeholder="1234 5678 9012 3456"
               maxlength="19" value="<?= e($_POST['cc_number'] ?? '') ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Expiration (MM/AA)</label>
          <input id="cc_exp" type="text" name="cc_exp" placeholder="12/27"
                 maxlength="5" value="<?= e($_POST['cc_exp'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>CVV</label>
          <input type="text" name="cc_cvv" placeholder="123" maxlength="4"
                 value="<?= e($_POST['cc_cvv'] ?? '') ?>" required>
        </div>
      </div>
    </div>
  </div>

  <!-- Résumé -->
  <div>
    <div class="card-block" style="position:sticky;top:90px;">
      <h3>Résumé</h3>
      <?php foreach ($panier as $item): ?>
      <div class="order-summary-line">
        <span><?= e($item['nom']) ?> × <?= $item['qte'] ?></span>
        <span><?= format_prix($item['prix'] * $item['qte']) ?></span>
      </div>
      <?php endforeach ?>
      <div class="order-summary-line order-total" style="font-weight:600;">
        <span>Total</span><span><?= format_prix($total) ?></span>
      </div>
      <button class="btn btn--full" style="margin-top:20px;" type="submit">Confirmer la commande →</button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
