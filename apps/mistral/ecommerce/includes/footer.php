<?php
require_once __DIR__ . '/config.php';
?>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Boutique en Ligne</h3>
                <p>Votre destination pour des achats en ligne faciles et sécurisés.</p>
            </div>
            <div class="footer-section">
                <h3>Liens utiles</h3>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>">Accueil</a></li>
                    <li><a href="<?php echo BASE_URL; ?>public/products.php">Produits</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Mon compte</h3>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>public/account.php">Mon profil</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/order_history.php">Mes commandes</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>public/login.php">Connexion</a></li>
                        <li><a href="<?php echo BASE_URL; ?>public/register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Boutique en Ligne. Tous droits réservés.</p>
        </div>
    </footer>

    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>
</html>