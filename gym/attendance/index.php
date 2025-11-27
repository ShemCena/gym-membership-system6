<?php
require_once '../helpers/functions.php';
require_once '../models/Attendance.php';
require_once '../models/Member.php';

// Require login
requireLogin();

// Handle search and filters
$search = sanitize($_GET['search'] ?? '');
$date = sanitize($_GET['date'] ?? date('Y-m-d'));
$page = max(1, (int)($_GET['page'] ?? 1));

$attendance = new Attendance();
$member = new Member();

$result = $attendance->getAll($search, $date, $page, 10);
$todayAttendance = $attendance->getTodayAttendance();
$stats = $attendance->getStatistics();

// Handle check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'])) {
    $memberId = (int)$_POST['member_id'];
    $result = $attendance->checkIn($memberId);
    
    if ($result['success']) {
        redirect('index.php', $result['message'], 'success');
    } else {
        redirect('index.php', $result['message'], 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Fitness Club Management System</title>
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
        
        .checkin-card {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }
        
        .checkin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.2);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .search-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
        
        .table-row:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .attendance-stat {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .checkin-success {
            animation: checkinSuccess 0.5s ease-out;
        }
        
        @keyframes checkinSuccess {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .member-option:hover {
            background: rgba(99, 102, 241, 0.1);
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
            <a href="../payments/index.php" class="sidebar-item flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
                <i class="fas fa-credit-card w-4 mr-2 text-sm"></i>
                <span class="text-sm">Payments</span>
            </a>
            <a href="index.php" class="sidebar-item active flex items-center p-2 rounded-lg text-gray-300 hover:text-white">
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
                <h1 class="text-2xl font-bold mb-1">Attendance Management</h1>
                <p class="text-gray-400 text-sm">Track member check-ins and attendance patterns</p>
            </div>
            <div class="text-xs text-gray-400">
                <i class="fas fa-clock mr-1"></i>
                <?php echo date('l, F d, Y'); ?>
            </div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Attendance Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-day text-white text-sm"></i>
                    </div>
                    <span class="text-green-400 text-xs pulse-animation">Today</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($stats['checkins_today']); ?></h3>
                <p class="text-gray-400 text-xs">Check-ins Today</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-week text-white text-sm"></i>
                    </div>
                    <span class="text-blue-400 text-xs">Week</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($stats['checkins_this_week']); ?></h3>
                <p class="text-gray-400 text-xs">This Week</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-sm"></i>
                    </div>
                    <span class="text-purple-400 text-xs">Month</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($stats['checkins_this_month']); ?></h3>
                <p class="text-gray-400 text-xs">This Month</p>
            </div>
            
            <div class="stat-card glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <span class="text-orange-400 text-xs">Average</span>
                </div>
                <h3 class="text-xl font-bold mb-1"><?php echo number_format($stats['avg_daily_checkins'], 1); ?></h3>
                <p class="text-gray-400 text-xs">Daily Average</p>
            </div>
        </div>
        
        <!-- Quick Check-in -->
        <div class="checkin-card glass-card p-3 mb-4">
            <h3 class="text-base font-semibold mb-2 text-sm">
                <i class="fas fa-user-check mr-2 text-green-400"></i>Quick Check-in
            </h3>
            <form method="POST" class="flex gap-2">
                <div class="flex-1">
                    <select name="member_id" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-green-500 text-sm" required>
                        <option value="">Select a member to check-in...</option>
                        <?php
                        $activeMembers = $member->getAll('', 'Active', '', 1, 100);
                        if ($activeMembers && !empty($activeMembers['members'])):
                            foreach ($activeMembers['members'] as $m):
                        ?>
                            <option value="<?php echo $m['id']; ?>" class="member-option">
                                <?php echo htmlspecialchars($m['full_name']); ?> - <?php echo htmlspecialchars($m['member_type']); ?>
                            </option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
                <button type="submit" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all btn-glow text-sm">
                    <i class="fas fa-sign-in-alt mr-1"></i>Check In
                </button>
            </form>
        </div>
        
        <!-- Filters and Search -->
        <div class="glass-card p-3 mb-3">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search members..."
                        class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 search-input text-sm"
                    >
                </div>
                
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Date</label>
                    <input 
                        type="date" 
                        name="date" 
                        value="<?php echo htmlspecialchars($date); ?>"
                        class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 text-sm"
                    >
                </div>
                
                <div class="md:col-span-3 flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Attendance History -->
        <div class="glass-card overflow-hidden">
            <div class="px-3 py-2 border-b border-gray-700">
                <h3 class="text-base font-semibold text-sm">Attendance History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-800 border-b border-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Check-in Date</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Check-in Time</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Day</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($result && !empty($result['attendance'])): ?>
                            <?php foreach ($result['attendance'] as $attendance): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img src="../uploads/members/<?php echo htmlspecialchars($attendance['photo'] ?? 'default.jpg'); ?>" 
                                                 alt="Member" class="w-6 h-6 rounded-full mr-2">
                                            <div>
                                                <div class="text-xs font-medium text-white"><?php echo htmlspecialchars($attendance['full_name']); ?></div>
                                                <div class="text-xs text-gray-400">ID: #<?php echo str_pad($attendance['member_id'], 5, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getMemberTypeBadgeColor($attendance['member_type']); ?>">
                                            <?php echo htmlspecialchars($attendance['member_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="text-xs text-white"><?php echo date('M d, Y', strtotime($attendance['checkin_date'])); ?></div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="text-xs text-white"><?php echo date('h:i A', strtotime($attendance['checkin_time'])); ?></div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="text-xs text-gray-400"><?php echo date('l', strtotime($attendance['checkin_date'])); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-400">
                                    <i class="fas fa-calendar-check text-2xl mb-2"></i>
                                    <p class="text-sm">No attendance records found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($result && $result['total_pages'] > 1): ?>
                <div class="px-3 py-2 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-400">
                            Showing <?php echo (($result['page'] - 1) * 10) + 1; ?> to 
                            <?php echo min($result['page'] * 10, $result['total']); ?> of 
                            <?php echo $result['total']; ?> records
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($result['page'] > 1): ?>
                                <a href="?page=<?php echo $result['page'] - 1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date); ?>" 
                                   class="px-2 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700 text-xs">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $result['page'] - 2); $i <= min($result['total_pages'], $result['page'] + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date); ?>" 
                                   class="px-2 py-1 <?php echo $i === $result['page'] ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-gray-300'; ?> rounded hover:bg-slate-700 text-xs">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($result['page'] < $result['total_pages']): ?>
                                <a href="?page=<?php echo $result['page'] + 1; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date); ?>" 
                                   class="px-2 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700 text-xs">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh today's check-ins every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
        
        // Add check-in animation
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking in...';
            button.disabled = true;
        });
        
        // Highlight today's date in date picker
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput && dateInput.value === '<?php echo date('Y-m-d'); ?>') {
                dateInput.style.borderColor = '#10b981';
            }
        });
    </script>
</body>
</html>
