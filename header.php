<?php require_once __DIR__ . '/../config/auth.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'prompt': ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        accent: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #ef4444 0%, #f97316 50%, #fbbf24 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-scale:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 font-prompt min-h-screen">
    <!-- Navigation -->
    <nav class="gradient-bg shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="<?= baseUrl() ?>" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-utensils text-primary-500 text-xl"></i>
                    </div>
                    <span class="text-white font-bold text-xl"><?= SITE_NAME ?></span>
                </a>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?= baseUrl() ?>" class="text-white hover:text-accent-200 transition">
                        <i class="fas fa-home mr-1"></i> หน้าแรก
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        
                        <?php if ($user['role'] === 'customer'): ?>
                            <a href="<?= baseUrl('cart.php') ?>" class="text-white hover:text-accent-200 transition relative">
                                <i class="fas fa-shopping-cart mr-1"></i> ตะกร้า
                                <span id="cart-count" class="absolute -top-2 -right-2 bg-accent-400 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                            </a>
                            <a href="<?= baseUrl('orders.php') ?>" class="text-white hover:text-accent-200 transition">
                                <i class="fas fa-receipt mr-1"></i> ออเดอร์
                            </a>
                            <a href="<?= baseUrl('topup.php') ?>" class="text-white hover:text-accent-200 transition">
                                <i class="fas fa-wallet mr-1"></i> <?= formatMoney($user['credit_balance']) ?>
                            </a>
                        <?php elseif ($user['role'] === 'seller'): ?>
                            <a href="<?= baseUrl('seller/dashboard.php') ?>" class="text-white hover:text-accent-200 transition">
                                <i class="fas fa-store mr-1"></i> จัดการร้าน
                            </a>
                        <?php elseif ($user['role'] === 'rider'): ?>
                            <a href="<?= baseUrl('rider/dashboard.php') ?>" class="text-white hover:text-accent-200 transition">
                                <i class="fas fa-motorcycle mr-1"></i> งานส่ง
                            </a>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <a href="<?= baseUrl('admin/dashboard.php') ?>" class="text-white hover:text-accent-200 transition">
                                <i class="fas fa-cog mr-1"></i> แอดมิน
                            </a>
                        <?php endif; ?>
                        
                        <div class="relative group">
                            <button class="flex items-center text-white hover:text-accent-200 transition">
                                <img src="<?= $user['avatar'] ? baseUrl('uploads/' . $user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=random' ?>" 
                                     class="w-8 h-8 rounded-full mr-2 border-2 border-white">
                                <?= htmlspecialchars($user['full_name']) ?>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block">
                                <a href="<?= baseUrl('profile.php') ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> โปรไฟล์
                                </a>
                                <a href="<?= baseUrl('logout.php') ?>" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= baseUrl('login.php') ?>" class="text-white hover:text-accent-200 transition">
                            <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                        </a>
                        <a href="<?= baseUrl('register.php') ?>" class="bg-white text-primary-500 px-4 py-2 rounded-full hover:bg-accent-100 transition font-medium">
                            <i class="fas fa-user-plus mr-1"></i> สมัครสมาชิก
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-primary-700 pb-4">
            <div class="px-4 space-y-2">
                <a href="<?= baseUrl() ?>" class="block text-white py-2">หน้าแรก</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= baseUrl('cart.php') ?>" class="block text-white py-2">ตะกร้า</a>
                    <a href="<?= baseUrl('orders.php') ?>" class="block text-white py-2">ออเดอร์</a>
                    <a href="<?= baseUrl('logout.php') ?>" class="block text-white py-2">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="<?= baseUrl('login.php') ?>" class="block text-white py-2">เข้าสู่ระบบ</a>
                    <a href="<?= baseUrl('register.php') ?>" class="block text-white py-2">สมัครสมาชิก</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="<?= $flash['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> px-4 py-3 rounded-lg border flex items-center justify-between">
                <span><i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i><?= $flash['message'] ?></span>
                <button onclick="this.parentElement.remove()" class="text-lg">&times;</button>
            </div>
        </div>
    <?php endif; ?>
    
    <main>
