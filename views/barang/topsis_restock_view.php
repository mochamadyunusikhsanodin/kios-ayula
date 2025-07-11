<?php
// File: topsis_restock_view.php

// Include the TOPSIS function
require_once 'topsis_functions.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "ayula_store";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Default criteria weights
$default_weights = [
    'stock_level' => 0.4,    // Lower stock is more urgent (40% importance)
    'price_value' => 0.3,    // Higher price might indicate higher priority (30% importance)
    'turnover_rate' => 0.3   // Higher turnover rate means faster selling (30% importance)
];

// Check if form was submitted with custom weights
$weights = $default_weights;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_weights'])) {
    $weights = [
        'stock_level' => floatval($_POST['weight_stock']),
        'price_value' => floatval($_POST['weight_price']),
        'turnover_rate' => floatval($_POST['weight_turnover'])
    ];
    
    // Normalize weights to ensure they sum to 1
    $total = array_sum($weights);
    if ($total > 0) {
        foreach ($weights as $key => $value) {
            $weights[$key] = $value / $total;
        }
    } else {
        $weights = $default_weights;
    }
}

// Get restocking priorities using TOPSIS
$restock_priorities = restockWithTOPSIS($conn, $weights);

// Check if any error occurred
$error_message = "";
if (isset($restock_priorities['error'])) {
    $error_message = $restock_priorities['error'];
    $restock_priorities = [];
}

// Calculate total products and those needing restocking (stock < 10)
$total_products = count($restock_priorities);
$low_stock_count = 0;
foreach ($restock_priorities as $product) {
    if ($product['stok'] < 10) {
        $low_stock_count++;
    }
}

// Get current date and time
$current_date = date('Y-m-d H:i:s');

// Page title
$page_title = "Analisis Restok dengan Metode TOPSIS";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=0" />
  <meta name="description" content="POS - Bootstrap Admin Template" />
  <meta
    name="keywords"
    content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects" />
  <meta name="author" content="Dreamguys - Bootstrap Admin Template" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Dreams Pos admin template</title>

  <link
    rel="shortcut icon"
    type="image/x-icon"
    href="/ayula-store/bootstrap/assets/img/favicon.jpg" />

  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap.min.css" />

  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/animate.css" />

  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/select2/css/select2.min.css" />

  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/dataTables.bootstrap4.min.css" />

  <link
    rel="stylesheet"
    href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/all.min.css" />

  <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .priority-high {
            background-color: #ffdddd !important;
        }
        .priority-medium {
            background-color: #ffffdd !important;
        }
        .priority-low {
            background-color: #ddffdd !important;
        }
        .weights-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .dashboard-card {
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        #weightChart {
            max-width: 300px;
            margin: 0 auto;
        }
        .restock-btn {
    background-color: #007bff;  /* Blue background */
    color: white;  /* White text */
    border: none;  /* Remove the border */
    padding: 8px 16px;  /* Adjust padding */
    font-size: 14px;  /* Adjust font size */
    border-radius: 5px;  /* Rounded corners */
    transition: background-color 0.3s ease;  /* Smooth hover effect */
}

.restock-btn:hover {
    background-color: #0056b3;  /* Darker blue on hover */
}
/* Table Header Styling */
.table th {
    color: white !important;  /* Ensures the text color in the header is white */
    background-color: #343a40 !important;  /* Dark background for the header */
}

/* Table Cells Styling */
.table td {
    color: #000000 !important;  /* Text color set to black for the table cells */
}

/* Row Priority Class - High Priority (Red Background) */
.priority-high td {
    background-color: #ffdddd !important;  /* Red background for high priority */
    color: black !important;  /* Black text color */
}

/* Row Priority Class - Medium Priority (Yellow Background) */
.priority-medium td {
    background-color: #ffffdd !important;  /* Yellow background for medium priority */
    color: black !important;  /* Black text color */
}

/* Row Priority Class - Low Priority (Green Background) */
.priority-low td {
    background-color: #ddffdd !important;  /* Green background for low priority */
    color: black !important;  /* Black text color */
}

/* Optional: Styling the action buttons */
.table td button {
    color: white;  /* Make button text white */
    background-color: #007bff;  /* Blue background for buttons */
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
}

.table td button:hover {
    background-color: #0056b3;  /* Darker blue on hover */
}


    </style>
