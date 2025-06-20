-- Database setup for Article CMS
CREATE DATABASE IF NOT EXISTS article_db;
USE article_db;

CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100),
  `role` ENUM('admin', 'author') DEFAULT 'author',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `categories` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `articles` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) UNIQUE NOT NULL,
  `content` TEXT NOT NULL,
  `image_url` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  `category_id` INTEGER NOT NULL,
  `author_id` INTEGER NOT NULL,
  `published` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
);

CREATE TABLE `article_related` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `article_id` INTEGER NOT NULL,
  `related_article_id` INTEGER NOT NULL,
  FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  FOREIGN KEY (`related_article_id`) REFERENCES `articles` (`id`)
);

-- Insert sample data
INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('author1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'author');

INSERT INTO `categories` (`name`, `description`) VALUES
('Technology', 'Articles about technology and programming'),
('Lifestyle', 'Articles about lifestyle and daily life'),
('Travel', 'Travel guides and experiences'),
('Food', 'Food recipes and restaurant reviews');

INSERT INTO `articles` (`title`, `slug`, `content`, `category_id`, `author_id`, `published`) VALUES
('Getting Started with PHP', 'getting-started-with-php', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 1, 2, 1),
('Best Travel Destinations 2025', 'best-travel-destinations-2025', 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.', 3, 2, 1),
('Healthy Breakfast Ideas', 'healthy-breakfast-ideas', 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 4, 2, 1);
