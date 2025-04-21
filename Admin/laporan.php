<?php
session_start();

// Cek apakah sudah login dan sebagai admin dengan namespace baru
if (!isset($_SESSION['admin']['logged_in']) || $_SESSION['admin']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Pastikan path ke config.php benar

// Set default filter dates and handle period selection
$period = isset($_GET['period']) ? $_GET['period'] : '';
$today = date('Y-m-d');

// Default to 7 days if no period/dates specified
if (empty($period) && !isset($_GET['dari_tanggal']) && !isset($_GET['sampai_tanggal']) && !isset($_GET['bulan_transaksi'])) {
    $period = '7days';
}

// Set date ranges based on period selection
if ($period == '7days') {
    $dari_tanggal = date('Y-m-d', strtotime('-7 days'));
    $sampai_tanggal = $today;
} elseif ($period == '1month') {
    $dari_tanggal = date('Y-m-d', strtotime('-30 days'));
    $sampai_tanggal = $today;
} else {
    // Use custom date range if provided
    $dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : date('Y-m-d', strtotime('-7 days'));
    $sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : $today;
}

$kasir = isset($_GET['kasir']) ? $_GET['kasir'] : '';

// Get bulan_transaksi filter - our single month filter for all data
$bulan_transaksi = isset($_GET['bulan_transaksi']) ? $_GET['bulan_transaksi'] : date('Y-m');

// Build where clause for filters
$where_clause = " WHERE 1=1 ";

// If bulan_transaksi is set, it takes precedence over other date filters
if (isset($_GET['bulan_transaksi']) && !empty($_GET['bulan_transaksi'])) {
    $where_clause = " WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = '$bulan_transaksi' ";
} else if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $where_clause .= " AND DATE(t.tanggal) BETWEEN '$dari_tanggal' AND '$sampai_tanggal' ";
}

if (!empty($kasir)) {
    $where_clause .= " AND t.kasir = '$kasir' ";
}

// Query untuk mendapatkan data transaksi dengan harga beli dan harga jual
$query = "SELECT t.id, t.tanggal, t.total, u.nama AS kasir,
          SUM(td.jumlah * IFNULL(p.harga_beli, 0)) AS total_harga_beli,
          SUM(td.subtotal) AS total_harga_jual,
          (SUM(td.subtotal) - SUM(td.jumlah * IFNULL(p.harga_beli, 0))) AS keuntungan
          FROM transaksi t
          JOIN karyawan u ON t.kasir = u.username
          JOIN transaksi_detail td ON t.id = td.transaksi_id
          JOIN produk p ON td.produk_id = p.id
          $where_clause
          GROUP BY t.id, t.tanggal, t.total, u.nama
          ORDER BY t.tanggal DESC";
$result = mysqli_query($conn, $query);

// Hitung total keuntungan dengan filter
$total_keuntungan_query = "SELECT 
                           SUM(td.subtotal) - SUM(td.jumlah * IFNULL(p.harga_beli, 0)) AS total_keuntungan
                           FROM transaksi_detail td 
                           JOIN produk p ON td.produk_id = p.id
                           JOIN transaksi t ON td.transaksi_id = t.id
                           $where_clause";
$total_keuntungan_result = mysqli_query($conn, $total_keuntungan_query);
$total_keuntungan_row = mysqli_fetch_assoc($total_keuntungan_result);
$total_keuntungan = $total_keuntungan_row['total_keuntungan'] ?: 0;

// Hitung total pendapatan dengan filter
$total_query = "SELECT SUM(total) AS total_pendapatan FROM transaksi t $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_pendapatan = $total_row['total_pendapatan'] ?: 0;

// Hitung total transaksi dengan filter
$count_query = "SELECT COUNT(*) AS total_transaksi FROM transaksi t $where_clause";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_transaksi = $count_row['total_transaksi'];

// Ambil data laba bersih untuk bulan yang dipilih
$bulan_filter = date('Y-m', strtotime($dari_tanggal));
$laba_bersih_query = "SELECT laba_bersih FROM laba_bersih WHERE bulan = '$bulan_transaksi' LIMIT 1";
$laba_bersih_result = mysqli_query($conn, $laba_bersih_query);

