<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
requireRole('customer');

$pageTitle = 'ตะกร้าสินค้า - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

$user = getCurrentUser();
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-shopping-cart mr-2 text-primary-500"></i>ตะกร้าสินค้า
    </h1>
    
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Cart Items -->
        <div class="flex-1">
            <div id="cart-container" class="space-y-4">
                <!-- Items will be rendered here -->
            </div>
            
            <div id="empty-cart" class="hidden bg-white rounded-2xl p-16 text-center">
                <i class="fas fa-shopping-basket text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-500">ตะกร้าว่างเปล่า</h3>
                <p class="text-gray-400 mt-2">ไปเลือกอาหารอร่อยกันเถอะ!</p>
                <a href="<?= baseUrl() ?>" class="inline-block mt-6 gradient-bg text-white px-8 py-3 rounded-full font-semibold hover:opacity-90 transition">
                    <i class="fas fa-utensils mr-2"></i>เลือกร้านอาหาร
                </a>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:w-96">
            <div id="order-summary" class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                <h3 class="font-bold text-lg text-gray-800 mb-4">
                    <i class="fas fa-receipt mr-2 text-primary-500"></i>สรุปคำสั่งซื้อ
                </h3>
                
                <div id="restaurant-info" class="pb-4 border-b mb-4 hidden">
                    <p class="text-gray-600"><i class="fas fa-store mr-2"></i><span id="restaurant-name"></span></p>
                </div>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ราคาอาหาร</span>
                        <span id="subtotal" class="font-medium">0.00 บาท</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ค่าจัดส่ง</span>
                        <span id="delivery-fee" class="font-medium">30.00 บาท</span>
                    </div>
                </div>
                
                <div class="flex justify-between text-lg font-bold text-primary-500 border-t pt-4 mb-4">
                    <span>รวมทั้งหมด</span>
                    <span id="total">0.00 บาท</span>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600 mb-2">
                        <i class="fas fa-wallet mr-1 text-primary-500"></i>เครดิตคงเหลือ
                    </p>
                    <p class="text-2xl font-bold text-green-600"><?= formatMoney($user['credit_balance']) ?></p>
                </div>
                
                <form action="<?= baseUrl('checkout.php') ?>" method="POST" id="checkout-form">
                    <input type="hidden" name="cart_data" id="cart-data">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-map-marker-alt mr-1 text-primary-500"></i>ที่อยู่จัดส่ง
                        </label>
                        <textarea name="delivery_address" id="delivery-address" required rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                                  placeholder="กรอกที่อยู่จัดส่ง"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">
                            <i class="fas fa-sticky-note mr-1 text-primary-500"></i>หมายเหตุ (ถ้ามี)
                        </label>
                        <textarea name="note" rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                                  placeholder="เช่น ไม่ใส่ผัก, เพิ่มพริก"></textarea>
                    </div>
                    
                    <button type="submit" id="checkout-btn" disabled
                            class="w-full gradient-bg text-white py-4 rounded-xl font-semibold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-credit-card mr-2"></i>ชำระเงินด้วยเครดิต
                    </button>
                </form>
                
                <p id="credit-warning" class="hidden text-center text-red-500 text-sm mt-4">
                    <i class="fas fa-exclamation-triangle mr-1"></i>เครดิตไม่เพียงพอ 
                    <a href="<?= baseUrl('topup.php') ?>" class="underline">เติมเงิน</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
const DELIVERY_FEE = 30;
const USER_CREDIT = <?= $user['credit_balance'] ?>;

function getCart() {
    return JSON.parse(localStorage.getItem('cart') || '[]');
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    renderCart();
    updateCartCount();
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

function removeItem(itemId) {
    let cart = getCart().filter(i => i.id !== itemId);
    saveCart(cart);
}

function clearCart() {
    if (confirm('ต้องการล้างตะกร้าหรือไม่?')) {
        localStorage.removeItem('cart');
        renderCart();
        updateCartCount();
    }
}

function renderCart() {
    const cart = getCart();
    const container = document.getElementById('cart-container');
    const emptyEl = document.getElementById('empty-cart');
    const summaryEl = document.getElementById('order-summary');
    
    if (cart.length === 0) {
        container.innerHTML = '';
        emptyEl.classList.remove('hidden');
        summaryEl.classList.add('hidden');
        return;
    }
    
    emptyEl.classList.add('hidden');
    summaryEl.classList.remove('hidden');
    
    // Show restaurant info
    document.getElementById('restaurant-info').classList.remove('hidden');
    document.getElementById('restaurant-name').textContent = cart[0].restaurant_name;
    
    let subtotal = 0;
    
    container.innerHTML = `
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-primary-500 to-accent-500 px-6 py-4 flex justify-between items-center">
                <h3 class="text-white font-semibold">
                    <i class="fas fa-store mr-2"></i>${cart[0].restaurant_name}
                </h3>
                <button onclick="clearCart()" class="text-white/80 hover:text-white text-sm">
                    <i class="fas fa-trash mr-1"></i>ล้างตะกร้า
                </button>
            </div>
            <div class="divide-y">
                ${cart.map(item => {
                    subtotal += item.price * item.quantity;
                    return `
                        <div class="p-4 flex items-center gap-4">
                            <div class="w-20 h-20 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                                ${item.image ? 
                                    `<img src="${baseUrl('uploads/' + item.image)}" class="w-full h-full object-cover">` :
                                    `<div class="w-full h-full flex items-center justify-center"><i class="fas fa-bowl-food text-2xl text-gray-300"></i></div>`
                                }
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-800">${item.name}</h4>
                                <p class="text-primary-500 font-semibold">${item.price.toFixed(2)} บาท</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="updateQuantity(${item.id}, -1)" 
                                        class="w-8 h-8 bg-gray-100 rounded-full hover:bg-gray-200 transition">-</button>
                                <span class="w-8 text-center font-medium">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" 
                                        class="w-8 h-8 bg-primary-500 text-white rounded-full hover:bg-primary-600 transition">+</button>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">${(item.price * item.quantity).toFixed(2)} บาท</p>
                                <button onclick="removeItem(${item.id})" class="text-red-500 text-sm hover:underline">
                                    <i class="fas fa-times"></i> ลบ
                                </button>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    // Update summary
    const total = subtotal + DELIVERY_FEE;
    document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' บาท';
    document.getElementById('total').textContent = total.toFixed(2) + ' บาท';
    document.getElementById('cart-data').value = JSON.stringify(cart);
    
    // Check credit
    const checkoutBtn = document.getElementById('checkout-btn');
    const warning = document.getElementById('credit-warning');
    
    if (USER_CREDIT >= total) {
        checkoutBtn.disabled = false;
        warning.classList.add('hidden');
    } else {
        checkoutBtn.disabled = true;
        warning.classList.remove('hidden');
    }
}

function baseUrl(path) {
    return '<?= SITE_URL ?>/' + path;
}

// Validate before submit
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const address = document.getElementById('delivery-address').value.trim();
    if (!address) {
        e.preventDefault();
        alert('กรุณากรอกที่อยู่จัดส่ง');
    }
});

// Initial render
renderCart();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
