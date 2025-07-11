<?php
session_start();
include('../routes/db_conn.php'); // Menyertakan koneksi database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menangkap data yang dikirimkan dari form
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Hash password untuk keamanan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Query untuk mengecek apakah username sudah ada di database
    $query = "SELECT * FROM kasir WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    // Jika username sudah ada
    if (mysqli_num_rows($result) > 0) {
        $error_message = "Username sudah terdaftar!";
    } else {
        // Query untuk memasukkan data baru ke dalam database
        $query = "INSERT INTO kasir (username, password, phone, role) VALUES ('$username', '$hashed_password', '$phone', 'user')";
        
        if (mysqli_query($conn, $query)) {
            // Jika berhasil, alihkan ke halaman login
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Gagal melakukan registrasi!";
        }
    }
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
    <title>Register - Ayula Store</title>
    <link rel="shortcut icon" type="image/x-icon" href="../src/img/smallest-ayula.png" />
    <link rel="stylesheet" href="../bootstrap/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../bootstrap/assets/css/style.css" />
</head>
<body class="account-page">
    <div class="main-wrapper">
        <div class="account-content">
            <div class="login-wrapper">
                <div class="login-content">
                    <div class="login-userset">
                        <div class="login-userheading">
                            <h3>Create an Account</h3>
                            <h4>Continue where you left off</h4>
                        </div>
                        <form method="POST" action="register.php">
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
                                <label>Phone number</label>
                                <div class="form-addons">
                                    <input type="text" name="phone" placeholder="Enter your phone number" required />
                                    <img src="../bootstrap/assets/img/icons/telephone.png" alt="img" />
                                </div>
                            </div>
                            <?php if (isset($error_message)) { ?>
                                <div class="error-message">
                                    <p style="color: red;"><?php echo $error_message; ?></p>
                                </div>
                            <?php } ?>
                            <div class="form-login">
                                <button type="submit" class="btn btn-login">Sign Up</button>
                            </div>
                            <div class="signinform text-center">
                                <h4>Already a user? <a href="index.php" class="hover-a">Sign In</a></h4>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="login-img">
                    <img src="../bootstrap/assets/img/login.jpg" alt="img" />
                </div>
            </div>
        </div>
    </div>
    <script src="../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../bootstrap/assets/js/feather.min.js"></script>
    <script src="../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../bootstrap/assets/js/script.js"></script>
</body>
</html>
