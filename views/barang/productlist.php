<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Ambil informasi user yang sedang login
$userRole = $_SESSION['role']; // 'user' atau 'admin'
$username = $_SESSION['username']; // Menambahkan username dari session

// Jika username adalah root, tampilkan nama yang lebih presentable
$displayName = ($username === 'root') ? 'Admin' : $username;

// Cek apakah session 'user_id' ada, yang berarti pengguna sudah login
if (!isset($_SESSION['user_id'])) {
  // Jika session tidak ada, arahkan pengguna ke halaman login
  header("Location: /ayula-store/index.php");
  exit();
}

// Database connection
include('../../routes/db_conn.php');

// Ambil parameter search jika ada
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mengambil data dari tabel barang
$sql = "SELECT b.id_barang, b.nama_barang, jb.nama_jenis, b.stok, b.harga, b.kode_barang, b.image 
        FROM barang b 
        JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis";

if (!empty($search)) {
    $sql .= " WHERE b.nama_barang LIKE ? OR jb.nama_jenis LIKE ?";
}

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();

// Delete logic
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Check if the ID exists before attempting to delete
    $checkSql = "SELECT id_barang FROM barang WHERE id_barang = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Proceed with deletion if the item exists
        $deleteSql = "DELETE FROM barang WHERE id_barang = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $id);
        if ($deleteStmt->execute()) {
            // After deletion, enable foreign key checks again
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            header("Location: productlist.php?success_delete=1");
            exit();
        } else {
            // If deletion failed, re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            header("Location: productlist.php?success_delete=0");
            exit();
        }
    } else {
        // Item does not exist
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: productlist.php?success_delete=0");
        exit();
    }
}?>
<!DOCTYPE html>
<html lang="en">

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
                                <li><a href="/ayula-store/views/barang/productlist.php" class="active">Daftar Barang</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Barang</a></li>
                                
                            </ul>
                        </li>
                        <li >
                            <a href="/ayula-store/views/barang/topsis_restock_view.php"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    Analisa Barang</span>
                            </a>    
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
        <div class="page-header">
          <div class="page-title">
            <h4>Daftar Barang</h4>
            <h6>Kelola Barang</h6>
          </div>
          <div class="page-btn">
            <a href="addproduct.php" class="btn btn-added"><img
                src="/ayula-store/bootstrap/assets/img/icons/plus.svg"
                alt="img"
                class="me-1" />Tambah Barang</a>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="table-top">
              <div class="search-set">
                <div class="search-path">
                  <a class="btn btn-filter" id="filter_search">
                    <img src="/ayula-store/bootstrap/assets/img/icons/filter.svg" alt="img" />
                    <span><img src="/ayula-store/bootstrap/assets/img/icons/closes.svg" alt="img" /></span>
                  </a>
                </div>
                <div class="search-input" disable>
                  
                </div>
              </div>
              <div class="wordset">
                <ul>
                  <li>
                    <button id="bulkTransferBtn" class="btn btn-primary" disabled>
                      <img src="/ayula-store/bootstrap/assets/img/icons/transfer1.svg" alt="transfer" class="me-1" />
                      Pindah ke Kasir
                    </button>
                  </li>
                  <!-- New button for adding to report -->
                  <li>
                    <button id="addToReportBtn" class="btn btn-success" disabled>
                      <img src="/ayula-store/bootstrap/assets/img/icons/excel.svg" alt="report" class="me-1" />
                      Tambah ke Laporan
                    </button>
                  </li>
                  
                </ul>
              </div>
            </div>

            <div class="card mb-0" id="filter_inputs">
              <div class="card-body pb-0">
                <div class="row">
                  <div class="col-lg-12 col-sm-12">
                    <div class="row">
                      <div class="col-lg col-sm-6 col-12">
                        <div class="form-group">
                          <select class="select">
                            <option>Choose Product</option>
                            <option>Macbook pro</option>
                            <option>Orange</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-lg col-sm-6 col-12">
                        <div class="form-group">
                          <select class="select">
                            <option>Choose Category</option>
                            <option>Computers</option>
                            <option>Fruits</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-lg col-sm-6 col-12">
                        <div class="form-group">
                          <select class="select">
                            <option>Choose Sub Category</option>
                            <option>Computer</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="col-lg col-sm-6 col-12">
                        <div class="form-group">
                          <select class="select">
                            <option>Price</option>
                            <option>150.00</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-lg-1 col-sm-6 col-12">
                        <div class="form-group">
                          <a class="btn btn-filters ms-auto"><img
                              src="/ayula-store/bootstrap/assets/img/icons/search-whites.svg"
                              alt="img" /></a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

           
            <!-- Tabel Data Barang -->
            <div class="table-responsive">
                <table class="table datanew">
                    <thead>
                        <tr>
                            <th>
                                <label class="checkboxs">
                                    <input type="checkbox" id="select-all">
                                    <span class="checkmarks"></span>
                                </label>
                            </th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Jenis</th>
                            
                            <th>Stok</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>
                                        <label class='checkboxs'>
                                            <input type='checkbox' class='product-checkbox' 
                                                data-id='{$row['id_barang']}' 
                                                data-name='{$row['nama_barang']}' 
                                                data-stock='{$row['stok']}' 
                                                data-kode='{$row['kode_barang']}' 
                                                data-harga='{$row['harga']}' 
                                                data-image='{$row['image']}'>
                                            <span class='checkmarks'></span>
                                        </label>
                                    </td>
                                    <td>{$row['kode_barang']}</td>
                                    <td>{$row['nama_barang']}</td>
                                    <td>{$row['nama_jenis']}</td>
                                    <td>{$row['stok']}</td>
                                    <td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                                    <td>
                                        <a class='me-3' href='product_details.php?id={$row['id_barang']}'>
                                            <img src='/ayula-store/bootstrap/assets/img/icons/eye.svg' alt='Lihat Detail' />
                                        </a>
                                        <a class='me-3' href='editproduct.php?id={$row['id_barang']}'>
                                            <img src='/ayula-store/bootstrap/assets/img/icons/edit.svg' alt='Edit' />
                                        </a>
                                        <a class='me-3' href='javascript:void(0);' onclick='confirmDelete({$row['id_barang']})'>
                                            <img src='/ayula-store/bootstrap/assets/img/icons/delete.svg' alt='Hapus' />
                                        </a>
                                        <a href='javascript:void(0);' onclick='openTransferModal(\"{$row['id_barang']}\", \"{$row['nama_barang']}\", {$row['stok']})'>
                                            <img src='/ayula-store/bootstrap/assets/img/icons/transfer1.svg' alt='Pindah ke Kasir' title='Pindah ke Kasir' />
                                        </a>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>Tidak ada data barang</td></tr>";
                    }
                    ?>

                    </tbody>
                </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
 <!-- Transfer Modal (Single Product) -->
 <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="transferModalLabel">Pindah Barang ke Kasir</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="transferForm">
            <input type="hidden" id="transferProductId" name="id_barang">
            <div class="mb-3">
              <label for="productName" class="form-label">Nama Barang</label>
              <input type="text" class="form-control" id="productName" readonly>
            </div>
            <div class="mb-3">
              <label for="availableStock" class="form-label">Stok Tersedia</label>
              <input type="text" class="form-control" id="availableStock" readonly>
            </div>
            <div class="mb-3">
              <label for="transferQuantity" class="form-label">Jumlah Pindah</label>
              <input type="number" class="form-control" id="transferQuantity" name="quantity" min="1" value="1" required>
            </div>
            <div class="alert alert-danger" id="transferError" style="display: none;"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="submitTransfer">Pindahkan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bulk Transfer Modal -->
  <div class="modal fade" id="bulkTransferModal" tabindex="-1" aria-labelledby="bulkTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bulkTransferModalLabel">Pindah Beberapa Barang ke Kasir</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="bulkTransferForm">
            <div class="table-responsive">
              <table class="table" id="selectedProductsTable">
                <thead>
                  <tr>
                    <th>ID Barang</th>
                    <th>Nama Barang</th>
                    <th>Stok Tersedia</th>
                    <th>Jumlah Pindah</th>
                  </tr>
                </thead>
                <tbody id="selectedProductsList">
                  <!-- Will be populated dynamically with JavaScript -->
                </tbody>
              </table>
            </div>
            <div class="alert alert-danger" id="bulkTransferError" style="display: none;"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="submitBulkTransfer">Pindahkan Semua</button>
        </div>
      </div>
    </div>
  </div>

 <!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportModalLabel">Tambah ke Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="reportForm" enctype="multipart/form-data">
          <!-- Selected Products -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Data Barang</h5>
              <span class="text-muted small">Klik ikon <i class="fas fa-times text-danger"></i> untuk menghapus item</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered" id="reportProductsTable">
                <thead class="table-light">
                  <tr>
                    <th width="15%">ID Barang</th>
                    <th width="35%">Nama Barang</th>
                    <th width="20%">Jumlah</th>
                    <th width="20%">Harga</th>
                    <th width="10%">Aksi</th>
                  </tr>
                </thead>
                <tbody id="reportProductsList">
                  <!-- Will be populated dynamically -->
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Nota/Receipt Image Upload -->
          <div class="mb-3">
            <label for="receiptImage" class="form-label">Upload Gambar Nota</label>
            <input type="file" class="form-control" id="receiptImage" name="receipt_image" accept="image/*" required>
            <small class="text-muted">Upload gambar nota/kwitansi sebagai bukti transaksi untuk semua barang yang dipilih.</small>
          </div>
          
          <div class="alert alert-danger" id="reportError" style="display: none;"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="submitReport">Simpan</button>
      </div>
    </div>
  </div>
