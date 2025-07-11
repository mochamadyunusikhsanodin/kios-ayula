<?php
// Koneksi ke database
$servername = "localhost";
$username = "root"; // Sesuaikan dengan username database kamu
$password = ""; // Sesuaikan dengan password database kamu
$database = "ayula_store"; // Sesuaikan dengan nama database kamu

$conn = new mysqli($servername, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Cek ID laporan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Laporan tidak valid");
}

$id_report = $_GET['id'];

// Query untuk mengambil data laporan
$sql = "SELECT r.id_report, r.tanggal, b.id_barang, b.nama_barang, jb.nama_jenis, 
         r.jumlah, r.harga, r.image 
         FROM report r 
         JOIN barang b ON r.id_barang = b.id_barang 
         JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis 
         WHERE r.id_report = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_report);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Laporan tidak ditemukan");
}

$report = $result->fetch_assoc();
$total = $report['jumlah'] * $report['harga'];
$image_url = !empty($report['image']) ? '../uploads/nota/' . $report['image'] : '/ayula-store/bootstrap/assets/img/no-image.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <title>Cetak Laporan - <?php echo $report['id_report']; ?></title>
    <link rel="stylesheet" href="/ayula-store/bootstrap/assets/css/bootstrap.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-subtitle {
            font-size: 16px;
            color: #555;
            margin-bottom: 15px;
        }
        
        .company-details {
            margin-bottom: 20px;
        }
        
        .invoice-meta {
            margin-bottom: 30px;
        }
        
        .invoice-meta table {
            width: 100%;
        }
        
        .invoice-meta td {
            padding: 5px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .invoice-table th {
            background-color: #f5f5f5;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .receipt-image {
            max-width: 100%;
            max-height: 300px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }
        
        .receipt-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 70px;
            margin-bottom: 10px;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 15px;">
            <button class="btn btn-primary" onclick="window.print()">Cetak Laporan</button>
            <button class="btn btn-secondary" onclick="window.close()">Tutup</button>
        </div>
        
        <div class="invoice-header">
            <div class="invoice-title">LAPORAN BARANG</div>
            <div class="invoice-subtitle">AYULA STORE</div>
        </div>
        
        <div class="company-details">
            <strong>Ayula Store</strong><br>
            Jl. Contoh No. 123, Kota<br>
            Telp: (021) 123-4567<br>
            Email: info@ayulastore.com
        </div>
        
        <div class="invoice-meta">
            <table>
                <tr>
                    <td width="150"><strong>ID Laporan</strong></td>
                    <td>: <?php echo $report['id_report']; ?></td>
                    <td width="150"><strong>Tanggal</strong></td>
                    <td>: <?php echo date('d/m/Y H:i', strtotime($report['tanggal'])); ?></td>
                </tr>
                <tr>
                    <td><strong>ID Barang</strong></td>
                    <td>: <?php echo $report['id_barang']; ?></td>
                    <td><strong>Jenis Barang</strong></td>
                    <td>: <?php echo $report['nama_jenis']; ?></td>
                </tr>
            </table>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="45%">Nama Barang</th>
                    <th width="15%">Jumlah</th>
                    <th width="15%">Harga</th>
                    <th width="20%">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td><?php echo $report['nama_barang']; ?></td>
                    <td class="text-center"><?php echo $report['jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($report['harga'], 0, ',', '.'); ?></td>
                    <td class="text-right">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="receipt-container">
            <h4>Gambar Nota</h4>
            <img src="<?php echo $image_url; ?>" alt="Nota" class="receipt-image">
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>Penanggung Jawab</strong>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>Admin</strong>
            </div>
        </div>
    </div>
    
    <script src="/ayula-store/bootstrap/assets/js/jquery-3.6.0.min.js"></script>
    <script src="/ayula-store/bootstrap/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Add a small delay to ensure everything is loaded
            setTimeout(function() {
                //window.print();
            }, 500);
        };
    </script>
</body>
</html>