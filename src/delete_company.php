<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
 
    if (empty($_POST['company_id'])) {
        $_SESSION['error'] = "Geçersiz firma ID";
        echo "Geçersiz firma ID";
        header("Location: admin_panel.php");
        exit;
    }

    $company_id = $_POST['company_id'];

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM Bus_Company WHERE id = ?");
        $check_stmt->execute([$company_id]);
        
        if ($check_stmt->rowCount() === 0) {
            $_SESSION['error'] = "Firma bulunamadı";
            echo "Firma bulunamadı";
            header("Location: admin_panel.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Firma kontrol hatası: " . $e->getMessage());
        $_SESSION['error'] = "Sistem hatası";
        
        header("Location: admin_panel.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM User_Coupons 
                               WHERE user_id IN (SELECT id FROM User WHERE company_id = ?)");
        $stmt->execute([$company_id]);

        $stmt = $pdo->prepare("DELETE FROM User WHERE company_id = ?");
        $stmt->execute([$company_id]);

        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE company_id = ?");
        $stmt->execute([$company_id]);

        $trips = $pdo->prepare("SELECT id FROM Trips WHERE company_id = ?");
        $trips->execute([$company_id]);
        $trip_ids = $trips->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($trip_ids)) {
            $placeholders = str_repeat('?,', count($trip_ids) - 1) . '?';
            
            $tickets = $pdo->prepare("SELECT id FROM Tickets WHERE trip_id IN ($placeholders)");
            $tickets->execute($trip_ids);
            $ticket_ids = $tickets->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ticket_ids)) {
                $ticket_placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
                
                $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN ($ticket_placeholders)");
                $stmt->execute($ticket_ids);

                $stmt = $pdo->prepare("DELETE FROM Tickets WHERE id IN ($ticket_placeholders)");
                $stmt->execute($ticket_ids);
            }

            $stmt = $pdo->prepare("DELETE FROM Trips WHERE id IN ($placeholders)");
            $stmt->execute($trip_ids);
        }

        $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
        $stmt->execute([$company_id]);

        $pdo->commit();
        
        $_SESSION['success'] = "Firma ve ilişkili tüm veriler başarıyla silindi.";
        header("Location: admin_panel.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Firma silme hatası: " . $e->getMessage());
        $_SESSION['error'] = "Firma silinirken bir hata oluştu";
        header("Location: admin_panel.php");
        exit;
    }
}