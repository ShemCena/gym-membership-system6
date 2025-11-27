<?php
require_once '../helpers/functions.php';
require_once '../models/Payment.php';
require_once '../models/Member.php';
require_once '../models/Plan.php';

// Require login
requireLogin();

$payment = new Payment();
$member = new Member();
$plan = new Plan();

// Get all active members and plans
$members = $member->getAll('', 'Active', '', 1, 100);
$plans = $plan->getAllActive();

$selectedMember = null;
$selectedPlan = null;
$discountInfo = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    verifyCSRFToken($csrfToken);
    
    $memberId = (int)($_POST['member_id'] ?? 0);
    $planId = (int)($_POST['plan_id'] ?? 0);
    $paymentDate = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
    $paidBy = sanitize($_POST['paid_by'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    $errors = [];
    
    // Validation
    if ($memberId === 0) {
        $errors[] = 'Please select a member';
    }
    
    if ($planId === 0) {
        $errors[] = 'Please select a plan';
    }
    
    if (empty($paymentDate)) {
        $errors[] = 'Payment date is required';
    }
    
    if (empty($paidBy)) {
        $errors[] = 'Paid by field is required';
    }
    
    // Get member and plan details
    $memberData = $member->getById($memberId);
    $planData = $plan->getById($planId);
    
    if (!$memberData) {
        $errors[] = 'Selected member not found';
    }
    
    if (!$planData) {
        $errors[] = 'Selected plan not found';
    }
    
    // Calculate discount
    if ($memberData && $planData) {
        $discountInfo = calculateDiscount($memberData['member_type'], $planData['price']);
        
        $paymentData = [
            'member_id' => $memberId,
            'plan_id' => $planId,
            'original_amount' => $planData['price'],
            'discount_percent' => $discountInfo['discount_percent'],
            'discount_amount' => $discountInfo['discount_amount'],
            'final_amount' => $discountInfo['final_amount'],
            'payment_date' => $paymentDate,
            'paid_by' => $paidBy,
            'notes' => $notes
        ];
        
        if (empty($errors)) {
            if ($payment->create($paymentData)) {
                redirect('index.php', 'Payment recorded successfully with automatic discount applied!', 'success');
            } else {
                $errors[] = 'Failed to record payment';
            }
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode(', ', $errors);
    }
}

// Handle AJAX requests for member/plan selection
if (isset($_GET['get_member']) && isset($_GET['member_id'])) {
    $memberId = (int)$_GET['member_id'];
    $memberData = $member->getById($memberId);
    
    if ($memberData) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'member' => $memberData
        ]);
        exit;
    }
}

