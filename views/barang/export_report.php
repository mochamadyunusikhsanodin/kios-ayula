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

// Ambil data untuk laporan
$sql = "SELECT r.id_report, r.tanggal, b.nama_barang, jb.nama_jenis, r.jumlah, r.harga 
        FROM report r 
        JOIN barang b ON r.id_barang = b.id_barang
        JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis
        ORDER BY r.tanggal DESC";

$result = $conn->query($sql);

// Set header for Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_barang_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Output tabel HTML yang akan dibaca sebagai Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Barang</title>
    <style>
        table {border-collapse: collapse;}
        th, td {border: 1px solid #000; padding: 5px;}
        th {background-color: #f0f0f0; font-weight: bold;}
        .text-right {text-align: right;}
        .text-center {text-align: center;}
    </style>
</head>
<body>
    <h2>Laporan Barang - Ayula Store</h2>
    <p>Tanggal Export: ' . date('d/m/Y H:i:s') . '</p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Laporan</th>
                <th>Tanggal</th>
                <th>Nama Barang</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Harga (Rp)</th>
                <th>Total (Rp)</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
$grand_total = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total = $row['jumlah'] * $row['harga'];
        $grand_total += $total;
        
        echo '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td>' . $row['id_report'] . '</td>
            <td>' . date('d/m/Y H:i', strtotime($row['tanggal'])) . '</td>
            <td>' . $row['nama_barang'] . '</td>
            <td>' . $row['nama_jenis'] . '</td>
            <td class="text-center">' . $row['jumlah'] . '</td>
            <td class="text-right">' . number_format($row['harga'], 0, ',', '.') . '</td>
            <td class="text-right">' . number_format($total, 0, ',', '.') . '</td>
        </tr>';
    }
    
    echo '<tr>
        <td colspan="7" class="text-right"><strong>Grand Total</strong></td>
        <td class="text-right"><strong>' . number_format($grand_total, 0, ',', '.') . '</strong></td>
    </tr>';
} else {
    echo '<tr><td colspan="8" class="text-center">Tidak ada data laporan</td></tr>';
}

echo '</tbody>
    </table>
</body>
</html>';

// Close database connection
$conn->close();
?>