<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('admin');

$pageTitle = 'ตรวจสอบสลิป - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topupId = (int)($_POST['topup_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    $note = sanitize($_POST['note'] ?? '');
    
    if ($topupId && in_array($action, ['approve', 'reject'])) {
        // Get topup info
        $stmt = $db->prepare("SELECT * FROM credit_topups WHERE id = ?");
        $stmt->execute([$topupId]);
        $topup = $stmt->fetch();
        
        if ($topup && $topup['status'] === 'pending') {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            
            $stmt = $db->prepare("UPDATE credit_topups SET status = ?, admin_id = ?, admin_note = ? WHERE id = ?");
            $stmt->execute([$newStatus, $user['id'], $note, $topupId]);
            
            // If approved, add credit
            if ($action === 'approve') {
                updateCredit($topup['user_id'], $topup['amount'], 'topup', "เติมเงิน #$topupId อนุมัติโดยแอดมิน");
            }
            
            setFlash('success', $action === 'approve' ? 'อนุมัติการเติมเงินเรียบร้อย' : 'ปฏิเสธการเติมเงินแล้ว');
        }
    }
    
    redirect(baseUrl('admin/topups.php'));
}

// Get topups
$status = $_GET['status'] ?? 'pending';
$stmt = $db->prepare("SELECT t.*, u.full_name, u.email, u.phone, u.credit_balance
                      FROM credit_topups t 
                      JOIN users u ON t.user_id = u.id 
                      WHERE t.status = ?
                      ORDER BY t.created_at DESC");
$stmt->execute([$status]);
$topups = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'รอตรวจสอบ', 'color' => 'yellow'],
    'approved' => ['label' => 'อนุมัติ', 'color' => 'green'],
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
        <i class="fas fa-receipt mr-2 text-primary-500"></i>ตรวจสอบสลิปเติมเงิน
    </h1>
    
    <!-- Status Tabs -->
    <div class="flex gap-2 mb-6">
        <a href="<?= baseUrl('admin/topups.php?status=pending') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'pending' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            รอตรวจสอบ
        </a>
        <a href="<?= baseUrl('admin/topups.php?status=approved') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'approved' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            อนุมัติแล้ว
        </a>
        <a href="<?= baseUrl('admin/topups.php?status=rejected') ?>" 
           class="px-4 py-2 rounded-full font-medium <?= $status === 'rejected' ? 'gradient-bg text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            ปฏิเสธ
        </a>
    </div>
    
    <?php if (empty($topups)): ?>
        <div class="bg-white rounded-2xl p-16 text-center">
            <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500">ไม่มีรายการ</h3>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($topups as $t): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
                        <div>
                            <span class="text-lg font-bold text-gray-800">เติมเงิน #<?= $t['id'] ?></span>
                            <span class="text-gray-500 ml-2"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></span>
                        </div>
                        <?php $s = $statusLabels[$t['status']]; ?>
                        <span class="px-3 py-1 rounded-full bg-<?= $s['color'] ?>-100 text-<?= $s['color'] ?>-600 text-sm font-medium">
                            <?= $s['label'] ?>
                        </span>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex gap-6">
                            <!-- Slip Image -->
                            <div class="w-40 flex-shrink-0">
                                <?php if ($t['slip_image']): ?>
                                    <a href="<?= baseUrl('uploads/' . $t['slip_image']) ?>" target="_blank">
                                        <img src="<?= baseUrl('uploads/' . $t['slip_image']) ?>" 
                                             class="w-full rounded-lg border hover:opacity-80 transition">
                                    </a>
                                    <p class="text-xs text-gray-500 text-center mt-1">คลิกเพื่อดูขนาดเต็ม</p>
                                <?php else: ?>
                                    <div class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-2xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-1">
                                <div class="text-center mb-4">
                                    <p class="text-gray-500">จำนวนเงิน</p>
                                    <p class="text-3xl font-bold text-primary-500"><?= formatMoney($t['amount']) ?></p>
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <p><i class="fas fa-user mr-2 text-gray-400"></i><?= htmlspecialchars($t['full_name']) ?></p>
                                    <p><i class="fas fa-envelope mr-2 text-gray-400"></i><?= htmlspecialchars($t['email']) ?></p>
                                    <p><i class="fas fa-phone mr-2 text-gray-400"></i><?= htmlspecialchars($t['phone']) ?></p>
                                    <p><i class="fas fa-wallet mr-2 text-gray-400"></i>เครดิตปัจจุบัน: <?= formatMoney($t['credit_balance']) ?></p>
                                    <p><i class="fas fa-university mr-2 text-gray-400"></i><?= htmlspecialchars($t['bank_name'] ?? '-') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($t['status'] === 'pending'): ?>
                            <div class="mt-6 pt-4 border-t">
                                <div class="flex gap-2">
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="topup_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="w-full py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-semibold">
                                            <i class="fas fa-check mr-1"></i>อนุมัติ
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-1" onsubmit="return confirm('ต้องการปฏิเสธรายการนี้?')">
                                        <input type="hidden" name="topup_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="note" value="สลิปไม่ถูกต้อง">
                                        <button type="submit" class="w-full py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold">
                                            <i class="fas fa-times mr-1"></i>ปฏิเสธ
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($t['admin_note']): ?>
                            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-sm">
                                <p class="text-gray-500"><i class="fas fa-sticky-note mr-1"></i>หมายเหตุ: <?= htmlspecialchars($t['admin_note']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
