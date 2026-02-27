<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
requireRole('customer');

// All redirects must happen BEFORE including header.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('cart.php'));
}

// Get user and database BEFORE including header
$user = getCurrentUser();
$db = getDB();

$cartData = json_decode($_POST['cart_data'] ?? '[]', true);
$deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
$note = sanitize($_POST['note'] ?? '');

if (empty($cartData) || empty($deliveryAddress)) {
    setFlash('error', 'ข้อมูลไม่ครบถ้วน');
    redirect(baseUrl('cart.php'));
}

// Calculate totals
$subtotal = 0;
foreach ($cartData as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$deliveryFee = 30.00;
$total = $subtotal + $deliveryFee;

// Check credit
if ($user['credit_balance'] < $total) {
    setFlash('error', 'เครดิตไม่เพียงพอ');
    redirect(baseUrl('cart.php'));
}

$restaurantId = $cartData[0]['restaurant_id'];

try {
    $db->beginTransaction();
    
    // Create order
    $stmt = $db->prepare("INSERT INTO orders (customer_id, restaurant_id, total_amount, delivery_fee, delivery_address, note, status, payment_status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'confirmed', 'paid')");
    $stmt->execute([$user['id'], $restaurantId, $total, $deliveryFee, $deliveryAddress, $note]);
    $orderId = $db->lastInsertId();
    
    // Create order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cartData as $item) {
        $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
    }
    
    // Deduct credit
    $result = updateCredit($user['id'], -$total, 'payment', "Order #$orderId");
    
    // Update restaurant order count
    $db->prepare("UPDATE restaurants SET total_orders = total_orders + 1 WHERE id = ?")->execute([$restaurantId]);
    
    $db->commit();
    
    // Clear cart via session flag (will be handled by JavaScript)
    $_SESSION['clear_cart'] = true;
    
    setFlash('success', 'สั่งอาหารสำเร็จ! กำลังหาไรเดอร์...');
    redirect(baseUrl('orders.php?id=' . $orderId));
    
} catch (Exception $e) {
    $db->rollBack();
    setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    redirect(baseUrl('cart.php'));
}
?>
