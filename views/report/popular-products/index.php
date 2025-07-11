<?php

// session_start();
// Include the configuration file with database functions and user permissions
include('popular-products-config.php');

// Assuming the user role is stored in session after login
$userRole = getUserRole();
$username = getUsername();

// Get appropriate date presets based on user role
$isAdmin = isAdmin();
$datePresets = getDatePresets($isAdmin);

// Handle reset request
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    // If reset is requested, redirect to same page with default dates
    header("Location: index.php");
    exit;
}

// If user is not admin, limit historical data access
if (!$isAdmin && empty($_GET['preset']) && empty($_GET['start_date'])) {
    // Default non-admin users to current month if no dates specified
    $_GET['preset'] = 'this_month';
}

// Check if preset is selected
$activePreset = isset($_GET['preset']) && !empty($_GET['preset']) ? $_GET['preset'] : '';

// Apply date ranges
$startDate = getStartDate($datePresets, $activePreset);
$endDate = getEndDate($datePresets, $activePreset);

// Ensure start date is not after end date
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Default limit for top products
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if ($limit < 5) $limit = 5;
if ($limit > 100) $limit = 100;

// Default sort type (quantity or revenue)
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'quantity';
if (!in_array($sortBy, ['quantity', 'revenue'])) {
    $sortBy = 'quantity';
}

// Get the popular products based on user role
$queryData = getPopularProducts($startDate, $endDate, $limit, $sortBy, $isAdmin);
$result = $queryData['result'];
$summary = $queryData['summary'];
$categories = $queryData['categories'];

