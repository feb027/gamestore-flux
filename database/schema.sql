-- Create the database
CREATE DATABASE IF NOT EXISTS game_store;
USE game_store;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    genre VARCHAR(100),
    release_date DATE,
    developer VARCHAR(255),
    publisher VARCHAR(255),
    stock_status ENUM('in_stock', 'out_of_stock', 'coming_soon') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT,
    price_at_time DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Insert sample product data
INSERT INTO products (title, description, price, genre, developer, publisher, release_date) VALUES
('The Adventure Quest', 'An epic adventure game with stunning graphics', 49.99, 'Adventure', 'GameDev Studios', 'GamePublisher Inc', '2024-01-15'),
('Space Explorer', 'Explore the vast universe in this sci-fi game', 39.99, 'Sci-Fi', 'Space Games', 'Universe Publishing', '2024-02-01'),
('Racing Champions', 'Experience high-speed racing action', 29.99, 'Racing', 'Speed Studios', 'Racing Games Ltd', '2024-03-10');

-- Create admin user (password: admin123)
INSERT INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@admin.com', '$2y$10$Zb0.uz.E9j9PCWDGsPj8ZuYQFhRr87kpfcys4r4y3hs9LzhdqYCZG', TRUE);