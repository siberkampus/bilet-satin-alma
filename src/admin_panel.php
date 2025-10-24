<?php
session_start();


if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require "libs/functions.php";
require "connection.php";

$company_name = $admin_id = $username = $email = $password = "";
$logo_path = null;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bc.id AS company_id, 
                bc.name AS company_name, 
                bc.logo_path, 
                bc.created_at,
                u.id AS admin_id,
                u.full_name AS admin_name, 
                u.email AS admin_email
            FROM Bus_Company bc
            LEFT JOIN User u ON u.company_id = bc.id AND u.role = 'company'
        ");
        $stmt->execute();
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$companies) {
            $companies = [];
        }

        $stmt2 = $pdo->prepare("
            SELECT id, full_name, email 
            FROM User 
            WHERE (role = 'user' OR role IS NULL) AND company_id IS NULL
        ");
        $stmt2->execute();
        $availableUsers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Veritabanı hatası: " . $e->getMessage());
        $errors[] = "Veritabanı hatası oluştu.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["company_name"]))) {
        $errors[] = "Firma adı zorunlu.";
    } else {
        $company_name = control_input(trim($_POST["company_name"]));
        if (strlen($company_name) < 2 || strlen($company_name) > 100) {
            $errors[] = "Firma adı 2-100 karakter arasında olmalıdır.";
        }
    }

    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['company_logo']['tmp_name'];
        $fileName = $_FILES['company_logo']['name'];
        $fileSize = $_FILES['company_logo']['size'];
        $fileType = $_FILES['company_logo']['type'];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            $errors[] = "Sadece JPG, JPEG, PNG veya GIF dosyalarına izin verilir.";
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
         
            $errors[] = "Geçersiz dosya türü.";
        }

        if ($fileSize > 5 * 1024 * 1024) {

            $errors[] = "Dosya boyutu 2 MB'tan büyük olamaz.";
        }

        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo "hataaa";
                $errors[] = "Upload klasörü oluşturulamadı.";
            }
        }

        if (empty($errors)) {
            $newFileName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $newFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $newFileName);
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $logo_path = 'uploads/' . $newFileName;
            } else {
                echo "logoooo";
                $errors[] = "Logo yükleme başarısız.";
            }
        }
    }

    $create_new_user = isset($_POST['create_new_user']);

    if ($create_new_user) {
        if (empty(trim($_POST["new_user_name"]))) {
            $errors[] = "Kullanıcı adı zorunlu.";
        } else {
            $username = control_input(trim($_POST["new_user_name"]));
            if (strlen($username) < 2 || strlen($username) > 50) {
                $errors[] = "Kullanıcı adı 2-50 karakter arasında olmalıdır.";
            }
        }

        if (empty(trim($_POST["new_user_email"]))) {
            $errors[] = "Email zorunlu.";
        } else {
            $email = control_input(trim($_POST["new_user_email"]));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Geçersiz email formatı.";
            }
        }

        if (empty($_POST["new_user_password"])) {
            $errors[] = "Parola zorunlu.";
        } else {
            $password = $_POST["new_user_password"];
            if (strlen($password) < 6) {
                $errors[] = "Parola en az 6 karakter olmalıdır.";
            }
        }
    } else {
        if (empty($_POST["admin_id"])) {
            $errors[] = "Admin seçimi zorunlu.";
        } else {
            $admin_id = control_input($_POST["admin_id"]);
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $company_id = uniqid('cmp_', true);
            $createdAt = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$company_id, $company_name, $logo_path, $createdAt]);

            if ($create_new_user) {
                $checkEmail = $pdo->prepare("SELECT id FROM User WHERE email = ?");
                $checkEmail->execute([$email]);

                if ($checkEmail->fetch()) {
                    throw new Exception("Bu e-posta adresi zaten kayıtlı.");
                }

                $user_id = uniqid('usr_', true);
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $role = 'company';

                $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $username, $email, $role, $hashedPassword, $company_id, $createdAt]);
            } else {
                $checkUser = $pdo->prepare("SELECT id, role FROM User WHERE id = ? AND (role = 'user' OR role IS NULL) AND company_id IS NULL");
                $checkUser->execute([$admin_id]);
                $existingUser = $checkUser->fetch();

                if (!$existingUser) {
                    throw new Exception("Seçilen kullanıcı bulunamadı veya zaten bir firmaya atanmış.");
                }

                $role = 'company';
                $stmt = $pdo->prepare("UPDATE User SET role = ?, company_id = ? WHERE id = ?");
                $stmt->execute([$role, $company_id, $admin_id]);
            }

            $pdo->commit();

            $_SESSION['success'] = "Firma ve kullanıcı başarıyla oluşturuldu.";
            header("Location: admin_panel.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "İşlem başarısız: " . $e->getMessage();

            if ($logo_path && file_exists(__DIR__ . '/' . $logo_path)) {
                unlink(__DIR__ . '/' . $logo_path);
            }
        }
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
                            <a class="nav-link" href="/admin_panel.php">
                                <i class="bi bi-house"></i> Dashboard
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
                            <!-- Actions -->
                            <div class="col-sm-6 col-12 text-sm-end">
                                <div class="mx-n1">
                                    <button id="create-company" class="btn btn-primary">
                                        <i class="bi bi-plus pe-2"></i>
                                        Firma Ekle
                                    </button>
                                </div>
                            </div>



                            <!-- Popup -->
                            <div id="company-popup" class="popup">
                                <div class="popup-content">
                                    <h2>Firma Oluştur</h2>
                                    <form id="company-form" action="admin_panel.php" method="post" enctype="multipart/form-data">
                                        <label for="company-name">Firma Adı:</label>
                                        <input type="text" id="company-name" name="company_name" required>

                                        <label for="company-logo">Logo:</label>
                                        <input type="file" id="company-logo" name="company_logo" accept="image/*">
                                        <label>
                                            <input type="checkbox" id="create-new-user" name="create_new_user">
                                            Yeni kullanıcı ekle
                                        </label>

                                        <label for="admin-select" id="admin-label">Admin Kullanıcı:</label>
                                        <select id="admin-select" name="admin_id">
                                            <?php foreach ($availableUsers as $user): ?>
                                                <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <div id="new-user-fields" style="display:none; margin-top:10px;">
                                            <label for="new-user-name">Yeni Kullanıcı Adı:</label>
                                            <input type="text" id="new-user-name" name="new_user_name">

                                            <label for="new-user-email">Yeni Kullanıcı Email:</label>
                                            <input type="email" id="new-user-email" name="new_user_email">

                                            <label for="new-user-password">Yeni Kullanıcı Parola:</label>
                                            <input type="password" id="new-user-password" name="new_user_password">
                                        </div>

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
                                        <th scope="col">Firma Adı</th>
                                        <th scope="col">Tarih</th>
                                        <th scope="col">Yönetici</th>
                                        <th scope="col">Email</th>
                                        <th scope="col"></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($companies)): ?>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($company['logo_path'])): ?>
                                                        <img alt="..." src="<?php echo htmlspecialchars($company['logo_path']); ?>" class="avatar avatar-sm rounded-circle me-2">
                                                        <a class="text-heading font-semibold" href="#"><?php echo htmlspecialchars($company['company_name']); ?></a>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($company['created_at']); ?></td>
                                                <td><a class="text-heading font-semibold" href="#"><?php echo htmlspecialchars($company['admin_name']); ?></a></td>
                                                <td><?php echo htmlspecialchars($company['admin_email']); ?></td>
                                                <td></td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-sm btn-neutral edit-admin-btn"
                                                        data-company-id="<?php echo htmlspecialchars($company['company_id']); ?>">Düzenle</a>

                                                    <form action="delete_company.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="company_id" value="<?php echo htmlspecialchars($company['company_id']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-square btn-neutral text-danger-hover">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

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
    <div id="edit-admin-popup" class="popup">
        <div class="popup-content">
            <h2>Admin Güncelle</h2>
            <form id="edit-admin-form" action="update_admin.php" method="post">
                <input type="hidden" name="company_id" id="edit-admin-company-id">

                <label>
                    <input type="checkbox" id="edit-create-new-user" name="create_new_user">
                    Yeni kullanıcı ekle
                </label>

                <label for="edit-admin-select" id="edit-admin-label">Admin Kullanıcı:</label>
                <select id="edit-admin-select" name="admin_id">
                    <?php foreach ($availableUsers as $user): ?>
                        <option value="<?php echo htmlspecialchars($user['id']); ?>">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="edit-new-user-fields" style="display:none; margin-top:10px;">
                    <label for="edit-new-user-name">Yeni Kullanıcı Adı:</label>
                    <input type="text" id="edit-new-user-name" name="new_user_name">

                    <label for="edit-new-user-email">Yeni Kullanıcı Email:</label>
                    <input type="email" id="edit-new-user-email" name="new_user_email">

                    <label for="edit-new-user-password">Yeni Kullanıcı Parola:</label>
                    <input type="password" id="edit-new-user-password" name="new_user_password">
                </div>

                <div class="popup-buttons">
                    <button type="submit">Kaydet</button>
                    <button type="button" id="close-edit-admin-popup">Kapat</button>
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
        document.querySelectorAll('.edit-admin-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                document.getElementById('edit-admin-company-id').value = this.dataset.companyId;

                document.getElementById('edit-admin-popup').style.display = 'flex';
            });
        });

        document.getElementById('close-edit-admin-popup').addEventListener('click', function() {
            document.getElementById('edit-admin-popup').style.display = 'none';
        });

        document.getElementById('edit-admin-popup').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>

    <script>
        const editCreateUserCheckbox = document.getElementById('edit-create-new-user');
        const editNewUserFields = document.getElementById('edit-new-user-fields');
        const editAdminSelect = document.getElementById('edit-admin-select');
        const editAdminLabel = document.getElementById('edit-admin-label');

        editCreateUserCheckbox.addEventListener('change', function() {
            if (this.checked) {
                editNewUserFields.style.display = 'block';
                editAdminSelect.style.display = 'none';
                editAdminLabel.style.display = 'none';
            } else {
                editNewUserFields.style.display = 'none';
                editAdminSelect.style.display = 'block';
                editAdminLabel.style.display = 'block';
            }
        });
    </script>
</body>

</html>