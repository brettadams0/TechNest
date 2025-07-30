-- db_seed.sql
-- T05: Populate TechNest database with sample data

-- Insert themes
INSERT INTO themes (name, css_file, is_active) VALUES
('Regular', 'theme-regular.css', TRUE),
('Holiday', 'theme-holiday.css', FALSE),
('Minimal', 'theme-minimal.css', FALSE);

-- Insert users
INSERT INTO users (username, email, password_hash, is_active) VALUES
('admin', 'admin@technest.com', '$2a$12$SK41ruYMGrpgJ.O9iDqRNO9sihZHyGs7EGI6AlFFiHpQIc3WpPlNa', TRUE),
('adams63', 'adams63@uwindsor.ca', '$2a$12$SK41ruYMGrpgJ.O9iDqRNO9sihZHyGs7EGI6AlFFiHpQIc3WpPlNa', TRUE);

-- Make admin user
INSERT INTO admin (user_id) VALUES (1);

-- Insert products
INSERT INTO products (name, description, image_url, price) VALUES
('iPhone 15 Pro', 'Latest Apple smartphone with advanced features.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 1199.99),
('Samsung Galaxy S23', 'Flagship Android phone with stunning display.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 1099.99),
('MacBook Air M2', 'Ultra-light laptop with Apple Silicon.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 1299.99),
('Dell XPS 13', 'Premium Windows ultrabook.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 999.99),
('Sony WH-1000XM5', 'Industry-leading noise-cancelling headphones.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 399.99),
('iPad Pro', 'High-performance tablet for work and play.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 899.99),
('Kindle Paperwhite', 'E-reader with high-res display.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 149.99),
('GoPro Hero 11', 'Action camera for adventure.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 499.99),
('Apple Watch Series 9', 'Smartwatch with health tracking.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 499.99),
('Fitbit Charge 6', 'Fitness tracker with heart rate monitor.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 129.99),
('Nintendo Switch OLED', 'Hybrid gaming console.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 349.99),
('Xbox Series X', 'Next-gen gaming console.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 499.99),
('Logitech MX Master 3S', 'Advanced wireless mouse.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 99.99),
('Razer BlackWidow V4', 'Mechanical gaming keyboard.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 179.99),
('Canon EOS R10', 'Mirrorless camera for creators.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 1099.99),
('Bose SoundLink Flex', 'Portable Bluetooth speaker.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 149.99),
('Anker PowerCore 20000', 'High-capacity portable charger.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 59.99),
('WD My Passport 2TB', 'Portable external hard drive.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9', 89.99),
('Google Nest Hub', 'Smart display for your home.', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8', 99.99),
('TP-Link Archer AX50', 'Wi-Fi 6 router for fast home internet.', 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308', 129.99);

-- Insert product options (2+ per product)
INSERT INTO product_options (product_id, option_name, option_value, extra_price) VALUES
-- iPhone 15 Pro
(1, 'Color', 'Silver', 0.00),
(1, 'Color', 'Space Black', 0.00),
(1, 'Storage', '128GB', 0.00),
(1, 'Storage', '256GB', 100.00),
-- Samsung Galaxy S23
(2, 'Color', 'Phantom Black', 0.00),
(2, 'Color', 'Cream', 0.00),
(2, 'Storage', '128GB', 0.00),
(2, 'Storage', '256GB', 100.00),
-- MacBook Air M2
(3, 'Color', 'Starlight', 0.00),
(3, 'Color', 'Midnight', 0.00),
(3, 'Storage', '256GB', 0.00),
(3, 'Storage', '512GB', 200.00),
-- Dell XPS 13
(4, 'Color', 'Platinum', 0.00),
(4, 'Color', 'Frost', 0.00),
(4, 'Storage', '256GB', 0.00),
(4, 'Storage', '512GB', 200.00),
-- Sony WH-1000XM5
(5, 'Color', 'Black', 0.00),
(5, 'Color', 'Silver', 0.00),
(5, 'Warranty', '1 Year', 0.00),
(5, 'Warranty', '2 Years', 49.99),
-- iPad Pro
(6, 'Color', 'Space Gray', 0.00),
(6, 'Color', 'Silver', 0.00),
(6, 'Storage', '128GB', 0.00),
(6, 'Storage', '256GB', 100.00),
-- Kindle Paperwhite
(7, 'Color', 'Black', 0.00),
(7, 'Color', 'White', 0.00),
(7, 'Storage', '8GB', 0.00),
(7, 'Storage', '16GB', 30.00),
-- GoPro Hero 11
(8, 'Bundle', 'Standard', 0.00),
(8, 'Bundle', 'Adventure', 50.00),
(8, 'Warranty', '1 Year', 0.00),
(8, 'Warranty', '2 Years', 39.99),
-- Apple Watch Series 9
(9, 'Color', 'Midnight', 0.00),
(9, 'Color', 'Starlight', 0.00),
(9, 'Size', '41mm', 0.00),
(9, 'Size', '45mm', 30.00),
-- Fitbit Charge 6
(10, 'Color', 'Black', 0.00),
(10, 'Color', 'Rose Gold', 0.00),
(10, 'Band', 'Small', 0.00),
(10, 'Band', 'Large', 0.00),
-- Nintendo Switch OLED
(11, 'Color', 'White', 0.00),
(11, 'Color', 'Neon Red/Blue', 0.00),
(11, 'Bundle', 'Standard', 0.00),
(11, 'Bundle', 'Mario Kart', 50.00),
-- Xbox Series X
(12, 'Bundle', 'Standard', 0.00),
(12, 'Bundle', 'Game Pass', 30.00),
(12, 'Warranty', '1 Year', 0.00),
(12, 'Warranty', '2 Years', 49.99),
-- Logitech MX Master 3S
(13, 'Color', 'Graphite', 0.00),
(13, 'Color', 'Pale Gray', 0.00),
(13, 'Warranty', '1 Year', 0.00),
(13, 'Warranty', '2 Years', 19.99),
-- Razer BlackWidow V4
(14, 'Switch', 'Green', 0.00),
(14, 'Switch', 'Yellow', 0.00),
(14, 'Warranty', '1 Year', 0.00),
(14, 'Warranty', '2 Years', 29.99),
-- Canon EOS R10
(15, 'Bundle', 'Body Only', 0.00),
(15, 'Bundle', 'w/ 18-45mm Lens', 200.00),
(15, 'Warranty', '1 Year', 0.00),
(15, 'Warranty', '2 Years', 99.99),
-- Bose SoundLink Flex
(16, 'Color', 'Black', 0.00),
(16, 'Color', 'White Smoke', 0.00),
(16, 'Warranty', '1 Year', 0.00),
(16, 'Warranty', '2 Years', 19.99),
-- Anker PowerCore 20000
(17, 'Color', 'Black', 0.00),
(17, 'Color', 'White', 0.00),
(17, 'Warranty', '1 Year', 0.00),
(17, 'Warranty', '2 Years', 9.99),
-- WD My Passport 2TB
(18, 'Color', 'Blue', 0.00),
(18, 'Color', 'Red', 0.00),
(18, 'Warranty', '1 Year', 0.00),
(18, 'Warranty', '2 Years', 14.99),
-- Google Nest Hub
(19, 'Color', 'Chalk', 0.00),
(19, 'Color', 'Charcoal', 0.00),
(19, 'Warranty', '1 Year', 0.00),
(19, 'Warranty', '2 Years', 19.99),
-- TP-Link Archer AX50
(20, 'Color', 'Black', 0.00),
(20, 'Warranty', '1 Year', 0.00),
(20, 'Warranty', '2 Years', 14.99); 