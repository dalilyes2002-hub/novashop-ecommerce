
DROP DATABASE IF EXISTS ecommerce;
CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(10, 2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    adresse_livraison VARCHAR(255) NOT NULL,
    mode_paiement ENUM('carte', 'livraison') NOT NULL DEFAULT 'livraison',
    statut ENUM('en_attente', 'payee', 'expediee', 'livree', 'annulee')
        NOT NULL DEFAULT 'en_attente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantite INT UNSIGNED NOT NULL,
    prix_unitaire DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO users (prenom, nom, email, telephone, adresse, mot_de_passe, role) VALUES
(
    'Lyes',
    'Admin',
    'lyes.admin@novashop.test',
    '0611111111',
    '10 rue de l Admin, 75001 Paris',
    '$2y$10$64XNVi0kZklHfvTKTsv4auDRID./7vix96st2E/ha6EMchuHii0o6',
    'admin'
),
(
    'Lyes',
    'Client',
    'lyes.client@novashop.test',
    '0622222222',
    '20 avenue du Client, 69001 Lyon',
    '$2y$10$1JQ6/Jt1oxwXMAGQlrZ3eOtnNIXR/H/31jM1Uoj7CBC75hLi2Dvim',
    'client'
);

INSERT INTO categories (nom, description) VALUES
('Ordinateurs', 'PC portables et de bureau'),
('Peripheriques', 'Claviers, souris, casques'),
('Accessoires', 'Sacs, supports, câbles');

INSERT INTO products (category_id, nom, description, prix, stock, image, actif) VALUES
(1, 'PC portable Nova 14', 'Écran 14 pouces, 16 Go RAM, SSD 512 Go', 799.90, 12, 'pc-portable-nova-14.jpg', 1),
(1, 'PC bureau Nova Tower', 'Ryzen 5, 32 Go RAM, SSD 1 To', 999.00, 6, 'pc-bureau-nova-tower.jpg', 1),
(2, 'Clavier mécanique NovaKey', 'Switchs tactiles, rétroéclairage', 89.90, 25, 'clavier-mecanique-novakey.jpg', 1),
(2, 'Souris sans fil NovaClick', 'Capteur 16000 DPI, 2,4 GHz', 39.90, 40, 'souris-sans-fil-novaclick.jpg', 1),
(2, 'Casque NovaSound', 'Bluetooth, réduction de bruit', 129.00, 0, 'casque-novasound.jpg', 1),
(3, 'Sacoche 14 pouces', 'Tissu résistant, compartiment tablette', 34.90, 30, 'sacoche-14-pouces.jpg', 1),
(3, 'Support laptop', 'Aluminium, inclinable', 24.90, 22, 'support-laptop.jpg', 1),
(3, 'Ancien câble USB-A', 'Modèle retiré du catalogue', 9.90, 5, 'ancien-cable-usb-a.jpg', 0);

INSERT INTO orders (user_id, total, adresse_livraison, mode_paiement, statut) VALUES
(2, 129.80, '20 avenue du Client, 69001 Lyon', 'carte', 'payee');

INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire) VALUES
(1, 3, 1, 89.90),
(1, 4, 1, 39.90);
