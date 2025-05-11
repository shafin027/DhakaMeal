-- Create the database if it doesn't exist, and use it
CREATE DATABASE IF NOT EXISTS dhakameal;
USE dhakameal;

SET FOREIGN_KEY_CHECKS = 0;

-- Existing Tables (unchanged)
CREATE TABLE Person (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('customer', 'restaurant', 'delivery_person') NOT NULL
);

CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    address TEXT NOT NULL,
    FOREIGN KEY (person_id) REFERENCES Person(person_id) ON DELETE CASCADE
);

CREATE TABLE Restaurant (
    restaurant_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    image_url VARCHAR(255),
    FOREIGN KEY (person_id) REFERENCES Person(person_id) ON DELETE CASCADE
);

CREATE TABLE Food (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_qty INT NOT NULL,
    image_url VARCHAR(255),
    category ENUM('Starters', 'Main Course', 'Desserts') NOT NULL,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE
);

-- Updated DeliveryPerson table
CREATE TABLE DeliveryPerson (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    vehicle_type VARCHAR(50),
    availability TINYINT(1) DEFAULT 1,
    FOREIGN KEY (person_id) REFERENCES Person(person_id) ON DELETE CASCADE
);

-- Updated Order table
CREATE TABLE `Order` (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_person_id INT,
    order_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('pending', 'picked_up', 'out_for_delivery', 'delivered', 'canceled') NOT NULL DEFAULT 'pending',
    restaurant_status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cod', 'online') NOT NULL,
    payment_status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    total_price DECIMAL(10, 2) NOT NULL,
    delivery_proof VARCHAR(255),
    delivery_time DATETIME,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_person_id) REFERENCES DeliveryPerson(delivery_id) ON DELETE SET NULL
);

CREATE TABLE OrderItem (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES Food(food_id) ON DELETE CASCADE
);

CREATE TABLE OrderReview (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    restaurant_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE
);

-- New Tables for Additional Functionality
CREATE TABLE Promotion (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT,
    food_id INT,
    code VARCHAR(50) UNIQUE,
    description TEXT,
    discount_percentage DECIMAL(5, 2) NOT NULL CHECK (discount_percentage BETWEEN 0 AND 100),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES Food(food_id) ON DELETE CASCADE
);

CREATE TABLE OrderTracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'picked_up', 'out_for_delivery', 'delivered', 'canceled') NOT NULL,
    status_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE
);

CREATE TABLE FoodReview (
    food_review_id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    customer_id INT NOT NULL,
    order_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anonymous BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (food_id) REFERENCES Food(food_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE
);

CREATE TABLE RestaurantReview (
    restaurant_review_id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anonymous BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

-- Insert Sample Data
INSERT INTO Person (name, email, password, phone, user_type) VALUES
('Customer One', 'customer1@example.com', 'customer123', '1234567890', 'customer'),
('Restaurant One', 'restaurant1@example.com', 'restaurant123', '0987654321', 'restaurant'),
('Delivery One', 'delivery1@example.com', 'delivery123', '1122334455', 'delivery_person');

INSERT INTO Customer (person_id, address) VALUES
((SELECT person_id FROM Person WHERE email = 'customer1@example.com'), '123 Main St, Dhaka');

INSERT INTO Restaurant (person_id, name, description, address, image_url) VALUES
((SELECT person_id FROM Person WHERE email = 'restaurant1@example.com'), 'DhakaMeal', 'Authentic Bangladeshi cuisine', '789 Food Lane, Dhaka', 'images/dhakameal.jpg');

INSERT INTO Food (restaurant_id, name, description, price, stock_qty, image_url, category) VALUES
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'Chicken Burger', 'Juicy chicken patty with fresh veggies', 320.00, 50, 'images/chicken_burger.jpg', 'Main Course'),
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'Vegetable Samosa', 'Crispy pastry with spiced veggies', 80.00, 100, 'images/vegetable_samosa.jpg', 'Starters'),
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'Chicken Biryani', 'Fragrant rice with tender chicken', 270.00, 40, 'images/chicken_biryani.jpg', 'Main Course'),
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'Paneer Tikka', 'Grilled paneer with spicy marinade', 360.00, 30, 'images/paneer_tikka.jpg', 'Main Course'),
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'Rasgulla', 'Sweet syrupy cottage cheese balls', 150.00, 60, 'images/rasgulla.jpg', 'Desserts');

INSERT INTO DeliveryPerson (person_id, vehicle_type, availability) VALUES
((SELECT person_id FROM Person WHERE email = 'delivery1@example.com'), 'Motorcycle', 1);

