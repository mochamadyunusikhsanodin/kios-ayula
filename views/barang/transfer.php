<?php
// Updated transfer.php file that handles both single and bulk transfers
// Save this in the same directory as your productlist.php

// Set headers for JSON response
header('Content-Type: application/json');

// Create log file
function log_debug($message) {
    file_put_contents('transfer_debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

log_debug('Script started');
log_debug('POST data: ' . json_encode($_POST));

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "ayula_store";

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    log_debug("Connection failed: " . $conn->connect_error);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit;
}

log_debug("Database connected successfully");

// Check if we're handling a single product or bulk transfer
$is_bulk = isset($_POST['product_ids']) && is_array($_POST['product_ids']);

if ($is_bulk) {
    // Bulk transfer
    log_debug("Processing bulk transfer");
    
    $product_ids = $_POST['product_ids'];
    $quantities = $_POST['quantities'];
    
    // Validate input
    if (empty($product_ids) || empty($quantities) || count($product_ids) != count($quantities)) {
        log_debug("Invalid bulk data");
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak valid untuk transfer massal.'
        ]);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    $success_count = 0;
    $error_messages = [];
    
    for ($i = 0; $i < count($product_ids); $i++) {
        $id_barang = $product_ids[$i];
        $quantity = intval($quantities[$i]);
        
        log_debug("Processing item $i: ID=$id_barang, Quantity=$quantity");
        
        // Skip invalid items
        if (empty($id_barang) || $quantity <= 0) {
            $error_messages[] = "Data tidak valid untuk produk #$i";
            continue;
        }
        
        // Get product details
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id_barang = ?");
        $stmt->bind_param("s", $id_barang);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $error_messages[] = "Produk dengan ID $id_barang tidak ditemukan";
            $stmt->close();
            continue;
        }
        
        $product = $result->fetch_assoc();
        
        // Check stock
        if ($quantity > $product['stok']) {
            $error_messages[] = "Stok tidak mencukupi untuk produk {$product['nama_barang']}";
            $stmt->close();
            continue;
        }
        
        // Update stock in barang
        $new_stock = $product['stok'] - $quantity;
        $update_stmt = $conn->prepare("UPDATE barang SET stok = ? WHERE id_barang = ?");
        $update_stmt->bind_param("is", $new_stock, $id_barang);
        
        if (!$update_stmt->execute()) {
            $error_messages[] = "Gagal memperbarui stok untuk produk {$product['nama_barang']}";
            $stmt->close();
            $update_stmt->close();
            continue;
        }
        
        $update_stmt->close();
        
        // Check if exists in barang_kasir
        $check_stmt = $conn->prepare("SELECT * FROM barang_kasir WHERE kode_barang = ?");
        $check_stmt->bind_param("s", $product['kode_barang']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $timestamp = date('Y-m-d H:i:s');
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $kasir_product = $check_result->fetch_assoc();
            $new_kasir_stock = $kasir_product['stok'] + $quantity;
            
            $kasir_update_sql = "UPDATE barang_kasir SET stok = ?, update_at = ? WHERE id_barangK = ?";
            $kasir_update_stmt = $conn->prepare($kasir_update_sql);
            $kasir_update_stmt->bind_param("isi", $new_kasir_stock, $timestamp, $kasir_product['id_barangK']);
            
            if (!$kasir_update_stmt->execute()) {
                $error_messages[] = "Gagal memperbarui stok di kasir untuk produk {$product['nama_barang']}";
                $stmt->close();
                $check_stmt->close();
                $kasir_update_stmt->close();
                continue;
            }
            
            $kasir_update_stmt->close();
        } else {
            // Insert new record
            // Using direct query to avoid parameter binding issues
            $kode_barang = $conn->real_escape_string($product['kode_barang']);
            $nama_barang = $conn->real_escape_string($product['nama_barang']);
            $id_jenis = (int)$product['id_jenis'];
            $harga = $conn->real_escape_string($product['harga']);
            $stok = (int)$quantity;
            $gambar = $conn->real_escape_string($product['image'] ?? '');
            $timestamp_esc = $conn->real_escape_string($timestamp);
            
            $insert_query = "INSERT INTO barang_kasir (kode_barang, nama_barang, id_jenis, harga, stok, gambar, created_at, update_at) 
                         VALUES ('$kode_barang', '$nama_barang', $id_jenis, '$harga', $stok, '$gambar', '$timestamp_esc', '$timestamp_esc')";
            
            if (!$conn->query($insert_query)) {
                $error_messages[] = "Gagal menambahkan produk {$product['nama_barang']} ke kasir";
                $stmt->close();
                $check_stmt->close();
                continue;
            }
        }
        
        $check_stmt->close();
        $stmt->close();
        $success_count++;
    }
    
    if ($success_count == 0) {
        // If no products were successfully processed
        $conn->rollback();
        log_debug("Bulk transfer failed: " . implode(', ', $error_messages));
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada produk yang berhasil dipindahkan: ' . implode(', ', $error_messages)
        ]);
    } else {
        // Commit if at least one product was processed
        $conn->commit();
        
        if (count($error_messages) > 0) {
            log_debug("Partial bulk transfer success: $success_count items, with errors: " . implode(', ', $error_messages));
            echo json_encode([
                'success' => true,
                'message' => "Berhasil memindahkan $success_count produk ke kasir. Beberapa produk gagal: " . implode(', ', $error_messages)
            ]);
        } else {
            log_debug("Complete bulk transfer success: $success_count items");
            echo json_encode([
                'success' => true,
                'message' => "Berhasil memindahkan $success_count produk ke kasir."
            ]);
        }
    }
} else {
    // Single product transfer
    if (!isset($_POST['id_barang']) || !isset($_POST['quantity'])) {
        log_debug("Missing required parameters for single transfer");
        echo json_encode([
            'success' => false,
            'message' => 'Parameter tidak lengkap'
        ]);
        exit;
    }
    
    $id_barang = $_POST['id_barang'];
    $quantity = (int)$_POST['quantity'];
    
    log_debug("Processing single transfer for product ID: $id_barang, quantity: $quantity");
    
    // Validate data
    if (empty($id_barang) || $quantity <= 0) {
        log_debug("Invalid parameters");
        echo json_encode([
            'success' => false,
            'message' => 'Parameter tidak valid'
        ]);
        exit;
    }
    
    // Get product information
    $stmt = $conn->prepare("SELECT * FROM barang WHERE id_barang = ?");
    $stmt->bind_param("s", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        log_debug("Product not found");
        echo json_encode([
            'success' => false,
            'message' => 'Barang tidak ditemukan'
        ]);
        $stmt->close();
        exit;
    }
    
    $product = $result->fetch_assoc();
    log_debug("Product found: " . json_encode($product));
    
    // Check if enough stock
    if ($quantity > $product['stok']) {
        log_debug("Insufficient stock");
        echo json_encode([
            'success' => false,
            'message' => 'Stok tidak mencukupi'
        ]);
        $stmt->close();
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update stock in barang
        $new_stock = $product['stok'] - $quantity;
        $update_stmt = $conn->prepare("UPDATE barang SET stok = ? WHERE id_barang = ?");
        $update_stmt->bind_param("is", $new_stock, $id_barang);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update product stock: " . $update_stmt->error);
        }
        
        log_debug("Updated barang stock to: $new_stock");
        $update_stmt->close();
        
        // 2. Check if product exists in barang_kasir
        $check_stmt = $conn->prepare("SELECT * FROM barang_kasir WHERE kode_barang = ?");
        $check_stmt->bind_param("s", $product['kode_barang']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $timestamp = date('Y-m-d H:i:s');
        
        if ($check_result->num_rows > 0) {
            // 3a. Update existing product
            $kasir_product = $check_result->fetch_assoc();
            $new_kasir_stock = $kasir_product['stok'] + $quantity;
            
            log_debug("Product exists in cashier, updating stock to: $new_kasir_stock");
            
            $kasir_update_stmt = $conn->prepare("UPDATE barang_kasir SET stok = ?, update_at = ? WHERE id_barangK = ?");
            $kasir_update_stmt->bind_param("isi", $new_kasir_stock, $timestamp, $kasir_product['id_barangK']);
            
            if (!$kasir_update_stmt->execute()) {
                throw new Exception("Failed to update cashier stock: " . $kasir_update_stmt->error);
            }
            
            $kasir_update_stmt->close();
        } else {
            // 3b. Insert new product with direct query
            log_debug("Product doesn't exist in cashier, inserting new record");
            
            // Set default empty string for image
            $kode_barang = $conn->real_escape_string($product['kode_barang']);
            $nama_barang = $conn->real_escape_string($product['nama_barang']);
            $id_jenis = (int)$product['id_jenis'];
            $harga = $conn->real_escape_string($product['harga']);
            $stok = (int)$quantity;
            $gambar = $conn->real_escape_string($product['image'] ?? '');
            $timestamp_esc = $conn->real_escape_string($timestamp);
            
            $insert_query = "INSERT INTO barang_kasir (kode_barang, nama_barang, id_jenis, harga, stok, gambar, created_at, update_at) 
                         VALUES ('$kode_barang', '$nama_barang', $id_jenis, '$harga', $stok, '$gambar', '$timestamp_esc', '$timestamp_esc')";
            
            log_debug("Insert query: $insert_query");
            
            if (!$conn->query($insert_query)) {
                throw new Exception("Failed to insert product into cashier: " . $conn->error);
            }
        }
        
        $check_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        log_debug("Transfer completed successfully");
        
        echo json_encode([
            'success' => true,
            'message' => 'Berhasil memindahkan ' . $quantity . ' item ke kasir'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        log_debug("Error occurred: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ]);
    }
    
    $stmt->close();
}

$conn->close();
log_debug('Script completed');
?>