<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
requireRole('seller');

$pageTitle = 'จัดการเมนูอาหาร - ' . SITE_NAME;
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = sanitize($_POST['category'] ?? '');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        if (empty($name) || $price <= 0) {
            setFlash('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        } else {
            // Handle image upload
            $imagePath = null;
            if (!empty($_FILES['image']['tmp_name'])) {
                $upload = uploadFile($_FILES['image'], 'menu');
                if ($upload['success']) {
                    $imagePath = $upload['filename'];
                }
            }
            
            if ($action === 'edit' && $itemId) {
                // Update existing
                if ($imagePath) {
                    $stmt = $db->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, is_available = ?, image = ? WHERE id = ? AND restaurant_id = ?");
                    $stmt->execute([$name, $description, $price, $category, $isAvailable, $imagePath, $itemId, $restaurant['id']]);
                } else {
                    $stmt = $db->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, is_available = ? WHERE id = ? AND restaurant_id = ?");
                    $stmt->execute([$name, $description, $price, $category, $isAvailable, $itemId, $restaurant['id']]);
                }
                setFlash('success', 'อัปเดตเมนูเรียบร้อย');
            } else {
                // Add new
                $stmt = $db->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$restaurant['id'], $name, $description, $price, $category, $isAvailable, $imagePath]);
                setFlash('success', 'เพิ่มเมนูเรียบร้อย');
            }
        }
    } elseif ($action === 'delete') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$itemId, $restaurant['id']]);
        setFlash('success', 'ลบเมนูเรียบร้อย');
    } elseif ($action === 'toggle') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $stmt = $db->prepare("UPDATE menu_items SET is_available = NOT is_available WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$itemId, $restaurant['id']]);
    }
    
    redirect(baseUrl('seller/menu.php'));
}

// Get menu items
$stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name");
$stmt->execute([$restaurant['id']]);
$menuItems = $stmt->fetchAll();

// Group by category
$menuByCategory = [];
foreach ($menuItems as $item) {
    $category = $item['category'] ?: 'ไม่มีหมวดหมู่';
    $menuByCategory[$category][] = $item;
}

$categories = ['จานหลัก', 'ของทอด', 'ยำ', 'ซุป', 'ย่าง', 'เครื่องดื่ม', 'ของหวาน', 'เครื่องเคียง'];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="<?= baseUrl('seller/dashboard.php') ?>" class="text-primary-500 hover:underline mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i>กลับไปแดชบอร์ด
            </a>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-utensils mr-2 text-primary-500"></i>จัดการเมนูอาหาร
            </h1>
        </div>
        <button onclick="openModal()" class="gradient-bg text-white px-6 py-3 rounded-xl font-medium hover:opacity-90 transition">
            <i class="fas fa-plus mr-2"></i>เพิ่มเมนู
        </button>
    </div>
    
    <?php if (empty($menuItems)): ?>
        <div class="bg-white rounded-2xl p-16 text-center">
            <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-500">ยังไม่มีเมนูอาหาร</h3>
            <p class="text-gray-400 mt-2">เริ่มเพิ่มเมนูอาหารในร้านของคุณ</p>
            <button onclick="openModal()" class="mt-6 gradient-bg text-white px-8 py-3 rounded-xl font-semibold hover:opacity-90 transition">
                <i class="fas fa-plus mr-2"></i>เพิ่มเมนูแรก
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($menuByCategory as $category => $items): ?>
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <?= htmlspecialchars($category) ?>
                    <span class="text-gray-400 font-normal text-base ml-2">(<?= count($items) ?> รายการ)</span>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($items as $item): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden <?= !$item['is_available'] ? 'opacity-60' : '' ?>">
                            <div class="h-40 bg-gradient-to-br from-primary-100 to-accent-100 relative">
                                <?php if ($item['image']): ?>
                                    <img src="<?= baseUrl('uploads/' . $item['image']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-bowl-food text-4xl text-primary-300"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$item['is_available']): ?>
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                        <span class="bg-red-500 text-white px-4 py-2 rounded-full font-medium">หมด</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="text-gray-500 text-sm line-clamp-2 h-10"><?= htmlspecialchars($item['description']) ?></p>
                                <p class="text-xl font-bold text-primary-500 mt-2"><?= formatMoney($item['price']) ?></p>
                                
                                <div class="flex gap-2 mt-4">
                                    <button onclick="openModal(<?= htmlspecialchars(json_encode($item)) ?>)" 
                                            class="flex-1 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">
                                        <i class="fas fa-edit mr-1"></i>แก้ไข
                                    </button>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="w-full py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition text-sm">
                                            <?= $item['is_available'] ? '<i class="fas fa-eye-slash mr-1"></i>ซ่อน' : '<i class="fas fa-eye mr-1"></i>แสดง' ?>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('ต้องการลบเมนูนี้?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="py-2 px-3 border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 id="modal-title" class="text-xl font-bold text-gray-800">เพิ่มเมนูอาหาร</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="item_id" id="form-item-id">
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">ชื่อเมนู *</label>
                <input type="text" name="name" id="form-name" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">หมวดหมู่</label>
                <select name="category" id="form-category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">รายละเอียด</label>
                <textarea name="description" id="form-description" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">ราคา (บาท) *</label>
                <input type="number" name="price" id="form-price" required min="0" step="0.01"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">รูปภาพ</label>
                <input type="file" name="image" accept="image/*"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="is_available" id="form-available" checked class="w-4 h-4 text-primary-500 rounded">
                <label for="form-available" class="ml-2 text-gray-700">เปิดขาย</label>
            </div>
            
            <button type="submit" class="w-full gradient-bg text-white py-3 rounded-xl font-semibold hover:opacity-90 transition">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
        </form>
    </div>
</div>

<script>
function openModal(item = null) {
    document.getElementById('modal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    if (item) {
        document.getElementById('modal-title').textContent = 'แก้ไขเมนู';
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-item-id').value = item.id;
        document.getElementById('form-name').value = item.name;
        document.getElementById('form-description').value = item.description || '';
        document.getElementById('form-price').value = item.price;
        document.getElementById('form-category').value = item.category || '';
        document.getElementById('form-available').checked = item.is_available == 1;
    } else {
        document.getElementById('modal-title').textContent = 'เพิ่มเมนูอาหาร';
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-item-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-description').value = '';
        document.getElementById('form-price').value = '';
        document.getElementById('form-category').value = '';
        document.getElementById('form-available').checked = true;
    }
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
