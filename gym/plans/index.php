<?php
require_once '../helpers/functions.php';
require_once '../models/Plan.php';

// Require login
requireLogin();

// Handle search
$search = sanitize($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$plan = new Plan();
$result = $plan->getAll($search, $page, 10);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    
    if ($plan->delete($deleteId)) {
        redirect('index.php', 'Plan deleted successfully!', 'success');
    } else {
        redirect('index.php', 'Cannot delete plan with existing payments', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - Fitness Club Management System</title>
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
        
        .plan-card {
            transition: all 0.3s ease;
        }
        
        .plan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
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
        
        .search-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
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
                <h1 class="text-2xl font-bold mb-1">Plans Management</h1>
                <p class="text-gray-400 text-sm">Manage fitness club membership plans and pricing</p>
            </div>
            <a href="add.php" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-3 py-2 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all btn-glow text-sm">
                <i class="fas fa-plus mr-1"></i>Add Plan
            </a>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Search -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="flex gap-3">
                <div class="flex-1">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search plans..."
                        class="w-full px-3 py-2 bg-slate-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 search-input text-sm"
                    >
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </form>
        </div>
        
        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if ($result && !empty($result['plans'])): ?>
                <?php foreach ($result['plans'] as $plan_data): ?>
                    <div class="plan-card glass-card p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($plan_data['plan_name']); ?></h3>
                                <div class="text-2xl font-bold price-tag">
                                    ₱<?php echo number_format($plan_data['price'], 2); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-1 bg-indigo-500/20 text-indigo-400 rounded-full text-xs mb-2">
                                    <?php echo $plan_data['duration_months']; ?> month<?php echo $plan_data['duration_months'] > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                        </div>
                        
                        <p class="text-gray-400 text-xs mb-3">
                            <?php echo htmlspecialchars($plan_data['description'] ?? 'No description available'); ?>
                        </p>
                        
                        <div class="flex justify-between items-center pt-3 border-t border-gray-700">
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('M d, Y', strtotime($plan_data['created_at'])); ?>
                            </div>
                            <div class="flex space-x-2">
                                <a href="edit.php?id=<?php echo $plan_data['id']; ?>" class="text-indigo-400 hover:text-indigo-300 text-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $plan_data['id']; ?>, '<?php echo htmlspecialchars($plan_data['plan_name']); ?>')" class="text-red-400 hover:text-red-300 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8">
                    <i class="fas fa-clipboard-list text-5xl text-gray-600 mb-3"></i>
                    <h3 class="text-lg font-semibold mb-2">No Plans Found</h3>
                    <p class="text-gray-400 text-sm mb-4">Get started by creating your first membership plan</p>
                    <a href="add.php" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all btn-glow text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Your First Plan
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($result && $result['total_pages'] > 1): ?>
            <div class="mt-6 flex justify-center">
                <div class="flex space-x-1">
                    <?php if ($result['page'] > 1): ?>
                        <a href="?page=<?php echo $result['page'] - 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-2 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700 text-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $result['page'] - 2); $i <= min($result['total_pages'], $result['page'] + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-2 py-1 <?php echo $i === $result['page'] ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-gray-300'; ?> rounded hover:bg-slate-700 text-sm">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($result['page'] < $result['total_pages']): ?>
                        <a href="?page=<?php echo $result['page'] + 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-2 py-1 bg-slate-800 text-gray-300 rounded hover:bg-slate-700 text-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="glass-card p-2 max-w-xs w-full mx-4">
            <h3 class="text-sm font-semibold mb-2">Confirm Delete</h3>
            <p class="text-gray-400 mb-3 text-xs">Are you sure you want to delete "<span id="planName" class="font-semibold text-white"></span>"? This action cannot be undone if there are existing payments.</p>
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
            document.getElementById('planName').textContent = name;
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
