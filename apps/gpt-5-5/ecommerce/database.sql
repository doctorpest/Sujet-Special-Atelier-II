CREATE DATABASE IF NOT EXISTS boutique_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boutique_php;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('client','admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  shipping_address TEXT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_last4 VARCHAR(4),
  status VARCHAR(50) NOT NULL DEFAULT 'payée',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(190) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

INSERT INTO categories (name) VALUES ('Informatique'), ('Maison'), ('Mode');

INSERT INTO products (category_id, name, description, price, stock, image) VALUES
(1, 'Clavier mécanique', 'Clavier rétroéclairé pour développeurs et gamers.', 79.99, 15, NULL),
(1, 'Souris sans fil', 'Souris ergonomique rechargeable.', 29.90, 40, NULL),
(2, 'Lampe de bureau', 'Lampe LED avec intensité réglable.', 34.50, 25, NULL),
(3, 'Sac à dos', 'Sac pratique avec compartiment ordinateur.', 49.99, 18, NULL);

-- Compte admin par défaut : admin@example.com / admin123
INSERT INTO users (name, email, password, role) VALUES
('Administrateur', 'admin@example.com', '$2y$10$8QwU6Q6P4hXcLEP7B/QZsua5dnxhX3NqZn5ULvXxv0a0EBuJcbqK.', 'admin');
