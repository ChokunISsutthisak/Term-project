<?php
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in (MUST be before any HTML output)
if (isLoggedIn()) {
    redirect(baseUrl());
}

// Handle login (MUST be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        setFlash('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            setFlash('success', 'เข้าสู่ระบบสำเร็จ');
            
            // Redirect based on role
            switch ($result['user']['role']) {
                case 'admin':
                    redirect(baseUrl('admin/dashboard.php'));
                    break;
                case 'seller':
                    redirect(baseUrl('seller/dashboard.php'));
                    break;
                case 'rider':
                    redirect(baseUrl('rider/dashboard.php'));
                    break;
                default:
                    redirect(baseUrl());
            }
        } else {
            setFlash('error', $result['message']);
            redirect(baseUrl('login.php'));
        }
    }
}

// Now include header (HTML output starts here)
$pageTitle = 'เข้าสู่ระบบ - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen gradient-bg flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="glass-card rounded-2xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 gradient-bg rounded-full mb-4">
                    <i class="fas fa-utensils text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800"><?= SITE_NAME ?></h1>
                <p class="text-gray-500 mt-2">เข้าสู่ระบบเพื่อสั่งอาหาร</p>
            </div>
            
            <form method="POST" class="space-y-6">
                <!-- Username -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-user mr-2 text-primary-500"></i>ชื่อผู้ใช้ หรือ อีเมล
                    </label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                           placeholder="กรอกชื่อผู้ใช้หรืออีเมล">
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock mr-2 text-primary-500"></i>รหัสผ่าน
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                               placeholder="กรอกรหัสผ่าน">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-primary-500 rounded">
                        <span class="ml-2 text-gray-600">จดจำฉัน</span>
                    </label>
                    <a href="#" class="text-primary-500 hover:underline text-sm">ลืมรหัสผ่าน?</a>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="w-full gradient-bg text-white py-3 rounded-lg font-semibold hover:opacity-90 transition transform hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                </button>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-4 text-gray-500 text-sm">หรือ</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>
            
            <!-- Register Link -->
            <div class="text-center">
                <p class="text-gray-600">ยังไม่มีบัญชี?</p>
                <a href="<?= baseUrl('register.php') ?>" class="inline-block mt-2 text-primary-500 font-semibold hover:underline">
                    สมัครสมาชิกเลย <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <!-- Demo Accounts -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 font-medium mb-2">
                    <i class="fas fa-info-circle mr-1"></i> บัญชีทดสอบ (รหัสผ่าน: password)
                </p>
                <div class="text-xs text-gray-500 space-y-1">
                    <p><strong>Admin:</strong> admin</p>
                    <p><strong>Seller:</strong> seller1</p>
                    <p><strong>Rider:</strong> rider1</p>
                    <p><strong>Customer:</strong> customer1</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
