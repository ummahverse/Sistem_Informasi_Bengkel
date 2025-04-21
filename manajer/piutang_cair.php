<?php
session_start();

// Check if logged in and manajer
if (!isset($_SESSION['manajer']['logged_in']) || $_SESSION['manajer']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Database connection

// Get all payment records (both product debts and transaction debts)
$piutang_cair_query = "SELECT pc.*, 
                       CASE 
                         WHEN pc.transaksi_id = '-1' THEN 'Pembayaran Hutang Produk' 
                         ELSE CONCAT('Transaksi #', pc.transaksi_id) 
                       END as sumber,
                       CASE 
                         WHEN pc.transaksi_id = '-1' THEN 'Produk' 
                         ELSE 'Transaksi' 
                       END as tipe,
                       CASE
                         WHEN u.role = 'manajer' THEN m.nama
                         WHEN u.role = 'admin' THEN a.nama
                         WHEN u.role = 'karyawan' THEN k.nama
                         ELSE 'System'
                       END as nama_user
                       FROM piutang_cair pc
                       LEFT JOIN users u ON pc.created_by = u.id
                       LEFT JOIN manajer m ON pc.created_by = m.id_manajer AND u.role = 'manajer'
                       LEFT JOIN admin a ON pc.created_by = a.id_admin AND u.role = 'admin'
                       LEFT JOIN karyawan k ON pc.created_by = k.id_karyawan AND u.role = 'karyawan'
                       ORDER BY pc.tanggal_bayar DESC, pc.id DESC";
$piutang_cair_result = mysqli_query($conn, $piutang_cair_query);

// Check if query was successful
if (!$piutang_cair_result) {
  // Log the error for debugging
  error_log("MySQL Error: " . mysqli_error($conn));
  // Initialize as empty result
  $piutang_cair_result = [];
  $has_results = false;
} else {
  $has_results = mysqli_num_rows($piutang_cair_result) > 0;
}

// Get statistics
$stats_query = "SELECT 
               COUNT(id) as total_pembayaran,
               SUM(jumlah_bayar) as total_bayar,
               COUNT(CASE WHEN transaksi_id = '-1' THEN 1 END) as total_bayar_produk,
               COUNT(CASE WHEN transaksi_id != '-1' THEN 1 END) as total_bayar_transaksi,
               SUM(CASE WHEN transaksi_id = '-1' THEN jumlah_bayar ELSE 0 END) as total_nominal_produk,
               SUM(CASE WHEN transaksi_id != '-1' THEN jumlah_bayar ELSE 0 END) as total_nominal_transaksi
               FROM piutang_cair";
$stats_result = mysqli_query($conn, $stats_query);

// Check if stats query was successful
if (!$stats_result) {
  // Log the error for debugging
  error_log("MySQL Stats Error: " . mysqli_error($conn));
  // Initialize with default values
  $stats_data = [
    'total_pembayaran' => 0,
    'total_bayar' => 0,
    'total_bayar_produk' => 0,
    'total_bayar_transaksi' => 0,
    'total_nominal_produk' => 0,
    'total_nominal_transaksi' => 0
  ];
} else {
  $stats_data = mysqli_fetch_assoc($stats_result);
}

// Filter by date if requested
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

if (!empty($filter_date)) {
  $filter_date = mysqli_real_escape_string($conn, $filter_date);
  
  // Apply filter to query
  $piutang_cair_query = "SELECT pc.*, 
                         CASE 
                           WHEN pc.transaksi_id = '-1' THEN 'Pembayaran Hutang Produk' 
                           ELSE CONCAT('Transaksi #', pc.transaksi_id) 
                         END as sumber,
                         CASE 
                           WHEN pc.transaksi_id = '-1' THEN 'Produk' 
                           ELSE 'Transaksi' 
                         END as tipe,
                         CASE
                           WHEN u.role = 'manajer' THEN m.nama
                           WHEN u.role = 'admin' THEN a.nama
                           WHEN u.role = 'karyawan' THEN k.nama
                           ELSE 'System'
                         END as nama_user
                         FROM piutang_cair pc
                         LEFT JOIN users u ON pc.created_by = u.id
                         LEFT JOIN manajer m ON pc.created_by = m.id_manajer AND u.role = 'manajer'
                         LEFT JOIN admin a ON pc.created_by = a.id_admin AND u.role = 'admin'
                         LEFT JOIN karyawan k ON pc.created_by = k.id_karyawan AND u.role = 'karyawan'
                         WHERE DATE(pc.tanggal_bayar) = '$filter_date'
                         ORDER BY pc.tanggal_bayar DESC, pc.id DESC";
  $piutang_cair_result = mysqli_query($conn, $piutang_cair_query);
  
  // Check if query was successful
  if (!$piutang_cair_result) {
    // Log the error for debugging
    error_log("MySQL Filter Error: " . mysqli_error($conn));
    // Initialize as empty result
    $piutang_cair_result = [];
    $has_results = false;
  } else {
    $has_results = mysqli_num_rows($piutang_cair_result) > 0;
  }
  
  // Also update statistics with filter
  $stats_query = "SELECT 
                 COUNT(id) as total_pembayaran,
                 SUM(jumlah_bayar) as total_bayar,
                 COUNT(CASE WHEN transaksi_id = '-1' THEN 1 END) as total_bayar_produk,
                 COUNT(CASE WHEN transaksi_id != '-1' THEN 1 END) as total_bayar_transaksi,
                 SUM(CASE WHEN transaksi_id = '-1' THEN jumlah_bayar ELSE 0 END) as total_nominal_produk,
                 SUM(CASE WHEN transaksi_id != '-1' THEN jumlah_bayar ELSE 0 END) as total_nominal_transaksi
                 FROM piutang_cair
                 WHERE DATE(tanggal_bayar) = '$filter_date'";
  $stats_result = mysqli_query($conn, $stats_query);
  
  // Check if stats query was successful
  if (!$stats_result) {
    // Log the error for debugging
    error_log("MySQL Filtered Stats Error: " . mysqli_error($conn));
  } else {
    $stats_data = mysqli_fetch_assoc($stats_result);
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pembayaran Piutang - BMS Bengkel</title>
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
      margin-bottom: 1.5rem;
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
    
    /* Badge styling */
    .badge-produk {
      background-color: rgba(25, 135, 84, 0.15);
      color: #198754;
      font-weight: 500;
      padding: 0.5rem 0.75rem;
      border-radius: 6px;
    }
    
    .badge-transaksi {
      background-color: rgba(13, 110, 253, 0.15);
      color: #0d6efd;
      font-weight: 500;
      padding: 0.5rem 0.75rem;
      border-radius: 6px;
    }
    
    /* Filter form styling */
    .filter-form {
      background-color: var(--white);
      border-radius: 10px;
      padding: 1.25rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      margin-bottom: 1.5rem;
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
      
      .card-header-actions {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .card-header-actions .btn {
        margin-top: 1rem;
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
            <h1><i class="fas fa-history me-2"></i> Riwayat Pembayaran</h1>
            <p class="mb-0">Catatan pembayaran hutang produk dan piutang transaksi</p>
          </div>
          <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="piutang.php" class="btn btn-light">
              <i class="fas fa-hand-holding-usd me-1"></i> Kembali ke Piutang
            </a>
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
    
    <!-- Filter Form -->
    <div class="filter-form">
      <form action="" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label for="filter_date" class="form-label">Filter berdasarkan Tanggal</label>
          <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?= $filter_date ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter me-1"></i> Terapkan Filter
          </button>
          <?php if (!empty($filter_date)): ?>
            <a href="piutang_cair.php" class="btn btn-outline-secondary ms-2">
              <i class="fas fa-times me-1"></i> Hapus Filter
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon" style="background-color: rgba(239, 108, 0, 0.1);">
            <i class="fas fa-money-bill-wave" style="color: var(--primary-orange);"></i>
          </div>
          <h6 class="summary-title">TOTAL PEMBAYARAN</h6>
          <h3 class="summary-value"><?= number_format($stats_data['total_pembayaran'] ?? 0) ?></h3>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon" style="background-color: rgba(239, 108, 0, 0.1);">
            <i class="fas fa-hand-holding-usd" style="color: var(--primary-orange);"></i>
          </div>
          <h6 class="summary-title">TOTAL NOMINAL</h6>
          <h3 class="summary-value">Rp <?= number_format($stats_data['total_bayar'] ?? 0, 0, ',', '.') ?></h3>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon" style="background-color: rgba(25, 135, 84, 0.1);">
            <i class="fas fa-box" style="color: #198754;"></i>
          </div>
          <h6 class="summary-title">PEMBAYARAN HUTANG PRODUK</h6>
          <h3 class="summary-value">Rp <?= number_format($stats_data['total_nominal_produk'] ?? 0, 0, ',', '.') ?></h3>
          <small class="text-muted"><?= $stats_data['total_bayar_produk'] ?? 0 ?> transaksi</small>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon" style="background-color: rgba(13, 110, 253, 0.1);">
            <i class="fas fa-receipt" style="color: #0d6efd;"></i>
          </div>
          <h6 class="summary-title">PEMBAYARAN PIUTANG TRANSAKSI</h6>
          <h3 class="summary-value">Rp <?= number_format($stats_data['total_nominal_transaksi'] ?? 0, 0, ',', '.') ?></h3>
          <small class="text-muted"><?= $stats_data['total_bayar_transaksi'] ?? 0 ?> transaksi</small>
        </div>
      </div>
    </div>
    
    <!-- Payment History Table -->
    <div class="data-card">
      <div class="card-header-actions">
        <h5 class="card-title">
          <i class="fas fa-history me-2"></i> 
          Riwayat Pembayaran
          <?php if (!empty($filter_date)): ?>
            <span class="text-muted fs-6 ms-2">
              (Tanggal: <?= date('d/m/Y', strtotime($filter_date)) ?>)
            </span>
          <?php endif; ?>
        </h5>
        
        <?php if ($has_results): ?>
        <button class="btn btn-sm btn-outline-primary" id="exportBtn">
          <i class="fas fa-file-excel me-1"></i> Export Excel
        </button>
        <?php endif; ?>
      </div>
      
      <div class="table-responsive">
        <table class="table table-hover" id="piutangCairTable">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th width="15%">Tanggal Bayar</th>
              <th width="15%">Tipe</th>
              <th width="25%">Keterangan</th>
              <th width="15%">Jumlah Bayar</th>
              <th width="15%">Dibuat Oleh</th>
              <th width="10%">Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $no = 1;
            if ($has_results):
              while ($row = mysqli_fetch_assoc($piutang_cair_result)):
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= date('d/m/Y', strtotime($row['tanggal_bayar'])) ?></td>
              <td>
                <?php if ($row['tipe'] == 'Produk'): ?>
                  <span class="badge-produk">
                    <i class="fas fa-box me-1"></i> Hutang Produk
                  </span>
                <?php else: ?>
                  <span class="badge-transaksi">
                    <i class="fas fa-receipt me-1"></i> Piutang Transaksi
                  </span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['keterangan']) ?></td>
              <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
              <td><?= htmlspecialchars($row['nama_user'] ?? 'System') ?></td>
              <td>
                <?php if ($row['transaksi_id'] != '-1'): ?>
                  <a href="detail_transaksi.php?id=<?= $row['transaksi_id'] ?>" class="btn btn-sm btn-outline-primary btn-action">
                    <i class="fas fa-search me-1"></i> Detail
                  </a>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-secondary btn-action" disabled>
                    <i class="fas fa-box me-1"></i> Produk
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php 
              endwhile;
            else:
            ?>
            <tr>
              <td colspan="7" class="text-center py-4">
                <?php if (!empty($filter_date)): ?>
                  Tidak ada data pembayaran pada tanggal <?= date('d/m/Y', strtotime($filter_date)) ?>
                <?php else: ?>
                  Belum ada data pembayaran
                <?php endif; ?>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- JavaScript Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  
  <script>
    // Initialize DataTable
    $(document).ready(function() {
      <?php if ($has_results): ?>
      const table = $('#piutangCairTable').DataTable({
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
      
      // Export to Excel functionality
      $('#exportBtn').on('click', function() {
        // Create a new workbook
        const workbook = XLSX.utils.book_new();
        
        // Filter date information for the filename
        let filename = 'Riwayat_Pembayaran';
        let title = 'Riwayat Pembayaran Piutang & Hutang';
        
        <?php if (!empty($filter_date)): ?>
          filename += '_<?= date('d-m-Y', strtotime($filter_date)) ?>';
          title += ' - Tanggal <?= date('d/m/Y', strtotime($filter_date)) ?>';
        <?php endif; ?>
        
        // Get table data
        const tableData = [];
        
        // Add header row
        tableData.push([
          'No', 
          'Tanggal Bayar', 
          'Tipe', 
          'Keterangan', 
          'Jumlah Bayar', 
          'Dibuat Oleh'
        ]);
        
        // Get data from the table (excluding the action column)
        const rows = table.rows().data();
        for (let i = 0; i < rows.length; i++) {
          const rowData = rows[i];
          // Clean table data (remove HTML tags)
          const cleanedRow = [
            i + 1,
            rowData[1].replace(/<[^>]*>/g, '').trim(),
            rowData[2].includes('Hutang Produk') ? 'Hutang Produk' : 'Piutang Transaksi',
            rowData[3].replace(/<[^>]*>/g, '').trim(),
            rowData[4].replace(/<[^>]*>/g, '').trim(),
            rowData[5].replace(/<[^>]*>/g, '').trim()
          ];
          tableData.push(cleanedRow);
        }
        
        // Add summary row
        tableData.push(['', '', '', '', '', '']);
        tableData.push(['', '', 'RINGKASAN', '', '', '']);
        tableData.push(['', '', 'Total Pembayaran', '<?= $stats_data['total_pembayaran'] ?> transaksi', '', '']);
        tableData.push(['', '', 'Total Nominal', 'Rp <?= number_format($stats_data['total_bayar'], 0, ',', '.') ?>', '', '']);
        tableData.push(['', '', 'Pembayaran Hutang Produk', 'Rp <?= number_format($stats_data['total_nominal_produk'], 0, ',', '.') ?>', '<?= $stats_data['total_bayar_produk'] ?> transaksi', '']);
        tableData.push(['', '', 'Pembayaran Piutang Transaksi', 'Rp <?= number_format($stats_data['total_nominal_transaksi'], 0, ',', '.') ?>', '<?= $stats_data['total_bayar_transaksi'] ?> transaksi', '']);
        
        // Create worksheet and add to workbook
        const worksheet = XLSX.utils.aoa_to_sheet(tableData);
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Riwayat');
        
        // Generate Excel file and trigger download
        XLSX.writeFile(workbook, filename + '.xlsx');
      });
      <?php endif; ?>
      
      // Auto dismiss alerts
      setTimeout(function() {
        $('.alert').alert('close');
      }, 5000);
    });
  </script>
</body>
</html>