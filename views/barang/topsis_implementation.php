<?php
// File: topsis_implementation.php
require_once 'topsis_functions.php';

function processRestock($conn, $productId, $amount, $note = '') {
    $productId = (int)$productId;
    $amount = (int)$amount;
    
    if ($productId <= 0 || $amount <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid product ID or amount'
        ];
    }
    
    // Ambil stok sekarang dari barang_kasir
    $query = "SELECT stok FROM barang_kasir WHERE id_barangK = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$row = mysqli_fetch_assoc($result)) {
        return [
            'success' => false,
            'message' => 'Product not found'
        ];
    }
    
    $currentStock = (int)$row['stok'];
    $newStock = $currentStock + $amount;
    
    // Update stok barang_kasir
    $updateQuery = "UPDATE barang_kasir SET stok = ? WHERE id_barangK = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "ii", $newStock, $productId);
    $success = mysqli_stmt_execute($updateStmt);
    
    if (!$success) {
        return [
            'success' => false,
            'message' => 'Failed to update stock: ' . mysqli_error($conn)
        ];
    }
    
    // Log aktivitas restok
    $logQuery = "INSERT INTO log_restok (id_barang, jumlah, tanggal, catatan) 
                 VALUES (?, ?, NOW(), ?)";
    $logStmt = mysqli_prepare($conn, $logQuery);
    mysqli_stmt_bind_param($logStmt, "iis", $productId, $amount, $note);
    mysqli_stmt_execute($logStmt);
    
    return [
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_stock' => $newStock
    ];
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $host = "localhost";
    $username = "root";      // sesuaikan username DB Anda
    $password = "";          // sesuaikan password DB Anda
    $database = "ayula_store";
    
    $conn = mysqli_connect($host, $username, $password, $database);
    if (!$conn) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . mysqli_connect_error()
        ]);
        exit;
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'restock':
            if (isset($_POST['productId']) && isset($_POST['amount'])) {
                $result = processRestock(
                    $conn, 
                    $_POST['productId'], 
                    $_POST['amount'],
                    $_POST['note'] ?? ''
                );
                echo json_encode($result);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ]);
            }
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unknown action'
            ]);
    }
    
    mysqli_close($conn);
    exit;
}

function createRestockLogTable($conn) {
    $query = "CREATE TABLE IF NOT EXISTS log_restok (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        id_barang INT(11) NOT NULL,
        jumlah INT(10) NOT NULL,
        tanggal DATETIME NOT NULL,
        catatan TEXT,
        FOREIGN KEY (id_barang) REFERENCES barang_kasir(id_barangK)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    return mysqli_query($conn, $query);
}

function exportToCSV($data, $filename = 'topsis_results.csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Rank', 'ID Barang', 'Nama Barang', 'Stok', 'Harga', 'TOPSIS Score']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['rank'],
            $row['id_barang'],
            $row['nama_barang'],
            $row['stok'],
            $row['harga'],
            $row['topsis_score']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
