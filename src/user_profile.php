<?php
session_start();
require "connection.php";
require "libs/functions.php";

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
  die("Geçersiz istek yöntemi.");
}

if (!isset($_SESSION['login']) || !isset($_SESSION['email'])) {
  die("Giriş yapmanız gerekiyor.");
}

$useremail = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT id, full_name, email, role, company_id, balance, created_at 
                       FROM User 
                       WHERE email = ?");
$stmt->execute([$useremail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  die("Kullanıcı bulunamadı.");
}

if ($user['role'] != "user") {
  header("Location: index.php");
}

$userId = $user['id'];

$stmt = $pdo->prepare("
    SELECT t.id AS ticket_id, t.status, t.total_price, t.created_at AS ticket_created,
           tr.id AS trip_id, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, tr.price AS trip_price,
           bs.seat_number
    FROM Tickets t
    INNER JOIN Trips tr ON t.trip_id = tr.id
    LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC, bs.seat_number ASC
");
$stmt->execute([$userId]);
$ticketSeats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeTickets = [];
$cancelledTickets = [];
$expiredTickets = [];

foreach ($ticketSeats as $row) {
  $ticketId = $row['ticket_id'];
  $seatNumber = $row['seat_number'];

  switch ($row['status']) {
    case 'active':
      $list = &$activeTickets;
      break;
    case 'canceled':
      $list = &$cancelledTickets;
      break;
    case 'expired':
      $list = &$expiredTickets;
      break;
    default:
      continue 2; 
  }

  if (!isset($list[$ticketId])) {
    $list[$ticketId] = [
      'ticket_id' => $ticketId,
      'departure_city' => $row['departure_city'],
      'destination_city' => $row['destination_city'],
      'departure_time' => $row['departure_time'],
      'arrival_time' => $row['arrival_time'],
      'total_price' => $row['total_price'],
      'seats' => [],
    ];
  }

  if ($seatNumber) {
    $list[$ticketId]['seats'][] = $seatNumber;
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="static/css/user.css">
  <link rel="stylesheet" href="static/css/homepage.css">

  <style>
    .cancel-btn {
      background-color: #e74c3c;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.3s;
    }

    .cancel-btn:hover {
      background-color: #ff1900ff;
    }

    .download-pdf {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      color: #fff;
      background-color: #3498db;
      padding: 5px 10px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.2s;
    }

    .download-pdf:hover {
      background-color: #2980b9;
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
    <div class="profile-header">
      <div class="profile-img">
        <img src="static/images/user.jpeg" width="200" alt="Profile Image">
      </div>
      <div class="profile-nav-info">
        <h3 class="user-name"><?php echo htmlspecialchars($user['full_name']) ?></h3>


      </div>

    </div>

    <div class="main-bd">
      <div class="left-side">
        <div class="profile-side">
          <h3>Email</h3>

          <p class="user-mail"><i class="fa fa-envelope"></i> <?php echo  htmlspecialchars($user['email']); ?></p>
          <div class="user-bio">
            <h3>Bakiye</h3>
            <p class="bio">
              <?php echo htmlspecialchars($user['balance']) ?>
            </p>
          </div>


        </div>

      </div>
      <div class="right-side">

        <div class="nav">
          <ul>
            <li onclick="tabs(0)" class="user-post active">Geçmiş Biletler</li>
            <li onclick="tabs(1)" class="user-review">Aktif Biletler</li>
            <li onclick="tabs(2)" class="user-setting">İptal Edilen Biletler</li>

          </ul>
        </div>
        <div class="profile-body">
          <div class="profile-posts tab">
            <h2>Geçmiş Biletler</h2>
            <table border="0" cellpadding="1" cellspacing="30">
              <thead>
                <tr>
                  <th>Kalkış</th>
                  <th>Varış</th>
                  <th>Kalkış Saati</th>
                  <th>Varış Saati</th>
                  <th>Fiyat</th>
                  <th>Koltuk No</th>
                  <th>Durum</th>
                  <th>İndir</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($expiredTickets as $ticket):
                  $departure = formatDateTime($ticket['departure_time']);
                  $arrival = formatDateTime($ticket['arrival_time']);
                ?>
                  <tr>
                    <td><?= htmlspecialchars($ticket['departure_city']) ?></td>
                    <td><?= htmlspecialchars($ticket['destination_city']) ?></td>
                    <td><?= htmlspecialchars($departure['short']) ?></td>
                    <td><?= htmlspecialchars($arrival['short']) ?></td>
                    <td><?= htmlspecialchars($ticket['total_price']) ?></td>
                    <td><?= htmlspecialchars(implode(', ', $ticket['seats'] ?? [])) ?></td> 
                    <td>
                      <button type="button" class="cancel-btn" disabled>Geçmiş</button>
                    </td>
                    <td>
                      <a href="download_ticket.php?ticket_id=<?= urlencode($ticket['ticket_id']) ?>"
                        title="Bileti PDF olarak indir"
                        class="download-pdf">
                        📄 İndir
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="profile-reviews tab">
            <h2>Aktif Biletler</h2>
            <table border="0" cellpadding="1" cellspacing="30">
              <thead>
                <tr>
                  <th>Kalkış</th>
                  <th>Varış</th>
                  <th>Kalkış Saati</th>
                  <th>Varış Saati</th>
                  <th>Fiyat</th>
                  <th>Koltuk No</th>
                  <th>Durum</th>
                  <th>İndir</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activeTickets as $ticket):
                  $departure = formatDateTime($ticket['departure_time']);
                  $arrival = formatDateTime($ticket['arrival_time']);
                ?>
                  <tr>
                    <td><?= htmlspecialchars($ticket['departure_city']) ?></td>
                    <td><?= htmlspecialchars($ticket['destination_city']) ?></td>
                    <td><?= htmlspecialchars($departure['short']) ?></td>
                    <td><?= htmlspecialchars($arrival['short']) ?></td>
                    <td><?= htmlspecialchars($ticket['total_price']) ?></td>
                    <td><?= htmlspecialchars(implode(', ', $ticket['seats'] ?? [])) ?></td>

                    <td>
                      <form method="POST" action="cancel_ticket.php" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?');">
                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
                        <button type="submit" class="cancel-btn">İptal Et</button>
                      </form>
                    </td>
                    <td>
                      <a href="download_ticket.php?ticket_id=<?= urlencode($ticket['ticket_id']) ?>"
                        title="Bileti PDF olarak indir"
                        class="download-pdf">
                        📄 İndir
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>

            </table>
          </div>
          <div class="profile-settings tab">
            <div class="account-setting">
              <h2>İptal Edilenler</h2>
              <table border="0" cellpadding="1" cellspacing="30">
                <thead>
                  <tr>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Kalkış Saati</th>
                    <th>Varış Saati</th>
                    <th>Fiyat</th>
                    <th>Koltuk No</th> 
                    <th>Durum</th>
                    <th>İndir</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cancelledTickets as $ticket):
                    $departure = formatDateTime($ticket['departure_time']);
                    $arrival = formatDateTime($ticket['arrival_time']);
                  ?>
                    <tr>
                      <td><?= htmlspecialchars($ticket['departure_city']) ?></td>
                      <td><?= htmlspecialchars($ticket['destination_city']) ?></td>
                      <td><?= htmlspecialchars($departure['short']) ?></td>
                      <td><?= htmlspecialchars($arrival['short']) ?></td>
                      <td><?= htmlspecialchars($ticket['total_price']) ?></td>
                      <td><?= htmlspecialchars(implode(', ', $ticket['seats'] ?? [])) ?></td> <!-- Koltuk numaraları -->
                      <td>
                        <button type="button" class="cancel-btn" disabled>İptal Edilmiş</button>
                      </td>
                      <td>
                        <a href="download_ticket.php?ticket_id=<?= urlencode($ticket['ticket_id']) ?>"
                          title="Bileti PDF olarak indir"
                          class="download-pdf">
                          📄 İndir
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
  <script>
    const tab = document.querySelectorAll(".tab");
    const tabBtn = document.querySelectorAll(".nav ul li");

    function tabs(panelIndex) {
      tab.forEach(node => node.style.display = "none");
      tab[panelIndex].style.display = "block";

      tabBtn.forEach(btn => btn.classList.remove("active"));
      tabBtn[panelIndex].classList.add("active");
    }

    tabs(0);
  </script>
</body>

</html>