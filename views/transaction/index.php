<?php
// Include transaction functions
require_once 'configtrans.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Debug for POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data: ' . print_r($_POST, true));
}

// Process adding product to cart
if (isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Get product details
    $product = getProductById($productId);

    if ($product) {
        // Check stock availability
        if ($quantity <= $product['stok']) {
            // Check if product already in cart, update quantity if exists
            $exists = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $productId) {
                    // Ensure we don't exceed stock
                    $newQuantity = $item['quantity'] + $quantity;
                    if ($newQuantity <= $product['stok']) {
                        $_SESSION['cart'][$key]['quantity'] = $newQuantity;
                    } else {
                        $_SESSION['error_message'] = "Tidak dapat menambahkan {$quantity} unit {$product['nama_barang']}. Total melebihi stok tersedia ({$product['stok']}).";
                        header('Location: index.php');
                        exit;
                    }
                    $exists = true;
                    break;
                }
            }

            // If not exists, add to cart
            if (!$exists) {
                $_SESSION['cart'][] = [
                    'id' => $productId,
                    'name' => $product['nama_barang'],
                    'code' => $product['kode_barang'],
                    'price' => $product['harga'],
                    'quantity' => $quantity,
                    'max_stock' => $product['stok']
                ];
            }
        } else {
            // Set error message for stock limit
            $_SESSION['error_message'] = "Tidak dapat menambahkan {$quantity} unit {$product['nama_barang']}. Hanya {$product['stok']} tersedia.";
        }
    } else {
        $_SESSION['error_message'] = "Produk tidak ditemukan.";
    }

    // Redirect to avoid form resubmission
    header('Location: index.php');
    exit;
}

// Process adding multiple products to cart
if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    // Log data for debugging
    error_log('Adding multiple products. product_ids: ' . print_r($_POST['product_ids'], true));

    foreach ($_POST['product_ids'] as $productId) {
        // Validate product ID
        $productId = (int)$productId;
        if ($productId <= 0) continue;

        // Get product details
        $product = getProductById($productId);

        if ($product) {
            // Check stock availability
            if ($product['stok'] > 0) {
                // Check if product already in cart, update quantity if exists
                $exists = false;
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['id'] == $productId) {
                        // Only add if it won't exceed stock
                        if ($item['quantity'] < $product['stok']) {
                            $_SESSION['cart'][$key]['quantity'] += 1; // Default quantity is 1
                        }
                        $exists = true;
                        break;
                    }
                }

                // If not exists, add to cart
                if (!$exists) {
                    $_SESSION['cart'][] = [
                        'id' => $productId,
                        'name' => $product['nama_barang'],
                        'code' => $product['kode_barang'],
                        'price' => $product['harga'],
                        'quantity' => 1,
                        'max_stock' => $product['stok']
                    ];
                }
            } else {
                // Log out-of-stock items
                error_log("Produk {$product['nama_barang']} (ID: {$productId}) stok habis.");
            }
        }
    }

    // Redirect to avoid form resubmission
    header('Location: index.php');
    exit;
}

// Update cart item quantity
if (isset($_POST['update_cart']) && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Get product details for stock check
    $product = getProductById($productId);

    if ($product) {
        // Make sure quantity doesn't exceed stock
        if ($quantity > $product['stok']) {
            $quantity = $product['stok'];
            $_SESSION['error_message'] = "Kuantitas disesuaikan ke stok maksimum yang tersedia ({$product['stok']}).";
        }

        // Update quantity in cart
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
                $_SESSION['cart'][$key]['max_stock'] = $product['stok']; // Update max stock in case it changed
                break;
            }
        }
    }

    header('Location: index.php');
    exit;
}

// Remove item from cart
if (isset($_GET['remove_item'])) {
    $index = $_GET['remove_item'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    }

    header('Location: index.php');
    exit;
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    header('Location: index.php');
    exit;
}

