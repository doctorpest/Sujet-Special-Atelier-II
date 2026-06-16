# Boutique en ligne PHP vanilla

Application de boutique en ligne simple développée en PHP sans framework.

## Fonctionnalités

- Catalogue de produits avec catégories, prix, stock et images
- Recherche par nom et filtres par catégorie / prix
- Inscription et connexion client
- Panier persistant en session
- Passage de commande avec adresse de livraison et paiement simulé
- Historique des commandes côté client
- Administration : ajout, modification, suppression de produits et téléversement d’images

## Prérequis

- Serveur web (Apache, Nginx)
- PHP 7.4 ou supérieur avec extensions PDO, MySQL, GD (pour les images)
- MySQL 5.7 ou supérieur

## Installation

1. **Importer la base de données**
   - Créez une base `shop_db` (ou utilisez le script fourni).
   - Importez le fichier `shop_db.sql` :
     ```bash
     mysql -u root -p < shop_db.sql