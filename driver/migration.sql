-- ============================================================
-- Travelr Taxi - Driver Panel Migration
-- Run this AFTER the original database.sql has been imported
-- ============================================================

USE travelr_taxi;

-- ============================================================
-- 1. Extend drivers table: add availability + shift tracking
-- ============================================================
ALTER TABLE drivers
    ADD COLUMN availability ENUM('available','unavailable','off_duty') NOT NULL DEFAULT 'available' AFTER status,
    ADD COLUMN last_location VARCHAR(255) DEFAULT NULL AFTER availability,
    ADD COLUMN shift_started_at DATETIME DEFAULT NULL AFTER last_location,
    ADD COLUMN shift_ended_at DATETIME DEFAULT NULL AFTER shift_started_at,
    ADD COLUMN total_earnings DECIMAL(12,2) DEFAULT 0.00 AFTER total_trips;

-- ============================================================
-- 2. Extend bookings table: add driver-specific status tracking
-- ============================================================
ALTER TABLE bookings
    MODIFY COLUMN status ENUM(
        'pending','confirmed','assigned',
        'accepted','declined',
        'on_the_way','arrived','trip_started',
        'in_progress','completed','cancelled','no_show'
    ) DEFAULT 'pending';

ALTER TABLE bookings
    ADD COLUMN driver_accepted_at DATETIME DEFAULT NULL AFTER notes,
    ADD COLUMN driver_arrived_at DATETIME DEFAULT NULL AFTER driver_accepted_at,
    ADD COLUMN trip_started_at DATETIME DEFAULT NULL AFTER driver_arrived_at,
    ADD COLUMN trip_completed_at DATETIME DEFAULT NULL AFTER trip_started_at;

-- ============================================================
-- 3. New table: driver_action_log (full audit trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_action_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    old_value VARCHAR(100) DEFAULT NULL,
    new_value VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_driver_action (driver_id, action),
    INDEX idx_booking_action (booking_id, action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 4. New table: driver_earnings (per-trip earnings record)
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    net_amount DECIMAL(10,2) DEFAULT 0.00,
    notes VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_driver_date (driver_id, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 5. New table: driver_notes (driver's personal ride notes)
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    note TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 6. Update existing helpers status badge colors
--    (handled in PHP, no SQL needed)
-- ============================================================

-- ============================================================
-- Done. Driver panel tables ready.
-- ============================================================
