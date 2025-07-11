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
// Include database connection
require_once '../../routes/db_conn.php';

// Initialize filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'date_range';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('m');
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

// Function untuk get report data
function getReportData($conn, $start_date, $end_date, $filter_type, $filter_month, $filter_year, $product_id, $category_id) {
    // Base query with joins to get product and category information
    $sql = "SELECT r.id_report, r.tanggal, r.jumlah, r.harga, r.image as receipt_image, 
                   b.id_barang, b.nama_barang, b.kode_barang, b.image as product_image,
                   jb.nama_jenis
            FROM report r
            JOIN barang b ON r.id_barang = b.id_barang
            JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Apply data filters based on tipe filter
    if ($filter_type == 'date_range') {
        $sql .= " AND DATE(r.tanggal) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    } elseif ($filter_type == 'month') {
        $sql .= " AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
        $params[] = $filter_month;
        $params[] = $filter_year;
        $types .= "ss";
    } elseif ($filter_type == 'year') {
        $sql .= " AND YEAR(r.tanggal) = ?";
        $params[] = $filter_year;
        $types .= "s";
    }
    
    // Apply barang filter if selected
    if (!empty($product_id)) {
        $sql .= " AND b.id_barang = ?";
        $params[] = $product_id;
        $types .= "i";
    }
    
    // Apply kategori filter if selected
    if (!empty($category_id)) {
        $sql .= " AND b.id_jenis = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // Order by date (terbaru first)
    $sql .= " ORDER BY r.tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    $stmt->close();
    
    return $reports;
}

// Function to get all products for filter dropdown
function getAllProducts($conn) {
    $sql = "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang";
    $result = $conn->query($sql);
    
    $products = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Function to get all categories for filter dropdown
function getAllCategories($conn) {
    $sql = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Function to calculate summary statistics
function calculateSummary($reports) {
    $summary = [
        'total_items' => 0,
        'total_amount' => 0,
        'total_reports' => count($reports),
        'categories' => []
    ];
    
    $category_totals = [];
    
    foreach ($reports as $report) {
        $summary['total_items'] += $report['jumlah'];
        $total_value = $report['jumlah'] * $report['harga'];
        $summary['total_amount'] += $total_value;
        
        // Track by category
        $category = $report['nama_jenis'];
        if (!isset($category_totals[$category])) {
            $category_totals[$category] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        
        $category_totals[$category]['count'] += $report['jumlah'];
        $category_totals[$category]['amount'] += $total_value;
    }
    
    // Format category data for charts
    foreach ($category_totals as $category => $data) {
        $summary['categories'][] = [
            'name' => $category,
            'count' => $data['count'],
            'amount' => $data['amount']
        ];
    }
    
    return $summary;
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data based on filters
$reports = getReportData($conn, $start_date, $end_date, $filter_type, $filter_month, $filter_year, $product_id, $category_id);
$products = getAllProducts($conn);
$categories = getAllCategories($conn);
$summary = calculateSummary($reports);

// Close the database connection
$conn->close();

// Format numbers for display
function formatCurrency($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta name="description" content="Ayula Store - Reports" />
    <meta name="keywords" content="admin, reports, inventory, sales" />
    <meta name="author" content="Ayula Store" />
    <meta name="robots" content="noindex, nofollow" />
    

    <link rel="shortcut icon" type="image/x-icon" href="../../bootstrap/assets/img/favicon.jpg" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/animate.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/select2/css/select2.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/style.css" />
    
    <!-- Date range picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    
    <style>
        .dash-widget {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .dash-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .dash-widgetimg {
            margin-bottom: 15px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .dash-widgetimg span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .dash-widgetcontent h5 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .dash-widgetcontent h6 {
            font-size: 14px;
            color: #888;
        }
        .filter-section {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #5682d6 !important;
            color: white !important;
            font-weight: 600;
        }
        .receipt-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .receipt-thumbnail:hover {
            transform: scale(1.1);
        }
        #receipt-modal .modal-img {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }
        .export-buttons {
            display: flex;
            gap: 8px;
        }
        .export-buttons .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        /* Added Sidebar CSS */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1040;
            display: none;
        }
        
        .sidebar-overlay.opened {
            display: block;
        }
        
        /* Mini sidebar styles */
        .mini-sidebar .header-left .logo img {
            max-width: 30px;
        }
        
        .mini-sidebar .header-left .logo {
            display: none;
        }
        
        .mini-sidebar .header-left .logo-small {
            display: block;
        }
        
        .mini-sidebar .sidebar {
            width: 78px;
        }
        
        .mini-sidebar .sidebar .sidebar-menu ul > li > a span {
            display: none;
            transition: all 0.2s ease;
        }
        
        .mini-sidebar .sidebar .sidebar-menu ul > li > a i {
            font-size: 24px;
            margin-right: 0;
        }
        
        .mini-sidebar .page-wrapper {
            margin-left: 78px;
        }
        
        /* Mobile menu styles */
        @media (max-width: 991.98px) {
            .main-wrapper.slide-nav {
                padding-top: 0;
            }
            .main-wrapper.slide-nav .sidebar {
                margin-left: 0;
            }
            .slide-nav .sidebar {
                z-index: 1044;
                margin-left: 0 !important;
            }
            .sidebar {
                margin-left: -225px;
                width: 225px;
                position: fixed;
                transition: all 0.4s ease;
                z-index: 1041;
            }
            .page-wrapper {
                margin-left: 0;
                padding-left: 0;
                padding-right: 0;
                transition: all 0.4s ease;
            }
        }
    </style>
</head>

<body>
    <!-- Added Sidebar Overlay Div -->
    
    
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
                        <li class="active">
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
                  
                    <div class="page-btn">
                        <div class="export-buttons">
                            <button type="button" class="btn btn-primary" id="print-btn">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-danger" id="export-pdf">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-success" id="export-excel">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="dash-widget">
                            <div class="dash-widgetimg bg-primary text-white">
                                <span><i class="fas fa-clipboard-list"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5><?= $summary['total_reports'] ?></h5>
                                <h6>Total Laporan</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="dash-widget dash1">
                            <div class="dash-widgetimg bg-success text-white">
                                <span><i class="fas fa-box"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5><?= $summary['total_items'] ?></h5>
                                <h6>Total Items</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="dash-widget dash2">
                            <div class="dash-widgetimg bg-warning text-white">
                                <span><i class="fas fa-money-bill-wave"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5><?= formatCurrency($summary['total_amount']) ?></h5>
                                <h6>Total Nilai</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="dash-widget dash3">
                            <div class="dash-widgetimg bg-info text-white">
                                <span><i class="fas fa-tags"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5><?= count($summary['categories']) ?></h5>
                                <h6>Kategori</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card filter-section mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filter Laporan</h5>
                        <form id="report-filter-form" method="get" action="">
                            <div class="row">
                                <!-- Filter Type Selection -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tipe Filter</label>
                                        <select class="select" id="filter-type" name="filter_type">
                                            <option value="date_range" <?= $filter_type == 'date_range' ? 'selected' : '' ?>>Rentang Tanggal</option>
                                            <option value="month" <?= $filter_type == 'month' ? 'selected' : '' ?>>Bulanan</option>
                                            <option value="year" <?= $filter_type == 'year' ? 'selected' : '' ?>>Tahunan</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Date Range Filter (shown by default) -->
                                <div class="col-md-4 filter-option" id="date-range-filter" <?= $filter_type != 'date_range' ? 'style="display:none;"' : '' ?>>
                                    <div class="form-group">
                                        <label>Rentang Tanggal</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="daterange" name="daterange" value="<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>" />
                                            <input type="hidden" name="start_date" id="start_date" value="<?= $start_date ?>">
                                            <input type="hidden" name="end_date" id="end_date" value="<?= $end_date ?>">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Month Filter (hidden by default) -->
                                <div class="col-md-2 filter-option" id="month-filter" <?= $filter_type != 'month' ? 'style="display:none;"' : '' ?>>
                                    <div class="form-group">
                                        <label>Bulan</label>
                                        <select class="select" name="filter_month">
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?= $i ?>" <?= $filter_month == $i ? 'selected' : '' ?>>
                                                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Year Filter (for both month and year filters) -->
                                <div class="col-md-2 filter-option" id="year-filter" <?= $filter_type == 'date_range' ? 'style="display:none;"' : '' ?>>
                                    <div class="form-group">
                                        <label>Tahun</label>
                                        <select class="select" name="filter_year">
                                            <?php 
                                            $currentYear = date('Y');
                                            for ($i = $currentYear; $i >= $currentYear - 5; $i--): 
                                            ?>
                                                <option value="<?= $i ?>" <?= $filter_year == $i ? 'selected' : '' ?>>
                                                    <?= $i ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Product Filter -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Produk</label>
                                        <select class="select" name="product_id">
                                            <option value="">Semua Produk</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id_barang'] ?>" <?= $product_id == $product['id_barang'] ? 'selected' : '' ?>>
                                                    <?= $product['nama_barang'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Category Filter -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Kategori</label>
                                        <select class="select" name="category_id">
                                            <option value="">Semua Kategori</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id_jenis'] ?>" <?= $category_id == $category['id_jenis'] ? 'selected' : '' ?>>
                                                    <?= $category['nama_jenis'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Apply Filter Button -->
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary" style="margin-top: 28px;">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-lg-7 col-sm-12 col-12 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Distribusi Kategori</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-sm-12 col-12 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Nilai Per Kategori</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="valueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Data Laporan</h4>
                            <div class="search-set">
                                <div class="search-input">
                                    <a class="btn btn-searchset"></a>
                                    <input type="text" id="search-input" placeholder="Cari laporan...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive dataview">
                            <table class="table datatable" id="report-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tanggal</th>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Total</th>
                                        <th>Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data laporan untuk periode ini.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><?= $report['id_report'] ?></td>
                                                <td><?= date('d M Y', strtotime($report['tanggal'])) ?></td>
                                                <td>
                                                    <div class="productimgname">
                                                        
                                                        <a href="javascript:void(0);"><?= $report['nama_barang'] ?></a>
                                                    </div>
                                                </td>
                                                <td><?= $report['nama_jenis'] ?></td>
                                                <td><?= $report['jumlah'] ?></td>
                                                <td><?= formatCurrency($report['harga']) ?></td>
                                                <td><?= formatCurrency($report['jumlah'] * $report['harga']) ?></td>
                                                <td>
                                                     <!-- Tombol untuk membuka modal dan menampilkan gambar -->
            <?php if (!empty($report['receipt_image'])) : ?>
                <button class="btn btn-info open-receipt-modal" 
                        data-receipt-image="<?php echo $report['receipt_image']; ?>" 
                        data-bs-toggle="modal" 
                        data-bs-target="#receipt-modal">
                    Lihat Nota
                </button>
            <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Receipt Image Modal -->
<div class="modal fade" id="receipt-modal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Bukti Nota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <!-- Menampilkan gambar nota berdasarkan data dari database -->
                <img src="" class="modal-img" id="receipt-img" alt="Receipt Image">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="download-receipt">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


    <!-- Print Template (hidden) -->
    <div id="print-template" style="display:none;">
        <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>Laporan Inventaris Ayula Store</h2>
                <p>Periode: <span id="print-period"></span></p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div style="flex: 1; padding: 10px; background: #f9f9f9; border-radius: 5px; margin-right: 10px;">
                    <h3>Total Laporan</h3>
                    <p style="font-size: 24px; margin: 0;"><?= $summary['total_reports'] ?></p>
                </div>
                <div style="flex: 1; padding: 10px; background: #f9f9f9; border-radius: 5px; margin-right: 10px;">
                    <h3>Total Items</h3>
                    <p style="font-size: 24px; margin: 0;"><?= $summary['total_items'] ?></p>
                </div>
                <div style="flex: 1; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                    <h3>Total Nilai</h3>
                    <p style="font-size: 24px; margin: 0;"><?= formatCurrency($summary['total_amount']) ?></p>
                </div>
            </div>
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">ID</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Tanggal</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Produk</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Kategori</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Jumlah</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Harga Satuan</th>
                        <th style="padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;">Total</th>
                    </tr>
                </thead>
                <tbody id="print-table-body">
                    <!-- Will be filled by JavaScript -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold; padding: 8px; border: 1px solid #ddd;">Grand Total:</td>
                        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;"><?= $summary['total_items'] ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"></td>
                        <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;"><?= formatCurrency($summary['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 30px; text-align: right;">
                <p>Dicetak pada: <?= date('d M Y H:i:s') ?></p>
            </div>
        </div>
    </div>
     
    <!-- Scripts -->
    <script src="../../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../bootstrap/assets/js/feather.min.js"></script>
    <script src="../../bootstrap/assets/js/jquery.slimscroll.min.js"></script>
    <script src="../../bootstrap/assets/js/jquery.dataTables.min.js"></script>
    <script src="../../bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../bootstrap/assets/plugins/select2/js/select2.min.js"></script>
    <script src="../../bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="../../bootstrap/assets/plugins/sweetalert/sweetalerts.min.js"></script>
    <script src="../../bootstrap/assets/js/script.js"></script>
    
    <!-- Date Range Picker -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    
    <!-- SheetJS (xlsx) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize feather icons
            feather.replace();
            
            // Initialize Select2
            $('.select').select2();
            
            // Initialize DataTable
            var reportTable = $('#report-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                "pageLength": 10,
                "language": {
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    },
                    "search": "Search:",
                    "emptyTable": "Tidak ada data laporan yang tersedia"
                }
            });
            
            // Search functionality
            $('#search-input').on('keyup', function() {
                reportTable.search(this.value).draw();
            });
            
            // Filter by filter type
            $('#filter-type').on('change', function() {
                const filterType = $(this).val();
                $('.filter-option').hide();
                
                if (filterType === 'date_range') {
                    $('#date-range-filter').show();
                } else if (filterType === 'month') {
                    $('#month-filter').show();
                    $('#year-filter').show();
                } else if (filterType === 'year') {
                    $('#year-filter').show();
                }
            });
            
            // Initialize Date Range Picker
            $('#daterange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY'
                },
                ranges: {
                   'Hari Ini': [moment(), moment()],
                   'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                   '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                   'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                   'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, function(start, end, label) {
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
            });
            
            // Handle receipt image modal
            $('#receipt-modal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const imgSrc = button.data('img');
                const title = button.data('title');
                
                const modal = $(this);
                modal.find('.modal-title').text(title);
                modal.find('#receipt-img').attr('src', imgSrc);
                
                // Set download link
                $('#download-receipt').off('click').on('click', function() {
                    const a = document.createElement('a');
                    a.href = imgSrc;
                    a.download = 'receipt_' + title.replace('Nota #', '') + '.jpg';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            });
            
            // Print functionality
            $('#print-btn').on('click', function() {
                // Set period text
                let periodText = '';
                const filterType = $('#filter-type').val();
                
                if (filterType === 'date_range') {
                    periodText = $('#daterange').val();
                } else if (filterType === 'month') {
                    const month = $('select[name="filter_month"] option:selected').text();
                    const year = $('select[name="filter_year"]').val();
                    periodText = month + ' ' + year;
                } else if (filterType === 'year') {
                    periodText = 'Tahun ' + $('select[name="filter_year"]').val();
                }
                
                $('#print-period').text(periodText);
                
                // Populate table body
                const tableBody = $('#print-table-body');
                tableBody.empty();
                
                <?php foreach ($reports as $report): ?>
                tableBody.append(`
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $report['id_report'] ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= date('d M Y', strtotime($report['tanggal'])) ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $report['nama_barang'] ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $report['nama_jenis'] ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= $report['jumlah'] ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= formatCurrency($report['harga']) ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?= formatCurrency($report['jumlah'] * $report['harga']) ?></td>
                    </tr>
                `);
                <?php endforeach; ?>
                
                // Open print dialog
                const printContent = document.getElementById('print-template').innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print Report</title>
                            <style>
                                body { font-family: Arial, sans-serif; }
                                table { width: 100%; border-collapse: collapse; }
                                th, td { padding: 8px; border: 1px solid #ddd; }
                                th { background-color: #f2f2f2; }
                            </style>
                        </head>
                        <body>
                            ${printContent}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                
                setTimeout(function() {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });
            
            // Export to PDF
            $('#export-pdf').on('click', function() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                
                // Add title
                doc.setFontSize(18);
                doc.text('Laporan Inventaris Ayula Store', doc.internal.pageSize.getWidth() / 2, 15, { align: 'center' });
                
                // Add period
                let periodText = '';
                const filterType = $('#filter-type').val();
                
                if (filterType === 'date_range') {
                    periodText = 'Periode: ' + $('#daterange').val();
                } else if (filterType === 'month') {
                    const month = $('select[name="filter_month"] option:selected').text();
                    const year = $('select[name="filter_year"]').val();
                    periodText = 'Periode: ' + month + ' ' + year;
                } else if (filterType === 'year') {
                    periodText = 'Periode: Tahun ' + $('select[name="filter_year"]').val();
                }
                
                doc.setFontSize(12);
                doc.text(periodText, doc.internal.pageSize.getWidth() / 2, 22, { align: 'center' });
                
                // Add summary
                doc.setFontSize(11);
                doc.text(`Total Laporan: <?= $summary['total_reports'] ?>`, 15, 30);
                doc.text(`Total Items: <?= $summary['total_items'] ?>`, 15, 37);
                doc.text(`Total Nilai: <?= formatCurrency($summary['total_amount']) ?>`, 15, 44);
                
                // Add table
                const tableColumn = ["ID", "Tanggal", "Produk", "Kategori", "Jumlah", "Harga Satuan", "Total"];
                const tableRows = [];
                
                <?php foreach ($reports as $report): ?>
                tableRows.push([
                    <?= $report['id_report'] ?>,
                    "<?= date('d M Y', strtotime($report['tanggal'])) ?>",
                    "<?= $report['nama_barang'] ?>",
                    "<?= $report['nama_jenis'] ?>",
                    <?= $report['jumlah'] ?>,
                    "<?= formatCurrency($report['harga']) ?>",
                    "<?= formatCurrency($report['jumlah'] * $report['harga']) ?>"
                ]);
                <?php endforeach; ?>
                
                // Add a footer row for totals
                tableRows.push([
                    "", "", "", "TOTAL", 
                    "<?= $summary['total_items'] ?>", 
                    "", 
                    "<?= formatCurrency($summary['total_amount']) ?>"
                ]);
                
                doc.autoTable({
                    head: [tableColumn],
                    body: tableRows,
                    startY: 55,
                    theme: 'grid',
                    styles: {
                        fontSize: 8,
                        cellPadding: 3
                    },
                    headStyles: {
                        fillColor: [86, 130, 214],
                        textColor: [255, 255, 255],
                        fontSize: 9,
                        halign: 'center'
                    },
                    footStyles: {
                        fillColor: [240, 240, 240],
                        fontStyle: 'bold'
                    }
                });
                
                // Add date generated
                const date = new Date();
                doc.setFontSize(8);
                doc.text(`Dicetak pada: ${date.toLocaleString('id-ID')}`, 
                    doc.internal.pageSize.getWidth() - 15, 
                    doc.internal.pageSize.getHeight() - 10, 
                    { align: 'right' });
                
                // Save the PDF
                doc.save('Laporan_Inventaris_' + date.toISOString().split('T')[0] + '.pdf');
            });
            
            // Export to Excel
            $('#export-excel').on('click', function() {
                const rows = [];
                
                // Add header row
                rows.push([
                    "ID", "Tanggal", "Produk", "Kategori", "Jumlah", "Harga Satuan", "Total"
                ]);
                
                // Add data rows
                <?php foreach ($reports as $report): ?>
                rows.push([
                    <?= $report['id_report'] ?>,
                    "<?= date('d M Y', strtotime($report['tanggal'])) ?>",
                    "<?= $report['nama_barang'] ?>",
                    "<?= $report['nama_jenis'] ?>",
                    <?= $report['jumlah'] ?>,
                    "<?= str_replace('Rp ', '', formatCurrency($report['harga'])) ?>",
                    "<?= str_replace('Rp ', '', formatCurrency($report['jumlah'] * $report['harga'])) ?>"
                ]);
                <?php endforeach; ?>
                
                // Add a footer row for totals
                rows.push([
                    "", "", "", "TOTAL", 
                    "<?= $summary['total_items'] ?>", 
                    "", 
                    "<?= str_replace('Rp ', '', formatCurrency($summary['total_amount'])) ?>"
                ]);
                
                // Create workbook
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.aoa_to_sheet(rows);
                
                // Add summary data
                XLSX.utils.sheet_add_aoa(ws, [
                    ["Laporan Inventaris Ayula Store"],
                    [""],
                    ["Ringkasan:"],
                    ["Total Laporan:", "<?= $summary['total_reports'] ?>"],
                    ["Total Items:", "<?= $summary['total_items'] ?>"],
                    ["Total Nilai:", "<?= str_replace('Rp ', '', formatCurrency($summary['total_amount'])) ?>"],
                    [""]
                ], { origin: "A1" });
                
                // Set column widths
                const cols = [
                    { wch: 8 },  // ID
                    { wch: 15 }, // Tanggal
                    { wch: 30 }, // Produk
                    { wch: 15 }, // Kategori
                    { wch: 10 }, // Jumlah
                    { wch: 15 }, // Harga Satuan
                    { wch: 15 }  // Total
                ];
                ws['!cols'] = cols;
                
                // Add to workbook and save
                XLSX.utils.book_append_sheet(wb, ws, "Laporan Inventaris");
                XLSX.writeFile(wb, 'Laporan_Inventaris_' + new Date().toISOString().split('T')[0] + '.xlsx');
            });
            
            // Category Distribution Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php 
                        foreach ($summary['categories'] as $category) {
                            echo "'" . $category['name'] . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Jumlah Items',
                        data: [
                            <?php 
                            foreach ($summary['categories'] as $category) {
                                echo $category['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)',
                            'rgba(83, 102, 255, 0.7)',
                            'rgba(40, 159, 64, 0.7)',
                            'rgba(210, 199, 199, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Value Distribution Chart
            const valueCtx = document.getElementById('valueChart').getContext('2d');
            const valueChart = new Chart(valueCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        foreach ($summary['categories'] as $category) {
                            echo "'" . $category['name'] . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Nilai (Rp)',
                        data: [
                            <?php 
                            foreach ($summary['categories'] as $category) {
                                echo $category['amount'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
            
            // Sidebar Toggle Functionality
            $("#toggle_btn").on("click", function() {
                if ($("body").hasClass("mini-sidebar")) {
                    $("body").removeClass("mini-sidebar");
                    $(".subdrop + ul").slideDown();
                } else {
                    $("body").addClass("mini-sidebar");
                    $(".subdrop + ul").slideUp();
                }
                return false;
            });
            
            // Mobile Sidebar Toggle
            $("#mobile_btn").on("click", function() {
                $(".main-wrapper").toggleClass("slide-nav");
                $(".sidebar-overlay").toggleClass("opened");
                $("body").addClass("menu-opened");
                return false;
            });
            
            // Close sidebar when clicking outside
            $(".sidebar-overlay").on("click", function() {
                $(".main-wrapper").removeClass("slide-nav");
                $(".sidebar-overlay").removeClass("opened");
                $("body").removeClass("menu-opened");
            });
            
            // Set active class on submenu items
            $(".submenu li a").on("click", function() {
                $(".submenu li a").removeClass("active");
                $(this).addClass("active");
            });
        });
        
        // Disable console logs and warnings
        if (window.location.hostname === 'localhost') {
            console.log = function() {}; // Disable console logs
            console.warn = function() {}; // Disable console warnings
            console.error = function() {}; // Disable console errors
            window.alert = function() {}; // Disable alert popups
        }
        // JavaScript untuk membuka modal dan menampilkan gambar yang sesuai
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('receipt-modal'));
    
    // Event listener untuk membuka modal
    document.querySelectorAll('.open-receipt-modal').forEach(button => {
        button.addEventListener('click', function () {
            // Ambil path gambar dari data yang sesuai
            const receiptImage = this.getAttribute('data-receipt-image');
            
            // Update src pada modal
            document.getElementById('receipt-img').src = '../uploads/nota/' + receiptImage;
            
            // Tampilkan modal
            modal.show();
        });
    });
});

    </script>
</body>
</html>