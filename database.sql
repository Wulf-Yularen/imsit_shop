-- создание базы данных
CREATE DATABASE IF NOT EXISTS `imsit-shop` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `imsit-shop`;

-- пользователи
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- категории
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- товары
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    image_main VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- дополнительные фото товаров
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- размеры
CREATE TABLE sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

-- связь товар — размер (остатки)
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- корзина
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- заказы
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT,
    comment TEXT,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending','confirmed','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- позиции заказа (снапшот)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    size_id INT DEFAULT NULL,
    product_name VARCHAR(200) NOT NULL,
    size_name VARCHAR(10) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== начальные данные ==========

-- размеры
INSERT INTO sizes (name) VALUES ('XS'),('S'),('M'),('L'),('XL'),('XXL'),('uni');

-- категории
INSERT INTO categories (name, slug, sort_order) VALUES
    ('Футболки', 'tshirts', 1),
    ('Худи', 'hoodies', 2),
    ('Кепки', 'caps', 3),
    ('Значки', 'badges', 4),
    ('Блокноты', 'notebooks', 5),
    ('Термокружки', 'mugs', 6);

-- администратор (пароль: admin123)
INSERT INTO users (login, email, password_hash, full_name, role) VALUES
    ('admin', 'admin@imsit.ru', '$2y$12$hDNFnsIf0AH8/0uXDQyTce4vXKz1B9jlQrDYbIkylG9C8S.cmaXs.', 'Администратор', 'admin');

-- примеры товаров
INSERT INTO products (category_id, name, description, price, image_main, is_featured, is_active) VALUES
    (1, 'Футболка ИМСИТ Classic', 'Классическая белая футболка с логотипом Академии ИМСИТ. 100% хлопок, комфортный крой.', 1500, 'tshirt.jpg', 1, 1),
    (1, 'Футболка ИМСИТ Dark', 'Чёрная футболка с минималистичным принтом ИМСИТ. Мягкий хлопок премиум-плотности.', 1700, 'tshirt_dark.jpg', 1, 1),
    (2, 'Худи ИМСИТ', 'Тёплое худи с вышитым логотипом ИМСИТ на груди. Флис, капюшон, карман-кенгуру.', 3500, 'hoodie.jpg', 1, 1),
    (3, 'Кепка ИМСИТ', 'Бейсболка с вышитой эмблемой. Регулируемый ремешок, 100% хлопок.', 900, 'cap.jpg', 1, 1),
    (4, 'Значок ИМСИТ', 'Металлический значок с логотипом Академии. Диаметр 25 мм, застёжка-булавка.', 200, 'badge.jpg', 1, 1),
    (5, 'Блокнот ИМСИТ', 'Блокнот А5 с символикой ИМСИТ. 80 листов, линейка, твёрдая обложка.', 450, 'notebook.jpg', 0, 1),
    (6, 'Термокружка ИМСИТ', 'Термокружка 350 мл из нержавеющей стали с гравировкой логотипа. Держит тепло до 6 часов.', 1200, 'mug.jpg', 1, 1);

-- остатки по размерам для одежды
INSERT INTO product_sizes (product_id, size_id, quantity)
SELECT p.id, s.id,
    CASE
        WHEN s.name IN ('M','L','XL') THEN 10
        WHEN s.name IN ('S','XXL') THEN 5
        ELSE 0
    END
FROM products p CROSS JOIN sizes s
WHERE p.category_id IN (1,2,3) AND s.name != 'uni';

-- остатки для аксессуаров (универсальный размер)
INSERT INTO product_sizes (product_id, size_id, quantity)
SELECT p.id, s.id, 50
FROM products p CROSS JOIN sizes s
WHERE p.category_id IN (4,5,6) AND s.name = 'uni';
