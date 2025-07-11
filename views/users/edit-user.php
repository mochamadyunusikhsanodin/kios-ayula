<?php
session_start();
include('../../routes/db_conn.php');
$userRole = $_SESSION['role']; // 'user' or 'admin'
$username = $_SESSION['username']; // Menambahkan username dari session

// Check if the user ID is passed in the URL
if (isset($_GET['id'])) {
    $id_kasir = $_GET['id'];

    // Fetch user data from the database
    $sql = "SELECT id_kasir, username, phone, role FROM kasir WHERE id_kasir = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_kasir); // Bind the id to the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "Pengguna tidak ditemukan.";
        exit;
    }
}

// Update user data if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $editUsername = $_POST['username'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    // Validate form fields (optional but recommended)
    if (!empty($editUsername) && !empty($phone) && !empty($role)) {
        // Check if password is provided
        if (!empty($password)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update with new password
            $updateSql = "UPDATE kasir SET username = ?, password = ?, phone = ?, role = ? WHERE id_kasir = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssssi", $editUsername, $hashed_password, $phone, $role, $id_kasir);
        } else {
            // Update without changing password
            $updateSql = "UPDATE kasir SET username = ?, phone = ?, role = ? WHERE id_kasir = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssi", $editUsername, $phone, $role, $id_kasir);
        }
        
        $updateStmt->execute();

        // Redirect to the users list after the update
        header("Location: index.php");
        exit;
    } else {
        // Show error if any form field is empty
        echo "<script>alert('Harap isi semua kolom yang diperlukan.');</script>";
    }
}

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
    <title>Ayula Store - Ubah Pengguna</title>

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

            <div class="dropdown mobile-user-menu">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-ellipsis-v"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                        <i class="fa fa-cog me-2"></i> Laporkan Masalah
                    </a>
                    <hr class="m-0" />
                    <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                            src="../../bootstrap/assets/img/icons/log-out.svg"
                            class="me-2"
                            alt="img" />Keluar</a>
                </div>
            </div>
        </div>

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
                                    <li><a href="/ayula-store/views/users/add-user.php">Pengguna Baru</a></li>
                                <?php } ?>
                                <li><a href="/ayula-store/views/users/" class="active">Daftar Pengguna</a></li>
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
                        <h6>Edit/Perbarui Pengguna</h6>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="edit-user.php?id=<?php echo $id_kasir; ?>">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Nama Pengguna</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Telepon</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Peran</label>
                                        <select name="role" class="form-control" required>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Karyawan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Simpan</button>
                                    <a href="index.php" class="btn btn-cancel">Batal</a>
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
</body>

</html>