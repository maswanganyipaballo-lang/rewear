-- ReWear Database Setup
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS rewear_db;
USE rewear_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'seller', 'admin') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(500),
    product_description TEXT,
    category VARCHAR(100),
    condition_type ENUM('New', 'Like New', 'Good', 'Fair') DEFAULT 'Good',
    size VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'PayFast',
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);

-- Sample admin user (password: admin123)
INSERT INTO users (full_name, email, password, role) VALUES
('Admin User', 'admin@rewear.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Note: The above password hash is for 'password' (Laravel default).
-- For your own admin, register via admin/setup or update after first run.

-- Sample products (using free Unsplash images)
INSERT INTO products (seller_id, product_name, product_price, product_image, product_description, category, condition_type, size) VALUES
(NULL, 'Vintage Denim Jacket', 350.00, 'https://images.unsplash.com/photo-1601333144130-8cbb312386b6?w=400', 'Classic blue denim jacket in excellent condition. Perfect for casual outings.', 'Jackets', 'Like New', 'M'),
(NULL, 'Floral Summer Dress', 180.00, 'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400', 'Beautiful floral print dress, worn twice. Great for summer days.', 'Dresses', 'Like New', 'S'),
(NULL, 'Leather Ankle Boots', 420.00, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 'Genuine leather boots, barely worn. Slight scuff on left heel.', 'Shoes', 'Good', '7'),
(NULL, 'Wool Overcoat', 650.00, 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?w=400', 'Premium wool overcoat in charcoal grey. Warm and stylish.', 'Coats', 'Good', 'L'),
(NULL, 'Graphic Tee Bundle', 90.00, 'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=400', 'Bundle of 3 graphic tees in good condition. Various designs.', 'Tops', 'Good', 'M'),
(NULL, 'High-waist Jeans', 280.00, 'https://images.unsplash.com/photo-1604176354204-9268737828e4?w=400', 'Trendy high-waist blue jeans. Only worn a few times.', 'Bottoms', 'Like New', '32'),
(NULL, 'Knit Cardigan', 160.00, 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400', 'Cosy cream knit cardigan. Perfect for layering in autumn.', 'Tops', 'Good', 'S'),
(NULL, 'Sneakers (Nike Air)', 550.00, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 'Nike Air Max, good condition. Soles show light wear.', 'Shoes', 'Good', '9');
