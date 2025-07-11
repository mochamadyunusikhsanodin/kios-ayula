<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Ambil informasi user yang sedang login
$userRole = $_SESSION['role']; // 'user' atau 'admin'
$username = $_SESSION['username']; // Menambahkan username dari session

// Jika username adalah root, tampilkan nama yang lebih presentable
$displayName = ($username === 'root') ? 'Admin' : $username;

// Cek apakah session 'user_id' ada, yang berarti pengguna sudah login
if (!isset($_SESSION['user_id'])) {
  // Jika session tidak ada, arahkan pengguna ke halaman login
  header("Location: /ayula-store/index.php");
  exit();
}

// Database connection
include('../../routes/db_conn.php');


// Function to generate new product code
function generateProductCode($conn) {
    // Query the database for the highest product code
    $query = "SELECT kode_barang FROM barang WHERE kode_barang LIKE 'BRG%' ORDER BY kode_barang DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        // Extract existing code
        $row = $result->fetch_assoc();
        $lastCode = $row['kode_barang'];
        
        // Extract the numeric part and increment
        $numericPart = intval(substr($lastCode, 3)); // Extract numbers after 'BRG'
        $nextNumeric = $numericPart + 1;
        
        // Format with leading zeros (e.g., BRG001, BRG002, etc.)
        $newCode = 'BRG' . str_pad($nextNumeric, 3, '0', STR_PAD_LEFT);
    } else {
        // If no existing codes, start with BRG001
        $newCode = 'BRG001';
    }
    
    return $newCode;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate new product code
    $kode_barang = generateProductCode($conn);
    
    // Get data from form
    $nama_barang = isset($_POST['nama_barang']) ? $_POST['nama_barang'] : '';
    $id_jenis = isset($_POST['id_jenis']) ? $_POST['id_jenis'] : '';
    $stok = isset($_POST['stok']) ? $_POST['stok'] : 0;
    $harga = isset($_POST['harga']) ? $_POST['harga'] : '';

    // Check if image file was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];

        // Validate image
        $image_name = basename($image['name']);
        $target_dir = "../uploads/img-barang/"; // Target folder to store images
        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if uploaded file is an image
        if (in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Move file to target folder
            if (!move_uploaded_file($image['tmp_name'], $target_file)) {
                echo "Error: Failed to upload image!";
                exit;
            }
        } else {
            echo "Error: Only images (JPG, JPEG, PNG, GIF) are allowed!";
            exit;
        }
    } else {
        $image_name = ''; // If no image was uploaded, set image name to empty
    }

    // Validate id_jenis
    $query_jenis = "SELECT id_jenis FROM jenis_barang WHERE id_jenis = ?";
    $stmt_jenis = $conn->prepare($query_jenis);
    $stmt_jenis->bind_param("i", $id_jenis);
    $stmt_jenis->execute();
    $stmt_jenis->store_result();

    if ($stmt_jenis->num_rows == 0 && $id_jenis != '') {
        echo "Error: Category not found!";
        exit;
    }

    // Validate stock and price must be numbers
    if (!preg_match('/^\d+$/', $stok) || !preg_match('/^\d+$/', $harga)) {
        die("<script>alert('Stock and price must contain only numbers!'); window.history.back();</script>");
    }

    // Convert to integers for safety
    $stok = intval($stok);
    $harga = intval($harga);

    // Insert data into database (now including kode_barang)
    $sql = "INSERT INTO barang (kode_barang, nama_barang, id_jenis, stok, harga, image) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisis", $kode_barang, $nama_barang, $id_jenis, $stok, $harga, $image_name);

    if ($stmt->execute()) {
    // Redirect with success parameter
    header("Location: productlist.php?success_add=1");
    exit();
}}

