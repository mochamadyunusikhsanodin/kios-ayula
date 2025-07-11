<?php
// Include database connection
require_once '../../routes/db_conn.php';


// Function to generate PDF report
function generatePDF($filters) {
    require_once '../vendor/autoload.php'; // Require TCPDF or FPDF library

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Ayula Store');
    $pdf->SetAuthor('Ayula Store');
    $pdf->SetTitle('Laporan Inventaris');
    $pdf->SetSubject('Laporan Inventaris');
    $pdf->SetKeywords('Laporan, Inventaris, Ayula Store');

    // Set default header data
    $pdf->SetHeaderData('logo.png', 30, 'Ayula Store', 'Laporan Inventaris', array(0,64,255), array(0,64,128));
    $pdf->setFooterData(array(0,64,0), array(0,64,128));

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Get report data
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Build query based on filters
    $sql = "SELECT r.id_report, r.tanggal, r.jumlah, r.harga, r.image, 
                   b.nama_barang, b.kode_barang,
                   jb.nama_jenis
            FROM report r
            JOIN barang b ON r.id_barang = b.id_barang
            JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis
            WHERE 1=1";
    
    $params = array();
    $types = "";
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(r.tanggal) BETWEEN ? AND ?";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $types .= "ss";
    } elseif (!empty($filters['month']) && !empty($filters['year'])) {
        $sql .= " AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
        $params[] = $filters['month'];
        $params[] = $filters['year'];
        $types .= "ss";
    } elseif (!empty($filters['year'])) {
        $sql .= " AND YEAR(r.tanggal) = ?";
        $params[] = $filters['year'];
        $types .= "s";
    }
    
    if (!empty($filters['product_id'])) {
        $sql .= " AND b.id_barang = ?";
        $params[] = $filters['product_id'];
        $types .= "i";
    }
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND b.id_jenis = ?";
        $params[] = $filters['category_id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY r.tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add report title
    $title = 'Laporan Inventaris Ayula Store';
    $period = '';
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $start = date('d M Y', strtotime($filters['start_date']));
        $end = date('d M Y', strtotime($filters['end_date']));
        $period = "Periode: $start - $end";
    } elseif (!empty($filters['month']) && !empty($filters['year'])) {
        $month = date('F', mktime(0, 0, 0, $filters['month'], 1));
        $period = "Periode: $month {$filters['year']}";
    } elseif (!empty($filters['year'])) {
        $period = "Periode: Tahun {$filters['year']}";
    }
    
    // Add title and period
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $period, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Add summary information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Ringkasan', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    $total_items = 0;
    $total_amount = 0;
    $reports_count = $result->num_rows;
    
    // Create table headers
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(15, 7, 'ID', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Tanggal', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Produk', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Kategori', 1, 0, 'C', 1);
    $pdf->Cell(15, 7, 'Jumlah', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Harga', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Total', 1, 1, 'C', 1);
    
    // Add data rows
    $pdf->SetFont('helvetica', '', 8);
    
    while ($row = $result->fetch_assoc()) {
        $total = $row['jumlah'] * $row['harga'];
        $total_items += $row['jumlah'];
        $total_amount += $total;
        
        $pdf->Cell(15, 6, $row['id_report'], 1, 0, 'C');
        $pdf->Cell(25, 6, date('d M Y', strtotime($row['tanggal'])), 1, 0, 'C');
        $pdf->Cell(50, 6, $row['nama_barang'], 1, 0, 'L');
        $pdf->Cell(30, 6, $row['nama_jenis'], 1, 0, 'L');
        $pdf->Cell(15, 6, $row['jumlah'], 1, 0, 'C');
        $pdf->Cell(25, 6, 'Rp ' . number_format($row['harga'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(30, 6, 'Rp ' . number_format($total, 0, ',', '.'), 1, 1, 'R');
    }
    
    // Add totals row
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(120, 7, 'TOTAL', 1, 0, 'R', 1);
    $pdf->Cell(15, 7, $total_items, 1, 0, 'C', 1);
    $pdf->Cell(25, 7, '', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Rp ' . number_format($total_amount, 0, ',', '.'), 1, 1, 'R', 1);
    
    // Add summary before table
    $pdf->SetY(60); // Position at beginning
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total Laporan: ' . $reports_count, 0, 1, 'L');
    $pdf->Cell(60, 7, 'Total Items: ' . $total_items, 0, 1, 'L');
    $pdf->Cell(60, 7, 'Total Nilai: Rp ' . number_format($total_amount, 0, ',', '.'), 0, 1, 'L');
    $pdf->Ln(5);
    
    // Close and output PDF
    $pdf->Output('Laporan_Inventaris_' . date('Y-m-d') . '.pdf', 'I');
    
    $stmt->close();
    $conn->close();
}

// Function to export to Excel
function exportExcel($filters) {
    require_once '../vendor/autoload.php'; // Require PhpSpreadsheet
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Ayula Store')
        ->setLastModifiedBy('Ayula Store')
        ->setTitle('Laporan Inventaris')
        ->setSubject('Laporan Inventaris Ayula Store')
        ->setDescription('Laporan Inventaris Ayula Store');
    
    // Get report data
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Build query based on filters
    $sql = "SELECT r.id_report, r.tanggal, r.jumlah, r.harga, r.image, 
                   b.nama_barang, b.kode_barang,
                   jb.nama_jenis
            FROM report r
            JOIN barang b ON r.id_barang = b.id_barang
            JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis
            WHERE 1=1";
    
    $params = array();
    $types = "";
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(r.tanggal) BETWEEN ? AND ?";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $types .= "ss";
    } elseif (!empty($filters['month']) && !empty($filters['year'])) {
        $sql .= " AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
        $params[] = $filters['month'];
        $params[] = $filters['year'];
        $types .= "ss";
    } elseif (!empty($filters['year'])) {
        $sql .= " AND YEAR(r.tanggal) = ?";
        $params[] = $filters['year'];
        $types .= "s";
    }
    
    if (!empty($filters['product_id'])) {
        $sql .= " AND b.id_barang = ?";
        $params[] = $filters['product_id'];
        $types .= "i";
    }
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND b.id_jenis = ?";
        $params[] = $filters['category_id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY r.tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add title
    $sheet->setCellValue('A1', 'LAPORAN INVENTARIS AYULA STORE');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add period
    $period = '';
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $start = date('d M Y', strtotime($filters['start_date']));
        $end = date('d M Y', strtotime($filters['end_date']));
        $period = "Periode: $start - $end";
    } elseif (!empty($filters['month']) && !empty($filters['year'])) {
        $month = date('F', mktime(0, 0, 0, $filters['month'], 1));
        $period = "Periode: $month {$filters['year']}";
    } elseif (!empty($filters['year'])) {
        $period = "Periode: Tahun {$filters['year']}";
    }
    
    $sheet->setCellValue('A2', $period);
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add summary information
    $total_items = 0;
    $total_amount = 0;
    $reports_count = $result->num_rows;
    
    // Calculate totals
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_items += $row['jumlah'];
        $total_amount += ($row['jumlah'] * $row['harga']);
    }
    
    // Add summary section
    $sheet->setCellValue('A4', 'Ringkasan:');
    $sheet->getStyle('A4')->getFont()->setBold(true);
    
    $sheet->setCellValue('A5', 'Total Laporan:');
    $sheet->setCellValue('B5', $reports_count);
    
    $sheet->setCellValue('A6', 'Total Items:');
    $sheet->setCellValue('B6', $total_items);
    
    $sheet->setCellValue('A7', 'Total Nilai:');
    $sheet->setCellValue('B7', 'Rp ' . number_format($total_amount, 0, ',', '.'));
    
    // Add table headers
    $sheet->setCellValue('A9', 'ID');
    $sheet->setCellValue('B9', 'Tanggal');
    $sheet->setCellValue('C9', 'Produk');
    $sheet->setCellValue('D9', 'Kategori');
    $sheet->setCellValue('E9', 'Jumlah');
    $sheet->setCellValue('F9', 'Harga');
    $sheet->setCellValue('G9', 'Total');
    
    // Style the headers
    $sheet->getStyle('A9:G9')->getFont()->setBold(true);
    $sheet->getStyle('A9:G9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
    $sheet->getStyle('A9:G9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Add data rows
    $row_num = 10;
    foreach ($data as $row) {
        $total = $row['jumlah'] * $row['harga'];
        
        $sheet->setCellValue('A' . $row_num, $row['id_report']);
        $sheet->setCellValue('B' . $row_num, date('d M Y', strtotime($row['tanggal'])));
        $sheet->setCellValue('C' . $row_num, $row['nama_barang']);
        $sheet->setCellValue('D' . $row_num, $row['nama_jenis']);
        $sheet->setCellValue('E' . $row_num, $row['jumlah']);
        $sheet->setCellValue('F' . $row_num, 'Rp ' . number_format($row['harga'], 0, ',', '.'));
        $sheet->setCellValue('G' . $row_num, 'Rp ' . number_format($total, 0, ',', '.'));
        
        $row_num++;
    }
    
    // Add totals row
    $sheet->setCellValue('A' . $row_num, 'TOTAL');
    $sheet->mergeCells('A' . $row_num . ':D' . $row_num);
    $sheet->setCellValue('E' . $row_num, $total_items);
    $sheet->setCellValue('G' . $row_num, 'Rp ' . number_format($total_amount, 0, ',', '.'));
    
    $sheet->getStyle('A' . $row_num . ':G' . $row_num)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row_num . ':G' . $row_num)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
    
    // Autosize columns
    foreach(range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create a border for the table
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A9:G' . $row_num)->applyFromArray($styleArray);
    
    // Output excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Laporan_Inventaris_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    $stmt->close();
    $conn->close();
    exit();
}

// Process report export request
if (isset($_GET['export']) && in_array($_GET['export'], ['pdf', 'excel'])) {
    $filters = [
        'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '',
        'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : '',
        'month' => isset($_GET['filter_month']) ? $_GET['filter_month'] : '',
        'year' => isset($_GET['filter_year']) ? $_GET['filter_year'] : '',
        'product_id' => isset($_GET['product_id']) ? $_GET['product_id'] : '',
        'category_id' => isset($_GET['category_id']) ? $_GET['category_id'] : ''
    ];
    
    if ($_GET['export'] == 'pdf') {
        generatePDF($filters);
    } else {
        exportExcel($filters);
    }
    exit();
}

// Handle AJAX request for chart data
if (isset($_GET['action']) && $_GET['action'] == 'get_chart_data') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
    
    // Build query based on filters
    $sql = "SELECT jb.nama_jenis, SUM(r.jumlah) as total_items, SUM(r.jumlah * r.harga) as total_value
            FROM report r
            JOIN barang b ON r.id_barang = b.id_barang
            JOIN jenis_barang jb ON b.id_jenis = jb.id_jenis
            WHERE 1=1";
    
    $params = array();
    $types = "";
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND DATE(r.tanggal) BETWEEN ? AND ?";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'];
        $types .= "ss";
    } elseif (!empty($_GET['filter_month']) && !empty($_GET['filter_year'])) {
        $sql .= " AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
        $params[] = $_GET['filter_month'];
        $params[] = $_GET['filter_year'];
        $types .= "ss";
    } elseif (!empty($_GET['filter_year'])) {
        $sql .= " AND YEAR(r.tanggal) = ?";
        $params[] = $_GET['filter_year'];
        $types .= "s";
    }
    
    if (!empty($_GET['product_id'])) {
        $sql .= " AND b.id_barang = ?";
        $params[] = $_GET['product_id'];
        $types .= "i";
    }
    
    if (!empty($_GET['category_id'])) {
        $sql .= " AND b.id_jenis = ?";
        $params[] = $_GET['category_id'];
        $types .= "i";
    }
    
    $sql .= " GROUP BY jb.nama_jenis";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $chart_data = [
        'labels' => [],
        'item_counts' => [],
        'values' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $chart_data['labels'][] = $row['nama_jenis'];
        $chart_data['item_counts'][] = (int)$row['total_items'];
        $chart_data['values'][] = (float)$row['total_value'];
    }
    
    echo json_encode($chart_data);
    
    $stmt->close();
    $conn->close();
    exit();
}