<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'company') {
    header("Location: login.php");
    exit;
}
require_once 'connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if (empty($_POST['trip_id'])) {
        $errors[] = 'Trip ID zorunlu.';
    } else {
        $trip_id = $_POST['trip_id'];
    }

    if (empty($_POST['departure_city'])) {
        $errors[] = 'Kalkış şehri zorunlu.';
    } else {
        $departure_city = trim($_POST['departure_city']);
    }

    if (empty($_POST['destination_city'])) {
        $errors[] = 'Varış şehri zorunlu.';
    } else {
        $destination_city = trim($_POST['destination_city']);
    }

    if (empty($_POST['departure_time'])) {
        $errors[] = 'Kalkış saati zorunlu.';
    } else {
        $departure_time = $_POST['departure_time'];
    }

    if (empty($_POST['arrival_time'])) {
        $errors[] = 'Varış saati zorunlu.';
    } else {
        $arrival_time = $_POST['arrival_time'];
    }

    if (empty($_POST['price'])) {
        $errors[] = 'Fiyat zorunlu.';
    } else {
        $price = (int)$_POST['price'];
    }

    if (empty($_POST['capacity'])) {
        $errors[] = 'Kapasite zorunlu.';
    } else {
        $capacity = (int)$_POST['capacity'];
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE Trips SET 
                departure_city = :departure_city, 
                destination_city = :destination_city, 
                departure_time = :departure_time, 
                arrival_time = :arrival_time, 
                price = :price, 
                capacity = :capacity 
                WHERE id = :trip_id");

            $stmt->execute([
                ':departure_city' => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time' => $departure_time,
                ':arrival_time' => $arrival_time,
                ':price' => $price,
                ':capacity' => $capacity,
                ':trip_id' => $trip_id
            ]);

            header('Location: company_panel.php');
            exit;
        } catch (PDOException $e) {
            echo 'Hata: ' . $e->getMessage();
        }
    } else {
        echo "Hataa";
    
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>{$error}</div>";
        }
    }
}
?>
