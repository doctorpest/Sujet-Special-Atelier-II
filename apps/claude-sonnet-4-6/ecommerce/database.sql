-- ============================================================
--  BOUTIQUE EN LIGNE — Script de création de la base MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS boutique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boutique;

-- -----------------------------------------------
--  Catégories de produits
-- -----------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100)  NOT NULL,
    slug        VARCHAR(100)  NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
--  Produits
-- -----------------------------------------------
CREATE TABLE produits (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    nom          VARCHAR(200)   NOT NULL,
    slug         VARCHAR(200)   NOT NULL UNIQUE,
    description  TEXT,
    prix         DECIMAL(10,2)  NOT NULL,
    stock        INT UNSIGNED   NOT NULL DEFAULT 0,
    image        VARCHAR(255)   DEFAULT NULL,
    actif        TINYINT(1)     NOT NULL DEFAULT 1,
    created_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------
--  Clients
-- -----------------------------------------------
CREATE TABLE clients (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prenom     VARCHAR(80)  NOT NULL,
    nom        VARCHAR(80)  NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    telephone  VARCHAR(20)  DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
--  Administrateurs
-- -----------------------------------------------
CREATE TABLE admins (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
--  Commandes
-- -----------------------------------------------
CREATE TABLE commandes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED NOT NULL,
    statut          ENUM('en_attente','confirmee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente',
    total           DECIMAL(10,2) NOT NULL,
    adresse_livraison TEXT         NOT NULL,
    ville           VARCHAR(100)  NOT NULL,
    code_postal     VARCHAR(20)   NOT NULL,
    pays            VARCHAR(80)   NOT NULL DEFAULT 'France',
    notes           TEXT          DEFAULT NULL,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------
--  Lignes de commande
-- -----------------------------------------------
CREATE TABLE commande_lignes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id INT UNSIGNED   NOT NULL,
    produit_id  INT UNSIGNED   NOT NULL,
    nom_produit VARCHAR(200)   NOT NULL,   -- snapshot au moment de l'achat
    prix_unit   DECIMAL(10,2)  NOT NULL,
    quantite    INT UNSIGNED   NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
--  Données de démo
-- ============================================================

-- Catégories
INSERT INTO categories (nom, slug, description) VALUES
('Électronique',  'electronique',  'Appareils et gadgets high-tech'),
('Vêtements',     'vetements',     'Mode homme, femme et enfant'),
('Maison & Déco', 'maison-deco',   'Mobilier et décoration intérieure'),
('Livres',        'livres',        'Romans, essais, BD et magazines'),
('Sport',         'sport',         'Équipement et accessoires sportifs');

-- Produits
INSERT INTO produits (categorie_id, nom, slug, description, prix, stock) VALUES
(1, 'Casque Bluetooth Pro X1',  'casque-bluetooth-pro-x1',  'Son cristallin, autonomie 30 h, réduction de bruit active.',  89.99,  15),
(1, 'Montre connectée FitLife', 'montre-connectee-fitlife', 'Suivi santé complet, GPS, étanche 50 m.',                     149.00, 8),
(1, 'Enceinte portable SoundWave','enceinte-portable-soundwave','360° de son, IP67, 12 h d'autonomie.',                   59.90,  22),
(2, 'T-Shirt Coton Bio Blanc',  't-shirt-coton-bio-blanc',  '100 % coton biologique certifié, coupe unisexe.',             24.90,  50),
(2, 'Veste en Lin Naturel',     'veste-lin-naturel',        'Légère et respirante, parfaite pour l'été.',                  79.00,  12),
(3, 'Lampe de Bureau Bois',     'lampe-bureau-bois',        'Design scandinave, lumière chaude réglable, USB-C.',          45.00,  30),
(3, 'Vase Céramique Artisanal', 'vase-ceramique-artisanal', 'Fait main, glaçure unique sur chaque pièce.',                 35.50,  18),
(4, 'L'Art de la Simplicité',   'art-de-la-simplicite',     'Bestseller sur le minimalisme et le bien-être.',              18.00,  40),
(4, 'Atlas du Monde Moderne',   'atlas-monde-moderne',      'Cartes détaillées et infographies, édition 2024.',            34.90,  25),
(5, 'Tapis de Yoga Premium',    'tapis-yoga-premium',       'Antidérapant, épaisseur 6 mm, sangle de transport incluse.',  39.90,  35);

-- Admin par défaut : admin@boutique.fr / Admin1234!
INSERT INTO admins (nom, email, password) VALUES
('Administrateur', 'admin@boutique.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- ↑ hash bcrypt de "Admin1234!" — changez-le via l'interface admin
