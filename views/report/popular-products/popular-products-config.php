<?php
session_start();

// Include database connection
include('../../../routes/db_conn.php');

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /ayula-store/views/login/');
    exit;
}

// Get current logged-in user's information
function getCurrentUser() {
    global $conn;
    if (isset($_SESSION['user_id'])) {
        $stmt = mysqli_prepare($conn, "SELECT id_kasir, username, role, phone FROM kasir WHERE id_kasir = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }
    return null;
}

// Ensure username is available in session
if (isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    $currentUser = getCurrentUser();
    if ($currentUser) {
        $_SESSION['username'] = $currentUser['username'];
    }
}

// Get user role from session
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : 'employee';
}

// Get username from session
function getUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
}

// Check if current user is admin
function isAdmin() {
    return getUserRole() === 'admin';
}

// Define date presets with role-based options
function getDatePresets($isAdmin = false) {
    $presets = [
        'today' => [
            'label' => 'Hari Ini',
            'start' => date('Y-m-d'),
            'end' => date('Y-m-d')
        ],
        'yesterday' => [
            'label' => 'Kemarin',
            'start' => date('Y-m-d', strtotime('-1 day')),
            'end' => date('Y-m-d', strtotime('-1 day'))
        ],
        'this_week' => [
            'label' => 'Minggu Ini',
            'start' => date('Y-m-d', strtotime('monday this week')),
            'end' => date('Y-m-d')
        ],
        'last_week' => [
            'label' => 'Minggu Lalu',
            'start' => date('Y-m-d', strtotime('monday last week')),
            'end' => date('Y-m-d', strtotime('sunday last week'))
        ],
        'this_month' => [
            'label' => 'Bulan Ini',
            'start' => date('Y-m-01'),
            'end' => date('Y-m-d')
        ],
    ];
    
    // Admin-only presets
    if ($isAdmin) {
        $presets['last_month'] = [
            'label' => 'Bulan Lalu',
            'start' => date('Y-m-d', strtotime('first day of last month')),
            'end' => date('Y-m-d', strtotime('last day of last month'))
        ];
        
        $presets['last_90_days'] = [
            'label' => '90 Hari Terakhir',
            'start' => date('Y-m-d', strtotime('-90 days')),
            'end' => date('Y-m-d')
        ];
        
        $presets['this_year'] = [
            'label' => 'Tahun Ini',
            'start' => date('Y') . '-01-01',
            'end' => date('Y-m-d')
        ];
        
        $presets['all_time'] = [
            'label' => 'Sepanjang Waktu',
            'start' => '2000-01-01', // Set a reasonable start date
            'end' => date('Y-m-d')
        ];
    }
    
    return $presets;
}

// Function to check if an employee has permission for a specific report feature
function canAccessFeature($feature) {
    // Default permissions based on role
    $permissions = [
        'admin' => [
            'export_excel' => true,
            'export_pdf' => true,
            'print_report' => true,
            'view_financial' => true,
            'view_payment_methods' => true,
            'view_all_time' => true,
            'view_summary' => true,
            'view_popular_products' => true
        ],
        'manager' => [
            'export_excel' => true,
            'export_pdf' => false,
            'print_report' => true,
            'view_financial' => true,
            'view_payment_methods' => true,
            'view_all_time' => false,
            'view_summary' => true,
            'view_popular_products' => true
        ],
        'employee' => [
            'export_excel' => false,
            'export_pdf' => false,
            'print_report' => false,
            'view_financial' => false,
            'view_payment_methods' => false,
            'view_all_time' => false,
            'view_summary' => false,
            'view_popular_products' => false
        ],
        'user' => [
            'export_excel' => false,
            'export_pdf' => false,
            'print_report' => false,
            'view_financial' => false,
            'view_payment_methods' => false,
            'view_all_time' => false,
            'view_summary' => false,
            'view_popular_products' => false
        ]
    ];
    
    $role = getUserRole();
    
    // If role doesn't exist in our permissions, default to employee
    if (!isset($permissions[$role])) {
        $role = 'employee';
    }
    
    // Return permission status
    return isset($permissions[$role][$feature]) ? $permissions[$role][$feature] : false;
}