if ($laba_bersih_result && mysqli_num_rows($laba_bersih_result) > 0) {
    $laba_bersih_row = mysqli_fetch_assoc($laba_bersih_result);
    $laba_bersih = $laba_bersih_row['laba_bersih'];
} else {
    $laba_bersih = 0;
}

// Query untuk data grafik pendapatan dan keuntungan per hari
$chart_query = "SELECT 
    DATE(t.tanggal) as tanggal,
    SUM(td.subtotal) AS pendapatan,
    SUM(td.subtotal - (td.jumlah * IFNULL(p.harga_beli, 0))) AS keuntungan
    FROM transaksi t
    JOIN transaksi_detail td ON t.id = td.transaksi_id
    JOIN produk p ON td.produk_id = p.id
    WHERE DATE(t.tanggal) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'
    " . (!empty($kasir) ? " AND t.kasir = '$kasir' " : "") . "
    GROUP BY DATE(t.tanggal)
    ORDER BY DATE(t.tanggal) ASC";

$chart_result = mysqli_query($conn, $chart_query);

$chart_dates = [];
$chart_income = [];
$chart_profit = [];

while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_dates[] = date('d M', strtotime($row['tanggal']));
    $chart_income[] = (float)$row['pendapatan'];
    $chart_profit[] = (float)$row['keuntungan'];
}

$chart_dates_json = json_encode($chart_dates);
$chart_income_json = json_encode($chart_income);
$chart_profit_json = json_encode($chart_profit);

// Query untuk data laba bersih bulanan (12 bulan terakhir)
$laba_bersih_chart_query = "SELECT 
    bulan,
    laba_kotor,
    pengeluaran,
    laba_bersih,
    (SELECT SUM(total) FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = laba_bersih.bulan) as pendapatan
    FROM laba_bersih
    WHERE bulan <= '$bulan_transaksi'
    ORDER BY bulan DESC
    LIMIT 12";

$laba_bersih_chart_result = mysqli_query($conn, $laba_bersih_chart_query);

$laba_bersih_months = [];
$laba_kotor_data = [];
$pengeluaran_data = [];
$laba_bersih_data = [];
$pendapatan_data = [];

while ($row = mysqli_fetch_assoc($laba_bersih_chart_result)) {
    // Add data in reverse order (oldest first)
    array_unshift($laba_bersih_months, date('M Y', strtotime($row['bulan'] . '-01')));
    array_unshift($laba_kotor_data, (float)$row['laba_kotor']);
    array_unshift($pengeluaran_data, (float)$row['pengeluaran']);
    array_unshift($laba_bersih_data, (float)$row['laba_bersih']);
    array_unshift($pendapatan_data, (float)($row['pendapatan'] ?: 0));
}

$laba_bersih_months_json = json_encode($laba_bersih_months);
$laba_kotor_data_json = json_encode($laba_kotor_data);
$pengeluaran_data_json = json_encode($pengeluaran_data);
$laba_bersih_data_json = json_encode($laba_bersih_data);
$pendapatan_data_json = json_encode($pendapatan_data);

// Query untuk data pie chart - persentase kategori produk
$kategori_query = "SELECT 
                  k.nama_kategori,
                  SUM(td.subtotal) AS total_penjualan
                  FROM transaksi_detail td
                  JOIN produk p ON td.produk_id = p.id
                  JOIN kategori k ON p.kategori_id = k.id
                  JOIN transaksi t ON td.transaksi_id = t.id
                  $where_clause
                  GROUP BY k.nama_kategori";
$kategori_result = mysqli_query($conn, $kategori_query);
$kategori_data = [];
while ($row = mysqli_fetch_assoc($kategori_result)) {
    $kategori_data[] = $row;
}
$kategori_data_json = json_encode($kategori_data);

// Query untuk data produk terlaris
$produk_query = "SELECT 
                p.nama AS nama_produk,
                SUM(td.jumlah) AS jumlah_terjual,
                SUM(td.subtotal) AS total_penjualan
                FROM transaksi_detail td
                JOIN produk p ON td.produk_id = p.id
                JOIN transaksi t ON td.transaksi_id = t.id
                $where_clause
                GROUP BY p.id, p.nama
                ORDER BY jumlah_terjual DESC
                LIMIT 5";
