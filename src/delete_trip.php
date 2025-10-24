<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'company' || !isset($_SESSION['company_id'])) {
    header("Location: login.php");
    exit;
}

require "connection.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['trip_id'])) {
        $trip_id = $_POST['trip_id'];
        $company_id = $_SESSION['company_id'];

        try {
            $pdo->beginTransaction();

            $stmtCheck = $pdo->prepare("SELECT id, departure_time FROM Trips WHERE id = ? AND company_id = ?");
            $stmtCheck->execute([$trip_id, $company_id]);
            $trip = $stmtCheck->fetch();

            if (!$trip) {
                throw new Exception("Sefer bulunamadı veya yetkiniz yok.");
            }

            $departure_time = strtotime($trip['departure_time']);
            $current_time = time();

            if ($departure_time < $current_time) {
                throw new Exception("Geçmiş seferler silinemez.");
            }

            $stmtSeats = $pdo->prepare("
                DELETE FROM Booked_Seats 
                WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ?)
            ");
            $stmtSeats->execute([$trip_id]);

            $stmtTickets = $pdo->prepare("DELETE FROM Tickets WHERE trip_id = ?");
            $stmtTickets->execute([$trip_id]);

          
            $stmtTrip = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
            $stmtTrip->execute([$trip_id, $company_id]);

            if ($stmtTrip->rowCount() === 0) {
                throw new Exception("Sefer silinemedi.");
            }

            $pdo->commit();

            $_SESSION['success'] = "Sefer başarıyla silindi.";
            header('Location: company_panel.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Sefer silme hatası: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        header('Location: company_panel.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Geçersiz sefer ID.";
        header('Location: company_panel.php');
        exit;
    }
} else {
    $_SESSION['error'] = "Geçersiz istek yöntemi.";
    header('Location: company_panel.php');
    exit;
}
