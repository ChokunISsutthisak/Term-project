    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 gradient-bg rounded-full flex items-center justify-center">
                            <i class="fas fa-utensils text-white text-xl"></i>
                        </div>
                        <span class="font-bold text-2xl"><?= SITE_NAME ?></span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        แพลตฟอร์มสั่งอาหารออนไลน์ที่ดีที่สุด เชื่อมต่อคุณกับร้านอาหารที่คุณชื่นชอบ
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-500 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-500 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-500 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary-500 transition">
                            <i class="fab fa-line"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold text-lg mb-4">ลิงก์ด่วน</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="<?= baseUrl() ?>" class="hover:text-white transition">หน้าแรก</a></li>
                        <li><a href="<?= baseUrl('register.php?role=seller') ?>" class="hover:text-white transition">เปิดร้านกับเรา</a></li>
                        <li><a href="<?= baseUrl('register.php?role=rider') ?>" class="hover:text-white transition">สมัครเป็นไรเดอร์</a></li>
                        <li><a href="#" class="hover:text-white transition">เกี่ยวกับเรา</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div>
                    <h4 class="font-semibold text-lg mb-4">ติดต่อเรา</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-phone mr-2"></i> 02-xxx-xxxx</li>
                        <li><i class="fas fa-envelope mr-2"></i> support@foodorder.com</li>
                        <li><i class="fas fa-clock mr-2"></i> ทุกวัน 08:00 - 22:00</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Update cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const countEl = document.getElementById('cart-count');
            if (countEl) {
                countEl.textContent = count;
                countEl.style.display = count > 0 ? 'flex' : 'none';
            }
        }
        updateCartCount();
    </script>
</body>
</html>
