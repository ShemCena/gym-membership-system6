<?php
require_once '../helpers/functions.php';
require_once '../models/Member.php';
require_once '../models/Plan.php';

// Require login
requireLogin();

// Load plans for expiry calculation
$plan = new Plan();
$plans = $plan->getAllActive();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    verifyCSRFToken($csrfToken);
    
    $data = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'member_type' => sanitize($_POST['member_type'] ?? 'Regular'),
        'join_date' => sanitize($_POST['join_date'] ?? date('Y-m-d')),
        'expiry_date' => sanitize($_POST['expiry_date'] ?? ''),
        'status' => sanitize($_POST['status'] ?? 'Active')
    ];
    
    // Auto-calculate expiry date if plan_id is provided
    $planId = (int)($_POST['plan_id'] ?? 0);
    if ($planId > 0 && !empty($data['join_date'])) {
        $planData = $plan->getById($planId);
        if ($planData && !empty($planData['duration_months'])) {
            $joinDate = new DateTime($data['join_date']);
            $joinDate->add(new DateInterval('P' . $planData['duration_months'] . 'M'));
            $data['expiry_date'] = $joinDate->format('Y-m-d');
        }
    }
    
    $errors = [];
    
    // Validation
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($data['email'])) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($data['phone'])) {
        $errors[] = 'Phone number is required';
    } elseif (!validatePhone($data['phone'])) {
        $errors[] = 'Invalid phone number format';
    }
    
    if (empty($data['address'])) {
        $errors[] = 'Address is required';
    }
    
    if (empty($data['expiry_date'])) {
        $errors[] = 'Expiry date is required';
    }
    
    // Check if email already exists
    $member = new Member();
    if ($member->getByEmail($data['email'])) {
        $errors[] = 'Email already exists';
    }
    
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadPhoto($_FILES['photo']);
        if (isset($uploadResult['error'])) {
            $errors[] = $uploadResult['error'];
        } else {
            $data['photo'] = $uploadResult['filename'];
        }
    }
    
    if (empty($errors)) {
        if ($member->create($data)) {
            redirect('index.php', 'Member added successfully!', 'success');
        } else {
            $errors[] = 'Failed to add member';
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode(', ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member - Fitness Club Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php includeCustomCSS(); ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .sidebar-item {
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, transparent 100%);
            border-left: 3px solid #6366f1;
            padding-left: 16px;
        }
        
        .sidebar-item.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.3) 0%, transparent 100%);
            border-left: 3px solid #6366f1;
            padding-left: 16px;
        }
        
        .form-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .photo-preview {
            transition: all 0.3s ease;
        }
        
        .photo-preview:hover {
            transform: scale(1.05);
        }
        
        .file-input-wrapper:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }
    </style>
