-- ============================================================
-- Travelr Taxi - Dispatch Command Center Migration
-- Run this AFTER the driver panel migration has been applied
-- ============================================================

USE travelr_taxi;

-- ============================================================
-- 1. Dispatch Notes (dispatcher-specific notes on bookings)
-- ============================================================
CREATE TABLE IF NOT EXISTS dispatch_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    is_priority TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking_notes (booking_id, created_at),
    INDEX idx_admin_notes (admin_id)
) ENGINE=InnoDB;

-- ============================================================
-- 2. Booking Status History (full audit trail of every status change)
-- ============================================================
CREATE TABLE IF NOT EXISTS booking_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_by_role ENUM('admin','dispatcher','driver','system','customer') DEFAULT 'admin',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking_history (booking_id, created_at),
    INDEX idx_status_change (new_status, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 3. Assignment Log (every driver assignment/reassignment)
-- ============================================================
CREATE TABLE IF NOT EXISTS assignment_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_driver_id INT DEFAULT NULL,
    new_driver_id INT DEFAULT NULL,
    assigned_by INT DEFAULT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (old_driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (new_driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking_assign (booking_id, created_at),
    INDEX idx_driver_assign (new_driver_id, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 4. Add dispatcher_notes column to bookings for quick inline notes
-- ============================================================
ALTER TABLE bookings
    ADD COLUMN dispatcher_notes TEXT DEFAULT NULL AFTER trip_completed_at;

-- ============================================================
-- Done. Dispatch tables ready.
-- ============================================================
