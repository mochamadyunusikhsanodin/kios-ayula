<?php
// Mulai sesi untuk menangani keranjang
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

// Definisikan preset tanggal dengan opsi berbasis peran
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
    
    // Preset hanya untuk admin
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
        
        $presets['all_time'] = [
            'label' => 'Sepanjang Waktu',
            'start' => '2000-01-01', // Tetapkan tanggal mulai yang masuk akal
            'end' => date('Y-m-d')
        ];
    }
    
    return $presets;
}

// Dapatkan preset tanggal yang sesuai berdasarkan peran pengguna
$isAdmin = isAdmin();
$datePresets = getDatePresets($isAdmin);

// Tangani permintaan reset
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    // Jika reset diminta, alihkan ke halaman yang sama dengan tanggal default
    header("Location: index.php");
    exit;
}

// Jika pengguna bukan admin, batasi akses data historis
if (!$isAdmin && empty($_GET['preset']) && empty($_GET['start_date'])) {
    // Default pengguna non-admin ke minggu ini jika tidak ada tanggal yang ditentukan
    $_GET['preset'] = 'this_week';
}

// Periksa apakah preset dipilih
$activePreset = isset($_GET['preset']) && !empty($_GET['preset']) ? $_GET['preset'] : '';

// Terapkan rentang tanggal berdasarkan preset atau pemilihan manual
function getStartDate() {
    global $datePresets, $activePreset;
    
    // Jika preset dipilih, gunakan tanggal preset
    if (!empty($activePreset) && isset($datePresets[$activePreset])) {
        return $datePresets[$activePreset]['start'];
    }
    
    // Jika tidak, gunakan parameter tanggal jika disediakan
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) 
        ? $_GET['start_date'] 
        : date('Y-m-01'); // Default ke hari pertama bulan ini
    
    // Pastikan tanggal dalam format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = date('Y-m-01');
    }
    
    return $startDate;
}

function getEndDate() {
    global $datePresets, $activePreset;
    
    // Jika preset dipilih, gunakan tanggal preset
    if (!empty($activePreset) && isset($datePresets[$activePreset])) {
        return $datePresets[$activePreset]['end'];
    }
    
    // Jika tidak, gunakan parameter tanggal jika disediakan
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) 
        ? $_GET['end_date'] 
        : date('Y-m-d');  // Default ke hari ini
    
    // Pastikan tanggal dalam format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $endDate = date('Y-m-d');
    }
    
    return $endDate;
}

$startDate = getStartDate();
$endDate = getEndDate();