// Function to get popular products
function getPopularProducts($startDate, $endDate, $limit = 20, $sortBy = 'quantity', $isAdmin = false) {
    global $conn;
    
    // Add time components to make the date range inclusive
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
    // Base query for popular products - modified to join jenis_barang for category
    $baseQuery = "
        SELECT 
            b.id_barang,
            b.kode_barang,
            b.nama_barang,
            b.harga,
            j.nama_jenis as kategori,
            SUM(dt.jumlah) as total_quantity,
            SUM(dt.total_harga) as total_revenue
        FROM 
            barang b
        JOIN 
            jenis_barang j ON b.id_jenis = j.id_jenis
        JOIN 
            detail_transaksi dt ON b.id_barang = dt.id_barang
        JOIN 
            transaksi t ON dt.id_transaksi = t.id_transaksi
        WHERE 
            t.tanggal BETWEEN ? AND ?
        GROUP BY 
            b.id_barang
        ORDER BY 
    ";
    
    // Add the appropriate ORDER BY clause
    if ($sortBy == 'revenue') {
        $baseQuery .= "total_revenue DESC";
    } else {
        $baseQuery .= "total_quantity DESC";
    }
    
    // Add limit
    $baseQuery .= " LIMIT ?";
    
    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $baseQuery);
    
    if (!$stmt) {
        return [
            'result' => false,
            'error' => 'Failed to prepare query: ' . mysqli_error($conn),
            'has_data' => false
        ];
    }
    
    mysqli_stmt_bind_param($stmt, "ssi", $startDateTime, $endDateTime, $limit);
    $execResult = mysqli_stmt_execute($stmt);
    
    if (!$execResult) {
        return [
            'result' => false,
            'error' => 'Failed to execute query: ' . mysqli_stmt_error($stmt),
            'has_data' => false
        ];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $rowCount = mysqli_num_rows($result);
    
    // Get summary totals
    $summaryQuery = "
        SELECT 
            COUNT(DISTINCT dt.id_barang) as total_products,
            SUM(dt.jumlah) as total_items_sold,
            SUM(dt.total_harga) as total_revenue
        FROM 
            detail_transaksi dt
        JOIN 
            transaksi t ON dt.id_transaksi = t.id_transaksi
        WHERE 
            t.tanggal BETWEEN ? AND ?
    ";
    
    $summaryStmt = mysqli_prepare($conn, $summaryQuery);
    
    if (!$summaryStmt) {
        return [
            'result' => $result,
            'summary' => null,
            'error' => 'Failed to prepare summary query: ' . mysqli_error($conn),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    mysqli_stmt_bind_param($summaryStmt, "ss", $startDateTime, $endDateTime);
    $summaryExecResult = mysqli_stmt_execute($summaryStmt);
    
    if (!$summaryExecResult) {
        return [
            'result' => $result,
            'summary' => null,
            'error' => 'Failed to execute summary query: ' . mysqli_stmt_error($summaryStmt),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    $summaryResult = mysqli_stmt_get_result($summaryStmt);
    $summary = mysqli_fetch_assoc($summaryResult);
    
    // Handle case where no data is found
    if (!$summary) {
        $summary = [
            'total_products' => 0,
            'total_items_sold' => 0,
            'total_revenue' => 0
        ];
    }
    
    // For non-admin users, potentially mask certain financial data
    if (!$isAdmin) {
        // Keep the summary accessible but hide financial details if needed
        if (!canAccessFeature('view_financial')) {
            $summary['total_revenue'] = 0;
        }
    }
    
    // Get category breakdown for pie chart - using jenis_barang table
    $categoryQuery = "
        SELECT 
            j.nama_jenis as kategori,
            SUM(dt.jumlah) as total_quantity,
            SUM(dt.total_harga) as total_revenue,
            COUNT(DISTINCT b.id_barang) as unique_products
        FROM 
            barang b
        JOIN 
            jenis_barang j ON b.id_jenis = j.id_jenis
        JOIN 
            detail_transaksi dt ON b.id_barang = dt.id_barang
        JOIN 
            transaksi t ON dt.id_transaksi = t.id_transaksi
        WHERE 
            t.tanggal BETWEEN ? AND ?
        GROUP BY 
            j.nama_jenis
        ORDER BY 
            total_quantity DESC
    ";
    
    $categoryStmt = mysqli_prepare($conn, $categoryQuery);
    
    if (!$categoryStmt) {
        return [
            'result' => $result,
            'summary' => $summary,
            'categories' => null,
            'error' => 'Failed to prepare category query: ' . mysqli_error($conn),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    mysqli_stmt_bind_param($categoryStmt, "ss", $startDateTime, $endDateTime);
    $categoryExecResult = mysqli_stmt_execute($categoryStmt);
    
    if (!$categoryExecResult) {
        return [
            'result' => $result,
            'summary' => $summary,
            'categories' => null,
            'error' => 'Failed to execute category query: ' . mysqli_stmt_error($categoryStmt),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    $categoryResult = mysqli_stmt_get_result($categoryStmt);
    
    return [
        'result' => $result,
        'summary' => $summary,
        'categories' => $categoryResult,
        'has_data' => ($rowCount > 0)
    ];
}

// Function to apply date ranges based on preset or manual selection
function getStartDate($datePresets, $activePreset) {
    // If preset is selected, use preset dates
    if (!empty($activePreset) && isset($datePresets[$activePreset])) {
        return $datePresets[$activePreset]['start'];
    }
    
    // Otherwise use the date parameters if provided
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) 
        ? $_GET['start_date'] 
        : date('Y-m-01'); // Default to first day of current month
    
    // Make sure date is in YYYY-MM-DD format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = date('Y-m-01');
    }
    
    return $startDate;
}

function getEndDate($datePresets, $activePreset) {
    // If preset is selected, use preset dates
    if (!empty($activePreset) && isset($datePresets[$activePreset])) {
        return $datePresets[$activePreset]['end'];
    }
    
    // Otherwise use the date parameters if provided
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) 
        ? $_GET['end_date'] 
        : date('Y-m-d');  // Default to today
    
    // Make sure date is in YYYY-MM-DD format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $endDate = date('Y-m-d');
    }
    
    return $endDate;
}

// Handle PDF export functionality
function exportToPDF($startDate, $endDate, $limit, $sortBy, $isAdmin) {
    // Check permissions
    if (!($isAdmin || canAccessFeature('export_pdf'))) {
        die("Access denied. You don't have permission to export to PDF.");
    }
    
    // Get the data
    $queryData = getPopularProducts($startDate, $endDate, $limit, $sortBy, $isAdmin);
    
    // Here you would implement PDF generation with a library like FPDF or TCPDF
    // For example:
    /*
    require('fpdf/fpdf.php');
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Ayula Store - Laporan Produk Terlaris');
    
    // Add more PDF generation code here
    
    $pdf->Output('D', 'produk_terlaris.pdf');
    exit;
    */
    
    // For now, just return a message
    echo "<h1>PDF Export</h1>";
    echo "<p>This functionality would generate a PDF for the selected date range: $startDate to $endDate</p>";
    echo "<p>Implementation with a PDF library is required to complete this feature.</p>";
    exit;
}