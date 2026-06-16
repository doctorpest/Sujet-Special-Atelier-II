  </div><!-- /.container -->
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo-accent">✦</span> <?= SITE_NAME ?>
      <p>Une sélection éclairée, livrée avec soin.</p>
    </div>
    <nav class="footer-nav">
      <h4>Boutique</h4>
      <ul>
        <li><a href="<?= SITE_URL ?>/">Accueil</a></li>
        <li><a href="<?= SITE_URL ?>/pages/catalogue.php">Catalogue</a></li>
        <li><a href="<?= SITE_URL ?>/pages/recherche.php">Recherche</a></li>
      </ul>
    </nav>
    <nav class="footer-nav">
      <h4>Mon compte</h4>
      <ul>
        <?php if (client_logged()): ?>
        <li><a href="<?= SITE_URL ?>/pages/compte.php">Mon profil</a></li>
        <li><a href="<?= SITE_URL ?>/pages/commandes.php">Mes commandes</a></li>
        <li><a href="<?= SITE_URL ?>/pages/logout.php">Déconnexion</a></li>
        <?php else: ?>
        <li><a href="<?= SITE_URL ?>/pages/login.php">Connexion</a></li>
        <li><a href="<?= SITE_URL ?>/pages/register.php">Créer un compte</a></li>
        <?php endif ?>
      </ul>
    </nav>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Tous droits réservés.</p>
  </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
