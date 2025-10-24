<?php

require "libs/functions.php";
require "connection.php";
$email = $password = "";



if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["email"])) {
        echo "Email zorunlu alan<br>";
    } else {
        $email = control_input($_POST["email"]);
    }

    if (empty($_POST["password"])) {
        echo "Parola zorunlu alan<br>";
    } else {
        $password = control_input($_POST["password"]);
    }

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            session_start(); 

            if ($user) {
                if (password_verify($password, $user['password'])) {

                    $_SESSION['email'] = $user['email'];
                    $_SESSION['login'] = true;
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'company') {
                        $_SESSION['company_id'] = $user['company_id']; 
                    }

                    switch ($user['role']) {
                        case 'admin':
                            header("Location: admin_panel.php");
                            break;
                        case 'company':
                            header("Location: company_panel.php");
                            break;
                        default:
                            header("Location: index.php");
                            break;
                    }
                    exit;
                } else {
                    echo "<div class='alert alert-danger mb-0'>Yanlış parola.</div>";
                }
            } else {
                echo "<div class='alert alert-danger mb-0'>Kullanıcı bulunamadı.</div>";
            }
        } catch (PDOException $e) {
            echo "Giriş hatası: " . $e->getMessage();
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
            <a href="/" title="Anasayfaya Dön"></a>
        </div>

    

        <div class="signup">
            <form action="/login.php" method="POST">
                <label for="chk" aria-hidden="true">Giriş Yap</label>
                <input type="email" name="email" placeholder="Email" required="">
                <input type="password" name="password" placeholder="Parola" required="">
                <button>Giriş Yap</button>
            </form>
        </div>
    </div>
</body>

</html>