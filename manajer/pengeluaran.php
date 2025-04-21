<?php
session_start();

// Check if logged in and manajer
if (!isset($_SESSION['manajer']['logged_in']) || $_SESSION['manajer']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Database connection

// Konfigurasi pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Default bulan adalah bulan ini
$bulan_ini = date('Y-m');
$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : $bulan_ini;

// Process add pengeluaran form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $bulan = date('Y-m', strtotime($tanggal));
    $created_by = $_SESSION['manajer']['id_manajer'];

    // Insert pengeluaran
    $query = "INSERT INTO pengeluaran (kategori, jumlah, keterangan, tanggal, bulan, created_by) 
              VALUES ('$kategori', '$jumlah', '$keterangan', '$tanggal', '$bulan', '$created_by')";
    
    if (mysqli_query($conn, $query)) {
        $pengeluaran_id = mysqli_insert_id($conn);
        
        // Check if laba_bersih entry exists for this month
        $check_query = "SELECT id FROM laba_bersih WHERE bulan = '$bulan'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing entry
            $laba_row = mysqli_fetch_assoc($check_result);
            $laba_id = $laba_row['id'];
            
            // Add to pengeluaran_detail
            $detail_query = "INSERT INTO pengeluaran_detail (laba_bersih_id, pengeluaran_id, nama_pengeluaran, jumlah) 
                            VALUES ('$laba_id', '$pengeluaran_id', '$kategori - $keterangan', '$jumlah')";
            mysqli_query($conn, $detail_query);
            
            // Update laba_bersih total
            $update_query = "UPDATE laba_bersih SET 
                            pengeluaran = pengeluaran + $jumlah,
                            laba_bersih = laba_kotor - (pengeluaran + $jumlah),
                            updated_at = NOW()
                            WHERE id = '$laba_id'";
            mysqli_query($conn, $update_query);
        } else {
            // Create new laba_bersih entry for this month
            // First, get total pendapatan for this month
            $pendapatan_query = "SELECT SUM(pendapatan) as total_pendapatan 
                                FROM transaksi 
                                WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
            $pendapatan_result = mysqli_query($conn, $pendapatan_query);
            $pendapatan_data = mysqli_fetch_assoc($pendapatan_result);
            $laba_kotor = $pendapatan_data['total_pendapatan'] ?: 0;
            
            // Insert new laba_bersih
            $new_laba_query = "INSERT INTO laba_bersih (bulan, laba_kotor, pengeluaran, laba_bersih) 
                              VALUES ('$bulan', '$laba_kotor', '$jumlah', '$laba_kotor' - '$jumlah')";
            if (mysqli_query($conn, $new_laba_query)) {
                $new_laba_id = mysqli_insert_id($conn);
                
                // Add to pengeluaran_detail
                $detail_query = "INSERT INTO pengeluaran_detail (laba_bersih_id, pengeluaran_id, nama_pengeluaran, jumlah) 
                                VALUES ('$new_laba_id', '$pengeluaran_id', '$kategori - $keterangan', '$jumlah')";
                mysqli_query($conn, $detail_query);
            }
        }
        
        $_SESSION['message'] = "Pengeluaran berhasil ditambahkan!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menambahkan pengeluaran: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?bulan=" . $bulan_filter);
    exit();
}

