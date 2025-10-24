<?php
require "connection.php";
require "libs/functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    $coupon_code = trim($input['coupon_code'] ?? '');

    if (!$coupon_code) {
        echo json_encode(['success' => false, 'message' => 'Kupon kodu boş olamaz.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT discount, usage_limit, expire_date 
            FROM Coupons 
            WHERE code = :code
            LIMIT 1
        ");
        $stmt->execute(['code' => $coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {
            $today = date('Y-m-d');
            if ($coupon['usage_limit'] > 0 && $coupon['expire_date'] >= $today) {
                echo json_encode([
                    'success' => true,
                    'discount' => floatval($coupon['discount'])
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Kupon süresi dolmuş veya kullanımı tükenmiş.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz kupon kodu!'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $trip_id = $_GET['id'] ?? null;

    if (!$trip_id) {
        echo "Trip ID eksik!";
        exit;
    }

    try {
        $stmtTrip = $pdo->prepare("
            SELECT id, company_id, departure_city, destination_city, 
                   departure_time, arrival_time, price, capacity, created_date
            FROM Trips
            WHERE id = ?
        ");
        $stmtTrip->execute([$trip_id]);
        $trip = $stmtTrip->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            echo "Trip bulunamadı!";
            exit;
        }

        $stmtSeats = $pdo->prepare("
            SELECT bs.seat_number 
            FROM Booked_Seats bs
            JOIN Tickets t ON bs.ticket_id = t.id
            WHERE t.trip_id = ?
              AND t.status = 'active'
            ORDER BY bs.seat_number ASC
        ");
        $stmtSeats->execute([$trip_id]);
        $bookedSeats = $stmtSeats->fetchAll(PDO::FETCH_COLUMN);
        $capacity = $trip['capacity'];
        $seatsPerRow = 4;
        $totalRows = ceil($capacity / $seatsPerRow);
        $departureFormatted = formatDateTime($trip['departure_time'])['short'];
        $arrivalFormatted = formatDateTime($trip['arrival_time'])['short'];
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
    <link rel="stylesheet" href="static/css/trip.css">

    <style>
        .trip-info-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            margin: 25px 0;
            color: white;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.15),
                0 8px 16px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .trip-info-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(180deg);
            }
        }

        .trip-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .trip-header h2 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }

        .trip-header .subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }

        .trip-route {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .trip-route:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .city {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .city-name {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .city-time {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .route-arrow {
            font-size: 45px;
            margin: 0 25px;
            color: #ffd700;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateX(0);
            }

            50% {
                transform: translateX(5px);
            }
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            background: rgba(255, 255, 255, 0.15);
            padding: 25px;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .detail-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .detail-label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 20px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .price-badge {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 24px;
            display: inline-block;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .price-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }

        @media (max-width: 768px) {
            .trip-info-container {
                padding: 20px;
                margin: 15px 0;
                border-radius: 16px;
            }

            .trip-header h2 {
                font-size: 26px;
            }

            .trip-route {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
            }

            .route-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
                font-size: 35px;
            }

            .city-name {
                font-size: 22px;
            }

            .trip-details {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 20px;
            }

            .price-badge {
                font-size: 20px;
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .trip-info-container {
                padding: 15px;
            }

            .trip-header h2 {
                font-size: 22px;
            }

            .city-name {
                font-size: 20px;
            }

            .detail-value {
                font-size: 18px;
            }
        }

        .trip-info-container.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .trip-info-container.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 3;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <div class="trip-info-container">
        <div class="trip-header">
            <h2>Sefer Detayları</h2>
        </div>

        <div class="trip-route">
            <div class="city">
                <div class="city-name"><?= htmlspecialchars($trip['departure_city']) ?></div>
            </div>
            <div class="city">
                <div class="city-time"><?= $departureFormatted ?></div>
            </div>

            <div class="route-arrow">➜</div>
            <div class="city">
                <div class="city-name"><?= htmlspecialchars($trip['destination_city']) ?></div>
            </div>
            <div class="city">
                <div class="city-time"><?= $arrivalFormatted ?></div>
            </div>
        </div>

        <div class="trip-details">

            <div class="detail-item">
                <span class="detail-label">Kapasite</span>
                <span class="detail-value"><?= htmlspecialchars($trip['capacity']) ?> koltuk</span>
            </div>



            <div class="detail-item">
                <span class="detail-label">Ücret</span>
                <span class="price-badge"><?= htmlspecialchars($trip['price']) ?>₺</span>
            </div>
        </div>

    </div>
    <div class="bus seat2-2 border-0 p-0">
        <div class="bus-layout">
            <form id="seatForm" action="book_seat.php" method="POST">
                <input type="hidden" id="trip_id" name="trip_id" value="<?= htmlspecialchars($trip['id'] ?? '') ?>">
                <input type="hidden" id="seat_price" name="seat_price" value="<?= htmlspecialchars($trip["price"]) ?>">

                <?php
                $hasAvailableSeats = false;
                for ($row = 1; $row <= $totalRows; $row++):
                ?>
                    <div class="seat-row-<?= $row ?>">
                        <ol class="seats">
                            <?php
                            $startSeat = ($row - 1) * $seatsPerRow + 1;
                            $endSeat = min($row * $seatsPerRow, $capacity);

                            for ($seatNum = $startSeat; $seatNum <= $endSeat; $seatNum++):
                                $isReserved = in_array($seatNum, $bookedSeats);
                                if (!$isReserved) $hasAvailableSeats = true;
                            ?>
                                <li class="seat">
                                    <input
                                        role="input-passenger-seat"
                                        name="seat_number"
                                        id="seat-radio-<?= $row ?>-<?= $seatNum ?>"
                                        value="<?= $seatNum ?>"
                                        type="radio"
                                        <?= $isReserved ? 'disabled' : '' ?>>
                                    <label
                                        for="seat-radio-<?= $row ?>-<?= $seatNum ?>"
                                        class="<?= $isReserved ? 'reserved' : '' ?>">
                                        <?= $seatNum ?>
                                    </label>
                                </li>
                            <?php endfor; ?>
                        </ol>
                    </div>
                <?php endfor; ?>

                <div style="text-align: center; margin-top: 1rem;">
                    <label for="coupon_code">Kupon Kodu:</label>
                    <input
                        type="text"
                        name="coupon_code"
                        id="coupon_code"
                        placeholder="Kupon kodunuzu girin"
                        style="padding: 0.5rem; margin-left: 0.5rem; width: 200px;">
                </div>

                <div style="text-align: center; margin-top: 1rem;">
                    <p>Toplam Fiyat: <span id="totalPrice"><?= htmlspecialchars($trip["price"]) ?>₺</span></p>
                </div>

                <div style="text-align: center; margin-top: 1rem;">
                    <?php if ($hasAvailableSeats): ?>
                        <button type="submit" class="btn btn-primary mt-3">Rezervasyonu Tamamla</button>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            Bu seferde boş koltuk bulunmamaktadır.
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('applyCoupon').addEventListener('click', () => {
            const code = document.getElementById('coupon_code').value.trim();
            if (!code) return alert("Lütfen bir kupon kodu girin.");

            const seatPrice = parseFloat(document.getElementById('seat_price').value);

            fetch('trip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        coupon_code: code
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const discount = parseFloat(data.discount); // örn: 0.1 => %10
                        console.log(discount);
                        const newPrice = (seatPrice * (100 - discount)) / 100;

                        document.getElementById('totalPrice').textContent = newPrice.toFixed(2) + "₺";
                    } else {
                        alert(data.message || "Geçersiz kupon kodu!");
                    }
                })
                .catch(err => console.error("Hata:", err));
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function showNotification(message, type = 'info') {
                const existingNotifications = document.querySelectorAll('.custom-notification');
                existingNotifications.forEach(notif => {
                    notif.style.transform = 'translateX(100%)';
                    setTimeout(() => notif.remove(), 300);
                });

                const notification = document.createElement('div');
                notification.className = 'custom-notification';

                const colors = {
                    success: '#27ae60',
                    error: '#e74c3c',
                    warning: '#f39c12',
                    info: '#3498db'
                };

                const icons = {
                    success: '✓',
                    error: '✕',
                    warning: '⚠',
                    info: 'ℹ'
                };

                notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                background: ${colors[type] || colors.info};
                color: white;
                font-weight: 600;
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 350px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-family: Arial, sans-serif;
            `;

                notification.innerHTML = `
                <span style="font-size: 18px;">${icons[type] || icons.info}</span>
                <span>${message}</span>
            `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 10);

                setTimeout(() => {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 4000);
            }

            const form = document.getElementById('seatForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasError = false;

                    const tripId = document.getElementById('trip_id')?.value.trim();
                    if (!tripId) {
                        e.preventDefault();
                        showNotification('Sefer bilgisi bulunamadı! Lütfen tekrar deneyin.', 'error');
                        hasError = true;
                        return;
                    }

                    const selectedSeat = document.querySelector('input[name="seat_number"]:checked');
                    if (!selectedSeat) {
                        e.preventDefault();
                        showNotification('Lütfen bir koltuk seçiniz.', 'warning');
                        hasError = true;
                        return;
                    }

                    if (selectedSeat.disabled) {
                        e.preventDefault();
                        showNotification('Seçtiğiniz koltuk dolu! Lütfen başka bir koltuk seçin.', 'error');
                        hasError = true;
                        return;
                    }

                    if (!hasError) {
                        showNotification('Rezervasyonunuz işleme alınıyor...', 'info');
                    }
                });
            }

            const seatInputs = document.querySelectorAll('input[name="seat_number"]:not(:disabled)');
            seatInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.checked) {
                        showNotification(`Koltuk ${this.value} seçildi`, 'success');
                    }
                });
            });

            const availableSeats = document.querySelectorAll('input[name="seat_number"]:not(:disabled)');
            if (availableSeats.length === 0) {
                showNotification('Bu seferde boş koltuk bulunmamaktadır.', 'warning');
            }
        });
    </script>
</body>

</html>