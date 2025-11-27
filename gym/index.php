<?php
require_once 'helpers/functions.php';
require_once 'models/Admin.php';
require_once 'models/Member.php';
require_once 'models/Plan.php';
require_once 'models/Payment.php';
require_once 'models/Attendance.php';

// Require login
requireLogin();

// Get dashboard statistics
$member = new Member();
$plan = new Plan();
$payment = new Payment();
$attendance = new Attendance();

$memberStats = $member->getStatistics();
$planStats = $plan->getStatistics();
$paymentStats = $payment->getStatistics();
$attendanceStats = $attendance->getStatistics();

// Get recent activities
$recentPayments = $payment->getAll('', '', '', '', '', 1, 5);
$recentAttendance = $attendance->getTodayAttendance();
$expiringSoon = $member->getExpiringSoon(7);
$topMembers = $payment->getTopMembers(5);

// Update expired members status
$member->updateExpiredStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fitness Club Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -5px rgba(99, 102, 241, 0.3);
        }
        
        .neon-text {
            text-shadow: 0 0 5px rgba(99, 102, 241, 0.8),
                         0 0 10px rgba(99, 102, 241, 0.6);
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
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .badge-glow {
            box-shadow: 0 0 10px currentColor;
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
            <a href="index.php" class="sidebar-item active flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-tachometer-alt w-4 mr-2 text-sm"></i>
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="members/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-users w-4 mr-2 text-sm"></i>
                <span class="text-sm">Members</span>
            </a>
            <a href="plans/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-clipboard-list w-4 mr-2 text-sm"></i>
                <span class="text-sm">Plans</span>
            </a>
            <a href="payments/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-credit-card w-4 mr-2 text-sm"></i>
                <span class="text-sm">Payments</span>
            </a>
            <a href="attendance/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
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
                <a href="logout.php" class="mt-3 flex items-center p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all">
                    <i class="fas fa-sign-out-alt w-4 mr-2 text-sm"></i>
                    <span class="text-sm">Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="ml-56 p-4">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">Dashboard</h1>
            <p class="text-gray-400 text-sm">Welcome back! Here's your gym overview.</p>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                    <span class="text-blue-400 text-xs">+12%</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($memberStats['total_members']); ?></h3>
                <p class="text-gray-400 text-xs">Total Members</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-check text-white text-sm"></i>
                    </div>
                    <span class="text-green-400 text-xs pulse-animation">Active</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($memberStats['active_members']); ?></h3>
                <p class="text-gray-400 text-xs">Active Members</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-sm"></i>
                    </div>
                    <span class="text-purple-400 text-xs">Today</span>
                </div>
                <h3 class="text-xl font-bold mb-1">$<?php echo number_format($paymentStats['revenue_today'], 2); ?></h3>
                <p class="text-gray-400 text-xs">Today's Revenue</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-day text-white text-sm"></i>
                    </div>
                    <span class="text-orange-400 text-xs">Today</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($attendanceStats['checkins_today']); ?></h3>
                <p class="text-gray-400 text-xs">Check-ins Today</p>
            </div>
        </div>
        
        <!-- Charts and Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Revenue Chart -->
            <div class="glass-card p-4">
                <h3 class="text-lg font-semibold mb-3">Monthly Revenue</h3>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- Member Type Distribution -->
            <div class="glass-card p-4">
                <h3 class="text-lg font-semibold mb-3">Member Types</h3>
                <div class="chart-container">
                    <canvas id="memberTypeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="glass-card p-4">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-700 mb-4">
                <nav class="flex space-x-8">
                    <button onclick="showTab('expiring')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-indigo-400 border-indigo-400" id="expiring-tab">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Expiring Soon
                    </button>
                    <button onclick="showTab('payments')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-400 hover:text-white border-transparent" id="payments-tab">
                        <i class="fas fa-credit-card mr-2"></i>Recent Payments
                    </button>
                    <button onclick="showTab('top-members')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-400 hover:text-white border-transparent" id="top-members-tab">
                        <i class="fas fa-trophy mr-2"></i>Top Members
                    </button>
                </nav>
            </div>
            
            <!-- Expiring Soon Tab -->
            <div id="expiring-content" class="tab-content">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Expiry Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Days Left</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (!empty($expiringSoon)): ?>
                                <?php foreach (array_slice($expiringSoon, 0, 5) as $member): ?>
                                    <tr class="table-row transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img src="uploads/members/<?php echo htmlspecialchars($member['photo'] ?? 'default.jpg'); ?>" 
                                                     alt="Member" class="w-8 h-8 rounded-full mr-3">
                                                <div>
                                                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($member['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getMemberTypeBadgeColor($member['member_type']); ?>">
                                                <?php echo htmlspecialchars($member['member_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                                            <?php echo date('M d, Y', strtotime($member['expiry_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php 
                                            $days = daysUntilExpiry($member['expiry_date']);
                                            if ($days <= 0): ?>
                                                <span class="text-red-400 font-medium">Expired</span>
                                            <?php elseif ($days <= 7): ?>
                                                <span class="text-orange-400 font-medium"><?php echo $days; ?> days</span>
                                            <?php else: ?>
                                                <span class="text-green-400 font-medium"><?php echo $days; ?> days</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <a href="members/view.php?id=<?php echo $member['id']; ?>" class="text-indigo-400 hover:text-indigo-300">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-check-circle text-2xl mb-2"></i>
                                        <p>No members expiring soon</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Payments Tab -->
            <div id="payments-content" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Discount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($recentPayments && !empty($recentPayments['payments'])): ?>
                                <?php foreach ($recentPayments['payments'] as $payment): ?>
                                    <tr class="table-row transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img src="uploads/members/<?php echo htmlspecialchars($payment['photo'] ?? 'default.jpg'); ?>" 
                                                     alt="Member" class="w-8 h-8 rounded-full mr-3">
                                                <div>
                                                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($payment['full_name']); ?></div>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($payment['member_type']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                                            <?php echo htmlspecialchars($payment['plan_name']); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-green-400">$<?php echo number_format($payment['final_amount'], 2); ?></div>
                                            <?php if ($payment['discount_amount'] > 0): ?>
                                                <div class="text-xs text-gray-400 line-through">$<?php echo number_format($payment['original_amount'], 2); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if ($payment['discount_percent'] > 0): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    -<?php echo $payment['discount_percent']; ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">None</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-credit-card text-2xl mb-2"></i>
                                        <p>No recent payments found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Members Tab -->
            <div id="top-members-content" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payments</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (!empty($topMembers)): ?>
                                <?php foreach ($topMembers as $index => $topMember): ?>
                                    <tr class="table-row transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if ($index < 3): ?>
                                                    <i class="fas fa-trophy text-<?php echo ['yellow', 'gray', 'orange'][$index]; ?>-400 mr-2"></i>
                                                <?php else: ?>
                                                    <span class="text-gray-400 mr-2"><?php echo $index + 1; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img src="uploads/members/<?php echo htmlspecialchars($topMember['photo'] ?? 'default.jpg'); ?>" 
                                                     alt="Member" class="w-8 h-8 rounded-full mr-3">
                                                <div>
                                                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($topMember['full_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getMemberTypeBadgeColor($topMember['member_type']); ?>">
                                                <?php echo htmlspecialchars($topMember['member_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                                            <?php echo $topMember['payment_count']; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-green-400">$<?php echo number_format($topMember['total_spent'], 2); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-trophy text-2xl mb-2"></i>
                                        <p>No member data available</p>
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
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($paymentStats['monthly_revenue'], 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($paymentStats['monthly_revenue'], 'revenue')); ?>,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#9ca3af'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#9ca3af'
                        }
                    }
                }
            }
        });
        
        // Member Type Chart
        const memberTypeCtx = document.getElementById('memberTypeChart').getContext('2d');
        const memberTypeChart = new Chart(memberTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($memberStats['by_type'], 'member_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($memberStats['by_type'], 'count')); ?>,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#9ca3af'
                        }
                    }
                }
            }
        });
        
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active state from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('text-indigo-400', 'border-indigo-400');
                button.classList.add('text-gray-400', 'border-transparent');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-content').classList.remove('hidden');
            
            // Set active state for clicked tab button
            const activeButton = document.getElementById(tabName + '-tab');
            activeButton.classList.remove('text-gray-400', 'border-transparent');
            activeButton.classList.add('text-indigo-400', 'border-indigo-400');
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
