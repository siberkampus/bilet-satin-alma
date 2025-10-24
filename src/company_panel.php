<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'company') {
    header("Location: login.php");
    exit;
}

require "libs/functions.php";  
require "connection.php";      
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company_id = $_SESSION['company_id'];
    $departure_city = $_POST['departure_city'] ?? '';
    $destination_city = $_POST['destination_city'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $capacity = $_POST['capacity'] ?? '';

    if (empty($company_id)) {
        $errors[] = "Firma seçimi zorunlu.";
    }
    if (empty($departure_city)) {
        $errors[] = "Kalkış şehri zorunlu.";
    }
    if (empty($destination_city)) {
        $errors[] = "Varış şehri zorunlu.";
    }
    if (empty($departure_time)) {
        $errors[] = "Kalkış saati zorunlu.";
    }
    if (empty($arrival_time)) {
        $errors[] = "Varış saati zorunlu.";
    }
    if ($price === '' || !is_numeric($price) || $price <= 0) {
        $errors[] = "Geçerli bir fiyat giriniz.";
    }
    if ($capacity === '' || !is_numeric($capacity) || $capacity <= 0) {
        $errors[] = "Geçerli bir kapasite giriniz.";
    }
    if (strtotime($departure_time) <= strtotime($current_time)) {
        $errors[] = "Kalkış zamanı şu anki zamandan sonra olmalıdır.";
    }

    if (strtotime($arrival_time) <= strtotime($departure_time)) {
        $errors[] = "Varış zamanı kalkış zamanından sonra olmalıdır.";
    }
    if (empty($errors)) {
        $trip_id = uniqid('trip_');
        $created_date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO Trips 
                (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity, created_date) 
                VALUES 
                (:id, :company_id, :destination_city, :arrival_time, :departure_time, :departure_city, :price, :capacity, :created_date)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $trip_id,
            ':company_id' => control_input($company_id),
            ':destination_city' => control_input($destination_city),
            ':arrival_time' => control_input($arrival_time),
            ':departure_time' => control_input($departure_time),
            ':departure_city' => control_input($departure_city),
            ':price' => control_input($price),
            ':capacity' => control_input($capacity),
            ':created_date' => $created_date
        ]);

        echo "Sefer başarıyla oluşturuldu!";
        header("Location: company_panel.php");
        exit;
    } else {
        echo "Sefer oluşturulamadı";
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_SESSION['company_id'])) {
            throw new Exception("Şirket ID oturumda bulunamadı!");
        }

        $companyId = $_SESSION['company_id'];

        $stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = :company_id ORDER BY departure_time ASC");
        $stmt->execute(['company_id' => $companyId]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo 'Hata: ' . $e->getMessage();
    } catch (PDOException $e) {
        echo 'Veritabanı Hatası: ' . $e->getMessage();
    }
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="static/css/admin_panel.css">
    <link rel="stylesheet" href="static/css/popup.css">
</head>

