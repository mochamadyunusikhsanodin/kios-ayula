<?php
// Include database connection

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
function dbConnect() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ayula_store";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Get product by ID
function getProductById($id) {
    $conn = dbConnect();
    
    $sql = "SELECT * FROM barang WHERE id_barang = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $product;
}

// Get all product categories
function getProductCategories() {
    $conn = dbConnect();
    
    $sql = "SELECT * FROM jenis_barang ORDER BY nama_jenis";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    $conn->close();
    return $categories;
}
//fungsi update product
function updateProduct($id, $data) {
    $conn = dbConnect();
    
    $sql = "UPDATE barang SET 
            nama_barang = ?,
            harga = ?,
            stok = ?,
            id_jenis = ?,
            image = ?
            WHERE id_barang = ?";
            
    $stmt = $conn->prepare($sql);
    // Change from "ssdiis" to "ssdisi" - notice the 'i' to 's' change for image parameter
    $stmt->bind_param("ssdisi", 
        $data['nama_barang'], 
        $data['harga'],
        $data['stok'],
        $data['id_jenis'],
        $data['image'],
        $id
    );
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_barang'];
    
    // Handle image upload if a new file is selected
    $image = $_POST['current_image']; // Default to current image
    
    if(isset($_FILES['product_image']) && $_FILES['product_image']['size'] > 0) {
        $target_dir = "../uploads/img-barang/";
        $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "product_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image = $new_filename;
            }
        }
    }
    
    // Prepare data for update
    $data = [
        'nama_barang' => $_POST['nama_barang'],
        'harga' => $_POST['harga'],
        'stok' => $_POST['stok'],
        'id_jenis' => $_POST['id_jenis'],
        'image' => $image
    ];
    
    // Update product
    if(updateProduct($id, $data)) {
        // Redirect to product list with success message
        // After successfully editing a product
header("Location: productlist.php?success_edit=1");
exit();
    } else {
        $error = "Failed to update product";
    }
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? $_GET['id'] : 0;
$product = getProductById($product_id);

// Get all categories
$categories = getProductCategories();

// If product doesn't exist, redirect to product list
if (!$product) {
    header("Location: productlist.php?error=Product not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="POS - Bootstrap Admin Template">
    <meta name="keywords"
        content="admin, estimates, bootstrap, business, corporate, creative, invoice, html5, responsive, Projects">
    <meta name="author" content="Dreamguys - Bootstrap Admin Template">
    <meta name="robots" content="noindex, nofollow">
    <title>Ayula Store - Edit Product</title>

    <link rel="shortcut icon" type="image/x-icon" href="/ayula-store/bootstrap/assets/img/favicon.jpg">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/animate.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/select2/css/select2.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/style.css">
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
                        <li class="submenu" >
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
                        <h4>Edit Barang</h4>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="editproduct.php" method="POST" enctype="multipart/form-data">
                            <!-- Hidden field for product ID -->
                            <input type="hidden" name="id_barang" value="<?php echo $product['id_barang']; ?>">
                            <input type="hidden" name="current_image" value="<?php echo $product['image']; ?>">
                            
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Nama Barang</label>
                                        <input type="text" name="nama_barang" value="<?php echo $product['nama_barang']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Kategori</label>
                                        <select class="select" name="id_jenis" required>
                                            <option value="">Pilih Kategori</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id_jenis']; ?>" <?php if($category['id_jenis'] == $product['id_jenis']) echo 'selected'; ?>>
                                                    <?php echo $category['nama_jenis']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                               
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Stok</label>
                                        <input type="text" name="stok" value="<?php echo $product['stok']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Harga</label>
                                        <input type="text" step="0.01" name="harga" value="<?php echo $product['harga']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>Gambar barang</label>
                                        <div class="image-upload">
                                            <input type="file" name="product_image" accept="image/*">
                                            <div class="image-uploads">
                                                <img src="/ayula-store/bootstrap/assets/img/icons/upload.svg" alt="img">
                                                <h4>Pilih Gambar</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($product['image']): ?>
                                <div class="col-12">
                                    <div class="product-list">
                                        <ul class="row">
                                            <li>
                                                <div class="productviews">
                                                    <div class="productviewsimg">
                                                        <img src="../uploads/img-barang/<?php echo $product['image']; ?>" alt="img">
                                                    </div>
                                                    <div class="productviewscontent">
                                                        <div class="productviewsname">
                                                            <h2><?php echo $product['image']; ?></h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Edit</button>
                                    <a href="productlist.php" class="btn btn-cancel">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
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
</body>

</html>