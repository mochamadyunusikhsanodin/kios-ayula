<?php
// Memasukkan fungsi transaksi
require_once 'configtrans.php';

// Memulai sesi
// session_start();
$userRole = $_SESSION['role']; // 'user' or 'admin'
// Memeriksa apakah ID transaksi tersedia
if (!isset($_GET['id'])) {
    // Arahkan ke halaman transaksi jika ID tidak ada
    header('Location: index.php');
    exit;
}

// Mendapatkan ID transaksi dari URL
$transactionId = $_GET['id'];

// Mendapatkan detail transaksi
$transaction = getTransactionById($transactionId);

// Memeriksa apakah transaksi ada
if (!$transaction) {
    // Arahkan ke halaman transaksi jika transaksi tidak ditemukan
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0"
    />
    <meta name="description" content="POS - Template Admin Bootstrap" />
    <meta
      name="keywords"
      content="admin, estimasi, bootstrap, bisnis, korporat, kreatif, faktur, html5, responsif, Proyek"
    />
    <meta name="author" content="Dreamguys - Template Admin Bootstrap" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Ayula Store POS - Transaksi Sukses</title>

    <link
      rel="shortcut icon"
      type="image/x-icon"
      href="../../bootstrap/assets/img/favicon.jpg"
    />

    <link rel="stylesheet" href="../../bootstrap/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/animate.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../../bootstrap/assets/css/style.css" />
    
    <style>
      /* Gaya layar reguler */
      @media screen {
        .print-only {
          display: none !important;
        }
      }
      
      /* Gaya cetak */
      @media print {
        /* Sembunyikan semua kecuali struk */
        body * {
          visibility: hidden;
        }
        
        .print-receipt, .print-receipt * {
          visibility: visible;
        }
        
        .print-receipt {
          position: absolute;
          left: 0;
          top: 0;
          width: 80mm; /* Lebar untuk struk thermal */
          padding: 2mm;
          margin: 0;
          font-size: 10pt;
        }
        
        /* Sembunyikan elemen hanya untuk layar */
        .screen-only {
          display: none !important;
        }
        
        /* Tampilkan elemen hanya untuk cetak */
        .print-only {
          display: block !important;
        }
        
        /* Gaya struk */
        .receipt-header {
          text-align: center;
          border-bottom: 1px dashed #000;
          padding-bottom: 5px;
          margin-bottom: 5px;
        }
        
        .receipt-info {
          margin-bottom: 5px;
          font-size: 9pt;
        }
        
        .receipt-table {
          width: 100%;
          font-size: 8pt;
          border-collapse: collapse;
        }
        
        .receipt-table th, .receipt-table td {
          padding: 2px 0;
        }
        
        .receipt-table th {
          text-align: left;
          border-bottom: 1px solid #000;
        }
        
        .receipt-footer {
          text-align: center;
          border-top: 1px dashed #000;
          padding-top: 5px;
          margin-top: 5px;
          font-size: 8pt;
        }
        
        .receipt-total {
          border-top: 1px solid #000;
          margin-top: 5px;
          padding-top: 5px;
        }
        
        .text-right {
          text-align: right;
        }
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
                                    <h6><?php echo $isAdmin ? 'Admin' : 'Karyawan'; ?></h6>
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
                        <li class="active">
                            <a href="/ayula-store/views/dashboard/"><img src="../../bootstrap/assets/img/icons/dashboard.svg" alt="img" /><span>
                                    Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="/ayula-store/views/transaction/"><img src="../../bootstrap/assets/img/icons/sales1.svg" alt="img" /><span>
                                    POS</span></a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/product.svg" alt="img" /><span>
                                    Produk</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/ayula-store/views/barang/productlist.php">Daftar Produk</a></li>
                                <li><a href="/ayula-store/views/barang/addproduct.php">Tambah Produk</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/purchase1.svg" alt="img" /><span>
                                    Pembelian</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="purchaselist.html">Daftar Pembelian</a></li>
                                <li><a href="addpurchase.html">Tambah Pembelian</a></li>
                                <li><a href="importpurchase.html">Impor Pembelian</a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);"><img src="../../bootstrap/assets/img/icons/time.svg" alt="img" /><span>
                                    Laporan</span>
                                <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="inventoryreport.html">Laporan Inventaris</a></li>
                                <li><a href="/ayula-store/views/report/sales-report/">Laporan Penjualan</a></li>
                                <li><a href="purchasereport.html">Laporan Pembelian</a></li>
                                <li><a href="supplierreport.html">Laporan Pemasok</a></li>
                            </ul>
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
          <div class="row">
            <div class="col-lg-8 col-sm-12 mx-auto">
              <div class="card screen-only">
                <div class="card-body">
                  <div class="text-center">
                    <h4 class="mt-2 mb-4"><i class="fa fa-check-circle text-success me-2"></i> Transaksi Berhasil</h4>
                    <p class="text-secondary">Transaksi Anda telah diproses dengan sukses</p>
                    <hr>
                  </div>
                  
                  <div class="row mt-4">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label text-muted">ID Transaksi</label>
                        <div class="form-control-static"><?php echo $transaction['kode_transaksi']; ?></div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label text-muted">Tanggal</label>
                        <div class="form-control-static"><?php echo date('d M Y H:i', strtotime($transaction['tanggal'])); ?></div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="table-responsive">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Barang</th>
                          <th>Jumlah</th>
                          <th class="text-end">Harga</th>
                          <th class="text-end">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($transaction['items'] as $item): ?>
                        <tr>
                          <td>
                            <div class="d-flex">
                              <div>
                                <h6><?php echo $item['nama_barang']; ?></h6>
                                <p class="text-muted mb-0"><?php echo $item['kode_barang']; ?></p>
                              </div>
                            </div>
                          </td>
                          <td><?php echo $item['jumlah']; ?></td>
                          <td class="text-end">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                          <td class="text-end">Rp <?php echo number_format($item['total_harga'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  
                  <div class="row justify-content-end mt-4">
                    <div class="col-lg-5">
                      <div class="card bg-light">
                        <div class="card-body">
                          <div class="d-flex justify-content-between border-top pt-2">
                            <h5>Total</h5>
                            <h5>Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></h5>
                          </div>
                          
                          <!-- Tambahkan informasi tunai dan kembalian -->
                          <?php if (isset($transaction['cash_amount']) && $transaction['cash_amount'] > 0): ?>
                          <div class="d-flex justify-content-between mt-3 mb-2">
                            <h6>Jumlah Tunai</h6>
                            <h6>Rp <?php echo number_format($transaction['cash_amount'], 0, ',', '.'); ?></h6>
                          </div>
                          <div class="d-flex justify-content-between">
                            <h6>Kembalian</h6>
                            <h6>Rp <?php echo number_format($transaction['change_amount'], 0, ',', '.'); ?></h6>
                          </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="text-center mt-4 screen-only">
                    <a href="index.php" class="btn btn-primary me-2">Transaksi Baru</a>
                    <button class="btn btn-secondary" onclick="window.print()">Cetak Struk</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Template Struk Thermal - Dioptimalkan untuk lebar 80mm -->
    <div class="print-receipt print-only">
      <div class="receipt-header">
        <h3 style="margin:0;font-size:14pt;">AYULA STORE</h3>
        <p style="margin:3px 0;font-size:9pt;">Senjayan, Kec. Gondang, Kabupaten Nganjuk, </p>
        <p style="margin:3px 0;font-size:9pt;">Jawa Timur 64451</p>
        <p style="margin:3px 0;font-size:9pt;">Telp: 0822 3472 2000</p>
        <p style="margin:3px 0;font-size:8pt;">-------------------------------------</p>
      </div>
      
      <div class="receipt-info">
        <table style="width:100%;font-size:9pt;">
          <tr>
            <td width="60%">No: <?php echo $transaction['kode_transaksi']; ?></td>
            <td width="40%" style="text-align:right;">Kasir: Admin</td>
          </tr>
          <tr>
            <td colspan="2">Tanggal: <?php echo date('d/m/Y H:i', strtotime($transaction['tanggal'])); ?></td>
          </tr>
        </table>
        <p style="margin:2px 0;font-size:8pt;">-------------------------------------</p>
      </div>
      
      <table class="receipt-table" style="width:100%;font-size:8pt;">
        <tr>
          <th style="width:50%;text-align:left;">Barang</th>
          <th style="width:10%;text-align:center;">Qty</th>
          <th style="width:20%;text-align:right;">Harga</th>
          <th style="width:20%;text-align:right;">Total</th>
        </tr>
        <?php foreach ($transaction['items'] as $item): ?>
        <tr>
          <td style="font-size:8pt;"><?php echo $item['nama_barang']; ?></td>
          <td style="text-align:center;"><?php echo $item['jumlah']; ?></td>
          <td style="text-align:right;"><?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
          <td style="text-align:right;"><?php echo number_format($item['total_harga'], 0, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
      
      <p style="margin:5px 0 2px;font-size:8pt;">-------------------------------------</p>
      
      <table style="width:100%;font-size:9pt;">
        <tr style="font-weight:bold;">
          <td style="text-align:right;">TOTAL:</td>
          <td style="text-align:right;">Rp <?php echo number_format($transaction['total'], 0, ',', '.'); ?></td>
        </tr>
        
        <?php if (isset($transaction['cash_amount']) && $transaction['cash_amount'] > 0): ?>
        <tr>
          <td style="text-align:right;">Tunai:</td>
          <td style="text-align:right;">Rp <?php echo number_format($transaction['cash_amount'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
          <td style="text-align:right;">Kembali:</td>
          <td style="text-align:right;">Rp <?php echo number_format($transaction['change_amount'], 0, ',', '.'); ?></td>
        </tr>
        <?php endif; ?>
      </table>
      
      <div class="receipt-footer">
        <p style="margin:5px 0 2px;font-size:8pt;">-------------------------------------</p>
        <p style="margin:3px 0;font-size:9pt;">Terima Kasih Atas Kunjungan Anda</p>
        <p style="margin:3px 0;font-size:8pt;">Barang yang sudah dibeli tidak dapat dikembalikan</p>
      </div>
    </div>

    <script src="../../bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../bootstrap/assets/js/feather.min.js"></script>
    <script src="../../bootstrap/assets/js/jquery.slimscroll.min.js"></script>
    <script src="../../bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../bootstrap/assets/js/script.js"></script>
    <script>
      // Auto-print saat halaman ini dimuat - aktifkan jika perlu
      // window.onload = function() {
      //   window.print();
      // };
    </script>
  </body>
</html>