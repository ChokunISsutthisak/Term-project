<?php
require_once __DIR__ . '/config/auth.php';
logoutUser();
setFlash('success', 'ออกจากระบบเรียบร้อยแล้ว');
redirect(baseUrl('login.php'));
?>
