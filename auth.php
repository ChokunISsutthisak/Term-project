<?php
require_once __DIR__ . '/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check user role
function hasRole($role) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    if (is_array($role)) {
        return in_array($user['role'], $role);
    }
    return $user['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'กรุณาเข้าสู่ระบบก่อน');
        redirect(baseUrl('login.php'));
    }
}

// Require specific role
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect(baseUrl('index.php'));
    }
}

// Register new user
function registerUser($username, $email, $password, $fullName, $phone, $role = 'customer') {
    $db = getDB();
    
    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'];
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'อีเมลนี้ถูกใช้แล้ว'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword, $fullName, $phone, $role]);
    
    return ['success' => true, 'user_id' => $db->lastInsertId()];
}

// Login user
function loginUser($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    
    return ['success' => true, 'user' => $user];
}

// Logout user
function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
    session_destroy();
}

// Update user credit
function updateCredit($userId, $amount, $type, $description = '') {
    $db = getDB();
    
    // Get current balance
    $stmt = $db->prepare("SELECT credit_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    $newBalance = $user['credit_balance'] + $amount;
    
    if ($newBalance < 0) {
        return ['success' => false, 'message' => 'ยอดเงินไม่เพียงพอ'];
    }
    
    // Update balance
    $stmt = $db->prepare("UPDATE users SET credit_balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);
    
    // Record transaction
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $amount, $newBalance, $description]);
    
    return ['success' => true, 'new_balance' => $newBalance];
}

// Upload file helper
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'ไม่พบไฟล์'];
    }
    
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'ประเภทไฟล์ไม่ถูกต้อง'];
    }
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB max
        return ['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป'];
    }
    
    $uploadDir = UPLOAD_PATH . $directory . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        return ['success' => true, 'filename' => $directory . '/' . $newFileName];
    }
    
    return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปโหลด'];
}
?>
