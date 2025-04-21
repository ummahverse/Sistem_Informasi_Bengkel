<?php
session_start();

// Check if logged in and admin
if (!isset($_SESSION['admin']['logged_in']) || $_SESSION['admin']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Database connection

// Process delete action if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $delete_query = "DELETE FROM laba_bersih WHERE id = '$id'";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['message'] = "Data laba bersih berhasil dihapus!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menghapus data laba bersih: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch net profit data
$query = "
    SELECT lb.id, lb.bulan, lb.laba_kotor, lb.pengeluaran, lb.laba_bersih
    FROM laba_bersih lb
    ORDER BY lb.bulan DESC
";
$result = mysqli_query($conn, $query);

// Calculate totals
$total_query = "
    SELECT 
        SUM(laba_kotor) as total_laba_kotor, 
        SUM(pengeluaran) as total_pengeluaran, 
        SUM(laba_bersih) as total_laba_bersih
    FROM laba_bersih
";
$total_result = mysqli_query($conn, $total_query);
$totals = mysqli_fetch_assoc($total_result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Laba Bersih - BMS Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    .data-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      background-color: var(--white);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
    }
    
    .data-card:hover {
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
    
    .card-title {
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
    
    .btn-warning {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(255, 152, 0, 0.2);
      color: white;
    }
    
    .btn-warning:hover {
      background: linear-gradient(135deg, #F57C00, #E65100);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(255, 152, 0, 0.3);
      color: white;
    }
    
    .btn-success {
      background: linear-gradient(135deg, #66BB6A, #43A047);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(102, 187, 106, 0.2);
      color: white;
    }
    
    .btn-success:hover {
      background: linear-gradient(135deg, #43A047, #2E7D32);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(102, 187, 106, 0.3);
      color: white;
    }
    
    .btn-action {
      padding: 0.4rem 0.7rem;
      border-radius: 6px;
      margin-right: 0.25rem;
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
    
    /* Badge styling */
    .badge {
      padding: 0.5rem 0.75rem;
      font-weight: 500;
      border-radius: 6px;
      font-size: 0.8rem;
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
    
    /* DataTables customization */
    div.dataTables_wrapper div.dataTables_filter input {
      border-radius: 8px;
      border: 1px solid #e0e6ed;
      padding: 0.5rem 1rem;
      margin-left: 0.5rem;
    }
    
    div.dataTables_wrapper div.dataTables_length select {
      border-radius: 8px;
      border: 1px solid #e0e6ed;
      padding: 0.5rem;
    }
    
    .page-item.active .page-link {
      background-color: var(--primary-purple);
      border-color: var(--primary-purple);
    }
    
    .page-link {
      color: var(--primary-purple);
    }
    
    /* Responsive media queries */
    @media (max-width: 992px) {
      .content {
        margin-left: 0;
      }
    }
    
    @media (max-width: 768px) {
      .data-card {
        padding: 1rem;
      }
      
      .page-header {
        padding: 1.5rem;
      }
      
      .table thead th, .table tbody td {
        padding: 0.75rem;
      }
    }
    
    /* Price column alignment */
    .text-end {
      text-align: right;
    }
    
    /* Profit status colors */
    .profit-positive {
      color: #4CAF50;
      font-weight: 600;
    }
    
    .profit-negative {
      color: #F44336;
      font-weight: 600;
    }
    
    .profit-neutral {
      color: #FF9800;
      font-weight: 600;
    }
    
    /* Delete confirmation modal */
    .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
      background-color: var(--light-purple);
      border-bottom: none;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 1.5rem;
    }
    
    .modal-title {
      color: var(--primary-purple);
      font-weight: 600;
    }
    
    .modal-body {
      padding: 1.5rem;
    }
    
    .modal-footer {
      border-top: none;
      padding: 1.5rem;
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
          Laporan Laba Bersih
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
            <h1><i class="fas fa-file-invoice-dollar me-2"></i>Data Laba Bersih</h1>
            <p class="lead mb-0">Kelola perhitungan laba bersih bulanan bengkel.</p>
          </div>
          <a href="tambah_laba_bersih.php" class="btn btn-light btn-lg">
            <i class="fas fa-plus me-2"></i>
            Tambah Data
          </a>
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

      <!-- Summary Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="data-card">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="fas fa-coins fa-3x text-success"></i>
              </div>
              <div>
                <h6 class="mb-1 text-muted">Total Laba Kotor</h6>
                <h3 class="mb-0 profit-positive">
                  Rp <?= number_format($totals['total_laba_kotor'] ?? 0, 0, ',', '.') ?>
                </h3>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="data-card">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="fas fa-money-bill-wave fa-3x text-warning"></i>
              </div>
              <div>
                <h6 class="mb-1 text-muted">Total Pengeluaran</h6>
                <h3 class="mb-0 text-warning">
                  Rp <?= number_format($totals['total_pengeluaran'] ?? 0, 0, ',', '.') ?>
                </h3>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="data-card">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="fas fa-chart-line fa-3x text-primary"></i>
              </div>
              <div>
                <h6 class="mb-1 text-muted">Total Laba Bersih</h6>
                <h3 class="mb-0 <?= ($totals['total_laba_bersih'] > 0) ? 'profit-positive' : (($totals['total_laba_bersih'] < 0) ? 'profit-negative' : 'profit-neutral') ?>">
                  Rp <?= number_format($totals['total_laba_bersih'] ?? 0, 0, ',', '.') ?>
                </h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Net Profit Table Card -->
      <div class="data-card">
        <div class="card-header-actions">
          <h5 class="card-title">
            <i class="fas fa-table me-2"></i>
            Daftar Perhitungan Laba Bersih Bulanan
          </h5>
          <div>
            <a href="export_laba_bersih.php" class="btn btn-outline-primary">
              <i class="fas fa-file-export me-1"></i>
              Export Semua Data
            </a>
          </div>
        </div>
        
        <div class="table-responsive">
          <table id="profitTable" class="table table-striped table-hover">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th width="20%">Bulan</th>
                <th width="20%" class="text-end">Laba Kotor</th>
                <th width="20%" class="text-end">Pengeluaran</th>
                <th width="20%" class="text-end">Laba Bersih</th>
                <th width="15%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                  // Determine profit status class
                  $profit_class = '';
                  if ($row['laba_bersih'] > 0) {
                    $profit_class = 'profit-positive';
                  } else if ($row['laba_bersih'] < 0) {
                    $profit_class = 'profit-negative';
                  } else {
                    $profit_class = 'profit-neutral';
                  }
                  
                  // Format bulan dalam format readable (Januari 2023)
                  $bulan_readable = date('F Y', strtotime($row['bulan'] . '-01'));
                  
                  // Store raw month value for export
                  $bulan_raw = $row['bulan'];
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td>
                  <strong><?= $bulan_readable ?></strong>
                </td>
                <td class="text-end">Rp <?= number_format($row['laba_kotor'], 0, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($row['pengeluaran'], 0, ',', '.') ?></td>
                <td class="text-end">
                  <span class="<?= $profit_class ?>">
                    Rp <?= number_format($row['laba_bersih'], 0, ',', '.') ?>
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group" role="group">
                    <a href="edit_laba_bersih.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-action" data-bs-toggle="tooltip" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="export_laba_bersih.php?bulan=<?= $bulan_raw ?>" class="btn btn-sm btn-success btn-action" data-bs-toggle="tooltip" title="Export Bulan Ini">
                      <i class="fas fa-file-export"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                  
                  <!-- Delete Confirmation Modal -->
                  <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="deleteModalLabel<?= $row['id'] ?>">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                            Konfirmasi Hapus
                          </h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <p>Anda yakin ingin menghapus data laba bersih bulan <strong>"<?= $bulan_readable ?>"</strong>?</p>
                          <p class="text-danger mb-0"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Batal
                          </button>
                          <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>
                            Ya, Hapus
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <?php
                endwhile;
              else:
              ?>
              <tr>
                <td colspan="6" class="text-center py-4">
                  <div class="d-flex flex-column align-items-center">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold">Belum Ada Data Laba Bersih</h5>
                    <p class="text-muted">Silahkan tambahkan data laba bersih baru untuk ditampilkan di sini.</p>
                    <a href="tambah_laba_bersih.php" class="btn btn-primary mt-2">
                      <i class="fas fa-plus me-1"></i>
                      Tambah Data Sekarang
                    </a>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
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
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Initialize DataTables
      $('#profitTable').DataTable({
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        responsive: true,
        "order": [[1, "desc"]], // Sort by month descending
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
      });
      
      // Initialize tooltips
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
      });
      
      // Auto close alerts after 5 seconds
      setTimeout(function() {
        $(".alert").alert('close');
      }, 5000);
    });
  </script>
</body>
</html>