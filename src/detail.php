<?php
require "connection.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmtDeparture = $pdo->prepare("
    SELECT DISTINCT departure_city
    FROM Trips
    ORDER BY departure_city ASC
");
        $stmtDeparture->execute();
        $departureCities = $stmtDeparture->fetchAll(PDO::FETCH_COLUMN);

        $stmtDestination = $pdo->prepare("
    SELECT DISTINCT destination_city
    FROM Trips
    ORDER BY destination_city ASC
");
        $stmtDestination->execute();
        $destinationCities = $stmtDestination->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        echo "Veritabanı hatası: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Form verilerini al
    $from = $_POST['from'] ?? null;
    $to = $_POST['to'] ?? null;
    $date = $_POST['date'] ?? null;

    // Boş alan kontrolü
    if (!$from || !$to || !$date) {
        echo "<p>Lütfen tüm alanları doldurun.</p>";
        exit;
    }

    try {
        // Tarihi formatla (örneğin: YYYY-MM-DD)
        $formattedDate = date('Y-m-d', strtotime($date));

        // Trips tablosundan eşleşen kayıtları getir
        $sql = "
            SELECT 
                Trips.id,
                Trips.company_id,
                Bus_Company.name AS company_name,
                Bus_Company.logo_path AS company_logo,
                Trips.departure_city,
                Trips.destination_city,
                Trips.departure_time,
                Trips.arrival_time,
                Trips.price,
                Trips.capacity
            FROM Trips
            INNER JOIN Bus_Company ON Trips.company_id = Bus_Company.id
            WHERE Trips.departure_city = :from
              AND Trips.destination_city = :to
              AND substr(Trips.departure_time, 1, 10) = :date
            ORDER BY Trips.departure_time ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':from' => $from,
            ':to' => $to,
            ':date' => $formattedDate
        ]);

        $matchingTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Veritabanı hatası: " . htmlspecialchars($e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="static/css/detail.css">
    <link rel="stylesheet" href="static/css/homepage.css">


    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .trip-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 800px;
            margin: 20px auto;
        }

        .trip-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .trip-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .trip-info {
            display: flex;
            flex-direction: column;
        }

        .trip-route {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .trip-time,
        .trip-price {
            font-size: 0.95rem;
            color: #555;
        }

        .trip-price {
            font-weight: bold;
            color: #e74c3c;
        }

        .no-trips {
            text-align: center;
            font-size: 1rem;
            color: #888;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <img src="static/images/logo.png" alt="AnadoluBilet Logo" style="height:70px;border-radius: 50%;">

        <div class="nav-links">
            <a href="/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Anasayfa</a>
            <a href="/detail.php" class="<?= basename($_SERVER['PHP_SELF']) === 'detail.php' ? 'active' : '' ?>">Sefer Detayları</a>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "admin"): ?>
                <a href="/admin_panel.php">Admin Panel </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "company"): ?>
                <a href="/company_panel.php">Yönetici Paneli </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true && $_SESSION["role"] === "user"): ?>
                <a href="/user_profile.php">Profilim </a>
            <?php endif; ?>
            <?php if (isset($_SESSION["login"]) && $_SESSION["login"] === true): ?>
                <a href="/logout.php">Çıkış Yap </a>
            <?php else: ?>
                <a href="/login.php">Giriş Yap</a>
                <a href="/register.php">Kayıt Ol</a>
            <?php endif; ?>
        </div>




        <button class="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>
    <div class="container">
        <div class="header">
            <h1>
                <ion-icon name="bus-outline"></ion-icon>
                Anadolu Bilet
            </h1>
            <p>Türkiye'nin Önde Gelen Otobüs Bileti Sitesi</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" id="searchForm" action="detail.php" method="post">
                <div class="form-group" style="position: relative;">
                    <label for="from">
                        <ion-icon name="location-outline"></ion-icon>
                        Nereden
                    </label>
                    <select id="from" name="from" required>
                        <option value="">Kalkış şehrini seçin</option>
                        <?php foreach ($departureCities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>">
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                </div>
                <div class="form-group">
                    <label for="to">
                        <ion-icon name="location-outline"></ion-icon>
                        Nereye
                    </label>
                    <select id="to" name="to" required>
                        <option value="">Varış şehrini seçin</option>
                        <?php foreach ($destinationCities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>">
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
                <div class="form-group">
                    <label for="date">
                        <ion-icon name="calendar-outline"></ion-icon>
                        Gidiş Tarihi
                    </label>
                    <input type="date" id="date" required name="date">
                    <ion-icon name="calendar-outline" class="input-icon"></ion-icon>
                </div>




                <button type="submit" class="search-btn" id="searchBtn" style="width:150px;height:55px;">
                    <ion-icon name="search-outline"></ion-icon>
                    Search Buses
                </button>
            </form>
        </div>
        <div class="trip-container">
            <?php if (!empty($matchingTrips)): ?>
                <?php foreach ($matchingTrips as $trip): ?>
                    <a href="/trip.php?id=<?= urlencode($trip['id']) ?>" class="trip-card-link">
                        <div class="trip-card">
                            <div class="company-info">
                                <?php if (!empty($trip['company_logo'])): ?>
                                    <img src="<?= htmlspecialchars($trip['company_logo']) ?>"
                                        alt="<?= htmlspecialchars($trip['company_name']) ?> Logo"
                                        class="company-logo">
                                <?php else: ?>
                                    <div class="no-logo">🚌</div>
                                <?php endif; ?>
                                <span class="company-name"><?= htmlspecialchars($trip['company_name']) ?></span>
                            </div>

                            <div class="trip-info">
                                <div class="trip-route">
                                    <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>
                                </div>
                                <div class="trip-time">
                                    <?= htmlspecialchars($trip['departure_time']) ?> → <?= htmlspecialchars($trip['arrival_time']) ?>
                                </div>
                            </div>

                            <div class="trip-price">
                                <?= htmlspecialchars($trip['price']) ?>₺
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-trips">Hiç sefer bulunamadı.</p>
            <?php endif; ?>
        </div>



    </div>
    <script>
        let selectedBus = null;
        let selectedSeats = [];
        let seatPrice = 0;
        let allBuses = [];
        let currentFilter = 'all';

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').min = today;
        document.getElementById('date').value = today;



        document.getElementById('searchForm').addEventListener('submit', function(e) {


            const from = document.getElementById('from').value;
            const to = document.getElementById('to').value;
            const date = document.getElementById('date').value;

            if (!from || !to) {
                showNotification('Please select both departure and destination cities', 'error');
                return;
            }

            if (from === to) {
                showNotification('Departure and destination cities cannot be the same', 'error');
                return;
            }

            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showNotification('Please select a valid departure date', 'error');
                return;
            }

        });


        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 300px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;

            const colors = {
                success: '#27ae60',
                error: '#e74c3c',
                warning: '#f39c12',
                info: '#3498db'
            };

            const icons = {
                success: 'checkmark-circle-outline',
                error: 'close-circle-outline',
                warning: 'warning-outline',
                info: 'information-circle-outline'
            };

            notification.style.background = colors[type];
            notification.innerHTML = `
                <ion-icon name="${icons[type]}"></ion-icon>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</body>

</html>