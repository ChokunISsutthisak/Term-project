<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('admin');

$pageTitle = 'จัดการผู้ใช้ - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if ($userId && in_array($action, ['ban', 'unban'])) {
        $newStatus = $action === 'ban' ? 'banned' : 'active';
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
        $stmt->execute([$newStatus, $userId]);
        
        setFlash('success', $action === 'ban' ? 'ระงับผู้ใช้เรียบร้อย' : 'ปลดระงับผู้ใช้เรียบร้อย');
    }
    
    redirect(baseUrl('admin/users.php'));
}

// Get users
$role = $_GET['role'] ?? 'all';
$sql = "SELECT * FROM users WHERE role != 'admin'";
if ($role !== 'all') {
    $sql .= " AND role = ?";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
if ($role !== 'all') {
    $stmt->execute([$role]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll();

$roleLabels = [
    'customer' => ['label' => 'ลูกค้า', 'color' => 'blue', 'icon' => 'user'],
    'seller' => ['label' => 'ร้านค้า', 'color' => 'green', 'icon' => 'store'],
    'rider' => ['label' => 'ไรเดอร์', 'color' => 'orange', 'icon' => 'motorcycle'],
];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="text-primary-500 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>กลับไปแดชบอร์ด
        </a>
    </div>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-users mr-2 text-primary-500"></i>จัดการผู้ใช้
    </h1>
    
    <!-- Role Tabs -->
    <div class="flex gap-2 mb-6">
        <a href="<?= baseUrl('admin/users.php?role=all') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $role === 'all' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ทั้งหมด
        </a>
        <a href="<?= baseUrl('admin/users.php?role=customer') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $role === 'customer' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ลูกค้า
        </a>
        <a href="<?= baseUrl('admin/users.php?role=seller') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $role === 'seller' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ร้านค้า
        </a>
        <a href="<?= baseUrl('admin/users.php?role=rider') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $role === 'rider' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ไรเดอร์
        </a>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="bg-white rounded-2xl p-16 text-center">
            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500">ไม่มีผู้ใช้</h3>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">ประเภท</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">เครดิต</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">สถานะ</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">สมัครเมื่อ</th>
                        <th class="px-6 py-4 text-right text-sm font-medium text-gray-500">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $u): ?>
                        <?php $r = $roleLabels[$u['role']] ?? ['label' => $u['role'], 'color' => 'gray', 'icon' => 'user']; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= $u['avatar'] ? baseUrl('uploads/' . $u['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($u['full_name']) . '&background=random' ?>" 
                                         class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($u['full_name']) ?></p>
                                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-<?= $r['color'] ?>-100 text-<?= $r['color'] ?>-600 text-sm">
                                    <i class="fas fa-<?= $r['icon'] ?> mr-1"></i><?= $r['label'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium"><?= formatMoney($u['credit_balance']) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($u['status'] === 'banned'): ?>
                                    <span class="text-red-500"><i class="fas fa-ban mr-1"></i>ถูกระงับ</span>
                                <?php else: ?>
                                    <span class="text-green-500"><i class="fas fa-check-circle mr-1"></i>ใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">
                                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if ($u['status'] === 'banned'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="unban">
                                        <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                            ปลดระงับ
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('ต้องการระงับผู้ใช้นี้?')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="ban">
                                        <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                            ระงับ
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