if (isset($_GET['calculate_discount']) && isset($_GET['member_id']) && isset($_GET['plan_id'])) {
    $memberId = (int)$_GET['member_id'];
    $planId = (int)$_GET['plan_id'];
    
    $memberData = $member->getById($memberId);
    $planData = $plan->getById($planId);
    
    if ($memberData && $planData) {
        $discountInfo = calculateDiscount($memberData['member_type'], $planData['price']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'discount' => $discountInfo,
            'member_type' => $memberData['member_type']
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - Fitness Club Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            border-radius: 16px;
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
        
        .form-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .discount-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            transition: all 0.3s ease;
        }
        
        .discount-card.applied {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .payment-preview {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);
            border: 1px solid rgba(99, 102, 241, 0.1);
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
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
            <a href="../plans/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-clipboard-list w-4 mr-2 text-sm"></i>
                <span class="text-sm">Plans</span>
            </a>
            <a href="index.php" class="sidebar-item active flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
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
                <h1 class="text-2xl font-bold mb-1">Record Payment</h1>
                <p class="text-gray-400 text-sm">Process member payments with automatic discounts</p>
            </div>
            <a href="index.php" class="text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Payments
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Payment Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Form -->
            <div class="lg:col-span-2">
                <div class="glass-card p-4">
                    <form method="POST" id="paymentForm" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Member Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                <i class="fas fa-user mr-2"></i>Select Member *
                            </label>
                            <select name="member_id" id="memberId" class="w-full px-4 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input" required onchange="loadMemberDetails()">
                                <option value="">Choose a member...</option>
                                <?php if ($members && !empty($members['members'])): ?>
                                    <?php foreach ($members['members'] as $m): ?>
                                        <option value="<?php echo $m['id']; ?>" <?php echo (isset($_POST['member_id']) && $_POST['member_id'] == $m['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['full_name']); ?> - <?php echo htmlspecialchars($m['member_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Member Details Display -->
                        <div id="memberDetails" class="hidden bg-slate-800/50 rounded-lg p-4">
                            <div class="flex items-center space-x-4">
                                <img id="memberPhoto" src="" alt="Member" class="w-16 h-16 rounded-full">
                                <div class="flex-1">
                                    <h4 id="memberName" class="font-semibold"></h4>
                                    <p id="memberEmail" class="text-sm text-gray-400"></p>
                                    <p id="memberType" class="text-sm"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Plan Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">
                                <i class="fas fa-clipboard-list mr-2"></i>Select Plan *
                            </label>
                            <select name="plan_id" id="planId" class="w-full px-4 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input" required onchange="calculateDiscount()">
                                <option value="">Choose a plan...</option>
                                <?php if ($plans): ?>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-duration="<?php echo $p['duration_months']; ?>" <?php echo (isset($_POST['plan_id']) && $_POST['plan_id'] == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['plan_name']); ?> - ₱<?php echo number_format($p['price'], 2); ?> (<?php echo $p['duration_months']; ?> months)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Discount Information -->
                        <div id="discountInfo" class="discount-card rounded-lg p-4 hidden">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-green-400">
                                        <i class="fas fa-percentage mr-2"></i>Automatic Discount Applied
                                    </h4>
                                    <p class="text-sm text-gray-300 mt-1">
                                        <span id="memberTypeDiscount"></span> members get <span id="discountPercent"></span> off
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-400">-<span id="discountAmount">$0.00</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                    <i class="fas fa-calendar mr-2"></i>Payment Date *
                                </label>
                                <input 
                                    type="date" 
                                    name="payment_date" 
                                    value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')); ?>"
                                    class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 form-input text-sm"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                    <i class="fas fa-user-check mr-2"></i>Paid By *
                                </label>
                                <input 
                                    type="text" 
                                    name="paid_by" 
                                    value="<?php echo htmlspecialchars($_POST['paid_by'] ?? ''); ?>"
                                    class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                    placeholder="Enter payer name"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">
                                <i class="fas fa-sticky-note mr-2"></i>Notes (Optional)
                            </label>
                            <textarea 
                                name="notes" 
                                rows="2"
                                class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 form-input text-sm"
                                placeholder="Add any additional notes..."
                            ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700">
                            <a href="index.php" class="px-3 py-1 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all text-sm">
                                Cancel
                            </a>
                            <button type="submit" class="px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all btn-glow text-sm">
                                <i class="fas fa-credit-card mr-1"></i>Record Payment
                                <span class="loading ml-1">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Payment Preview -->
            <div>
                <div class="glass-card p-3">
                    <h3 class="text-lg font-semibold mb-3">Payment Summary</h3>
                    
                    <div class="payment-preview rounded-lg p-3">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 text-sm">Original Amount:</span>
                                <span class="font-semibold text-sm" id="originalAmount">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 text-sm">Discount:</span>
                                <span class="font-semibold text-green-400 text-sm" id="discountDisplay">₱0.00</span>
                            </div>
                            <div class="border-t border-gray-700 pt-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-sm">Final Amount:</span>
                                    <span class="text-lg font-bold text-green-400" id="finalAmount">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-2 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                        <p class="text-xs text-blue-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            Automatic discounts: Student 10%, Senior 15%, Regular 0%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function loadMemberDetails() {
            const memberId = document.getElementById('memberId').value;
            const detailsDiv = document.getElementById('memberDetails');
            
            if (!memberId) {
                detailsDiv.classList.add('hidden');
                updatePaymentSummary(0, 0, 0);
                return;
            }
            
            fetch(`add.php?get_member=1&member_id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const member = data.member;
                        document.getElementById('memberPhoto').src = `../uploads/members/${member.photo || 'default.jpg'}`;
                        document.getElementById('memberName').textContent = member.full_name;
                        document.getElementById('memberEmail').textContent = member.email;
                        
                        const memberTypeSpan = document.getElementById('memberType');
                        memberTypeSpan.textContent = member.member_type;
                        memberTypeSpan.className = `px-2 py-1 text-xs rounded-full ${getMemberTypeBadgeClass(member.member_type)}`;
                        
                        detailsDiv.classList.remove('hidden');
                        calculateDiscount();
                    }
                })
                .catch(error => console.error('Error loading member:', error));
        }
        
        function calculateDiscount() {
            const memberId = document.getElementById('memberId').value;
            const planId = document.getElementById('planId').value;
            const discountDiv = document.getElementById('discountInfo');
            
            if (!memberId || !planId) {
                discountDiv.classList.add('hidden');
                updatePaymentSummary(0, 0, 0);
                return;
            }
            
            fetch(`add.php?calculate_discount=1&member_id=${memberId}&plan_id=${planId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const discount = data.discount;
                        const memberType = data.member_type;
                        
                        if (discount.discount_percent > 0) {
                            discountDiv.classList.remove('hidden');
                            discountDiv.classList.add('applied');
                            
                            document.getElementById('memberTypeDiscount').textContent = memberType;
                            document.getElementById('discountPercent').textContent = discount.discount_percent + '%';
                            document.getElementById('discountAmount').textContent = '₱' + discount.discount_amount.toFixed(2);
                            
                            setTimeout(() => {
                                discountDiv.classList.remove('applied');
                            }, 2000);
                        } else {
                            discountDiv.classList.add('hidden');
                        }
                        
                        updatePaymentSummary(discount.original_amount, discount.discount_amount, discount.final_amount);
                    }
                })
                .catch(error => console.error('Error calculating discount:', error));
        }
        
        function updatePaymentSummary(original, discount, final) {
            document.getElementById('originalAmount').textContent = '₱' + original.toFixed(2);
            document.getElementById('discountDisplay').textContent = discount > 0 ? '-₱' + discount.toFixed(2) : '₱0.00';
            document.getElementById('finalAmount').textContent = '₱' + final.toFixed(2);
        }
        
        function getMemberTypeBadgeClass(type) {
            switch(type) {
                case 'Student':
                    return 'bg-blue-100 text-blue-800';
                case 'Senior':
                    return 'bg-purple-100 text-purple-800';
                case 'Regular':
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }
        
        // Form submission with loading state
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const loadingSpan = this.querySelector('.loading');
            loadingSpan.classList.add('show');
        });
        
        // Initialize with pre-selected values if available
        <?php if (isset($_POST['member_id']) && $_POST['member_id']): ?>
            loadMemberDetails();
        <?php endif; ?>
        
        <?php if (isset($_POST['member_id']) && isset($_POST['plan_id'])): ?>
            calculateDiscount();
        <?php endif; ?>
    </script>
</body>
</html>