<body>

    <!-- Dashboard -->
    <div class="d-flex flex-column flex-lg-row h-lg-full bg-surface-secondary">
        <!-- Vertical Navbar -->
        <nav class="navbar show navbar-vertical h-lg-screen navbar-expand-lg px-0 py-3 navbar-light bg-white border-bottom border-bottom-lg-0 border-end-lg" id="navbarVertical">
            <div class="container-fluid">
                <!-- Toggler -->
                <button class="navbar-toggler ms-n2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- Brand -->
                <a class="navbar-brand py-lg-2 mb-lg-5 px-lg-6 me-0" href="/">
                    <img src="static/images/logo.png" alt="Logo" style="height: 60px; width: auto; object-fit: contain; filter: brightness(1.1);">
                </a>
                <!-- User menu (mobile) -->
                <div class="navbar-user d-lg-none">
                    <!-- Dropdown -->
                    <div class="dropdown">
                        <!-- Toggle -->
                        <a href="#" id="sidebarAvatar" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar-parent-child">
                                <img alt="Image Placeholder" src="https://images.unsplash.com/photo-1548142813-c348350df52b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80" class="avatar avatar- rounded-circle">
                                <span class="avatar-child avatar-badge bg-success"></span>
                            </div>
                        </a>
                        <!-- Menu -->
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="sidebarAvatar">
                            <a href="#" class="dropdown-item">Profile</a>
                            <a href="#" class="dropdown-item">Settings</a>
                            <a href="#" class="dropdown-item">Billing</a>
                            <hr class="dropdown-divider">
                            <a href="/logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
                <!-- Collapse -->
                <div class="collapse navbar-collapse" id="sidebarCollapse">
                    <!-- Navigation -->
                    <ul class="navbar-nav">

                        <li class="nav-item">
                            <a class="nav-link" href="company_panel.php">
                                <i class="bi bi-bookmarks"></i> Seferler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/tickets.php">
                                <i class="bi bi-people"></i> Biletler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/coupons.php">
                                <i class="bi bi-basket2"></i> Kuponlar
                            </a>
                        </li>
                    </ul>
                    <!-- Divider -->
                    <hr class="navbar-divider my-5 opacity-20">
                    <!-- Navigation -->
                    <!-- Push content down -->
                    <div class="mt-auto"></div>
                    <!-- User (md) -->
                    <ul class="navbar-nav">
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">
                                <i class="bi bi-box-arrow-left"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Main content -->
        <div class="h-screen flex-grow-1 overflow-y-lg-auto">
            <!-- Header -->
            <header class="bg-surface-primary border-bottom pt-6">
                <div class="container-fluid">
                    <div class="mb-npx">
                        <div class="row align-items-center">
                            <div class="col-sm-6 col-12 mb-4 mb-sm-0">
                                <!-- Title -->
                                <h1 class="h2 mb-0 ls-tight">Anadolu Bilet</h1>
                            </div>
                            <!-- Actions -->
                            <div class="col-sm-6 col-12 text-sm-end">
                                <div class="mx-n1">
                                    <button id="create-company" class="btn btn-primary">
                                        <i class="bi bi-plus pe-2"></i>
                                        Sefer Ekle
                                    </button>
                                </div>
                            </div>



                            <!-- Popup -->
                            <div id="company-popup" class="popup">
                                <div class="popup-content">
                                    <h2>Sefer Oluştur</h2>
                                    <form id="company-form" action="company_panel.php" method="post" enctype="multipart/form-data">

                                        <label for="departure-city">Kalkış Şehri:</label>
                                        <select id="departure-city" name="departure_city" required>
                                            <option value="Adana">Adana</option>
                                            <option value="Adıyaman">Adıyaman</option>
                                            <option value="Afyonkarahisar">Afyonkarahisar</option>
                                            <option value="Ağrı">Ağrı</option>
                                            <option value="Aksaray">Aksaray</option>
                                            <option value="Amasya">Amasya</option>
                                            <option value="Ankara">Ankara</option>
                                            <option value="Antalya">Antalya</option>
                                            <option value="Ardahan">Ardahan</option>
                                            <option value="Artvin">Artvin</option>
                                            <option value="Aydın">Aydın</option>
                                            <option value="Balıkesir">Balıkesir</option>
                                            <option value="Bartın">Bartın</option>
                                            <option value="Batman">Batman</option>
                                            <option value="Bayburt">Bayburt</option>
                                            <option value="Bilecik">Bilecik</option>
                                            <option value="Bingöl">Bingöl</option>
                                            <option value="Bitlis">Bitlis</option>
                                            <option value="Bolu">Bolu</option>
                                            <option value="Burdur">Burdur</option>
                                            <option value="Bursa">Bursa</option>
                                            <option value="Çanakkale">Çanakkale</option>
                                            <option value="Çankırı">Çankırı</option>
                                            <option value="Çorum">Çorum</option>
                                            <option value="Denizli">Denizli</option>
                                            <option value="Diyarbakır">Diyarbakır</option>
                                            <option value="Düzce">Düzce</option>
                                            <option value="Edirne">Edirne</option>
                                            <option value="Elazığ">Elazığ</option>
                                            <option value="Erzincan">Erzincan</option>
                                            <option value="Erzurum">Erzurum</option>
                                            <option value="Eskişehir">Eskişehir</option>
                                            <option value="Gaziantep">Gaziantep</option>
                                            <option value="Giresun">Giresun</option>
                                            <option value="Gümüşhane">Gümüşhane</option>
                                            <option value="Hakkari">Hakkari</option>
                                            <option value="Hatay">Hatay</option>
                                            <option value="Iğdır">Iğdır</option>
                                            <option value="Isparta">Isparta</option>
                                            <option value="İstanbul">İstanbul</option>
                                            <option value="İzmir">İzmir</option>
                                            <option value="Kahramanmaraş">Kahramanmaraş</option>
                                            <option value="Karabük">Karabük</option>
                                            <option value="Karaman">Karaman</option>
                                            <option value="Kars">Kars</option>
                                            <option value="Kastamonu">Kastamonu</option>
                                            <option value="Kayseri">Kayseri</option>
                                            <option value="Kırıkkale">Kırıkkale</option>
                                            <option value="Kırklareli">Kırklareli</option>
                                            <option value="Kırşehir">Kırşehir</option>
                                            <option value="Kilis">Kilis</option>
                                            <option value="Kocaeli">Kocaeli</option>
                                            <option value="Konya">Konya</option>
                                            <option value="Kütahya">Kütahya</option>
                                            <option value="Malatya">Malatya</option>
                                            <option value="Manisa">Manisa</option>
                                            <option value="Mardin">Mardin</option>
                                            <option value="Mersin">Mersin</option>
                                            <option value="Muğla">Muğla</option>
                                            <option value="Muş">Muş</option>
                                            <option value="Nevşehir">Nevşehir</option>
                                            <option value="Niğde">Niğde</option>
                                            <option value="Ordu">Ordu</option>
                                            <option value="Osmaniye">Osmaniye</option>
                                            <option value="Rize">Rize</option>
                                            <option value="Sakarya">Sakarya</option>
                                            <option value="Samsun">Samsun</option>
                                            <option value="Siirt">Siirt</option>
                                            <option value="Sinop">Sinop</option>
                                            <option value="Sivas">Sivas</option>
                                            <option value="Şanlıurfa">Şanlıurfa</option>
                                            <option value="Şırnak">Şırnak</option>
                                            <option value="Tekirdağ">Tekirdağ</option>
                                            <option value="Tokat">Tokat</option>
                                            <option value="Trabzon">Trabzon</option>
                                            <option value="Tunceli">Tunceli</option>
                                            <option value="Uşak">Uşak</option>
                                            <option value="Van">Van</option>
                                            <option value="Yalova">Yalova</option>
                                            <option value="Yozgat">Yozgat</option>
                                            <option value="Zonguldak">Zonguldak</option>

                                        </select>

                                        <label for="destination-city">Varış Şehri:</label>
                                        <select id="destination-city" name="destination_city" required>
                                            <option value="Adana">Adana</option>
                                            <option value="Adıyaman">Adıyaman</option>
                                            <option value="Afyonkarahisar">Afyonkarahisar</option>
                                            <option value="Ağrı">Ağrı</option>
                                            <option value="Aksaray">Aksaray</option>
                                            <option value="Amasya">Amasya</option>
                                            <option value="Ankara">Ankara</option>
                                            <option value="Antalya">Antalya</option>
                                            <option value="Ardahan">Ardahan</option>
                                            <option value="Artvin">Artvin</option>
                                            <option value="Aydın">Aydın</option>
                                            <option value="Balıkesir">Balıkesir</option>
                                            <option value="Bartın">Bartın</option>
                                            <option value="Batman">Batman</option>
                                            <option value="Bayburt">Bayburt</option>
                                            <option value="Bilecik">Bilecik</option>
                                            <option value="Bingöl">Bingöl</option>
                                            <option value="Bitlis">Bitlis</option>
                                            <option value="Bolu">Bolu</option>
                                            <option value="Burdur">Burdur</option>
                                            <option value="Bursa">Bursa</option>
                                            <option value="Çanakkale">Çanakkale</option>
                                            <option value="Çankırı">Çankırı</option>
                                            <option value="Çorum">Çorum</option>
                                            <option value="Denizli">Denizli</option>
                                            <option value="Diyarbakır">Diyarbakır</option>
                                            <option value="Düzce">Düzce</option>
                                            <option value="Edirne">Edirne</option>
                                            <option value="Elazığ">Elazığ</option>
                                            <option value="Erzincan">Erzincan</option>
                                            <option value="Erzurum">Erzurum</option>
                                            <option value="Eskişehir">Eskişehir</option>
                                            <option value="Gaziantep">Gaziantep</option>
                                            <option value="Giresun">Giresun</option>
                                            <option value="Gümüşhane">Gümüşhane</option>
                                            <option value="Hakkari">Hakkari</option>
                                            <option value="Hatay">Hatay</option>
                                            <option value="Iğdır">Iğdır</option>
                                            <option value="Isparta">Isparta</option>
                                            <option value="İstanbul">İstanbul</option>
                                            <option value="İzmir">İzmir</option>
                                            <option value="Kahramanmaraş">Kahramanmaraş</option>
                                            <option value="Karabük">Karabük</option>
                                            <option value="Karaman">Karaman</option>
                                            <option value="Kars">Kars</option>
                                            <option value="Kastamonu">Kastamonu</option>
                                            <option value="Kayseri">Kayseri</option>
                                            <option value="Kırıkkale">Kırıkkale</option>
                                            <option value="Kırklareli">Kırklareli</option>
                                            <option value="Kırşehir">Kırşehir</option>
                                            <option value="Kilis">Kilis</option>
                                            <option value="Kocaeli">Kocaeli</option>
                                            <option value="Konya">Konya</option>
                                            <option value="Kütahya">Kütahya</option>
                                            <option value="Malatya">Malatya</option>
                                            <option value="Manisa">Manisa</option>
                                            <option value="Mardin">Mardin</option>
                                            <option value="Mersin">Mersin</option>
                                            <option value="Muğla">Muğla</option>
                                            <option value="Muş">Muş</option>
                                            <option value="Nevşehir">Nevşehir</option>
                                            <option value="Niğde">Niğde</option>
                                            <option value="Ordu">Ordu</option>
                                            <option value="Osmaniye">Osmaniye</option>
                                            <option value="Rize">Rize</option>
                                            <option value="Sakarya">Sakarya</option>
                                            <option value="Samsun">Samsun</option>
                                            <option value="Siirt">Siirt</option>
                                            <option value="Sinop">Sinop</option>
                                            <option value="Sivas">Sivas</option>
                                            <option value="Şanlıurfa">Şanlıurfa</option>
                                            <option value="Şırnak">Şırnak</option>
                                            <option value="Tekirdağ">Tekirdağ</option>
                                            <option value="Tokat">Tokat</option>
                                            <option value="Trabzon">Trabzon</option>
                                            <option value="Tunceli">Tunceli</option>
                                            <option value="Uşak">Uşak</option>
                                            <option value="Van">Van</option>
                                            <option value="Yalova">Yalova</option>
                                            <option value="Yozgat">Yozgat</option>
                                            <option value="Zonguldak">Zonguldak</option>

                                        </select>

                                        <label for="departure-time">Kalkış Saati:</label>
                                        <input type="datetime-local"
                                            id="departure-time"
                                            name="departure_time"
                                            min="<?php echo date('Y-m-d\TH:i'); ?>"
                                            required>

                                        <label for="arrival-time">Varış Saati:</label>
                                        <input type="datetime-local"
                                            id="arrival-time"
                                            name="arrival_time"
                                            min="<?php echo date('Y-m-d\TH:i'); ?>"
                                            required>

                                        <label for="price">Fiyat (TL):</label>
                                        <input type="number" id="price" name="price" min="0" required>

                                        <label for="capacity">Kapasite:</label>
                                        <input type="number" id="capacity" name="capacity" min="1" required>

                                        <div class="popup-buttons">
                                            <button type="submit">Kaydet</button>
                                            <button type="button" id="close-popup">Kapat</button>
                                        </div>
                                    </form>
                                </div>
                            </div>



                        </div>
                        <!-- Nav -->
                        <ul class="nav nav-tabs mt-4 overflow-x border-0">
                        </ul>
                    </div>
                </div>
            </header>
            <!-- Main -->
            <main class="py-6 bg-surface-secondary">
                <div class="container-fluid">

                    <div class="card shadow border-0 mb-7">
                        <div class="card-header">
                            <h5 class="mb-0">Otobüs Firmaları</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Kalkış Şehri</th>
                                        <th scope="col">Varış Şehri</th>
                                        <th scope="col">Kalkış Tarihi</th>
                                        <th scope="col">Varış Tarihi</th>
                                        <th scope="col">Kapasite</th>
                                        <th scope="col">Fiyat</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trips as $trip): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trip['departure_city']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['destination_city']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['departure_time']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['arrival_time']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['price']); ?></td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-sm btn-neutral edit-btn"
                                                    data-id="<?php echo htmlspecialchars($trip['id']); ?>"
                                                    data-departure-city="<?php echo htmlspecialchars($trip['departure_city']); ?>"
                                                    data-destination-city="<?php echo htmlspecialchars($trip['destination_city']); ?>"
                                                    data-departure-time="<?php echo htmlspecialchars($trip['departure_time']); ?>"
                                                    data-arrival-time="<?php echo htmlspecialchars($trip['arrival_time']); ?>"
                                                    data-price="<?php echo htmlspecialchars($trip['price']); ?>"
                                                    data-capacity="<?php echo htmlspecialchars($trip['capacity']); ?>">Düzenle</a>

                                                <form action="delete_trip.php" method="post" style="display:inline;">
                                                    <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip['id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-square btn-neutral text-danger-hover">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer border-0 py-5">
                            <span class="text-muted text-sm">Showing 10 items out of 250 results found</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div id="edit-trip-popup" class="popup">
        <div class="popup-content">
            <h2>Seferi Düzenle</h2>
            <form id="edit-trip-form" action="update_trip.php" method="post">
                <input type="hidden" name="trip_id" id="edit-trip-id">

                <label for="edit-departure-city">Kalkış Şehri:</label>
                <select id="edit-departure-city" name="departure_city" required>
                    <option value="Adana">Adana</option>
                    <option value="Adıyaman">Adıyaman</option>
                    <option value="Afyonkarahisar">Afyonkarahisar</option>
                    <option value="Ağrı">Ağrı</option>
                    <option value="Aksaray">Aksaray</option>
                    <option value="Amasya">Amasya</option>
                    <option value="Ankara">Ankara</option>
                    <option value="Antalya">Antalya</option>
                    <option value="Ardahan">Ardahan</option>
                    <option value="Artvin">Artvin</option>
                    <option value="Aydın">Aydın</option>
                    <option value="Balıkesir">Balıkesir</option>
                    <option value="Bartın">Bartın</option>
                    <option value="Batman">Batman</option>
                    <option value="Bayburt">Bayburt</option>
                    <option value="Bilecik">Bilecik</option>
                    <option value="Bingöl">Bingöl</option>
                    <option value="Bitlis">Bitlis</option>
                    <option value="Bolu">Bolu</option>
                    <option value="Burdur">Burdur</option>
                    <option value="Bursa">Bursa</option>
                    <option value="Çanakkale">Çanakkale</option>
                    <option value="Çankırı">Çankırı</option>
                    <option value="Çorum">Çorum</option>
                    <option value="Denizli">Denizli</option>
                    <option value="Diyarbakır">Diyarbakır</option>
                    <option value="Düzce">Düzce</option>
                    <option value="Edirne">Edirne</option>
                    <option value="Elazığ">Elazığ</option>
                    <option value="Erzincan">Erzincan</option>
                    <option value="Erzurum">Erzurum</option>
                    <option value="Eskişehir">Eskişehir</option>
                    <option value="Gaziantep">Gaziantep</option>
                    <option value="Giresun">Giresun</option>
                    <option value="Gümüşhane">Gümüşhane</option>
                    <option value="Hakkari">Hakkari</option>
                    <option value="Hatay">Hatay</option>
                    <option value="Iğdır">Iğdır</option>
                    <option value="Isparta">Isparta</option>
                    <option value="İstanbul">İstanbul</option>
                    <option value="İzmir">İzmir</option>
                    <option value="Kahramanmaraş">Kahramanmaraş</option>
                    <option value="Karabük">Karabük</option>
                    <option value="Karaman">Karaman</option>
                    <option value="Kars">Kars</option>
                    <option value="Kastamonu">Kastamonu</option>
                    <option value="Kayseri">Kayseri</option>
                    <option value="Kırıkkale">Kırıkkale</option>
                    <option value="Kırklareli">Kırklareli</option>
                    <option value="Kırşehir">Kırşehir</option>
                    <option value="Kilis">Kilis</option>
                    <option value="Kocaeli">Kocaeli</option>
                    <option value="Konya">Konya</option>
                    <option value="Kütahya">Kütahya</option>
                    <option value="Malatya">Malatya</option>
                    <option value="Manisa">Manisa</option>
                    <option value="Mardin">Mardin</option>
                    <option value="Mersin">Mersin</option>
                    <option value="Muğla">Muğla</option>
                    <option value="Muş">Muş</option>
                    <option value="Nevşehir">Nevşehir</option>
                    <option value="Niğde">Niğde</option>
                    <option value="Ordu">Ordu</option>
                    <option value="Osmaniye">Osmaniye</option>
                    <option value="Rize">Rize</option>
                    <option value="Sakarya">Sakarya</option>
                    <option value="Samsun">Samsun</option>
                    <option value="Siirt">Siirt</option>
                    <option value="Sinop">Sinop</option>
                    <option value="Sivas">Sivas</option>
                    <option value="Şanlıurfa">Şanlıurfa</option>
                    <option value="Şırnak">Şırnak</option>
                    <option value="Tekirdağ">Tekirdağ</option>
                    <option value="Tokat">Tokat</option>
                    <option value="Trabzon">Trabzon</option>
                    <option value="Tunceli">Tunceli</option>
                    <option value="Uşak">Uşak</option>
                    <option value="Van">Van</option>
                    <option value="Yalova">Yalova</option>
                    <option value="Yozgat">Yozgat</option>
                    <option value="Zonguldak">Zonguldak</option>

                </select>

                <label for="edit-destination-city">Varış Şehri:</label>
                <select id="edit-destination-city" name="destination_city" required>
                    <option value="Adana">Adana</option>
                    <option value="Adıyaman">Adıyaman</option>
                    <option value="Afyonkarahisar">Afyonkarahisar</option>
                    <option value="Ağrı">Ağrı</option>
                    <option value="Aksaray">Aksaray</option>
                    <option value="Amasya">Amasya</option>
                    <option value="Ankara">Ankara</option>
                    <option value="Antalya">Antalya</option>
                    <option value="Ardahan">Ardahan</option>
                    <option value="Artvin">Artvin</option>
                    <option value="Aydın">Aydın</option>
                    <option value="Balıkesir">Balıkesir</option>
                    <option value="Bartın">Bartın</option>
                    <option value="Batman">Batman</option>
                    <option value="Bayburt">Bayburt</option>
                    <option value="Bilecik">Bilecik</option>
                    <option value="Bingöl">Bingöl</option>
                    <option value="Bitlis">Bitlis</option>
                    <option value="Bolu">Bolu</option>
                    <option value="Burdur">Burdur</option>
                    <option value="Bursa">Bursa</option>
                    <option value="Çanakkale">Çanakkale</option>
                    <option value="Çankırı">Çankırı</option>
                    <option value="Çorum">Çorum</option>
                    <option value="Denizli">Denizli</option>
                    <option value="Diyarbakır">Diyarbakır</option>
                    <option value="Düzce">Düzce</option>
                    <option value="Edirne">Edirne</option>
                    <option value="Elazığ">Elazığ</option>
                    <option value="Erzincan">Erzincan</option>
                    <option value="Erzurum">Erzurum</option>
                    <option value="Eskişehir">Eskişehir</option>
                    <option value="Gaziantep">Gaziantep</option>
                    <option value="Giresun">Giresun</option>
                    <option value="Gümüşhane">Gümüşhane</option>
                    <option value="Hakkari">Hakkari</option>
                    <option value="Hatay">Hatay</option>
                    <option value="Iğdır">Iğdır</option>
                    <option value="Isparta">Isparta</option>
                    <option value="İstanbul">İstanbul</option>
                    <option value="İzmir">İzmir</option>
                    <option value="Kahramanmaraş">Kahramanmaraş</option>
                    <option value="Karabük">Karabük</option>
                    <option value="Karaman">Karaman</option>
                    <option value="Kars">Kars</option>
                    <option value="Kastamonu">Kastamonu</option>
                    <option value="Kayseri">Kayseri</option>
                    <option value="Kırıkkale">Kırıkkale</option>
                    <option value="Kırklareli">Kırklareli</option>
                    <option value="Kırşehir">Kırşehir</option>
                    <option value="Kilis">Kilis</option>
                    <option value="Kocaeli">Kocaeli</option>
                    <option value="Konya">Konya</option>
                    <option value="Kütahya">Kütahya</option>
                    <option value="Malatya">Malatya</option>
                    <option value="Manisa">Manisa</option>
                    <option value="Mardin">Mardin</option>
                    <option value="Mersin">Mersin</option>
                    <option value="Muğla">Muğla</option>
                    <option value="Muş">Muş</option>
                    <option value="Nevşehir">Nevşehir</option>
                    <option value="Niğde">Niğde</option>
                    <option value="Ordu">Ordu</option>
                    <option value="Osmaniye">Osmaniye</option>
                    <option value="Rize">Rize</option>
                    <option value="Sakarya">Sakarya</option>
                    <option value="Samsun">Samsun</option>
                    <option value="Siirt">Siirt</option>
                    <option value="Sinop">Sinop</option>
                    <option value="Sivas">Sivas</option>
                    <option value="Şanlıurfa">Şanlıurfa</option>
                    <option value="Şırnak">Şırnak</option>
                    <option value="Tekirdağ">Tekirdağ</option>
                    <option value="Tokat">Tokat</option>
                    <option value="Trabzon">Trabzon</option>
                    <option value="Tunceli">Tunceli</option>
                    <option value="Uşak">Uşak</option>
                    <option value="Van">Van</option>
                    <option value="Yalova">Yalova</option>
                    <option value="Yozgat">Yozgat</option>
                    <option value="Zonguldak">Zonguldak</option>

                </select>

                <label for="edit-departure-time">Kalkış Saati:</label>
                <input type="datetime-local" id="edit-departure-time" name="departure_time" required>

                <label for="edit-arrival-time">Varış Saati:</label>
                <input type="datetime-local" id="edit-arrival-time" name="arrival_time" required>

                <label for="edit-price">Fiyat (TL):</label>
                <input type="number" id="edit-price" name="price" min="0" required>

                <label for="edit-capacity">Kapasite:</label>
                <input type="number" id="edit-capacity" name="capacity" min="1" required>

                <div class="popup-buttons">
                    <button type="submit">Kaydet</button>
                    <button type="button" id="close-edit-popup">Kapat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const popup = document.getElementById('company-popup');
        const openBtn = document.getElementById('create-company');
        const closeBtn = document.getElementById('close-popup');

        openBtn.addEventListener('click', () => {
            popup.style.display = 'flex';
        });

        closeBtn.addEventListener('click', () => {
            popup.style.display = 'none';
        });

        popup.addEventListener('click', (e) => {
            if (e.target === popup) popup.style.display = 'none';
        });
    </script>
    <script>
        const createNewUserCheckbox = document.getElementById('create-new-user');
        const newUserFields = document.getElementById('new-user-fields');
        const adminSelect = document.getElementById('admin-select');
        const adminLabel = document.getElementById('admin-label');
        createNewUserCheckbox.addEventListener('change', function() {
            if (this.checked) {
                newUserFields.style.display = 'block';
                adminSelect.style.display = 'none';
                adminLabel.style.display = 'none';
            } else {
                newUserFields.style.display = 'none';
                adminSelect.style.display = 'flex';
                adminLabel.style.display = 'flex';
            }
        });
    </script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                document.getElementById('edit-trip-id').value = this.dataset.id;
                document.getElementById('edit-departure-city').value = this.dataset.departureCity;
                document.getElementById('edit-destination-city').value = this.dataset.destinationCity;
                document.getElementById('edit-departure-time').value = this.dataset.departureTime;
                document.getElementById('edit-arrival-time').value = this.dataset.arrivalTime;
                document.getElementById('edit-price').value = this.dataset.price;
                document.getElementById('edit-capacity').value = this.dataset.capacity;

                document.getElementById('edit-trip-popup').style.display = 'flex';
            });
        });

        document.getElementById('close-edit-popup').addEventListener('click', function() {
            document.getElementById('edit-trip-popup').style.display = 'none';
        });

        document.getElementById('edit-trip-popup').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const departureInput = document.getElementById('departure-time');
            const arrivalInput = document.getElementById('arrival-time');

            const now = new Date();
            now.setMinutes(now.getMinutes() + 1); 
            const minDateTime = now.toISOString().slice(0, 16);

            departureInput.min = minDateTime;
            arrivalInput.min = minDateTime;

            departureInput.addEventListener('change', function() {
                if (this.value) {
                    const departureTime = new Date(this.value);
                    departureTime.setMinutes(departureTime.getMinutes() + 30);

                    const minArrivalTime = departureTime.toISOString().slice(0, 16);
                    arrivalInput.min = minArrivalTime;

                    if (arrivalInput.value && new Date(arrivalInput.value) <= new Date(this.value)) {
                        arrivalInput.value = '';
                    }
                }
            });
        });
    </script>
</body>

</html>