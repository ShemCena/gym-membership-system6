<?php
require_once '../helpers/functions.php';
require_once '../models/Payment.php';
require_once '../models/Member.php';
require_once '../models/Plan.php';

// Require login
requireLogin();

// Handle search and filters
$search = sanitize($_GET['search'] ?? '');
$memberId = (int)($_GET['member_id'] ?? 0);
$planId = (int)($_GET['plan_id'] ?? 0);
$startDate = sanitize($_GET['start_date'] ?? '');
$endDate = sanitize($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$payment = new Payment();
$member = new Member();
$plan = new Plan();

$result = $payment->getAll($search, $memberId, $planId, $startDate, $endDate, $page, 10);
$members = $member->getAll('', '', '', 1, 100); // Get all members for dropdown
$plans = $plan->getAllActive(); // Get all active plans for dropdown
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Fitness Club Management System</title>
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
        
        .payment-card {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }
        
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.2);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .discount-badge {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(16, 185, 129, 0.2) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .search-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
        
        .table-row:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .revenue-stat {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
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
                <h1 class="text-2xl font-bold mb-1">Payment Management</h1>
                <p class="text-gray-400 text-sm">Track and manage member payments with automatic discounts</p>
            </div>
            <a href="add.php" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-3 py-2 rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all btn-glow text-sm">
                <i class="fas fa-plus mr-1"></i>Record Payment
            </a>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Revenue Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <?php
            $stats = $payment->getStatistics();
            ?>
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-sm"></i>
                    </div>
                    <span class="text-green-400 text-sm">Total</span>
                </div>
                <h3 class="text-xl font-bold mb-1">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                <p class="text-gray-400 text-xs">Total Revenue</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-white text-sm"></i>
                    </div>
                    <span class="text-blue-400 text-sm">Month</span>
                </div>
                <h3 class="text-xl font-bold mb-1">$<?php echo number_format($stats['revenue_this_month'], 2); ?></h3>
                <p class="text-gray-400 text-xs">This Month</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-white text-sm"></i>
                    </div>
                    <span class="text-purple-400 text-sm">Saved</span>
                </div>
                <h3 class="text-xl font-bold mb-1">$<?php echo number_format($stats['total_discounts'], 2); ?></h3>
                <p class="text-gray-400 text-xs">Total Discounts</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-receipt text-white text-sm"></i>
                    </div>
                    <span class="text-orange-400 text-sm">Count</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($stats['total_payments']); ?></h3>
                <p class="text-gray-400 text-xs">Total Payments</p>
            </div>
        </div>
        
        <!-- Filters and Search -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search payments..."
                        class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 search-input text-sm"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Member</label>
                    <select name="member_id" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">All Members</option>
                        <?php if ($members && !empty($members['members'])): ?>
                            <?php foreach ($members['members'] as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo $memberId === $m['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Plan</label>
                    <select name="plan_id" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">All Plans</option>
                        <?php if ($plans): ?>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $planId === $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['plan_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                        <i class="fas fa-search mr-1"></i>Search
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Payments Table -->
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-800 border-b border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Payment Info</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Discount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Final</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($result && !empty($result['payments'])): ?>
                            <?php foreach ($result['payments'] as $payment_data): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">Payment #<?php echo str_pad($payment_data['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                        <div class="text-sm text-gray-400">Paid by: <?php echo htmlspecialchars($payment_data['paid_by']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <img class="h-8 w-8 rounded-full" src="../uploads/members/<?php echo htmlspecialchars($payment_data['photo'] ?? 'default.jpg'); ?>" alt="">
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($payment_data['full_name']); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($payment_data['member_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white"><?php echo htmlspecialchars($payment_data['plan_name']); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo $payment_data['duration_months']; ?> month(s)</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">$<?php echo number_format($payment_data['original_amount'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($payment_data['discount_percent'] > 0): ?>
                                            <div class="discount-badge px-2 py-1 rounded text-xs">
                                                <div class="text-green-400 font-semibold"><?php echo $payment_data['discount_percent']; ?>%</div>
                                                <div class="text-gray-300">-$<?php echo number_format($payment_data['discount_amount'], 2); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-400">$<?php echo number_format($payment_data['final_amount'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white"><?php echo date('M d, Y', strtotime($payment_data['payment_date'])); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo date('h:i A', strtotime($payment_data['created_at'])); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fas fa-credit-card text-4xl mb-4"></i>
                                    <p>No payments found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($result && $result['total_pages'] > 1): ?>
                <div class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Showing <?php echo (($result['page'] - 1) * 10) + 1; ?> to 
                            <?php echo min($result['page'] * 10, $result['total']); ?> of 
                            <?php echo $result['total']; ?> payments
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($result['page'] > 1): ?>
                                <a href="?page=<?php echo $result['page'] - 1; ?>&search=<?php echo urlencode($search); ?>&member_id=<?php echo urlencode($memberId); ?>&plan_id=<?php echo urlencode($planId); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                   class="px-3 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $result['page'] - 2); $i <= min($result['total_pages'], $result['page'] + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&member_id=<?php echo urlencode($memberId); ?>&plan_id=<?php echo urlencode($planId); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                   class="px-3 py-1 <?php echo $i === $result['page'] ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-gray-300'; ?> rounded hover:bg-slate-700">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($result['page'] < $result['total_pages']): ?>
                                <a href="?page=<?php echo $result['page'] + 1; ?>&search=<?php echo urlencode($search); ?>&member_id=<?php echo urlencode($memberId); ?>&plan_id=<?php echo urlencode($planId); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                   class="px-3 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
