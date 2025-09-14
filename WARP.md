# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is a **Blood Donation Management System** built with PHP and MySQL. It manages blood donors (students), donation events, blood requests, and administrative functions for educational institutions. The system tracks donor eligibility based on a 90-day donation cycle and includes WhatsApp integration for emergency notifications.

## Development Setup

### Database Setup
```bash
# Import the main database structure and data
mysql -u root -p blood_group_management < database/blood_group_management\ \(1\).sql

# Alternative: Use the simple structure
mysql -u root -p blood_group_management < database/db.sql
```

### Local Development Server
```bash
# Start PHP development server from project root
php -S localhost:8000

# Or use XAMPP/WAMP/MAMP and point to this directory
# Default admin login: admin / admin123
```

### Configuration
- **Database configuration**: `includes/config.php`
- **Default database**: `blood_group_management`
- **Default credentials**: localhost, root, no password
- **Timezone**: Asia/Kolkata
- **WhatsApp Group Setup**: Update `whatsapp_group_link` in `system_settings` table
  ```sql
  UPDATE system_settings SET 
    whatsapp_group_link = 'https://chat.whatsapp.com/YOUR_GROUP_INVITE_LINK',
    whatsapp_group_name = 'Your Blood Donors Group Name'
    WHERE id = 1;
  ```

## Architecture Overview

### Core Structure
```
blood/
├── admin/               # Admin panel (dashboard, management)
├── includes/           # Shared PHP logic and configurations
│   ├── config.php      # Database connection
│   ├── functions.php   # Utility functions
│   ├── admin_*.php     # Admin-specific includes
│   └── *_auth.php      # Authentication logic
├── database/           # SQL files and database dumps
├── assets/             # CSS, JS, and external libraries
│   ├── css/           # Stylesheets for different sections
│   ├── js/            # JavaScript files
│   └── TCPDF-main/    # PDF generation library
└── emergency.php      # Public emergency donor search
```

### Database Architecture
**Core Tables:**
- `students` - Donor records with blood groups and eligibility
- `admins` - System administrators with role-based access
- `donations` - Historical donation records with auto-triggers
- `donation_events` - Organized blood camps and events
- `blood_requests` - Emergency blood requests with approval workflow
- `event_registrations/attendance` - Event participation tracking

**Key Relationships:**
- Students → Donations (one-to-many with auto-update triggers)
- Events → Registrations/Attendance (many-to-many)
- Blood Requests → Fulfillments → Donations (request lifecycle)
- WhatsApp broadcasts linked to blood requests

**Important Features:**
- Auto-updating `last_donation_date` via database triggers
- 90-day donation eligibility check via stored procedure
- Role-based admin access (admin/superadmin)
- Login attempt tracking and security

### Authentication System
- **Student Auth**: Student ID + password, session-based
- **Admin Auth**: Username + password, role-based (admin/superadmin)
- **Security**: Password hashing, login attempt tracking, session management
- **Password Reset**: Token-based reset system with email integration

## Common Development Tasks

### Database Operations
```bash
# Create fresh database backup
# Via admin panel: Admin → Settings → Create Backup Now

# Import sample data for testing
mysql -u root -p blood_group_management < database/samples.sql

# Reset database to clean state
mysql -u root -p -e "DROP DATABASE blood_group_management; CREATE DATABASE blood_group_management;"
mysql -u root -p blood_group_management < database/blood_group_management\ \(1\).sql
```

### Testing Blood Requests
```php
# Emergency donor search (public access)
# Visit: http://localhost:8000/emergency.php?blood_group=A+

# Test admin blood request approval workflow
# Login as admin → Blood Requests → Approve/Reject requests
```

### Event Management Testing
```php
# Create test donation event
# Admin → Donation Events → Add Event
# Test student registration and check-in flow
```

### WhatsApp Broadcast Testing
```php
# Test emergency broadcast workflow
# 1. Admin → Blood Requests → View Request → Send WhatsApp Broadcast
# 2. Test individual donor messaging (opens multiple WhatsApp windows)
# 3. Test group broadcast (requires valid WhatsApp group link in settings)
# 4. Test message copy/share functionality
# 5. Verify broadcast history is recorded in database
```

### PDF Report Generation
The system uses TCPDF library for generating reports:
- Student lists by blood group
- Donation history reports
- Event attendance reports
- Files: `includes/tcpdf.php` and `assets/TCPDF-main/`

### WhatsApp Integration
- **Emergency broadcasts** for blood requests with individual and group messaging
- **Group broadcast functionality** - send messages directly to WhatsApp groups
- **Individual donor messaging** - bulk WhatsApp messaging to eligible donors
- **Broadcast history tracking** - maintain records of all sent broadcasts
- **Copy/share message functionality** - easy message distribution
- **Group join functionality** for donors via public pages
- **Configurable** via admin settings (group links, broadcast preferences)
- **Files**: `admin/whatsapp_broadcast.php`, `includes/whatsapp-broadcast.php`

## Key Business Logic

### Donor Eligibility Rules
- **90-day waiting period** between donations (enforced by stored procedure)
- **Automatic availability updates** when donations are recorded
- **Year-based student status** with automatic progression via scheduled events

### Blood Request Workflow
1. **Request submission** (public or logged-in students)
2. **Admin review and approval** (pending → approved/rejected)
3. **WhatsApp broadcast** to eligible donors
4. **Donation fulfillment** tracking with unit counting
5. **Auto-completion** when required units are met

### Event Management
- **Target-based events** with specific blood groups and departments
- **Registration and attendance** tracking
- **Donation recording** directly linked to events
- **Automated donor reminders** and notifications

## Important Files to Understand

### Core Configuration
- `includes/config.php` - Database connection and timezone
- `includes/admin_auth.php` - Admin authentication logic
- `includes/functions.php` - SMS and utility functions

### Main Controllers
- `admin/dashboard.php` - Admin overview with charts
- `admin/manage_students.php` - Student CRUD with pagination/filtering
- `admin/blood_requests.php` - Request approval workflow
- `admin/donation_events.php` - Event management
- `emergency.php` - Public emergency donor search

### Database Layer
- Auto-updating triggers on donations table
- `GetAvailableDonorsByBloodGroup` stored procedure
- Scheduled events for student year progression
- Foreign key relationships with cascading deletes

## System Constraints

### Technical Limitations
- **PHP 8.0+** required for password hashing functions
- **MySQL/MariaDB** with trigger and stored procedure support
- **TCPDF library** for PDF generation (included)
- **Session-based** authentication (not JWT/token-based)

### Business Rules
- Students can only donate every **90 days**
- **Blood group compatibility** rules are not enforced (manual admin responsibility)
- **Department and year filtering** for targeted events
- **Manual approval** required for all blood requests

## Development Notes

### Code Style
- Mixed procedural and basic OOP PHP patterns
- HTML embedded in PHP (not templated)
- Bootstrap CSS framework for admin styling
- Chart.js for dashboard visualizations

### Security Considerations
- SQL injection protection via prepared statements
- Password hashing with PHP's `password_hash()`
- Session-based authentication
- Login attempt tracking and IP logging
- XSS protection via `htmlspecialchars()`

### Maintenance Tasks
- Regular database backups via admin panel
- Monitor login attempts for suspicious activity
- Update student year progression (automated via MySQL events)
- Clean up expired password reset tokens

This system is designed for educational institutions managing student blood donation programs with emphasis on emergency response and organized donation events.