</head>
<body>
    <div id="global-loader">
      <div class="whirly-loader"></div>
    </div>

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
            <span class="user-img"><img src="../../src/img/userprofile.png" alt="">
              <span class="status online"></span></span>
          </a>
          <div class="dropdown-menu menu-drop-user">
            <div class="profilename">
              <div class="profileset">
                <span class="user-img"><img src="../../src/img/userprofile.png" alt="">
                  <span class="status online"></span></span>
                <div class="profilesets">
                  <h6><?php echo $userRole == 'admin' ? 'Admin' : 'Karyawan'; ?></h6>
                  <h5><?php echo htmlspecialchars($displayName); ?></h5>
                </div>
              </div>
              <hr class="m-0" />
              <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                <img src="../../src/img/warning.png" class="me-2" alt="img" /> Laporkan Masalah
              </a>
              <hr class="m-0" />
              <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                  src="../../bootstrap/assets/img/icons/log-out.svg" class="me-2" alt="img" />Keluar</a>
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
              src="../../bootstrap/assets/img/icons/log-out.svg" class="me-2" alt="img" />Keluar</a>
        </div>
      </div>
    </div>
    <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li>
                            <a href="/ayula-store/views/reporttt/report.php"><img src="../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                                    Dashboard</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
                                    Barang</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/ayula-store/views/barang/productlist.php" >Daftar Barang</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Barang</a></li>
                            </ul>
                        </li>
                        <li class="active">
                            <a href="/ayula-store/views/barang/topsis_restock_view.php"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    Analisa Barang</span>
                            </a>    
                        </li>
                        
                         <li class="submenu">
              <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/users1.svg" alt="img" /><span>
                  Pengguna</span>
                <span class="menu-arrow"></span></a>
              <ul>
               
                  <li><a href="/ayula-store/views/users/add-user.php">Pengguna Baru</a></li>
                
                <li><a href="/ayula-store/views/users/">Daftar Pengguna</a></li>
              </ul>
            </li>
                    </ul>
                </div>
            </div>
        </div>
    <div class="page-wrapper">
        <h1 class="mb-4 text-center"><?php echo $page_title; ?></h1>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Produk</h5>
                        <h2><?php echo $total_products; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Produk Stok Rendah</h5>
                        <h2><?php echo $low_stock_count; ?></h2>
                        <small>Stok < 10</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info dashboard-card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Tanggal Analisis</h5>
                        <p><?php echo $current_date; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="weights-form">
                    <h4>Kustomisasi Bobot Kriteria</h4>
                    <form method="POST" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="weight_stock" class="form-label">Bobot Stok:</label>
                            <input type="number" class="form-control" id="weight_stock" name="weight_stock" 
                                   min="0" max="10" step="0.1" value="<?php echo $weights['stock_level'] * 10; ?>">
                            <small class="text-muted">Nilai lebih tinggi = stok rendah lebih penting</small>
                        </div>
                        <div class="col-md-4">
                            <label for="weight_price" class="form-label">Bobot Harga:</label>
                            <input type="number" class="form-control" id="weight_price" name="weight_price" 
                                   min="0" max="10" step="0.1" value="<?php echo $weights['price_value'] * 10; ?>">
                            <small class="text-muted">Nilai lebih tinggi = harga tinggi lebih penting</small>
                        </div>
                        <div class="col-md-4">
                            <label for="weight_turnover" class="form-label">Bobot Perputaran:</label>
                            <input type="number" class="form-control" id="weight_turnover" name="weight_turnover" 
                                   min="0" max="10" step="0.1" value="<?php echo $weights['turnover_rate'] * 10; ?>">
                            <small class="text-muted">Nilai lebih tinggi = penjualan cepat lebih penting</small>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" name="submit_weights" class="btn btn-primary">Terapkan Bobot</button>
                            <button type="button" class="btn btn-secondary" onclick="resetWeights()">Reset ke Default</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        Distribusi Bobot
                    </div>
                    <div class="card-body">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Hasil Analisis TOPSIS</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Prioritas</th>
                                    <th>ID Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Harga</th>
                                   
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restock_priorities as $product): 
                                    // Determine priority class based on TOPSIS score
                                    $priority_class = '';
                                    if ($product['topsis_score'] > 0.7) {
                                        $priority_class = 'priority-high';
                                    } elseif ($product['topsis_score'] > 0.4) {
                                        $priority_class = 'priority-medium';
                                    } else {
                                        $priority_class = 'priority-low';
                                    }
                                ?>
                                    <tr class="<?php echo $priority_class; ?>">
                                        <td><?php echo $product['rank']; ?></td>
                                        <td><?php echo $product['id_barang']; ?></td>
                                        <td><?php echo $product['nama_barang']; ?></td>
                                        <td><?php echo $product['stok']; ?></td>
                                        <td>Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></td>
                                        
                                        <td>
                                            <button class=" restock-btn" onclick="openRestockModal(<?php echo $product['id_barang']; ?>, '<?php echo $product['nama_barang']; ?>')">
                                                Restok
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Export Options -->
            
        <?php endif; ?>
    </div>
    
   <div class="modal fade" id="restockModal" tabindex="-1" aria-labelledby="restockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restockModalLabel">Restok Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="restockForm">
                    <input type="hidden" id="productId" name="productId">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="productName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="restockAmount" class="form-label">Jumlah Restok</label>
                        <input type="number" class="form-control" id="restockAmount" name="restockAmount" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="supplierNote" class="form-label">Catatan</label>
                        <textarea class="form-control" id="supplierNote" name="supplierNote" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitRestock()">Proses Restok</button>
            </div>
        </div>
    </div>
