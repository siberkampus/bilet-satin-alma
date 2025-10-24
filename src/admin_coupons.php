<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require "libs/functions.php";
require "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {

        $sql = "
                SELECT 
                    c.id,
                    c.code,
                    c.discount,
                    c.usage_limit,
                    c.expire_date,
                    c.created_at,
                    c.company_id,
                    bc.name as company_name,
                    bc.logo_path as company_logo
                FROM Coupons c
                LEFT JOIN Bus_Company bc ON c.company_id = bc.id
                ORDER BY c.created_at DESC
            ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();


        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$coupons) {
            $coupons = [];
        }
    } catch (PDOException $e) {
        error_log("Kupon listeleme hatası: " . $e->getMessage());
        $coupons = [];
        $_SESSION['error'] = "Kuponlar yüklenirken bir hata oluştu.";
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


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
                            <a class="nav-link" href="admin_panel.php">
                                <i class="bi bi-bookmarks"></i> Seferler
                            </a>
                        </li>
                     
                        <li class="nav-item">
                            <a class="nav-link" href="/admin_coupons.php">
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
                            <div class="col-sm-6 col-12 text-sm-end">
                                <div class="mx-n1">
                                    <button id="create-company" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
                                        <i class="bi bi-plus pe-2"></i>
                                        Kupon Ekle
                                    </button>
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
                            <h5 class="mb-0">Biletler</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">ŞİRKET ADI</th>

                                        <th scope="col">KUPON KODU</th>
                                        <th scope="col">İNDİRİM (%)</th>
                                        <th scope="col">KULLANIM LİMİTİ</th>
                                        <th scope="col">BİTİŞ TARİHİ</th>
                                        <th scope="col">OLUŞTURMA ZAMANI</th>
                                        <th></th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($coupon['company_name']); ?> </td>
                                            <td><?php echo htmlspecialchars($coupon['code']); ?> </td>
                                            <td><?php echo htmlspecialchars($coupon['discount']); ?>%</td>
                                            <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                            <td><?php echo htmlspecialchars($coupon['expire_date']); ?></td>
                                            <td><?php echo htmlspecialchars($coupon['created_at']); ?></td>

                                            <td class="text-end">
                                                <form action="delete_coupon.php" method="post" style="display:inline;" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                                    <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($coupon['id']) ?>">

                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Kuponu Sil
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

    <div class="modal fade" id="couponModal" tabindex="-1" aria-labelledby="couponModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="couponForm" method="POST" action="add_coupon.php">
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($company_id) ?>">

                    <div class="modal-header">
                        <h5 class="modal-title" id="couponModalLabel">Yeni Kupon Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="code" class="form-label">Kupon Kodu</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="discount" class="form-label">İndirim (%)</label>
                            <input type="number" class="form-control" id="discount" name="discount" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Kullanım Limiti</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="expire_date" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="expire_date" name="expire_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>