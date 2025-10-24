<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require "connection.php"; 
require "libs/functions.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $company_id = $_POST['company_id'] ?? '';
    $admin_id = $_POST['admin_id'] ?? '';
    $create_new_user = isset($_POST['create_new_user']);

    if (empty($company_id)) {
        die("Firma bilgisi eksik.");
    }

    try {
        if ($create_new_user) {
            $new_user_name = control_input($_POST['new_user_name'] ?? '');
            $new_user_email = control_input($_POST['new_user_email'] ?? '');
            $new_user_password = $_POST['new_user_password'] ?? '';

            if (empty($new_user_name) || empty($new_user_email) || empty($new_user_password)) {
                die("Yeni kullanıcı bilgileri eksik.");
            }

            $check = $pdo->prepare("SELECT * FROM User WHERE email = :email");
            $check->bindParam(':email', $new_user_email);
            $check->execute();

            if ($check->fetch()) {
                die("Bu e-posta adresi zaten kayıtlı.");
            }

            $hashedPassword = password_hash($new_user_password, PASSWORD_BCRYPT);
            $new_user_id = uniqid('usr_', true);
            $createdAt = date('Y-m-d H:i:s');
            $role = 'company';

            $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, created_at)
                                   VALUES (:id, :full_name, :email, :role, :password, :company_id, :created_at)");
            $stmt->bindParam(':id', $new_user_id);
            $stmt->bindParam(':full_name', $new_user_name);
            $stmt->bindParam(':email', $new_user_email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->bindParam(':created_at', $createdAt);
            $stmt->execute();

            $admin_id = $new_user_id; 
        }

        $stmtReset = $pdo->prepare("UPDATE User SET role = 'user', company_id = NULL WHERE company_id = :company_id AND role = 'company'");
        $stmtReset->bindParam(':company_id', $company_id);
        $stmtReset->execute();

        $stmtUpdate = $pdo->prepare("UPDATE User SET role = 'company', company_id = :company_id WHERE id = :admin_id");
        $stmtUpdate->bindParam(':company_id', $company_id);
        $stmtUpdate->bindParam(':admin_id', $admin_id);
        $stmtUpdate->execute();

        echo "Admin güncelleme işlemi başarıyla tamamlandı.";
        header("Location: admin_panel.php");
        exit;

    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    die("Geçersiz istek.");
}

