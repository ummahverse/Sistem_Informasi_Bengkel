<?php
session_start();

// Check if logged in and admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config.php'; // Database connection

// Initialize variables
$bulan = date('Y-m'); // Default to current month
$laba_kotor = 0;
$laba_bersih = 0;
$errors = [];
$pengeluaran_items = [['nama' => '', 'jumlah' => 0]]; // Initialize with one empty item

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $bulan = isset($_POST['bulan']) ? $_POST['bulan'] : '';
    $laba_kotor = isset($_POST['laba_kotor']) ? floatval($_POST['laba_kotor']) : 0;
    
    // Process pengeluaran items
    $pengeluaran_items = [];
    $total_pengeluaran = 0;
    
    // Process dynamic pengeluaran items
    if (isset($_POST['pengeluaran_nama']) && is_array($_POST['pengeluaran_nama'])) {
        for ($i = 0; $i < count($_POST['pengeluaran_nama']); $i++) {
            if (!empty($_POST['pengeluaran_nama'][$i]) && isset($_POST['pengeluaran_jumlah'][$i])) {
                $nama = $_POST['pengeluaran_nama'][$i];
                $jumlah = floatval($_POST['pengeluaran_jumlah'][$i]);
                
                $pengeluaran_items[] = [
                    'nama' => $nama,
                    'jumlah' => $jumlah
                ];
                
                $total_pengeluaran += $jumlah;
            }
        }
    }
    
    // Calculate net profit
    $laba_bersih = $laba_kotor - $total_pengeluaran;
    
    // Validate bulan (required)
    if (empty($bulan)) {
        $errors[] = "Bulan harus dipilih";
    }
    
    // Check if the month data already exists
    $check_query = "SELECT id FROM laba_bersih WHERE bulan = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, 's', $bulan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Data laba bersih untuk bulan ini sudah ada. Silakan edit data yang ada.";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert main record
            $insert_query = "INSERT INTO laba_bersih (bulan, laba_kotor, pengeluaran, laba_bersih) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, 'sddd', $bulan, $laba_kotor, $total_pengeluaran, $laba_bersih);
            mysqli_stmt_execute($stmt);
            
            // Get the last inserted ID
            $laba_bersih_id = mysqli_insert_id($conn);
            
            // Insert pengeluaran details
            if (!empty($pengeluaran_items)) {
                $detail_query = "INSERT INTO pengeluaran_detail (laba_bersih_id, nama_pengeluaran, jumlah) VALUES (?, ?, ?)";
                $detail_stmt = mysqli_prepare($conn, $detail_query);
                
                foreach ($pengeluaran_items as $item) {
                    if (!empty($item['nama'])) {
                        mysqli_stmt_bind_param($detail_stmt, 'isd', $laba_bersih_id, $item['nama'], $item['jumlah']);
                        mysqli_stmt_execute($detail_stmt);
                    }
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['message'] = "Data laba bersih berhasil disimpan!";
            $_SESSION['alert_type'] = "success";
            header("Location: laba_bersih.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

// Get last month's revenue data to help calculate gross profit (if needed)
$last_month = date('Y-m', strtotime('-1 month'));
$revenue_query = "
    SELECT SUM(td.subtotal) AS total_pendapatan
    FROM transaksi t
    JOIN transaksi_detail td ON t.id = td.transaksi_id
    WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?
";
$stmt = mysqli_prepare($conn, $revenue_query);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$revenue_result = mysqli_stmt_get_result($stmt);
$revenue_data = mysqli_fetch_assoc($revenue_result);
$total_pendapatan = $revenue_data['total_pendapatan'] ?? 0;

// Get COGS data to help calculate gross profit (if needed)
$cogs_query = "
    SELECT SUM(td.jumlah * p.harga_beli) AS total_cogs
    FROM transaksi t
    JOIN transaksi_detail td ON t.id = td.transaksi_id
    JOIN produk p ON td.produk_id = p.id
    WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?
";
$stmt = mysqli_prepare($conn, $cogs_query);
mysqli_stmt_bind_param($stmt, 's', $bulan);
mysqli_stmt_execute($stmt);
$cogs_result = mysqli_stmt_get_result($stmt);
$cogs_data = mysqli_fetch_assoc($cogs_result);
$total_cogs = $cogs_data['total_cogs'] ?? 0;

// Calculate suggested gross profit
$suggested_laba_kotor = $total_pendapatan - $total_cogs;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Data Laba Bersih - Kasir Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-blue: #1565C0;
      --secondary-blue: #42A5F5;
      --light-blue: #E3F2FD;
      --accent-blue: #0D47A1;
      --white: #ffffff;
      --light-gray: #f8f9fa;
      --text-dark: #2C3E50;
    }
    
    body {
      background-color: var(--light-gray);
      font-family: 'Poppins', 'Arial', sans-serif;
      color: var(--text-dark);
    }
    
    /* Navbar Styling */
    .navbar {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: none;
    padding: 0.8rem 1.5rem;
    z-index: 1020; /* Lower than sidebar but higher than content */
  }
    
  .navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--white);
    letter-spacing: 0.5px;
  }

  /* Content area with adjusted padding */
  .content {
    margin-left: 280px;
    padding: 20px;
    transition: all 0.3s;
    padding-top: 20px; /* Add padding to prevent content from being hidden under navbar */
  }
    
    /* Card styling */
    .card {
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      border-radius: 10px;
      margin-bottom: 1.5rem;
    }
    
    .card-header {
      background-color: var(--primary-blue);
      color: var(--white);
      border-radius: 10px 10px 0 0 !important;
      font-weight: 600;
      padding: 1rem 1.5rem;
    }
    
    .card-body {
      padding: 1.5rem;
    }
    
    /* Form styling */
    .form-label {
      font-weight: 500;
      margin-bottom: 0.5rem;
    }
    
    .form-control {
      border-radius: 6px;
      border: 1px solid #e0e0e0;
      padding: 0.6rem 1rem;
      transition: all 0.2s;
    }
    
    .form-control:focus {
      border-color: var(--secondary-blue);
      box-shadow: 0 0 0 0.25rem rgba(66, 165, 245, 0.25);
    }
    
    /* Button styling */
    .btn-primary {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 6px;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      background-color: var(--accent-blue);
      border-color: var(--accent-blue);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Expenditure item styling */
    .pengeluaran-item {
      background-color: var(--light-blue);
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 10px;
      position: relative;
    }

    .remove-item {
      position: absolute;
      top: 10px;
      right: 10px;
      cursor: pointer;
      color: #dc3545;
    }

    .add-item-btn {
      margin-bottom: 20px;
    }
    
    /* Alert styling */
    .alert {
      border-radius: 8px;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    /* Sidebar (assuming there is one in the layout) */
    .sidebar {
      width: 260px;
      position: fixed;
      top: 62px;
      left: 0;
      bottom: 0;
      background-color: var(--white);
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
      padding: 1.5rem 1rem;
      overflow-y: auto;
      z-index: 100;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
    .content {
      margin-left: 0;
      padding-left: 70px; /* Add padding to prevent content from being hidden under toggle button */
    }
    
    .navbar {
      padding-left: 70px; /* Make space for the sidebar toggle button */
    }
    
    .navbar-brand {
      font-size: 1.2rem; /* Slightly smaller text on mobile */
    }
  }
  
  @media (max-width: 576px) {
    .navbar-brand-wrapper {
      max-width: 180px; /* Limit width on very small screens */
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
</style>
</head>
<body>

 <!-- Sidebar -->
 <?php include 'sidebar.php'; ?>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <div class="navbar-brand-wrapper">
      <span class="navbar-brand">
      </span>
    </div>
    <div class="d-flex align-items-center">
      <span class="text-white me-3 d-none d-md-inline">
        <i class="fas fa-user-circle me-1"></i>
        <?= htmlspecialchars($_SESSION['nama']) ?>
      </span>
      <div class="btn-group">
        <a href="profile.php" class="btn btn-outline-light btn-sm me-2">
          <i class="fas fa-user-edit me-1"></i><span class="d-none d-md-inline">Profil</span>
        </a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">
          <i class="fas fa-sign-out-alt me-1"></i><span class="d-none d-md-inline">Logout</span>
        </a>
      </div>
    </div>
  </div>
</nav>
  
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item"><a href="laba_bersih.php">Laba Bersih</a></li>
              <li class="breadcrumb-item active" aria-current="page">Tambah Data</li>
            </ol>
          </nav>
        </div>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Data Laba Bersih</h5>
            </div>
            <div class="card-body">
              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                      <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              
              <form method="POST" id="labaBersihForm">
                <div class="mb-3">
                  <label for="bulan" class="form-label">Bulan</label>
                  <input type="month" class="form-control" id="bulan" name="bulan" value="<?php echo htmlspecialchars($bulan); ?>" required>
                  <small class="text-muted">Pilih bulan dan tahun untuk laporan laba bersih</small>
                </div>
                
                <div class="mb-4">
    <label for="laba_kotor" class="form-label">Laba Kotor (Rp)</label>
    <input type="text" class="form-control" id="laba_kotor" name="laba_kotor" value="<?php echo number_format($laba_kotor, 0, ',', '.'); ?>" required oninput="formatRupiah(this)">
    <?php if ($suggested_laba_kotor > 0): ?>
        <small class="text-success">
            Berdasarkan data transaksi bulan ini, laba kotor yang disarankan adalah: Rp <?php echo number_format($suggested_laba_kotor, 0, ',', '.'); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="document.getElementById('laba_kotor').value='<?php echo $suggested_laba_kotor; ?>'">
                Gunakan
            </button>
        </small>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header bg-light text-dark">
        <h6 class="mb-0"><i class="fas fa-list-alt me-2"></i>Detail Pengeluaran</h6>
    </div>
    <div class="card-body">
        <div id="pengeluaran-container">
            <!-- Initial pengeluaran item will be added here by JavaScript -->
        </div>

        <button type="button" class="btn btn-success add-item-btn" id="add-pengeluaran">
            <i class="fas fa-plus-circle me-2"></i>Tambah Item Pengeluaran
        </button>

        <div class="alert alert-info mt-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2 fs-4"></i>
                <div>
                    <strong>Total Pengeluaran:</strong> Rp <span id="total-pengeluaran-display">0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <label class="form-label">Laba Bersih (Rp)</label>
    <div class="input-group">
        <input type="text" class="form-control" id="laba_bersih_display" value="<?php echo number_format($laba_bersih, 0, ',', '.'); ?>" readonly>
        <span class="input-group-text bg-light">Otomatis dihitung</span>
    </div>
    <small class="text-muted">Laba Bersih = Laba Kotor - Total Pengeluaran</small>
</div>

<div class="d-flex justify-content-between mt-4">
    <a href="laba_bersih.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Simpan Data
    </button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
</div>

<script>
// Fungsi untuk memformat angka menjadi format rupiah
function formatRupiah(input) {
    // Menghilangkan semua karakter non-digit selain titik
    let value = input.value.replace(/\D/g, '');

    // Memformat angka dengan titik sebagai pemisah ribuan
    let formattedValue = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Mengupdate nilai input dengan format rupiah
    input.value = formattedValue;
}
</script>


  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add initial item
      addPengeluaranItem();
      
      // Update calculations
      updateCalculations();
      
      // Add event listener for adding new item button
      document.getElementById('add-pengeluaran').addEventListener('click', function() {
        addPengeluaranItem();
      });
      
      // Add event listener for laba kotor changes
      document.getElementById('laba_kotor').addEventListener('input', updateCalculations);
    });
    
    // Counter for unique IDs
    let itemCounter = 0;
    
    // Function to add a new pengeluaran item
    function addPengeluaranItem() {
      itemCounter++;
      const container = document.getElementById('pengeluaran-container');
      
      const itemDiv = document.createElement('div');
      itemDiv.className = 'pengeluaran-item';
      itemDiv.id = `item-${itemCounter}`;
      
      itemDiv.innerHTML = `
        <div class="row">
          <div class="col-md-6 mb-2">
            <label class="form-label">Nama Pengeluaran</label>
            <input type="text" class="form-control pengeluaran-nama" name="pengeluaran_nama[]" placeholder="Contoh: Gaji Karyawan, Sewa Tempat, dll" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Jumlah (Rp)</label>
            <input type="number" step="0.01" class="form-control pengeluaran-jumlah" name="pengeluaran_jumlah[]" placeholder="0" value="0" required onchange="updateCalculations()">
          </div>
        </div>
        <span class="remove-item" onclick="removePengeluaranItem(${itemCounter})">
          <i class="fas fa-times-circle"></i> Hapus
        </span>
      `;
      
      container.appendChild(itemDiv);
    }
    
    // Function to remove a pengeluaran item
    function removePengeluaranItem(id) {
      const item = document.getElementById(`item-${id}`);
      
      // Don't remove if it's the last remaining item
      const allItems = document.getElementsByClassName('pengeluaran-item');
      if (allItems.length <= 1) {
        alert('Anda harus memiliki minimal satu item pengeluaran.');
        return;
      }
      
      if (item) {
        item.remove();
        updateCalculations();
      }
    }
    
    // Function to calculate totals
    function updateCalculations() {
      const labaKotor = parseFloat(document.getElementById('laba_kotor').value) || 0;
      
      // Calculate total pengeluaran
      let totalPengeluaran = 0;
      const pengeluaranItems = document.getElementsByClassName('pengeluaran-jumlah');
      
      for (let item of pengeluaranItems) {
        const amount = parseFloat(item.value) || 0;
        totalPengeluaran += amount;
      }
      
      // Calculate laba bersih
      const labaBersih = labaKotor - totalPengeluaran;
      
      // Update displays with formatted numbers
      document.getElementById('total-pengeluaran-display').textContent = new Intl.NumberFormat('id-ID').format(totalPengeluaran);
      document.getElementById('laba_bersih_display').value = new Intl.NumberFormat('id-ID').format(labaBersih);
    }
  </script>
</body>
</html>