<?php
require_once '../helpers/functions.php';
require_once '../models/Member.php';

// Require login
requireLogin();

// Handle search and filters
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$memberType = sanitize($_GET['member_type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$member = new Member();
$result = $member->getAll($search, $status, $memberType, $page, 10);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    
    if ($member->delete($deleteId)) {
        redirect('index.php', 'Member deleted successfully!', 'success');
    } else {
        redirect('index.php', 'Cannot delete member with existing records', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - Fitness Club Management System</title>
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
        
        .member-card {
            transition: all 0.3s ease;
        }
        
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
        }
        
        .badge-glow {
            box-shadow: 0 0 10px currentColor;
        }
        
        .search-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
        
        .table-row:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
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
                <h1 class="text-2xl font-bold mb-1">Members Management</h1>
                <p class="text-gray-400 text-sm">Manage gym members and their information</p>
            </div>
            <a href="add.php" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-3 py-2 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all btn-glow text-sm">
                <i class="fas fa-plus mr-1"></i>Add Member
            </a>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Filters and Search -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-4">
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search members..."
                        class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 search-input transition-all text-sm"
                    >
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Status</label>
                    <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Expired" <?php echo $status === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-400 mb-1 text-xs">Member Type</label>
                    <select name="member_type" class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500 text-sm">
                        <option value="">All Types</option>
                        <option value="Regular" <?php echo $memberType === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                        <option value="Student" <?php echo $memberType === 'Student' ? 'selected' : ''; ?>>Student</option>
                        <option value="Senior" <?php echo $memberType === 'Senior' ? 'selected' : ''; ?>>Senior</option>
                    </select>
                </div>
                
                <div class="md:col-span-2 flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Members Table -->
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-800 border-b border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Expiry</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($result && !empty($result['members'])): ?>
                            <?php foreach ($result['members'] as $member_data): ?>
                                <tr class="table-row transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img src="../uploads/members/<?php echo htmlspecialchars($member_data['photo'] ?? 'default.jpg'); ?>" 
                                                 alt="Member" class="w-10 h-10 rounded-full mr-3">
                                            <div>
                                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($member_data['full_name']); ?></div>
                                                <div class="text-sm text-gray-400">ID: #<?php echo str_pad($member_data['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white"><?php echo htmlspecialchars($member_data['email']); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo htmlspecialchars($member_data['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getMemberTypeBadgeColor($member_data['member_type']); ?>">
                                            <?php echo htmlspecialchars($member_data['member_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeColor($member_data['status']); ?>">
                                            <?php echo htmlspecialchars($member_data['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white"><?php echo date('M d, Y', strtotime($member_data['expiry_date'])); ?></div>
                                        <div class="text-sm text-gray-400">
                                            <?php 
                                            $days = daysUntilExpiry($member_data['expiry_date']);
                                            echo $days > 0 ? "$days days left" : "Expired";
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="edit.php?id=<?php echo $member_data['id']; ?>" class="text-indigo-400 hover:text-indigo-300">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?php echo $member_data['id']; ?>" class="text-green-400 hover:text-green-300">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $member_data['id']; ?>, '<?php echo htmlspecialchars($member_data['full_name']); ?>')" class="text-red-400 hover:text-red-300">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    <p>No members found</p>
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
                            <?php echo $result['total']; ?> members
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($result['page'] > 1): ?>
                                <a href="?page=<?php echo $result['page'] - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&member_type=<?php echo urlencode($memberType); ?>" 
                                   class="px-3 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $result['page'] - 2); $i <= min($result['total_pages'], $result['page'] + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&member_type=<?php echo urlencode($memberType); ?>" 
                                   class="px-3 py-1 <?php echo $i === $result['page'] ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-gray-300'; ?> rounded hover:bg-slate-700">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($result['page'] < $result['total_pages']): ?>
                                <a href="?page=<?php echo $result['page'] + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&member_type=<?php echo urlencode($memberType); ?>" 
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
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="glass-card p-2 max-w-xs w-full mx-4">
            <h3 class="text-sm font-semibold mb-2">Confirm Delete</h3>
            <p class="text-gray-400 mb-3 text-xs">Are you sure you want to delete <span id="memberName" class="font-semibold text-white"></span>? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_id" id="deleteId">
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-2 py-1 bg-gray-700 text-white rounded hover:bg-gray-600 text-xs">
                        Cancel
                    </button>
                    <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function confirmDelete(id, name) {
            document.getElementById('memberName').textContent = name;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
