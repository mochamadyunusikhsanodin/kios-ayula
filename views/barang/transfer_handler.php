<?php
// transfer_handler.php
// File to handle individual and bulk product transfers to cashier

// Koneksi ke database
$servername = "localhost";
$username = "root"; // Sesuaikan dengan username database kamu
$password = ""; // Sesuaikan dengan password database kamu
$database = "ayula_store"; // Sesuaikan dengan nama database kamu

$conn = new mysqli($servername, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Koneksi gagal: " . $conn->connect_error
    ]));
}

// Fungsi untuk memvalidasi data yang diterima
function validateData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle single product transfer
if (isset($_POST['action']) && $_POST['action'] == 'transfer_product') {
    // Ambil data dari POST
    $id_barang = validateData($_POST['id_barang']);
    $quantity = intval($_POST['quantity']);
    
    // Validasi input
    if (empty($id_barang) || $quantity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak valid.'
        ]);
        exit;
    }
    
    // Check if stock is sufficient
    $sql = "SELECT stok FROM barang WHERE id_barang = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_barang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Barang tidak ditemukan.'
        ]);
        $stmt->close();
        exit;
    }
    
    $row = $result->fetch_assoc();
    $current_stock = $row['stok'];
    
    if ($quantity > $current_stock) {
        echo json_encode([
            'success' => false,
            'message' => 'Jumlah melebihi stok tersedia.'
        ]);
        $stmt->close();
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update stock in barang table
        $new_stock = $current_stock - $quantity;
        $update_sql = "UPDATE barang SET stok = ? WHERE id_barang = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $new_stock, $id_barang);
        $update_stmt->execute();
        
        // TODO: Insert data to cashier table or perform other actions as needed
        // This will depend on your specific requirements for transferring to cashier
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Berhasil memindahkan ' . $quantity . ' item ke kasir.'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ]);
    }
    
    $stmt->close();
    if (isset($update_stmt)) {
        $update_stmt->close();
    }
}

// Handle bulk product transfers
if (isset($_POST['action']) && $_POST['action'] == 'bulk_transfer_products') {
    // Get product IDs and quantities
    $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    
    // Validate input
    if (empty($product_ids) || empty($quantities) || count($product_ids) != count($quantities)) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak valid.'
        ]);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        $success_count = 0;
        $error_messages = [];
        
        // Process each product
        for ($i = 0; $i < count($product_ids); $i++) {
            $id_barang = validateData($product_ids[$i]);
            $quantity = intval($quantities[$i]);
            
            if (empty($id_barang) || $quantity <= 0) {
                $error_messages[] = "Data tidak valid untuk produk #$i";
                continue;
            }
            
            // Check if stock is sufficient
            $sql = "SELECT stok FROM barang WHERE id_barang = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id_barang);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $error_messages[] = "Barang dengan ID $id_barang tidak ditemukan";
                $stmt->close();
                continue;
            }
            
            $row = $result->fetch_assoc();
            $current_stock = $row['stok'];
            
            if ($quantity > $current_stock) {
                $error_messages[] = "Jumlah melebihi stok tersedia untuk produk ID $id_barang";
                $stmt->close();
                continue;
            }
            
            // Update stock in barang table
            $new_stock = $current_stock - $quantity;
            $update_sql = "UPDATE barang SET stok = ? WHERE id_barang = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("is", $new_stock, $id_barang);
            $update_stmt->execute();
            $update_stmt->close();
            $stmt->close();
            
            // TODO: Insert data to cashier table or perform other actions as needed
            // This will depend on your specific requirements for transferring to cashier
            
            $success_count++;
        }
        
        if ($success_count == 0) {
            // If no products were successfully processed, rollback and return error
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada produk yang berhasil dipindahkan. ' . implode('; ', $error_messages)
            ]);
        } else {
            // Commit the transaction if at least one product was successfully processed
            $conn->commit();
            
            if (count($error_messages) > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "Berhasil memindahkan $success_count produk ke kasir. Beberapa produk gagal: " . implode('; ', $error_messages)
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => "Berhasil memindahkan $success_count produk ke kasir."
                ]);
            }
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ]);
    }
}

// Close connection
$conn->close();
?>