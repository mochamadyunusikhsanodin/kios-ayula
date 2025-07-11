<?php
// Include configuration and database queries
require_once 'salesreport-config.php';

$username = getUsername();
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
    <title>Ayula Store POS - Laporan Penjualan</title>

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
    <link rel="stylesheet" href="salesreport.css">
    <!-- Tambahkan kode CSS ini di bagian head -->
    <style>
        /* Loading overlay styles */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #7367f0;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* No data message styles */
        .no-data-container {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .no-data-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #6c757d;
        }

        .no-data-message {
            font-size: 1.25rem;
            font-weight: 500;
            margin-bottom: 15px;
            color: #343a40;
        }

        .no-data-help {
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="<?php echo $isAdmin ? 'admin' : 'employee'; ?>">
    <div id="global-loader">
        <div class="whirly-loader"> </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memuat data...</div>
    </div>

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
                    <a class="dropdown-item logout pb-0" href="../../../views/logout.php"><img
                            src="../../../bootstrap/assets/img/icons/log-out.svg"
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
                                <li><a href="/ayula-store/views/report/sales-report/" class="active">Laporan Penjualan</a></li>
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
                        <h4>Laporan Penjualan</h4>
                        <h6>Kelola Laporan Penjualan Anda</h6>
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
                                <span class="dash-widget-icon "><i class="fa fa-shopping-cart"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5>Total <span class="counters"><?php echo number_format($totals['total_transactions']); ?></span></h5>
                                <h6>Transaksi</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12 d-flex">
                        <div class="dash-widget flex-fill">
                            <div class="dash-widgetimg">
                                <span class="dash-widget-icon"><i class="fa fa-cubes"></i></span>
                            </div>
                            <div class="dash-widgetcontent">
                                <h5>Total <span class="counters"><?php echo number_format($totals['total_items']); ?></span></h5>
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
                                    <h5>Rp. <span class="counters"><?php echo number_format($totals['grand_total']); ?></span></h5>
                                    <h6>Total Penjualan</h6>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Employee placeholders for layout consistency -->
                        <div class="col-lg-6 col-sm-12 col-12 d-flex">
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
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Filter Tanggal</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" id="date-filter-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="preset">Rentang Tanggal:</label>
                                        <select name="preset" id="preset" class="form-control">
                                            <?php if ($isAdmin): ?>
                                                <option value="">Kustom</option>
                                            <?php endif; ?>
                                            <?php
                                            // Buat array terjemahan untuk label preset
                                            $presetLabels = [
                                                'today' => 'Hari Ini',
                                                'yesterday' => 'Kemarin',
                                                'this_week' => 'Minggu Ini',
                                                'last_week' => 'Minggu Lalu',
                                                'this_month' => 'Bulan Ini',
                                                'last_month' => 'Bulan Lalu',
                                                // 'last_90_days' => '90 Hari Terakhir',
                                                'all_time' => 'Sepanjang Waktu'
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
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="start_date">Dari Tanggal:</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                                        value="<?php echo $startDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="end_date">Sampai Tanggal:</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                                        value="<?php echo $endDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
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
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary" id="search-button">
                                                <i class="fas fa-search"></i> Terapkan Filter
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-end">
                                    <a href="?reset=1" class="btn btn-secondary me-2">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>

                                    <?php if ($isAdmin || canAccessFeature('print_report')): ?>
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
                                            <i class="fas fa-download"></i> Ekspor
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Date Range Display -->
                <div class="alert alert-info mb-4">
                    <strong>Rentang Tanggal Saat Ini:</strong> <?php echo date('d M Y', strtotime($startDate)); ?> sampai
                    <?php echo date('d M Y', strtotime($endDate)); ?>
                    <?php if (!empty($activePreset)):
                        $presetLabels = [
                            'today' => 'Hari Ini',
                            'yesterday' => 'Kemarin',
                            'this_week' => 'Minggu Ini',
                            'last_week' => 'Minggu Lalu',
                            'this_month' => 'Bulan Ini',
                            'last_30_days' => '30 Hari Terakhir',
                            'last_month' => 'Bulan Lalu',
                            'last_90_days' => '90 Hari Terakhir',
                            'last_quarter' => 'Kuartal Terakhir',
                            'last_year' => 'Tahun Lalu',
                            'all_time' => 'Sepanjang Waktu'
                        ];
                        $label = isset($presetLabels[$activePreset]) ? $presetLabels[$activePreset] : $datePresets[$activePreset]['label'];
                    ?>
                        <span class="badge bg-primary ms-2"><?php echo $label; ?></span>
                    <?php endif; ?>
                    <div class="small mt-1">
                        <?php
                        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                        echo "Menampilkan data untuk " . number_format($daysDiff) . " hari";
                        ?>
                    </div>
                </div>

                <!-- Sales Report Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Transaksi Penjualan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($queryData['has_data']) && $queryData['has_data'] === false): ?>
                            <!-- Tampilan No Data Found (Tidak ada data) -->
                            <div class="no-data-container">
                                <div class="no-data-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h4 class="no-data-message">Tidak ada transaksi ditemukan untuk periode yang dipilih</h4>
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
                            <!-- Tampilan Table Normal (Ada data) -->
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>ID Transaksi</th>
                                            <th>Tanggal</th>
                                            <th>Item</th>
                                            <th>Produk</th>
                                            <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                <th>Total</th>
                                            <?php endif; ?>
                                            <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                <th>Tunai</th>
                                                <th>Kembalian</th>
                                            <?php else: ?>
                                                <th>Status</th>
                                            <?php endif; ?>
                                            <th class="text-end no-print">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                            <?php mysqli_data_seek($result, 0); // Reset pointer to beginning of result set 
                                            ?>
                                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo $row['kode_transaksi']; ?></td>
                                                    <td><?php echo date('d M Y H:i', strtotime($row['tanggal'])); ?></td>
                                                    <td><?php echo $row['total_item']; ?></td>
                                                    <td><?php echo $row['total_products']; ?></td>

                                                    <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                        <!-- Admin sees all financial details -->
                                                        <td>Rp. <?php echo number_format($row['total']); ?></td>
                                                    <?php endif; ?>

                                                    <?php if ($isAdmin || canAccessFeature('view_financial')): ?>
                                                        <td>Rp. <?php echo number_format($row['cash_amount']); ?></td>
                                                        <td>Rp. <?php echo number_format($row['change_amount']); ?></td>
                                                    <?php else: ?>
                                                        <!-- Employee sees limited details -->
                                                        <td><span class="badge bg-success">Selesai</span></td>
                                                    <?php endif; ?>

                                                    <td class="text-end no-print">
                                                        <a class="btn btn-sm btn-secondary" href="../../transaction/transaction_success.php?id=<?php echo $row['id_transaksi']; ?>">
                                                            <i class="fas fa-eye"></i> Lihat
                                                        </a>
                                                        <?php if ($isAdmin || canAccessFeature('print_report')): ?>
                                                            <a class="btn btn-sm btn-primary" href="#" onclick="printReceipt(<?php echo $row['id_transaksi']; ?>); return false;">
                                                                <i class="fas fa-print"></i> Cetak
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">Tidak ada transaksi ditemukan untuk periode yang dipilih</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Period Summary Report - Admin Only -->
                <?php if ($isAdmin || canAccessFeature('view_summary')): ?>
                    <div class="row mt-4 no-print">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Ringkasan untuk Periode yang Dipilih</h5>
                                    <h6><?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="report-summary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="report-summary-item">
                                                    <span>Total Transaksi:</span>
                                                    <strong><?php echo number_format($totals['total_transactions']); ?></strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Total Item Terjual:</span>
                                                    <strong><?php echo number_format($totals['total_items']); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="report-summary-item">
                                                    <span>Total:</span>
                                                    <strong>Rp. <?php echo number_format($totals['grand_total']); ?></strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Rata-rata Harian:</span>
                                                    <strong>Rp. <?php
                                                                $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                                                                $dailyAvg = $daysDiff > 0 ? $totals['grand_total'] / $daysDiff : 0;
                                                                echo number_format($dailyAvg);
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
                                        Untuk laporan keuangan yang lebih rinci atau untuk mengekspor data, silakan hubungi administrator Anda.
                                    </div>

                                    <div class="report-summary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="report-summary-item">
                                                    <span>Transaksi Dilihat:</span>
                                                    <strong><?php echo $totals['total_transactions']; ?></strong>
                                                </div>
                                                <div class="report-summary-item">
                                                    <span>Item Diproses:</span>
                                                    <strong><?php echo $totals['total_items']; ?></strong>
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
                        <!-- Akan diisi secara dinamis -->
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

    <!-- Custom JavaScript -->
    <script src="salesreport.js"></script>
    <script>
        // Fungsi untuk menyembunyikan loading overlay saat halaman dimuat
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading-overlay').style.display = 'none';
            }, 500); // 500ms delay untuk memastikan semuanya dimuat
        });

        // Jika halaman terlalu lama dimuat, sembunyikan loading overlay setelah 10 detik
        setTimeout(function() {
            document.getElementById('loading-overlay').style.display = 'none';
        }, 10000);

        // Tambahan untuk penanganan filter dengan preset
        document.addEventListener('DOMContentLoaded', function() {
            const presetSelect = document.getElementById('preset');
            const customDateInputs = document.getElementById('custom-date-inputs');

            if (presetSelect && customDateInputs) {
                presetSelect.addEventListener('change', function() {
                    if (this.value === '') {
                        customDateInputs.style.display = 'flex';
                    } else {
                        // Tampilkan loading overlay saat memilih preset
                        document.getElementById('loading-overlay').style.display = 'flex';
                        // Tambahkan timeout kecil untuk memastikan loading screen muncul
                        setTimeout(function() {
                            document.getElementById('date-filter-form').submit();
                        }, 100);
                    }
                });
            }
        });
    </script>
    <script>
        // Nonaktifkan console logs dan warnings
        if (window.location.hostname === 'localhost') {
            console.log = function() {}; // Nonaktifkan console logs
            console.warn = function() {}; // Nonaktifkan console warnings
            console.error = function() {}; // Nonaktifkan console errors
            window.alert = function() {}; // Nonaktifkan alert popups
        }
    </script>
</body>

</html>