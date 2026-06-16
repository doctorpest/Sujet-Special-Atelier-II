# ✦ Maison Lumière — Boutique en ligne PHP/MySQL

Application e-commerce complète en PHP vanilla + MySQL. Aucun framework requis.

---

## 📁 Structure du projet

```
boutique/
├── config.php                  ← Configuration globale (DB, URL…)
├── index.php                   ← Page d'accueil
├── database.sql                ← Script de création de la base de données
│
├── includes/
│   ├── db.php                  ← Connexion PDO singleton
│   ├── functions.php           ← Fonctions utilitaires (auth, panier, etc.)
│   ├── header.php              ← En-tête commun boutique
│   └── footer.php              ← Pied de page commun boutique
│
├── pages/
│   ├── catalogue.php           ← Liste produits avec filtres
│   ├── produit.php             ← Fiche produit
│   ├── recherche.php           ← Recherche par nom/catégorie/prix
│   ├── panier.php              ← Panier d'achat
│   ├── panier_action.php       ← Actions panier (ajouter/modifier/supprimer)
│   ├── commande.php            ← Tunnel de commande (livraison + paiement simulé)
│   ├── commandes.php           ← Historique commandes client
│   ├── compte.php              ← Tableau de bord client
│   ├── login.php               ← Connexion client
│   ├── register.php            ← Création de compte
│   └── logout.php              ← Déconnexion
│
├── admin/
│   ├── index.php               ← Tableau de bord admin
│   ├── produits.php            ← CRUD produits + upload image
│   ├── categories.php          ← Gestion des catégories
│   ├── commandes.php           ← Gestion commandes + changement statut
│   ├── clients.php             ← Liste clients
│   ├── login.php               ← Connexion admin
│   └── logout.php              ← Déconnexion admin
│   └── includes/
│       ├── admin_header.php
│       └── admin_footer.php
│
├── assets/
│   ├── css/style.css           ← Feuille de style principale
│   ├── js/main.js              ← Scripts (quantité, masque carte, etc.)
│   └── images/placeholder.svg ← Image par défaut produit
│
└── uploads/                    ← Images uploadées (doit être inscriptible)
```

---

## 🚀 Installation

### Prérequis

- PHP 8.1+ avec extensions : `pdo_mysql`, `mbstring`, `fileinfo`, `intl`
- MySQL 8.0+ (ou MariaDB 10.6+)
- Serveur web : Apache (mod_rewrite) ou Nginx

---

### Étape 1 — Copier les fichiers

Placez le dossier `boutique/` dans la racine de votre serveur web.

```
/var/www/html/boutique/       ← Linux/Apache
C:\xampp\htdocs\boutique\     ← XAMPP Windows
/Applications/MAMP/htdocs/boutique/  ← MAMP macOS
```

---

### Étape 2 — Créer la base de données

Connectez-vous à MySQL et exécutez le script :

```bash
mysql -u root -p < database.sql
```

Ou via phpMyAdmin :
1. Créez une base `boutique`
2. Importez `database.sql`

---

### Étape 3 — Configurer `config.php`

Ouvrez `config.php` et adaptez ces constantes :

```php
define('DB_HOST',  'localhost');
define('DB_NAME',  'boutique');
define('DB_USER',  'root');        // votre utilisateur MySQL
define('DB_PASS',  '');            // votre mot de passe MySQL

define('SITE_URL', 'http://localhost/boutique');  // URL sans slash final
```

---

### Étape 4 — Rendre `uploads/` inscriptible

```bash
chmod 755 uploads/
# Ou en cas de problème de permission :
chmod 775 uploads/
chown www-data:www-data uploads/   # Linux/Apache
```

---

### Étape 5 — Vérifier l'accès

| URL | Description |
|-----|-------------|
| `http://localhost/boutique/` | Boutique (accueil) |
| `http://localhost/boutique/pages/catalogue.php` | Catalogue produits |
| `http://localhost/boutique/admin/` | Interface admin |

---

## 🔑 Identifiants par défaut

### Administrateur
| Champ | Valeur |
|-------|--------|
| Email | `admin@boutique.fr` |
| Mot de passe | `Admin1234!` |

> ⚠️ **Changez ce mot de passe immédiatement** via phpMyAdmin ou en vous connectant à la base :
> ```sql
> UPDATE admins SET password = '$2y$12$...' WHERE email = 'admin@boutique.fr';
> ```
> Générez le hash avec : `php -r "echo password_hash('NouveauMDP', PASSWORD_BCRYPT);"`

---

## ✨ Fonctionnalités

### Boutique (côté client)
- ✅ Catalogue avec pagination (9 produits/page)
- ✅ Filtres : catégorie, prix min/max, tri
- ✅ Recherche full-text par nom et description
- ✅ Fiche produit avec sélecteur de quantité
- ✅ Panier persistant en session (ajout, modification, suppression)
- ✅ Création de compte sécurisée (bcrypt + CSRF)
- ✅ Connexion / déconnexion
- ✅ Tunnel de commande : adresse de livraison + formulaire carte simulé
- ✅ Décrémentation automatique du stock à la commande
- ✅ Historique des commandes avec détail des articles

### Administration
- ✅ Tableau de bord (stats : produits, clients, commandes, CA)
- ✅ CRUD produits complet (ajout/modification/suppression)
- ✅ Upload d'images produit (jpg/png/webp, max 3 Mo)
- ✅ Gestion des catégories
- ✅ Liste et détail des commandes
- ✅ Changement de statut commande (en_attente → confirmée → expédiée → livrée / annulée)
- ✅ Liste clients avec statistiques

---

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` + bcrypt (coût 12)
- Protection CSRF sur tous les formulaires POST
- Requêtes SQL préparées (PDO — zéro injection SQL)
- `htmlspecialchars()` sur tous les affichages
- Cookies de session `httponly` + `samesite=Lax`
- Validation type/taille des images uploadées

---

## 🛠️ Personnalisation rapide

### Changer le nom de la boutique
Dans `config.php` :
```php
define('SITE_NAME', 'Ma Boutique');
```

### Activer HTTPS
Dans `config.php` :
```php
define('SITE_URL', 'https://monsite.com');
```
Et dans `config.php`, activez `'secure' => true` dans `session_set_cookie_params`.

### Passer en mode production
Dans `config.php`, commentez :
```php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
```

---

## 📋 Checklist mise en production

- [ ] Changer le mot de passe admin
- [ ] Mettre à jour `SITE_URL` avec le vrai domaine
- [ ] Activer HTTPS et passer `'secure' => true`
- [ ] Désactiver `display_errors`
- [ ] Vérifier les permissions de `uploads/`
- [ ] Intégrer un vrai prestataire de paiement (Stripe, Mollie…)
- [ ] Configurer les sauvegardes automatiques MySQL

---

## 📝 Notes techniques

- **Session** : Le panier est stocké en session PHP (`$_SESSION['panier']`). Il persiste tant que la session est active (fermeture navigateur ou timeout serveur).
- **Paiement** : Le formulaire carte est purement simulé. Pour la production, intégrez [Stripe](https://stripe.com/docs/payments/accept-a-payment) ou [Mollie](https://docs.mollie.com/).
- **Images** : Stockées dans `uploads/`. En production, envisagez un stockage objet (S3, OVH Object Storage).
- **PHP minimum** : 8.1 (utilisation de `never` return type et syntaxes modernes).
