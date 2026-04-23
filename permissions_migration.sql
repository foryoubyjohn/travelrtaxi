-- ============================================================
-- Travelr Taxi - Granular Permissions Migration
-- Run ONCE against your existing travelr_taxi database.
-- Safe to re-run (uses IF NOT EXISTS / INSERT IGNORE).
-- ============================================================

USE travelr_taxi;

-- ============================================================
-- 1. Ensure dispatcher role exists in users table
-- ============================================================
ALTER TABLE users
  MODIFY COLUMN role
    ENUM('admin','customer','driver','dispatcher')
    NOT NULL DEFAULT 'customer';

-- ============================================================
-- 2. booking_status_history: add changed_by_role column
--    (dispatch_migration.sql did not include this column)
--    "Duplicate column name" is treated as harmless on re-run.
-- ============================================================
ALTER TABLE booking_status_history
  ADD COLUMN changed_by_role
    ENUM('admin','dispatcher','driver','system','customer')
    DEFAULT 'admin';

-- admin/api/dispatch.php inserts into 'notes' (plural); the table
-- created by dispatch_migration.sql only has 'note' (singular).
-- Add 'notes' so the dispatch API does not error on status changes.
ALTER TABLE booking_status_history
  ADD COLUMN notes TEXT DEFAULT NULL;

-- ============================================================
-- 3. Reconcile dispatch_notes schema divergence.
--    Each ALTER is separate so one "Duplicate column" skip does not
--    block the other. MySQL 5.x does not support ADD COLUMN IF NOT EXISTS.
-- ============================================================
ALTER TABLE dispatch_notes ADD COLUMN admin_id INT DEFAULT NULL;

ALTER TABLE dispatch_notes ADD COLUMN dispatcher_id INT DEFAULT NULL;

-- ============================================================
-- 4. bookings: tracking_token for the public ride-tracking page.
--    Split into separate ALTERs for the same reason as above.
-- ============================================================
ALTER TABLE bookings ADD COLUMN tracking_token VARCHAR(64) DEFAULT NULL AFTER notes;

ALTER TABLE bookings ADD COLUMN tracking_enabled TINYINT(1) DEFAULT 1;

-- Plain CREATE INDEX — "Duplicate key name" is treated as harmless on re-run.
CREATE INDEX idx_tracking_token ON bookings (tracking_token);

-- ============================================================
-- 4. Named permissions catalogue
-- ============================================================
CREATE TABLE IF NOT EXISTS permissions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 4. Role → permission mappings
-- ============================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    role            ENUM('admin','customer','driver','dispatcher') NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_perm (role, permission_name),
    FOREIGN KEY (permission_name) REFERENCES permissions(name) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- 5. Seed permission definitions
-- ============================================================
INSERT IGNORE INTO permissions (name, description) VALUES
  ('view_dashboard',    'View the admin dashboard and summary stats'),
  ('view_bookings',     'View the bookings list and booking details'),
  ('edit_bookings',     'Create, edit, cancel, and manage bookings'),
  ('view_drivers',      'View driver profiles and status'),
  ('manage_drivers',    'Add, edit, deactivate, and delete drivers'),
  ('view_fleet',        'View the vehicle fleet'),
  ('manage_fleet',      'Add, edit, and retire vehicles'),
  ('view_customers',    'View the customer list and profiles'),
  ('manage_customers',  'Edit or deactivate customer accounts'),
  ('view_pricing',      'View pricing rules and tariffs'),
  ('manage_pricing',    'Edit pricing rules and tariffs'),
  ('view_routes',       'View route definitions'),
  ('manage_routes',     'Add, edit, and delete routes'),
  ('view_reviews',      'View customer testimonials and reviews'),
  ('manage_reviews',    'Approve, edit, or remove reviews'),
  ('view_messages',     'View contact form messages'),
  ('manage_messages',   'Reply to or delete messages'),
  ('view_settings',     'View site and system settings'),
  ('manage_settings',   'Edit site and system settings'),
  ('dispatch',          'Access the Dispatch Center'),
  ('run_migrations',    'Execute database migration scripts');

-- ============================================================
-- 6. Seed role → permission assignments
-- ============================================================

-- Admin: all permissions (wildcard stored as '*' in PHP; DB gets every row)
INSERT IGNORE INTO role_permissions (role, permission_name)
  SELECT 'admin', name FROM permissions;

-- Dispatcher: dispatch-related access only
INSERT IGNORE INTO role_permissions (role, permission_name) VALUES
  ('dispatcher', 'dispatch'),
  ('dispatcher', 'view_bookings'),
  ('dispatcher', 'edit_bookings'),
  ('dispatcher', 'view_drivers'),
  ('dispatcher', 'view_fleet');

-- Driver and customer rows intentionally empty (driver panel / customer area
-- are controlled by separate guards, not this permissions table).

-- ============================================================
-- Done. Run this file once; re-running is safe.
-- ============================================================
