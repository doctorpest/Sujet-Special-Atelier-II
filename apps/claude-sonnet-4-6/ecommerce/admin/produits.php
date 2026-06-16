<?php
$pageTitle = 'Produits';
require_once __DIR__ . '/includes/admin_header.php';

$pdo  = db();
$cats = get_categories();
$errors = [];

// ── Suppression ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    csrf_check();
    $id = (int)$_POST['id'];
    $row = $pdo->prepare('SELECT image FROM produits WHERE id=?');
    $row->execute([$id]);
    $p = $row->fetch();
    if ($p && $p['image']) @unlink(UPLOAD_DIR . $p['image']);
    $pdo->prepare('DELETE FROM produits WHERE id=?')->execute([$id]);
    flash('Produit supprimé.', 'success');
    header('Location: produits.php'); exit;
}

// ── Ajout / Modification ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['ajouter','modifier'])) {
    csrf_check();
    $action = $_POST['action'];
    $id_edit    = (int)($_POST['id'] ?? 0);
    $nom        = trim($_POST['nom'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $prix       = (float)($_POST['prix'] ?? 0);
    $stock      = (int)($_POST['stock'] ?? 0);
    $cat_id     = (int)($_POST['categorie_id'] ?? 0);
    $actif      = isset($_POST['actif']) ? 1 : 0;

    if (!$nom)   $errors[] = 'Nom requis.';
    if ($prix<=0) $errors[] = 'Prix invalide.';
    if (!$cat_id) $errors[] = 'Catégorie requise.';

    $image_name = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $ancien = null;
        if ($id_edit) {
            $r = $pdo->prepare('SELECT image FROM produits WHERE id=?'); $r->execute([$id_edit]);
            $ancien = ($r->fetch())['image'] ?? null;
        }
        $image_name = upload_image($_FILES['image'], $ancien);
        if (!$image_name) $errors[] = 'Image invalide (jpg/png/webp, max 3 Mo).';
    }

    if (empty($errors)) {
        $slug = slugify($nom);
        if ($action === 'ajouter') {
            $stmt = $pdo->prepare('INSERT INTO produits (categorie_id, nom, slug, description, prix, stock, image, actif) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$cat_id, $nom, $slug, $desc, $prix, $stock, $image_name, $actif]);
            flash('Produit ajouté.', 'success');
        } else {
            $set = 'categorie_id=?, nom=?, slug=?, description=?, prix=?, stock=?, actif=?';
            $params = [$cat_id, $nom, $slug, $desc, $prix, $stock, $actif];
            if ($image_name) { $set .= ', image=?'; $params[] = $image_name; }
            $params[] = $id_edit;
            $pdo->prepare("UPDATE produits SET $set WHERE id=?")->execute($params);
            flash('Produit mis à jour.', 'success');
        }
        header('Location: produits.php'); exit;
    }
}

// Produit à éditer
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM produits WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

// Liste
$produits = $pdo->query(
    'SELECT p.*, c.nom AS cat_nom FROM produits p JOIN categories c ON c.id=p.categorie_id ORDER BY p.created_at DESC'
)->fetchAll();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <h1>Produits</h1>
  <a href="produits.php?nouveau=1" class="btn">+ Nouveau produit</a>
</div>

<?php foreach ($errors as $err): ?>
<div class="alert alert--error"><?= e($err) ?></div>
<?php endforeach ?>

<?php if ($edit || isset($_GET['nouveau'])): ?>
<!-- Formulaire -->
<div class="admin-card" style="margin-bottom:36px;">
  <h3 style="margin-bottom:20px;"><?= $edit ? 'Modifier : '.e($edit['nom']) : 'Nouveau produit' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'modifier' : 'ajouter' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif ?>
    <div class="form-row">
      <div class="form-group">
        <label>Nom du produit</label>
        <input type="text" name="nom" value="<?= e($edit['nom'] ?? $_POST['nom'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Catégorie</label>
        <select name="categorie_id" required>
          <option value="">— choisir —</option>
          <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($edit['categorie_id'] ?? $_POST['categorie_id'] ?? 0)==$c['id']?'selected':'' ?>><?= e($c['nom']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"><?= e($edit['description'] ?? $_POST['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Prix (€)</label>
        <input type="number" name="prix" step="0.01" min="0" value="<?= $edit['prix'] ?? $_POST['prix'] ?? '' ?>" required>
      </div>
      <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" min="0" value="<?= $edit['stock'] ?? $_POST['stock'] ?? 0 ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Image du produit</label>
      <?php if ($edit && $edit['image']): ?>
      <img src="<?= produit_image_url($edit['image']) ?>" style="height:80px;border-radius:4px;margin-bottom:8px;">
      <?php endif ?>
      <input type="file" name="image" accept="image/*">
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" id="actif" name="actif" <?= ($edit['actif'] ?? 1) ? 'checked' : '' ?>>
      <label for="actif" style="text-transform:none;letter-spacing:0;font-size:14px;margin:0;">Produit actif (visible en boutique)</label>
    </div>
    <div class="flex-end">
      <a href="produits.php" class="btn btn--outline">Annuler</a>
      <button class="btn" type="submit"><?= $edit ? 'Enregistrer' : 'Ajouter' ?></button>
    </div>
  </form>
</div>
<?php endif ?>

<!-- Table -->
<table class="admin-table">
  <thead><tr><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actif</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($produits as $p): ?>
  <tr>
    <td><img src="<?= produit_image_url($p['image']) ?>" alt=""></td>
    <td><?= e($p['nom']) ?></td>
    <td><?= e($p['cat_nom']) ?></td>
    <td><?= format_prix((float)$p['prix']) ?></td>
    <td><?= $p['stock'] ?></td>
    <td><?= $p['actif'] ? '✅' : '❌' ?></td>
    <td style="white-space:nowrap;">
      <a href="?edit=<?= $p['id'] ?>" class="btn btn--sm btn--outline">Modifier</a>
      <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce produit ?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <button class="btn btn--sm btn--danger">Supprimer</button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
