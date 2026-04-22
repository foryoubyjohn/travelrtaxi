-- ============================================================
-- Travelr Taxi & Tours Services - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS travelr_taxi
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE travelr_taxi;

-- ============================================================
-- USERS TABLE (admin, customer, driver)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','customer','driver') NOT NULL DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DRIVERS TABLE (extends users)
-- ============================================================
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_number VARCHAR(100),
    license_expiry DATE,
    vehicle_id INT DEFAULT NULL,
    status ENUM('available','on_trip','offline') DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 5.00,
    total_trips INT DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- VEHICLES TABLE
-- ============================================================
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('sedan','van','minibus') NOT NULL DEFAULT 'sedan',
    capacity INT NOT NULL DEFAULT 4,
    plate_number VARCHAR(30),
    color VARCHAR(50),
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','maintenance','retired') DEFAULT 'active',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Add FK from drivers to vehicles
ALTER TABLE drivers ADD FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL;

-- ============================================================
-- ROUTES TABLE (predefined routes for flat-rate pricing)
-- ============================================================
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    distance_km DECIMAL(10,2) DEFAULT NULL,
    estimated_time_min INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- PRICING RULES TABLE
-- ============================================================
CREATE TABLE pricing_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('flat','distance','rideshare','hourly') NOT NULL DEFAULT 'flat',
    route_id INT DEFAULT NULL,
    base_fare DECIMAL(10,2) DEFAULT 0.00,
    per_km_rate DECIMAL(10,2) DEFAULT 0.00,
    per_minute_rate DECIMAL(10,2) DEFAULT 0.00,
    per_hour_rate DECIMAL(10,2) DEFAULT 0.00,
    flat_price DECIMAL(10,2) DEFAULT 0.00,
    vehicle_type ENUM('sedan','van','minibus','all') DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- BOOKINGS TABLE
