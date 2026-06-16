<?php
require_once __DIR__ . '/../header.php';
if (!is_admin()) {
    redirect(BASE_URL . '/admin/admin_login.php');
}
?>
<h2>Tableau de bord administrateur</h2>
<ul>
    <li><a href="products.php">Gérer les produits</a></li>
</ul>
<?php require_once __DIR__ . '/../footer.php'; ?>