-- Sample Order from customer1 to DhakaMeal, pending for restaurant to accept
INSERT INTO `Order` (customer_id, restaurant_id, delivery_person_id, order_time, order_status, restaurant_status, payment_method, payment_status, total_price)
VALUES
((SELECT customer_id FROM Customer WHERE person_id = (SELECT person_id FROM Person WHERE email = 'customer1@example.com')),
 (SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'),
 NULL,
 NOW(), 'pending', 'pending', 'cod', 'pending', 320.00);

INSERT INTO OrderItem (order_id, food_id, quantity, price)
VALUES
((SELECT order_id FROM `Order` ORDER BY order_id DESC LIMIT 1),
 (SELECT food_id FROM Food WHERE name = 'Chicken Burger'), 1, 320.00);

-- Additional order for history, already in transit
INSERT INTO `Order` (customer_id, restaurant_id, delivery_person_id, order_time, order_status, restaurant_status, payment_method, payment_status, total_price)
VALUES
((SELECT customer_id FROM Customer WHERE person_id = (SELECT person_id FROM Person WHERE email = 'customer1@example.com')),
 (SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'),
 (SELECT delivery_id FROM DeliveryPerson WHERE person_id = (SELECT person_id FROM Person WHERE email = 'delivery1@example.com')),
 '2025-05-08 14:30:00', 'in_transit', 'accepted', 'cod', 'pending', 320.00);

INSERT INTO OrderItem (order_id, food_id, quantity, price)
VALUES
((SELECT order_id FROM `Order` ORDER BY order_id DESC LIMIT 1),
 (SELECT food_id FROM Food WHERE name = 'Chicken Burger'), 1, 320.00);

-- Sample review for the in_transit order (only if the in_transit order exists)
SET @in_transit_order_id = (SELECT order_id FROM `Order` WHERE order_status = 'in_transit' LIMIT 1);
INSERT INTO OrderReview (order_id, customer_id, restaurant_id, rating, comment)
SELECT @in_transit_order_id,
       (SELECT customer_id FROM Customer WHERE person_id = (SELECT person_id FROM Person WHERE email = 'customer1@example.com')),
       (SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'),
       4, 'Great food, waiting for delivery.'
WHERE @in_transit_order_id IS NOT NULL;

-- Insert a sample promotion for DhakaMeal
INSERT INTO Promotion (restaurant_id, code, description, discount_percentage, start_date, end_date)
VALUES
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'), 'FIRSTORDER10', '10% off on your first order', 10.00, '2025-05-01 00:00:00', '2025-12-31 23:59:59');

-- Insert a sample promotion for a specific food item (Chicken Burger)
INSERT INTO Promotion (restaurant_id, food_id, code, description, discount_percentage, start_date, end_date)
VALUES
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'),
 (SELECT food_id FROM Food WHERE name = 'Chicken Burger'),
 'BURGER20', '20% off on Chicken Burger', 20.00, '2025-05-01 00:00:00', '2025-06-30 23:59:59');

-- Insert tracking entries for the in_transit order (only if the in_transit order exists)
INSERT INTO OrderTracking (order_id, status, status_time)
SELECT @in_transit_order_id, 'pending', '2025-05-08 14:30:00'
WHERE @in_transit_order_id IS NOT NULL;

INSERT INTO OrderTracking (order_id, status, status_time)
SELECT @in_transit_order_id, 'picked_up', '2025-05-08 14:45:00'
WHERE @in_transit_order_id IS NOT NULL;

INSERT INTO OrderTracking (order_id, status, status_time)
SELECT @in_transit_order_id, 'out_for_delivery', '2025-05-08 15:00:00'
WHERE @in_transit_order_id IS NOT NULL;

-- Insert tracking entries for the pending order
SET @pending_order_id = (SELECT order_id FROM `Order` WHERE order_status = 'pending' LIMIT 1);
INSERT INTO OrderTracking (order_id, status, status_time)
SELECT @pending_order_id, 'pending', NOW()
WHERE @pending_order_id IS NOT NULL;

-- Insert a food review for the Chicken Burger in the in_transit order (only if the in_transit order exists)
INSERT INTO FoodReview (food_id, customer_id, order_id, rating, comment, anonymous)
SELECT (SELECT food_id FROM Food WHERE name = 'Chicken Burger'),
       (SELECT customer_id FROM Customer WHERE person_id = (SELECT person_id FROM Person WHERE email = 'customer1@example.com')),
       @in_transit_order_id,
       5, 'The Chicken Burger was amazing!', 0
WHERE @in_transit_order_id IS NOT NULL;

-- Insert a restaurant review for DhakaMeal
INSERT INTO RestaurantReview (restaurant_id, customer_id, rating, comment, anonymous)
VALUES
((SELECT restaurant_id FROM Restaurant WHERE name = 'DhakaMeal'),
 (SELECT customer_id FROM Customer WHERE person_id = (SELECT person_id FROM Person WHERE email = 'customer1@example.com')),
 4, 'Great food variety, but the service can be improved.', 0);

SET FOREIGN_KEY_CHECKS = 1;