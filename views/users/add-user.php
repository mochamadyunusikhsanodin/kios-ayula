<?php
include('../../routes/db_conn.php');
session_start();
$userRole = $_SESSION['role'];
$username = $_SESSION['username']; // Mengambil username dari session

// Register new user data if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $newUsername = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    // Validate form fields (optional but recommended)
    if (!empty($newUsername) && !empty($password) && !empty($confirmPassword) && !empty($phone) && !empty($role)) {
        // Check if phone number contains only numbers
        if (!preg_match('/^[0-9]+$/', $phone)) {
            $showUsernameModal = true;
            $modalMessage = "Nomor telepon hanya boleh berisi angka.";
        }
        // Check if phone number length is valid (11-13 digits for Indonesian numbers)
        else if (strlen($phone) < 11 || strlen($phone) > 13) {
            $showUsernameModal = true;
            $modalMessage = "Nomor telepon harus terdiri dari 11 hingga 13 angka.";
        }
        // Check if passwords match
        else if ($password !== $confirmPassword) {
            $showUsernameModal = true;
            $modalMessage = "Password tidak sama.";
        } else {
            // Check if username already exists
            $checkUsername = "SELECT username FROM kasir WHERE username = ?";
            $checkStmt = $conn->prepare($checkUsername);
            $checkStmt->bind_param("s", $newUsername);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Set flag to show modal
                $showUsernameModal = true;
                $modalMessage = "Username sudah digunakan. Silakan pilih username lain.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the user information into the database
                $insertSql = "INSERT INTO kasir (username, password, phone, role) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("ssss", $newUsername, $hashed_password, $phone, $role);
                $insertStmt->execute();

                // Redirect to the users list after the insert
                header("Location: index.php");
                exit;
            }
        }
    } else {
        // Show error if any form field is empty
        $showUsernameModal = true;
        $modalMessage = "Harap isi semua kolom.";
    }
}

// Variable for modal display
$showUsernameModal = isset($showUsernameModal) ? $showUsernameModal : false;
$modalMessage = isset($modalMessage) ? $modalMessage : "";

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="POS - Bootstrap Admin Template">
    <meta name="keywords" content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects">
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <title>Ayula Store - Tambah Pengguna</title>

    <link rel="shortcut icon" type="image/x-icon" href="../../src/img/smallest-ayula.png">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/animate.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/style.css">
</head>

<body>
    <div id="global-loader">
        <div class="whirly-loader"> </div>
    </div>

    <div class="main-wrapper">
        <div class="header">
            <div class="header-left active">
                <a href="/ayula-store/views/dashboard/" class="logo">
                    <img src="../../src/img/logoayula.png" alt="" />
                </a>
                <a href="/ayula-store/views/dashboard/" class="logo-small">
                    <img src="../../src/img/smallest-ayula.png" alt="" />
                </a>
                <a id="toggle_btn" href="javascript:void(0);"> </a>
            </div>

            <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                <span class="bar-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </a>

            <ul class="nav user-menu">
                <li class="nav-item dropdown has-arrow main-drop">
                    <a href="javascript:void(0);" class="dropdown-toggle nav-link userset" data-bs-toggle="dropdown">
                        <span class="user-img">
                            <img src="../../src/img/userprofile.png" alt="" />
                            <span class="status online"></span>
                        </span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilename">
                            <div class="profileset">
                                <span class="user-img">
                                    <img src="../../src/img/userprofile.png" alt="" />
                                    <span class="status online"></span>
                                </span>
                                <div class="profilesets">
                                    <h6><?php echo $userRole == 'admin' ? 'Admin' : 'Karyawan'; ?></h6>
                                    <h5><?php echo htmlspecialchars($username); ?></h5>
                                </div>
                            </div>
                            <hr class="m-0" />
                            <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                                <img src="../../src/img/warning.png" class="me-2" alt="img" /> Laporkan Masalah
                            </a>
                            <hr class="m-0" />
                            <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                                    src="../../bootstrap/assets/img/icons/log-out.svg"
                                    class="me-2"
                                    alt="img" />Keluar</a>
                        </div>
                    </div>
                </li>
            </ul>

             <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li>
                            <a href="/ayula-store/views/reporttt/report.php"><img src="../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                                    Dashboard</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
                                    Barang</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/ayula-store/views/barang/productlist.php" class="active">Daftar Barang</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Barang</a></li>
                                
                            </ul>
                        </li>
                        <li >
                            <a href="/ayula-store/views/barang/topsis_restock_view.php"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    Analisa Barang</span>
                            </a>    
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/users1.svg" alt="img" /><span>
                                    Pengguna</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <?php if ($userRole == 'admin') { ?>
                                    <li><a href="/ayula-store/views/users/add-user.php" class="active">Pengguna Baru</a></li>
                                <?php } ?>
                                <li><a href="/ayula-store/views/users/">Daftar Pengguna</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Manajemen Pengguna</h4>
                        <h6>Tambah Pengguna Baru</h6>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="add-user.php">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Konfirmasi Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Telepon</label>
                                        <input type="text" name="phone" class="form-control" required 
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                                               pattern="[0-9]{11,13}" 
                                               title="Masukkan nomor telepon yang valid (11-13 angka)"
                                               minlength="11" maxlength="13">
                                        <!-- <small class="form-text text-muted">Nomor telepon harus terdiri dari 11-13 angka.</small> -->
                                    </div>
                                    <div class="form-group">
                                        <label>Peran</label>
                                        <select name="role" class="form-control" required>
                                            <option value="admin">Admin</option>
                                            <option value="user">Karyawan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Daftar</button>
                                    <a href="/ayula-store/views/users/" class="btn btn-cancel">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="/ayula-store/bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/feather.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/jquery.slimscroll.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/jquery.dataTables.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/plugins/select2/js/select2.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/script.js"></script>
    
    <!-- Modal for notifications -->
    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-circle text-warning" style="font-size: 48px;"></i>
                        <p class="mt-3"><?php echo $modalMessage; ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showUsernameModal): ?>
    <script>
        // Show the modal when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            var notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notificationModal.show();
        });
    </script>
    <?php endif; ?>
</body>

</html>