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

// Get today's date in MySQL format
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$startOfWeek = date('Y-m-d', strtotime('this week monday'));
$endOfWeek = date('Y-m-d', strtotime('this week sunday'));

// Function to get employee-specific dashboard metrics
function getEmployeeDashboardData($conn) {
    $today = date('Y-m-d');
    $data = array();
    
    // Today's transactions count
    $query = "SELECT COUNT(*) as count FROM transaksi WHERE DATE(tanggal) = '$today'";
    $result = $conn->query($query);
    $data['today_transactions'] = ($result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;
    
    // Today's items sold
    $query = "SELECT SUM(total_item) as count FROM transaksi WHERE DATE(tanggal) = '$today'";
    $result = $conn->query($query);
    $data['today_items'] = ($result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;
    if ($data['today_items'] === NULL) $data['today_items'] = 0;
    
    // This week's transactions
    $startOfWeek = date('Y-m-d', strtotime('this week monday'));
    $query = "SELECT COUNT(*) as count FROM transaksi WHERE tanggal BETWEEN '$startOfWeek' AND '$today'";
    $result = $conn->query($query);
    $data['week_transactions'] = ($result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;
    
    // Total products in inventory
    $query = "SELECT COUNT(*) as count FROM barang";
    $result = $conn->query($query);
    $data['total_products'] = ($result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;
    
    // Count low stock items (stok <= 10)
    $query = "SELECT COUNT(*) as count FROM barang WHERE stok <= 10";
    $result = $conn->query($query);
    $data['low_stock_count'] = ($result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;
    
    return $data;
}

// Function to get recent transactions
function getRecentTransactions($conn, $limit = 5) {
    $query = "SELECT t.kode_transaksi, t.tanggal, t.total_item, t.status
              FROM transaksi t
              ORDER BY t.tanggal DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $transactions = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    return $transactions;
}

// Function to get popular products - FIXED to ensure it gets the most recent data
function getPopularProducts($conn, $limit = 10) {
    // Use current date to ensure we get the latest data
    $today = date('Y-m-d');
    $startOfWeek = date('Y-m-d', strtotime('this week monday'));
    
    // Modified query to join with detail_transaksi directly on id_transaksi
    $query = "SELECT b.kode_barang, b.nama_barang, SUM(dt.jumlah) as total_quantity
              FROM detail_transaksi dt
              JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
              JOIN barang b ON dt.id_barang = b.id_barang
              WHERE t.tanggal BETWEEN '$startOfWeek' AND NOW()
              GROUP BY b.id_barang
              ORDER BY total_quantity DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $products = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get low stock items
function getLowStockItems($conn, $threshold = 10, $limit = 5) {
    $query = "SELECT kode_barang, nama_barang, stok
              FROM barang
              WHERE stok <= $threshold
              ORDER BY stok ASC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $lowStockItems = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $lowStockItems[] = $row;
        }
    }
    
    return $lowStockItems;
}

// Get dashboard data
$dashboardData = getEmployeeDashboardData($conn);
$recentTransactions = getRecentTransactions($conn);
$popularProducts = getPopularProducts($conn);
$lowStockItems = getLowStockItems($conn);

// Get user activity count (simplified)
$userActivityQuery = "SELECT COUNT(*) as count FROM transaksi WHERE tanggal > DATE_SUB(NOW(), INTERVAL 30 DAY)";
$userActivityResult = $conn->query($userActivityQuery);
$userActivity = ($userActivityResult->num_rows > 0) ? $userActivityResult->fetch_assoc()['count'] : 0;

// Recent products
$recentProductsQuery = "SELECT kode_barang, nama_barang, harga FROM barang ORDER BY created_at DESC LIMIT 5";
$recentProductsResult = $conn->query($recentProductsQuery);
$recentProducts = array();
if ($recentProductsResult->num_rows > 0) {
    while($row = $recentProductsResult->fetch_assoc()) {
        $recentProducts[] = $row;
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
  <meta name="description" content="POS - Bootstrap Admin Template" />
  <meta name="keywords"
    content="admin, estimates, bootstrap, business, corporate, creative, management, minimal, modern,  html5, responsive" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Ayula Store - Dashboard</title>

  <link rel="shortcut icon" type="image/x-icon" href="../../src/img/smallest-ayula.png" />

  <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../../bootstrap/assets/css/animate.css" />
  <link rel="stylesheet" href="../../bootstrap/assets/css/dataTables.bootstrap4.min.css" />

  <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
  <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/all.min.css" />

  <link rel="stylesheet" href="../../bootstrap/assets/css/style.css" />
  
  <style>
    .dash-count {
      border-radius: 10px;
      padding: 15px;
      transition: all 0.3s ease;
    }
    .dash-count:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .product-card {
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    .product-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .transaction-item {
      padding: 12px;
      border-bottom: 1px solid #f0f0f0;
      transition: all 0.2s ease;
    }
    .transaction-item:hover {
      background-color: #f9f9f9;
    }
    .activity-list .activity-item {
      position: relative;
      padding-bottom: 16px;
      padding-left: 30px;
      border-left: 2px solid #e9ecef;
    }
    .activity-list .activity-item:before {
      content: '';
      position: absolute;
      left: -7px;
      top: 0;
      background-color: #7367f0;
      width: 12px;
      height: 12px;
      border-radius: 50%;
    }
    .low-stock-alert {
      border-left: 4px solid #ff9f43;
    }
    .activity-date {
      font-size: 12px;
      color: #6c757d;
    }
    .metric-subtitle {
      font-size: 13px;
      color: #6c757d;
    }
    .welcome-message {
      background: linear-gradient(to right, #1b2850, #344e9c);
      color: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
    }
    .welcome-message h4 {
      font-weight: 600;
    }
    
    /* Custom 20% width column for 5 products in a row */
    .col-md-20p {
      -ms-flex: 0 0 20%;
      flex: 0 0 20%;
      max-width: 20%;
    }
    
    @media (max-width: 768px) {
      .col-md-20p {
        -ms-flex: 0 0 50%;
        flex: 0 0 50%;
        max-width: 50%;
      }
    }
    
    @media (max-width: 576px) {
      .col-md-20p {
        -ms-flex: 0 0 100%;
        flex: 0 0 100%;
        max-width: 100%;
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
              <a href="/ayula-store/views/dashboard/" class="active"><img
                  src="../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                  Dashboard</span>
              </a>
            </li>
            <li>
              <a href="/ayula-store/views/transaction/"><img src="../../bootstrap/assets/img/icons/sales1.svg"
                  alt="img" /><span>
                  POS</span></a>
            </li>
            <li class="submenu">
              <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
                  Produk</span>
                <span class="menu-arrow"></span></a>
              <ul>
                <li><a href="/ayula-store/views/barang/productlist.php">Daftar Produk</a></li>
                <!-- <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Produk</a></li>
                <li><a href="categorylist.html">Daftar Kategori</a></li>
                <li><a href="addcategory.html">Tambah Kategori</a></li> -->
              </ul>
            </li>
            <!-- <li class="submenu">
              <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/purchase1.svg" alt="img" /><span>
                  Pembelian</span>
                <span class="menu-arrow"></span></a>
              <ul>
                <li><a href="purchaselist.html">Daftar Pembelian</a></li>
                <li><a href="addpurchase.html">Tambah Pembelian</a></li>
                <li><a href="importpurchase.html">Import Pembelian</a></li>
              </ul>
            </li> -->

            <li class="submenu">
              <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/time.svg" alt="img" /><span>
                  Laporan</span>
                <span class="menu-arrow"></span></a>
              <ul>
                <li>
                  <!-- <a href="purchaseorderreport.html">Laporan Order Pembelian</a>
                </li>
                <li><a href="inventoryreport.html">Laporan Inventaris</a></li> -->
                <li><a href="/ayula-store/views/report/sales-report/">Laporan Penjualan</a></li>
                <?php if ($userRole == 'admin') { ?>
                  <li><a href="/ayula-store/views/report/popular-products/">Produk Terlaris</a></li>
                <?php } ?>
                <!-- <li><a href="invoicereport.html">Laporan Faktur</a></li>
                <li><a href="purchasereport.html">Laporan Pembelian</a></li>
                <li><a href="supplierreport.html">Laporan Pemasok</a></li>
                <li><a href="customerreport.html">Laporan Pelanggan</a></li> -->
              </ul>
            </li>
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
        <!-- Welcome message -->
        <div class="welcome-message">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h4>Selamat Datang, <?php echo htmlspecialchars($displayName); ?>!</h4>
              <p class="mb-0">Ringkasan aktivitas toko hari ini, <?php echo date('d F Y'); ?></p>
            </div>
            <div class="col-md-4 text-end">
              <i class="fas fa-store fa-3x"></i>
            </div>
          </div>
        </div>

        <!-- Main metrics -->
        <div class="row">
          <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="dash-count das1 flex-fill">
              <div class="dash-counts">
                <h4><?php echo number_format($dashboardData['today_transactions']); ?></h4>
                <h5>Transaksi Hari Ini</h5>
                <p class="metric-subtitle mb-0">
                  <i class="fas fa-calendar-day me-1"></i> <?php echo date('d M Y'); ?>
                </p>
              </div>
              <div class="dash-imgs">
                <i data-feather="shopping-cart"></i>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="dash-count flex-fill">
              <div class="dash-counts">
                <h4><?php echo number_format($dashboardData['today_items']); ?></h4>
                <h5>Item Terjual Hari Ini</h5>
                <p class="metric-subtitle mb-0">
                  <i class="fas fa-box me-1"></i> Total unit terjual
                </p>
              </div>
              <div class="dash-imgs">
                <i data-feather="package"></i>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="dash-count das2 flex-fill">
              <div class="dash-counts">
                <h4><?php echo number_format($dashboardData['week_transactions']); ?></h4>
                <h5>Transaksi Minggu Ini</h5>
                <p class="metric-subtitle mb-0">
                  <i class="fas fa-calendar-week me-1"></i> <?php echo date('d M', strtotime($startOfWeek)) . ' - ' . date('d M', strtotime($endOfWeek)); ?>
                </p>
              </div>
              <div class="dash-imgs">
                <i data-feather="file-text"></i>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="dash-count das3 flex-fill">
              <div class="dash-counts">
                <h4><?php echo number_format($dashboardData['low_stock_count']); ?></h4>
                <h5>Produk Stok Menipis</h5>
                <p class="metric-subtitle mb-0">
                  <i class="fas fa-exclamation-triangle me-1"></i> Perlu perhatian
                </p>
              </div>
              <div class="dash-imgs">
                <i data-feather="alert-triangle"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Cards -->
        <div class="row">
          <!-- Recent Transactions -->
          <div class="col-lg-6 col-sm-12 col-12 d-flex">
            <div class="card flex-fill">
              <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                <a href="/ayula-store/views/report/sales-report/" class="btn btn-sm btn-primary">Lihat Semua</a>
              </div>
              <div class="card-body">
                <?php if (count($recentTransactions) > 0): ?>
                  <div class="transaction-list">
                    <?php foreach($recentTransactions as $transaction): ?>
                      <div class="transaction-item d-flex justify-content-between align-items-center">
                        <div>
                          <h6 class="mb-1"><?php echo $transaction['kode_transaksi']; ?></h6>
                          <p class="mb-0 text-muted"><?php echo $transaction['total_item']; ?> item</p>
                        </div>
                        <div class="text-end">
                          <span class="badge bg-success"><?php echo $transaction['status']; ?></span>
                          <p class="mb-0 text-muted"><?php echo date('d M, H:i', strtotime($transaction['tanggal'])); ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h6>Belum ada transaksi hari ini</h6>
                    <p class="text-muted">Transaksi baru akan muncul di sini</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Low Stock Items -->
          <div class="col-lg-6 col-sm-12 col-12 d-flex">
            <div class="card flex-fill">
              <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Stok Menipis</h5>
                <a href="/ayula-store/views/barang/productlist.php" class="btn btn-sm btn-primary">Lihat Semua</a>
              </div>
              <div class="card-body">
                <?php if (count($lowStockItems) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Kode</th>
                          <th>Nama Produk</th>
                          <th>Stok</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach($lowStockItems as $item): ?>
                          <tr>
                            <td><?php echo $item['kode_barang']; ?></td>
                            <td><?php echo $item['nama_barang']; ?></td>
                            <td><?php echo $item['stok']; ?></td>
                            <td>
                              <?php if ($item['stok'] <= 5): ?>
                                <span class="badge bg-danger">Kritis</span>
                              <?php else: ?>
                                <span class="badge bg-warning">Menipis</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h6>Semua Produk Memiliki Stok Memadai</h6>
                    <p class="text-muted">Tidak ada produk yang stoknya menipis saat ini</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Popular Products - Full Width -->
          <div class="col-12 d-flex">
            <div class="card flex-fill">
              <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">10 Produk Terlaris Minggu Ini</h5>
                <?php if ($userRole == 'admin'): ?>
                  <a href="/ayula-store/views/report/popular-products/" class="btn btn-sm btn-primary">Detail Lengkap</a>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <?php if (count($popularProducts) > 0): ?>
                  <div class="row">
                    <?php 
                    // Split the products into two rows for better display
                    $totalProducts = count($popularProducts);
                    $firstRowCount = min(5, $totalProducts);
                    $secondRowCount = $totalProducts - $firstRowCount;
                    ?>
                    
                    <!-- First row of products (top 5) -->
                    <?php for($i = 0; $i < $firstRowCount; $i++): 
                      $product = $popularProducts[$i];
                    ?>
                      <div class="col-md-20p mb-3">
                        <div class="product-card p-3 border rounded h-100">
                          <div class="d-flex justify-content-between align-items-start">
                            <div>
                              <h6 class="mb-1"><?php echo $product['nama_barang']; ?></h6>
                              <p class="mb-1 text-muted small"><?php echo $product['kode_barang']; ?></p>
                            </div>
                            <span class="badge bg-primary">#<?php echo $i + 1; ?></span>
                          </div>
                          <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                              <span>Total Terjual:</span>
                              <span class="fw-bold"><?php echo number_format($product['total_quantity']); ?> unit</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                              <div class="progress-bar bg-success" role="progressbar" 
                                  style="width: <?php echo min(100, ($product['total_quantity'] / ($popularProducts[0]['total_quantity'] ?: 1)) * 100); ?>%">
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endfor; ?>
                    
                    <!-- Second row of products (6-10) -->
                    <?php if($secondRowCount > 0): ?>
                      <?php for($i = $firstRowCount; $i < $totalProducts; $i++): 
                        $product = $popularProducts[$i];
                      ?>
                        <div class="col-md-20p mb-3">
                          <div class="product-card p-3 border rounded h-100">
                            <div class="d-flex justify-content-between align-items-start">
                              <div>
                                <h6 class="mb-1"><?php echo $product['nama_barang']; ?></h6>
                                <p class="mb-1 text-muted small"><?php echo $product['kode_barang']; ?></p>
                              </div>
                              <span class="badge bg-primary">#<?php echo $i + 1; ?></span>
                            </div>
                            <div class="mt-3">
                              <div class="d-flex justify-content-between align-items-center">
                                <span>Total Terjual:</span>
                                <span class="fw-bold"><?php echo number_format($product['total_quantity']); ?> unit</span>
                              </div>
                              <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                    style="width: <?php echo min(100, ($product['total_quantity'] / ($popularProducts[0]['total_quantity'] ?: 1)) * 100); ?>%">
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endfor; ?>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h6>Belum Ada Data Penjualan</h6>
                    <p class="text-muted">Produk terlaris akan ditampilkan saat ada penjualan</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
  <script src="../../bootstrap/assets/js/feather.min.js"></script>
  <script src="../../bootstrap/assets/js/jquery.slimscroll.min.js"></script>
  <script src="../../bootstrap/assets/js/jquery.dataTables.min.js"></script>
  <script src="../../bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>
  <script src="../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
  <script src="../../bootstrap/assets/plugins/apexchart/apexcharts.min.js"></script>
  <script src="../../bootstrap/assets/plugins/apexchart/chart-data.js"></script>
  <script src="../../bootstrap/assets/js/script.js"></script>
  
  <script>
    // Hide loader when page is fully loaded
    $(window).on('load', function() {
      setTimeout(function() {
        $("#global-loader").fadeOut('slow');
      }, 100);
    });
    
    // Initialize any DataTables
    $(document).ready(function() {
      if ($.fn.DataTable.isDataTable('.datatable') === false) {
        $('.datatable').DataTable({
          language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            paginate: {
              first: "Pertama",
              last: "Terakhir",
              next: "Selanjutnya",
              previous: "Sebelumnya"
            }
          }
        });
      }
    });
  </script>
</body>

</html>