<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$database = "ayula_store";

$conn = new mysqli($servername, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
}

// Cek action yang diminta
if (isset($_POST['action']) && $_POST['action'] == 'create_report') {
    createReport($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
}

function createReport($conn) {
    // Handle upload gambar nota
    $upload_dir = '../uploads/nota/';
    
    // Buat direktori jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $image_name = '';
    
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        // Generate nama file unik
        $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $image_name = 'nota_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_extension;
        $target_file = $upload_dir . $image_name;
        
        // Validasi tipe file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF']);
            return;
        }
        
        // Validasi ukuran file (max 5MB)
        if ($_FILES['receipt_image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maksimum 5MB)']);
            return;
        }
        
        // Upload file
        if (!move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target_file)) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengunggah gambar']);
            return;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gambar nota harus diunggah']);
        return;
    }
    
    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // Loop through the items to insert
        if (isset($_POST['id_barang'])) {
            foreach ($_POST['id_barang'] as $key => $id_barang) {
                $jumlah = $_POST['jumlah'][$key] ?? '';
                $harga = $_POST['harga'][$key] ?? '';
                
                // Validasi data
                if (empty($id_barang) || empty($jumlah) || empty($harga)) {
                    echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
                    return;
                }
                
                // Buat ID report otomatis (format: RPT-YYYYMMDDxxx)
                $date = date('Ymd');
                $query = "SELECT MAX(SUBSTRING(id_report, 12)) as last_id FROM report WHERE id_report LIKE 'RPT-$date%'";
                $result = $conn->query($query);
                $row = $result->fetch_assoc();
                $last_id = $row['last_id'] ?? 0;
                $new_id = 'RPT-' . $date . str_pad(intval($last_id) + 1, 3, '0', STR_PAD_LEFT);
                
                // Simpan data ke tabel report
                $query = "INSERT INTO report (id_report, id_barang, tanggal, jumlah, harga, image) 
                         VALUES (?, ?, NOW(), ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sssss', $new_id, $id_barang, $jumlah, $harga, $image_name);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Gagal menyimpan data report");
                }
            }
        } else {
            throw new Exception("ID Barang tidak ditemukan");
        }
        
        // Commit transaksi
        $conn->commit();
        
        echo json_encode([ 
            'success' => true, 
            'message' => 'Data berhasil ditambahkan ke laporan', 
            'image_url' => '/ayula-store/uploads/nota/' . $image_name 
        ]);
        
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $conn->rollback();
        
        // Hapus file gambar jika upload sudah terjadi
        if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
            unlink($upload_dir . $image_name);
        }
        
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
