# Ma Boutique en Ligne - PHP Vanilla

## Prérequis
- Un serveur local avec PHP (>= 7.4) et MySQL/MariaDB (ex: XAMPP, WAMP, MAMP, ou Laragon).

## Installation
1. Clonez ou placez ce dossier dans le répertoire racine de votre serveur web (ex: `htdocs` ou `www`).
2. Ouvrez PhpMyAdmin (ou tout autre client MySQL) et exécutez le script SQL fourni (`database.sql`) pour créer la base de données `maboutique` et ses tables.
3. Ouvrez le fichier `config/database.php` et ajustez les identifiants de connexion (`$username` et `$password`) selon votre environnement local (souvent `root` avec un mot de passe vide sur XAMPP).
4. Accédez à l'application via votre navigateur : `http://localhost/maboutique/index.php`.