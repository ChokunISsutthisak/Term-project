<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Handle form submission (MUST be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    // Update basic info
    if (!empty($fullName)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $address, $user['id']]);
        
        // Update avatar if provided
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $upload = uploadFile($_FILES['avatar'], 'avatars');
            if ($upload['success']) {
                $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$upload['filename'], $user['id']]);
            }
        }
        
        setFlash('success', 'อัปเดตข้อมูลเรียบร้อย');
    }
    
    // Change password if provided
    if (!empty($currentPassword) && !empty($newPassword)) {
        if (password_verify($currentPassword, $user['password'])) {
            if (strlen($newPassword) >= 6) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);
                setFlash('success', 'เปลี่ยนรหัสผ่านเรียบร้อย');
            } else {
                setFlash('error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
            }
        } else {
            setFlash('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }
    }
    
    redirect(baseUrl('profile.php'));
}

// Refresh user data
$user = getCurrentUser();

// Now include header (HTML output starts here)
$pageTitle = 'โปรไฟล์ - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

// Get stats based on role
$stats = [];
if ($user['role'] === 'customer') {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
    $stmt->execute([$user['id']]);
    $stats['orders'] = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE customer_id = ? AND payment_status = 'paid'");
    $stmt->execute([$user['id']]);
    $stats['spent'] = $stmt->fetch()['total'];
}
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-user mr-2 text-primary-500"></i>โปรไฟล์ของฉัน
    </h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
            <div class="relative inline-block mb-4">
                <img src="<?= $user['avatar'] ? baseUrl('uploads/' . $user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=random&size=128' ?>" 
                     class="w-32 h-32 rounded-full mx-auto border-4 border-primary-200">
            </div>
            
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['full_name']) ?></h2>
            <p class="text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
            
            <div class="mt-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                <?= $user['role'] === 'customer' ? 'bg-blue-100 text-blue-600' : 
                   ($user['role'] === 'seller' ? 'bg-green-100 text-green-600' : 
                   ($user['role'] === 'rider' ? 'bg-orange-100 text-orange-600' : 'bg-purple-100 text-purple-600')) ?>">
                <?= ucfirst($user['role']) ?>
            </div>
            
            <?php if ($user['role'] === 'customer'): ?>
                <div class="mt-6 pt-6 border-t">
                    <div class="bg-gradient-to-r from-primary-500 to-accent-500 text-white rounded-xl p-4">
                        <p class="text-sm opacity-80">ยอดเครดิต</p>
                        <p class="text-2xl font-bold"><?= formatMoney($user['credit_balance']) ?></p>
                    </div>
                    <a href="<?= baseUrl('topup.php') ?>" class="block mt-3 py-2 border-2 border-primary-500 text-primary-500 rounded-lg hover:bg-primary-50 transition">
                        <i class="fas fa-plus mr-1"></i>เติมเงิน
                    </a>
                </div>
                
                <div class="mt-6 grid grid-cols-2 gap-4 text-center">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['orders'] ?></p>
                        <p class="text-gray-500 text-xs">ออเดอร์</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['spent']) ?></p>
                        <p class="text-gray-500 text-xs">ใช้ไป</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Edit Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="font-bold text-lg text-gray-800 mb-4">
                    <i class="fas fa-edit mr-2 text-primary-500"></i>แก้ไขข้อมูล
                </h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">รูปโปรไฟล์</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">ชื่อ-นามสกุล</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <?php if ($user['role'] === 'customer'): ?>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">ที่อยู่จัดส่ง</label>
                            <textarea name="address" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="w-full gradient-bg text-white py-3 rounded-xl font-semibold hover:opacity-90 transition">
                        <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="font-bold text-lg text-gray-800 mb-4">
                    <i class="fas fa-lock mr-2 text-primary-500"></i>เปลี่ยนรหัสผ่าน
                </h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>">
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                               placeholder="อย่างน้อย 6 ตัวอักษร">
                    </div>
                    
                    <button type="submit" class="w-full py-3 border-2 border-primary-500 text-primary-500 rounded-xl font-semibold hover:bg-primary-50 transition">
                        <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
