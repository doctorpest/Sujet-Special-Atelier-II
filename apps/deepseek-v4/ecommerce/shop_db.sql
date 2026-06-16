-- Création de la base de données
CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Table des produits
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT,
    image VARCHAR(255) DEFAULT 'placeholder.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'payée',
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des articles commandés
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertion des catégories de test
INSERT INTO categories (name) VALUES
('Électronique'),
('Vêtements'),
('Maison & Jardin'),
('Livres');

-- Insertion de produits de test
INSERT INTO products (name, description, price, stock, category_id, image) VALUES
('Smartphone XYZ', 'Un smartphone puissant avec écran 6.5"', 299.99, 15, 1, 'phone.jpg'),
('T-shirt coton bio', 'T-shirt confortable en coton biologique', 19.99, 50, 2, 'tshirt.jpg'),
('Lampe de bureau LED', 'Lampe moderne avec variateur', 45.00, 30, 3, 'lamp.jpg'),
('Roman policier', 'Best-seller de l\'année', 12.50, 100, 4, 'book.jpg');

-- Compte administrateur (mot de passe: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$e0NnvUeFfO1q0U4xQqz.OO0kzq3jP1JfQkG0fVvH5v1vPz1.zG8lm', 'admin');
-- Le hash ci-dessus correspond à "admin123" (généré par password_hash)
-- Remarque : en pratique, utilisez password_hash() pour créer le hash.