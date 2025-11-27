# Fitness Club Management System

A comprehensive fitness club membership management system built with PHP 8+, MySQL, and Tailwind CSS. Features automatic discounts, attendance tracking, payment processing, and detailed analytics.

## Features

### 🔐 Authentication & Security
- Secure admin login with session management
- CSRF protection on all forms
- Password hashing with bcrypt
- SQL injection prevention with PDO

### 👥 Member Management
- Full CRUD operations for members
- Photo upload functionality
- Member types (Regular, Student, Senior)
- Automatic status updates (Active/Expired)
- Search and filter capabilities

### 💳 Payment System
- Upfront payment processing
- **Automatic Discounts:**
  - Student members: 10% discount
  - Senior members: 15% discount
  - Regular members: No discount
- Payment history tracking
- Automatic expiry date updates
- Revenue analytics

### 📅 Attendance Tracking
- Quick member check-in
- Daily check-in limits
- Attendance statistics
- Historical records
- Peak hour analysis

### 📊 Dashboard Analytics
- Real-time statistics and charts
- Member overview and trends
- Revenue summaries
- Recent activities feed
- Quick access to all modules

### 🎨 Modern UI/UX
- Tailwind CSS with custom fitness club theme
- Glassmorphism effects
- Responsive design
- Interactive charts with Chart.js
- Dark fitness club aesthetic
- Smooth animations

## System Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- GD extension (for image uploads)

## Installation

1. **Database Setup**
   ```sql
   Create a database named `fitness_club_system`
   Import the `database.sql` file
   ```

2. **Configuration**
   ```bash
   Clone the repository to your web server directory
   Ensure the `uploads/members/` directory is writable
   ```

3. **Access the System**
   - Navigate to `http://localhost/gym` in your browser
   - Default login credentials:
     - Username: `admin`
     - Password: `admin123`

   Note: The URL remains `/gym` for compatibility, but the system is now configured as a Fitness Club Management System.

## Database Schema

The system uses a normalized 3NF database structure with the following tables:

- **admins** - Administrator accounts
- **members** - Member information and profiles
- **plans** - Membership plans and pricing
- **payments** - Payment records with discount tracking
- **attendance** - Member check-in records

## Project Structure

```
fitness_club/
├── config/
│   └── database.php          # Database connection class
├── helpers/
│   └── functions.php         # Helper functions and utilities
├── models/
│   ├── Admin.php            # Admin model
│   ├── Member.php           # Member model
│   ├── Plan.php             # Plan model
│   ├── Payment.php          # Payment model
│   └── Attendance.php       # Attendance model
├── members/
│   ├── index.php            # Member list
│   ├── add.php              # Add member
│   ├── edit.php             # Edit member
│   └── view.php             # View member details
├── plans/
│   ├── index.php            # Plan list
│   ├── add.php              # Add plan
│   └── edit.php             # Edit plan
├── payments/
│   ├── index.php            # Payment history
│   └── add.php              # Record payment
├── attendance/
│   └── index.php            # Attendance management
├── uploads/
│   └── members/             # Member photos
├── index.php                # Main dashboard
├── login.php                # Login page
├── logout.php               # Logout handler
├── database.sql             # Database schema
└── README.md                # This file
```

## Key Features Explained

### Automatic Discount System
The payment system automatically applies discounts based on member type:
- **Student Members**: 10% discount on all plans
- **Senior Members**: 15% discount on all plans
- **Regular Members**: No discount

Discounts are calculated and displayed in real-time during payment processing.

### Member Expiry Management
- Members are automatically marked as "Expired" when their expiry date passes
- Reports show members expiring in the next 30 days
- Renewal reminders and quick payment links

### Attendance System
- One-time check-in per day per member
- Only active members can check-in
- Real-time attendance statistics
- Daily, weekly, and monthly analytics

## Security Features

- **Input Validation**: All user inputs are sanitized and validated
- **SQL Injection Protection**: PDO prepared statements used throughout
- **CSRF Protection**: Tokens on all forms
- **Session Security**: Secure session management with regeneration
- **File Upload Security**: Validated image uploads with size limits

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Support

For issues, questions, or contributions, please refer to the project documentation or contact the development team.

## License

This project is for educational purposes. Please ensure compliance with your local regulations when using in a production environment.

---

**Built with ❤️ using PHP, MySQL, and Tailwind CSS**