</div>

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


// Function to confirm product deletion
// Function to confirm product deletion
function confirmDelete(id) {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "productlist.php?delete_id=" + id;
        }
    });
}

// Check for success/failure message for both add and delete
const urlParams = new URLSearchParams(window.location.search);

// Check if success_add is present (for adding a product)
const successAdd = urlParams.get('success_add');
if (successAdd === '1') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Barang berhasil ditambahkan!',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_add query from URL
    });
} else if (successAdd === '0') {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Barang gagal ditambahkan!',
        showConfirmButton: true
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_add query from URL
    });
}

// Check if success_delete is present (for deleting a product)
const successDelete = urlParams.get('success_delete');
if (successDelete === '1') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Barang berhasil dihapus!',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_delete query from URL
    });
} else if (successDelete === '0') {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Barang gagal dihapus!',
        showConfirmButton: true
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_delete query from URL
    });
}
// Check if success_edit is present (for editing a product)
const successEdit = urlParams.get('success_edit');
if (successEdit === '1') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Barang berhasil diedit!',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_edit query from URL
    });
} else if (successEdit === '0') {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Barang gagal diedit!',
        showConfirmButton: true
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname); // Remove success_edit query from URL
    });
}

// Function to open transfer modal for single product
function openTransferModal(id, name, stock) {
    // Set values in the modal
    document.getElementById('transferProductId').value = id;
    document.getElementById('productName').value = name;
    document.getElementById('availableStock').value = stock;
    document.getElementById('transferQuantity').value = 1;
    document.getElementById('transferQuantity').max = stock;
    
    // Hide any previous error messages
    document.getElementById('transferError').style.display = 'none';
    
    // Open the modal
    const transferModal = new bootstrap.Modal(document.getElementById('transferModal'));
    transferModal.show();
}

