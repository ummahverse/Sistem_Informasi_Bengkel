<?php
session_start();

// Check if logged in and manajer
if (!isset($_SESSION['manajer']['logged_in']) || $_SESSION['manajer']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Database connection

// Process product debt payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_hutang_produk') {
    $produk_id = mysqli_real_escape_string($conn, $_POST['produk_id']);
    $jumlah_bayar = mysqli_real_escape_string($conn, $_POST['jumlah_bayar']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $tanggal_bayar = mysqli_real_escape_string($conn, $_POST['tanggal_bayar']);
    $created_by = $_SESSION['manajer']['id_manajer'];
    
    // Get product data
    $produk_query = "SELECT * FROM produk WHERE id = '$produk_id'";
    $produk_result = mysqli_query($conn, $produk_query);
    $produk = mysqli_fetch_assoc($produk_result);
    
    if ($produk) {
        // Calculate remaining debt
        $sisa_hutang = $produk['nominal_hutang'] - $jumlah_bayar;
        
        // Start transaction
        mysqli_autocommit($conn, false);
        $error = false;
        
        // Update product status
        $hutang_status = ($sisa_hutang <= 0) ? 'Cash' : 'Hutang';
        $update_query = "UPDATE produk SET 
                        nominal_hutang = '$sisa_hutang', 
                        hutang_sparepart = '$hutang_status' 
                        WHERE id = '$produk_id'";
                        
        if (!mysqli_query($conn, $update_query)) {
            $error = true;
        }
        
        // Insert payment record to piutang_cair with special note
        $nama_produk = $produk['nama'];
        $keterangan_lengkap = "Pembayaran hutang produk: $nama_produk - $keterangan";
        
        // Use -1 as a marker for product debt payments in transaksi_id field
        $insert_query = "INSERT INTO piutang_cair (
                        transaksi_id, 
                        jumlah_bayar, 
                        tanggal_bayar, 
                        keterangan, 
                        created_by) 
                        VALUES 
                        ('-1', '$jumlah_bayar', '$tanggal_bayar', '$keterangan_lengkap', '$created_by')";
        
        if (!mysqli_query($conn, $insert_query)) {
            $error = true;
        }
        
        // Commit or rollback transaction
        if ($error) {
            mysqli_rollback($conn);
            $_SESSION['message'] = "Gagal memproses pembayaran: " . mysqli_error($conn);
            $_SESSION['alert_type'] = "danger";
        } else {
            mysqli_commit($conn);
            $_SESSION['message'] = "Pembayaran hutang produk berhasil diproses!";
            $_SESSION['alert_type'] = "success";
        }
    } else {
        $_SESSION['message'] = "Produk tidak ditemukan!";
        $_SESSION['alert_type'] = "danger";
    }
    
    header("Location: piutang.php");
    exit();
}

// Process update transaction debt status (set to lunas)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_hutang_transaksi') {
    $transaksi_id = mysqli_real_escape_string($conn, $_POST['transaksi_id']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $tanggal_bayar = mysqli_real_escape_string($conn, $_POST['tanggal_bayar']);
    $created_by = $_SESSION['manajer']['id_manajer'];
    
    // Get transaction data
    $transaksi_query = "SELECT * FROM transaksi WHERE id = '$transaksi_id'";
    $transaksi_result = mysqli_query($conn, $transaksi_query);
    $transaksi = mysqli_fetch_assoc($transaksi_result);
    
    if ($transaksi && $transaksi['status_hutang'] == 1 && $transaksi['hutang'] > 0) {
        // Start transaction
        mysqli_autocommit($conn, false);
        $error = false;
        
        // Insert payment record
        $jumlah_bayar = $transaksi['hutang'];
        $insert_query = "INSERT INTO piutang_cair (
                        transaksi_id, 
                        jumlah_bayar, 
                        tanggal_bayar, 
                        keterangan, 
                        created_by) 
                        VALUES 
                        ('$transaksi_id', '$jumlah_bayar', '$tanggal_bayar', '$keterangan', '$created_by')";
        
        if (!mysqli_query($conn, $insert_query)) {
            $error = true;
        }
        
        // Update transaction status
        $update_query = "UPDATE transaksi SET 
                        hutang = 0, 
                        status_hutang = 0
                        WHERE id = '$transaksi_id'";
        
        if (!mysqli_query($conn, $update_query)) {
            $error = true;
        }
        
        // Commit or rollback transaction
        if ($error) {
            mysqli_rollback($conn);
            $_SESSION['message'] = "Gagal memproses pembayaran: " . mysqli_error($conn);
            $_SESSION['alert_type'] = "danger";
        } else {
            mysqli_commit($conn);
            $_SESSION['message'] = "Status hutang transaksi berhasil diupdate menjadi LUNAS!";
            $_SESSION['alert_type'] = "success";
        }
    } else {
        $_SESSION['message'] = "Transaksi tidak ditemukan atau tidak memiliki hutang!";
        $_SESSION['alert_type'] = "danger";
    }
    
    header("Location: piutang.php");
    exit();
}

