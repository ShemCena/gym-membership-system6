<?php
require_once '../helpers/functions.php';
require_once '../models/Plan.php';

// Require login
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('index.php', 'Invalid plan ID', 'error');
}

$plan = new Plan();
$plan_data = $plan->getById($id);

if (!$plan_data) {
    redirect('index.php', 'Plan not found', 'error');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planName = sanitize($_POST['plan_name'] ?? '');
    $duration = (int)($_POST['duration_months'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    
    // Validation
    if (empty($planName)) {
        $errors[] = 'Plan name is required';
    }
    
    if ($duration <= 0) {
        $errors[] = 'Duration must be greater than 0';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0';
    }
    
    if (empty($errors)) {
        $data = [
            'plan_name' => $planName,
            'duration_months' => $duration,
            'price' => $price,
            'description' => $description
        ];
        
        if ($plan->update($id, $data)) {
            redirect('index.php', 'Plan updated successfully!', 'success');
        } else {
            $errors[] = 'Failed to update plan';
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $plan_data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plan - Fitness Club Management System</title>
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
            border-left: 4px solid #6366f1;
            padding-left: 20px;
        }
        
        .sidebar-item.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.3) 0%, transparent 100%);
            border-left: 4px solid #6366f1;
            padding-left: 20px;
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .price-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
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
            <a href="../members/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-users w-4 mr-2 text-sm"></i>
                <span class="text-sm">Members</span>
            </a>
            <a href="index.php" class="sidebar-item active flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
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
                <h1 class="text-2xl font-bold mb-1">Edit Plan</h1>
                <p class="text-gray-400 text-sm">Modify membership plan details</p>
            </div>
            <a href="index.php" class="bg-gray-700 text-white px-3 py-2 rounded-lg hover:bg-gray-600 transition-all text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Plans
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="glass-card p-4 mb-6 border border-red-500/50">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    <div>
                        <h3 class="text-red-400 font-semibold mb-1">Please fix the following errors:</h3>
                        <ul class="text-red-300 text-sm space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li>• <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Edit Plan Form -->
        <div class="glass-card p-4">
            <form method="POST" id="planForm">
                <div class="space-y-4">
                    <!-- Plan Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                            <i class="fas fa-tag mr-2"></i>Plan Name *
                        </label>
                        <input 
                            type="text" 
                            name="plan_name" 
                            value="<?php echo htmlspecialchars($_POST['plan_name'] ?? ''); ?>"
                            placeholder="e.g., Monthly Basic, Annual Premium"
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                            required
                        >
                    </div>
                    
                    <!-- Duration and Price Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                <i class="fas fa-clock mr-2"></i>Duration (Months) *
                            </label>
                            <input 
                                type="number" 
                                name="duration_months" 
                                value="<?php echo htmlspecialchars($_POST['duration_months'] ?? ''); ?>"
                                placeholder="e.g., 1, 3, 6, 12"
                                min="1"
                                class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                <i class="fas fa-peso-sign mr-2"></i>Price (₱) *
                            </label>
                            <input 
                                type="number" 
                                name="price" 
                                value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                placeholder="e.g., 900, 2700, 5400"
                                step="0.01"
                                min="0.01"
                                class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                required
                            >
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                            <i class="fas fa-align-left mr-2"></i>Description
                        </label>
                        <textarea 
                            name="description" 
                            rows="3"
                            placeholder="Describe what's included in this plan..."
                            class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm resize-none"
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Plan Info -->
                <div class="mt-4 p-3 bg-slate-800/30 rounded-lg border border-gray-700">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 text-xs">Created:</span>
                            <span class="text-white ml-2 text-xs"><?php echo date('M d, Y H:i', strtotime($plan_data['created_at'])); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Last Updated:</span>
                            <span class="text-white ml-2 text-xs"><?php echo isset($plan_data['updated_at']) ? date('M d, Y H:i', strtotime($plan_data['updated_at'])) : 'Not updated yet'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex justify-between items-center mt-6">
                    <a href="index.php" class="text-gray-400 hover:text-white transition-colors text-sm">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <div class="space-x-3">
                        <button type="button" onclick="resetForm()" class="px-3 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all text-sm">
                            <i class="fas fa-redo mr-1"></i>Reset
                        </button>
                        <button type="submit" class="px-3 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all btn-glow text-sm">
                            <i class="fas fa-save mr-1"></i>Update Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function resetForm() {
            if (confirm('Are you sure you want to reset the form?')) {
                document.getElementById('planForm').reset();
                // Reset to original values
                const planNameInput = document.querySelector('input[name="plan_name"]');
                const durationInput = document.querySelector('input[name="duration_months"]');
                const priceInput = document.querySelector('input[name="price"]');
                const descriptionInput = document.querySelector('textarea[name="description"]');
                
                planNameInput.value = '<?php echo htmlspecialchars($plan_data['plan_name']); ?>';
                durationInput.value = '<?php echo $plan_data['duration_months']; ?>';
                priceInput.value = '<?php echo $plan_data['price']; ?>';
                descriptionInput.value = '<?php echo htmlspecialchars($plan_data['description'] ?? ''); ?>';
            }
        }
        
        // Form validation
        document.getElementById('planForm').addEventListener('submit', function(e) {
            const planNameValue = document.querySelector('input[name="plan_name"]').value.trim();
            const durationValue = parseInt(document.querySelector('input[name="duration_months"]').value);
            const priceValue = parseFloat(document.querySelector('input[name="price"]').value);
            
            if (!planNameValue) {
                e.preventDefault();
                alert('Plan name is required');
                return false;
            }
            
            if (durationValue <= 0) {
                e.preventDefault();
                alert('Duration must be greater than 0');
                return false;
            }
            
            if (priceValue <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return false;
            }
        });
    </script>
</body>
</html>
