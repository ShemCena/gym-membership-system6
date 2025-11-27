<?php
/**
 * Helper Functions
 * Gym Membership Management System
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include custom CSS
function includeCustomCSS() {
    echo '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/style.css">';
}

// Define base URL if not defined
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    define('BASE_URL', "$protocol://$host$path/");
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number
function validatePhone($phone) {
    return preg_match('/^[0-9\-\+\(\)\s]+$/', $phone);
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Calculate days until expiry
function daysUntilExpiry($expiryDate) {
    $expiry = new DateTime($expiryDate);
    $today = new DateTime();
    $diff = $today->diff($expiry);
    return $diff->days;
}

// Get member type badge color
function getMemberTypeBadgeColor($type) {
    switch($type) {
        case 'Student':
            return 'bg-blue-100 text-blue-800';
        case 'Senior':
            return 'bg-purple-100 text-purple-800';
        case 'Regular':
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Get status badge color
function getStatusBadgeColor($status) {
    switch($status) {
        case 'Active':
            return 'bg-green-100 text-green-800';
        case 'Expired':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Calculate discount based on member type
function calculateDiscount($memberType, $amount) {
    $discountPercent = 0;
    switch($memberType) {
        case 'Student':
            $discountPercent = 0.10; // 10% discount
            break;
        case 'Senior':
            $discountPercent = 0.15; // 15% discount
            break;
        case 'Regular':
        default:
            $discountPercent = 0; // No discount
            break;
    }
    
    $discountAmount = $amount * $discountPercent;
    $finalAmount = $amount - $discountAmount;
    
    return [
        'discount_percent' => $discountPercent * 100,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount
    ];
}

// Upload file (member photo)
function uploadPhoto($file, $currentPhoto = null) {
    $targetDir = "uploads/members/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Remove current photo if exists
    if ($currentPhoto && file_exists($targetDir . $currentPhoto)) {
        unlink($targetDir . $currentPhoto);
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    
    // Check file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['error' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['error' => 'File size must be less than 5MB'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['filename' => $fileName];
    } else {
        return ['error' => 'Failed to upload file'];
    }
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        $alertClass = $type === 'success' ? 'bg-green-500/20 border-green-500/50 text-green-400' : 
                     ($type === 'error' ? 'bg-red-500/20 border-red-500/50 text-red-400' : 
                     'bg-blue-500/20 border-blue-500/50 text-blue-400');
        
        echo "<div class='glass-card px-3 py-2 rounded relative mb-3 {$alertClass}' role='alert'>";
        echo "<span class='text-sm'>{$message}</span>";
        echo "</div>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Pagination helper
function paginate($totalRecords, $recordsPerPage, $currentPage) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'offset' => $offset,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'has_next' => $currentPage < $totalPages,
        'has_prev' => $currentPage > 1
    ];
}
?>
