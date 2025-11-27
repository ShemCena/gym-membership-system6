-- Fitness Club Management System Database Schema
-- Created for WST1, OOP & ADS Final Project

CREATE DATABASE IF NOT EXISTS fitness_club_system;
USE fitness_club_system;

-- Admins table for authentication
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table with proper normalization
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    member_type ENUM('Regular', 'Student', 'Senior') NOT NULL DEFAULT 'Regular',
    photo VARCHAR(255) DEFAULT NULL,
    join_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Active', 'Expired') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry_date)
);

-- Fitness Club Membership Plans table
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(50) NOT NULL UNIQUE,
    duration_months INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plan_name (plan_name)
);

-- Payments table with proper foreign key relationships
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    original_amount DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    paid_by VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
    INDEX idx_member (member_id),
    INDEX idx_payment_date (payment_date)
);

-- Attendance table for fitness club check-ins
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    checkin_date DATE NOT NULL,
    checkin_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_checkin (member_id, checkin_date),
    INDEX idx_checkin_date (checkin_date),
    INDEX idx_member_attendance (member_id)
);

-- Insert default admin account (password: admin123)
INSERT INTO admins (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample fitness club membership plans
INSERT INTO plans (plan_name, duration_months, price, description) VALUES 
('Monthly Basic', 1, 900.00, 'Access to fitness club equipment during regular hours'),
('Monthly Premium', 1, 1200.00, 'All access plus group fitness classes and personal trainer'),
('3-Month Basic', 3, 2550.00, '3 months basic membership - 5% discount'),
('3-Month Premium', 3, 3450.00, '3 months premium membership - 5% discount'),
('6-Month Basic', 6, 4860.00, '6 months basic membership - 10% discount'),
('6-Month Premium', 6, 6480.00, '6 months premium membership - 10% discount'),
('Annual Basic', 12, 9000.00, 'Full year basic membership - 17% discount'),
('Annual Premium', 12, 12000.00, 'Full year premium membership - 17% discount');

-- Insert sample members
INSERT INTO members (full_name, email, phone, address, member_type, join_date, expiry_date, status) VALUES 
('John Smith', 'john.smith@email.com', '555-0101', '123 Main St, City, State', 'Regular', '2024-01-15', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Active'),
('Sarah Johnson', 'sarah.j@email.com', '555-0102', '456 Oak Ave, City, State', 'Student', '2024-02-01', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Active'),
('Michael Brown', 'michael.b@email.com', '555-0103', '789 Pine Rd, City, State', 'Senior', '2023-12-15', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Expired'),
('Emily Davis', 'emily.d@email.com', '555-0104', '321 Elm St, City, State', 'Regular', '2024-03-10', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'Active'),
('Robert Wilson', 'robert.w@email.com', '555-0105', '654 Maple Dr, City, State', 'Student', '2024-01-20', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Active');

-- Insert sample payments
INSERT INTO payments (member_id, plan_id, original_amount, discount_percent, discount_amount, final_amount, payment_date, paid_by, notes) VALUES 
(1, 1, 900.00, 0.00, 0.00, 900.00, '2024-11-25', 'John Smith', 'Monthly basic membership'),
(2, 2, 1200.00, 10.00, 120.00, 1080.00, '2024-11-24', 'Sarah Johnson', 'Student discount applied'),
(4, 3, 2550.00, 0.00, 0.00, 2550.00, '2024-11-20', 'Emily Davis', '3-month basic plan'),
(5, 2, 1200.00, 10.00, 120.00, 1080.00, '2024-11-22', 'Robert Wilson', 'Student discount applied'),
(1, 4, 3450.00, 0.00, 0.00, 3450.00, '2024-11-15', 'John Smith', '3-month premium upgrade');

-- Insert sample attendance
INSERT INTO attendance (member_id, checkin_date, checkin_time) VALUES 
(1, CURDATE(), '08:30:00'),
(2, CURDATE(), '09:15:00'),
(4, CURDATE(), '07:45:00'),
(5, CURDATE(), '10:00:00'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:30:00');
