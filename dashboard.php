<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('seller');

$pageTitle = 'แดชบอร์ดร้านค้า - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$db = getDB();

// Get seller's restaurant
$stmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    redirect(baseUrl('seller/register.php'));
}

// Get stats
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE restaurant_id = ?");
$stmt->execute([$restaurant['id']]);
$totalOrders = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$restaurant['id']]);
$todayOrders = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE restaurant_id = ? AND payment_status = 'paid'");
$stmt->execute([$restaurant['id']]);
$totalRevenue = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM menu_items WHERE restaurant_id = ?");
$stmt->execute([$restaurant['id']]);
$menuCount = $stmt->fetch()['total'];

// Get recent orders
$stmt = $db->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone 
                      FROM orders o 
                      JOIN users u ON o.customer_id = u.id 
                      WHERE o.restaurant_id = ? 
                      ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$restaurant['id']]);
$recentOrders = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'รอยืนยัน', 'color' => 'yellow'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'color' => 'blue'],
    'preparing' => ['label' => 'กำลังเตรียม', 'color' => 'indigo'],
    'ready' => ['label' => 'พร้อมส่ง', 'color' => 'purple'],
    'picked_up' => ['label' => 'ไรเดอร์รับแล้ว', 'color' => 'orange'],
    'delivered' => ['label' => 'ส่งสำเร็จ', 'color' => 'green'],
    'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red'],
];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">ยินดีต้อนรับ, <?= htmlspecialchars($user['full_name']) ?></h1>
            <p class="text-gray-500">จัดการร้าน <?= htmlspecialchars($restaurant['name']) ?></p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="<?= baseUrl('seller/menu.php') ?>" class="gradient-bg text-white px-6 py-3 rounded-xl font-medium hover:opacity-90 transition">
                <i class="fas fa-utensils mr-2"></i>จัดการเมนู
            </a>
            <a href="<?= baseUrl('seller/orders.php') ?>" class="bg-white border-2 border-primary-500 text-primary-500 px-6 py-3 rounded-xl font-medium hover:bg-primary-50 transition">
                <i class="fas fa-receipt mr-2"></i>ดูออเดอร์
            </a>
        </div>
    </div>
    
    <!-- Restaurant Status -->
    <?php if ($restaurant['status'] === 'pending'): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8 flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-200 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-yellow-800">รอการอนุมัติ</h3>
                <p class="text-yellow-600">ร้านของคุณกำลังรอแอดมินอนุมัติ กรุณารอสักครู่</p>
            </div>
        </div>
    <?php elseif ($restaurant['status'] === 'rejected'): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8 flex items-center gap-4">
            <div class="w-12 h-12 bg-red-200 rounded-full flex items-center justify-center">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-red-800">ไม่ผ่านการอนุมัติ</h3>
                <p class="text-red-600">กรุณาติดต่อแอดมินเพื่อสอบถามเหตุผล</p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">ออเดอร์วันนี้</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $todayOrders ?></p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-primary-500"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">รายได้รวม</p>
                    <p class="text-3xl font-bold text-gray-800"><?= number_format($totalRevenue) ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-baht-sign text-green-500"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">ออเดอร์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $totalOrders ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-receipt text-blue-500"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-accent-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">จำนวนเมนู</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $menuCount ?></p>
                </div>
                <div class="w-12 h-12 bg-accent-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-utensils text-accent-500"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-receipt mr-2 text-primary-500"></i>ออเดอร์ล่าสุด
                </h2>
                <a href="<?= baseUrl('seller/orders.php') ?>" class="text-primary-500 hover:underline">
                    ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <?php if (empty($recentOrders)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>ยังไม่มีออเดอร์</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recentOrders as $order): ?>
                        <?php $status = $statusLabels[$order['status']]; ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <p class="font-semibold text-gray-800">ออเดอร์ #<?= $order['id'] ?></p>
                                <p class="text-gray-500 text-sm"><?= htmlspecialchars($order['customer_name']) ?></p>
                                <p class="text-gray-400 text-xs"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-primary-500"><?= formatMoney($order['total_amount']) ?></p>
                                <span class="inline-block px-2 py-1 rounded-full text-xs bg-<?= $status['color'] ?>-100 text-<?= $status['color'] ?>-600">
                                    <?= $status['label'] ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Shop Info -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-store mr-2 text-primary-500"></i>ข้อมูลร้าน
            </h2>
            
            <div class="text-center mb-6">
                <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-primary-400 to-accent-400 overflow-hidden mb-4">
                    <?php if ($restaurant['image']): ?>
                        <img src="<?= baseUrl('uploads/' . $restaurant['image']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-store text-3xl text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-lg"><?= htmlspecialchars($restaurant['name']) ?></h3>
                <span class="inline-block px-3 py-1 rounded-full text-sm bg-<?= $restaurant['status'] === 'approved' ? 'green' : 'yellow' ?>-100 text-<?= $restaurant['status'] === 'approved' ? 'green' : 'yellow' ?>-600 mt-2">
                    <?= $restaurant['status'] === 'approved' ? 'เปิดให้บริการ' : 'รอการอนุมัติ' ?>
                </span>
            </div>
            
            <div class="space-y-3 text-sm">
                <p class="flex items-center text-gray-600">
                    <i class="fas fa-tag mr-3 text-gray-400 w-4"></i>
                    <?= htmlspecialchars($restaurant['category'] ?? '-') ?>
                </p>
                <p class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt mr-3 text-gray-400 w-4"></i>
                    <?= htmlspecialchars($restaurant['address']) ?>
                </p>
                <p class="flex items-center text-gray-600">
                    <i class="fas fa-phone mr-3 text-gray-400 w-4"></i>
                    <?= htmlspecialchars($restaurant['phone']) ?>
                </p>
                <p class="flex items-center text-gray-600">
                    <i class="fas fa-clock mr-3 text-gray-400 w-4"></i>
                    <?= substr($restaurant['opening_time'], 0, 5) ?> - <?= substr($restaurant['closing_time'], 0, 5) ?>
                </p>
                <p class="flex items-center text-gray-600">
                    <i class="fas fa-star mr-3 text-yellow-400 w-4"></i>
                    <?= number_format($restaurant['rating'], 1) ?> คะแนน
                </p>
            </div>
            
            <a href="<?= baseUrl('seller/register.php?edit=1') ?>" class="block w-full text-center mt-6 py-3 border-2 border-gray-200 rounded-xl text-gray-600 hover:border-primary-500 hover:text-primary-500 transition">
                <i class="fas fa-edit mr-2"></i>แก้ไขข้อมูลร้าน
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
