<?php
// Include database connection
include('../../routes/db_conn.php');


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_barang = $_POST['id_barang'];
    $nama_barang = $_POST['nama_barang'];
    $brand = $_POST['brand'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];
    $id_jenis = $_POST['id_jenis'];  // Ambil id_jenis dari POST
    $existing_image = $_POST['existing_image'];  // Menyimpan nama gambar yang ada

    // Cek apakah id_jenis yang dipilih valid di tabel jenis_barang
    $query_jenis = "SELECT * FROM jenis_barang WHERE id_jenis = '$id_jenis'";
    $result_jenis_check = mysqli_query($conn, $query_jenis);

    if (mysqli_num_rows($result_jenis_check) == 0) {
        echo "Error: Kategori yang dipilih tidak valid.";
        exit;  // Hentikan skrip jika id_jenis tidak valid
    }

    // Periksa apakah ada gambar yang diunggah
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Menangani unggahan file gambar
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_dir = "image/" . $image_name;

        // Pindahkan file yang diunggah ke direktori gambar
        move_uploaded_file($image_tmp, $image_dir);
    } else {
        // Jika tidak ada gambar baru, gunakan gambar yang ada
        $image_name = $existing_image;
    }

    // Membuat query UPDATE
    $query = "UPDATE barang SET 
                nama_barang = '$nama_barang', 
                brand = '$brand', 
                stok = '$stok', 
                harga = '$harga', 
                id_jenis = '$id_jenis', 
                image = '$image_name' 
              WHERE id_barang = $id_barang";

    // Debug: Tampilkan query SQL yang akan dijalankan
    echo $query;
    exit; // Hentikan eksekusi untuk melihat query SQL

    // Eksekusi query UPDATE
    if (mysqli_query($conn, $query)) {
        echo "Produk berhasil diperbarui!";
        header("Location: productlist.php");  // Redirect setelah pembaruan berhasil
    } else {
        echo "Error memperbarui produk: " . mysqli_error($conn);
    }
}
?>
