# Travelr Taxi & Tours Services - Website Platform

## Overview
Complete PHP + MySQL booking platform for **Travelr Taxi and Tour Services**, Jamaica's affordable taxi and tour service. Built for deployment on HostGator shared hosting.

---

## Features

### Public Website
- **Home Page** - Hero with booking widget, services preview, fleet categories, testimonials, CTA sections
- **About Page** - Company story, mission/vision/values, team stats
- **Services Page** - Detailed service descriptions (Standard Taxi, Airport Transfers, Tours, Rideshare, Hourly Charter)
- **Fleet Page** - Vehicle gallery with filtering by type (Sedan, Van, Mini Bus)
- **Booking Page** - Multi-step booking form with real-time price estimation
- **Pricing Page** - Route pricing tables, fare calculator, service pricing cards
- **Testimonials Page** - Customer reviews with star ratings + review submission form
- **FAQ Page** - Categorized accordion FAQ
- **Contact Page** - Contact form, phone/WhatsApp/email info, business hours

### Customer Accounts
- Registration & Login
- Booking history dashboard
- Guest booking (no account required)

### Admin Dashboard (`/admin/`)
- **Dashboard** - Stats overview (bookings, revenue, drivers, messages)
- **Bookings** - Full booking management with status updates, driver assignment
- **Booking Detail** - Individual booking view with update capabilities
- **Drivers** - Add/edit/deactivate drivers, assign vehicles
- **Fleet** - Vehicle management (add/edit/retire, image upload)
- **Pricing** - Pricing rules management (flat, distance, hourly, rideshare)
- **Routes** - Route management with distance and time estimates
- **Customers** - Registered customer list + guest booking records
- **Testimonials** - Review approval/rejection/deletion
- **Messages** - Contact form message management
- **Settings** - Site-wide settings (name, phone, WhatsApp, social media, currency)

### Integrations
- **WhatsApp** - Floating WhatsApp button, booking via WhatsApp links
- **Payment Stubs** - Cash, bank transfer, card payment options (ready for gateway integration)
- **AJAX Price Calculator** - Real-time fare estimation API

---

## Tech Stack
- **Backend:** PHP 7.4+ / 8.x (PDO MySQL)
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Fonts:** Google Fonts (Montserrat, Open Sans)
- **Icons:** Font Awesome 6
- **Hosting:** HostGator compatible (shared hosting)

---

## Installation on HostGator

### Step 1: Create MySQL Database
1. Log into **cPanel** → **MySQL Databases**
2. Create a new database (e.g., `travelr_db`)
3. Create a new user (e.g., `travelr_user`) with a strong password
4. Add the user to the database with **ALL PRIVILEGES**

### Step 2: Import Database
1. Go to **cPanel** → **phpMyAdmin**
2. Select your new database
3. Click **Import** tab
4. Upload the `database.sql` file
5. Click **Go** to execute

### Step 3: Upload Files
1. Go to **cPanel** → **File Manager**
2. Navigate to `public_html/` (or your domain's root)
3. Upload all files from this package (maintaining directory structure)
4. Or use FTP client (FileZilla) to upload

### Step 4: Configure Database Connection
1. Open `includes/config.php`
2. Update these values:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_cpanel_prefix_travelr_db');
define('DB_USER', 'your_cpanel_prefix_travelr_user');
define('DB_PASS', 'your_secure_password');
```

### Step 5: Set Permissions
```
chmod 755 /public_html/uploads/
chmod 644 /public_html/.htaccess
chmod 644 /public_html/includes/config.php
```

### Step 6: Access Admin Panel
- URL: `https://yourdomain.com/admin/`
- Default credentials:
  - **Email:** `admin@travelrtaxi.com`
  - **Password:** `admin123`
- **IMPORTANT:** Change the admin password immediately after first login!

---

## File Structure
```
travelr-taxi/
├── admin/                    # Admin dashboard
│   ├── includes/             # Admin header/footer
│   ├── booking-detail.php    # Single booking view
│   ├── bookings.php          # Booking management
│   ├── customers.php         # Customer records
│   ├── drivers.php           # Driver management
│   ├── fleet.php             # Fleet management
│   ├── index.php             # Dashboard home
│   ├── login.php             # Admin login
│   ├── logout.php            # Admin logout
│   ├── messages.php          # Contact messages
│   ├── pricing.php           # Pricing rules
│   ├── routes.php            # Route management
│   ├── settings.php          # Site settings
│   └── testimonials.php      # Review management
├── api/                      # API endpoints
│   └── calculate-price.php   # AJAX price calculator
├── assets/
│   ├── css/
│   │   ├── style.css         # Main stylesheet
│   │   └── admin.css         # Admin stylesheet
│   ├── images/
│   │   ├── fleet/            # Vehicle images
│   │   ├── logo.jpeg         # Company logo
│   │   └── flyer.jpeg        # Promotional flyer
│   └── js/
│       └── main.js           # Main JavaScript
├── includes/
│   ├── config.php            # Database & site config
│   ├── db.php                # Database connection
│   ├── auth.php              # Authentication functions
│   ├── helpers.php           # Helper functions
│   ├── header.php            # Site header
│   └── footer.php            # Site footer
├── uploads/                  # User uploads directory
├── .htaccess                 # Apache config
├── 404.php                   # Error page
├── about.php                 # About page
├── account.php               # Customer account
├── booking.php               # Booking page
├── contact.php               # Contact page
├── database.sql              # Database schema + seed data
├── faq.php                   # FAQ page
├── fleet.php                 # Fleet page
├── index.php                 # Homepage
├── login.php                 # Login/Register
├── logout.php                # Logout handler
├── pricing.php               # Pricing page
├── process-booking.php       # Booking form handler
├── process-contact.php       # Contact form handler
├── services.php              # Services page
├── submit-review.php         # Review form handler
├── testimonials.php          # Testimonials page
└── README.md                 # This file
```

---

## Customization

### Branding Colors (in style.css)
- **Primary Yellow:** `#FFD400`
- **Black:** `#1a1a1a`
- **Green (WhatsApp):** `#25D366`
- **Blue (accent):** `#38bdf8`
- **Red (markers):** `#ef4444`

### Contact Information
Update in `includes/config.php`:
- `SITE_PHONE` - Main phone number
- `WHATSAPP_NUMBER` - WhatsApp number (with country code)
- `SITE_EMAIL` - Email address

Or update via Admin Panel → Settings.

### Adding Payment Gateways
The booking system is ready for payment gateway integration. Look for the `payment_method` field in `booking.php` and `process-booking.php`. Recommended gateways for Jamaica:
- **NCB VISA/Mastercard**
- **Paypal**
- **Stripe** (if available in Jamaica)

---

## Security Notes
- All user inputs are sanitized via `htmlspecialchars()`
- Database queries use PDO prepared statements
- Passwords are hashed with `password_hash()` (bcrypt)
- Session-based authentication
- `.htaccess` blocks access to sensitive files
- CSRF protection recommended for production

---

## Support
- **Phone:** 876.926.1438
- **WhatsApp:** 876-512-2324
- **Service Areas:** Portmore, Kingston, Spanish Town, Old Harbour

*"The Affordable Way To Travel"*
