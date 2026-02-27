<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('seller');

$pageTitle = 'จัดการออเดอร์ - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$db = getDB();

// Get restaurant
$stmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    redirect(baseUrl('seller/register.php'));
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? '');
    
    $validStatuses = ['confirmed', 'preparing', 'ready'];
    if ($orderId && in_array($newStatus, $validStatuses)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$newStatus, $orderId, $restaurant['id']]);
        setFlash('success', 'อัปเดตสถานะเรียบร้อย');
    }
    
    redirect(baseUrl('seller/orders.php'));
}

// Get orders
$status = $_GET['status'] ?? 'all';
$sql = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone,
        r.full_name as rider_name, r.phone as rider_phone
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.restaurant_id = ?";

if ($status !== 'all') {
    $sql .= " AND o.status = ?";
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
if ($status !== 'all') {
    $stmt->execute([$restaurant['id'], $status]);
} else {
    $stmt->execute([$restaurant['id']]);
}
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'รอยืนยัน', 'color' => 'yellow', 'icon' => 'clock'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'color' => 'blue', 'icon' => 'check'],
    'preparing' => ['label' => 'กำลังเตรียม', 'color' => 'indigo', 'icon' => 'fire'],
    'ready' => ['label' => 'พร้อมส่ง', 'color' => 'purple', 'icon' => 'box'],
    'picked_up' => ['label' => 'ไรเดอร์รับแล้ว', 'color' => 'orange', 'icon' => 'motorcycle'],
    'delivered' => ['label' => 'ส่งสำเร็จ', 'color' => 'green', 'icon' => 'check-circle'],
    'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red', 'icon' => 'times-circle'],
];

$statusTabs = [
    'all' => 'ทั้งหมด',
    'confirmed' => 'ใหม่',
    'preparing' => 'กำลังทำ',
    'ready' => 'พร้อมส่ง',
    'delivered' => 'สำเร็จ',
];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= baseUrl('seller/dashboard.php') ?>" class="text-primary-500 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>กลับไปแดชบอร์ด
        </a>
    </div>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-receipt mr-2 text-primary-500"></i>จัดการออเดอร์
    </h1>
    
    <!-- Status Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <?php foreach ($statusTabs as $key => $label): ?>
            <a href="<?= baseUrl('seller/orders.php?status=' . $key) ?>" 
               class="px-4 py-2 rounded-full font-medium whitespace-nowrap <?= $status === $key ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-2xl p-16 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500">ไม่มีออเดอร์</h3>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <?php 
                $orderStatus = $statusLabels[$order['status']];
                
                // Get order items
                $stmt = $db->prepare("SELECT oi.*, mi.name FROM order_items oi 
                                      JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                      WHERE oi.order_id = ?");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll();
                ?>
                
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <span class="text-lg font-bold text-gray-800">ออเดอร์ #<?= $order['id'] ?></span>
                            <span class="text-gray-500 ml-2"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-<?= $orderStatus['color'] ?>-600 bg-<?= $orderStatus['color'] ?>-100 font-medium">
                                <i class="fas fa-<?= $orderStatus['icon'] ?> mr-1"></i><?= $orderStatus['label'] ?>
                            </span>
                            <span class="font-bold text-primary-500 text-lg"><?= formatMoney($order['total_amount']) ?></span>
                        </div>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Items -->
                        <div class="md:col-span-2">
                            <h4 class="font-medium text-gray-700 mb-3">
                                <i class="fas fa-utensils mr-1 text-primary-500"></i>รายการ
                            </h4>
                            <div class="space-y-2">
                                <?php foreach ($items as $item): ?>
                                    <div class="flex justify-between text-sm">
                                        <span><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
                                        <span class="text-gray-600"><?= formatMoney($item['price'] * $item['quantity']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($order['note']): ?>
                                <p class="mt-3 text-sm text-gray-500 bg-yellow-50 p-2 rounded">
                                    <i class="fas fa-sticky-note mr-1"></i><?= htmlspecialchars($order['note']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Customer Info -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">
                                <i class="fas fa-user mr-1 text-primary-500"></i>ลูกค้า
                            </h4>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($order['customer_phone']) ?></p>
                            <p class="text-sm text-gray-600 mt-2"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($order['delivery_address']) ?></p>
                            
                            <?php if ($order['rider_name']): ?>
                                <div class="mt-3 p-2 bg-green-50 rounded">
                                    <p class="text-sm font-medium text-green-700">
                                        <i class="fas fa-motorcycle mr-1"></i>ไรเดอร์: <?= htmlspecialchars($order['rider_name']) ?>
                                    </p>
                                    <p class="text-sm text-green-600"><?= htmlspecialchars($order['rider_phone']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <?php if (in_array($order['status'], ['confirmed', 'preparing', 'ready'])): ?>
                        <div class="px-6 pb-6 flex gap-2">
                            <?php if ($order['status'] === 'confirmed'): ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="preparing">
                                    <button type="submit" class="px-6 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition">
                                        <i class="fas fa-fire mr-1"></i>เริ่มทำอาหาร
                                    </button>
                                </form>
                            <?php elseif ($order['status'] === 'preparing'): ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="ready">
                                    <button type="submit" class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                                        <i class="fas fa-check mr-1"></i>อาหารพร้อมส่ง
                                    </button>
                                </form>
                            <?php elseif ($order['status'] === 'ready'): ?>
                                <span class="px-6 py-2 bg-gray-100 text-gray-600 rounded-lg">
                                    <i class="fas fa-clock mr-1"></i>รอไรเดอร์มารับ
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
