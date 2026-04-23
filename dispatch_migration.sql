-- ============================================================
-- Travelr Taxi - Combined Dispatch + Driver Panel Migration
-- Run ONCE against your existing travelr_taxi database.
-- Covers: Dispatch Console + Driver Panel additions.
-- Compatible with MySQL 5.6+ / MariaDB on shared hosting.
-- ============================================================

USE travelr_taxi;

-- ============================================================
-- 1. Add 'dispatcher' role to users table
-- ============================================================
ALTER TABLE users
  MODIFY COLUMN role
    ENUM('admin','customer','driver','dispatcher')
    NOT NULL DEFAULT 'customer';

-- ============================================================
-- 2. Unified bookings.status ENUM
--    Merges dispatch lifecycle + driver action statuses.
--    accepted     = driver confirmed acceptance
--    declined     = driver declined (booking reverts to unassigned)
--    on_the_way   = driver en route to pickup
--    arrived      = driver at pickup location
--    trip_started = trip underway
--    in_progress  = legacy alias for trip_started
--    no_show      = customer no-show
-- ============================================================
ALTER TABLE bookings
  MODIFY COLUMN status
    ENUM(
      'pending','confirmed','assigned',
      'accepted','declined',
      'on_the_way','arrived','trip_started',
      'in_progress','completed','cancelled','no_show'
    )
    NOT NULL DEFAULT 'pending';

-- ============================================================
-- 3. Booking timestamp columns (driver action tracking)
-- ============================================================
ALTER TABLE bookings
  ADD COLUMN driver_accepted_at  DATETIME DEFAULT NULL AFTER notes,
  ADD COLUMN driver_arrived_at   DATETIME DEFAULT NULL AFTER driver_accepted_at,
  ADD COLUMN trip_started_at     DATETIME DEFAULT NULL AFTER driver_arrived_at,
  ADD COLUMN trip_completed_at   DATETIME DEFAULT NULL AFTER trip_started_at;

-- ============================================================
-- 4. Extend drivers table (availability + shift + earnings)
-- ============================================================
ALTER TABLE drivers
  ADD COLUMN availability     ENUM('available','unavailable','off_duty') NOT NULL DEFAULT 'available' AFTER status,
  ADD COLUMN last_location    VARCHAR(255) DEFAULT NULL AFTER availability,
  ADD COLUMN shift_started_at DATETIME DEFAULT NULL AFTER last_location,
  ADD COLUMN shift_ended_at   DATETIME DEFAULT NULL AFTER shift_started_at,
  ADD COLUMN total_earnings   DECIMAL(12,2) DEFAULT 0.00 AFTER total_trips;

-- ============================================================
-- 5. dispatch_notes - Per-booking dispatcher notes
-- ============================================================
CREATE TABLE IF NOT EXISTS dispatch_notes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    booking_id    INT NOT NULL,
    dispatcher_id INT NOT NULL,
    note          TEXT NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)    REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (dispatcher_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 6. booking_status_history - Full status-change audit trail
-- ============================================================
CREATE TABLE IF NOT EXISTS booking_status_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT NOT NULL,
    old_status  VARCHAR(30) DEFAULT NULL,
    new_status  VARCHAR(30) NOT NULL,
    changed_by  INT DEFAULT NULL,
    note        VARCHAR(500) DEFAULT NULL,
    changed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 7. assignment_history - Driver assignment / reassignment log
-- ============================================================
CREATE TABLE IF NOT EXISTS assignment_history (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    booking_id    INT NOT NULL,
    old_driver_id INT DEFAULT NULL,
    new_driver_id INT DEFAULT NULL,
    assigned_by   INT DEFAULT NULL,
    note          VARCHAR(500) DEFAULT NULL,
    assigned_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)    REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (old_driver_id) REFERENCES drivers(id)  ON DELETE SET NULL,
    FOREIGN KEY (new_driver_id) REFERENCES drivers(id)  ON DELETE SET NULL,
    FOREIGN KEY (assigned_by)   REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 8. dispatcher_action_logs - Unified dispatch activity log
-- ============================================================
CREATE TABLE IF NOT EXISTS dispatcher_action_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    dispatcher_id INT DEFAULT NULL,
    booking_id    INT DEFAULT NULL,
    action_type   ENUM('assigned','reassigned','status_changed',
                       'note_added','driver_status_changed') NOT NULL,
    description   TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispatcher_id) REFERENCES users(id)     ON DELETE SET NULL,
    FOREIGN KEY (booking_id)    REFERENCES bookings(id)  ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 9. driver_action_log - Full driver-side audit trail
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_action_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    driver_id  INT NOT NULL,
    booking_id INT DEFAULT NULL,
    action     VARCHAR(50) NOT NULL,
    old_value  VARCHAR(100) DEFAULT NULL,
    new_value  VARCHAR(100) DEFAULT NULL,
    notes      TEXT DEFAULT NULL,
    latitude   DECIMAL(10,7) DEFAULT NULL,
    longitude  DECIMAL(10,7) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id)  REFERENCES drivers(id)  ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_driver_action  (driver_id, action),
    INDEX idx_booking_action (booking_id, action),
    INDEX idx_created        (created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 10. driver_earnings - Per-trip earnings record
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_earnings (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    driver_id         INT NOT NULL,
    booking_id        INT NOT NULL,
    amount            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    commission_rate   DECIMAL(5,2) DEFAULT 0.00,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    net_amount        DECIMAL(10,2) DEFAULT 0.00,
    notes             VARCHAR(255) DEFAULT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id)  REFERENCES drivers(id)  ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_driver_date (driver_id, created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 11. driver_notes - Driver's personal per-ride notes
-- ============================================================
CREATE TABLE IF NOT EXISTS driver_notes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    driver_id  INT NOT NULL,
    booking_id INT DEFAULT NULL,
    note       TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id)  REFERENCES drivers(id)  ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- Done. Run this file ONCE. All tables use IF NOT EXISTS,
-- so re-running is safe (column ADDs may warn if already exist).
-- ============================================================
