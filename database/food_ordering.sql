CREATE DATABASE IF NOT EXISTS food_ordering;
USE food_ordering;

DROP TABLE IF EXISTS foods;

CREATE TABLE foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(100) NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
  delivery_time VARCHAR(50) NOT NULL,
  badge VARCHAR(50) DEFAULT NULL,
  emoji VARCHAR(20) NOT NULL,
  is_favorite TINYINT(1) NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO foods (category, name, description, price, rating, delivery_time, badge, emoji, is_favorite, is_featured) VALUES
('Burgers', 'Classic Smash Burger', 'Double beef patty, cheddar, pickles, house sauce on a brioche bun.', 1099.00, 4.9, '20–25 min', 'Best Seller', '🍔', 1, 1),
('Pizza', 'Truffle Margherita', 'San Marzano tomatoes, fresh mozzarella, basil, truffle oil, sea salt.', 1349.00, 4.8, '25–35 min', 'New', '🍕', 0, 1),
('Salads', 'Garden Power Bowl', 'Quinoa, avocado, roasted chickpeas, feta, tahini lemon dressing.', 999.00, 4.7, '15–20 min', 'Healthy', '🥗', 0, 1),
('Desserts', 'NY Cheesecake Slice', 'Velvety cream cheese filling on a buttery graham cracker crust, berry compote.', 749.00, 5.0, '10–15 min', 'Popular', '🍰', 1, 1),
('Drinks', 'Mango Smoothie', 'Fresh mango blended with yogurt and ice for a creamy tropical drink.', 399.00, 4.6, '8–12 min', NULL, '🥤', 0, 1),
('Chicken', 'Crispy Chicken Box', 'Crispy fried chicken with seasoned fries and dip.', 899.00, 4.8, '18–25 min', NULL, '🍗', 1, 1);
