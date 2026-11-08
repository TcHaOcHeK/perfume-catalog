-- sql/schema.sql — схема базы данных perfume_db
-- Выполнить в MySQL: source sql/schema.sql

CREATE DATABASE IF NOT EXISTS `perfume_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `perfume_db`;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `login` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'editor', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_login` (`login`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица брендов
CREATE TABLE IF NOT EXISTS `brands` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `country` VARCHAR(50),
    `website` VARCHAR(255),
    `logo_path` VARCHAR(255),
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица типов парфюма
CREATE TABLE IF NOT EXISTS `perfume_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица ароматных семейств
CREATE TABLE IF NOT EXISTS `fragrance_families` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица коллекций
CREATE TABLE IF NOT EXISTS `collections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `brand_id` INT UNSIGNED,
    `release_year` YEAR,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица товаров
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `brand_id` INT UNSIGNED NOT NULL,
    `type_id` INT UNSIGNED NOT NULL,
    `family_id` INT UNSIGNED,
    `collection_id` INT UNSIGNED,
    `gender` ENUM('men', 'women', 'unisex') NOT NULL DEFAULT 'unisex',
    `release_date` DATE,
    `volume_ml` INT UNSIGNED,
    `concentration` VARCHAR(50),
    `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_new` BOOLEAN NOT NULL DEFAULT FALSE,
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`type_id`) REFERENCES `perfume_types`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`family_id`) REFERENCES `fragrance_families`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL,
    INDEX `idx_brand` (`brand_id`),
    INDEX `idx_type` (`type_id`),
    INDEX `idx_family` (`family_id`),
    INDEX `idx_gender` (`gender`),
    INDEX `idx_price` (`price`),
    INDEX `idx_featured` (`is_featured`),
    INDEX `idx_new` (`is_new`),
    INDEX `idx_views` (`views`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица изображений товаров
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `is_main` BOOLEAN NOT NULL DEFAULT FALSE,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_main` (`product_id`, `is_main`),
    INDEX `idx_sort` (`product_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица нот ароматов
CREATE TABLE IF NOT EXISTS `notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь товаров и нот (многие-ко-многим с этапами пирамиды)
CREATE TABLE IF NOT EXISTS `product_notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `note_id` INT UNSIGNED NOT NULL,
    `note_stage` ENUM('top', 'heart', 'base') NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_product_note_stage_order` (`product_id`, `note_id`, `note_stage`, `sort_order`),
    INDEX `idx_product_stage` (`product_id`, `note_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Тестовые данные для типов парфюма
INSERT IGNORE INTO `perfume_types` (`name`, `description`) VALUES
('Eau de Parfum', 'Туалетная вода с концентрацией 15-20%'),
('Eau de Toilette', 'Туалетная вода с концентрацией 5-15%'),
('Parfum', 'Парфюм с концентрацией 20-30%'),
('Toilet water', 'Одеколон с концентрацией 3-5%');

-- Тестовые данные для ароматных семейств
INSERT IGNORE INTO `fragrance_families` (`name`, `description`) VALUES
('Floral', 'Цветочные ароматы'),
('Oriental', 'Восточные/амберные ароматы'),
('Woody', 'Древесные ароматы'),
('Fresh', 'Свежие ароматы'),
('Fougère', 'Фужерные ароматы'),
('Chypre', 'Шипровые ароматы'),
('Gourmand', 'Гурманские/съедобные ароматы'),
('Citrus', 'Цитрусовые ароматы');

-- Базовые ноты
INSERT IGNORE INTO `notes` (`name`) VALUES
('Бергамот'), ('Лимон'), ('Апельсин'), ('Мандарин'), ('Грейпфрут'),
('Роза'), ('Жасмин'), ('Ирис'), ('Лаванда'), ('Лиловый'),
('Ваниль'), ('Тонка'), ('Бензоин'), ('Амбра'), ('Мускус'),
('Патчули'), ('Ветвер'), ('Кедр'), ('Сандал'), ('Дубовый мх'),
('Шоколад'), ('Карамель'), ('Мед'), ('Кофе'), ('Коньяк');

-- Пример бренда
INSERT IGNORE INTO `brands` (`id`, `name`, `description`, `country`, `website`) VALUES
(1, 'Maison Lumeria', 'Французский нишевый бренд', 'France', 'https://maison-lumeria.com'),
(2, 'Maurtin', 'Британский парфюмерный дом', 'UK', 'https://maurtin.com'),
(3, 'Nocturne', 'Итальянский бренд', 'Italy', 'https://nocturne.com'),
(4, 'Peak Ecstasy', 'Американский нишевый бренд', 'USA', 'https://peakextasy.com');

-- Пример админа (пароль: admin123)
-- Хэш создан через password_hash('admin123', PASSWORD_DEFAULT)
INSERT IGNORE INTO `users` (`login`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@perfume-catalog.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');