<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = SITE_NAME . ' - สั่งอาหารออนไลน์';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get approved restaurants
$stmt = $db->query("SELECT r.*, u.full_name as owner_name, 
                    (SELECT COUNT(*) FROM menu_items WHERE restaurant_id = r.id AND is_available = 1) as menu_count
                    FROM restaurants r 
                    JOIN users u ON r.owner_id = u.id 
                    WHERE r.status = 'approved' 
                    ORDER BY r.rating DESC, r.total_orders DESC");
$restaurants = $stmt->fetchAll();

// Get categories
$categories = ['อาหารไทย', 'อาหารอีสาน', 'อาหารจีน', 'อาหารญี่ปุ่น', 'ฟาสต์ฟู้ด', 'เครื่องดื่ม', 'ของหวาน'];
?>

<!-- Hero Section -->
<section class="gradient-bg text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-6xl font-bold mb-4 animate-pulse">
            🍜 สั่งอาหารง่ายๆ ส่งถึงที่!
        </h1>
        <p class="text-xl md:text-2xl mb-8 opacity-90">
            เลือกร้านโปรด สั่งอาหารอร่อย รอรับที่บ้าน
        </p>
        
        <!-- Search Box -->
        <div class="max-w-2xl mx-auto">
            <form action="" method="GET" class="flex gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           class="w-full pl-12 pr-4 py-4 rounded-full text-gray-800 focus:ring-4 focus:ring-accent-300 outline-none"
                           placeholder="ค้นหาร้านอาหาร หรือ เมนูที่ชอบ...">
                </div>
                <button type="submit" class="bg-accent-500 hover:bg-accent-600 px-8 py-4 rounded-full font-semibold transition">
                    ค้นหา
                </button>
            </form>
        </div>
        
        <!-- Quick Stats -->
        <div class="flex justify-center gap-8 mt-12">
            <div class="text-center">
                <div class="text-3xl font-bold"><?= count($restaurants) ?>+</div>
                <div class="text-sm opacity-80">ร้านอาหาร</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold">1000+</div>
                <div class="text-sm opacity-80">เมนู</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold">30</div>
                <div class="text-sm opacity-80">นาทีส่งถึง</div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-th-large mr-2 text-primary-500"></i>หมวดหมู่
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
            <a href="<?= baseUrl() ?>" class="flex-shrink-0 px-6 py-3 rounded-full <?= empty($_GET['category']) ? 'gradient-bg text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> font-medium transition">
                ทั้งหมด
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= baseUrl('?category=' . urlencode($cat)) ?>" 
                   class="flex-shrink-0 px-6 py-3 rounded-full <?= ($_GET['category'] ?? '') === $cat ? 'gradient-bg text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> font-medium transition">
                    <?= $cat ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Restaurants Section -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-store mr-2 text-primary-500"></i>ร้านอาหารแนะนำ
        </h2>
        
        <?php if (empty($restaurants)): ?>
            <div class="text-center py-16 bg-white rounded-2xl">
                <i class="fas fa-store-slash text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-500">ยังไม่มีร้านอาหาร</h3>
                <p class="text-gray-400 mt-2">กรุณารอร้านค้าเปิดให้บริการ</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($restaurants as $restaurant): ?>
                    <a href="<?= baseUrl('restaurant.php?id=' . $restaurant['id']) ?>" 
                       class="group bg-white rounded-2xl overflow-hidden shadow-lg hover-scale">
                        <!-- Restaurant Image -->
                        <div class="relative h-48 bg-gradient-to-br from-primary-400 to-accent-400 overflow-hidden">
                            <?php if ($restaurant['image']): ?>
                                <img src="<?= baseUrl('uploads/' . $restaurant['image']) ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-utensils text-6xl text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <span class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-sm font-medium text-gray-700">
                                <?= htmlspecialchars($restaurant['category'] ?? 'อาหารทั่วไป') ?>
                            </span>
                            
                            <!-- Rating Badge -->
                            <div class="absolute top-4 right-4 bg-accent-500 text-white px-3 py-1 rounded-full text-sm font-bold flex items-center">
                                <i class="fas fa-star mr-1"></i><?= number_format($restaurant['rating'], 1) ?>
                            </div>
                        </div>
                        
                        <!-- Restaurant Info -->
                        <div class="p-5">
                            <h3 class="font-bold text-lg text-gray-800 group-hover:text-primary-500 transition">
                                <?= htmlspecialchars($restaurant['name']) ?>
                            </h3>
                            <p class="text-gray-500 text-sm mt-1 line-clamp-2">
                                <?= htmlspecialchars($restaurant['description'] ?? 'ร้านอาหารอร่อย') ?>
                            </p>
                            
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center text-gray-500 text-sm">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= substr($restaurant['opening_time'], 0, 5) ?> - <?= substr($restaurant['closing_time'], 0, 5) ?>
                                </div>
                                <div class="flex items-center text-gray-500 text-sm">
                                    <i class="fas fa-utensils mr-1"></i>
                                    <?= $restaurant['menu_count'] ?> เมนู
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">
            ทำไมต้องเลือกเรา?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-3xl text-white"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-800 mb-2">ส่งไว</h3>
                <p class="text-gray-500">รับอาหารภายใน 30 นาที ไรเดอร์พร้อมบริการตลอด</p>
            </div>
            <div class="text-center p-6">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl text-white"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-800 mb-2">ปลอดภัย</h3>
                <p class="text-gray-500">ชำระเงินด้วยเครดิต หรือ โอนเงินผ่านธนาคาร</p>
            </div>
            <div class="text-center p-6">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-heart text-3xl text-white"></i>
                </div>
                <h3 class="font-bold text-xl text-gray-800 mb-2">หลากหลาย</h3>
                <p class="text-gray-500">ร้านอาหารมากมาย เมนูนับพัน ให้คุณเลือก</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 gradient-bg">
    <div class="max-w-4xl mx-auto px-4 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">อยากเปิดร้านกับเรา?</h2>
        <p class="text-xl mb-8 opacity-90">สมัครเป็นพาร์ทเนอร์ร้านอาหาร เปิดร้านได้เลยวันนี้!</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= baseUrl('register.php?role=seller') ?>" 
               class="bg-white text-primary-500 px-8 py-4 rounded-full font-bold hover:bg-gray-100 transition inline-flex items-center justify-center">
                <i class="fas fa-store mr-2"></i>เปิดร้านกับเรา
            </a>
            <a href="<?= baseUrl('register.php?role=rider') ?>" 
               class="border-2 border-white text-white px-8 py-4 rounded-full font-bold hover:bg-white hover:text-primary-500 transition inline-flex items-center justify-center">
                <i class="fas fa-motorcycle mr-2"></i>สมัครเป็นไรเดอร์
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