// Process checkout
if (isset($_POST['checkout'])) {
    // Log untuk debugging
    error_log("Checkout process started: " . json_encode($_POST));

    $items = [];
    $total = 0;

    // Validasi keranjang
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['error_message'] = "Keranjang Anda kosong. Silakan tambahkan item ke keranjang.";
        header('Location: index.php');
        exit;
    }

    foreach ($_SESSION['cart'] as $item) {
        $items[] = [
            'product_id' => $item['id'],
            'quantity' => $item['quantity']
        ];
        $total += $item['price'] * $item['quantity'];
    }

    // Get cash and change amount
    $cashAmount = isset($_POST['cash_amount']) ? (float)$_POST['cash_amount'] : 0;
    $changeAmount = isset($_POST['change_amount']) ? (float)$_POST['change_amount'] : 0;

    // Validate cash amount
    if ($cashAmount < $total) {
        $_SESSION['error_message'] = "Jumlah tunai harus sama dengan atau lebih besar dari jumlah total.";
        header('Location: index.php');
        exit;
    }

    // Log transaction details for debugging
    error_log("Processing checkout: Items: " . count($items) . ", Total: $total, Cash: $cashAmount, Change: $changeAmount");

    // Create transaction with cash and change amounts
    $result = createTransaction($items, $total, $cashAmount, $changeAmount);

    if ($result['success']) {
        // Log success for debugging
        error_log("Transaction successful. ID: " . $result['transaction_id']);

        // Clear cart after successful checkout
        $_SESSION['cart'] = [];
        $_SESSION['last_transaction'] = $result;

        // Redirect to success page
        header('Location: transaction_success.php?id=' . $result['transaction_id']);
        exit;
    } else {
        // Log error for debugging
        error_log("Transaction failed: " . $result['message']);

        $error = $result['message'];
        $_SESSION['error_message'] = $error;
        header('Location: index.php');
        exit;
    }
}

// Get all product types
$productTypes = getProductTypes();

// Handle search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get products by selected product type or all products
$selectedType = isset($_GET['type']) ? $_GET['type'] : null;
$products = getProducts($selectedType, $searchQuery);