</head>
<body class="bg-slate-900 text-white">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-56 glass-card p-4 z-10">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mr-2">
                <i class="fas fa-dumbbell text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold neon-text">FITNESS CLUB PRO</h1>
                <p class="text-gray-400 text-xs">Management System</p>
            </div>
        </div>
        
        <nav class="space-y-1">
            <a href="../index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-tachometer-alt w-4 mr-2 text-sm"></i>
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="index.php" class="sidebar-item active flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-users w-4 mr-2 text-sm"></i>
                <span class="text-sm">Members</span>
            </a>
            <a href="../plans/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-clipboard-list w-4 mr-2 text-sm"></i>
                <span class="text-sm">Plans</span>
            </a>
            <a href="../payments/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-credit-card w-4 mr-2 text-sm"></i>
                <span class="text-sm">Payments</span>
            </a>
            <a href="../attendance/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-calendar-check w-4 mr-2 text-sm"></i>
                <span class="text-sm">Attendance</span>
            </a>
        </nav>
        
        <div class="absolute bottom-6 left-6 right-6">
            <div class="border-t border-gray-700 pt-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-blue-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                        <p class="text-xs text-gray-400">Administrator</p>
                    </div>
                </div>
                <a href="../logout.php" class="mt-3 flex items-center p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all">
                    <i class="fas fa-sign-out-alt w-4 mr-2 text-sm"></i>
                    <span class="text-sm">Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="ml-56 p-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold mb-1">Add New Member</h1>
                <p class="text-gray-400 text-sm">Register a new gym member</p>
            </div>
            <a href="index.php" class="text-gray-400 hover:text-white transition-colors text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Members
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-3 py-2 rounded-lg mb-4 text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Member Form -->
        <div class="glass-card p-4">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Photo Upload -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Member Photo</label>
                        <div class="file-input-wrapper border-2 border-dashed border-gray-600 rounded-lg p-4 text-center transition-all cursor-pointer" onclick="document.getElementById('photo').click()">
                            <div id="photoPreview" class="mb-3">
                                <i class="fas fa-camera text-3xl text-gray-500"></i>
                            </div>
                            <p class="text-gray-400 text-sm">Click to upload photo</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF (Max 5MB)</p>
                            <input type="file" id="photo" name="photo" accept="image/*" class="hidden" onchange="previewPhoto(this)">
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                <i class="fas fa-user mr-2"></i>Full Name *
                            </label>
                            <input 
                                type="text" 
                                name="full_name" 
                                value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                placeholder="Enter full name"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">
                                <i class="fas fa-envelope mr-2"></i>Email *
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                placeholder="Enter email address"
                                required
                            >
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-phone mr-2"></i>Phone *
                        </label>
                        <input 
                            type="tel" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                            placeholder="Enter phone number"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-home mr-2"></i>Address *
                        </label>
                        <input 
                            type="text" 
                            name="address" 
                            value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                            placeholder="Enter address"
                            required
                        >
                    </div>
                </div>
                
                <!-- Member Details -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-tag mr-2"></i>Member Type *
                        </label>
                        <select name="member_type" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input text-sm" required>
                            <option value="Regular" <?php echo ($_POST['member_type'] ?? '') === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                            <option value="Student" <?php echo ($_POST['member_type'] ?? '') === 'Student' ? 'selected' : ''; ?>>Student</option>
                            <option value="Senior" <?php echo ($_POST['member_type'] ?? '') === 'Senior' ? 'selected' : ''; ?>>Senior</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-clipboard-list mr-2"></i>Membership Plan *
                        </label>
                        <select name="plan_id" id="planId" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input text-sm" required onchange="calculateExpiryDate()">
                            <option value="">Select Plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" data-duration="<?php echo $plan['duration_months']; ?>" <?php echo ($_POST['plan_id'] ?? '') == $plan['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($plan['plan_name']); ?> (<?php echo $plan['duration_months']; ?> months)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-calendar-plus mr-2"></i>Join Date *
                        </label>
                        <input 
                            type="date" 
                            name="join_date" 
                            id="joinDate"
                            value="<?php echo htmlspecialchars($_POST['join_date'] ?? date('Y-m-d')); ?>"
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input text-sm"
                            required
                            onchange="calculateExpiryDate()"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">
                            <i class="fas fa-calendar-check mr-2"></i>Expiry Date *
                        </label>
                        <input 
                            type="date" 
                            name="expiry_date" 
                            id="expiryDate"
                            value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>"
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input text-sm"
                            readonly
                            required
                        >
                    </div>
                </div>
                
                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">
                        <i class="fas fa-info-circle mr-2"></i>Status *
                    </label>
                    <div class="flex space-x-3">
                        <label class="flex items-center">
                            <input type="radio" name="status" value="Active" <?php echo ($_POST['status'] ?? 'Active') === 'Active' ? 'checked' : ''; ?> class="mr-2" required>
                            <span class="text-green-400 text-sm">Active</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="status" value="Expired" <?php echo ($_POST['status'] ?? '') === 'Expired' ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-red-400 text-sm">Expired</span>
                        </label>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700">
                    <a href="index.php" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all text-sm">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all btn-glow text-sm">
                        <i class="fas fa-save mr-2"></i>Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function calculateExpiryDate() {
            const planSelect = document.getElementById('planId');
            const joinDateInput = document.getElementById('joinDate');
            const expiryDateInput = document.getElementById('expiryDate');
            
            if (planSelect.value && joinDateInput.value) {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                const duration = parseInt(selectedOption.getAttribute('data-duration'));
                
                if (duration) {
                    const joinDate = new Date(joinDateInput.value);
                    const expiryDate = new Date(joinDate);
                    expiryDate.setMonth(expiryDate.getMonth() + duration);
                    
                    // Format date as YYYY-MM-DD
                    const formattedExpiry = expiryDate.toISOString().split('T')[0];
                    expiryDateInput.value = formattedExpiry;
                }
            } else {
                expiryDateInput.value = '';
            }
        }
        
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto photo-preview">`;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.querySelector('input[name="email"]').value;
            const phone = document.querySelector('input[name="phone"]').value;
            const joinDate = document.querySelector('input[name="join_date"]').value;
            const expiryDate = document.querySelector('input[name="expiry_date"]').value;
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Phone validation
            const phoneRegex = /^[0-9\-\+\(\)\s]+$/;
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return false;
            }
            
            // Date validation
            if (new Date(expiryDate) <= new Date(joinDate)) {
                e.preventDefault();
                alert('Expiry date must be after join date');
                return false;
            }
        });
        
        // Auto-calculate expiry date based on join date (optional enhancement)
        document.querySelector('input[name="join_date"]').addEventListener('change', function() {
            const joinDate = new Date(this.value);
            const defaultExpiry = new Date(joinDate);
            defaultExpiry.setMonth(defaultExpiry.getMonth() + 1); // Default 1 month
            document.querySelector('input[name="expiry_date"]').value = defaultExpiry.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
