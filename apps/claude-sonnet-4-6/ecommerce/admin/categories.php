<?php
$pageTitle = 'Catégories';
require_once __DIR__ . '/includes/admin_header.php';
$pdo    = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom  = trim($_POST['nom'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$nom) $errors[] = 'Nom requis.';
        if (empty($errors)) {
            $pdo->prepare('INSERT INTO categories (nom, slug, description) VALUES (?,?,?)')->execute([$nom, slugify($nom), $desc]);
            flash('Catégorie ajoutée.', 'success');
            header('Location: categories.php'); exit;
        }
    } elseif ($action === 'supprimer') {
        $id = (int)$_POST['id'];
        $nb = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE categorie_id=?');
        $nb->execute([$id]);
        if ($nb->fetchColumn() > 0) {
            flash('Impossible de supprimer : des produits y sont rattachés.', 'error');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
            flash('Catégorie supprimée.', 'success');
        }
        header('Location: categories.php'); exit;
    }
}

$cats = $pdo->query('SELECT c.*, COUNT(p.id) AS nb FROM categories c LEFT JOIN produits p ON p.categorie_id=c.id GROUP BY c.id ORDER BY c.nom')->fetchAll();
?>

<h1 style="margin-bottom:24px;">Catégories</h1>
<?php foreach ($errors as $e): ?><div class="alert alert--error"><?= e($e) ?></div><?php endforeach ?>

<div class="admin-card" style="margin-bottom:32px;">
  <h3 style="margin-bottom:16px;">Nouvelle catégorie</h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="ajouter">
    <div class="form-row">
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="nom" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" name="description">
      </div>
    </div>
    <button class="btn" type="submit">Ajouter</button>
  </form>
</div>

<table class="admin-table">
  <thead><tr><th>Nom</th><th>Slug</th><th>Description</th><th>Produits</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): ?>
  <tr>
    <td><?= e($c['nom']) ?></td>
    <td><code><?= e($c['slug']) ?></code></td>
    <td><?= e($c['description']) ?></td>
    <td><?= $c['nb'] ?></td>
    <td>
      <form method="post" onsubmit="return confirm('Supprimer cette catégorie ?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="btn btn--sm btn--danger">Supprimer</button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
