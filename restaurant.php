<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'ร้านอาหาร - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$restaurantId = (int)($_GET['id'] ?? 0);

if (!$restaurantId) {
    setFlash('error', 'ไม่พบร้านอาหาร');
    redirect(baseUrl());
}

// Get restaurant
$stmt = $db->prepare("SELECT r.*, u.full_name as owner_name 
                      FROM restaurants r 
                      JOIN users u ON r.owner_id = u.id 
                      WHERE r.id = ? AND r.status = 'approved'");
$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    setFlash('error', 'ไม่พบร้านอาหาร หรือ ร้านยังไม่ได้รับการอนุมัติ');
    redirect(baseUrl());
}

// Get menu items
$stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name");
$stmt->execute([$restaurantId]);
$menuItems = $stmt->fetchAll();

// Group by category
$menuByCategory = [];
foreach ($menuItems as $item) {
    $category = $item['category'] ?: 'เมนูทั่วไป';
    $menuByCategory[$category][] = $item;
}

$pageTitle = $restaurant['name'] . ' - ' . SITE_NAME;
?>

<!-- Restaurant Header -->
<section class="relative">
    <div class="h-64 md:h-80 bg-gradient-to-br from-primary-400 to-accent-400 overflow-hidden">
        <?php if ($restaurant['image']): ?>
            <img src="<?= baseUrl('uploads/' . $restaurant['image']) ?>" 
                 class="w-full h-full object-cover opacity-80">
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 relative">
        <div class="glass-card rounded-2xl shadow-xl p-6 -mt-20 relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800"><?= htmlspecialchars($restaurant['name']) ?></h1>
                        <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i>เปิดให้บริการ
                        </span>
                    </div>
                    <p class="text-gray-500 mb-3"><?= htmlspecialchars($restaurant['description']) ?></p>
                    
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                        <span><i class="fas fa-map-marker-alt mr-1 text-primary-500"></i><?= htmlspecialchars($restaurant['address']) ?></span>
                        <span><i class="fas fa-phone mr-1 text-primary-500"></i><?= htmlspecialchars($restaurant['phone']) ?></span>
                        <span><i class="fas fa-clock mr-1 text-primary-500"></i><?= substr($restaurant['opening_time'], 0, 5) ?> - <?= substr($restaurant['closing_time'], 0, 5) ?></span>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0 flex items-center gap-4">
                    <div class="text-center">
                        <div class="bg-accent-100 text-accent-600 px-4 py-2 rounded-xl">
                            <i class="fas fa-star"></i>
                            <span class="text-xl font-bold ml-1"><?= number_format($restaurant['rating'], 1) ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">คะแนน</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-primary-100 text-primary-600 px-4 py-2 rounded-xl">
                            <span class="text-xl font-bold"><?= $restaurant['total_orders'] ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">ออเดอร์</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Menu List -->
            <div class="flex-1">
                <?php if (empty($menuByCategory)): ?>
                    <div class="text-center py-16 bg-white rounded-2xl">
                        <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500">ยังไม่มีเมนูอาหาร</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($menuByCategory as $category => $items): ?>
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-utensils mr-2 text-primary-500"></i>
                                <?= htmlspecialchars($category) ?>
                                <span class="ml-2 text-sm font-normal text-gray-400">(<?= count($items) ?> เมนู)</span>
                            </h2>
                            
                            <div class="grid gap-4">
                                <?php foreach ($items as $item): ?>
                                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex hover:shadow-lg transition <?= !$item['is_available'] ? 'opacity-50' : '' ?>">
                                        <!-- Image -->
                                        <div class="w-32 h-32 flex-shrink-0 bg-gradient-to-br from-primary-100 to-accent-100">
                                            <?php if ($item['image']): ?>
                                                <img src="<?= baseUrl('uploads/' . $item['image']) ?>" 
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-bowl-food text-3xl text-primary-300"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Info -->
                                        <div class="flex-1 p-4 flex flex-col justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                                <p class="text-sm text-gray-500 line-clamp-2"><?= htmlspecialchars($item['description']) ?></p>
                                            </div>
                                            
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-lg font-bold text-primary-500"><?= formatMoney($item['price']) ?></span>
                                                
                                                <?php if ($item['is_available']): ?>
                                                    <button onclick="addToCart(<?= htmlspecialchars(json_encode([
                                                        'id' => $item['id'],
                                                        'restaurant_id' => $restaurant['id'],
                                                        'restaurant_name' => $restaurant['name'],
                                                        'name' => $item['name'],
                                                        'price' => $item['price'],
                                                        'image' => $item['image']
                                                    ])) ?>)" 
                                                            class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                                        <i class="fas fa-plus mr-1"></i>เพิ่ม
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400 font-medium">หมด</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Cart Sidebar -->
            <div class="lg:w-80">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart mr-2 text-primary-500"></i>
                        ตะกร้าของคุณ
                    </h3>
                    
                    <div id="cart-items" class="space-y-3 mb-4">
                        <!-- Cart items will be rendered here -->
                    </div>
                    
                    <div id="cart-empty" class="text-center py-8 text-gray-400">
                        <i class="fas fa-shopping-basket text-4xl mb-2"></i>
                        <p>ตะกร้าว่างเปล่า</p>
                    </div>
                    
                    <div id="cart-summary" class="hidden border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">รวม</span>
                            <span id="cart-subtotal" class="font-semibold">0 บาท</span>
                        </div>
                        <div class="flex justify-between mb-4">
                            <span class="text-gray-600">ค่าส่ง</span>
                            <span class="font-semibold">30 บาท</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-primary-500 border-t pt-4">
                            <span>รวมทั้งหมด</span>
                            <span id="cart-total">0 บาท</span>
                        </div>
                        
                        <a href="<?= baseUrl('cart.php') ?>" class="block w-full gradient-bg text-white text-center py-3 rounded-xl font-semibold mt-4 hover:opacity-90 transition">
                            <i class="fas fa-shopping-cart mr-2"></i>ดูตะกร้า
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const CURRENT_RESTAURANT = <?= $restaurantId ?>;
const DELIVERY_FEE = 30;

