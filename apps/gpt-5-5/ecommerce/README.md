# Boutique PHP vanille + MySQL

Application e-commerce simple sans framework.

## Fonctionnalités

- Catalogue de produits avec catégories, prix, stock et image
- Recherche par nom, catégorie, prix minimum et prix maximum
- Création de compte client et connexion
- Panier persistant en session PHP
- Passage de commande avec adresse de livraison et paiement par carte simulé
- Administration des produits : ajout, modification, suppression
- Téléversement d'images produit par l'administrateur
- Historique des commandes côté client

## Prérequis

- PHP 8+
- MySQL ou MariaDB
- Serveur local : XAMPP, MAMP, Laragon, WAMP ou serveur PHP intégré
- Extension PHP PDO MySQL activée

## Installation

1. Copie le dossier `boutique_php` dans ton serveur local, par exemple `htdocs/boutique_php` avec XAMPP.
2. Crée la base de données en important `database.sql` dans phpMyAdmin ou avec la commande :

```bash
mysql -u root -p < database.sql
```

3. Vérifie les identifiants MySQL dans `config/db.php` :

```php
$host = 'localhost';
$dbname = 'boutique_php';
$user = 'root';
$pass = '';
```

4. Assure-toi que le dossier `uploads/` est accessible en écriture par PHP.
5. Lance l'application dans le navigateur :

```text
http://localhost/boutique_php/index.php
```

Ou avec le serveur intégré PHP depuis le dossier du projet :

```bash
php -S localhost:8000
```

Puis ouvre :

```text
http://localhost:8000
```

## Compte administrateur de démonstration

- Email : `admin@example.com`
- Mot de passe : `admin123`

## Structure

```text
boutique_php/
├── admin/
│   ├── products.php
│   ├── product_form.php
│   └── delete_product.php
├── assets/css/style.css
├── config/
│   ├── db.php
│   └── helpers.php
├── uploads/
├── cart.php
├── checkout.php
├── database.sql
├── footer.php
├── header.php
├── index.php
├── login.php
├── logout.php
├── orders.php
├── register.php
└── README.md
```

## Notes importantes

Le paiement est volontairement simulé : aucune donnée bancaire réelle n'est traitée. Pour un vrai site, il faudrait utiliser un prestataire comme Stripe, PayPal ou équivalent, ajouter une protection CSRF complète, des validations plus strictes, une gestion fine des droits, des logs et un système de configuration par variables d'environnement.
