<?php
require "connection.php";
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

if (!isset($_POST['ticket_id']) || empty($_POST['ticket_id'])) {
    die("Bilet ID'si eksik.");
}
$ticketId = $_POST['ticket_id'];

$stmt = $pdo->prepare("SELECT id, role, balance FROM User WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] != 'user') {
    die("Yetkisiz işlem.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, t.total_price, t.status, t.trip_id,
               tr.departure_time 
        FROM Tickets t 
        JOIN Trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticketId, $user['id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception("Bilet bulunamadı veya size ait değil.");
    }

    if ($ticket['status'] !== 'active') {
        throw new Exception("Bilet zaten iptal edilmiş.");
    }

    $departureTime = new DateTime($ticket['departure_time']);
    $currentTime = new DateTime();
    $timeDifference = $departureTime->getTimestamp() - $currentTime->getTimestamp();

    if ($timeDifference < 3600) {
        throw new Exception("Kalkışa 1 saatten az kaldığı için bilet iptal edilemez!");
    }

    $stmt = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
    $stmt->execute([$ticketId]);

    $refundAmount = (float)$ticket['total_price'];
    $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$refundAmount, $user['id']]);

    $pdo->commit();

    $_SESSION['success'] = "Bilet başarıyla iptal edildi. İade edilen tutar: " . $refundAmount . " ₺";
    header("Location: user_profile.php");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: user_profile.php");
    exit;
}
