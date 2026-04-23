-- ============================================================
-- Travelr Taxi - Real-Time & GPS Tracking Migration
-- Run AFTER all previous migrations have been applied
-- ============================================================

USE agmsxxte_travelrtaxi;

-- ============================================================
-- 1. Driver Locations (GPS history + current position)
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    accuracy DECIMAL(8,2) DEFAULT NULL,
    speed DECIMAL(8,2) DEFAULT NULL,
    heading DECIMAL(6,2) DEFAULT NULL,
    is_current TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_driver_current (driver_id, is_current, created_at),
    INDEX idx_booking_loc (booking_id, created_at),
    INDEX idx_current_active (is_current, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 2. Extend drivers table: GPS tracking fields
-- ============================================================
ALTER TABLE drivers
    ADD COLUMN location_sharing TINYINT(1) DEFAULT 0 AFTER availability,
    ADD COLUMN last_latitude DECIMAL(10,7) DEFAULT NULL AFTER location_sharing,
    ADD COLUMN last_longitude DECIMAL(10,7) DEFAULT NULL AFTER last_latitude,
    ADD COLUMN last_location_at DATETIME DEFAULT NULL AFTER last_longitude;

-- ============================================================
-- 3. Extend bookings table: tracking token for customer view
-- ============================================================
ALTER TABLE bookings
    ADD COLUMN tracking_token VARCHAR(64) DEFAULT NULL AFTER dispatcher_notes,
    ADD COLUMN tracking_enabled TINYINT(1) DEFAULT 1 AFTER tracking_token;

-- Add index for fast token lookup
ALTER TABLE bookings ADD INDEX idx_tracking_token (tracking_token);

-- ============================================================
-- 4. Generate tracking tokens for existing bookings
-- ============================================================
-- New bookings will get tokens automatically via PHP code.
-- For existing bookings, run this one-time update:
UPDATE bookings SET tracking_token = CONCAT(
    LOWER(HEX(RANDOM_BYTES(16))),
    LOWER(HEX(RANDOM_BYTES(16)))
) WHERE tracking_token IS NULL;

-- ============================================================
-- 5. Sync checkpoints table (lightweight change-detection)
-- ============================================================
CREATE TABLE IF NOT EXISTS sync_checkpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('booking','driver','location') NOT NULL,
    entity_id INT NOT NULL,
    last_changed_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    change_hash VARCHAR(32) DEFAULT NULL,
    UNIQUE KEY uk_entity (entity_type, entity_id),
    INDEX idx_changed (entity_type, last_changed_at)
) ENGINE=InnoDB;

-- ============================================================
-- Done. Real-time tracking tables ready.
-- ============================================================