$produk_result = mysqli_query($conn, $produk_query);
$produk_data = [];
while ($row = mysqli_fetch_assoc($produk_result)) {
    $produk_data[] = $row;
}
$produk_data_json = json_encode($produk_data);

// Query untuk mengecek ada tidaknya transaksi pada bulan tersebut
$bulan_transaksi = isset($_GET['bulan_transaksi']) ? $_GET['bulan_transaksi'] : date('Y-m');
$cek_transaksi_query = "SELECT COUNT(*) as total_transaksi 
                        FROM transaksi 
                        WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_transaksi' " . 
                        (!empty($kasir) ? " AND kasir = '$kasir'" : "");

$cek_result = mysqli_query($conn, $cek_transaksi_query);
$row = mysqli_fetch_assoc($cek_result);

// Cek jika tidak ada transaksi
$ada_transaksi = $row['total_transaksi'] > 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Penjualan - BMS Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      /* Admin purple theme */
      --primary-purple: #7E57C2;
      --secondary-purple: #5E35B1;
      --light-purple: #EDE7F6;
      --accent-purple: #4527A0;
      
      /* General colors */
      --white: #ffffff;
      --light-gray: #f8f9fa;
      --text-dark: #2C3E50;
      --border-color: #e1e7ef;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light-gray);
      margin: 0;
      padding: 0;
      color: var(--text-dark);
    }

    .content {
      margin-left: 280px;
      transition: margin-left 0.3s ease;
      min-height: 100vh;
    }

    /* Navbar Styling */
    .navbar {
      background: linear-gradient(135deg, var(--primary-purple), var(--accent-purple));
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border: none;
      padding: 0.8rem 1.5rem;
      margin-bottom: 20px;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--white);
      letter-spacing: 0.5px;
    }
    
    /* Page header section */
    .page-header {
      background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
      border-radius: 15px;
      padding: 30px;
      color: var(--white);
      margin-bottom: 25px;
      box-shadow: 0 6px 18px rgba(126, 87, 194, 0.15);
    }
    
    .page-header h1 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    /* Data card */
    .data-card, .filter-card, .table-card, .chart-container {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      background-color: var(--white);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
    }
    
    .data-card:hover, .chart-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.12);
    }
    
    /* Card header with actions */
    .card-header-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--light-purple);
    }
    
    .card-title, .chart-title, .filter-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary-purple);
      margin-bottom: 0;
    }
    
    /* Button styling */
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(126, 87, 194, 0.2);
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, var(--accent-purple), var(--primary-purple));
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(126, 87, 194, 0.3);
    }
    
    .btn-outline-light, .btn-outline-primary, .btn-outline-secondary {
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #F44336, #D32F2F);
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(244, 67, 54, 0.2);
    }
    
    .btn-danger:hover {
      background: linear-gradient(135deg, #D32F2F, #B71C1C);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(244, 67, 54, 0.3);
    }
    
    /* Form styling */
    .form-control, .form-select {
      border-radius: 8px;
      border: 1px solid #d1e3f0;
      padding: 0.75rem 1rem;
      transition: all 0.3s;
      background-color: var(--white);
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary-purple);
      box-shadow: 0 0 0 0.25rem rgba(126, 87, 194, 0.25);
    }
    
    /* Summary cards */
    .summary-card {
      border-radius: 15px;
      color: var(--white);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
      height: 100%;
      transition: all 0.3s ease;
    }
    
    .summary-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.18);
    }
    
    .summary-card {
      background: linear-gradient(135deg, #9575CD, #673AB7);
    }
    
    .summary-card.profit {
      background: linear-gradient(135deg, #66BB6A, #43A047);
    }
    
    .summary-card.transactions {
      background: linear-gradient(135deg, #FF7043, #E64A19);
    }
    
    .summary-card.net-profit {
      background: linear-gradient(135deg, #29B6F6, #0288D1);
    }
    
    .summary-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .summary-value {
      font-size: 1.8rem;
      font-weight: 700;
    }
    
    /* Table styling */
    .table {
      margin-bottom: 0;
    }
    
    .table thead {
      background-color: var(--light-purple);
    }
    
    .table thead th {
      color: var(--primary-purple);
      font-weight: 600;
      border-bottom: 2px solid var(--secondary-purple);
      padding: 1rem;
      vertical-align: middle;
    }
    
    .table tbody td {
      padding: 1rem;
      vertical-align: middle;
      border-color: var(--light-purple);
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(237, 231, 246, 0.3);
    }
    
    /* Period toggle buttons */
    .period-toggle {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    .period-toggle .btn {
      border-radius: 20px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .period-toggle .btn.active {
      background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
      color: white;
      box-shadow: 0 4px 10px rgba(126, 87, 194, 0.2);
    }
    
    /* Produk terlaris chart */
    .product-item {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 10px;
      background-color: var(--light-purple);
      position: relative;
    }
    
    .product-name {
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--accent-purple);
    }
    
    .product-progress {
      height: 8px;
      border-radius: 4px;
      margin-top: 5px;
      background-color: #e9ecef;
    }
    
    .product-progress .progress-bar {
      background: linear-gradient(135deg, var(--secondary-purple), var(--primary-purple));
      border-radius: 4px;
    }
    
    /* Alerts */
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .alert-success {
      background-color: #E8F5E9;
      color: #1B5E20;
    }
    
    .alert-danger {
      background-color: #FFEBEE;
      color: #B71C1C;
    }
    
    /* Responsive media queries */
    @media (max-width: 992px) {
      .content {
        margin-left: 0;
      }
    }
    
    @media (max-width: 768px) {
      .chart-container, .table-card, .filter-card, .data-card {
        padding: 1rem;
      }
      
      .page-header {
        padding: 1.5rem;
      }
      
      .table thead th, .table tbody td {
        padding: 0.75rem;
      }
      
      .summary-value {
        font-size: 1.5rem;
      }
      
      .period-toggle .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Content -->
  <div class="content">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container-fluid">
        <span class="navbar-brand">
          <i class="fas fa-chart-line me-2"></i>
          Laporan Penjualan
        </span>
        <div class="d-flex align-items-center">
          <span class="text-white me-3">
            <i class="fas fa-user-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['admin']['nama']) ?>
          </span>
          <a href="profile.php" class="btn btn-outline-light btn-sm me-2">
            <i class="fas fa-user-edit me-1"></i> Profil
          </a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
          </a>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <!-- Page Header -->
      <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1><i class="fas fa-chart-line me-2"></i>Laporan Penjualan</h1>
            <p class="lead mb-0">Berikut adalah laporan penjualan yang tercatat di sistem kasir.</p>
          </div>
          <span class="badge bg-light text-primary p-3 fs-6">
            <i class="fas fa-user-shield me-1"></i> Admin
          </span>
        </div>
      </div>
      
      <!-- Alert Messages -->
      <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $_SESSION['alert_type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php
        unset($_SESSION['message']);
        unset($_SESSION['alert_type']);
      endif;
      ?>
      
      <!-- Filter Section -->
      <div class="filter-card">
        <h5 class="filter-title mb-3">
          <i class="fas fa-filter me-2"></i>
          Filter Laporan
        </h5>
        <form action="" method="GET" class="row g-3">
          <!-- Opsi filter tanggal -->
          <div class="col-md-3">
            <label for="dari_tanggal" class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" id="dari_tanggal" name="dari_tanggal" value="<?= $dari_tanggal ?>">
          </div>
          <div class="col-md-3">
            <label for="sampai_tanggal" class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" id="sampai_tanggal" name="sampai_tanggal" value="<?= $sampai_tanggal ?>">
          </div>
          <div class="col-md-3">
            <label for="period" class="form-label">Periode Cepat</label>
            <select class="form-select" id="period" name="period">
              <option value="" <?= ($period == '') ? 'selected' : '' ?>>Kustom</option>
              <option value="7days" <?= ($period == '7days') ? 'selected' : '' ?>>7 Hari Terakhir</option>
              <option value="1month" <?= ($period == '1month') ? 'selected' : '' ?>>30 Hari Terakhir</option>
            </select>
          </div>
                
          <div class="col-md-3">
            <label for="kasir" class="form-label">Filter Kasir</label>
            <select class="form-select" id="kasir" name="kasir">
              <option value="">Semua Kasir</option>
              <!-- Tambahkan opsi kasir dari database jika diperlukan -->
              <?php
              $kasir_query = "SELECT DISTINCT u.username, u.nama FROM karyawan u JOIN transaksi t ON u.username = t.kasir ORDER BY u.nama";
              $kasir_result = mysqli_query($conn, $kasir_query);
              while ($kasir_row = mysqli_fetch_assoc($kasir_result)) {
                $selected = ($kasir == $kasir_row['username']) ? 'selected' : '';
                echo "<option value='" . $kasir_row['username'] . "' $selected>" . $kasir_row['nama'] . "</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search me-2"></i>
              Tampilkan Laporan
            </button>
            <a href="laporan.php" class="btn btn-outline-secondary ms-2">
              <i class="fas fa-redo me-2"></i>
              Reset Filter
            </a>
          </div>
        </form>
      </div>

      <!-- Summary Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
          <div class="summary-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="summary-title">Total Pendapatan</h6>
                <h2 class="summary-value">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h2>
              </div>
              <i class="fas fa-money-bill-wave fa-3x text-white-50"></i>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="summary-card profit">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="summary-title">Laba Kotor</h6>
                <h2 class="summary-value">Rp <?= number_format($total_keuntungan, 0, ',', '.') ?></h2>
              </div>
              <i class="fas fa-chart-line fa-3x text-white-50"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="summary-card transactions">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="summary-title">Total Transaksi</h6>
                <h2 class="summary-value"><?= $total_transaksi ?></h2>
              </div>
              <i class="fas fa-shopping-cart fa-3x text-white-50"></i>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="summary-card net-profit">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="summary-title">Laba Bersih</h6>
                <h2 class="summary-value">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h2>
              </div>
              <i class="fas fa-wallet fa-3x text-white-50"></i>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Chart Section -->
      <div class="row">
        <!-- Chart Tren Pendapatan dan Laba Kotor -->
        <div class="col-lg-6 mb-4">
          <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="chart-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Tren Pendapatan dan Laba Kotor
              </h5>
              <div class="period-toggle">
                <a href="?period=7days<?= !empty($kasir) ? '&kasir='.$kasir : '' ?>" class="btn btn-sm <?= $period == '7days' ? 'active' : 'btn-outline-primary' ?>">7 Hari</a>
                <a href="?period=1month<?= !empty($kasir) ? '&kasir='.$kasir : '' ?>" class="btn btn-sm <?= $period == '1month' ? 'active' : 'btn-outline-primary' ?>">30 Hari</a>
              </div>
            </div>
            <div style="height: 240px;">
              <?php if ((!empty($chart_dates) && count($chart_dates) > 0) && (!isset($_GET['bulan_transaksi']) || $ada_transaksi)) { ?>
                <canvas id="incomeChart"></canvas>
              <?php } else { ?>
                <div class="text-center py-4">
                  <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                  <p>Tidak ada data transaksi pada periode yang dipilih.</p>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
              
        <!-- Chart Kategori Produk -->
        <div class="col-lg-6 mb-4">
          <div class="chart-container">
            <h5 class="chart-title">
              <i class="fas fa-chart-pie me-2"></i>
              Kategori Produk Terjual
            </h5>
            <div style="height: 240px;">
              <?php if ((!empty($kategori_data) && count($kategori_data) > 0) && (!isset($_GET['bulan_transaksi']) || $ada_transaksi)) { ?>
                <canvas id="categoryChart"></canvas>
              <?php } else { ?>
                <div class="text-center py-4">
                  <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                  <p>Tidak ada data kategori produk pada periode yang dipilih.</p>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
              
        <!-- Chart Laba Bersih -->
        <div class="col-lg-8 mb-4">
          <div class="chart-container">
            <h5 class="chart-title">
              <i class="fas fa-chart-bar me-2"></i>
              Tren Laba Bersih per Bulan
            </h5>
            <div style="height: 240px;">
              <?php if ((!empty($laba_bersih_months) && count($laba_bersih_months) > 0) && (!isset($_GET['bulan_transaksi']) || $ada_transaksi)) { ?>
                <canvas id="netProfitChart"></canvas>
              <?php } else { ?>
                <div class="text-center py-4">
                  <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                  <p>Tidak ada data laba bersih pada periode yang dipilih.</p>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
                      
        <!-- Produk Terlaris -->
        <div class="col-lg-4 mb-4">
          <div class="chart-container">
            <h5 class="chart-title">
              <i class="fas fa-medal me-2"></i>
              5 Produk Terlaris
            </h5>
            <div id="topProducts">
              <?php
              $max_quantity = 0;
              foreach($produk_data as $product) {
                if($product['jumlah_terjual'] > $max_quantity) {
                  $max_quantity = $product['jumlah_terjual'];
                }
              }
              
              foreach($produk_data as $product) {
                $percentage = ($max_quantity > 0) ? ($product['jumlah_terjual'] / $max_quantity) * 100 : 0;
              ?>
              <div class="product-item">
                <div class="product-name"><?= htmlspecialchars($product['nama_produk']) ?></div>
                <div class="d-flex justify-content-between">
                  <small><?= number_format($product['jumlah_terjual']) ?> unit</small>
                  <small>Rp <?= number_format($product['total_penjualan'], 0, ',', '.') ?></small>
                </div>
                <div class="product-progress">
                  <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" 
                       aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
              <?php } ?>
              
              <?php if (empty($produk_data)) { ?>
                <div class="text-center py-4">
                  <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                  <p>Tidak ada data produk pada periode yang dipilih.</p>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Data Table -->
      <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="chart-title mb-0">
            <i class="fas fa-table me-2"></i>
            Daftar Transaksi
          </h5>
          <div class="d-flex align-items-center">
            <!-- Filter bulan untuk semua data -->
            <div class="me-2">
              <input type="month" class="form-control" id="bulan_transaksi" name="bulan_transaksi" value="<?= $bulan_transaksi ?>">
            </div>
            <button type="button" class="btn btn-primary me-2" onclick="filterTransaksi()">
              <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <a href="export_detail_transaksi.php?bulan=<?= $bulan_transaksi ?><?= !empty($kasir) ? '&kasir='.$kasir : '' ?>" class="btn btn-danger" target="_blank">
              <i class="fas fa-file-pdf me-2"></i> Export PDF
            </a>
          </div>
        </div>
              
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Total Pendapatan</th>
                <th>Total Harga Beli</th>
                <th>Laba Kotor</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['kasir']) ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['total_harga_beli'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['keuntungan'], 0, ',', '.') ?></td>
                    <td>
                      <a href="detail_transaksi.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Detail
                      </a>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3 d-block mt-3"></i>
                    <p class="mb-3">Tidak ada data transaksi pada periode yang dipilih.</p>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  
  <script>
    // Function to handle transaction month filter change
    function filterTransaksi() {
      const bulanTransaksi = document.getElementById('bulan_transaksi').value;
      const currentUrl = new URL(window.location.href);
      
      // Update or add the bulan_transaksi parameter
      if (bulanTransaksi) {
        currentUrl.searchParams.set('bulan_transaksi', bulanTransaksi);
        
        // Remove the other date filters when using month filter
        currentUrl.searchParams.delete('dari_tanggal');
        currentUrl.searchParams.delete('sampai_tanggal');
        currentUrl.searchParams.delete('period');
      } else {
        currentUrl.searchParams.delete('bulan_transaksi');
      }
      
      // Keep the kasir filter if it exists
      const kasir = currentUrl.searchParams.get('kasir');
      if (!kasir) {
        currentUrl.searchParams.delete('kasir');
      }
      
      // Navigate to the filtered URL
      window.location.href = currentUrl.toString();
    }

    // Chart Data
    const chartDates = <?= $chart_dates_json ?>;
    const chartIncome = <?= $chart_income_json ?>;
    const chartProfit = <?= $chart_profit_json ?>;
    
    // Laba Bersih Chart Data
    const labaBersihMonths = <?= $laba_bersih_months_json ?>;
    const labaKotorData = <?= $laba_kotor_data_json ?>;
    const pengeluaranData = <?= $pengeluaran_data_json ?>;
    const labaBersihData = <?= $laba_bersih_data_json ?>;
    const pendapatanData = <?= $pendapatan_data_json ?>;
    
    // Kategori Chart Data
    const kategoriData = <?= $kategori_data_json ?>;
    
    // Auto close alerts after 5 seconds
    setTimeout(function() {
      document.querySelectorAll(".alert").forEach(function(alert) {
        let closeButton = alert.querySelector(".btn-close");
        if (closeButton) {
          closeButton.click();
        }
      });
    }, 5000);
    
    // Initialize Income and Profit Chart
    const incomeChartCtx = document.getElementById('incomeChart');
    if (incomeChartCtx && chartDates && chartDates.length > 0) {  
      const incomeChart = new Chart(incomeChartCtx.getContext('2d'), {
        type: 'bar',
        data: {
          labels: chartDates,
          datasets: [
            {
              label: 'Pendapatan',
              data: chartIncome,
              backgroundColor: 'rgba(126, 87, 194, 0.7)', // Purple for pendapatan
              borderColor: '#7E57C2',
              borderWidth: 1
            },
            {
              label: 'Laba Kotor',
              data: chartProfit,
              backgroundColor: 'rgba(76, 175, 80, 0.7)', // Green for laba kotor
              borderColor: '#4CAF50',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  label += 'Rp ' + context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                  return label;
                }
              }
            }
          }
        }
      });
    }
      
    // Initialize Category Chart
    const categoryChartCtx = document.getElementById('categoryChart');
    if (categoryChartCtx && kategoriData && kategoriData.length > 0) {
      // Prepare data for category chart
      const categoryLabels = kategoriData.map(item => item.nama_kategori);
      const categoryValues = kategoriData.map(item => item.total_penjualan);
      const categoryColors = [
        '#7E57C2', // Primary purple
        '#4CAF50', // Green 
        '#FF7043', // Orange
        '#29B6F6', // Blue
        '#EC407A', // Pink
        '#26A69A', // Teal
        '#5C6BC0', // Indigo
        '#26C6DA', // Cyan
        '#D4E157', // Lime
        '#FFD54F'  // Amber
      ];
      
      const categoryChart = new Chart(categoryChartCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: categoryLabels,
          datasets: [{
            data: categoryValues,
            backgroundColor: categoryColors,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = 'Rp ' + context.parsed.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                  return `${label}: ${value}`;
                }
              }
            }
          }
        }
      });
    }
      
    // Initialize Net Profit Chart
    const netProfitChartCtx = document.getElementById('netProfitChart');
    if (netProfitChartCtx && labaBersihMonths && labaBersihMonths.length > 0) {
      const netProfitChart = new Chart(netProfitChartCtx.getContext('2d'), {
        type: 'bar',
        data: {
          labels: labaBersihMonths,
          datasets: [
            {
              label: 'Pendapatan',
              data: pendapatanData,
              backgroundColor: 'rgba(126, 87, 194, 0.7)', // Purple for pendapatan
              borderColor: '#7E57C2',
              borderWidth: 1
            },
            {
              label: 'Laba Kotor',
              data: labaKotorData,
              backgroundColor: 'rgba(76, 175, 80, 0.7)', // Green for laba kotor
              borderColor: '#4CAF50',
              borderWidth: 1
            },
            {
              label: 'Pengeluaran',
              data: pengeluaranData,
              backgroundColor: 'rgba(244, 67, 54, 0.7)', // Red for pengeluaran
              borderColor: '#F44336',
              borderWidth: 1
            },
            {
              label: 'Laba Bersih',
              data: labaBersihData,
              backgroundColor: 'rgba(41, 182, 246, 0.7)', // Blue for laba bersih
              borderColor: '#29B6F6',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  label += 'Rp ' + context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                  return label;
                }
              }
            }
          }
        }
      });
    }
    
    // Listen for changes on the period select to reload the page
    document.getElementById('period').addEventListener('change', function() {
      // Get current values from all form inputs
      const form = this.form;
      const filters = {
        period: this.value,
        kasir: form.kasir.value
      };
      
      // Only include dates if custom period is selected
      if(!this.value) {
        filters.dari_tanggal = form.dari_tanggal.value;
        filters.sampai_tanggal = form.sampai_tanggal.value;
      }
      
      // Build query string
      const query = Object.entries(filters)
        .filter(([_, v]) => v) // Remove empty values
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
        .join('&');
      
      // Reload page with new filters
      window.location.href = `?${query}`;
    });
  </script>
</body>
</html>