// Get all products with debt
$hutang_produk_query = "SELECT * FROM produk WHERE hutang_sparepart = 'Hutang' AND nominal_hutang > 0 ORDER BY nominal_hutang DESC";
$hutang_produk_result = mysqli_query($conn, $hutang_produk_query);

// Get all transactions with debt
$hutang_transaksi_query = "SELECT t.*, 
                          COUNT(td.id) as jumlah_item
                          FROM transaksi t
                          LEFT JOIN transaksi_detail td ON t.id = td.transaksi_id
                          WHERE t.status_hutang = 1 AND t.hutang > 0
                          GROUP BY t.id
                          ORDER BY t.tanggal DESC, t.id DESC";
$hutang_transaksi_result = mysqli_query($conn, $hutang_transaksi_query);

// Get total debt summary
$summary_query = "SELECT 
                 (SELECT COUNT(*) FROM produk WHERE hutang_sparepart = 'Hutang' AND nominal_hutang > 0) as total_produk_hutang,
                 (SELECT SUM(nominal_hutang) FROM produk WHERE hutang_sparepart = 'Hutang' AND nominal_hutang > 0) as total_hutang_produk,
                 (SELECT COUNT(*) FROM transaksi WHERE status_hutang = 1 AND hutang > 0) as total_transaksi_hutang,
                 (SELECT SUM(hutang) FROM transaksi WHERE status_hutang = 1 AND hutang > 0) as total_hutang_transaksi";
$summary_result = mysqli_query($conn, $summary_query);
$summary_data = mysqli_fetch_assoc($summary_result);