// Handle select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const isChecked = this.checked;
    const checkboxes = document.querySelectorAll('.product-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    // Update bulk buttons state
    updateBulkButtonsState();
});

// Handle individual checkbox changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('product-checkbox')) {
        updateBulkButtonsState();
        
        // Check if "select all" should be checked or unchecked
        const totalCheckboxes = document.querySelectorAll('.product-checkbox').length;
        const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked').length;
        document.getElementById('select-all').checked = (totalCheckboxes === checkedCheckboxes);
        document.getElementById('select-all').indeterminate = (checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
    }
});

// Function to update bulk buttons state
function updateBulkButtonsState() {
    const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkTransferBtn = document.getElementById('bulkTransferBtn');
    const addToReportBtn = document.getElementById('addToReportBtn');
    
    if (checkedCheckboxes.length > 0) {
        bulkTransferBtn.removeAttribute('disabled');
        addToReportBtn.removeAttribute('disabled');
        
        // Update the button text based on selection count
        if (checkedCheckboxes.length === 1) {
            bulkTransferBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/transfer1.svg" alt="transfer" class="me-1" /> Pindah ke Kasir`;
            addToReportBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/excel.svg" alt="report" class="me-1" /> Tambah ke Laporan`;
        } else {
            bulkTransferBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/transfer1.svg" alt="transfer" class="me-1" /> Pindah ${checkedCheckboxes.length} Barang ke Kasir`;
            addToReportBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/excel.svg" alt="report" class="me-1" /> Tambah ${checkedCheckboxes.length} Barang ke Laporan`;
        }
    } else {
        bulkTransferBtn.setAttribute('disabled', 'disabled');
        addToReportBtn.setAttribute('disabled', 'disabled');
        
        // Reset button text
        bulkTransferBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/transfer1.svg" alt="transfer" class="me-1" /> Pindah ke Kasir`;
        addToReportBtn.innerHTML = `<img src="/ayula-store/bootstrap/assets/img/icons/excel.svg" alt="report" class="me-1" /> Tambah ke Laporan`;
    }
}

