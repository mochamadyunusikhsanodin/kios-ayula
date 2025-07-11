<?php
// Start session to get user information
session_start();

// Include database connection if needed for user validation
include('../../routes/db_conn.php');

// Get user role and name if available
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
$isAdmin = ($userRole === 'admin');

// WhatsApp number (with country code format for Indonesia)
$waNumber = "6287857242169"; // 62 + 87704632355 (removing the leading 0)
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="Ayula Store - Laporkan Masalah">
    <meta name="keywords" content="admin, reports, issues, support, ayula store">
    <meta name="author" content="Ayula Store Developer">
    <meta name="robots" content="noindex, nofollow">
    <title>Ayula Store POS - Laporkan Masalah</title>

    <link rel="shortcut icon" type="image/x-icon" href="../../src/img/smallest-ayula.png">
    <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../bootstrap/assets/css/animate.css">
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../bootstrap/assets/css/style.css">

    <style>
        .report-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .report-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
        }

        .report-body {
            padding: 30px;
        }

        .wa-button {
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .wa-button:hover {
            background-color: #128C7E;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .wa-icon {
            margin-right: 8px;
        }

        .issue-type-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .issue-type-btn {
            flex: 1 0 calc(33.333% - 10px);
            min-width: 150px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .issue-type-btn:hover {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }

        .issue-type-btn.active {
            background-color: #ff9f43;
            color: white;
            border-color: #ff9f43;
        }

        .issue-type-btn i {
            display: block;
            font-size: 24px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .issue-type-btn {
                flex: 1 0 calc(50% - 10px);
            }
        }

        @media (max-width: 576px) {
            .issue-type-btn {
                flex: 1 0 100%;
            }
        }
    </style>
</head>

<body class="<?php echo $isAdmin ? 'admin' : 'employee'; ?>">
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
                        <li class="active">
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
                        <h4>Laporkan Masalah</h4>
                        <h6>Laporkan masalah atau saran untuk pengembangan sistem</h6>
                    </div>
                </div>

               <div class="card report-card">
                    <div class="card-header report-header">
                        <h5 class="card-title">Hubungi Developer via WhatsApp</h5>
                    </div>
                    <div class="card-body report-body">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Isi formulir di bawah ini untuk melaporkan masalah. Pesan akan dikirim langsung ke developer melalui WhatsApp.
                        </div>

                        <form id="issue-report-form">
                            <div class="mb-4">
                                <label class="form-label">Jenis Masalah</label>
                                <div class="issue-type-container">
                                    <div class="issue-type-btn" data-type="Bug/Error">
                                        <i class="fas fa-bug"></i>
                                        <span>Bug/Error</span>
                                    </div>
                                    <div class="issue-type-btn" data-type="Fitur Tidak Berfungsi">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Fitur Tidak Berfungsi</span>
                                    </div>
                                    <div class="issue-type-btn" data-type="Permintaan Fitur">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>Permintaan Fitur</span>
                                    </div>
                                    <div class="issue-type-btn" data-type="Optimasi Kinerja">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Optimasi Kinerja</span>
                                    </div>
                                    <div class="issue-type-btn" data-type="UI/UX">
                                        <i class="fas fa-palette"></i>
                                        <span>UI/UX</span>
                                    </div>
                                    <div class="issue-type-btn" data-type="Lainnya">
                                        <i class="fas fa-question-circle"></i>
                                        <span>Lainnya</span>
                                    </div>
                                </div>
                                <input type="hidden" id="issue-type" name="issue-type" value="">
                            </div>

                            <div class="mb-4">
                                <label for="issue-location" class="form-label">Lokasi Masalah</label>
                                <select class="form-select" id="issue-location" name="issue-location">
                                    <option value="">Pilih Halaman/Fitur...</option>
                                    <option value="Login">Login</option>
                                    <option value="Dashboard">Dashboard</option>
                                    <option value="POS/Transaksi">Barang</option>
                                    <option value="Produk">Analisa barang</option>
                                    <option value="Pengguna">Pengguna</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="issue-priority" class="form-label">Prioritas</label>
                                <select class="form-select" id="issue-priority" name="issue-priority">
                                    <option value="Rendah">Rendah - Tidak mengganggu operasi utama</option>
                                    <option value="Sedang" selected>Sedang - Mengganggu tapi ada solusi</option>
                                    <option value="Tinggi">Tinggi - Menghambat pekerjaan</option>
                                    <option value="Kritis">Kritis - Sistem tidak dapat digunakan</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="issue-description" class="form-label">Deskripsi Masalah</label>
                                <textarea class="form-control" id="issue-description" name="issue-description" rows="5" placeholder="Jelaskan masalah dengan detail. Berikan langkah-langkah untuk mereproduksi masalah (jika ada)..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="issue-contact" class="form-label">Kontak Anda (Opsional)</label>
                                <input type="text" class="form-control" id="issue-contact" name="issue-contact" placeholder="Nomor telepon atau email untuk follow-up" value="<?php echo htmlspecialchars($username); ?>">
                            </div>

                            <div class="text-center mt-5">
                                <button type="button" id="send-wa-btn" class="btn wa-button btn-lg">
                                    <i class="fab fa-whatsapp wa-icon"></i>
                                    Kirim Laporan via WhatsApp
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../bootstrap/assets/js/feather.min.js"></script>
    <script src="../../bootstrap/assets/js/jquery.slimscroll.min.js"></script>
    <script src="../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../bootstrap/assets/plugins/select2/js/select2.min.js"></script>
    <script src="../../bootstrap/assets/js/script.js"></script>

    <script>
        $(document).ready(function() {
            // Handle issue type selection with animation
            $('.issue-type-btn').on('click', function() {
                // Remove active class from all buttons with animation
                $('.issue-type-btn').removeClass('active').fadeOut(200).fadeIn(200);
                $(this).addClass('active');

                // Set the value in the hidden input
                $('#issue-type').val($(this).data('type'));
            });

            // WhatsApp send button functionality with custom message
            $('#send-wa-btn').on('click', function() {
                var issueType = $('#issue-type').val() || 'Tidak ditentukan';
                var issueLocation = $('#issue-location').val() || 'Tidak ditentukan';
                var issuePriority = $('#issue-priority').val() || 'Sedang';
                var issueDescription = $('#issue-description').val() || 'Tidak ada deskripsi';
                var issueContact = $('#issue-contact').val() || '<?php echo htmlspecialchars($username); ?>';

                // Validation check before sending
                if (!issueType) {
                    alert('Mohon pilih jenis masalah terlebih dahulu.');
                    return;
                }
                if (!issueDescription) {
                    alert('Mohon isi deskripsi masalah terlebih dahulu.');
                    return;
                }

                var message = "🔴 *LAPORAN MASALAH AYULA STORE* 🔴\n\n" +
                              "*User:* <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($userRole); ?>)\n" +
                              "*Jenis Masalah:* " + issueType + "\n" +
                              "*Lokasi:* " + issueLocation + "\n" +
                              "*Prioritas:* " + issuePriority + "\n\n" +
                              "*Deskripsi:*\n" + issueDescription + "\n\n" +
                              "*Kontak:* " + issueContact;

                var encodedMessage = encodeURIComponent(message);
                var waLink = "https://wa.me/<?php echo $waNumber; ?>?text=" + encodedMessage;

                window.open(waLink, '_blank');
            });
        });
    </script>
</body>

</html>
