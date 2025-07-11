<?php
include('../../routes/db_conn.php');

// Start the session to access the logged-in user information
session_start();

// // Ambil informasi user yang sedang login
$userRole = $_SESSION['role']; // 'user' atau 'admin' $username = $_SESSION['username']; // Menambahkan username dari session

// Query database untuk daftar kasir
$sql = "SELECT id_kasir, username, phone, role FROM kasir"; // Menghapus 'password' dari query
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $kasirData = [];
    while ($row = $result->fetch_assoc()) {
        $kasirData[] = $row;
    }
} else {
    $kasirData = null; // Jika tidak ada data
}

// Menutup koneksi
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Ayula Store - Pengguna</title>
    <link rel="shortcut icon" type="image/x-icon" href="../../src/img/smallest-ayula.png">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/animate.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/style.css">
</head>

<body>
    <div id="global-loader">
        <div class="whirly-loader"> </div>
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
                        <span class="user-img">
                            <img src="../../src/img/userprofile.png" alt="" />
                            <span class="status online"></span>
                        </span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilename">
                            <div class="profileset">
                                <span class="user-img">
                                    <img src="../../src/img/userprofile.png" alt="" />
                                    <span class="status online"></span>
                                </span>
                                <div class="profilesets">
                                    <h6><?php echo $userRole == 'admin' ? 'Admin' : 'Karyawan'; ?></h6>
                                    <h5><?php echo htmlspecialchars($username); ?></h5>
                                </div>
                            </div>
                            <hr class="m-0" />
                            <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                                <img src="../../src/img/warning.png" class="me-2" alt="img" /> Laporkan Masalah
                            </a>
                            <hr class="m-0" />
                            <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                                    src="../../bootstrap/assets/img/icons/log-out.svg"
                                    class="me-2"
                                    alt="img" />Keluar</a>
                        </div>
                    </div>
                </li>
            </ul>

            <div class="dropdown mobile-user-menu">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-ellipsis-v"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="/ayula-store/views/report-issue/">
                        <i class="fa fa-cog me-2"></i> Laporkan Masalah
                    </a>
                    <hr class="m-0" />
                    <a class="dropdown-item logout pb-0" href="../../views/logout.php"><img
                            src="../../bootstrap/assets/img/icons/log-out.svg"
                            class="me-2"
                            alt="img" />Keluar</a>
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
                               
                                    <li><a href="/ayula-store/views/users/add-user.php">Pengguna Baru</a></li>
                               
                                <li><a href="/ayula-store/views/users/" class="active">Daftar Pengguna</a></li>
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
                        <h4>Daftar Pengguna</h4>
                        <h6>Kelola Pengguna Anda</h6>
                    </div>
                    <div class="page-btn">
                        
                            <a href="add-user.php" class="btn btn-added"><img src="/ayula-store/bootstrap/assets/img/icons/plus.svg" alt="img">Tambah Pengguna</a>
                        
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table datanew">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Telepon</th>
                                        <th>Peran</th>
                                       
                                            <th>Aksi</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($kasirData) {
                                        foreach ($kasirData as $kasir) {
                                            echo "<tr>";
                                            echo "<td>" . $kasir['username'] . "</td>";
                                            echo "<td>" . $kasir['phone'] . "</td>";
                                            echo "<td>" . ($kasir['role'] == 'admin' ? 'Admin' : 'Karyawan') . "</td>";
                                           
                                                echo "<td>
                                                    <a class='me-3' href='edit-user.php?id=" . $kasir['id_kasir'] . "'>
                                                        <img src='/ayula-store/bootstrap/assets/img/icons/edit.svg' alt='img'>
                                                    </a>
                                                    <a class='me-3 delete-btn' href='#' data-id='" . $kasir['id_kasir'] . "'>
                                                        <img src='/ayula-store/bootstrap/assets/img/icons/delete.svg' alt='img'>
                                                    </a>
                                                  </td>";
                                            }
                                            echo "</tr>";
                                        }
                                    
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Delete Confirmation -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Hapus Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus pengguna ini?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Batal</button>
                        <a id="confirmDelete" href="#" class="btn btn-submit">Hapus</a>
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
    <script src="/ayula-store/bootstrap/assets/js/moment.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/bootstrap-datetimepicker.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/script.js"></script>

    <script>
        $(document).ready(function() {
            // Check if the table is already initialized before initializing it
            if (!$.fn.dataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable({
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang cocok",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
            }

            // Handling the delete button click
            $('.delete-btn').on('click', function() {
                var userId = $(this).data('id');
                var deleteUrl = 'delete-user.php?id=' + userId;

                // Set the delete link in the modal
                $('#confirmDelete').attr('href', deleteUrl);

                // Show the modal
                $('#deleteModal').modal('show');
            });
        });
    </script>
    <script>
        // Disable console logs and warnings
        if (window.location.hostname === 'localhost') {
            console.log = function() {}; // Disable console logs
            console.warn = function() {}; // Disable console warnings
            console.error = function() {}; // Disable console errors
            window.alert = function() {}; // Disable alert popups
        }
    </script>
</body>

</html>