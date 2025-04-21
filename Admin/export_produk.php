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

// Fetch products with category join
$query = "
    SELECT produk.id, produk.nama, produk.harga_beli, produk.harga_jual, produk.stok, kategori.nama_kategori
    FROM produk
    LEFT JOIN kategori ON produk.kategori_id = kategori.id
    ORDER BY produk.nama ASC
";
$result = mysqli_query($conn, $query);

// Create a PDF instance in landscape mode
class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo - if you have a logo file in your server
        // $this->Image('logo.png', 10, 6, 30);
        
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'DAFTAR PRODUK BENGKEL', 0, 1, 'C');
        
        // Subtitle with date
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Tanggal: ' . date('d/m/Y'), 0, 1, 'C');
        $this->Ln(10);
        
        // Table header
        $this->SetFillColor(21, 101, 192); // Blue header
        $this->SetTextColor(255); // White text
        $this->SetDrawColor(21, 101, 192); // Blue border
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Header content (adjust widths to match your needs)
        $this->Cell(10, 10, 'No', 1, 0, 'C', true);
        $this->Cell(70, 10, 'Nama Produk', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Kategori', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Harga Beli', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Harga Jual', 1, 0, 'C', true);
        $this->Cell(20, 10, 'Stok', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Nilai Inventaris', 1, 1, 'C', true);
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
}

// Instantiate PDF document (Landscape - 'L')
$pdf = new PDF('L', 'mm', 'A4');

// Set document information
$pdf->SetTitle('Daftar Produk Bengkel');
$pdf->SetAuthor('Sistem Kasir Bengkel');
$pdf->SetCreator('FPDF');

// Add a page
$pdf->AliasNbPages();
$pdf->AddPage();

// Set font for data
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

// Variables for total calculation
$no = 1;
$total_inventory_value = 0;

// Output data rows
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate inventory value
        $inventory_value = $row['harga_beli'] * $row['stok'];
        $total_inventory_value += $inventory_value;
        
        // Set row colors alternating
        if ($no % 2 == 0) {
            $pdf->SetFillColor(240, 240, 240); // Light grey for even rows
            $fill = true;
        } else {
            $pdf->SetFillColor(255, 255, 255); // White for odd rows
            $fill = true;
        }
        
        // Determine stock status and set color
        if ($row['stok'] <= 5) {
            $pdf->SetTextColor(194, 24, 7); // Red for low stock
        } else if ($row['stok'] <= 10) {
            $pdf->SetTextColor(230, 126, 34); // Orange for warning stock
        } else {
            $pdf->SetTextColor(46, 125, 50); // Green for ok stock
        }
        
        // Item number
        $pdf->SetTextColor(0);
        $pdf->Cell(10, 8, $no, 'LR', 0, 'C', $fill);
        
        // Product name - longer field
        $pdf->Cell(70, 8, utf8_decode($row['nama']), 'LR', 0, 'L', $fill);
        
        // Category
        $kategori = empty($row['nama_kategori']) ? 'Tidak Terkategori' : $row['nama_kategori'];
        $pdf->Cell(40, 8, utf8_decode($kategori), 'LR', 0, 'L', $fill);
        
        // Purchase price
        $pdf->Cell(40, 8, 'Rp ' . number_format($row['harga_beli'], 0, ',', '.'), 'LR', 0, 'R', $fill);
        
        // Sale price
        $pdf->Cell(40, 8, 'Rp ' . number_format($row['harga_jual'], 0, ',', '.'), 'LR', 0, 'R', $fill);
        
        // Stock with proper color
        if ($row['stok'] <= 5) {
            $pdf->SetTextColor(194, 24, 7); // Red for low stock
        } else if ($row['stok'] <= 10) {
            $pdf->SetTextColor(230, 126, 34); // Orange for warning stock
        } else {
            $pdf->SetTextColor(46, 125, 50); // Green for ok stock
        }
        $pdf->Cell(20, 8, $row['stok'], 'LR', 0, 'C', $fill);
        
        // Reset text color
        $pdf->SetTextColor(0);
        
        // Inventory value
        $pdf->Cell(40, 8, 'Rp ' . number_format($inventory_value, 0, ',', '.'), 'LR', 1, 'R', $fill);
        
        $no++;
    }
    
    // Close the table with a line
    $pdf->Cell(260, 0, '', 'T', 1);
    
    // Add total row
    $pdf->SetFillColor(235, 245, 251); // Light blue background
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(180, 10, 'TOTAL NILAI INVENTARIS', 1, 0, 'R', true);
    $pdf->Cell(80, 10, 'Rp ' . number_format($total_inventory_value, 0, ',', '.'), 1, 1, 'R', true);
    
    // Add summary information
    $pdf->Ln(10);

    // Get summary statistics
    $count_query = "SELECT COUNT(*) as total FROM produk";
    $count_result = mysqli_query($conn, $count_query);
    $count_data = mysqli_fetch_assoc($count_result);
    
    $low_stock_query = "SELECT COUNT(*) as total FROM produk WHERE stok <= 5";
    $low_stock_result = mysqli_query($conn, $low_stock_query);
    $low_stock_data = mysqli_fetch_assoc($low_stock_result);
    
    // Summary box
    $pdf->SetFillColor(21, 101, 192); // Blue header
    $pdf->SetTextColor(255); // White text
    $pdf->Cell(260, 8, 'RINGKASAN INVENTARIS', 1, 1, 'C', true);
    
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial', '', 10);
    
    // Summary content
    $pdf->Cell(87, 8, 'Total Produk: ' . $count_data['total'], 1, 0, 'L', true);
    $pdf->Cell(87, 8, 'Produk Stok Menipis: ' . $low_stock_data['total'], 1, 0, 'L', true);
    $pdf->Cell(86, 8, 'Laporan Dibuat Oleh: ' . $_SESSION['nama'], 1, 1, 'L', true);
    
} else {
    // No products found
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(260, 20, 'Tidak ada data produk yang tersedia.', 0, 1, 'C');
}

// Output PDF
$pdf->Output('I', 'Daftar_Produk_Bengkel_' . date('Y-m-d') . '.pdf');


// Close database connection
mysqli_close($conn);
?>