<?php
session_start();
include('../routes/db_conn.php'); // Menyertakan koneksi database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menangkap data dari form login
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Password yang dimasukkan oleh pengguna

    // Query untuk mencari pengguna berdasarkan username
    $query = "SELECT * FROM kasir WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    // Mengecek apakah user ditemukan
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi password menggunakan password_verify()
        if (password_verify($password, $user['password'])) {
            // Jika password valid, login berhasil
            $_SESSION['user_id'] = $user['id_kasir'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Jika role adalah admin, tampilkan modal pilihan
            if ($user['role'] == 'admin') {
                // Set flag untuk menampilkan modal
                $showRoleModal = true;
            } else {
                // Jika bukan admin, redirect langsung ke dashboard
                header("Location: /ayula-store/views/dashboard/");
                exit();
            }
        } else {
            // Jika password salah
            $error_message = "Password salah!";
        }
    } else {
        // Jika username tidak ditemukan
        $error_message = "Username tidak ditemukan!";
    }
}

// Proses pilihan role dari modal
if (isset($_POST['role_choice'])) {
    if ($_POST['role_choice'] == 'gudang') {
        header("Location: /ayula-store/views/reporttt/report.php");
        exit();
    } else if ($_POST['role_choice'] == 'kasir') {
        header("Location: /ayula-store/views/dashboard/");
        exit();
    }
}

// Proses logout jika tombol close ditekan
if (isset($_POST['logout'])) {
    // Hapus semua data session
    session_unset();
    session_destroy();
    // Redirect ke halaman login
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="description" content="POS - Bootstrap Admin Template" />
    <meta name="keywords" content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects" />
    <meta name="author" content="Dreamguys - Bootstrap Admin Template" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Login - Ayula Store</title>
    <link rel="shortcut icon" type="image/x-icon" href="../src/img/smallest-ayula.png" />
    <link rel="stylesheet" href="../bootstrap/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/css/style.css" />
    <style>
        .role-btn {
            padding: 20px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 150px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 15px;
            width: 100%;
        }
        
        .role-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .role-btn i {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .gudang-btn {
            background-color: #ff9f43;
            color: white;
        }
        
        .gudang-btn:hover {
            background-color: #ffb63f;
            color: white;
        }
        
        .kasir-btn {
            background-color: #1b2850;
            color: white;
        }
        
        .kasir-btn:hover {
            background-color: #344e9c;
            color: white;
        }
        
        .modal-title {
            font-weight: 700;
            color: #333;
        }
        
        .modal-header {
            border-bottom: 2px solid #f0f0f0;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .welcome-text {
            font-size: 16px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .role-container {
            padding: 0 15px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #888;
            cursor: pointer;
            transition: color 0.3s ease;
            padding: 0;
            margin-left: auto;
        }
        
        .close-btn:hover {
            color: #ff6b6b;
        }
        
        .close-btn:focus {
            outline: none;
        }
    </style>
</head>
<body class="account-page">
    <div class="main-wrapper">
        <div class="account-content">
            <div class="login-wrapper">
                <div class="login-content">
                    <div class="login-userset">
                        <div class="login-userheading">
                            <h3>Masuk</h3>
                            <h4>Silakan masuk ke akun Anda</h4>
                        </div>
                        <form method="POST" action="index.php">
                            <div class="form-login">
                                <label>Username</label>
                                <div class="form-addons">
                                    <input type="text" name="username" placeholder="Enter your username" required />
                                    <img src="../bootstrap/assets/img/icons/users1.svg" alt="img" />
                                </div>
                            </div>
                            <div class="form-login">
                                <label>Password</label>
                                <div class="pass-group">
                                    <input type="password" name="password" class="pass-input" placeholder="Enter your password" required />
                                    <span class="fas toggle-password fa-eye-slash"></span>
                                </div>
                            </div>
                            <div class="form-login">
                                <div class="alreadyuser">
                                    <h4>
                                        <a href="forgot-password.php" class="hover-a">Lupa Password?</a>
                                    </h4>
                                </div>
                            </div>
                            <?php if (isset($error_message)) { ?>
                                <div class="error-message">
                                    <p style="color: red;"><?php echo $error_message; ?></p>
                                </div>
                            <?php } ?>
                            <div class="form-login">
                                <button type="submit" class="btn btn-login">Masuk</button>
                            </div>
                            <!-- <div class="signinform text-center">
                                <h4>Don't have an account? <a href="register.php" class="hover-a">Sign Up</a></h4>
                            </div> -->
                        </form>
                    </div>
                </div>
                <div class="login-img">
                    <img src="../bootstrap/assets/img/login.jpg" alt="img" />
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilihan Role untuk Admin -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalLabel">Pilih Akses</h5>
                    <form method="POST" action="" id="logoutForm">
                        <input type="hidden" name="logout" value="1">
                        <button type="submit" class="close-btn" title="Keluar">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                <div class="modal-body">
                    <p class="welcome-text">Selamat datang, <strong><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?></strong>! <br>Silakan pilih akses yang ingin Anda gunakan:</p>
                    <form method="POST" action="">
                        <div class="row role-container">
                            <div class="col-md-6">
                                <button type="submit" name="role_choice" value="gudang" class="role-btn gudang-btn">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Gudang</span>
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="role_choice" value="kasir" class="role-btn kasir-btn">
                                    <i class="fas fa-cash-register"></i>
                                    <span>Kasir</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../bootstrap/assets/js/feather.min.js"></script>
    <script src="../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../bootstrap/assets/js/script.js"></script>

    <?php if (isset($showRoleModal) && $showRoleModal): ?>
    <script>
        $(document).ready(function() {
            $('#roleModal').modal('show');
        });
    </script>
    <?php endif; ?>
</body>
</html>