</div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
  <script src="/ayula-store/bootstrap/assets/js/jquery-3.6.0.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/js/feather.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/js/jquery.slimscroll.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/js/jquery.dataTables.min.js"></script>
  <script src="/ayula-store/bootstrap/assets/js/dataTables.bootstrap4.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/js/bootstrap.bundle.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/plugins/select2/js/select2.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
  <script src="/ayula-store/bootstrap/assets/plugins/sweetalert/sweetalerts.min.js"></script>

  <script src="/ayula-store/bootstrap/assets/js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize modal
        const restockModal = new bootstrap.Modal(document.getElementById('restockModal'));
        
        // Open restock modal
        function openRestockModal(productId, productName) {
            document.getElementById('productId').value = productId;
            document.getElementById('productName').value = productName;
            document.getElementById('restockAmount').value = '10'; // Default value
            document.getElementById('supplierNote').value = '';
            restockModal.show();
        }
        
        // Submit restock form
        function submitRestock() {
            const productId = document.getElementById('productId').value;
            const amount = document.getElementById('restockAmount').value;
            const note = document.getElementById('supplierNote').value;
            
            // Here you would typically send an AJAX request to update the database
            alert(`Produk ID: ${productId} akan direstok dengan jumlah: ${amount}`);
            
            // In a real implementation, you would use fetch or XMLHttpRequest to send to server
            // Example:
            /*
            fetch('process_restock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `productId=${productId}&amount=${amount}&note=${encodeURIComponent(note)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Restok berhasil!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses restok.');
            });
            */
            
            restockModal.hide();
        }
        
        // Reset weights to default
        function resetWeights() {
            document.getElementById('weight_stock').value = '4';
            document.getElementById('weight_price').value = '3';
            document.getElementById('weight_turnover').value = '3';
        }
        
        // Export table to CSV/PDF
        function exportTable(format) {
            alert(`Mengekspor tabel ke format ${format.toUpperCase()}`);
            // Implement actual export functionality here
        }
        
        // Print table
        function printTable() {
            window.print();
        }
        
        // Initialize weight chart
        const ctx = document.getElementById('weightChart').getContext('2d');
        const weightChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Stok', 'Harga', 'Perputaran'],
                datasets: [{
                    data: [
                        <?php echo $weights['stock_level']; ?>, 
                        <?php echo $weights['price_value']; ?>, 
                        <?php echo $weights['turnover_rate']; ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue || '';
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
          function submitRestock() {
        // Get the values from the modal form
        var productName = document.getElementById('productName').value;
        var restockAmount = document.getElementById('restockAmount').value;
        var supplierNote = document.getElementById('supplierNote').value;

        // Validate form fields before proceeding
        if (!productName || !restockAmount || !supplierNote) {
            alert('Please fill out all fields.');
            return;
        }

        // Prepare the message to be sent via WhatsApp
        var message = "Restok Produk:\n";
        message += "Nama Produk: " + productName + "\n";
        message += "Jumlah Restok: " + restockAmount + "\n";
        message += "Catatan : " + supplierNote;

        // Encode the message for URL (using encodeURIComponent for proper encoding)
        var encodedMessage = encodeURIComponent(message);

        // Define the phone number for WhatsApp (replace with your desired phone number)
        var phoneNumber = "+6287857242169"; // Replace with the recipient's phone number (without + sign)

        // WhatsApp API URL
        var whatsappURL = "https://wa.me/" + phoneNumber + "?text=" + encodedMessage;

        // Open WhatsApp with the message
        window.open(whatsappURL, "_blank");
    }
    </script>
</body>
</html>