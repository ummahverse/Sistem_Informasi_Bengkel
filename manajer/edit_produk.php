<?php
session_start();

// Check if logged in and manajer
if (!isset($_SESSION['manajer']['logged_in']) || $_SESSION['manajer']['logged_in'] !== true) {
  header("Location: ../login.php");
  exit();
}

include '../config.php'; // Database connection

// Check if id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION['message'] = "ID Produk tidak ditemukan!";
  $_SESSION['alert_type'] = "danger";
  header("Location: produk.php");
  exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch product data
$query = "SELECT * FROM produk WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
  $_SESSION['message'] = "Produk tidak ditemukan!";
  $_SESSION['alert_type'] = "danger";
  header("Location: produk.php");
  exit();
}

$row = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $harga_beli = mysqli_real_escape_string($conn, $_POST['harga_beli']);
    $harga_jual = mysqli_real_escape_string($conn, $_POST['harga_jual']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $kategori_id = mysqli_real_escape_string($conn, $_POST['kategori_id']);
    $hutang_sparepart = mysqli_real_escape_string($conn, $_POST['hutang_sparepart']);
    $nominal_hutang = 0;
    
    if ($hutang_sparepart === 'Hutang') {
        $nominal_hutang = mysqli_real_escape_string($conn, $_POST['nominal_hutang']);
    }
    
    // Update database
    $update_query = "UPDATE produk SET 
                    nama = '$nama', 
                    harga_beli = '$harga_beli', 
                    harga_jual = '$harga_jual', 
                    stok = '$stok', 
                    kategori_id = '$kategori_id',
                    hutang_sparepart = '$hutang_sparepart',
                    nominal_hutang = '$nominal_hutang'
                    WHERE id = '$id'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Produk berhasil diperbarui!";
        $_SESSION['alert_type'] = "success";
        header("Location: produk.php");
        exit();
    } else {
        $_SESSION['message'] = "Gagal memperbarui produk: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }
}

// Get all categories
$kategori_query = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$kategori_result = mysqli_query($conn, $kategori_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Produk - BMS Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      margin-left: 280px;
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
    
    /* Form card */
    .form-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      background-color: var(--white);
      padding: 1.5rem;
    }
    
    /* Form section header */
    .form-section-header {
      border-bottom: 2px solid var(--light-orange);
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .form-section-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary-orange);
      margin-bottom: 0;
    }
    
    /* Form group styling */
    .form-label {
      font-weight: 600;
      color: var(--text-dark);
    }
    
    .form-control, .form-select {
      border-radius: 8px;
      border: 1px solid #e0e6ed;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--secondary-orange);
      box-shadow: 0 0 0 0.25rem rgba(239, 108, 0, 0.25);
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
    
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
    }
    
    /* Required field indicator */
    .required-field::after {
      content: "*";
      color: #dc3545;
      margin-left: 4px;
    }
    
    /* Form text helper */
    .form-text {
      font-size: 0.8rem;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
      .content {
        margin-left: 0;
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
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
      <div class="container-fluid">
        <span class="navbar-brand">
          <i class="fas fa-edit me-2"></i>
          Edit Produk
        </span>
        <div class="d-flex align-items-center">
        
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
        <div class="d-flex align-items-center">
          <div>
            <h1><i class="fas fa-edit me-2"></i>Edit Produk</h1>
            <p class="lead mb-0">Perbarui informasi produk dalam sistem kasir bengkel.</p>
          </div>
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

      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="form-card">
            <div class="form-section-header">
              <h5 class="form-section-title">
                <i class="fas fa-box me-2"></i>
                Edit Produk: <?= htmlspecialchars($row['nama']) ?>
              </h5>
            </div>
            
            <form method="POST" action="">
              <div class="mb-3">
                <label for="nama" class="form-label required-field">Nama Produk</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required>
                <div class="form-text">Masukkan nama lengkap produk.</div>
              </div>
              
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="harga_beli" class="form-label required-field">Harga Beli</label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="harga_beli" name="harga_beli" min="0" value="<?= $row['harga_beli'] ?>" required>
                  </div>
                  <div class="form-text">Harga pembelian produk dari supplier.</div>
                </div>
                
                <div class="col-md-6">
                  <label for="harga_jual" class="form-label required-field">Harga Jual</label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="harga_jual" name="harga_jual" min="0" value="<?= $row['harga_jual'] ?>" required>
                  </div>
                  <div class="form-text">Harga penjualan produk ke pelanggan.</div>
                </div>
              </div>
              
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="stok" class="form-label required-field">Stok</label>
                  <input type="number" class="form-control" id="stok" name="stok" min="0" value="<?= $row['stok'] ?>" required>
                  <div class="form-text">Jumlah barang yang tersedia.</div>
                </div>
                
                <div class="col-md-6">
                  <label for="kategori_id" class="form-label">Kategori</label>
                  <select class="form-select" id="kategori_id" name="kategori_id">
                    <option value="">Pilih Kategori</option>
                    <?php while ($kategori = mysqli_fetch_assoc($kategori_result)): ?>
                      <option value="<?= $kategori['id'] ?>" <?= $row['kategori_id'] == $kategori['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                  <div class="form-text">Pilih kategori yang sesuai untuk produk ini.</div>
                </div>
              </div>
              
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="hutang_sparepart" class="form-label required-field">Status Pembayaran</label>
                  <select class="form-select" id="hutang_sparepart" name="hutang_sparepart" required>
                    <option value="Cash" <?= $row['hutang_sparepart'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Hutang" <?= $row['hutang_sparepart'] == 'Hutang' ? 'selected' : '' ?>>Hutang</option>
                  </select>
                  <div class="form-text">Pilih status pembayaran untuk sparepart ini.</div>
                </div>
                
                <div class="col-md-6" id="nominal_hutang_container" style="display: <?= $row['hutang_sparepart'] == 'Hutang' ? 'block' : 'none' ?>;">
                  <label for="nominal_hutang" class="form-label">Nominal Hutang</label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" id="nominal_hutang" name="nominal_hutang" 
                           value="<?= $row['nominal_hutang'] ?>" min="0" step="1000">
                  </div>
                  <div class="form-text">Masukkan nominal hutang untuk sparepart ini.</div>
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-between">
                <a href="produk.php" class="btn btn-secondary">
                  <i class="fas fa-arrow-left me-1"></i>
                  Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i>
                  Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  
  <script>
    // Script untuk menampilkan/menyembunyikan field nominal hutang
    document.getElementById('hutang_sparepart').addEventListener('change', function() {
      var nominalHutangContainer = document.getElementById('nominal_hutang_container');
      if (this.value === 'Hutang') {
        nominalHutangContainer.style.display = 'block';
      } else {
        nominalHutangContainer.style.display = 'none';
        document.getElementById('nominal_hutang').value = '0';
      }
    });
    
    // Auto close alerts after 5 seconds
    setTimeout(function() {
      $(".alert").alert('close');
    }, 5000);
  </script>
</body>
</html>