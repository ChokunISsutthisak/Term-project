<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
requireRole('customer');

$user = getCurrentUser();
$db = getDB();

// Handle form submission (MUST be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'bank_transfer');
    $bankName = sanitize($_POST['bank_name'] ?? '');
    
    if ($amount < 100) {
        setFlash('error', 'จำนวนเงินขั้นต่ำ 100 บาท');
    } elseif (empty($_FILES['slip']['tmp_name'])) {
        setFlash('error', 'กรุณาอัปโหลดสลิปการโอนเงิน');
    } else {
        // Upload slip
        $upload = uploadFile($_FILES['slip'], 'slips');
        
        if ($upload['success']) {
            $stmt = $db->prepare("INSERT INTO credit_topups (user_id, amount, payment_method, slip_image, bank_name, status) 
                                  VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user['id'], $amount, $paymentMethod, $upload['filename'], $bankName]);
            
            setFlash('success', 'ส่งคำขอเติมเงินสำเร็จ! รอแอดมินตรวจสอบ');
            redirect(baseUrl('topup.php'));
        } else {
            setFlash('error', $upload['message']);
        }
    }
}

// Refresh user data for balance display
$user = getCurrentUser();

// Get topup history
$stmt = $db->prepare("SELECT * FROM credit_topups WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$topups = $stmt->fetchAll();

$statusLabels = [
    'pending' => ['label' => 'รอตรวจสอบ', 'color' => 'yellow'],
    'approved' => ['label' => 'อนุมัติ', 'color' => 'green'],
    'rejected' => ['label' => 'ปฏิเสธ', 'color' => 'red'],
];

// Now include header (HTML output starts here)
$pageTitle = 'เติมเครดิต - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-wallet mr-2 text-primary-500"></i>เติมเครดิต
    </h1>
    
    <!-- Current Balance -->
    <div class="bg-gradient-to-r from-primary-500 to-accent-500 rounded-2xl p-6 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 mb-1">ยอดเครดิตคงเหลือ</p>
                <p class="text-4xl font-bold"><?= formatMoney($user['credit_balance']) ?></p>
            </div>
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-coins text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Topup Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="font-bold text-xl text-gray-800 mb-6">
                <i class="fas fa-plus-circle mr-2 text-primary-500"></i>เติมเงิน
            </h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Amount -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">จำนวนเงิน (บาท)</label>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <button type="button" onclick="setAmount(100)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">100</button>
                        <button type="button" onclick="setAmount(200)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">200</button>
                        <button type="button" onclick="setAmount(500)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">500</button>
                        <button type="button" onclick="setAmount(1000)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">1,000</button>
                        <button type="button" onclick="setAmount(2000)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">2,000</button>
                        <button type="button" onclick="setAmount(5000)" class="amount-btn py-2 border-2 border-gray-200 rounded-lg hover:border-primary-500 transition">5,000</button>
                    </div>
                    <input type="number" name="amount" id="amount" min="100" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                           placeholder="หรือกรอกจำนวนเอง">
                </div>
                
                <!-- Payment Method -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">วิธีการชำระเงิน</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="bank_transfer" checked class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50 transition">
                                <i class="fas fa-university text-2xl text-gray-400 peer-checked:text-primary-500 mb-2"></i>
                                <p class="font-medium">โอนเงิน</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="promptpay" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-primary-500 peer-checked:bg-primary-50 transition">
                                <i class="fas fa-qrcode text-2xl text-gray-400 peer-checked:text-primary-500 mb-2"></i>
                                <p class="font-medium">PromptPay</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Bank Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ธนาคารที่โอน</label>
                    <select name="bank_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="kbank">ธนาคารกสิกรไทย</option>
                        <option value="scb">ธนาคารไทยพาณิชย์</option>
                        <option value="bbl">ธนาคารกรุงเทพ</option>
                        <option value="ktb">ธนาคารกรุงไทย</option>
                        <option value="tmb">ธนาคารทหารไทยธนชาต</option>
                        <option value="promptpay">PromptPay</option>
                    </select>
                </div>
                
                <!-- Slip Upload -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">อัปโหลดสลิป</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-500 transition">
                        <input type="file" name="slip" id="slip" accept="image/*" required class="hidden"
                               onchange="previewSlip(this)">
                        <label for="slip" class="cursor-pointer">
                            <div id="slip-preview" class="hidden mb-4">
                                <img id="slip-image" class="max-h-48 mx-auto rounded-lg">
                            </div>
                            <div id="slip-placeholder">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500">คลิกเพื่ออัปโหลดสลิป</p>
                                <p class="text-gray-400 text-sm">รองรับ JPG, PNG ขนาดไม่เกิน 5MB</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="w-full gradient-bg text-white py-4 rounded-xl font-semibold hover:opacity-90 transition">
                    <i class="fas fa-paper-plane mr-2"></i>ส่งคำขอเติมเงิน
                </button>
            </form>
        </div>
        
        <!-- Bank Info & History -->
        <div class="space-y-6">
            <!-- Bank Info -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-bold text-xl text-gray-800 mb-4">
                    <i class="fas fa-info-circle mr-2 text-primary-500"></i>ข้อมูลการโอนเงิน
                </h2>
                
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white mb-4">
                    <p class="text-sm text-white/80">ธนาคารกสิกรไทย</p>
                    <p class="text-2xl font-bold tracking-wider">xxx-x-xxxxx-x</p>
                    <p class="mt-2">ชื่อบัญชี: บริษัท ฟู้ดออเดอร์ จำกัด</p>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-4">
                    <p class="font-medium text-blue-800 mb-2">
                        <i class="fas fa-qrcode mr-1"></i>PromptPay
                    </p>
                    <p class="text-2xl font-bold text-blue-600">xxx-xxx-xxxx</p>
                </div>
                
                <div class="mt-4 text-sm text-gray-500">
                    <p><i class="fas fa-check-circle mr-1 text-green-500"></i>ระบบจะตรวจสอบและเติมเงินภายใน 10-30 นาที</p>
                    <p><i class="fas fa-check-circle mr-1 text-green-500"></i>หากมีปัญหา ติดต่อ Line: @foodorder</p>
                </div>
            </div>
            
            <!-- History -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-bold text-xl text-gray-800 mb-4">
                    <i class="fas fa-history mr-2 text-primary-500"></i>ประวัติการเติมเงิน
                </h2>
                
                <?php if (empty($topups)): ?>
                    <p class="text-gray-400 text-center py-4">ยังไม่มีประวัติ</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($topups as $topup): ?>
                            <?php $status = $statusLabels[$topup['status']]; ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium"><?= formatMoney($topup['amount']) ?></p>
                                    <p class="text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($topup['created_at'])) ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-sm bg-<?= $status['color'] ?>-100 text-<?= $status['color'] ?>-600 font-medium">
                                    <?= $status['label'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function setAmount(amount) {
    document.getElementById('amount').value = amount;
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('border-primary-500', 'bg-primary-50');
    });
    event.target.classList.add('border-primary-500', 'bg-primary-50');
}

function previewSlip(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('slip-image').src = e.target.result;
            document.getElementById('slip-preview').classList.remove('hidden');
            document.getElementById('slip-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
