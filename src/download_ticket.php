<?php
require 'vendor/autoload.php';
require 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    die("Giriş yapmanız gerekiyor.");
}

$userEmail = $_SESSION['email'];
$stmt = $pdo->prepare("SELECT id, role, full_name, email, company_id FROM User WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    die("Kullanıcı bulunamadı.");
}

if ($user['role'] !== 'user') {
    http_response_code(403); 
    die("Bu işlemi gerçekleştirmek için yetkiniz yok.");
}
if (!isset($_GET['ticket_id'])) {
    http_response_code(400);
    die("Ticket ID eksik.");
}

$ticketId = $_GET['ticket_id'];

$stmt = $pdo->prepare("
    SELECT 
        t.id AS ticket_id,
        t.total_price,
        t.status,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        u.full_name AS user_name,
        u.email AS user_email,
        bc.name AS company_name,
        bs.seat_number
    FROM Tickets t
    INNER JOIN Trips tr ON t.trip_id = tr.id
    INNER JOIN User u ON t.user_id = u.id
    INNER JOIN Bus_Company bc ON tr.company_id = bc.id
    INNER JOIN Booked_Seats bs ON t.id = bs.ticket_id
    WHERE t.id = ? AND u.email = ?
");
$stmt->execute([$ticketId, $userEmail]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    http_response_code(404);
    die("Bilet bulunamadı.");
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, iconv("UTF-8", "ISO-8859-9", "Bilet Detayları"), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);

$pdf->Cell(50, 10, "Bilet ID:", 0, 0);
$pdf->Cell(0, 10, $ticket['ticket_id'], 0, 1);

$pdf->Cell(50, 10, "Kullanici Adi:", 0, 0);
$pdf->Cell(0, 10, iconv("UTF-8", "ISO-8859-9", $ticket['user_name']), 0, 1);

$pdf->Cell(50, 10, "Kullanici Email:", 0, 0);
$pdf->Cell(0, 10, $ticket['user_email'], 0, 1);

$pdf->Cell(50, 10, "Firma:", 0, 0);
$pdf->Cell(0, 10, iconv("UTF-8", "ISO-8859-9", $ticket['company_name']), 0, 1);

$pdf->Cell(50, 10, "Koltuk No:", 0, 0);
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);

$pdf->Cell(50, 10, "Kalkis Sehir:", 0, 0);
$pdf->Cell(0, 10, iconv("UTF-8", "ISO-8859-9", $ticket['departure_city']), 0, 1);

$pdf->Cell(50, 10, "Varis Sehir:", 0, 0);
$pdf->Cell(0, 10, iconv("UTF-8", "ISO-8859-9", $ticket['destination_city']), 0, 1);

$pdf->Cell(50, 10, "Kalkis Saati:", 0, 0);
$pdf->Cell(0, 10, $ticket['departure_time'], 0, 1);

$pdf->Cell(50, 10, "Varis Saati:", 0, 0);
$pdf->Cell(0, 10, $ticket['arrival_time'], 0, 1);

$pdf->Cell(50, 10, "Fiyat:", 0, 0);
$pdf->Cell(0, 10, $ticket['total_price'] . " TL", 0, 1);

$pdf->Cell(50, 10, "Durum:", 0, 0);
$pdf->Cell(0, 10, ucfirst($ticket['status']), 0, 1);

$pdf->Output('D', "Bilet_{$ticket['ticket_id']}.pdf");
exit;