// Pastikan tanggal mulai tidak setelah tanggal akhir
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Fungsi untuk mendapatkan kueri yang sesuai dengan peran dengan penanganan tidak ada data yang lebih baik
function getReportQueries($startDate, $endDate, $isAdmin = false) {
    global $conn;
    
    // Tambahkan komponen waktu untuk membuat rentang tanggal inklusif
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
    // Siapkan kueri transaksi dasar dengan filter tanggal
    $baseQuery = "SELECT t.id_transaksi, t.kode_transaksi, t.tanggal, t.total_item,
                  t.subtotal, t.total, t.metode_pembayaran, t.cash_amount, t.change_amount,
                  COUNT(DISTINCT dt.id_barang) as total_products
                 FROM transaksi t
                 LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
                 WHERE t.tanggal BETWEEN ? AND ?";
    
    // Admin melihat semuanya
    if ($isAdmin) {
        $transactionQuery = $baseQuery . " GROUP BY t.id_transaksi ORDER BY t.tanggal DESC";
    } 
    // Karyawan melihat data terbatas dan hanya transaksi terbaru
    else {
        // Untuk karyawan, kami membatasi jumlah catatan
        $transactionQuery = $baseQuery . " GROUP BY t.id_transaksi ORDER BY t.tanggal DESC LIMIT 100";
    }
    
    // Siapkan dan jalankan kueri transaksi
    $stmt = mysqli_prepare($conn, $transactionQuery);
    
    if (!$stmt) {
        // Tangani kesalahan persiapan kueri
        return [
            'result' => false,
            'paymentResult' => null,
            'totals' => [
                'total_transactions' => 0,
                'total_items' => 0,
                'total_subtotal' => 0,
                'grand_total' => 0
            ],
            'error' => 'Gagal menyiapkan kueri: ' . mysqli_error($conn),
            'has_data' => false
        ];
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $startDateTime, $endDateTime);
    $execResult = mysqli_stmt_execute($stmt);
    
    if (!$execResult) {
        // Tangani kesalahan eksekusi kueri
        return [
            'result' => false,
            'paymentResult' => null,
            'totals' => [
                'total_transactions' => 0,
                'total_items' => 0,
                'total_subtotal' => 0,
                'grand_total' => 0
            ],
            'error' => 'Gagal mengeksekusi kueri: ' . mysqli_stmt_error($stmt),
            'has_data' => false
        ];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $rowCount = mysqli_num_rows($result);
    
    // Dapatkan total untuk ringkasan
    $totalQuery = "SELECT
                  COUNT(id_transaksi) as total_transactions,
                  SUM(total_item) as total_items,
                  SUM(subtotal) as total_subtotal,
                  SUM(total) as grand_total
                  FROM transaksi
                  WHERE tanggal BETWEEN ? AND ?";
    
    $totalStmt = mysqli_prepare($conn, $totalQuery);
    
    if (!$totalStmt) {
        // Tangani kesalahan persiapan kueri total
        return [
            'result' => $result,
            'paymentResult' => null,
            'totals' => [
                'total_transactions' => 0,
                'total_items' => 0,
                'total_subtotal' => 0,
                'grand_total' => 0
            ],
            'error' => 'Gagal menyiapkan kueri total: ' . mysqli_error($conn),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    mysqli_stmt_bind_param($totalStmt, "ss", $startDateTime, $endDateTime);
    $totalExecResult = mysqli_stmt_execute($totalStmt);
    
    if (!$totalExecResult) {
        // Tangani kesalahan eksekusi kueri total
        return [
            'result' => $result,
            'paymentResult' => null,
            'totals' => [
                'total_transactions' => 0,
                'total_items' => 0,
                'total_subtotal' => 0,
                'grand_total' => 0
            ],
            'error' => 'Gagal mengeksekusi kueri total: ' . mysqli_stmt_error($totalStmt),
            'has_data' => ($rowCount > 0)
        ];
    }
    
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totals = mysqli_fetch_assoc($totalResult);
    
    // Tangani kasus di mana tidak ada data yang ditemukan
    if (!$totals) {
        $totals = [
            'total_transactions' => 0,
            'total_items' => 0,
            'total_subtotal' => 0,
            'grand_total' => 0
        ];
    }
    
    // Untuk pengguna non-admin, masker data keuangan tertentu
    if (!$isAdmin && $totals) {
        // Hanya tampilkan kuantitas, sembunyikan angka keuangan
        if (!canAccessFeature('view_financial')) {
            $totals['total_subtotal'] = 0;
            $totals['grand_total'] = 0;
        }
    }
    
    // Dapatkan breakdown metode pembayaran - hanya untuk pengguna dengan izin yang tepat
    $paymentResult = null;
    if (($isAdmin || canAccessFeature('view_payment_methods')) && $rowCount > 0) {
        $paymentQuery = "SELECT
                        metode_pembayaran,
                        COUNT(*) as count,
                        SUM(total) as total_amount
                        FROM transaksi
                        WHERE tanggal BETWEEN ? AND ?
                        GROUP BY metode_pembayaran
                        ORDER BY total_amount DESC";
        
        $paymentStmt = mysqli_prepare($conn, $paymentQuery);
        if ($paymentStmt) {
            mysqli_stmt_bind_param($paymentStmt, "ss", $startDateTime, $endDateTime);
            $paymentExecResult = mysqli_stmt_execute($paymentStmt);
            
            if ($paymentExecResult) {
                $paymentResult = mysqli_stmt_get_result($paymentStmt);
            }
        }
    }
    
    return [
        'result' => $result,
        'paymentResult' => $paymentResult,
        'totals' => $totals,
        'has_data' => ($rowCount > 0)
    ];
}

// Fungsi untuk memeriksa apakah karyawan memiliki izin untuk fitur laporan tertentu
function canAccessFeature($feature) {
    // Izin default berdasarkan peran
    $permissions = [
        'admin' => [
            'export_excel' => true,
            'export_pdf' => true,
            'print_report' => true,
            'view_financial' => true,
            'view_payment_methods' => true,
            'view_all_time' => true,
            'view_summary' => true
        ],
        'manager' => [
            'export_excel' => true,
            'export_pdf' => false,
            'print_report' => true,
            'view_financial' => true,
            'view_payment_methods' => true,
            'view_all_time' => false,
            'view_summary' => true
        ],
        'employee' => [
            'export_excel' => false,
            'export_pdf' => false,
            'print_report' => false,
            'view_financial' => false,
            'view_payment_methods' => false,
            'view_all_time' => false,
            'view_summary' => false
        ]
    ];
    
    $role = getUserRole();
    
    // Jika peran tidak ada dalam izin kami, default ke karyawan
    if (!isset($permissions[$role])) {
        $role = 'employee';
    }
    
    // Kembalikan status izin
    return isset($permissions[$role][$feature]) ? $permissions[$role][$feature] : false;
}

// Dapatkan kueri yang sesuai berdasarkan peran pengguna
$queryData = getReportQueries($startDate, $endDate, $isAdmin);
$result = $queryData['result'];
$paymentResult = $queryData['paymentResult'];
$totals = $queryData['totals'];

// Dapatkan peran pengguna dari sesi untuk izin menu
$userRole = getUserRole();

// Menangani permintaan ekspor PDF
if (isset($_POST['export_pdf']) || (isset($_GET['export']) && $_GET['export'] == 'pdf')) {
    // Periksa izin
    if (!($isAdmin || canAccessFeature('export_pdf'))) {
        die("Akses ditolak. Anda tidak memiliki izin untuk mengekspor ke PDF.");
    }
    
    // Dapatkan parameter tanggal
    $exportStartDate = isset($_POST['start_date']) ? $_POST['start_date'] : (isset($_GET['start_date']) ? $_GET['start_date'] : $startDate);
    $exportEndDate = isset($_POST['end_date']) ? $_POST['end_date'] : (isset($_GET['end_date']) ? $_GET['end_date'] : $endDate);
    
    // Tetapkan header untuk PDF (memaksa unduhan)
    header('Content-Type: text/html; charset=utf-8');
    
    // Mulai output buffering
    ob_start();
    
    // Konten HTML ramah PDF
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Laporan Penjualan</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.header { text-align: center; margin-bottom: 30px; }';
    echo '.summary { margin: 20px 0; }';
    echo '.footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }';
    echo '@media print { .no-print { display: none; } }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Tambahkan tombol cetak
    echo '<div class="no-print" style="text-align: right; margin-bottom: 20px;">';
    echo '<button onclick="window.print()">Cetak laporan ini</button>';
    echo '<p>Setelah mencetak, gunakan dialog cetak browser Anda untuk menyimpan sebagai PDF</p>';
    echo '</div>';
    
    // Header laporan
    echo '<div class="header">';
    echo '<h1>Ayula Store - Laporan Penjualan</h1>';
    echo '<h3>Periode: ' . date('d M Y', strtotime($exportStartDate)) . ' sampai ' . date('d M Y', strtotime($exportEndDate)) . '</h3>';
    echo '<p>Dibuat pada: ' . date('d M Y H:i:s') . '</p>';
    echo '<p>Dibuat oleh: ' . ($userRole) . '</p>';
    echo '</div>';
    
    // Jalankan kembali kueri untuk mendapatkan data baru
    $exportQueryData = getReportQueries($exportStartDate, $exportEndDate, true);
    $exportResult = $exportQueryData['result'];
    $exportTotals = $exportQueryData['totals'];
    
    // Bagian ringkasan
    echo '<div class="summary">';
    echo '<h2>Ringkasan</h2>';
    echo '<table>';
    echo '<tr><th style="width: 200px;">Total Transaksi</th><td>' . number_format($exportTotals['total_transactions']) . '</td></tr>';
    echo '<tr><th>Total Item Terjual</th><td>' . number_format($exportTotals['total_items']) . '</td></tr>';
    echo '<tr><th>Subtotal</th><td>Rp. ' . number_format($exportTotals['total_subtotal']) . '</td></tr>';
    echo '<tr><th>Total</th><td>Rp. ' . number_format($exportTotals['grand_total']) . '</td></tr>';
    
    $daysDiff = (strtotime($exportEndDate) - strtotime($exportStartDate)) / (60 * 60 * 24) + 1;
    $dailyAvg = $daysDiff > 0 ? $exportTotals['grand_total'] / $daysDiff : 0;
    echo '<tr><th>Rata-rata Harian</th><td>Rp. ' . number_format($dailyAvg) . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Tabel transaksi
    echo '<h2>Transaksi</h2>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID Transaksi</th>';
    echo '<th>Tanggal</th>';
    echo '<th>Item</th>';
    echo '<th>Subtotal</th>';
    echo '<th>Total</th>';
    echo '<th>Pembayaran</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if ($exportResult && mysqli_num_rows($exportResult) > 0) {
        mysqli_data_seek($exportResult, 0); // Reset pointer ke awal set hasil
        while ($row = mysqli_fetch_assoc($exportResult)) {
            echo '<tr>';
            echo '<td>' . $row['kode_transaksi'] . '</td>';
            echo '<td>' . date('d M Y H:i', strtotime($row['tanggal'])) . '</td>';
            echo '<td>' . $row['total_item'] . '</td>';
            echo '<td>Rp. ' . number_format($row['subtotal']) . '</td>';
            echo '<td>Rp. ' . number_format($row['total']) . '</td>';
            echo '<td>' . $row['metode_pembayaran'] . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7" align="center">Tidak ada transaksi ditemukan</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Footer
    echo '<div class="footer">';
    echo '<p>Ayula Store &copy; ' . date('Y') . ' - Hak Cipta Dilindungi</p>';
    echo '<p>Laporan ini bersifat rahasia dan ditujukan hanya untuk personel yang berwenang.</p>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    
    // Keluarkan buffer dan akhiri
    ob_end_flush();
    exit;
}
?>