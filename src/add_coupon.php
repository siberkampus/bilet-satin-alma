<?php
require 'connection.php';
session_start();


if (!isset($_SESSION['login']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    die("Giriş yapmanız gerekiyor.");
}

if (!in_array($_SESSION['role'], ['company', 'admin'])) {
    http_response_code(403);
    die("Bu işlemi gerçekleştirme yetkiniz yok.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Yalnızca POST isteği kabul edilir.");
}



$code = trim($_POST['code'] ?? '');
$discount = $_POST['discount'] ?? '';
$usage_limit = $_POST['usage_limit'] ?? '';
$expire_date = $_POST['expire_date'] ?? '';
$company_id = $_POST['company_id'] ?? '';

if ($_SESSION['role'] === 'company') {
    $company_id = $_SESSION['company_id'];
}

$errors = [];
if (empty($code)) $errors[] = "Kupon kodu gereklidir.";
if (!is_numeric($discount) || $discount < 1 || $discount > 100) $errors[] = "İndirim 1-100 arasında olmalıdır.";
if (!is_numeric($usage_limit) || $usage_limit < 1) $errors[] = "Kullanım limiti geçersiz.";

$expireDateTime = DateTime::createFromFormat('Y-m-d', $expire_date);
$today = new DateTime();
if (!$expireDateTime || $expireDateTime < $today) {
    $errors[] = "Geçersiz son kullanım tarihi.";
}

if ($_SESSION['role'] === 'admin' && !empty($company_id)) {
    $checkCompany = $pdo->prepare("SELECT id FROM User WHERE id = ? AND role = 'company'");
    $checkCompany->execute([$company_id]);
    if (!$checkCompany->fetch()) {
        $errors[] = "Geçersiz şirket ID'si.";
    }
}

if (!empty($errors)) {
    die(implode("<br>", $errors));
}

$checkCode = $pdo->prepare("SELECT id FROM Coupons WHERE code = ?");
$checkCode->execute([$code]);
if ($checkCode->fetch()) {
    die("Bu kupon kodu zaten mevcut!");
}

$couponId = bin2hex(random_bytes(16));
$createdAt = date('Y-m-d H:i:s');

try {
    $stmt = $pdo->prepare("
        INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$couponId, $code, $discount, $company_id, $usage_limit, $expire_date, $createdAt]);

    $_SESSION['success'] = "Kupon başarıyla eklendi!";
    header("Location: coupons.php");
    exit;
} catch (PDOException $e) {
    die("Kupon eklenirken bir hata oluştu: " . $e->getMessage());
}
