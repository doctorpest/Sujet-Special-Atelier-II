# Système de Gestion de Flotte d'Employés (FastAPI Backend)

Ce projet fournit une API de tableau de bord administratif robuste construite avec FastAPI, SQLite pour le stockage persistant, et Matplotlib pour l'analyse visuelle.

## Fonctionnalités Clés
- **Authentification JWT sécurisée** avec hachage des mots de passe via Bcrypt.
- **Autorisations RBAC** basées sur les rôles : `admin`, `manager`, et `employee`.
- **Gestion des Documents** : Téléversement et récupération de pièces jointes au format PDF.
- **Reporting** : Extraction de jeux de données ciblés en CSV et génération dynamique de graphiques statistiques au format PNG.
- **Sécurisation machine-to-machine** : Point d'accès externe protégé par clé d'API statique.

---

## Installation et Lancement

### 1. Prérequis
Assurez-vous d'avoir Python 3.10+ installé sur votre machine.

### 2. Configuration de l'environnement
Clonez ou copiez les fichiers dans votre répertoire local, puis installez les dépendances :

```bash
pip install -r requirements.txt