// Total all debts
$total_semua_hutang = 
    ($summary_data['total_hutang_produk'] ?? 0) + 
    ($summary_data['total_hutang_transaksi'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Piutang & Hutang - BMS Bengkel</title>
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
    
    .btn-action {
      padding: 0.4rem 0.7rem;
      border-radius: 6px;
      margin-right: 0.25rem;
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
    
    /* Tab styling */
    .nav-tabs {
      border-bottom: 2px solid var(--light-orange);
      margin-bottom: 1.5rem;
    }
    
    .nav-tabs .nav-link {
      border: none;
      color: #6c757d;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      border-radius: 8px 8px 0 0;
      transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link.active {
      color: var(--primary-orange);
      background-color: var(--light-orange);
      font-weight: 600;
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
      background-color: #f8f9fa;
      color: var(--primary-orange);
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
            <h1><i class="fas fa-hand-holding-usd me-2"></i> Piutang & Hutang</h1>
            <p class="mb-0">Manajemen hutang produk dan piutang transaksi</p>
          </div>
          <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="piutang_cair.php" class="btn btn-light">
              <i class="fas fa-history me-1"></i> Riwayat Pembayaran
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
    
    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-primary-light">
            <i class="fas fa-box text-primary"></i>
          </div>
          <h6 class="summary-title">TOTAL PRODUK HUTANG</h6>
          <h3 class="summary-value"><?= number_format($summary_data['total_produk_hutang'] ?? 0) ?></h3>
        </div>
      </div>
      
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-success-light">
            <i class="fas fa-money-bill-wave text-success"></i>
          </div>
          <h6 class="summary-title">TOTAL HUTANG PRODUK</h6>
          <h3 class="summary-value">Rp <?= number_format($summary_data['total_hutang_produk'] ?? 0, 0, ',', '.') ?></h3>
        </div>
      </div>
      
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-warning-light">
            <i class="fas fa-receipt text-warning"></i>
          </div>
          <h6 class="summary-title">TOTAL TRANSAKSI HUTANG</h6>
          <h3 class="summary-value"><?= number_format($summary_data['total_transaksi_hutang'] ?? 0) ?></h3>
        </div>
      </div>
      
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 summary-card bg-white p-4">
          <div class="summary-icon bg-danger-light">
            <i class="fas fa-hand-holding-usd text-danger"></i>
          </div>
          <h6 class="summary-title">TOTAL SEMUA HUTANG</h6>
          <h3 class="summary-value">Rp <?= number_format($total_semua_hutang, 0, ',', '.') ?></h3>
        </div>
      </div>
    </div>
    
    <!-- Tabs for Hutang Produk and Piutang Transaksi -->
    <ul class="nav nav-tabs" id="piutangTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="hutang-produk-tab" data-bs-toggle="tab" data-bs-target="#hutang-produk" type="button" role="tab">
          <i class="fas fa-box me-2"></i> Hutang Produk
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="piutang-transaksi-tab" data-bs-toggle="tab" data-bs-target="#piutang-transaksi" type="button" role="tab">
          <i class="fas fa-receipt me-2"></i> Piutang Transaksi
        </button>
      </li>
    </ul>
    
    <div class="tab-content" id="piutangTabContent">
      <!-- Tab Hutang Produk -->
      <div class="tab-pane fade show active" id="hutang-produk" role="tabpanel" tabindex="0">
        <div class="data-card">
          <div class="card-header-actions">
            <h5 class="card-title">Daftar Produk dengan Hutang</h5>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover" id="hutangProdukTable">
              <thead>
                <tr>
                  <th width="5%">No</th>
                  <th width="25%">Nama Produk</th>
                  <th width="15%">Kategori</th>
                  <th width="15%">Harga Beli</th>
                  <th width="15%">Nominal Hutang</th>
                  <th width="25%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $no = 1;
                if (mysqli_num_rows($hutang_produk_result) > 0):
                  while ($row = mysqli_fetch_assoc($hutang_produk_result)):
                    // Get category name
                    $kategori_id = $row['kategori_id'];
                    $kategori_query = "SELECT nama_kategori FROM kategori WHERE id = '$kategori_id'";
                    $kategori_result = mysqli_query($conn, $kategori_query);
                    $kategori_name = mysqli_fetch_assoc($kategori_result)['nama_kategori'] ?? 'Tidak Ada Kategori';
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($row['nama']) ?></td>
                  <td><?= htmlspecialchars($kategori_name) ?></td>
                  <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($row['nominal_hutang'], 0, ',', '.') ?></td>
                  <td>
                    <a href="bayar_hutang.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-action">
                      <i class="fas fa-money-bill-wave me-1"></i> Bayar Hutang
                    </a>
                  </td>
                </tr>
                <?php 
                  endwhile;
                else:
                ?>
                <tr>
                  <td colspan="6" class="text-center py-4">Tidak ada produk dengan hutang</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Tab Piutang Transaksi -->
      <div class="tab-pane fade" id="piutang-transaksi" role="tabpanel" tabindex="0">
        <div class="data-card">
          <div class="card-header-actions">
            <h5 class="card-title">Daftar Transaksi dengan Piutang</h5>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover" id="piutangTransaksiTable">
              <thead>
                <tr>
                  <th width="5%">No</th>
                  <th width="10%">Tanggal</th>
                  <th width="20%">Customer</th>
                  <th width="15%">Total Transaksi</th>
                  <th width="15%">Jumlah Hutang</th>
                  <th width="10%">Metode</th>
                  <th width="25%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $no = 1;
                if (mysqli_num_rows($hutang_transaksi_result) > 0):
                  while ($row = mysqli_fetch_assoc($hutang_transaksi_result)):
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold"><?= htmlspecialchars($row['nama_customer']) ?></span>
                      <small class="text-muted"><?= $row['plat_nomor_motor'] ?></small>
                    </div>
                  </td>
                  <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                  <td class="text-danger fw-bold">Rp <?= number_format($row['hutang'], 0, ',', '.') ?></td>
                  <td><?= $row['metode_pembayaran'] ?></td>
                  <td>
                    <div class="btn-group">
                    
                      <button type="button" class="btn btn-sm btn-success btn-action update-hutang-transaksi" 
                              data-id="<?= $row['id'] ?>"
                              data-nama="<?= htmlspecialchars($row['nama_customer']) ?>"
                              data-hutang="<?= $row['hutang'] ?>">
                        <i class="fas fa-check-circle me-1"></i> Seting  Menjadi Lunas
                      </button>
                    </div>
                  </td>
                </tr>
                <?php 
                  endwhile;
                else:
                ?>
                <tr>
                  <td colspan="7" class="text-center py-4">Tidak ada transaksi dengan piutang</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal Update Hutang Transaksi -->
  <div class="modal fade" id="updateHutangTransaksiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Update Status Hutang: <span id="transaksiNama"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="update_hutang_transaksi">
            <input type="hidden" name="transaksi_id" id="transaksiId">
            
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i> Anda akan mengubah status hutang menjadi <strong>LUNAS</strong>. Tindakan ini akan mencatat pembayaran penuh atas sisa hutang.
            </div>
            
            <div class="mb-3">
              <label class="form-label">Total Hutang yang Akan Dilunasi</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control" id="transaksiTotalHutang" disabled>
              </div>
            </div>
            
            <div class="mb-3">
              <label for="tanggal_bayar" class="form-label">Tanggal Pelunasan</label>
              <input type="date" class="form-control" name="tanggal_bayar" required value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="mb-3">
              <label for="keterangan" class="form-label">Keterangan Pelunasan</label>
              <textarea class="form-control" name="keterangan" rows="3" placeholder="Contoh: Pelunasan hutang manual oleh manager">Pelunasan manual</textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Update Status Menjadi Lunas</button>
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
      $('#hutangProdukTable').DataTable({
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
      
      $('#piutangTransaksiTable').DataTable({
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
      
      // Auto dismiss alerts
      setTimeout(function() {
        $('.alert').alert('close');
      }, 5000);
      
      // Update Hutang Transaksi Modal
      $('.update-hutang-transaksi').on('click', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        const hutang = $(this).data('hutang');
        
        $('#transaksiId').val(id);
        $('#transaksiNama').text(nama);
        $('#transaksiTotalHutang').val(formatRupiah(hutang));
        $('#updateHutangTransaksiModal').modal('show');
      });
      
      // Format number to Rupiah
      function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
      }
    });
  </script>
</body>
</html>