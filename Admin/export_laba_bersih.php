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

// Set default month or get from request
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Get laba bersih data for the specified month
$query = "
    SELECT lb.*, DATE_FORMAT(CONCAT(lb.bulan, '-01'), '%M %Y') AS bulan_format
    FROM laba_bersih lb
    WHERE lb.bulan = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['message'] = "Data laba bersih untuk bulan yang dipilih tidak ditemukan!";
    $_SESSION['alert_type'] = "danger";
    header("Location: laba_bersih.php");
    exit();
}

// Get detail pengeluaran
$detail_query = "
    SELECT pd.nama_pengeluaran, pd.jumlah
    FROM pengeluaran_detail pd
    WHERE pd.laba_bersih_id = ?
";
$stmt = mysqli_prepare($conn, $detail_query);
mysqli_stmt_bind_param($stmt, 'i', $data['id']);
mysqli_stmt_execute($stmt);
$detail_result = mysqli_stmt_get_result($stmt);
$pengeluaran_detail = [];
while ($row = mysqli_fetch_assoc($detail_result)) {
    $pengeluaran_detail[] = $row;
}

// Create a PDF instance in portrait mode
class PDF extends FPDF {
    // Page header
    function Header() {
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'LAPORAN LABA BERSIH BENGKEL', 0, 1, 'C');
        
        // Get month from global variable
        global $data;
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, strtoupper($data['bulan_format']), 0, 1, 'C');
        
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
}

// Instantiate PDF document (Portrait - 'P')
$pdf = new PDF('P', 'mm', 'A4');

// Set document information
$pdf->SetTitle('Laporan Laba Bersih - ' . $data['bulan_format']);
$pdf->SetAuthor('Sistem Kasir Bengkel');
$pdf->SetCreator('FPDF');

// Add a page
$pdf->AliasNbPages();
$pdf->AddPage();

// === SECTION 1: BASIC INFORMATION ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'INFORMASI LAPORAN', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border

// Basic Info Table
$pdf->SetFillColor(245, 245, 245); // Light gray
$pdf->Cell(50, 8, 'Periode', 1, 0, 'L', true);
$pdf->Cell(140, 8, ': ' . $data['bulan_format'], 1, 1, 'L', true);

$pdf->Cell(50, 8, 'Dibuat Oleh', 1, 0, 'L');
$pdf->Cell(140, 8, ': ' . $_SESSION['nama'], 1, 1, 'L');

$pdf->Ln(5);

// === SECTION 2: LABA KOTOR ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'LABA KOTOR', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border

// Laba Kotor Value
$pdf->SetFillColor(245, 245, 245); // Light gray
$pdf->Cell(190, 8, 'Rp ' . number_format($data['laba_kotor'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(5);

// === SECTION 3: PENGELUARAN ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'DETAIL PENGELUARAN', 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border
$pdf->SetFillColor(235, 235, 235); // Header background

// Table Header
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(130, 8, 'Nama Pengeluaran', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Jumlah (Rp)', 1, 1, 'C', true);

// Table content
$pdf->SetFont('Arial', '', 10);
$no = 1;
$fill = false;

if (count($pengeluaran_detail) > 0) {
    foreach ($pengeluaran_detail as $item) {
        // Alternating row colors
        $fill = !$fill;
        if ($fill) {
            $pdf->SetFillColor(245, 245, 245); // Light gray for even rows
        } else {
            $pdf->SetFillColor(255, 255, 255); // White for odd rows
        }
        
        $pdf->Cell(10, 8, $no, 1, 0, 'C', $fill);
        $pdf->Cell(130, 8, utf8_decode($item['nama_pengeluaran']), 1, 0, 'L', $fill);
        $pdf->Cell(50, 8, number_format($item['jumlah'], 0, ',', '.'), 1, 1, 'R', $fill);
        
        $no++;
    }
} else {
    $pdf->Cell(190, 8, 'Tidak ada data pengeluaran', 1, 1, 'C');
}

// Total Pengeluaran
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->Cell(140, 8, 'TOTAL PENGELUARAN', 1, 0, 'R', true);
$pdf->Cell(50, 8, 'Rp ' . number_format($data['pengeluaran'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(5);

// === SECTION 4: SUMMARY - LABA BERSIH ===
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(21, 101, 192); // Blue header
$pdf->SetTextColor(255); // White text
$pdf->SetDrawColor(21, 101, 192); // Blue border
$pdf->Cell(0, 8, 'PERHITUNGAN LABA BERSIH', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Black text
$pdf->SetDrawColor(0); // Black border
$pdf->SetFillColor(245, 245, 245); // Light gray

// Summary calculation
$pdf->Cell(140, 8, 'Laba Kotor', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Rp ' . number_format($data['laba_kotor'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Cell(140, 8, 'Total Pengeluaran', 1, 0, 'L');
$pdf->Cell(50, 8, 'Rp ' . number_format($data['pengeluaran'], 0, ',', '.'), 1, 1, 'R');

// Final result - Laba Bersih
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(46, 125, 50); // Green for positive result
$pdf->SetTextColor(255); // White text

// Change color to red if negative profit
if ($data['laba_bersih'] < 0) {
    $pdf->SetFillColor(198, 40, 40); // Red for negative result
}

$pdf->Cell(140, 8, 'LABA BERSIH', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Rp ' . number_format($data['laba_bersih'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(10);

// === SECTION 5: NOTES & SIGNATURE ===
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(0); // Black text
$pdf->Cell(0, 6, 'Catatan: Laporan ini digenerate secara otomatis oleh sistem.', 0, 1, 'L');

// Signature area
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(60, 6, '.................., ' . date('d F Y'), 0, 1, 'C');
$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(60, 6, 'Admin/Pemilik', 0, 1, 'C');

$pdf->Ln(15);

$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(60, 6, '(..................................)', 0, 1, 'C');

// Output PDF
$pdf->Output('I', 'Laporan_Laba_Bersih_' . str_replace('-', '_', $bulan) . '.pdf');

// Close database connection
mysqli_close($conn);
?>