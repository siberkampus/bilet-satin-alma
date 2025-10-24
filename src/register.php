<?php
require "libs/functions.php";
require "connection.php";
$username = $email = $password = $password2 = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    if (empty($_POST["username"])) {
        $errors[] = "Kullanıcı adı zorunlu alan.";
    } else {
        $username = control_input($_POST["username"]);
    }

    if (empty($_POST["email"])) {
        $errors[] = "Email zorunlu alan.";
    } else {
        $email = control_input($_POST["email"]);
    }

    if (empty($_POST["password"])) {
        $errors[] = "Parola zorunlu alan.";
    } else {
        $password = control_input($_POST["password"]);
    }

    if (empty($_POST["password2"])) {
        $errors[] = "Parola tekrar zorunlu alan.";
    } else {
        $password2 = control_input($_POST["password2"]);
    }

    if ($password !== $password2) {
        $errors[] = "Parolalar eşleşmiyor.";
    }

    if (empty($errors)) {
        try {
            $check = $pdo->prepare("SELECT * FROM User WHERE email = :email");
            $check->bindParam(':email', $email);
            $check->execute();

            if ($check->fetch()) {
                echo "Bu e-posta adresi zaten kayıtlı.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $id = uniqid('usr_', true);
                $createdAt = date('Y-m-d H:i:s');
                $role = 'user';

                $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, role, password, created_at)
                                       VALUES (:id, :full_name, :email, :role, :password, :created_at)");

                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':full_name', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':created_at', $createdAt);

                $stmt->execute();

                echo "Kayıt başarıyla tamamlandı!";
                header('Location: login.php');
            }
        } catch (PDOException $e) {
            echo "Kayıt hatası: " . $e->getMessage();
        }
    } else {
        foreach ($errors as $error) {
            echo $error . "<br>";
        }
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>Slide Navbar</title>
    <link rel="stylesheet" type="text/css" href="slide navbar style.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="static/css/register.css">

</head>

<body>
    <div class="main">
        <input type="checkbox" id="chk" aria-hidden="true">

        <div class="home-btn">
            <a href="/" title="Anasayfaya Dön">🏠</a>
        </div>

      

        <div class="signup">
            <form action="register.php" method="POST">
                <label for="chk" aria-hidden="true">Kayıt Ol</label>
                <input type="text" name="username" placeholder="Kullanıcı Adı Soyadı" required="">
                <input type="email" name="email" placeholder="Email" required="">
                <input type="password" name="password" placeholder="Parola" required="">
                <input type="password" name="password2" placeholder="Parola Tekrar" required="">
                <button type="submit">Kayıt Ol</button>
                <div class="login-link">
                    <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>