-- =============================================
-- Food Ordering System Database
-- =============================================

-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_ordering;

-- =============================================
-- 1. ตาราง users (ผู้ใช้งานทั้งหมด)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'seller', 'rider', 'admin') DEFAULT 'customer',
    credit_balance DECIMAL(10,2) DEFAULT 0.00,
    avatar VARCHAR(255),
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 2. ตาราง restaurants (ร้านอาหาร)
-- =============================================
CREATE TABLE IF NOT EXISTS restaurants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    image VARCHAR(255),
    category VARCHAR(50),
    opening_time TIME DEFAULT '08:00:00',
    closing_time TIME DEFAULT '22:00:00',
    status ENUM('pending', 'approved', 'rejected', 'closed') DEFAULT 'pending',
    rating DECIMAL(2,1) DEFAULT 0.0,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- 3. ตาราง menu_items (เมนูอาหาร)
-- =============================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- =============================================
-- 4. ตาราง orders (คำสั่งซื้อ)
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    rider_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    delivery_address TEXT NOT NULL,
    note TEXT,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'picked_up', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 5. ตาราง order_items (รายการอาหารในคำสั่งซื้อ)
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- =============================================
-- 6. ตาราง credit_topups (การเติมเครดิต)
-- =============================================
CREATE TABLE IF NOT EXISTS credit_topups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bank_transfer', 'promptpay') NOT NULL,
    slip_image VARCHAR(255),
    bank_name VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_id INT,
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 7. ตาราง transactions (ประวัติธุรกรรม)
-- =============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('topup', 'payment', 'refund', 'earning') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- ข้อมูลเริ่มต้น
-- =============================================

-- สร้าง Admin account (password: admin123)
INSERT INTO users (username, email, password, full_name, phone, role, status) VALUES
('admin', 'admin@foodorder.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', '0800000000', 'admin', 'active');

-- ตัวอย่างลูกค้า (password: password123)
INSERT INTO users (username, email, password, full_name, phone, role, credit_balance) VALUES
('customer1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมชาย ใจดี', '0812345678', 'customer', 500.00),
('customer2', 'customer2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมหญิง รักดี', '0823456789', 'customer', 1000.00);

-- ตัวอย่าง Seller (password: password123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('seller1', 'seller1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ร้านอร่อย จัง', '0834567890', 'seller'),
('seller2', 'seller2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ร้านแซ่บ นัว', '0845678901', 'seller');

-- ตัวอย่าง Rider (password: password123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('rider1', 'rider1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ไรเดอร์ เร็วมาก', '0856789012', 'rider'),
('rider2', 'rider2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ไรเดอร์ ใจดี', '0867890123', 'rider');

-- ตัวอย่างร้านอาหาร
INSERT INTO restaurants (owner_id, name, description, address, phone, category, status) VALUES
(4, 'ร้านอร่อยจัง', 'อาหารไทยรสชาติดั้งเดิม อร่อยถูกปาก', '123 ถนนสุขุมวิท กรุงเทพฯ', '0834567890', 'อาหารไทย', 'approved'),
(5, 'ร้านแซ่บนัว', 'อาหารอีสานแท้ๆ รสจัดจ้าน', '456 ถนนพหลโยธิน กรุงเทพฯ', '0845678901', 'อาหารอีสาน', 'approved');

-- ตัวอย่างเมนูอาหาร
INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available) VALUES
(1, 'ข้าวผัดกุ้ง', 'ข้าวผัดกุ้งสดๆ ใส่ไข่ แตงกวา มะเขือเทศ', 60.00, 'จานหลัก', TRUE),
(1, 'ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำข้น รสชาติเข้มข้น', 120.00, 'ซุป', TRUE),
(1, 'ผัดไทยกุ้งสด', 'ผัดไทยสูตรดั้งเดิม กุ้งสดตัวใหญ่', 80.00, 'จานหลัก', TRUE),
(1, 'ส้มตำไทย', 'ส้มตำปูปลาร้า รสชาติแซ่บ', 50.00, 'ยำ', TRUE),
(2, 'ลาบหมู', 'ลาบหมูสับ ใส่ข้าวคั่ว พริกลาบ', 60.00, 'ยำ', TRUE),
(2, 'ส้มตำปูปลาร้า', 'ส้มตำใส่ปูดอง ปลาร้า รสแซ่บนัว', 50.00, 'ยำ', TRUE),
(2, 'ไก่ย่าง', 'ไก่ย่างหมักสมุนไพร หอมนุ่ม', 90.00, 'ย่าง', TRUE),
(2, 'ข้าวเหนียว', 'ข้าวเหนียวนึ่งร้อนๆ', 10.00, 'เครื่องเคียง', TRUE);
