-- Jewellery Shop — Database Seed Data
-- Run this after the application creates the tables automatically via config/db.php
-- Usage: mysql -u root jewellery_shop < seed.sql

-- Insert sample products
INSERT INTO products (name, price, description, image) VALUES
('Diamond Solitaire Ring', 1299.99, 'A stunning 1-carat diamond set in 18K white gold. Perfect for engagements and special occasions. Comes with a certificate of authenticity.', 'diamond_ring.jpg'),
('Gold Pearl Necklace', 459.00, 'Elegant freshwater pearl necklace strung on a 14K gold chain. Length: 18 inches with a secure lobster clasp.', 'pearl_necklace.jpg'),
('Silver Hoop Earrings', 89.50, 'Classic sterling silver hoop earrings with a polished finish. Diameter: 30mm. Hypoallergenic and lightweight for all-day comfort.', 'silver_hoops.jpg'),
('Ruby Bracelet', 749.00, 'Handcrafted bracelet featuring natural rubies set in 18K rose gold. Each ruby is carefully selected for its rich color and clarity.', 'ruby_bracelet.jpg'),
('Sapphire Pendant', 599.99, 'A deep blue sapphire pendant surrounded by a halo of small diamonds, set in platinum. Chain included.', 'sapphire_pendant.jpg');

-- Insert a demo user (password: 'password123')
-- The password hash below is generated with: password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password) VALUES
('Admin User', 'admin@jewelleryshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');