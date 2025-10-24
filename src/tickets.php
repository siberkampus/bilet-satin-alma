<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'company') {
    header("Location: login.php");
    exit;
}

require "libs/functions.php";  
require "connection.php";      
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $company_id = $_SESSION['company_id'];

    $sql = "
    SELECT 
        t.id AS ticket_id,
        u.full_name AS user_name,
        t.total_price,
        t.status,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time
    FROM Tickets t
    JOIN User u ON t.user_id = u.id
    JOIN Trips tr ON t.trip_id = tr.id
    WHERE tr.company_id = :company_id
      AND t.status = 'active'
    ORDER BY t.created_at DESC
";


    $stmt = $pdo->prepare($sql);
    $stmt->execute(['company_id' => $company_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'] ?? null;
    if (!$ticketId) {
        $_SESSION['error'] = 'Ticket ID gönderilmedi!';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (!isset($_SESSION['login']) || !isset($_SESSION['email'])) {
        $_SESSION['error'] = "Giriş yapmanız gerekiyor.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $userEmail = $_SESSION['email'];

    $stmt = $pdo->prepare("SELECT id, role, company_id, balance FROM User WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "Kullanıcı bulunamadı.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $userRole = $user['role'];
    $userCompanyId = $user['company_id'] ?? null;

    if ($userRole !== 'company') {
        $_SESSION['error'] = "Bu işlemi gerçekleştirme yetkiniz yok.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT t.id AS ticket_id, t.user_id, t.total_price, t.status, 
                   tr.company_id, tr.departure_time, tr.departure_city, tr.destination_city
            FROM Tickets t
            JOIN Trips tr ON t.trip_id = tr.id
            WHERE t.id = :ticket_id AND tr.company_id = :company_id
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'company_id' => $userCompanyId
        ]);

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            throw new Exception("Bilet bulunamadı veya sizin şirketinize ait değil!");
        }

        if ($ticket['status'] !== 'active') {
            throw new Exception("Bilet zaten iptal edilmiş veya süresi geçmiş.");
        }

        $departureTime = new DateTime($ticket['departure_time']);
        $currentTime = new DateTime();
        $timeDifference = $departureTime->getTimestamp() - $currentTime->getTimestamp();

        if ($timeDifference < 3600) {
            throw new Exception("Kalkışa 1 saatten az kaldığı için bilet iptal edilemez!");
        }

        $stmt = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);

        $refundAmount = (float)$ticket['total_price'];
        $stmt = $pdo->prepare("UPDATE User SET balance = balance + :refund WHERE id = :user_id");
        $stmt->execute([
            'refund' => $refundAmount,
            'user_id' => $ticket['user_id']
        ]);

        $pdo->commit();

        $_SESSION['success'] = "Bilet başarıyla iptal edildi. Kullanıcıya iade edilen tutar: $refundAmount ₺";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "İptal işlemi sırasında hata oluştu: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
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
                            <h5 class="mb-0">Biletler</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Kullanıcı Adı</th>
                                        <th scope="col">Tutar</th>
                                        <th scope="col">Kalkış Şehri</th>
                                        <th scope="col">Varış Şehri</th>
                                        <th scope="col">Kalkış Zamanı</th>
                                        <th scope="col">Varış Zamanı</th>
                                        <th scope="col">Durum</th>
                                        <th></th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['total_price']); ?> ₺</td>
                                            <td><?php echo htmlspecialchars($ticket['departure_city']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['departure_time']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['arrival_time']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                            <td class="text-end">
                                                <form action="tickets.php" method="post" style="display:inline;" class="cancel-ticket-form">

                                                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-neutral edit-admin-btn">
                                                        İptal Et
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
                    <option value="İstanbul">İstanbul</option>
                    <option value="Ankara">Ankara</option>
                    <option value="İzmir">İzmir</option>
                </select>

                <label for="edit-destination-city">Varış Şehri:</label>
                <select id="edit-destination-city" name="destination_city" required>
                    <option value="İstanbul">İstanbul</option>
                    <option value="Ankara">Ankara</option>
                    <option value="İzmir">İzmir</option>
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

</body>

</html>