function getCart() {
    return JSON.parse(localStorage.getItem('cart') || '[]');
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    renderCart();
    updateCartCount();
}

function addToCart(item) {
    let cart = getCart();
    
    // Check if cart has items from different restaurant
    if (cart.length > 0 && cart[0].restaurant_id !== item.restaurant_id) {
        if (!confirm('ตะกร้ามีสินค้าจากร้านอื่น ต้องการล้างตะกร้าและเพิ่มสินค้าใหม่หรือไม่?')) {
            return;
        }
        cart = [];
    }
    
    // Find existing item
    const existingIndex = cart.findIndex(i => i.id === item.id);
    if (existingIndex > -1) {
        cart[existingIndex].quantity++;
    } else {
        item.quantity = 1;
        cart.push(item);
    }
    
    saveCart(cart);
    
    // Show feedback
    showToast('เพิ่มลงตะกร้าแล้ว');
}

function updateQuantity(itemId, delta) {
    let cart = getCart();
    const index = cart.findIndex(i => i.id === itemId);
    
    if (index > -1) {
        cart[index].quantity += delta;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        saveCart(cart);
    }
}

function renderCart() {
    const cart = getCart().filter(i => i.restaurant_id === CURRENT_RESTAURANT);
    const container = document.getElementById('cart-items');
    const emptyMsg = document.getElementById('cart-empty');
    const summary = document.getElementById('cart-summary');
    
    if (cart.length === 0) {
        container.innerHTML = '';
        emptyMsg.classList.remove('hidden');
        summary.classList.add('hidden');
        return;
    }
    
    emptyMsg.classList.add('hidden');
    summary.classList.remove('hidden');
    
    let subtotal = 0;
    container.innerHTML = cart.map(item => {
        subtotal += item.price * item.quantity;
        return `
            <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
                <div class="flex-1">
                    <p class="font-medium text-sm">${item.name}</p>
                    <p class="text-primary-500 text-sm">${item.price.toFixed(2)} บาท</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="updateQuantity(${item.id}, -1)" class="w-6 h-6 bg-gray-200 rounded-full text-sm hover:bg-gray-300">-</button>
                    <span class="w-6 text-center">${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)" class="w-6 h-6 bg-primary-500 text-white rounded-full text-sm hover:bg-primary-600">+</button>
                </div>
            </div>
        `;
    }).join('');
    
    document.getElementById('cart-subtotal').textContent = subtotal.toFixed(2) + ' บาท';
    document.getElementById('cart-total').textContent = (subtotal + DELIVERY_FEE).toFixed(2) + ' บาท';
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-pulse';
    toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

// Initial render
renderCart();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