// Process delete pengeluaran
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Get pengeluaran data before deleting
    $get_query = "SELECT jumlah, bulan FROM pengeluaran WHERE id = '$id'";
    $get_result = mysqli_query($conn, $get_query);
    
    if ($row = mysqli_fetch_assoc($get_result)) {
        $jumlah = $row['jumlah'];
        $bulan = $row['bulan'];
        
        // Update laba_bersih
        $update_laba_query = "UPDATE laba_bersih SET 
                             pengeluaran = pengeluaran - $jumlah, 
                             laba_bersih = laba_bersih + $jumlah 
                             WHERE bulan = '$bulan'";
        mysqli_query($conn, $update_laba_query);
        
        // Delete pengeluaran_detail entries
        $delete_detail_query = "DELETE FROM pengeluaran_detail WHERE pengeluaran_id = '$id'";
        mysqli_query($conn, $delete_detail_query);
        
        // Delete pengeluaran
        $delete_query = "DELETE FROM pengeluaran WHERE id = '$id'";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['message'] = "Pengeluaran berhasil dihapus!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menghapus pengeluaran: " . mysqli_error($conn);
            $_SESSION['alert_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Data pengeluaran tidak ditemukan!";
        $_SESSION['alert_type'] = "danger";
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?bulan=" . $bulan_filter);
    exit();
}

// Get pengeluaran data with filter
$query = "SELECT p.*, 
          CASE 
            WHEN p.created_by IS NOT NULL THEN m.nama 
            ELSE 'Unknown' 
          END as created_by_name
          FROM pengeluaran p
          LEFT JOIN manajer m ON p.created_by = m.id_manajer
          WHERE p.bulan = '$bulan_filter'
          ORDER BY p.tanggal DESC, p.created_at DESC
          LIMIT $start, $per_page";
$result = mysqli_query($conn, $query);

// Count total rows for pagination
$count_query = "SELECT COUNT(*) as total FROM pengeluaran WHERE bulan = '$bulan_filter'";
$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_pages = ceil($count_data['total'] / $per_page);

// Get summary data
$summary_query = "SELECT 
                  SUM(jumlah) as total_pengeluaran,
                  SUM(CASE WHEN kategori = 'Sewa Lahan' THEN jumlah ELSE 0 END) as sewa_lahan,
                  SUM(CASE WHEN kategori = 'Token Listrik' THEN jumlah ELSE 0 END) as token_listrik,
                  SUM(CASE WHEN kategori = 'Kasbon Karyawan' THEN jumlah ELSE 0 END) as kasbon_karyawan,
                  SUM(CASE WHEN kategori = 'Uang Makan' THEN jumlah ELSE 0 END) as uang_makan,
                  SUM(CASE WHEN kategori = 'Gaji Karyawan' THEN jumlah ELSE 0 END) as gaji_karyawan,
                  SUM(CASE WHEN kategori = 'Lainnya' THEN jumlah ELSE 0 END) as lainnya
                  FROM pengeluaran
                  WHERE bulan = '$bulan_filter'";
$summary_result = mysqli_query($conn, $summary_query);
$summary_data = mysqli_fetch_assoc($summary_result);

// Get laba data
$laba_query = "SELECT * FROM laba_bersih WHERE bulan = '$bulan_filter'";
$laba_result = mysqli_query($conn, $laba_query);
$laba_data = mysqli_fetch_assoc($laba_result);

// Get available months for filter
$months_query = "SELECT DISTINCT bulan FROM pengeluaran ORDER BY bulan DESC";
$months_result = mysqli_query($conn, $months_query);
$months = [];
while ($row = mysqli_fetch_assoc($months_result)) {
    $months[] = $row['bulan'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengeluaran - BMS Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-orange: #EF6C00;
      --secondary-orange: #F59E0B;
      --light-orange: #FFF3E0;
      --accent-orange: #D84315;
      --white: #ffffff;
      --light-gray: #f8f9fa;
      --text-dark: #2C3E50;
    }
    
    body {
      background-color: var(--light-gray);
      font-family: 'Poppins', 'Arial', sans-serif;
      color: var(--text-dark);
      padding-left: 280px; /* Add this to account for the sidebar */
    }
    
    /* Navbar Styling */
    .navbar {
      background: linear-gradient(135deg, var(--primary-orange), var(--accent-orange));
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border: none;
      padding: 0.8rem 1.5rem;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--white);
      letter-spacing: 0.5px;
    }
    
    /* Content area */
    .content {
      padding: 20px;
      background-color: var(--light-gray);
      min-height: 100vh;
    }
    
    /* Page header section */
    .page-header {
      background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
      border-radius: 15px;
      padding: 2rem;
      color: var(--white);
      margin-bottom: 2rem;
      box-shadow: 0 6px 18px rgba(0, 123, 255, 0.15);
    }
    
    .page-header h1 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    /* Data card */
    .data-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      background-color: var(--white);
      padding: 1.5rem;
      height: 100%;
    }
    
    /* Table styling */
    .table {
      margin-bottom: 0;
    }

    .table th {
      background-color: var(--light-orange);
      color: var(--primary-orange);
      font-weight: 600;
      border: none;
      vertical-align: middle;
    }
    
    .table td {
      vertical-align: middle;
      border-color: #e9ecef;
    }
    
    /* Card header with actions */
    .card-header-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--light-orange);
    }
    
    .card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary-orange);
      margin-bottom: 0;
    }
    
    /* Button styling */
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(239, 108, 0, 0.2);
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, var(--accent-orange), var(--primary-orange));
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(239, 108, 0, 0.3);
    }
    
    .btn-outline-primary {
      color: var(--primary-orange);
      border-color: var(--primary-orange);
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background-color: var(--primary-orange);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(239, 108, 0, 0.2);
    }
    
    .btn-outline-light {
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-danger {
      border-radius: 8px;
      padding: 0.375rem 0.75rem;
      transition: all 0.3s ease;
    }
    
    .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
    }
    
    .btn-action {
      padding: 0.4rem 0.7rem;
      border-radius: 6px;
      margin-right: 0.25rem;
    }

    .btn-group {
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      border-radius: 8px;
    }
    
    /* Summary cards */
    .summary-card {
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .summary-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    
    .summary-icon {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      margin-bottom: 15px;
    }
    
    .summary-title {
      font-size: 0.9rem;
      font-weight: 600;
      color: #6c757d;
      margin-bottom: 5px;
    }
    
    .summary-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
    }
    
    /* Pagination styling */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 2rem;
    }
    
    .pagination .page-item .page-link {
      color: var(--primary-orange);
      border-radius: 5px;
      margin: 0 3px;
      transition: all 0.3s ease;
    }
    
    .pagination .page-item.active .page-link {
      background-color: var(--primary-orange);
      border-color: var(--primary-orange);
      color: white;
    }
    
    .pagination .page-item .page-link:hover {
      background-color: var(--light-orange);
      color: var(--primary-orange);
    }
    
    /* Filter dropdown */
    .filter-dropdown {
      border-radius: 8px;
      border: 1px solid #e9ecef;
      padding: 0.5rem 1rem;
      width: 200px;
    }
    
    /* Alert styling */
    .alert-dismissible {
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    .alert-success {
      background-color: #d1e7dd;
      color: #0f5132;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      color: #842029;
    }
    
    /* Responsive adjustments for mobile */
    @media (max-width: 992px) {
      body {
        padding-left: 0;
      }
      
      .content {
        padding: 70px 15px 20px;
      }
      
      .page-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
      }
      
      .data-card {
        padding: 1rem;
      }
      
      .summary-card {
        margin-bottom: 1rem;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>
  
  <div class="content">
    <!-- Page Header -->
    <div class="page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1><i class="fas fa-money-bill-wave me-2"></i> Pengeluaran</h1>
            <p class="mb-0">Manajemen data pengeluaran bengkel</p>
          </div>
          <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addPengeluaranModal">
              <i class="fas fa-plus-circle me-1"></i> Tambah Pengeluaran
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php 
        unset($_SESSION['message']);
        unset($_SESSION['alert_type']);
      ?>
    <?php endif; ?>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-primary-light">
            <i class="fas fa-chart-pie text-primary"></i>
          </div>
          <h6 class="summary-title">TOTAL PENGELUARAN</h6>
          <h3 class="summary-value">Rp <?= number_format($summary_data['total_pengeluaran'] ?? 0, 0, ',', '.') ?></h3>
        </div>
      </div>
  
      
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-warning-light">
            <i class="fas fa-file-invoice-dollar text-warning"></i>
          </div>
          <h6 class="summary-title">PENGELUARAN BULAN INI</h6>
          <h3 class="summary-value">Rp <?= number_format($laba_data['pengeluaran'] ?? 0, 0, ',', '.') ?></h3>
        </div>
      </div>
      
    
    
    <!-- Main Content -->
    <div class="row">
      <div class="col-lg-12">
        <div class="data-card">
          <div class="card-header-actions">
            <h5 class="card-title">Data Pengeluaran</h5>
            <div class="d-flex gap-2">
              <select id="bulanFilter" class="form-select filter-dropdown" onchange="window.location.href='?bulan='+this.value">
                <option value="<?= $bulan_ini ?>" <?= ($bulan_filter == $bulan_ini) ? 'selected' : '' ?>>Bulan Ini (<?= date('F Y', strtotime($bulan_ini)) ?>)</option>
                <?php foreach ($months as $month): ?>
                  <?php if ($month != $bulan_ini): ?>
                    <option value="<?= $month ?>" <?= ($bulan_filter == $month) ? 'selected' : '' ?>>
                      <?= date('F Y', strtotime($month)) ?>
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover" id="pengeluaranTable">
              <thead>
                <tr>
                  <th width="5%">#</th>
                  <th width="15%">Tanggal</th>
                  <th width="15%">Kategori</th>
                  <th width="15%">Jumlah</th>
                  <th width="30%">Keterangan</th>
                  <th width="10%">Dibuat Oleh</th>
                  <th width="10%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $no = $start + 1;
                if (mysqli_num_rows($result) > 0):
                  while ($row = mysqli_fetch_assoc($result)):
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                  <td><span class="badge bg-primary"><?= $row['kategori'] ?></span></td>
                  <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                  <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
                  <td><?= $row['created_by_name'] ?></td>
                  <td>
                    <a href="?delete=<?= $row['id'] ?>&bulan=<?= $bulan_filter ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
                <?php 
                  endwhile;
                else:
                ?>
                <tr>
                  <td colspan="7" class="text-center py-4">Tidak ada data pengeluaran pada bulan ini</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination">
              <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?= $page-1 ?>&bulan=<?= $bulan_filter ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&bulan=<?= $bulan_filter ?>"><?= $i ?></a>
              </li>
              <?php endfor; ?>
              
              <?php if ($page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?= $page+1 ?>&bulan=<?= $bulan_filter ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Category Breakdown -->
    <div class="row mt-4">
      <div class="col-lg-12">
        <div class="data-card">
          <div class="card-header-actions">
            <h5 class="card-title">Rincian Pengeluaran Per Kategori</h5>
          </div>
          
          <div class="row g-4">
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-building me-2"></i> Sewa Lahan</h6>
                <h4>Rp <?= number_format($summary_data['sewa_lahan'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-bolt me-2"></i> Token Listrik</h6>
                <h4>Rp <?= number_format($summary_data['token_listrik'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-money-bill-wave me-2"></i> Kasbon Karyawan</h6>
                <h4>Rp <?= number_format($summary_data['kasbon_karyawan'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-utensils me-2"></i> Uang Makan</h6>
                <h4>Rp <?= number_format($summary_data['uang_makan'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-users me-2"></i> Gaji Karyawan</h6>
                <h4>Rp <?= number_format($summary_data['gaji_karyawan'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
              <div class="border rounded p-3">
                <h6 class="text-primary mb-2"><i class="fas fa-ellipsis-h me-2"></i> Lainnya</h6>
                <h4>Rp <?= number_format($summary_data['lainnya'] ?? 0, 0, ',', '.') ?></h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Add Pengeluaran Modal -->
  <div class="modal fade" id="addPengeluaranModal" tabindex="-1" aria-labelledby="addPengeluaranModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addPengeluaranModalLabel">Tambah Pengeluaran Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            
            <div class="mb-3">
              <label for="kategori" class="form-label">Kategori Pengeluaran</label>
              <select class="form-select" id="kategori" name="kategori" required>
                <option value="" disabled selected>Pilih Kategori</option>
                <option value="Sewa Lahan">Sewa Lahan</option>
                <option value="Token Listrik">Token Listrik</option>
                <option value="Kasbon Karyawan">Kasbon Karyawan</option>
                <option value="Uang Makan">Uang Makan</option>
                <option value="Gaji Karyawan">Gaji Karyawan</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label for="jumlah" class="form-label">Jumlah (Rp)</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" id="jumlah" name="jumlah" required min="1" placeholder="Masukkan jumlah pengeluaran">
              </div>
            </div>
            
            <div class="mb-3">
              <label for="tanggal" class="form-label">Tanggal</label>
              <input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="mb-3">
              <label for="keterangan" class="form-label">Keterangan</label>
              <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Masukkan keterangan pengeluaran (opsional)"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- JavaScript Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  
  <script>
    // Initialize DataTable
    $(document).ready(function() {
      $('#pengeluaranTable').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "info": false,
        "autoWidth": false,
        "responsive": true,
        "language": {
          "search": "Cari:",
          "zeroRecords": "Tidak ada data yang cocok",
          "emptyTable": "Tidak ada data yang tersedia",
        }
      });
      
      // Format number input
      $('#jumlah').on('input', function() {
        // Remove non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
      });
      
      // Auto dismiss alerts
      setTimeout(function() {
        $('.alert').alert('close');
      }, 5000);
    });
  </script>
</body>
</html>