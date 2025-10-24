<?php
require 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Geçersiz istek yöntemi.");
}

if (!isset($_SESSION['login']) || !isset($_SESSION['email'])) {
    http_response_code(401);
    die("Giriş yapmanız gerekiyor.");
}

$userEmail = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT id, role, company_id FROM User WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    die("Kullanıcı bulunamadı.");
}

if (!in_array($user['role'], ['admin', 'company'])) {
    http_response_code(403);
    die("Bu işlem için yetkiniz yok.");
}

if (!isset($_POST['coupon_id']) || empty($_POST['coupon_id'])) {
    http_response_code(400);
    die("Kupon ID eksik.");
}

$couponId = $_POST['coupon_id'];

try {
    $pdo->beginTransaction();
    
    if ($user['role'] === 'company') {
        $checkCoupon = $pdo->prepare("
            SELECT id, company_id, code 
            FROM Coupons 
            WHERE id = ? AND company_id = ?
        ");
        $checkCoupon->execute([$couponId, $user['company_id']]);
    } else {
        $checkCoupon = $pdo->prepare("SELECT id, company_id, code FROM Coupons WHERE id = ?");
        $checkCoupon->execute([$couponId]);
    }
    
    $coupon = $checkCoupon->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        throw new Exception("Kupon bulunamadı veya silme yetkiniz yok.");
    }
    
    $checkUsage = $pdo->prepare("SELECT COUNT(*) FROM Tickets WHERE id = ?");
    $checkUsage->execute([$couponId]);
    $usageCount = $checkUsage->fetchColumn();
    
    if ($usageCount > 0) {
        throw new Exception("Bu kupon kullanıldığı için silinemez! Kullanım sayısı: " . $usageCount);
    }
    
    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
    $result = $stmt->execute([$couponId]);
    
    if (!$result || $stmt->rowCount() === 0) {
        throw new Exception("Kupon silinemedi.");
    }
    
    $pdo->commit();
    
    $_SESSION['success'] = "Kupon başarıyla silindi: " . $coupon['code'];
    header("Location: admin_coupons.php");
    exit;
    
} catch (Exception $e) {
    
    echo "Kupon silinemedi";
    header("Location: admin_coupons.php");
    exit;
}
?>