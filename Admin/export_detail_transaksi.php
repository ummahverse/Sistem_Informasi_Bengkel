<?php
session_start();

// Check if logged in and admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../config.php';

// Load FPDF library
require('../fpdf186/fpdf.php');

// Get filter parameters
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$kasir = isset($_GET['kasir']) ? $_GET['kasir'] : '';

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $_SESSION['message'] = "Format bulan tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: laporan.php");
    exit();
}

// Build where clause for filters
$where_clause = " WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = '$bulan' ";
if (!empty($kasir)) {
    $where_clause .= " AND t.kasir = '$kasir' ";
}

// Query untuk mendapatkan data transaksi dengan harga beli dan harga jual
$query = "SELECT t.id, t.tanggal, t.total, u.nama AS kasir,
          SUM(td.jumlah * IFNULL(p.harga_beli, 0)) AS total_harga_beli,
          SUM(td.subtotal) AS total_harga_jual,
          (SUM(td.subtotal) - SUM(td.jumlah * IFNULL(p.harga_beli, 0))) AS keuntungan
          FROM transaksi t
          JOIN users u ON t.kasir = u.username
          JOIN transaksi_detail td ON t.id = td.transaksi_id
          JOIN produk p ON td.produk_id = p.id
          $where_clause
          GROUP BY t.id, t.tanggal, t.total, u.nama
          ORDER BY t.tanggal ASC";
$result = mysqli_query($conn, $query);

// Hitung total pendapatan, modal, dan keuntungan tanpa duplikasi
$summary_query = "
    SELECT 
        (
            SELECT SUM(t.total)
            FROM transaksi t
            $where_clause
        ) AS total_pendapatan,
        
        SUM(td.jumlah * IFNULL(p.harga_beli, 0)) AS total_modal,
        SUM(td.subtotal - (td.jumlah * IFNULL(p.harga_beli, 0))) AS total_keuntungan,
        COUNT(DISTINCT t.id) AS jumlah_transaksi
    FROM transaksi t
    JOIN transaksi_detail td ON t.id = td.transaksi_id
    JOIN produk p ON td.produk_id = p.id
    $where_clause
";

$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Get top 5 products
$top_products_query = "SELECT 
                      p.nama AS nama_produk,
                      SUM(td.jumlah) AS jumlah_terjual,
                      SUM(td.subtotal) AS total_penjualan,
                      SUM(td.subtotal - (td.jumlah * IFNULL(p.harga_beli, 0))) AS keuntungan
                      FROM transaksi_detail td
                      JOIN produk p ON td.produk_id = p.id
                      JOIN transaksi t ON td.transaksi_id = t.id
                      $where_clause
                      GROUP BY p.id, p.nama
                      ORDER BY jumlah_terjual DESC
                      LIMIT 5";
$top_products_result = mysqli_query($conn, $top_products_query);

// Get category sales data
$kategori_query = "SELECT 
                  k.nama_kategori,
                  SUM(td.subtotal) AS total_penjualan,
                  SUM(td.jumlah) AS jumlah_terjual
                  FROM transaksi_detail td
                  JOIN produk p ON td.produk_id = p.id
                  JOIN kategori k ON p.kategori_id = k.id
                  JOIN transaksi t ON td.transaksi_id = t.id
                  $where_clause
                  GROUP BY k.nama_kategori
                  ORDER BY total_penjualan DESC";
$kategori_result = mysqli_query($conn, $kategori_query);

// Get net profit data for the selected month
$laba_bersih_query = "SELECT lb.laba_kotor, lb.pengeluaran, lb.laba_bersih
                      FROM laba_bersih lb 
                      WHERE lb.bulan = '$bulan' 
                      LIMIT 1";
$laba_bersih_result = mysqli_query($conn, $laba_bersih_query);
$laba_bersih_data = mysqli_fetch_assoc($laba_bersih_result);

// Get kasir name if filter is applied
$kasir_name = '';
if (!empty($kasir)) {
    $kasir_query = "SELECT nama FROM users WHERE username = '$kasir' LIMIT 1";
    $kasir_result = mysqli_query($conn, $kasir_query);
    if ($kasir_result && mysqli_num_rows($kasir_result) > 0) {
        $kasir_row = mysqli_fetch_assoc($kasir_result);
        $kasir_name = $kasir_row['nama'];
    }
}

// Create PDF class
class PDF extends FPDF {
    // Page header
    function Header() {
        global $bulan, $kasir_name;
        
        // Logo or Shop Name
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'LAPORAN PENJUALAN BULANAN', 0, 1, 'C');
        