-- ============================================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT DEFAULT NULL,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(30) NOT NULL,
    pickup_location VARCHAR(500) NOT NULL,
    dropoff_location VARCHAR(500) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    passengers INT NOT NULL DEFAULT 1,
    service_type ENUM('standard','tour','hourly','airport') NOT NULL DEFAULT 'standard',
    vehicle_type ENUM('sedan','van','minibus','any') DEFAULT 'any',
    vehicle_id INT DEFAULT NULL,
    driver_id INT DEFAULT NULL,
    pricing_rule_id INT DEFAULT NULL,
    estimated_price DECIMAL(10,2) DEFAULT 0.00,
    final_price DECIMAL(10,2) DEFAULT NULL,
    status ENUM('pending','confirmed','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    payment_method ENUM('cash','stripe','square','pay_later') DEFAULT 'cash',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (pricing_rule_id) REFERENCES pricing_rules(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PAYMENTS TABLE
-- ============================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'JMD',
    method ENUM('cash','stripe','square','pay_later') NOT NULL DEFAULT 'cash',
    transaction_id VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TESTIMONIALS TABLE
-- ============================================================
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    rating INT DEFAULT 5,
    message TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SETTINGS TABLE (key-value store for site settings)
-- ============================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CONTACT MESSAGES TABLE
-- ============================================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(30),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES
('Admin', 'Travelr', 'admin@travelrtaxi.com', '876-926-1438', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Travelr Taxi & Tours Services'),
('tagline', 'The Affordable Way To Travel'),
('phone', '876-926-1438'),
('whatsapp', '876-512-2324'),
('email', 'info@travelrtaxi.com'),
('currency', 'JMD'),
('currency_symbol', '$'),
('address', 'Kingston, Jamaica'),
('facebook', ''),
('instagram', ''),
('twitter', '');

-- Sample routes
INSERT INTO routes (origin, destination, distance_km, estimated_time_min) VALUES
('Norman Manley International Airport', 'Kingston City Centre', 22.0, 35),
('Norman Manley International Airport', 'Portmore', 35.0, 45),
('Kingston', 'Spanish Town', 20.0, 30),
('Kingston', 'Old Harbour', 50.0, 55),
('Kingston', 'Ocho Rios', 90.0, 120),
('Kingston', 'Montego Bay', 185.0, 240),
('Kingston', 'Negril', 250.0, 300),
('Portmore', 'Spanish Town', 15.0, 20),
('Portmore', 'Kingston', 18.0, 25);

-- Sample pricing rules
INSERT INTO pricing_rules (name, type, route_id, base_fare, per_km_rate, flat_price, vehicle_type) VALUES
('Airport to Kingston (Sedan)', 'flat', 1, 0, 0, 3500.00, 'sedan'),
('Airport to Kingston (Van)', 'flat', 1, 0, 0, 5000.00, 'van'),
('Airport to Portmore (Sedan)', 'flat', 2, 0, 0, 4500.00, 'sedan'),
('Kingston to Spanish Town', 'flat', 3, 0, 0, 2500.00, 'sedan'),
('Kingston to Old Harbour', 'flat', 4, 0, 0, 5000.00, 'sedan'),
('Standard Ride (Per KM)', 'distance', NULL, 500.00, 120.00, 0, 'all'),
('Rideshare Rate', 'rideshare', NULL, 350.00, 100.00, 0, 'sedan'),
('Hourly Tour Rate', 'hourly', NULL, 0, 0, 0, 'all');
UPDATE pricing_rules SET per_hour_rate = 4000.00 WHERE name = 'Hourly Tour Rate';
UPDATE pricing_rules SET per_minute_rate = 15.00 WHERE name = 'Rideshare Rate';

-- Sample vehicles
INSERT INTO vehicles (name, type, capacity, plate_number, color, status) VALUES
('Toyota Corolla 2023', 'sedan', 4, 'KN-1234', 'White', 'active'),
('Toyota Axio 2022', 'sedan', 4, 'KN-2345', 'Silver', 'active'),
('Honda Fit 2023', 'sedan', 4, 'KN-3456', 'Black', 'active'),
('Nissan Sylphy 2022', 'sedan', 4, 'KN-4567', 'White', 'active'),
('Toyota Noah 2023', 'van', 7, 'KN-5678', 'White', 'active'),
('Toyota Voxy 2022', 'van', 7, 'KN-6789', 'Silver', 'active'),
('Nissan Serena 2023', 'van', 7, 'KN-7890', 'White', 'active'),
('Toyota HiAce 2022', 'minibus', 14, 'KN-8901', 'White', 'active'),
('Toyota Coaster 2021', 'minibus', 25, 'KN-9012', 'White', 'active');

-- Sample testimonials
INSERT INTO testimonials (customer_name, location, rating, message, is_approved) VALUES
('Marcia Brown', 'Kingston', 5, 'Excellent service! The driver was on time and very professional. Best taxi service in Kingston!', 1),
('David Thompson', 'Portmore', 5, 'I use Travelr Taxi every week for my commute. Reliable and affordable. Highly recommend!', 1),
('Sarah Williams', 'Spanish Town', 4, 'Great tour experience around Jamaica. The driver was knowledgeable and friendly.', 1),
('Michael Johnson', 'Old Harbour', 5, 'Airport pickup was smooth and hassle-free. Will definitely use again!', 1),
('Karen Mitchell', 'Kingston', 5, 'The WhatsApp booking is so convenient! Love the quick response time.', 1);

-- Sample drivers
INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES
('Marcus', 'Williams', 'marcus@travelrtaxi.com', '876-555-0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver'),
('Andre', 'Campbell', 'andre@travelrtaxi.com', '876-555-0002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver'),
('Kevin', 'Stewart', 'kevin@travelrtaxi.com', '876-555-0003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver');

INSERT INTO drivers (user_id, license_number, license_expiry, vehicle_id, status) VALUES
(2, 'JM-DL-12345', '2027-06-15', 1, 'available'),
(3, 'JM-DL-23456', '2027-08-20', 5, 'available'),
(4, 'JM-DL-34567', '2027-03-10', 8, 'available');