// Handle single product transfer form submission
document.getElementById('submitTransfer').addEventListener('click', function() {
    const id_barang = document.getElementById('transferProductId').value;
    const quantity = document.getElementById('transferQuantity').value;
    const availableStock = parseInt(document.getElementById('availableStock').value);
    
    // Validate quantity
    if (quantity <= 0) {
        document.getElementById('transferError').textContent = 'Jumlah harus lebih dari 0';
        document.getElementById('transferError').style.display = 'block';
        return;
    }
    
    if (quantity > availableStock) {
        document.getElementById('transferError').textContent = 'Jumlah melebihi stok tersedia';
        document.getElementById('transferError').style.display = 'block';
        return;
    }
    
    // Show loading indicator
    document.getElementById('transferError').style.display = 'none';
    document.getElementById('submitTransfer').disabled = true;
    document.getElementById('submitTransfer').textContent = 'Sedang Memproses...';
    
    // Use FormData for simpler approach
    const formData = new FormData();
    formData.append('id_barang', id_barang);
    formData.append('quantity', quantity);
    
    // Send AJAX request
    fetch('transfer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        // Try to get the response text first
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // Try to parse as JSON
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        console.log('Parsed data:', data);
        
        // Reset button state
        document.getElementById('submitTransfer').disabled = false;
        document.getElementById('submitTransfer').textContent = 'Pindahkan';
        
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                // Reload page to refresh stock display
                window.location.reload();
            });
        } else {
            // Show error message
            document.getElementById('transferError').textContent = data.message || 'Terjadi kesalahan pada server';
            document.getElementById('transferError').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Reset button state
        document.getElementById('submitTransfer').disabled = false;
        document.getElementById('submitTransfer').textContent = 'Pindahkan';
        
        // Show error message
        document.getElementById('transferError').textContent = 'Terjadi kesalahan: ' + error.message;
        document.getElementById('transferError').style.display = 'block';
    });
});

