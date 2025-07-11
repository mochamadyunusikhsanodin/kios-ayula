<?php
function restockWithTOPSIS($conn, $criteria_weights = null) {
    // Bobot default (sesuai manual)
    if ($criteria_weights === null) {
        $criteria_weights = [
            'stock_level' => 0.4,
            'price_value' => 0.3,
            'turnover_rate' => 0.3,
        ];
    }
    
    // Ambil data produk dan penjualan 30 hari terakhir sesuai struktur DB
    $query = "
    SELECT 
        bk.id_barangK AS id_barang,
        bk.nama_barang,
        bk.stok,
        bk.harga,
        COALESCE(SUM(dt.jumlah), 0) AS total_penjualan
    FROM barang_kasir bk
    LEFT JOIN detail_transaksi dt ON bk.id_barangK = dt.id_barangK
    LEFT JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
        AND t.tanggal BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()
    GROUP BY bk.id_barangK, bk.nama_barang, bk.stok, bk.harga
    ";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return ["error" => "Query failed: " . mysqli_error($conn)];
    }
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Pastikan harga sudah dalam format numeric (decimal)
        $harga = (float)$row['harga'];
        
        // Hitung turnover rate (penjualan/stok), hindari pembagian 0
        $turnover_rate = ($row['stok'] > 0) ? ($row['total_penjualan'] / $row['stok']) : 0;
        
        $products[] = [
            'id_barang' => $row['id_barang'],
            'nama_barang' => $row['nama_barang'],
            'stok' => (int)$row['stok'],
            'harga' => $harga,
            'turnover_rate' => $turnover_rate,
        ];
    }
    
    if (empty($products)) {
        return ["message" => "No products found"];
    }
    
    // Buat decision matrix dengan reciprocal stok (1/stok)
    $decision_matrix = [];
    foreach ($products as $product) {
        $reciprocal_stock = ($product['stok'] > 0) ? (1 / $product['stok']) : 0;
        $decision_matrix[] = [
            'id_barang' => $product['id_barang'],
            'nama_barang' => $product['nama_barang'],
            'stock_level' => $reciprocal_stock,
            'price_value' => $product['harga'],
            'turnover_rate' => $product['turnover_rate'],
        ];
    }
    
    // Normalisasi
    $normalized_matrix = normalizeMatrix($decision_matrix);
    
    // Terapkan bobot
    $weighted_matrix = applyWeights($normalized_matrix, $criteria_weights);
    
    // Tentukan solusi ideal positif dan negatif
    $ideal_solution = [];
    $negative_ideal_solution = [];
    foreach (['stock_level', 'price_value', 'turnover_rate'] as $criteria) {
        $values = array_column($weighted_matrix, $criteria);
        $ideal_solution[$criteria] = max($values);
        $negative_ideal_solution[$criteria] = min($values);
    }
    
    // Hitung jarak ke solusi ideal positif dan negatif
    $separation_ideal = [];
    $separation_negative = [];
    foreach ($weighted_matrix as $key => $product) {
        $separation_ideal[$key] = sqrt(
            pow($product['stock_level'] - $ideal_solution['stock_level'], 2) +
            pow($product['price_value'] - $ideal_solution['price_value'], 2) +
            pow($product['turnover_rate'] - $ideal_solution['turnover_rate'], 2)
        );
        $separation_negative[$key] = sqrt(
            pow($product['stock_level'] - $negative_ideal_solution['stock_level'], 2) +
            pow($product['price_value'] - $negative_ideal_solution['price_value'], 2) +
            pow($product['turnover_rate'] - $negative_ideal_solution['turnover_rate'], 2)
        );
    }
    
    // Hitung skor TOPSIS (relative closeness)
    $relative_closeness = [];
    foreach ($weighted_matrix as $key => $product) {
        $denominator = $separation_negative[$key] + $separation_ideal[$key];
        $relative_closeness[$key] = ($denominator > 0) ? ($separation_negative[$key] / $denominator) : 0;
    }
    
    // Siapkan hasil
    $topsis_scores = [];
    foreach ($relative_closeness as $key => $score) {
        $topsis_scores[] = [
            'id_barang' => $decision_matrix[$key]['id_barang'],
            'nama_barang' => $decision_matrix[$key]['nama_barang'],
            'stok' => $products[$key]['stok'],
            'harga' => $products[$key]['harga'],
            'topsis_score' => round($score, 4),
            'rank' => 0,
        ];
    }
    
    // Urutkan berdasarkan skor TOPSIS descending
    usort($topsis_scores, function($a, $b) {
        return $b['topsis_score'] <=> $a['topsis_score'];
    });
    
    // Beri peringkat
    foreach ($topsis_scores as $key => $product) {
        $topsis_scores[$key]['rank'] = $key + 1;
    }
    
    return $topsis_scores;
}

function normalizeMatrix($matrix) {
    $normalized = [];
    $sum_squares = [
        'stock_level' => 0,
        'price_value' => 0,
        'turnover_rate' => 0,
    ];
    
    foreach ($matrix as $product) {
        $sum_squares['stock_level'] += pow($product['stock_level'], 2);
        $sum_squares['price_value'] += pow($product['price_value'], 2);
        $sum_squares['turnover_rate'] += pow($product['turnover_rate'], 2);
    }
    
    foreach ($sum_squares as $key => $value) {
        $sum_squares[$key] = sqrt($value);
    }
    
    foreach ($matrix as $key => $product) {
        $normalized[$key] = [
            'id_barang' => $product['id_barang'],
            'nama_barang' => $product['nama_barang'],
            'stock_level' => ($sum_squares['stock_level'] > 0) ? ($product['stock_level'] / $sum_squares['stock_level']) : 0,
            'price_value' => ($sum_squares['price_value'] > 0) ? ($product['price_value'] / $sum_squares['price_value']) : 0,
            'turnover_rate' => ($sum_squares['turnover_rate'] > 0) ? ($product['turnover_rate'] / $sum_squares['turnover_rate']) : 0,
        ];
    }
    
    return $normalized;
}

function applyWeights($matrix, $weights) {
    $weighted = [];
    foreach ($matrix as $key => $product) {
        $weighted[$key] = [
            'id_barang' => $product['id_barang'],
            'nama_barang' => $product['nama_barang'],
            'stock_level' => $product['stock_level'] * $weights['stock_level'],
            'price_value' => $product['price_value'] * $weights['price_value'],
            'turnover_rate' => $product['turnover_rate'] * $weights['turnover_rate'],
        ];
    }
    return $weighted;
}
?>