// Fetch categories for the dropdown
$query_categories = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis";
$result_categories = $conn->query($query_categories);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="POS - Bootstrap Admin Template">
    <meta name="keywords"
        content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects">
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <title>Dreams Pos admin template</title>

    <link rel="shortcut icon" type="image/x-icon" href="/ayula-store/bootstrap/assets/img/favicon.jpg">

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
      <div class="whirly-loader"></div>
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
            <span class="user-img"><img src="../../src/img/userprofile.png" alt="">
              <span class="status online"></span></span>
          </a>
          <div class="dropdown-menu menu-drop-user">
            <div class="profilename">
              <div class="profileset">
                <span class="user-img"><img src="../../src/img/userprofile.png" alt="">
                  <span class="status online"></span></span>
                <div class="profilesets">
                  <h6><?php echo $userRole == 'admin' ? 'Admin' : 'Karyawan'; ?></h6>
                  <h5><?php echo htmlspecialchars($displayName); ?></h5>
                </div>
              </div>
              <hr class="m-0" />
              <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                <img src="../../src/img/warning.png" class="me-2" alt="img" /> Laporkan Masalah
              </a>
              <hr class="m-0" />
              <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                  src="../../bootstrap/assets/img/icons/log-out.svg" class="me-2" alt="img" />Keluar</a>
            </div>
          </div>
        </li>
      </ul>

      <div class="dropdown mobile-user-menu">
        <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
          aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item" href="/ayula-store/views/report-issue/">
            <i class="fa fa-cog me-2"></i> Laporkan Masalah
          </a>
          <hr class="m-0" />
          <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
              src="../../bootstrap/assets/img/icons/log-out.svg" class="me-2" alt="img" />Keluar</a>
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
                                <li><a href="/ayula-store/views/barang/productlist.php" >Daftar Barang</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php" class="active">Tambah Barang</a></li>
                                
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
               
                  <li><a href="/ayula-store/views/users/add-user.php">Pengguna Baru</a></li>
               
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
                        <h4>Tambah Barang</h4>
                    </div>
                </div>
                <form action="addproduct.php" method="POST" enctype="multipart/form-data">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Nama Barang</label>
                                        <input type="text" name="nama_barang" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Kategori</label>
                                        <select class="select" name="id_jenis" required>
                                            <option value="">Pilih Kategori</option>
                                            <?php
                                            // Loop through all categories and create option tags
                                            if ($result_categories && $result_categories->num_rows > 0) {
                                                while($category = $result_categories->fetch_assoc()) {
                                                    echo '<option value="' . $category['id_jenis'] . '">' . $category['nama_jenis'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Harga</label>
                                        <input type="text" name="harga" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Stok</label>
                                        <input type="text" name="stok" required>
                                    </div>
                                </div>
                                
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label> Gambar Barang</label>
                                        <div class="image-upload">
                                            <input type="file" name="image" id="image" accept="image/*">
                                            <div class="image-uploads">
                                                <img src="/ayula-store/bootstrap/assets/img/icons/upload.svg" alt="img">
                                                <h4>Pilih Gambar</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit">Tambah</button>
                                    <a href="productlist.php" class="btn btn-cancel">Batal</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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
    <script src="/ayula-store/bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/plugins/sweetalert/sweetalerts.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/script.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        function onlyNumbers(event) {
            let charCode = event.which ? event.which : event.keyCode;
            if (charCode < 48 || charCode > 57) {
                event.preventDefault();
                Swal.fire({
                    icon: "error",
                    title: "Input Tidak Valid!",
                    text: "Hanya angka yang diperbolehkan.",
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }

        let stokInput = document.querySelector("input[name='stok']");
        let hargaInput = document.querySelector("input[name='harga']");

        stokInput.addEventListener("keypress", onlyNumbers);
        hargaInput.addEventListener("keypress", onlyNumbers);

        function validateOnBlur(input) {
            input.addEventListener("blur", function () {
                if (!/^\d+$/.test(input.value)) {
                    Swal.fire({
                        icon: "warning",
                        title: "Input Tidak Valid!",
                        text: "Harap masukkan angka saja.",
                        showConfirmButton: false,
                        timer: 2000
                    });
                    input.value = "";
                }
            });
        }

        validateOnBlur(stokInput);
        validateOnBlur(hargaInput);
    });
    </script>
</body>
</html>