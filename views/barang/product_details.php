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

$product_id = $_GET['id']; // Fetching product ID from URL parameter

// SQL query to fetch product details
$sql = "SELECT * FROM barang WHERE id_barang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $product_id); // Bind product_id as integer
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo "Product not found.";
}
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

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/owlcarousel/owl.carousel.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/style.css">
    
    <!-- Add JsBarcode library -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <style>
        .barcode-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .barcode-label {
            display: block;
            font-size: 14px;
            margin-top: 5px;
            font-weight: bold;
        }
        .barcode-print-btn {
            margin-top: 10px;
            display: inline-block;
            background-color: #ff9f43;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .barcode-print-btn:hover {
            background-color: #ff8a1e;
            color: white;
        }
        .barcode-print-btn i {
            margin-right: 5px;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .barcode-print-section, .barcode-print-section * {
                visibility: visible;
            }
            .barcode-print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .barcode-print-btn {
                display: none;
            }
        }
    </style>
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
                                <li><a href="/ayula-store/views/barang/productlist.php" class="active">Daftar Barang</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Barang</a></li>
                                
                            </ul>
                        </li>
                        <li >
                            <a href="/ayula-store/views/barang/topsis_restock_view.php"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    Analisa Barang</span>
                            </a>    
                        </li>
                        
                        <!-- Other menu items -->
                         <li class="submenu">
              <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/users1.svg" alt="img" /><span>
                  Pengguna</span>
                <span class="menu-arrow"></span></a>
              <ul>
                <?php if ($userRole == 'admin') { ?>
                  <li><a href="/ayula-store/views/users/add-user.php">Pengguna Baru</a></li>
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
                    <h4>Detail barang</h4>
                    <h6></h6>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Barcode Section -->
                            <div class="barcode-container barcode-print-section">
                                <svg id="barcode"></svg>
                                <span class="barcode-label"><?php echo isset($product['kode_barang']) ? $product['kode_barang'] : 'No Product Code'; ?></span>
                                <a href="javascript:void(0);" class="barcode-print-btn" onclick="printBarcode()">
                                    <i class="fa fa-print"></i> Print Barcode
                                </a>
                            </div>
                            
                            <div class="productdetails">
                                <ul class="product-bar">
                                    <li>
                                        <h4>barang</h4>
                                        <h6><?php echo isset($product['nama_barang']) ? $product['nama_barang'] : ''; ?></h6>
                                    </li>
                                    <li>
                                        <h4>kode barang</h4>
                                        <h6><?php echo isset($product['kode_barang']) ? $product['kode_barang'] : ''; ?></h6>
                                    </li>
                                    <li>
                                        <h4>Stok</h4>
                                        <h6><?php echo isset($product['stok']) ? $product['stok'] : '0'; ?></h6>
                                    </li>
                                    <li>
                                        <h4>Harga</h4>
                                        <h6>Rp <?php echo isset($product['harga']) ? number_format($product['harga'], 0, ',', '.') : '0'; ?></h6>
                                    </li>
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="slider-product-details">
                                <div class="owl-carousel owl-theme product-slide">
                                    <div class="slider-product">
                                        <?php if (isset($product['image']) && !empty($product['image'])): ?>
                                            <img src="../uploads/img-barang/<?php echo $product['image']; ?>" alt="Product Image">
                                            <h4><?php echo $product['image']; ?></h4>
                                        <?php else: ?>
                                            <img src="/ayula-store/bootstrap/assets/img/product/noimage.png" alt="No Image">
                                            <h4>No Image Available</h4>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>


    <script src="/ayula-store/bootstrap/assets/js/jquery-3.6.0.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/js/feather.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/js/jquery.slimscroll.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/js/bootstrap.bundle.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/plugins/owlcarousel/owl.carousel.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/plugins/select2/js/select2.min.js"></script>

    <script src="/ayula-store/bootstrap/assets/js/script.js"></script>
    
    <script>
        // Generate barcode
        $(document).ready(function() {
            <?php if (isset($product['kode_barang']) && !empty($product['kode_barang'])): ?>
                JsBarcode("#barcode", "<?php echo $product['kode_barang']; ?>", {
                    format: "CODE128",
                    width: 2,
                    height: 70,
                    displayValue: false,
                    margin: 10,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
            <?php else: ?>
                JsBarcode("#barcode", "BRG000", {
                    format: "CODE128",
                    width: 2,
                    height: 70,
                    displayValue: false,
                    margin: 10,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
            <?php endif; ?>
        });
        
        // Function to print barcode
        function printBarcode() {
            window.print();
        }
    </script>
</body>

</html>