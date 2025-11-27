<?php
require_once '../helpers/functions.php';
require_once '../models/Member.php';
require_once '../models/Payment.php';
require_once '../models/Attendance.php';

// Require login
requireLogin();

// Get member ID
$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    redirect('index.php', 'Invalid member ID', 'error');
}

$member = new Member();
$payment = new Payment();
$attendance = new Attendance();

$memberData = $member->getById($id);

if (!$memberData) {
    redirect('index.php', 'Member not found', 'error');
}

// Get member's payment history
$paymentHistory = $payment->getByMemberId($id);

// Get member's attendance history
$attendanceHistory = $attendance->getByMemberId($id, 10);

// Get member statistics
$attendanceStats = $attendance->getMemberAttendanceStats($id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Member - Fitness Club Management System</title>
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
        
        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        }
        
        .badge-glow {
            box-shadow: 0 0 10px currentColor;
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.3) 0%, transparent 100%);
            border-bottom: 2px solid #6366f1;
        }
        
        .table-row:hover {
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
                <h1 class="text-2xl font-bold mb-1">Member Details</h1>
                <p class="text-gray-400 text-sm">View member information and activity</p>
            </div>
            <div class="flex space-x-2">
                <a href="edit.php?id=<?php echo $memberData['id']; ?>" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <a href="index.php" class="text-gray-400 hover:text-white transition-colors text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Members
                </a>
            </div>
        </div>
        
        <!-- Member Profile Card -->
        <div class="glass-card p-4 mb-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-shrink-0">
                    <img src="../uploads/members/<?php echo htmlspecialchars($memberData['photo'] ?? 'default.jpg'); ?>" 
                         alt="Member Photo" class="w-24 h-24 rounded-lg object-cover">
                </div>
                <div class="flex-1">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h2 class="text-xl font-bold mb-1 text-sm"><?php echo htmlspecialchars($memberData['full_name']); ?></h2>
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getMemberTypeBadgeColor($memberData['member_type']); ?>">
                                    <?php echo htmlspecialchars($memberData['member_type']); ?>
                                </span>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeColor($memberData['status']); ?>">
                                    <?php echo htmlspecialchars($memberData['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Member ID</p>
                            <p class="font-semibold text-xs">#<?php echo str_pad($memberData['id'], 5, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 text-xs">Email:</span>
                            <p class="font-medium text-xs"><?php echo htmlspecialchars($memberData['email']); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Phone:</span>
                            <p class="font-medium text-xs"><?php echo htmlspecialchars($memberData['phone']); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Address:</span>
                            <p class="font-medium text-xs"><?php echo htmlspecialchars($memberData['address']); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Join Date:</span>
                            <p class="font-medium text-xs"><?php echo date('F d, Y', strtotime($memberData['join_date'])); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Expiry Date:</span>
                            <p class="font-medium text-xs"><?php echo date('F d, Y', strtotime($memberData['expiry_date'])); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Days Until Expiry:</span>
                            <p class="font-medium text-xs <?php echo daysUntilExpiry($memberData['expiry_date']) <= 7 ? 'text-orange-400' : ''; ?>">
                                <?php 
                                $days = daysUntilExpiry($memberData['expiry_date']);
                                echo $days > 0 ? "$days days" : "Expired";
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-calendar-check text-green-400 text-xl"></i>
                    <span class="text-xs text-gray-400">Total</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo number_format($attendanceStats['total_checkins'] ?? 0); ?></h3>
                <p class="text-sm text-gray-400">Total Check-ins</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-calendar-day text-blue-400 text-xl"></i>
                    <span class="text-xs text-gray-400">This Month</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo number_format($attendanceStats['checkins_this_month'] ?? 0); ?></h3>
                <p class="text-sm text-gray-400">Monthly Check-ins</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-credit-card text-purple-400 text-xl"></i>
                    <span class="text-xs text-gray-400">Total</span>
                </div>
                <h3 class="text-2xl font-bold"><?php echo count($paymentHistory); ?></h3>
                <p class="text-sm text-gray-400">Payments</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-clock text-orange-400 text-xl"></i>
                    <span class="text-xs text-gray-400">Last</span>
                </div>
                <h3 class="text-lg font-bold">
                    <?php 
                    if ($attendanceStats['last_checkin']) {
                        echo date('M d', strtotime($attendanceStats['last_checkin']['checkin_date']));
                    } else {
                        echo 'Never';
                    }
                    ?>
                </h3>
                <p class="text-sm text-gray-400">Last Check-in</p>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="glass-card">
            <div class="border-b border-gray-700">
                <nav class="flex space-x-8 px-6">
                    <button onclick="showTab('payments')" class="tab-button active py-4 px-1 border-b-2 font-medium text-sm" id="payments-tab">
                        <i class="fas fa-credit-card mr-2"></i>Payment History
                    </button>
                    <button onclick="showTab('attendance')" class="tab-button py-4 px-1 border-b-2 font-medium text-sm text-gray-400 hover:text-white" id="attendance-tab">
                        <i class="fas fa-calendar-check mr-2"></i>Attendance History
                    </button>
                </nav>
            </div>
            
            <!-- Payments Tab -->
            <div id="payments-content" class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Discount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Final</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (!empty($paymentHistory)): ?>
                                <?php foreach ($paymentHistory as $payment): ?>
                                    <tr class="table-row transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($payment['plan_name']); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo $payment['duration_months']; ?> month(s)
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            $<?php echo number_format($payment['original_amount'], 2); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php if ($payment['discount_percent'] > 0): ?>
                                                <span class="text-green-400">
                                                    <?php echo $payment['discount_percent']; ?>% ($<?php echo number_format($payment['discount_amount'], 2); ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-400">
                                            $<?php echo number_format($payment['final_amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-credit-card text-3xl mb-2"></i>
                                        <p>No payment records found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Attendance Tab -->
            <div id="attendance-content" class="p-6 hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Check-in Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Day</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (!empty($attendanceHistory)): ?>
                                <?php foreach ($attendanceHistory as $attendance): ?>
                                    <tr class="table-row transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo date('M d, Y', strtotime($attendance['checkin_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo date('h:i A', strtotime($attendance['checkin_time'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php echo date('l', strtotime($attendance['checkin_date'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-calendar-check text-3xl mb-2"></i>
                                        <p>No attendance records found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all content
            document.getElementById('payments-content').classList.add('hidden');
            document.getElementById('attendance-content').classList.add('hidden');
            
            // Remove active class from all tabs
            document.getElementById('payments-tab').classList.remove('active');
            document.getElementById('attendance-tab').classList.remove('active');
            document.getElementById('attendance-tab').classList.add('text-gray-400', 'hover:text-white');
            document.getElementById('payments-tab').classList.add('text-gray-400', 'hover:text-white');
            
            // Show selected content and activate tab
            document.getElementById(tabName + '-content').classList.remove('hidden');
            document.getElementById(tabName + '-tab').classList.add('active');
            document.getElementById(tabName + '-tab').classList.remove('text-gray-400', 'hover:text-white');
        }
    </script>
</body>
</html>
