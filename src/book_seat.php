<?php
session_start();
require 'connection.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek yöntemi.");
}

if (!isset($_SESSION['login'])) {
     header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}
$userEmail = $_SESSION['email'];
$stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userId = $user["id"];
if (!$user) {
    die("Kullanıcı bulunamadı.");
}



$tripId = $_POST['trip_id'] ?? null;
$seatNumber = $_POST['seat_number'] ?? null;
$couponCode = trim($_POST['coupon_code'] ?? ''); 

if (!$tripId || !$seatNumber) {
    die("Trip ID veya koltuk seçimi eksik.");
}

$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı.");

$price = (float)$trip['price']; 
$discountAmount = 0; 

if ($couponCode !== '') {
    $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = ?");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        $today = date('Y-m-d');
        if ($coupon['usage_limit'] > 0 && $coupon['expire_date'] >= $today) {
            $discountAmount = (float)$coupon['discount']; 
            $price = ($price * (100 - $discountAmount)) / 100;

            $stmt = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?");
            $stmt->execute([$coupon['id']]);
        } else {
            die("Kupon geçersiz veya süresi dolmuş.");
        }
    } else {
        die("Geçersiz kupon kodu.");
    }
}

if ($user['balance'] < $price) {
    die("Yeterli bakiyeniz yok. Fiyat: $price, Bakiyeniz: {$user['balance']}");
}

if ($seatNumber < 1 || $seatNumber > $trip['capacity']) {
    die("Geçersiz koltuk numarası.");
}

$stmt = $pdo->prepare("SELECT * FROM Booked_Seats bs 
                       INNER JOIN Tickets t ON bs.ticket_id = t.id
                       WHERE t.trip_id = ? AND bs.seat_number = ? 
                       AND t.status != 'canceled'");
$stmt->execute([$tripId, $seatNumber]);
$booked = $stmt->fetch(PDO::FETCH_ASSOC);

if ($booked) {
    die("Seçtiğiniz koltuk zaten dolu.");
}

try {
    $pdo->beginTransaction();

    $ticketId = uniqid('ticket_');
    $seatId = uniqid('seat_');
    $createdAt = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO Tickets (id, user_id, trip_id, total_price, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ticketId, $userId, $tripId, $price, $createdAt]);

    $stmt = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$seatId, $ticketId, $seatNumber, $createdAt]);

    $newBalance = $user['balance'] - $price;
    $stmt = $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);

    if (!empty($coupon)) {
        $userCouponId = uniqid('user_coupon_');
        $stmt = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userCouponId, $coupon['id'], $userId, $createdAt]);
    }

    $pdo->commit();

    echo "Rezervasyon tamamlandı! Bilet ID: $ticketId, Koltuk: $seatNumber, Yeni bakiyeniz: $newBalance";
    header("Location: user_profile.php");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Rezervasyon sırasında hata oluştu: " . $e->getMessage());
}
