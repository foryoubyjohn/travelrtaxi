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
-- 2. Ensure dispatcher is in booking_status_history.changed_by_role
--    (ALTER MODIFY is idempotent when the value is already present)
-- ============================================================
ALTER TABLE booking_status_history
  MODIFY COLUMN changed_by_role
    ENUM('admin','dispatcher','driver','system','customer')
    DEFAULT 'admin';

-- ============================================================
-- 3. Reconcile dispatch_notes schema divergence
--    dispatch_migration.sql used 'dispatcher_id'
--    admin/dispatch-migration.sql used 'admin_id'
--    Ensure both columns exist side-by-side.
-- ============================================================
ALTER TABLE dispatch_notes
  ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS dispatcher_id INT DEFAULT NULL;

-- ============================================================
-- 4. Ensure bookings has a tracking_token column
--    (booking.php now writes it on every insert)
-- ============================================================
ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS tracking_token VARCHAR(64) DEFAULT NULL AFTER notes,
  ADD COLUMN IF NOT EXISTS tracking_enabled TINYINT(1) DEFAULT 1 AFTER tracking_token;

-- Index for fast token lookups on the public tracking page
CREATE INDEX IF NOT EXISTS idx_tracking_token ON bookings (tracking_token);

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