// Handle PDF export request if admin
if (isset($_POST['export_pdf']) || (isset($_GET['export']) && $_GET['export'] == 'pdf')) {
    exportToPDF($startDate, $endDate, $limit, $sortBy, $isAdmin);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="POS - Bootstrap Admin Template">
    <meta name="keywords" content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects">
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <title>Ayula Store POS - Produk Terlaris</title>

    <link rel="shortcut icon" type="image/x-icon" href="../../../src/img/smallest-ayula.png">


    <link rel="stylesheet" href="../../../bootstrap/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/css/animate.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../bootstrap/assets/css/style.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../salesreport.css">
    <link rel="stylesheet" href="popular-products.css">
    <!-- Chart.js for visualizations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
</head>

<body class="<?php echo $isAdmin ? 'admin' : 'employee'; ?>">
    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memuat data produk terlaris...</div>
    </div>

    <div id="global-loader">
        <div class="whirly-loader"></div>
    </div>


    <!-- Header (similar to sales report) -->
    <div class="main-wrapper">
        <div class="header">
            <div class="header-left active">
                <a href="/ayula-store/views/dashboard/" class="logo">
                    <img src="../../../src/img/logoayula.png" alt="" />
                </a>
                <a href="/ayula-store/views/dashboard/" class="logo-small">
                    <img src="../../../src/img/smallest-ayula.png" alt="" />
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
                        <span class="user-img"><img src="../../../src/img/userprofile.png" alt="">
                            <span class="status online"></span></span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilename">
                            <div class="profileset">
                                <span class="user-img"><img src="../../../src/img/userprofile.png" alt="">
                                    <span class="status online"></span></span>
                                <div class="profilesets">
                                    <h6><?php echo $isAdmin ? 'Admin' : 'Karyawan'; ?></h6>
                                    <h5><?php echo htmlspecialchars($username); ?></h5>
                                </div>
                            </div>
                            <hr class="m-0" />
                            <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                                <img src="../../../src/img/warning.png" class="me-2" alt="img" /> Laporkan Masalah
                            </a>
                            <hr class="m-0" />
                            <a class="dropdown-item logout pb-0" href="../../../views/logout.php"><img
                                    src="../../../bootstrap/assets/img/icons/log-out.svg"
                                    class="me-2"
                                    alt="img" />Keluar</a>
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
                            src="../../bootstrap/assets/img/icons/log-out.svg"
                            class="me-2"
                            alt="img" />Keluar</a>
                </div>
            </div>
        </div>
        <!-- Sidebar (similar to sales report) -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li>
                            <a href="/ayula-store/views/dashboard/"><img src="../../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                                    Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="/ayula-store/views/transaction/"><img src="../../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    POS</span></a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
                                    Produk</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/ayula-store/views/barang/productlist.php">Daftar Produk</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Produk</a></li>
                                <li><a href="categorylist.html">Daftar Kategori</a></li>
                                <li><a href="addcategory.html">Tambah Kategori</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../../bootstrap/assets/img/icons/purchase1.svg" alt="img" /><span>
                                    Pembelian</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="purchaselist.html">Daftar Pembelian</a></li>
                                <li><a href="addpurchase.html">Tambah Pembelian</a></li>
                                <li><a href="importpurchase.html">Import Pembelian</a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../../bootstrap/assets/img/icons/time.svg" alt="img" /><span>
                                    Laporan</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li>
                                    <a href="purchaseorderreport.html">Laporan Order Pembelian</a>
                                </li>
                                <li><a href="inventoryreport.html">Laporan Inventaris</a></li>
                                <li><a href="/ayula-store/views/report/sales-report/">Laporan Penjualan</a></li>
                                <?php if ($userRole == 'admin') { ?>
                                    <li><a href="/ayula-store/views/report/popular-products/">Produk Terlaris</a></li>
                                <?php } ?>
                                <li><a href="invoicereport.html">Laporan Faktur</a></li>
                                <li><a href="purchasereport.html">Laporan Pembelian</a></li>
                                <li><a href="supplierreport.html">Laporan Pemasok</a></li>
                                <li><a href="customerreport.html">Laporan Pelanggan</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../../bootstrap/assets/img/icons/users1.svg" alt="img" /><span>
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
                        <h4>Laporan Produk Terlaris</h4>
                        <h6>Lihat dan analisis produk paling populer</h6>
                        <?php if (!$isAdmin): ?>
                            <div class="alert alert-info mt-2 employee-warning">
                                <small><i class="fa fa-info-circle me-1"></i> Anda melihat laporan ini dengan akses karyawan. Beberapa data mungkin dibatasi.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dashboard stat widgets -->
                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12 d-flex">
                        <div class="dash-widget flex-fill">
                            <div class="dash-widgetimg">
                                <span class="dash-widget-icon"><i class="fa fa-box"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5>Total <span class="counters"><?php echo number_format($summary['total_products']); ?></span></h5>
                                <h6>Produk Terjual</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12 d-flex">
                        <div class="dash-widget flex-fill">
                            <div class="dash-widgetimg">
                                <span class="dash-widget-icon"><i class="fa fa-shopping-basket"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5>Total <span class="counters"><?php echo number_format($summary['total_items_sold']); ?></span></h5>
                                <h6>Item Terjual</h6>
                            </div>
                        </div>
                    </div>
                    <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                        <div class="col-lg-4 col-sm-6 col-12 d-flex">
                            <div class="dash-widget flex-fill">
                                <div class="dash-widgetimg">
                                    <span class="dash-widget-icon"><i class="fa fa-money-bill-alt"></i></span>
                                </div>
                                <div class="dash-widgetcontent">
                                    <h5>Rp. <span class="counters"><?php echo number_format($summary['total_revenue']); ?></span></h5>
                                    <h6>Total Pendapatan</h6>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Employee placeholder for layout consistency -->
                        <div class="col-lg-4 col-sm-6 col-12 d-flex">
                            <div class="dash-widget flex-fill">
                                <div class="dash-widgetimg">
                                    <span class="dash-widget-icon"><i class="fa fa-chart-line"></i></span>
                                </div>
                                <div class="dash-widgetcontent">
                                    <h5>Laporan Aktivitas</h5>
                                    <h6>Hubungi admin untuk detail keuangan</h6>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Enhanced Date Filter with Presets -->
                <!-- Enhanced Date Filter with Presets -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Filter Tanggal</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" id="date-filter-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="preset">Rentang Waktu:</label>
                                        <select name="preset" id="preset" class="form-control">
                                            <?php if ($isAdmin): ?>
                                                <option value="">Kustom</option>
                                            <?php endif; ?>
                                            <?php
                                            // Buat array terjemahan untuk label preset yang lebih familiar
                                            $presetLabels = [
                                                'today' => 'Hari Ini',
                                                'yesterday' => 'Kemarin',
                                                'this_week' => 'Minggu Ini',
                                                'last_week' => 'Minggu Lalu',
                                                'this_month' => 'Bulan Ini',
                                                'last_month' => 'Bulan Lalu',
                                                // 'last_90_days' => '3 Bulan Terakhir',
                                                'this_year' => 'Tahun Ini',
                                                'all_time' => 'Seluruh Waktu'
                                            ];

                                            foreach ($datePresets as $key => $preset):
                                                $label = isset($presetLabels[$key]) ? $presetLabels[$key] : $preset['label'];
                                            ?>
                                                <option value="<?php echo $key; ?>" <?php echo ($activePreset === $key) ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <?php if ($isAdmin): ?>
                                    <!-- Custom Date Inputs - Admin Only -->
                                    <div class="col-md-8">
                                        <div class="row custom-date-inputs" id="custom-date-inputs" <?php echo !empty($activePreset) ? 'style="display:none;"' : ''; ?>>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="start_date">Dari Tanggal:</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                                        value="<?php echo $startDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="end_date">Sampai Tanggal:</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                                        value="<?php echo $endDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary w-100" id="search-button">
                                                        <i class="fas fa-search"></i> Cari
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- For employees - simpler view -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="limit">Jumlah Produk:</label>
                                            <select name="limit" id="limit" class="form-control">
                                                <option value="5" <?php echo ($limit == 5) ? 'selected' : ''; ?>>Top 5</option>
                                                <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>Top 10</option>
                                                <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>Top 20</option>
                                                <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>Top 50</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary" id="search-button">
                                                <i class="fas fa-search"></i> Terapkan Filter
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <!-- Sorting options (for both admin and employee) -->
                                <div class="col-md-4 <?php echo (!$isAdmin) ? 'd-none d-md-block' : ''; ?>">
                                    <div class="form-group">
                                        <label for="sort_by">Urutkan Berdasarkan:</label>
                                        <select name="sort_by" id="sort_by" class="form-control">
                                            <option value="quantity" <?php echo ($sortBy == 'quantity') ? 'selected' : ''; ?>>Jumlah Terjual</option>
                                            <option value="revenue" <?php echo ($sortBy == 'revenue') ? 'selected' : ''; ?>>Total Pendapatan</option>
                                        </select>
                                    </div>
                                </div>

                                <?php if ($isAdmin): ?>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="limit">Jumlah Produk:</label>
                                            <select name="limit" id="limit" class="form-control">
                                                <option value="5" <?php echo ($limit == 5) ? 'selected' : ''; ?>>Top 5</option>
                                                <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>Top 10</option>
                                                <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>Top 20</option>
                                                <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>Top 50</option>
                                                <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>Top 100</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-end">
                                    <a href="?reset=1" class="btn btn-secondary me-2">
                                        <i class="fas fa-redo"></i> Atur Ulang
                                    </a>

                                    <!-- <?php if ($isAdmin || canAccessFeature('print_report')): ?>
                                        <a href="#" class="btn btn-info me-2 print-report">
                                            <i class="fas fa-print"></i> Cetak
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="btn btn-info me-2 employee-print request-only"
                                            data-action="print_attempt">
                                            <i class="fas fa-print"></i> Cetak
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($isAdmin || canAccessFeature('export_excel') || canAccessFeature('export_pdf')): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-download"></i> Ekspor
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($isAdmin || canAccessFeature('export_excel')): ?>
                                                    <li><a class="dropdown-item excel-export" href="#"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                                                <?php endif; ?>
                                                <?php if ($isAdmin || canAccessFeature('export_pdf')): ?>
                                                    <li><a class="dropdown-item pdf-export" href="#"><i class="fas fa-file-pdf me-2"></i> PDF</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <!-- Employee-limited export (with logging) -->
                                        <a href="#" class="btn btn-success request-only employee-export"
                                            data-action="export_attempt">
                                            <i class="fas fa-download"></i> Export
                                        </a>
                                    <?php endif; ?> -->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Date Range Display -->
                <div class="alert alert-info mb-4">
                    <strong>Rentang Tanggal:</strong> <?php echo date('d M Y', strtotime($startDate)); ?> sampai
                    <?php echo date('d M Y', strtotime($endDate)); ?>
                    <?php if (!empty($activePreset)): ?>
                        <span class="badge bg-primary ms-2"><?php echo $datePresets[$activePreset]['label']; ?></span>
                    <?php endif; ?>
                    <div class="small mt-1">
                        <?php
                        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                        echo "Menampilkan data untuk " . number_format($daysDiff) . " hari";
                        ?>
                    </div>
                </div>

                <!-- Category Distribution Charts (for both admin and employee) -->
                <?php if ($queryData['has_data'] && $categories && mysqli_num_rows($categories) > 0): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title">Kategori Produk Terlaris</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryPieChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title">Distribusi Penjualan per Kategori</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryBarChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Top Products Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <?php
                            echo "Top " . $limit . " Produk";
                            echo ($sortBy == 'revenue') ? " (Berdasarkan Pendapatan)" : " (Berdasarkan Kuantitas)";
                            ?>
                        </h5>
                        <div class="view-toggle btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" data-view="grid">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-view="table">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$queryData['has_data']): ?>
                            <!-- Tampilan No Data Found (Tidak ada data) -->
                            <div class="no-data-container">
                                <div class="no-data-icon">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h4 class="no-data-message">Tidak ada data penjualan produk untuk periode yang dipilih</h4>
                                <p class="no-data-help">
                                    <?php if ($activePreset == 'last_year'): ?>
                                        Tidak ada transaksi penjualan yang tercatat untuk tahun lalu (<?php echo date('Y') - 1; ?>).
                                    <?php else: ?>
                                        Coba pilih rentang tanggal atau preset tanggal yang berbeda.
                                    <?php endif; ?>
                                </p>
                                <a href="?reset=1" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Reset Filter
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Grid View (Default) - FIXED VERSION -->
                            <div class="view-content" id="grid-view">
                                <div class="top-products-grid">
                                    <?php
                                    mysqli_data_seek($result, 0); // Reset pointer
                                    $rank = 1;
                                    while ($product = mysqli_fetch_assoc($result)):
                                    ?>
                                        <div class="product-card">
                                            <div class="card h-100">
                                                <div class="rank-badge <?php echo ($rank <= 3) ? 'top-3' : ''; ?>">
                                                    <?php echo $rank++; ?>
                                                </div>
                                                <div class="top-product position-relative ">
                                                    <!-- Move badge outside card-body but still inside top-product -->
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo $product['nama_barang']; ?></h5>
                                                        <p class="card-text text-muted">Kode: <?php echo $product['kode_barang']; ?></p>
                                                        <p class="card-text"><span class="badge bg-info"><?php echo $product['kategori']; ?></span></p>
                                                        <div class="product-stats">
                                                            <div class="d-flex justify-content-between">
                                                                <span>Terjual:</span>
                                                                <span class="fw-bold"><?php echo number_format($product['total_quantity']); ?> unit</span>
                                                            </div>
                                                            <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Harga:</span>
                                                                    <span class="fw-bold">Rp. <?php echo number_format($product['harga']); ?></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Total:</span>
                                                                    <span class="fw-bold">Rp. <?php echo number_format($product['total_revenue']); ?></span>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Progress bar showing percentage of total sales -->
                                                            <div class="progress product-progress mt-2">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: <?php echo min(100, ($product['total_quantity'] / $summary['total_items_sold']) * 100); ?>%"
                                                                    aria-valuenow="<?php echo ($product['total_quantity'] / $summary['total_items_sold']) * 100; ?>"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <small class="text-muted mt-1 d-block">
                                                                <?php echo number_format(($product['total_quantity'] / $summary['total_items_sold']) * 100, 1); ?>% dari total penjualan
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Table View (Hidden by default) - FIXED VERSION -->
                            <div class="view-content" id="table-view" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped datanew">
                                        <thead>
                                            <tr>
                                                <th width="70">Rank</th>
                                                <th>Kode</th>
                                                <th>Nama Produk</th>
                                                <th>Kategori</th>
                                                <th>Jumlah Terjual</th>
                                                <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                    <th>Harga Satuan</th>
                                                    <th>Total Pendapatan</th>
                                                <?php endif; ?>
                                                <th>% dari Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            mysqli_data_seek($result, 0); // Reset pointer
                                            $rank = 1;
                                            while ($product = mysqli_fetch_assoc($result)):
                                                $percentOfTotal = ($product['total_quantity'] / $summary['total_items_sold']) * 100;
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <?php if ($rank <= 3): ?>
                                                            <span class="badge bg-warning">#<?php echo $rank++; ?></span>
                                                        <?php else: ?>
                                                            <?php echo $rank++; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $product['kode_barang']; ?></td>
                                                    <td><?php echo $product['nama_barang']; ?></td>
                                                    <td><span class="badge bg-info"><?php echo $product['kategori']; ?></span></td>
                                                    <td><?php echo number_format($product['total_quantity']); ?></td>
                                                    <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                        <td>Rp. <?php echo number_format($product['harga']); ?></td>
                                                        <td>Rp. <?php echo number_format($product['total_revenue']); ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: <?php echo min(100, $percentOfTotal); ?>%"
                                                                    aria-valuenow="<?php echo $percentOfTotal; ?>"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span><?php echo number_format($percentOfTotal, 1); ?>%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Summary Section - Admin Only -->
                <?php if ($isAdmin || canAccessFeature('view_summary')): ?>
                    <div class="row mt-4 no-print">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Rangkuman untuk Periode Terpilih</h5>
                                    <h6><?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="report-summary">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="report-summary-item">
                                                    <span>Total Produk Terjual:</span>
                                                    <strong><?php echo number_format($summary['total_products']); ?> produk</strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Total Unit Terjual:</span>
                                                    <strong><?php echo number_format($summary['total_items_sold']); ?> unit</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="report-summary-item">
                                                    <span>Rata-rata Per Hari:</span>
                                                    <strong><?php
                                                            $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                                                            $dailyAvgUnits = $daysDiff > 0 ? $summary['total_items_sold'] / $daysDiff : 0;
                                                            echo number_format($dailyAvgUnits, 1);
                                                            ?> unit/hari</strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Pendapatan Rata-rata Per Hari:</span>
                                                    <strong>Rp. <?php
                                                                $dailyAvgRevenue = $daysDiff > 0 ? $summary['total_revenue'] / $daysDiff : 0;
                                                                echo number_format($dailyAvgRevenue);
                                                                ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="report-summary-item">
                                                    <span>Total Pendapatan:</span>
                                                    <strong>Rp. <?php echo number_format($summary['total_revenue']); ?></strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Rata-rata Pendapatan Per Produk:</span>
                                                    <strong>Rp. <?php
                                                                $avgRevenuePerProduct = $summary['total_products'] > 0 ? $summary['total_revenue'] / $summary['total_products'] : 0;
                                                                echo number_format($avgRevenuePerProduct);
                                                                ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Employee-only section: Activity log -->
                <?php if (!$isAdmin): ?>
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Ringkasan Aktivitas Anda</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle me-2"></i>
                                        Untuk laporan keuangan terperinci atau untuk mengekspor data, silakan hubungi administrator Anda.
                                    </div>

                                    <div class="report-summary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="report-summary-item">
                                                    <span>Produk Dilihat:</span>
                                                    <strong><?php echo $limit; ?> produk teratas</strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Total Unit Terjual (Diproses):</span>
                                                    <strong><?php echo number_format($summary['total_items_sold']); ?> unit</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="report-summary-item">
                                                    <span>Laporan Dibuat:</span>
                                                    <strong><?php echo date('d M Y H:i'); ?></strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Level Akses:</span>
                                                    <strong>Karyawan</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Employee Permission Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="permissionModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Akses Dibatasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock" style="font-size: 3rem; color: #ff9f43; margin-bottom: 15px;"></i>
                        <h4>Fitur Dibatasi</h4>
                    </div>
                    <p>Fitur ini hanya tersedia untuk administrator dan personel yang berwenang.</p>
                    <p>Akun karyawan tidak memiliki akses ke fungsi cetak atau ekspor.</p>
                    <div class="alert alert-info mt-3" id="actionDetails">
                        <!-- Will be filled dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript libraries -->
    <script src="../../../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../../bootstrap/assets/js/feather.min.js"></script>
    <script src="../../../bootstrap/assets/js/jquery.slimscroll.min.js"></script>
    <script src="../../../bootstrap/assets/js/jquery.dataTables.min.js"></script>
    <script src="../../../bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../../bootstrap/assets/js/moment.min.js"></script>
    <script src="../../../bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
    <script src="../../../bootstrap/assets/plugins/select2/js/select2.min.js"></script>
    <script src="../../../bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="../../../bootstrap/assets/plugins/sweetalert/sweetalerts.min.js"></script>
    <script src="../../../bootstrap/assets/js/script.js"></script>

    <!-- Chart.js for visualizations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

    <!-- Initialize Charts for Category Data -->
    <script>
        // Function to initialize Chart.js charts
        function initializeCharts() {
            <?php if ($queryData['has_data'] && $categories && mysqli_num_rows($categories) > 0): ?>
                // Prepare category data for charts
                var categoryData = {
                    labels: [],
                    quantities: [],
                    revenues: [],
                    products: [],
                    colors: [
                        '#FF9F43', '#7367F0', '#00CFE8', '#28C76F', '#EA5455',
                        '#9F44D3', '#1E9FF2', '#F6416C', '#28a745', '#17a2b8',
                        '#fd7e14', '#6c757d', '#343a40', '#20c997', '#6610f2'
                    ]
                };

                <?php
                mysqli_data_seek($categories, 0); // Reset pointer
                $categoryCount = 0;
                while ($category = mysqli_fetch_assoc($categories)):
                    $categoryCount++;
                ?>
                    categoryData.labels.push('<?php echo $category['kategori']; ?>');
                    categoryData.quantities.push(<?php echo $category['total_quantity']; ?>);
                    categoryData.revenues.push(<?php echo $category['total_revenue']; ?>);
                    categoryData.products.push(<?php echo $category['unique_products']; ?>);
                <?php endwhile; ?>

                // Fill in any missing colors needed
                while (categoryData.colors.length < categoryData.labels.length) {
                    const randomColor = '#' + Math.floor(Math.random() * 16777215).toString(16);
                    categoryData.colors.push(randomColor);
                }

                // Pie Chart for category distribution
                var pieCtx = document.getElementById('categoryPieChart').getContext('2d');
                var categoryPieChart = new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: categoryData.labels,
                        datasets: [{
                            data: categoryData.quantities,
                            backgroundColor: categoryData.colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                padding: 15
                            }
                        },
                        tooltips: {
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    const category = data.labels[tooltipItem.index];
                                    const quantity = data.datasets[0].data[tooltipItem.index];
                                    const totalQuantity = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = ((quantity / totalQuantity) * 100).toFixed(1);

                                    return `${category}: ${quantity} unit (${percentage}%)`;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribusi Unit Terjual per Kategori'
                        }
                    }
                });

                // Bar Chart for quantity comparison
                var barCtx = document.getElementById('categoryBarChart').getContext('2d');
                var categoryBarChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryData.labels,
                        datasets: [{
                            label: 'Unit Terjual',
                            data: categoryData.quantities,
                            backgroundColor: categoryData.colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        },
                        legend: {
                            display: false
                        }
                    }
                });
            <?php endif; ?>
        }
    </script>

    <!-- Custom JavaScript for popular products report -->
    <script src="popular-products.js"></script>
    <script>
        // Disable console logs and warnings
        if (window.location.hostname === 'localhost') {
            console.log = function() {}; // Disable console logs
            console.warn = function() {}; // Disable console warnings
            console.error = function() {}; // Disable console errors
            window.alert = function() {}; // Disable alert popups
        }
    </script>
</body>

</html>