// Handle bulk transfer button click
document.getElementById('bulkTransferBtn').addEventListener('click', function() {
    const selectedProducts = [];
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    
    checkboxes.forEach(checkbox => {
        selectedProducts.push({
            id: checkbox.dataset.id,
            name: checkbox.dataset.name,
            stock: parseInt(checkbox.dataset.stock),
            kode: checkbox.dataset.kode,
            harga: checkbox.dataset.harga,
            image: checkbox.dataset.image
        });
    });
    
    if (selectedProducts.length === 1) {
        // If only one product, use the single transfer modal
        const product = selectedProducts[0];
        openTransferModal(product.id, product.name, product.stock);
    } else {
        // Otherwise use the bulk transfer modal
        openBulkTransferModal(selectedProducts);
    }
});

// Function to open bulk transfer modal
function openBulkTransferModal(products) {
    // Clear previous products
    const productsList = document.getElementById('selectedProductsList');
    productsList.innerHTML = '';
    
    // Calculate total items
    let totalItems = 0;
    
    // Add selected products to the table
    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>${product.name}</td>
            <td>${product.stock}</td>
            <td>
                <input type="hidden" name="product_ids[]" value="${product.id}">
                <input type="number" class="form-control bulk-quantity" 
                       name="quantities[]" min="1" max="${product.stock}" 
                       value="1" required 
                       data-stock="${product.stock}"
                       oninput="updateBulkTotal()">
            </td>
        `;
        productsList.appendChild(row);
        totalItems++;
    });
    
    // Add a total row if more than one product
    if (products.length > 1) {
        const footerRow = document.createElement('tr');
        footerRow.className = 'table-info';
        footerRow.innerHTML = `
            <td colspan="2" class="text-end"><strong>Total:</strong></td>
            <td id="totalSelectedItems">${products.length} barang</td>
            <td id="totalTransferQuantity">${products.length} item</td>
        `;
        productsList.appendChild(footerRow);
    }
    
    // Hide any previous error messages
    document.getElementById('bulkTransferError').style.display = 'none';
    
    // Open the modal
    const bulkTransferModal = new bootstrap.Modal(document.getElementById('bulkTransferModal'));
    bulkTransferModal.show();
    
    // Update the totals after modal is shown
    if (products.length > 1) {
        updateBulkTotal();
    }
}

// Function to update bulk transfer totals
function updateBulkTotal() {
    const quantityInputs = document.querySelectorAll('.bulk-quantity');
    let totalQuantity = 0;
    
    quantityInputs.forEach(input => {
        totalQuantity += parseInt(input.value) || 0;
    });
    
    const totalElement = document.getElementById('totalTransferQuantity');
    if (totalElement) {
        totalElement.textContent = totalQuantity + ' item';
    }
}

// Handle bulk transfer form submission
document.getElementById('submitBulkTransfer').addEventListener('click', function() {
    // Validate all quantities
    let isValid = true;
    const quantityInputs = document.querySelectorAll('.bulk-quantity');
    
    quantityInputs.forEach(input => {
        const quantity = parseInt(input.value);
        const availableStock = parseInt(input.dataset.stock);
        
        if (quantity <= 0 || quantity > availableStock) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        document.getElementById('bulkTransferError').textContent = 'Ada jumlah yang tidak valid. Pastikan semua jumlah lebih dari 0 dan tidak melebihi stok tersedia.';
        document.getElementById('bulkTransferError').style.display = 'block';
        return;
    }
    
    // Prepare form data
    const formData = new FormData(document.getElementById('bulkTransferForm'));
    
    // Show loading state
    document.getElementById('bulkTransferError').style.display = 'none';
    document.getElementById('submitBulkTransfer').disabled = true;
    document.getElementById('submitBulkTransfer').textContent = 'Sedang Memproses...';
    
    // Send AJAX request
    fetch('transfer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        // Reset button state
        document.getElementById('submitBulkTransfer').disabled = false;
        document.getElementById('submitBulkTransfer').textContent = 'Pindahkan Semua';
        
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('bulkTransferModal')).hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                // Reload page to refresh stock display
                window.location.reload();
            });
        } else {
            // Show error message
            document.getElementById('bulkTransferError').textContent = data.message;
            document.getElementById('bulkTransferError').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Reset button state
        document.getElementById('submitBulkTransfer').disabled = false;
        document.getElementById('submitBulkTransfer').textContent = 'Pindahkan Semua';
        
        // Show error message
        document.getElementById('bulkTransferError').textContent = 'Terjadi kesalahan: ' + error.message;
        document.getElementById('bulkTransferError').style.display = 'block';
    });
});

// Handle "Add to Report" button
document.getElementById('addToReportBtn').addEventListener('click', function() {
    // Get selected products
    const selectedProducts = [];
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    
    // Now we allow multiple products for report
    if (checkboxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih minimal satu barang untuk laporan.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    checkboxes.forEach(checkbox => {
        // Get product data from the table row
        const row = checkbox.closest('tr');
        const id = checkbox.dataset.id;
        const name = row.cells[2].textContent;
        const price = row.cells[5].textContent.replace('Rp ', '').replace(/\./g, '');
        
        selectedProducts.push({
            id: id,
            name: name,
            price: price
        });
    });
    
    // Open the report modal
    openReportModal(selectedProducts);
});

// Function to open report modal - updated for multiple products
function openReportModal(products) {
    // Clear previous products
    const productsList = document.getElementById('reportProductsList');
    productsList.innerHTML = '';
    
    // Add selected products to the table
    products.forEach((product, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}
                <input type="hidden" name="id_barang[]" value="${product.id}">
            </td>
            <td>${product.name}</td>
            <td>
                <input type="number" class="form-control report-quantity" 
                      name="jumlah[]" min="1" value="1" required>
            </td>
            <td>
                <input type="text" class="form-control report-price" 
                      name="harga[]" value="${product.price}" required>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-report-item">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        productsList.appendChild(row);
    });
    
    // Add a total row
    if (products.length > 1) {
        const footerRow = document.createElement('tr');
        footerRow.className = 'table-info';
        footerRow.innerHTML = `
            <td colspan="2" class="text-end"><strong>Total:</strong></td>
            <td id="totalReportQuantity">0 item</td>
            <td id="totalReportPrice">Rp 0</td>
            <td></td>
        `;
        productsList.appendChild(footerRow);
        
        // Update totals
        updateReportTotals();
    }
    
    // Reset form elements
    document.getElementById('receiptImage').value = '';
    
    // Hide any previous error messages
    document.getElementById('reportError').style.display = 'none';
    
    // Open the modal
    const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
    reportModal.show();
    
    // Bind events to the quantity and price inputs
    bindReportEvents();
}

// Function to update report totals
function updateReportTotals() {
    const quantityInputs = document.querySelectorAll('.report-quantity');
    const priceInputs = document.querySelectorAll('.report-price');
    
    let totalQuantity = 0;
    let totalPrice = 0;
    
    quantityInputs.forEach((input, index) => {
        const quantity = parseInt(input.value) || 0;
        const price = parseInt(priceInputs[index].value.replace(/\D/g, '')) || 0;
        
        totalQuantity += quantity;
        totalPrice += (quantity * price);
    });
    
    // Update the total elements if they exist
    const totalQuantityElement = document.getElementById('totalReportQuantity');
    const totalPriceElement = document.getElementById('totalReportPrice');
    
    if (totalQuantityElement) {
        totalQuantityElement.textContent = totalQuantity + ' item';
    }
    
    if (totalPriceElement) {
        totalPriceElement.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
    }
}

// Function to bind events to report items
function bindReportEvents() {
    // Event for quantity and price changes
    document.querySelectorAll('.report-quantity, .report-price').forEach(input => {
        input.addEventListener('input', updateReportTotals);
    });
    
    // Event for remove buttons
    document.querySelectorAll('.remove-report-item').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.remove();
            
            // Check if we still have products
            const remainingRows = document.querySelectorAll('#reportProductsList tr:not(.table-info)');
            if (remainingRows.length === 0) {
                // Close modal if no products left
                bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Ada Produk',
                    text: 'Semua produk telah dihapus dari laporan.',
                    confirmButtonText: 'OK'
                });
            } else {
                // Update totals
                updateReportTotals();
                
                // If only one product left, remove the total row
                if (remainingRows.length === 1) {
                    const totalRow = document.querySelector('#reportProductsList tr.table-info');
                    if (totalRow) {
                        totalRow.remove();
                    }
                }
            }
        });
    });
}

// Handle report form submission
document.getElementById('submitReport').addEventListener('click', function() {
    // Get form data
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Add action parameter
    formData.append('action', 'create_report');
    
    // Check if we have any products
    const productIds = formData.getAll('id_barang[]');
    if (productIds.length === 0) {
        document.getElementById('reportError').textContent = 'Tidak ada produk yang dipilih.';
        document.getElementById('reportError').style.display = 'block';
        return;
    }
    
    // Validate quantities and prices
    let isValid = true;
    let errorMsg = '';
    
    const quantities = document.querySelectorAll('.report-quantity');
    const prices = document.querySelectorAll('.report-price');
    
    quantities.forEach((input, index) => {
        const quantity = parseInt(input.value) || 0;
        const price = parseInt(prices[index].value.replace(/\D/g, '')) || 0;
        
        if (quantity <= 0) {
            isValid = false;
            errorMsg = 'Semua jumlah harus lebih dari 0.';
        }
        
        if (price <= 0) {
            isValid = false;
            errorMsg = 'Semua harga harus lebih dari 0.';
        }
    });
    
    if (!isValid) {
        document.getElementById('reportError').textContent = errorMsg;
        document.getElementById('reportError').style.display = 'block';
        return;
    }
    
    // Check if receipt image is uploaded
    const receiptImage = document.getElementById('receiptImage').files[0];
    if (!receiptImage) {
        document.getElementById('reportError').textContent = 'Gambar nota harus diunggah.';
        document.getElementById('reportError').style.display = 'block';
        return;
    }
    
    // Show loading state
    document.getElementById('reportError').style.display = 'none';
    document.getElementById('submitReport').disabled = true;
    document.getElementById('submitReport').textContent = 'Sedang Menyimpan...';
    
    // Send AJAX request
    fetch('report_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Try to get the response text first
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // Try to parse as JSON
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        // Reset button state
        document.getElementById('submitReport').disabled = false;
        document.getElementById('submitReport').textContent = 'Simpan';
        
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
            
            // Show success message
            let message = data.message;
            if (data.product_count > 1) {
                message += ` (${data.product_count} produk)`;
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                showConfirmButton: false,
                timer: 2000
            });
            
            // Uncheck all checkboxes
            document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Update select all checkbox
            document.getElementById('select-all').checked = false;
            document.getElementById('select-all').indeterminate = false;
            
            // Update bulk buttons state
            updateBulkButtonsState();
        } else {
            // Show error message
            document.getElementById('reportError').textContent = data.message;
            document.getElementById('reportError').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Reset button state
        document.getElementById('submitReport').disabled = false;
        document.getElementById('submitReport').textContent = 'Simpan';
        
        // Show error message
        document.getElementById('reportError').textContent = 'Terjadi kesalahan: ' + error.message;
        document.getElementById('reportError').style.display = 'block';
    });
});

// Initialize Select2 for better dropdown UI and update bulk buttons state on page load
$(document).ready(function() {
    $('.select2').select2({
        dropdownParent: $('#reportModal')
    });
    
    updateBulkButtonsState();
});
</script>
</body>

</html>