// Calculate cart totals
$cartItems = count($_SESSION['cart']);
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
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
    <title>Ayula Store - POS</title>

    <link rel="shortcut icon" type="image/x-icon" href="../../src/img/smallest-ayula.png" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/animate.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/owlcarousel/owl.carousel.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/owlcarousel/owl.theme.default.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/select2/css/select2.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap-datetimepicker.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/style.css" />
    <link rel="stylesheet" href="barcode-scanner.css" />

    <!-- Custom CSS for loader state -->
    <style>
        .multi-select-toolbar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #ffffff;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            display: none;
            z-index: 9999;
            justify-content: space-between;
            align-items: center;
        }

        .multi-select-toolbar.active {
            display: flex;
        }

        /* Highlight selected products */
        .productset.selected {
            border: 2px solid #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }

        /* Loading state for products */
        .tab_content.loading {
            position: relative;
        }

        .tab_content.loading:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            margin-top: -20px;
            margin-left: -20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff9f43;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 100;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Mengatur container utama keranjang */
        .card-order {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
            max-height: calc(100vh - 120px);
            overflow: hidden;
            /* Penting: mencegah scroll pada card-order secara keseluruhan */
        }

        /* Bagian header keranjang (fixed) */
        .order-list {
            position: sticky;
            top: 0;
            z-index: 101;
            background-color: #f8f9fa;
        }

        /* Bagian atas keranjang yang tidak di-scroll (header) */
        .card-order .card-body:first-of-type {
            padding-top: 10px;
            padding-bottom: 0;
            overflow: hidden;

            /* Penting: mencegah scroll pada header */
        }

        /* Area yang bisa di-scroll (daftar produk) */
        .product-table {
            max-height: calc(100vh - 420px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px;
            /* Tambahkan sedikit padding untuk scrollbar */
        }

        /* Bagian bawah keranjang yang tetap (total, payment, checkout) */
        .card-order .card-body:last-of-type {
            flex-shrink: 0;
            /* Penting: mencegah area ini mengecil */
            padding-top: 10px;
            background-color: #fff;
            position: sticky;
            bottom: 0;
            z-index: 101;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Membuat heading total items sticky tetapi dalam product-table */
        .totalitem {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 100;
            padding: 5px 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* Style untuk scrollbar agar lebih slim */
        .product-table::-webkit-scrollbar {
            width: 6px;

        }

        .product-table::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .product-table::-webkit-scrollbar-thumb {
            background: #ff9f43;
            border-radius: 3px;
        }

        .product-table::-webkit-scrollbar-thumb:hover {
            background: #ff8f33;
        }

        /* Style untuk highlight ketika item ditambahkan via barcode */
        .highlight-product {
            animation: highlight-pulse 1.5s ease;
        }

        @keyframes highlight-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        /* Style untuk notifikasi penambahan produk */
        .barcode-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 15px;
            background-color: #ff9f43;
            color: white;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .barcode-notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .barcode-scanner-container {
            background-color: #ff9f43;
        }

        /* Style untuk sticky scanner */
        .barcode-scanner-container.sticky-scanner {
            position: fixed;
            width: 66%;
            z-index: 990;
            border-radius: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 159, 67, 0.7);
        }

        /* Scanner container collapsed state */
        .barcode-scanner-container.collapsed .barcode-content {
            display: none;

        }

        .barcode-scanner-container.collapsed .barcode-toggle-btn i {
            transform: rotate(180deg);
        }

        /* Fix untuk tombol checkout */
        #checkout-form .btn-totallabel:not([disabled]) {
            background-color: #ff9f43;
            cursor: pointer;
        }

        /* Menambahkan pointer ke tombol update */
        .update-cart-btn {
            cursor: pointer;
        }

        /* Ensure the checkout button is clickable and visible */
        #checkout-form button[type="submit"] {
            width: 100%;
            border: none;
            outline: none;
        }

        /* Style untuk form kuantitas agar lebih compact */
        .cart-item-form {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
        }

        .quantity-control-container {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
        }

        /* Tombol kuantitas lebih kecil */
        .quantity-btn {
            padding: 2px 6px;
            min-width: 28px;
        }

        /* Bidang input kuantitas lebih kecil */
        .quantity-field {
            width: 40px !important;
            min-width: 40px;
            padding: 2px 4px;
            text-align: center;
        }

        /* Tombol update lebih kecil */
        .update-cart-btn {
            padding: 2px 8px;
            font-size: 0.85rem;
        }

        /* Perbaikan style product-lists agar lebih compact */
        .product-lists {
            margin-bottom: 8px;
            position: relative;
        }
    </style>
</head>

<body class="<?php echo $isAdmin ? 'admin' : 'employee'; ?>">
    <div id="global-loader">
        <div class="whirly-loader"></div>
    </div>

    <!-- Multi-select toolbar -->
    <div class="multi-select-toolbar">
        <div class="selected-count">0 produk terseleksi</div>
        <div class="toolbar-actions">
            <button type="button" id="cancel-selection" class="btn btn-light">Batal</button>
            <button type="button" id="add-selected-to-cart" class="btn btn-primary">Tambah barang ke keranjang</button>
        </div>
    </div>

    <!-- Display error messages if any -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

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
                                    <h6><?php echo $isAdmin ? 'Admin' : 'Karyawan'; ?></h6>
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
                            <a href="/ayula-store/views/dashboard/"><img src="../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                                    Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="/ayula-store/views/transaction/" class="active"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    POS</span></a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
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
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/purchase1.svg" alt="img" /><span>
                                    Pembelian</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="purchaselist.html">Daftar Pembelian</a></li>
                                <li><a href="addpurchase.html">Tambah Pembelian</a></li>
                                <li><a href="importpurchase.html">Import Pembelian</a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/time.svg" alt="img" /><span>
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
                <div class="row">
                    <div class="col-lg-8 col-sm-12 tabs_wrapper">
                        <div class="page-header d-flex justify-content-between align-items-center">
                            <div class="page-title">
                                <h4>Kategori Produk</h4>
                                <h6>Cari berdasarkan kategori</h6>
                            </div>
                            <div class="product-search-form">
                                <form action="index.php" method="GET" class="d-flex">
                                    <div class="input-group" style="width: 300px;">
                                        <input type="text" name="search" class="form-control" placeholder="Cari barang..."
                                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                                        <?php if ($selectedType): ?>
                                            <input type="hidden" name="type" value="<?php echo $selectedType; ?>">
                                        <?php endif; ?>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fa fa-search"></i>
                                        </button>
                                        <?php if (!empty($searchQuery)): ?>
                                            <a href="index.php<?php echo $selectedType ? '?type=' . $selectedType : ''; ?>"
                                                class="btn btn-outline-secondary">
                                                <i class="fa fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="barcode-scanner-container">
                            <div class="barcode-scanner-header" id="toggle-barcode-scanner">
                                <h5><i class="fa fa-barcode"></i> Pindai Barcode</h5>
                                <button type="button" class="barcode-toggle-btn" aria-label="Toggle barcode scanner">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                            <div class="barcode-content">
                                <div class="barcode-input-group">
                                    <span class="input-group-text">
                                        <i class="fa fa-barcode"></i>
                                    </span>
                                    <input type="text" id="barcode-input" placeholder="Scan atau masukkan barcode..." autofocus>
                                </div>
                                <div id="barcode-status">
                                    <i class="fa fa-barcode"></i> Pindai disini
                                </div>
                            </div>
                        </div>


                        <?php if (!empty($searchQuery)): ?>
                            <div class="search-results-info mb-3">
                                <div class="alert alert-info">
                                    Hasil pencarian untuk: <strong><?php echo htmlspecialchars($searchQuery); ?></strong>
                                    (<?php echo count($products); ?> produk ditemukan)
                                </div>
                            </div>
                        <?php endif; ?>
                        <ul class="tabs owl-carousel owl-theme owl-product border-0">
                            <li class="<?php echo (!$selectedType) ? 'active' : ''; ?>">
                                <a href="index.php<?php echo (!empty($searchQuery)) ? '?search=' . urlencode($searchQuery) : ''; ?>" class="product-details category-tab">
                                    <img src="../../bootstrap/assets/img/product/product61.png" alt="img" />
                                    <h6>All</h6>
                                </a>
                            </li>
                            <?php foreach ($productTypes as $type): ?>
                                <li class="<?php echo ($selectedType == $type['id_jenis']) ? 'active' : ''; ?>">
                                    <a href="index.php?type=<?php echo $type['id_jenis']; ?><?php echo (!empty($searchQuery)) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="product-details category-tab">
                                        <img src="../../bootstrap/assets/img/product/product62.png" alt="img" />
                                        <h6><?php echo $type['nama_jenis']; ?></h6>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tabs_container">
                            <div class="tab_content active">
                                <div class="row">
                                    <?php if (count($products) > 0): ?>
                                        <?php foreach ($products as $product): ?>
                                            <div class="col-lg-3 col-sm-6 d-flex">
                                                <div class="productset flex-fill" data-product-id="<?php echo $product['id_barangK']; ?>">
                                                    <div class="productsetimg">
                                                        <?php if (!empty($product['gambar'])): ?>
                                                            <img src="<?php echo $product['gambar']; ?>" alt="<?php echo $product['nama_barang']; ?>" />
                                                        <?php else: ?>
                                                            <img src="../../bootstrap/assets/img/product/product29.jpg" alt="default" />
                                                        <?php endif; ?>
                                                        <h6>Stok: <?php echo $product['stok']; ?></h6>
                                                        <div class="check-product">
                                                            <input type="checkbox" class="product-checkbox" value="<?php echo $product['id_barangK']; ?>" id="product-<?php echo $product['id_barangK']; ?>" style="display:none;">
                                                            <i class="fa fa-check"></i>
                                                        </div>
                                                    </div>
                                                    <div class="productsetcontent">
                                                        <h5><?php echo $product['nama_jenis']; ?></h5>
                                                        <h4><?php echo $product['nama_barang']; ?></h4>
                                                        <h6>Rp. <?php echo number_format($product['harga'], 0, ',', '.'); ?></h6>
                                                        <form method="post" action="index.php" class="add-single-form">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id_barangK']; ?>">
                                                            <input type="hidden" name="quantity" value="1">
                                                            <button type="submit" name="add_to_cart" class="btn btn-sm btn-adds mt-2">
                                                                <i class="fa fa-plus me-1"></i> Tambahkan ke keranjang
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-center">Tidak ada barang ditemukan.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-sm-12">
                        <!-- Header keranjang yang tetap -->
                        <div class="order-list">
                            <div class="orderid">
                                <h4>Keranjang Belanja</h4>
                                <h5>Transaksi sedang berlangsung</h5>
                            </div>
                        </div>

                        <!-- Card container utama -->
                        <div class="card card-order">
                            <!-- Bagian total items -->
                            <div class="card-body pb-0">
                                <div class="totalitem">
                                    <h4>Total barang: <?php echo $cartItems; ?></h4>
                                    <a href="javascript:void(0);" id="clear-cart-btn" <?php if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): ?>class="disabled" style="opacity: 0.5; cursor: not-allowed;" <?php endif; ?>>Hapus semua</a>
                                </div>
                            </div>

                            <!-- Bagian daftar produk (scrollable) -->
                            <div class="product-table">
                                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                    <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                        <ul class="product-lists">
                                            <li>
                                                <div class="productimg">
                                                    <div class="productimgs">
                                                        <img src="../../bootstrap/assets/img/product/product30.jpg" alt="img" />
                                                    </div>
                                                    <div class="productcontet">
                                                        <h4><?php echo $item['name']; ?></h4>
                                                        <div class="productlinkset">
                                                            <h5><?php echo $item['code']; ?></h5>
                                                        </div>
                                                        <div class="increment-decrement">
                                                            <div class="input-groups">
                                                                <form method="post" action="index.php" class="cart-item-form">
                                                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                                    <input type="hidden" name="update_cart" value="1">
                                                                    <div class="quantity-control-container">
                                                                        <button type="button" class="btn btn-sm btn-light quantity-btn decrement">-</button>
                                                                        <input type="text" name="quantity" value="<?php echo $item['quantity']; ?>"
                                                                            class="quantity-field form-control mx-1"
                                                                            style="width: 45px; text-align: center;"
                                                                            data-max-stock="<?php echo isset($item['max_stock']) ? $item['max_stock'] : (getProductById($item['id'])['stok'] ?? 99); ?>" />
                                                                        <button type="button" class="btn btn-sm btn-light quantity-btn increment">+</button>
                                                                        <button type="submit" class="btn btn-sm btn-primary ms-1 update-cart-btn">
                                                                            <i class="fa fa-check"></i>
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>Rp. <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></li>
                                            <li>
                                                <a href="javascript:void(0);" class="delete-cart-item" data-index="<?php echo $index; ?>">
                                                    <img src="../../bootstrap/assets/img/icons/delete-2.svg" alt="img" />
                                                </a>
                                            </li>
                                        </ul>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center">Keranjang Anda kosong.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Bagian totals dan checkout (tetap di bawah) -->
                            <div class="card-body pt-2">
                                <div class="setvalue">
                                    <ul>
                                        <li class="total-value">
                                            <h5>Total</h5>
                                            <h6>Rp. <?php echo number_format($total, 0, ',', '.'); ?></h6>
                                        </li>
                                    </ul>
                                </div>

                                <div class="setvaluecash">
                                    <ul>
                                        <li>
                                            <div class="paymentmethod">
                                                <img src="../../bootstrap/assets/img/icons/cash.svg" alt="img" class="me-2" />
                                                Tunai
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Cash payment input -->
                                <div class="setvaluecash">
                                    <div class="form-group mb-2">
                                        <label for="cash-amount">Jumlah Tunai</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" id="cash-amount" class="form-control" placeholder="Enter cash amount"
                                                value="<?php echo number_format($total, 0, ',', '.'); ?>" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-1 quick-cash" data-value="<?php echo ceil($total / 1000) * 1000; ?>">
                                                Rp. <?php echo number_format(ceil($total / 1000) * 1000, 0, ',', '.'); ?>
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-1 quick-cash" data-value="<?php echo ceil($total / 10000) * 10000; ?>">
                                                Rp. <?php echo number_format(ceil($total / 10000) * 10000, 0, ',', '.'); ?>
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-1 quick-cash" data-value="<?php echo ceil($total / 50000) * 50000; ?>">
                                                Rp. <?php echo number_format(ceil($total / 50000) * 50000, 0, ',', '.'); ?>
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-1 quick-cash" data-value="<?php echo ceil($total / 100000) * 100000; ?>">
                                                Rp. <?php echo number_format(ceil($total / 100000) * 100000, 0, ',', '.'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Change display -->
                                <div class="setvaluecash mt-2" id="change-container" style="display: none;">
                                    <div class="d-flex justify-content-between">
                                        <h5>Kembalian</h5>
                                        <h5 id="change-amount">Rp. 0</h5>
                                    </div>
                                </div>

                                <!-- Checkout Form -->
                                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                    <form id="checkout-form" method="post" action="index.php">
                                        <input type="hidden" name="cash_amount" id="hidden-cash-amount" value="<?php echo $total; ?>">
                                        <input type="hidden" name="change_amount" id="hidden-change-amount" value="0">
                                        <input type="hidden" name="checkout" value="1">
                                        <button type="submit" class="btn-totallabel w-100">
                                            <h5>Pesan Sekarang</h5>
                                            <h6>Rp. <?php echo number_format($total, 0, ',', '.'); ?></h6>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-totallabel w-100" disabled>
                                        <h5>Pesan Sekarang</h5>
                                        <h6>Rp. 0</h6>
                                    </button>
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
    <script src="../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../bootstrap/assets/js/jquery.dataTables.min.js"></script>
    <script src="../../bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../bootstrap/assets/plugins/select2/js/select2.min.js"></script>
    <script src="../../bootstrap/assets/plugins/owlcarousel/owl.carousel.min.js"></script>
    <script src="../../bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="../../bootstrap/assets/plugins/sweetalert/sweetalerts.min.js"></script>
    <script src="../../bootstrap/assets/js/script.js"></script>
    <!-- Include our custom transaction JS file -->
    <script src="transaction.js"></script>
    <script src="barcode-scanner.js"></script>
    <script src="cart-functionality.js"></script>
    <script>
        // This script is added directly to the HTML to ensure the Clear All button is functional
        document.addEventListener('DOMContentLoaded', function() {
            // Force update the clear cart button state based on actual cart content
            function fixClearCartButton() {
                // Check if cart actually has items
                const hasItems = document.querySelectorAll('.product-lists').length > 0;
                console.log('Fixing clear cart button. Cart has items:', hasItems);

                const clearCartBtn = document.getElementById('clear-cart-btn');
                if (!clearCartBtn) return;

                if (hasItems) {
                    clearCartBtn.href = 'javascript:void(0);';
                    clearCartBtn.classList.remove('disabled');
                    clearCartBtn.style.opacity = '1';
                    clearCartBtn.style.cursor = 'pointer';

                    // Make sure event listener is properly attached
                    clearCartBtn.onclick = function(e) {
                        e.preventDefault();
                        if (typeof bootstrap !== 'undefined' && document.getElementById('clearCartModal')) {
                            try {
                                var clearModal = new bootstrap.Modal(document.getElementById('clearCartModal'));
                                clearModal.show();

                                // Important: properly setup the cancel button
                                document.getElementById('cancel-clear-cart').onclick = function() {
                                    clearModal.hide();
                                    // Force cleanup if needed
                                    setTimeout(function() {
                                        if (document.body.classList.contains("modal-open")) {
                                            document.body.classList.remove("modal-open");
                                            document.body.style.paddingRight = "";
                                            var backdrop = document.querySelector(".modal-backdrop");
                                            if (backdrop) backdrop.remove();
                                        }
                                    }, 300);
                                };
                            } catch (error) {
                                console.log('Modal error, using fallback:', error);
                                if (confirm('Apakah Anda yakin ingin menghapus semua item dari keranjang Anda?')) {
                                    window.location.href = 'index.php?clear_cart=1';
                                }
                            }
                        } else {
                            if (confirm('Apakah Anda yakin ingin menghapus semua item dari keranjang Anda?')) {
                                window.location.href = 'index.php?clear_cart=1';
                            }
                        }
                    };
                } else {
                    clearCartBtn.href = '#';
                    clearCartBtn.classList.add('disabled');
                    clearCartBtn.style.opacity = '0.5';
                    clearCartBtn.style.cursor = 'not-allowed';
                    clearCartBtn.onclick = null;
                }
            }

            // Run on page load
            fixClearCartButton();

            // Custom event listener for when barcode scan completes
            document.addEventListener('cartUpdated', function() {
                fixClearCartButton();
            });

            // Make sure modal buttons are properly initialized
            const setupModalButtons = function() {
                // Clear Cart Modal - Cancel button
                const cancelClearCartBtn = document.getElementById('cancel-clear-cart');
                if (cancelClearCartBtn) {
                    cancelClearCartBtn.onclick = function() {
                        // Try to get the modal instance and hide it
                        if (typeof bootstrap !== 'undefined') {
                            try {
                                var clearModal = bootstrap.Modal.getInstance(document.getElementById('clearCartModal'));
                                if (clearModal) {
                                    clearModal.hide();
                                } else {
                                    // Fallback to jQuery method
                                    $('#clearCartModal').modal('hide');
                                }
                            } catch (error) {
                                console.log('Modal hide error:', error);
                                // Force modal to close if all else fails
                                document.getElementById('clearCartModal').classList.remove('show');
                                document.body.classList.remove('modal-open');
                                document.body.style.paddingRight = '';
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.parentNode.removeChild(backdrop);
                            }
                        }
                    };
                }

                // Clear Cart Modal - Confirm button
                const confirmClearCartBtn = document.getElementById('confirm-clear-cart');
                if (confirmClearCartBtn) {
                    confirmClearCartBtn.onclick = function() {
                        window.location.href = 'index.php?clear_cart=1';
                    };
                }
            };

            // Run modal button setup
            setupModalButtons();

            // Also check periodically (safety measure)
            setInterval(fixClearCartButton, 2000);
        });
    </script>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus item ini dari keranjang Anda?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirm-delete-btn" class="btn btn-primary">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear cart confirmation modal -->
    <div class="modal fade" id="clearCartModal" tabindex="-1" aria-labelledby="clearCartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearCartModalLabel">Hapus semua</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus semua item dari keranjang Anda?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancel-clear-cart">Batalkan</button>
                    <a href="index.php?clear_cart=1" class="btn btn-primary" id="confirm-clear-cart">Hapus Semua</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>