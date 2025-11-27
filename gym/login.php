<?php
require_once 'helpers/functions.php';
require_once 'models/Admin.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'login') {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (empty($errors)) {
            $admin = new Admin();
            if ($admin->login($username, $password)) {
                redirect('index.php', 'Login successful! Welcome back.', 'success');
            } else {
                $errors[] = 'Invalid username or password';
            }
        }
        
        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
        }
    } elseif ($action === 'register') {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $admin = new Admin();
            if ($admin->create($username, $password)) {
                redirect('login.php', 'Registration successful! Please login.', 'success');
            } else {
                $errors[] = 'Username already exists or registration failed';
            }
        }
        
        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Club Management System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
        }
        
        .btn-glow {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-glow:hover::before {
            left: 100%;
        }
        
        .form-input {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .tab-active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-6 floating">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mb-3 shadow-lg">
                <i class="fas fa-dumbbell text-xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1">GYM PRO</h1>
            <p class="text-gray-400 text-xs">Management System</p>
        </div>

        <!-- Login/Register Form -->
        <div class="glass-card p-4 fade-in">
            <!-- Tab Navigation -->
            <div class="flex mb-4 border-b border-gray-700">
                <button onclick="showTab('login')" id="loginTab" class="flex-1 py-2 px-3 text-center text-xs font-medium text-gray-400 border-b-2 border-transparent hover:text-white transition-all tab-active">
                    Login
                </button>
                <button onclick="showTab('register')" id="registerTab" class="flex-1 py-2 px-3 text-center text-xs font-medium text-gray-400 border-b-2 border-transparent hover:text-white transition-all">
                    Register
                </button>
            </div>
            
            <!-- Login Form -->
            <div id="loginForm" class="space-y-3">
                <h2 class="text-lg font-semibold text-white mb-3 text-center">Admin Login</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-3 py-2 rounded-lg mb-3 text-xs">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="login">
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg text-white placeholder-gray-500 focus:outline-none form-input text-sm"
                            placeholder="Enter username"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            class="w-full px-3 py-2 rounded-lg text-white placeholder-gray-500 focus:outline-none form-input text-sm"
                            placeholder="Enter password"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-2 px-3 rounded-lg btn-glow hover:from-indigo-600 hover:to-purple-700 text-sm"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div id="registerForm" class="space-y-3 hidden">
                <h2 class="text-lg font-semibold text-white mb-3 text-center">Create Account</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-3 py-2 rounded-lg mb-3 text-xs">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="register">
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg text-white placeholder-gray-500 focus:outline-none form-input text-sm"
                            placeholder="Choose username"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            class="w-full px-3 py-2 rounded-lg text-white placeholder-gray-500 focus:outline-none form-input text-sm"
                            placeholder="Choose password"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-medium mb-1">
                            <i class="fas fa-lock mr-2"></i>Confirm Password
                        </label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="w-full px-3 py-2 rounded-lg text-white placeholder-gray-500 focus:outline-none form-input text-sm"
                            placeholder="Confirm password"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold py-2 px-3 rounded-lg btn-glow hover:from-green-600 hover:to-emerald-700 text-sm"
                    >
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>
                </form>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-gray-500 text-xs">
                    <i class="fas fa-info-circle mr-2"></i>
                    Default: admin / admin123
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-gray-500 text-xs">
                © 2024 Fitness Club Management System
            </p>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            
            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                loginTab.classList.add('tab-active');
                registerTab.classList.remove('tab-active');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                registerTab.classList.add('tab-active');
                loginTab.classList.remove('tab-active');
            }
        }
        
        // Add form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                
                if (action === 'login') {
                    const username = this.querySelector('input[name="username"]').value.trim();
                    const password = this.querySelector('input[name="password"]').value;
                    
                    if (!username || !password) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                        return false;
                    }
                } else if (action === 'register') {
                    const username = this.querySelector('input[name="username"]').value.trim();
                    const password = this.querySelector('input[name="password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                    
                    if (!username || !password || !confirmPassword) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                        return false;
                    }
                    
                    if (username.length < 3) {
                        e.preventDefault();
                        alert('Username must be at least 3 characters');
                        return false;
                    }
                    
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters');
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match');
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>
