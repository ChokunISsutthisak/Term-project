<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('admin');

$pageTitle = 'จัดการร้านค้า - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if ($restaurantId && in_array($action, ['approve', 'reject'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $db->prepare("UPDATE restaurants SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $restaurantId]);
        
        setFlash('success', $action === 'approve' ? 'อนุมัติร้านค้าเรียบร้อย' : 'ปฏิเสธร้านค้าแล้ว');
    }
    
    redirect(baseUrl('admin/restaurants.php'));
}

// Get restaurants
$status = $_GET['status'] ?? 'pending';
$stmt = $db->prepare("SELECT r.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
                      FROM restaurants r 
                      JOIN users u ON r.owner_id = u.id 
                      WHERE r.status = ?
                      ORDER BY r.created_at DESC");
$stmt->execute([$status]);
$restaurants = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'รออนุมัติ', 'color' => 'yellow'],
    'approved' => ['label' => 'อนุมัติแล้ว', 'color' => 'green'],
    'rejected' => ['label' => 'ปฏิเสธ', 'color' => 'red'],
];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="text-primary-500 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>กลับไปแดชบอร์ด
        </a>
    </div>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-store mr-2 text-primary-500"></i>จัดการร้านค้า
    </h1>
    
    <!-- Status Tabs -->
    <div class="flex gap-2 mb-6">
        <a href="<?= baseUrl('admin/restaurants.php?status=pending') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'pending' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            รออนุมัติ
        </a>
        <a href="<?= baseUrl('admin/restaurants.php?status=approved') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'approved' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            อนุมัติแล้ว
        </a>
        <a href="<?= baseUrl('admin/restaurants.php?status=rejected') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'rejected' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ปฏิเสธ
        </a>
    </div>
    
    <?php if (empty($restaurants)): ?>
        <div class="bg-white rounded-2xl p-16 text-center">
            <i class="fas fa-store-slash text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500">ไม่มีร้านค้า</h3>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($restaurants as $r): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="flex flex-col md:flex-row">
                        <!-- Image -->
                        <div class="md:w-48 h-48 bg-gradient-to-br from-primary-100 to-accent-100 flex-shrink-0">
                            <?php if ($r['image']): ?>
                                <img src="<?= baseUrl('uploads/' . $r['image']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-store text-4xl text-primary-300"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Info -->
                        <div class="flex-1 p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($r['name']) ?></h3>
                                    <p class="text-gray-500"><?= htmlspecialchars($r['category'] ?? 'ไม่ระบุหมวดหมู่') ?></p>
                                </div>
                                <?php $s = $statusLabels[$r['status']]; ?>
                                <span class="px-3 py-1 rounded-full bg-<?= $s['color'] ?>-100 text-<?= $s['color'] ?>-600 text-sm font-medium">
                                    <?= $s['label'] ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($r['description']) ?></p>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                                <div>
                                    <p class="text-gray-500"><i class="fas fa-user mr-1"></i>เจ้าของ</p>
                                    <p class="font-medium"><?= htmlspecialchars($r['owner_name']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500"><i class="fas fa-envelope mr-1"></i>อีเมล</p>
                                    <p class="font-medium"><?= htmlspecialchars($r['owner_email']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500"><i class="fas fa-phone mr-1"></i>โทร</p>
                                    <p class="font-medium"><?= htmlspecialchars($r['phone']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500"><i class="fas fa-calendar mr-1"></i>สมัครเมื่อ</p>
                                    <p class="font-medium"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></p>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($r['address']) ?>
                            </p>
                            
                            <?php if ($r['status'] === 'pending'): ?>
                                <div class="flex gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                                            <i class="fas fa-check mr-1"></i>อนุมัติ
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('ต้องการปฏิเสธร้านนี้?')">
                                        <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                            <i class="fas fa-times mr-1"></i>ปฏิเสธ
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
