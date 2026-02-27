<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('seller');

$pageTitle = 'ลงทะเบียนร้านค้า - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$db = getDB();

// Check if editing
$isEdit = isset($_GET['edit']);

// Get existing restaurant
$stmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if ($restaurant && !$isEdit) {
    redirect(baseUrl('seller/dashboard.php'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $openingTime = $_POST['opening_time'] ?? '08:00';
    $closingTime = $_POST['closing_time'] ?? '22:00';
    
    // Validation
    if (empty($name) || empty($address) || empty($phone)) {
        setFlash('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
    } else {
        // Handle image upload
        $imagePath = $restaurant['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $upload = uploadFile($_FILES['image'], 'restaurants');
            if ($upload['success']) {
                $imagePath = $upload['filename'];
            }
        }
        
        if ($restaurant) {
            // Update existing
            $stmt = $db->prepare("UPDATE restaurants SET name = ?, description = ?, address = ?, phone = ?, 
                                  category = ?, opening_time = ?, closing_time = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $address, $phone, $category, $openingTime, $closingTime, $imagePath, $restaurant['id']]);
            setFlash('success', 'อัปเดตข้อมูลร้านเรียบร้อย');
        } else {
            // Create new
            $stmt = $db->prepare("INSERT INTO restaurants (owner_id, name, description, address, phone, category, opening_time, closing_time, image, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user['id'], $name, $description, $address, $phone, $category, $openingTime, $closingTime, $imagePath]);
            setFlash('success', 'ลงทะเบียนร้านค้าสำเร็จ! รอแอดมินอนุมัติ');
        }
        
        redirect(baseUrl('seller/dashboard.php'));
    }
}

$categories = ['อาหารไทย', 'อาหารอีสาน', 'อาหารจีน', 'อาหารญี่ปุ่น', 'ฟาสต์ฟู้ด', 'เครื่องดื่ม', 'ของหวาน', 'อาหารทะเล'];
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= baseUrl('seller/dashboard.php') ?>" class="text-primary-500 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>กลับไปแดชบอร์ด
        </a>
    </div>
    
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-store text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">
                <?= $restaurant ? 'แก้ไขข้อมูลร้าน' : 'ลงทะเบียนร้านค้า' ?>
            </h1>
            <p class="text-gray-500">กรอกข้อมูลร้านของคุณ</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Restaurant Name -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-store mr-1 text-primary-500"></i>ชื่อร้าน *
                </label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($restaurant['name'] ?? '') ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                       placeholder="ชื่อร้านอาหารของคุณ">
            </div>
            
            <!-- Category -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-tag mr-1 text-primary-500"></i>หมวดหมู่
                </label>
                <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($restaurant['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Description -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-align-left mr-1 text-primary-500"></i>รายละเอียดร้าน
                </label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                          placeholder="อธิบายเกี่ยวกับร้านของคุณ"><?= htmlspecialchars($restaurant['description'] ?? '') ?></textarea>
            </div>
            
            <!-- Address -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-map-marker-alt mr-1 text-primary-500"></i>ที่อยู่ร้าน *
                </label>
                <textarea name="address" rows="2" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                          placeholder="ที่อยู่ร้าน"><?= htmlspecialchars($restaurant['address'] ?? '') ?></textarea>
            </div>
            
            <!-- Phone -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-phone mr-1 text-primary-500"></i>เบอร์โทรศัพท์ *
                </label>
                <input type="tel" name="phone" required
                       value="<?= htmlspecialchars($restaurant['phone'] ?? '') ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                       placeholder="08x-xxx-xxxx">
            </div>
            
            <!-- Opening Hours -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-clock mr-1 text-primary-500"></i>เปิด
                    </label>
                    <input type="time" name="opening_time"
                           value="<?= $restaurant['opening_time'] ?? '08:00' ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-clock mr-1 text-primary-500"></i>ปิด
                    </label>
                    <input type="time" name="closing_time"
                           value="<?= $restaurant['closing_time'] ?? '22:00' ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            
            <!-- Image -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-image mr-1 text-primary-500"></i>รูปภาพร้าน
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-500 transition">
                    <input type="file" name="image" id="image" accept="image/*" class="hidden"
                           onchange="previewImage(this)">
                    <label for="image" class="cursor-pointer">
                        <?php if ($restaurant && $restaurant['image']): ?>
                            <div id="image-preview" class="mb-4">
                                <img src="<?= baseUrl('uploads/' . $restaurant['image']) ?>" class="max-h-48 mx-auto rounded-lg" id="preview-img">
                            </div>
                        <?php else: ?>
                            <div id="image-preview" class="hidden mb-4">
                                <img class="max-h-48 mx-auto rounded-lg" id="preview-img">
                            </div>
                        <?php endif; ?>
                        <div id="image-placeholder" class="<?= $restaurant && $restaurant['image'] ? 'hidden' : '' ?>">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">คลิกเพื่ออัปโหลดรูปภาพ</p>
                        </div>
                        <p class="text-gray-400 text-sm mt-2">หรือลากไฟล์มาวางที่นี่</p>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full gradient-bg text-white py-4 rounded-xl font-semibold hover:opacity-90 transition">
                <i class="fas fa-save mr-2"></i><?= $restaurant ? 'บันทึกการเปลี่ยนแปลง' : 'ลงทะเบียนร้านค้า' ?>
            </button>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
            document.getElementById('image-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
