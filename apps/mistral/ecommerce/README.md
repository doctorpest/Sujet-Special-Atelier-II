# 🛒 Boutique en Ligne - Application PHP/MySQL

Une application complète de boutique en ligne développée en **PHP natif** (sans framework) avec **MySQL** pour la gestion des données.
Cette application permet aux utilisateurs d'acheter des produits en ligne, de gérer leur panier, de passer des commandes, et aux administrateurs de gérer les produits, les commandes et les utilisateurs.

---

---

## 📌 **Fonctionnalités**

### 🛍️ **Pour les clients**
✅ **Catalogue de produits** : Affichage des produits avec images, prix, descriptions et stock.
✅ **Recherche avancée** : Recherche par nom de produit et filtrage par catégorie/prix.
✅ **Gestion de compte** : Inscription, connexion, déconnexion, mise à jour des informations personnelles.
✅ **Panier persistant** : Ajout, suppression, mise à jour des quantités (stocké en session).
✅ **Processus de commande** : Adresse de livraison, choix de la méthode de paiement (simulation de carte bancaire).
✅ **Historique des commandes** : Visualisation des commandes passées avec détails (statut, produits, montant total).

---

### 👨‍💼 **Pour les administrateurs**
✅ **Tableau de bord** : Vue d'ensemble des statistiques (produits, commandes, utilisateurs, revenus).
✅ **Gestion des produits** : Ajout, modification, suppression de produits avec téléchargement d'images.
✅ **Gestion des commandes** : Visualisation des commandes, mise à jour du statut (en attente, en cours, expédiée, livrée, annulée).
✅ **Gestion des catégories** : Création et gestion des catégories de produits.

---

---

## 🛠 **Prérequis**

### **Environnement requis**
   Logiciel | Version minimale | Description |
 |----------|------------------|-------------|
 | **PHP** | 8.0+ | Avec les extensions `mysqli`, `gd`, `fileinfo`, `session`. |
 | **MySQL** | 5.7+ | Base de données pour stocker les produits, utilisateurs, commandes, etc. |
 | **Serveur Web** | Apache/Nginx | Pour exécuter l'application PHP. |
 | **Navigateur** | Moderne (Chrome, Firefox, Edge) | Pour une expérience utilisateur optimale. |

---

### **Extensions PHP requises**
Assurez-vous que les extensions suivantes sont **activées** dans votre fichier `php.ini` :
```ini
extension=mysqli
extension=gd
extension=fileinfo
extension=session