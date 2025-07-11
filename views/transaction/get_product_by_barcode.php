<?php
// Pencegahan error tampil di output (sangat penting untuk AJAX)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Pastikan output hanya JSON, bukan error PHP
header('Content-Type: application/json');

// Include transaction functions
require_once 'configtrans.php';

// Fungsi untuk mengirim respons JSON dan keluar
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

// Tangkap semua error untuk mencegah tampil di output
function handleErrors($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error ($errno): $errstr in $errfile on line $errline");
    sendResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan internal. Silakan coba lagi.',
        'product' => null,
        'cart_html' => '',
        'cart_totals' => [
            'items' => 0,
            'total' => 0,
            'formatted_total' => '0'
        ]
    ]);
    return true;
}
set_error_handler('handleErrors');

// Try-catch untuk menangkap semua exception
try {
    // Pastikan sesi dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Log request untuk debugging
    error_log("Barcode scan request received: " . json_encode($_POST));

    // Initialize response
    $response = [
        'success' => false,
        'message' => 'Barcode tidak valid',
        'product' => null,
        'cart_html' => '',
        'cart_totals' => [
            'items' => 0,
            'total' => 0,
            'formatted_total' => '0'
        ]
    ];

    // Check if barcode is provided
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
        $barcode = trim($_POST['barcode']);
        
        // Validate barcode
        if (empty($barcode)) {
            $response['message'] = "Barcode tidak boleh kosong";
            sendResponse($response);
        }
        
        // Get product by barcode (kode_barang)
        $product = getProductByBarcode($barcode);
        
        if (!$product) {
            $response['message'] = "Produk tidak ditemukan untuk barcode: " . htmlspecialchars($barcode);
            sendResponse($response);
        }
        
        // Check stock availability
        if ($product['stok'] <= 0) {
            $response['message'] = "Produk " . htmlspecialchars($product['nama_barang']) . " stok habis";
            sendResponse($response);
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
            error_log("Cart initialized");
        }
        
        $productId = $product['id_barangK'];
        $quantity = 1; // Default quantity is 1
        
        // Check if product already in cart, update quantity if exists
        $exists = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                // Only add if it won't exceed stock
                if ($item['quantity'] < $product['stok']) {
                    $_SESSION['cart'][$key]['quantity'] += 1;
                } else {
                    // Stock limit reached
                    $response['success'] = false;
                    $response['message'] = "Tidak dapat menambahkan unit lagi. Batas stok tercapai.";
                    sendResponse($response);
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
        
        // Set success response
        $response['success'] = true;
        $response['message'] = 'Produk ditambahkan ke keranjang';
        $response['product'] = [
            'id' => $product['id_barangK'],
            'name' => $product['nama_barang'],
            'code' => $product['kode_barang'],
            'price' => $product['harga'],
            'stock' => $product['stok']
        ];
        
        // Generate cart HTML
        $response['cart_html'] = generateCartHTML();
        
        // Calculate cart totals
        $cartItems = count($_SESSION['cart']);
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        $response['cart_totals'] = [
            'items' => $cartItems,
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.')
        ];
        
        // Log cart state for debugging
        error_log("Cart updated. Items: " . $cartItems . ", Total: " . $total);
    }

    // Send the response
    sendResponse($response);

} catch (Exception $e) {
    // Log exception
    error_log("Exception in get_product_by_barcode.php: " . $e->getMessage());
    
    // Return error response
    sendResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'product' => null,
        'cart_html' => '',
        'cart_totals' => [
            'items' => 0,
            'total' => 0,
            'formatted_total' => '0'
        ]
    ]);
}

/**
 * Generate HTML for cart items
 * 
 * @return string HTML content for cart
 */
function generateCartHTML() {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $html = '';
        foreach ($_SESSION['cart'] as $index => $item) {
            $html .= '
            <ul class="product-lists">
                <li>
                    <div class="productimg">
                        <div class="productimgs">
                            <img src="../../bootstrap/assets/img/product/product30.jpg" alt="img" />
                        </div>
                        <div class="productcontet">
                            <h4>' . htmlspecialchars($item['name']) . '</h4>
                            <div class="productlinkset">
                                <h5>' . htmlspecialchars($item['code']) . '</h5>
                            </div>
                            <div class="increment-decrement">
                                <div class="input-groups">
                                    <form method="post" action="index.php" class="cart-item-form">
                                        <input type="hidden" name="product_id" value="' . intval($item['id']) . '">
                                        <input type="hidden" name="update_cart" value="1">
                                        <div class="quantity-control-container">
                                            <button type="button" class="btn btn-sm btn-light quantity-btn decrement">-</button>
                                            <input type="text" name="quantity" value="' . intval($item['quantity']) . '" 
                                                class="quantity-field form-control mx-1" 
                                                style="width: 45px; text-align: center;"
                                                data-max-stock="' . intval($item['max_stock']) . '" />
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
                <li>Rp. ' . number_format($item['price'] * $item['quantity'], 0, ',', '.') . '</li>
                <li>
                    <a href="javascript:void(0);" class="delete-cart-item" data-index="' . $index . '">
                        <img src="../../bootstrap/assets/img/icons/delete-2.svg" alt="img" />
                    </a>
                </li>
            </ul>';
        }
        
        // Add script to enable functionalities
        $html .= '
        <script>
            // Setup event handlers for the new cart items
            if (typeof setupQuantityControls === "function") {
                setupQuantityControls();
            }
            if (typeof setupCartDeletionConfirmation === "function") {
                setupCartDeletionConfirmation();
            }
            
            // Dispatch custom event to notify that cart was updated
            document.dispatchEvent(new CustomEvent("cartUpdated"));
        </script>';
        
        return $html;
    } else {
        return '<p class="text-center">Keranjang Anda kosong.</p>';
    }
}