        // Month info
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'PERIODE: ' . date('F Y', strtotime($bulan . '-01')), 0, 1, 'C');
        
        // Kasir filter if applied
        if (!empty($kasir_name)) {
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'Filter Kasir: ' . $kasir_name, 0, 1, 'C');
        }
        
        // Date generated
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Tanggal Cetak: ' . date('d/m/Y'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    // Page footer
    function Footer() {
        // Position 15 mm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Check if adding content would cause a page break
    function CheckPageBreak($h) {
        // If the height of the element would cause an overflow, add a new page
        if($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            return true;
        }
        return false;
    }
    
    // Function to print a transaction block
    function PrintTransaction($no, $transaksi, $conn) {
        $transaction_id = $transaksi['id'];
        $table_width = 190;
        
        // Calculate approximate height needed for this transaction
        // Main row + customer row + products header + estimate 2 products + spacing
        $estimated_height = 8 + 6 + 5 + (5 * 2) + 4; 
        
        // Check if we need a page break before this transaction
        if ($this->GetY() + $estimated_height > $this->PageBreakTrigger) {
            $this->AddPage();
            
            // Reprint the transactions table header
            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(21, 101, 192);
            $this->SetTextColor(255);
            $this->SetDrawColor(21, 101, 192);
            $this->Cell(0, 8, 'DAFTAR TRANSAKSI (lanjutan)', 1, 1, 'L', true);
            
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(0);
            $this->SetDrawColor(0);
            $this->SetFillColor(235, 235, 235);
            
            $this->Cell(10, 8, 'No', 1, 0, 'C', true);
            $this->Cell(25, 8, 'ID', 1, 0, 'C', true);
            $this->Cell(35, 8, 'Tanggal', 1, 0, 'C', true);
            $this->Cell(35, 8, 'Kasir', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Pendapatan (Rp)', 1, 0, 'C', true);
            $this->Cell(25, 8, 'Modal (Rp)', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Laba (Rp)', 1, 1, 'C', true);
        }
        
        // Determine row fill color
        $fill = ($no % 2 == 0);
        if ($fill) {
            $this->SetFillColor(245, 245, 245); // Light gray for even rows
        } else {
            $this->SetFillColor(255, 255, 255); // White for odd rows
        }
        
        // Main transaction row
        $this->SetFont('Arial', '', 9);
        $this->Cell(10, 8, $no, 1, 0, 'C', $fill);
        $this->Cell(25, 8, '#' . $transaksi['id'], 1, 0, 'C', $fill);
        $this->Cell(35, 8, date('d/m/Y H:i', strtotime($transaksi['tanggal'])), 1, 0, 'C', $fill);
        $this->Cell(35, 8, utf8_decode($transaksi['kasir']), 1, 0, 'L', $fill);
        $this->Cell(30, 8, number_format($transaksi['total'], 0, ',', '.'), 1, 0, 'R', $fill);
        $this->Cell(25, 8, number_format($transaksi['total_harga_beli'], 0, ',', '.'), 1, 0, 'R', $fill);
        $this->Cell(30, 8, number_format($transaksi['keuntungan'], 0, ',', '.'), 1, 1, 'R', $fill);
        
        // Get customer details
        $customer_query = "SELECT nama_customer, no_whatsapp, alamat, plat_nomor_motor FROM transaksi WHERE id = $transaction_id";
        $customer_result = mysqli_query($conn, $customer_query);
        $customer_data = mysqli_fetch_assoc($customer_result);
        
        // Display customer details
        if ($customer_data) {
            $this->SetFillColor(230, 237, 244); // Light blue for customer details
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(10, 6, '', 1, 0, 'L', true);
            $this->Cell(85, 6, 'Pelanggan: ' . utf8_decode($customer_data['nama_customer']), 1, 0, 'L', true);
            $this->Cell(45, 6, 'WA: ' . $customer_data['no_whatsapp'], 1, 0, 'L', true);
            $this->Cell(50, 6, 'Plat: ' . strtoupper($customer_data['plat_nomor_motor']), 1, 1, 'L', true);
        }
        
        // Get products for this transaction
        $products_query = "SELECT p.nama AS product_name, td.jumlah AS quantity, td.harga_satuan AS price, td.subtotal 
                          FROM transaksi_detail td
                          JOIN produk p ON td.produk_id = p.id
                          WHERE td.transaksi_id = $transaction_id";
        $products_result = mysqli_query($conn, $products_query);
        $product_count = mysqli_num_rows($products_result);
        
        // Display products header
        $this->SetFont('Arial', 'I', 8);
        $this->SetFillColor(235, 235, 235);
        $this->Cell(10, 5, '', 1, 0, 'C', true);
        $this->Cell(85, 5, 'Produk', 1, 0, 'C', true);
        $this->Cell(25, 5, 'Jumlah', 1, 0, 'C', true);
        $this->Cell(35, 5, 'Harga Satuan', 1, 0, 'C', true);
        $this->Cell(35, 5, 'Subtotal', 1, 1, 'C', true);
        
        // Check if we're going to run out of space for products
        // If there are many products, we might need a page break
        if ($this->GetY() + ($product_count * 5) > $this->PageBreakTrigger) {
            $this->AddPage();
            
            // Remind reader which transaction these products belong to
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(0, 8, 'Daftar Produk untuk Transaksi #' . $transaction_id, 0, 1, 'L');
            
            // Reprint products header
            $this->SetFont('Arial', 'I', 8);
            $this->SetFillColor(235, 235, 235);
            $this->Cell(10, 5, '', 1, 0, 'C', true);
            $this->Cell(85, 5, 'Produk', 1, 0, 'C', true);
            $this->Cell(25, 5, 'Jumlah', 1, 0, 'C', true);
            $this->Cell(35, 5, 'Harga Satuan', 1, 0, 'C', true);
            $this->Cell(35, 5, 'Subtotal', 1, 1, 'C', true);
        }
        
        // Display products
        if ($product_count > 0) {
            $rowCounter = 0;
            while ($product = mysqli_fetch_assoc($products_result)) {
                $rowColor = ($rowCounter % 2 == 0) ? 240 : 250;
                $this->SetFillColor($rowColor, $rowColor, $rowColor);
                
                $this->Cell(10, 5, '', 1, 0, 'C', true);
                $this->Cell(85, 5, utf8_decode($product['product_name']), 1, 0, 'L', true);
                $this->Cell(25, 5, $product['quantity'], 1, 0, 'C', true);
                $this->Cell(35, 5, number_format($product['price'], 0, ',', '.'), 1, 0, 'R', true);
                $this->Cell(35, 5, number_format($product['subtotal'], 0, ',', '.'), 1, 1, 'R', true);
                
                $rowCounter++;
            }
        } else {
            $this->Cell($table_width, 5, 'Tidak ada data produk', 1, 1, 'C', true);
        }
        
        // Add a small gap between transactions
        $this->Cell($table_width, 3, '', 0, 1);
    }
}

// Instantiate PDF document
$pdf = new PDF('P', 'mm', 'A4');

// Set document information
$pdf->SetTitle('Laporan Bulanan ' . date('F Y', strtotime($bulan . '-01')));
$pdf->SetAuthor('Sistem Kasir Bengkel');
$pdf->SetCreator('FPDF');

// Add a page
$pdf->AliasNbPages();
$pdf->AddPage();

// === SECTION 1: SUMMARY ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'RINGKASAN LAPORAN', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border

// Summary Table
$pdf->SetFillColor(245, 245, 245); // Light gray

$total_pendapatan = $summary['total_pendapatan'] ?: 0;
$total_modal = $summary['total_modal'] ?: 0;
$total_keuntungan = $summary['total_keuntungan'] ?: 0;
$jumlah_transaksi = $summary['jumlah_transaksi'] ?: 0;

$pdf->Cell(50, 8, 'Total Transaksi', 1, 0, 'L', true);
$pdf->Cell(140, 8, ': ' . $jumlah_transaksi . ' transaksi', 1, 1, 'L', true);

$pdf->Cell(50, 8, 'Total Pendapatan', 1, 0, 'L');
$pdf->Cell(140, 8, ': Rp ' . number_format($total_pendapatan, 0, ',', '.'), 1, 1, 'L');

$pdf->Cell(50, 8, 'Total Modal', 1, 0, 'L', true);
$pdf->Cell(140, 8, ': Rp ' . number_format($total_modal, 0, ',', '.'), 1, 1, 'L', true);

$pdf->Cell(50, 8, 'Total Laba Kotor', 1, 0, 'L');
$pdf->Cell(140, 8, ': Rp ' . number_format($total_keuntungan, 0, ',', '.'), 1, 1, 'L');

// Calculate gross profit margin (renamed from profit margin)
$profit_margin = ($total_pendapatan > 0) ? ($total_keuntungan / $total_pendapatan) * 100 : 0;
$pdf->Cell(50, 8, 'Margin Laba Kotor', 1, 0, 'L', true);
$pdf->Cell(140, 8, ': ' . number_format($profit_margin, 2) . '%', 1, 1, 'L', true);

// Add net profit information if available
if ($laba_bersih_data) {
    $pdf->Cell(50, 8, 'Total Pengeluaran', 1, 0, 'L');
    $pdf->Cell(140, 8, ': Rp ' . number_format($laba_bersih_data['pengeluaran'], 0, ',', '.'), 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'Total Laba Bersih', 1, 0, 'L', true);
    $pdf->Cell(140, 8, ': Rp ' . number_format($laba_bersih_data['laba_bersih'], 0, ',', '.'), 1, 1, 'L', true);
    
    // Add net profit margin calculation
    $net_profit_margin = ($total_pendapatan > 0) ? ($laba_bersih_data['laba_bersih'] / $total_pendapatan) * 100 : 0;
    $pdf->Cell(50, 8, 'Margin Laba Bersih', 1, 0, 'L');
    $pdf->Cell(140, 8, ': ' . number_format($net_profit_margin, 2) . '%', 1, 1, 'L');
}

$pdf->Ln(5);

// === SECTION 2: TOP 5 PRODUCTS ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'PRODUK TERLARIS', 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border
$pdf->SetFillColor(235, 235, 235); // Header background

// Table Header for Top Products
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(85, 8, 'Nama Produk', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Jumlah', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total (Rp)', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Laba (Rp)', 1, 1, 'C', true);

// Table content for Top Products
$pdf->SetFont('Arial', '', 9);
$no = 1;
$fill = false;

if (mysqli_num_rows($top_products_result) > 0) {
    while ($product = mysqli_fetch_assoc($top_products_result)) {
        // Alternating row colors
        $fill = !$fill;
        if ($fill) {
            $pdf->SetFillColor(245, 245, 245); // Light gray for even rows
        } else {
            $pdf->SetFillColor(255, 255, 255); // White for odd rows
        }
        
        $pdf->Cell(10, 8, $no, 1, 0, 'C', $fill);
        $pdf->Cell(85, 8, utf8_decode($product['nama_produk']), 1, 0, 'L', $fill);
        $pdf->Cell(25, 8, $product['jumlah_terjual'], 1, 0, 'C', $fill);
        $pdf->Cell(35, 8, number_format($product['total_penjualan'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell(35, 8, number_format($product['keuntungan'], 0, ',', '.'), 1, 1, 'R', $fill);
        
        $no++;
    }
} else {
    $pdf->Cell(190, 8, 'Tidak ada data produk', 1, 1, 'C');
}

$pdf->Ln(5);

// Check if we need a new page for the next section
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

// === SECTION 3: CATEGORY SUMMARY ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'PENJUALAN PER KATEGORI', 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border
$pdf->SetFillColor(235, 235, 235); // Header background

// Table Header for Categories
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(85, 8, 'Kategori', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Jumlah Terjual', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Total Penjualan (Rp)', 1, 1, 'C', true);

// Table content for Categories
$pdf->SetFont('Arial', '', 9);
$no = 1;
$fill = false;

if (mysqli_num_rows($kategori_result) > 0) {
    while ($kategori = mysqli_fetch_assoc($kategori_result)) {
        // Alternating row colors
        $fill = !$fill;
        if ($fill) {
            $pdf->SetFillColor(245, 245, 245); // Light gray for even rows
        } else {
            $pdf->SetFillColor(255, 255, 255); // White for odd rows
        }
        
        $pdf->Cell(10, 8, $no, 1, 0, 'C', $fill);
        $pdf->Cell(85, 8, utf8_decode($kategori['nama_kategori']), 1, 0, 'L', $fill);
        $pdf->Cell(35, 8, $kategori['jumlah_terjual'], 1, 0, 'C', $fill);
        $pdf->Cell(60, 8, number_format($kategori['total_penjualan'], 0, ',', '.'), 1, 1, 'R', $fill);
        
        $no++;
    }
} else {
    $pdf->Cell(190, 8, 'Tidak ada data kategori', 1, 1, 'C');
}

$pdf->Ln(5);

// Check if we need a new page for the next section
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}


// Check if we need a new page for the transactions section
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

// === SECTION 5: DETAILED TRANSACTIONS ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'DAFTAR TRANSAKSI', 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border
$pdf->SetFillColor(235, 235, 235); // Header background

// Table Header for Transactions
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'ID', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Kasir', 1, 0, 'C', true); 
$pdf->Cell(30, 8, 'Pendapatan (Rp)', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Modal (Rp)', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Laba (Rp)', 1, 1, 'C', true);

// Process transactions one by one using the dedicated function
if (mysqli_num_rows($result) > 0) {
    $no = 1;
    
    while ($transaksi = mysqli_fetch_assoc($result)) {
        $pdf->PrintTransaction($no, $transaksi, $conn);
        $no++;
    }
} else {
    $pdf->Cell(190, 8, 'Tidak ada data transaksi untuk periode ini', 1, 1, 'C');
}

// Output PDF
$pdf->Output('I', 'Laporan_Bulanan' . date('Y-m-d') . '.pdf');

// Close database connection
mysqli_close($conn);
?>