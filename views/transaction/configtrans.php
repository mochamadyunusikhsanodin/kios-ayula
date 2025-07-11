<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the main database connection
include('../../routes/db_conn.php');

// Check for database connection
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    die("Database connection failed. Please try again later.");
}

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php-errors.log');

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return JSON error for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.', 'redirect' => '/ayula-store/views/login/']);
        exit;
    } else {
        // Redirect for regular requests
        header('Location: /ayula-store/views/login/');
        exit;
    }
}

// Set variables for use throughout the application
$userRole = $_SESSION['role'] ?? ''; // 'user' or 'admin'
$username = $_SESSION['username'] ?? 'Unknown User';
$isAdmin = ($userRole === 'admin');

/**
 * Get all products or filter by product type and/or search query
 * 
 * @param int|null $typeId Optional product type ID to filter by
 * @param string|null $searchQuery Optional search term to filter product names
 * @return array List of products
 */
function getProducts($typeId = null, $searchQuery = null) {
    global $conn;
    
    try {
        $sql = "SELECT b.*, j.nama_jenis 
                FROM barang_kasir b
                JOIN jenis_barang j ON b.id_jenis = j.id_jenis
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Add type filter if specified
        if ($typeId) {
            $sql .= " AND b.id_jenis = ?";
            $params[] = $typeId;
            $types .= "i"; // integer parameter
        }
        
        // Add search filter if specified
        if ($searchQuery && !empty($searchQuery)) {
            $sql .= " AND (b.nama_barang LIKE ? OR b.kode_barang LIKE ?)";
            $params[] = "%" . $searchQuery . "%";
            $params[] = "%" . $searchQuery . "%";
            $types .= "ss"; // two string parameters
        }
        
        $sql .= " ORDER BY b.nama_barang ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            error_log("Query preparation failed: " . mysqli_error($conn));
            return [];
        }
        
        // Bind parameters dynamically if we have any
        if (!empty($params)) {
            $bindParams = array(&$stmt, &$types);
            foreach($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bindParams);
        }
        
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $products;
    } catch (Exception $e) {
        error_log("Exception in getProducts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product details by ID
 * 
 * @param int $productId The product ID
 * @return array|null Product details or null if not found
 */
function getProductById($productId) {
    global $conn;
    
    try {
        $stmt = mysqli_prepare($conn, "SELECT b.*, j.nama_jenis 
                                      FROM barang_kasir b 
                                      LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis 
                                      WHERE b.id_barangK = ?");
        
        if (!$stmt) {
            error_log("Query preparation failed: " . mysqli_error($conn));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $product;
    } catch (Exception $e) {
        error_log("Exception in getProductById: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all product types (jenis_barang)
 * 
 * @return array List of product types
 */
function getProductTypes() {
    global $conn;
    
    try {
        $query = "SELECT * FROM jenis_barang ORDER BY nama_jenis";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            error_log("Query failed: " . mysqli_error($conn));
            return [];
        }
        
        $types = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
        
        return $types;
    } catch (Exception $e) {
        error_log("Exception in getProductTypes: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new transaction
 * 
 * @param array $items Array of items with product_id and quantity
 * @param float $total Total amount
 * @param float $cashAmount Cash amount received from customer
 * @param float $changeAmount Change amount to be returned to customer
 * @return array Result with success status and transaction ID
 */
function createTransaction($items, $total, $cashAmount = 0, $changeAmount = 0) {
    global $conn;
    
    if (empty($items)) {
        return [
            'success' => false,
            'message' => 'No items in cart'
        ];
    }
    
    $totalItems = array_sum(array_column($items, 'quantity'));
    
    // Generate transaction code (e.g., TRX-20250416-001)
    $transactionCode = 'TRX-' . date('Ymd') . '-' . rand(100, 999);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert into transaksi table with cash_amount and change_amount
        $stmt = mysqli_prepare($conn, "INSERT INTO transaksi (kode_transaksi, total_item, total, metode_pembayaran, cash_amount, change_amount) VALUES (?, ?, ?, 'Cash', ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare transaction statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "siddd", $transactionCode, $totalItems, $total, $cashAmount, $changeAmount);
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            throw new Exception("Failed to execute transaction statement: " . mysqli_stmt_error($stmt));
        }
        
        // Get the ID of the transaction just created
        $transactionId = mysqli_insert_id($conn);
        
        if (!$transactionId) {
            throw new Exception("Failed to get transaction ID");
        }
        
        // Insert each item into detail_transaksi
        foreach ($items as $item) {
            $product = getProductById($item['product_id']);
            
            if (!$product) {
                throw new Exception("Product not found: ID=" . $item['product_id']);
            }
            
            $itemTotal = $product['harga'] * $item['quantity'];
            
            $stmt = mysqli_prepare($conn, "INSERT INTO detail_transaksi (id_transaksi, id_barangK, jumlah, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare detail statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "iiddd", $transactionId, $item['product_id'], $item['quantity'], $product['harga'], $itemTotal);
            $executed = mysqli_stmt_execute($stmt);
            
            if (!$executed) {
                throw new Exception("Failed to execute detail statement: " . mysqli_stmt_error($stmt));
            }
            
            // Update inventory (reduce stock)
            $stmt = mysqli_prepare($conn, "UPDATE barang_kasir SET stok = stok - ? WHERE id_barangK = ? AND stok >= ?");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare stock update statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "iii", $item['quantity'], $item['product_id'], $item['quantity']);
            $executed = mysqli_stmt_execute($stmt);
            
            if (!$executed) {
                throw new Exception("Failed to execute stock update statement: " . mysqli_stmt_error($stmt));
            }
            
            // Check if stock was actually updated (prevents overselling)
            if (mysqli_affected_rows($conn) == 0) {
                throw new Exception("Insufficient stock for product: " . $product['nama_barang']);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'transaction_code' => $transactionCode,
            'cash_amount' => $cashAmount,
            'change_amount' => $changeAmount
        ];
    } catch (Exception $e) {
        // Rollback in case of error
        mysqli_rollback($conn);
        error_log("Transaction failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get transaction by ID
 * 
 * @param int $transactionId The transaction ID
 * @return array|null Transaction details or null if not found
 */
function getTransactionById($transactionId) {
    global $conn;
    
    try {
        // Get transaction details
        $stmt = mysqli_prepare($conn, "SELECT * FROM transaksi WHERE id_transaksi = ?");
        
        if (!$stmt) {
            error_log("Query preparation failed: " . mysqli_error($conn));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $transactionId);
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $transaction = mysqli_fetch_assoc($result);
        
        if (!$transaction) {
            return null;
        }
        
        // Get transaction items
        $stmt = mysqli_prepare($conn, "
            SELECT dt.*, b.nama_barang, b.kode_barang, j.nama_jenis
            FROM detail_transaksi dt
            JOIN barang_kasir b ON dt.id_barangK = b.id_barangK
            JOIN jenis_barang j ON b.id_jenis = j.id_jenis
            WHERE dt.id_transaksi = ?
        ");
        
        if (!$stmt) {
            error_log("Query preparation failed: " . mysqli_error($conn));
            return $transaction; // Return transaction without items
        }
        
        mysqli_stmt_bind_param($stmt, "i", $transactionId);
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return $transaction; // Return transaction without items
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
        
        $transaction['items'] = $items;
        
        return $transaction;
    } catch (Exception $e) {
        error_log("Exception in getTransactionById: " . $e->getMessage());
        return null;
    }
}

/**
 * Get recent transactions
 * 
 * @param int $limit Maximum number of transactions to return
 * @return array List of recent transactions
 */
function getRecentTransactions($limit = 10) {
    global $conn;
    
    try {
        $query = "SELECT * FROM transaksi ORDER BY created_at DESC LIMIT " . intval($limit);
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            error_log("Query failed: " . mysqli_error($conn));
            return [];
        }
        
        $transactions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    } catch (Exception $e) {
        error_log("Exception in getRecentTransactions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get current user information
 * 
 * @return array|null User details or null if not found
 */
function getCurrentUser() {
    global $conn;
    
    try {
        if (isset($_SESSION['user_id'])) {
            $stmt = mysqli_prepare($conn, "SELECT id_kasir, username, role, phone FROM kasir WHERE id_kasir = ?");
            
            if (!$stmt) {
                error_log("Query preparation failed: " . mysqli_error($conn));
                return null;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            $executed = mysqli_stmt_execute($stmt);
            
            if (!$executed) {
                error_log("Query execution failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return null;
            }
            
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            return $user;
        }
        return null;
    } catch (Exception $e) {
        error_log("Exception in getCurrentUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Get product by barcode (kode_barang)
 * 
 * @param string $barcode The product barcode
 * @return array|null Product details or null if not found
 */
function getProductByBarcode($barcode) {
    global $conn;
    
    try {
        $stmt = mysqli_prepare($conn, "SELECT b.*, j.nama_jenis FROM barang_kasir b 
                                     LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis 
                                     WHERE b.kode_barang = ?");
        if (!$stmt) {
            error_log("Query preparation failed: " . mysqli_error($conn));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $barcode);
        $executed = mysqli_stmt_execute($stmt);
        
        if (!$executed) {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Log untuk debugging
        if ($product) {
            error_log("Product found: ID=" . $product['id_barangK'] . ", Name=" . $product['nama_barang']);
        } else {
            error_log("Product not found for barcode: " . $barcode);
        }
        
        return $product;
    } catch (Exception $e) {
        error_log("Exception in getProductByBarcode: " . $e->getMessage());
        return null;
    }
}